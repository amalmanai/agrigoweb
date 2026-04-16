<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\HistoriqueIrrigationRepository;
use App\Repository\SystemeIrrigationRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class RapportPDFService
{
    public function __construct(
        private readonly HistoriqueIrrigationRepository $historiqueRepository,
        private readonly SystemeIrrigationRepository $systemeRepository,
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
        private readonly SMSService $smsService,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%env(APP_SECRET)%')]
        private readonly string $appSecret,
        #[Autowire('%env(default::MAILER_FROM_ADDRESS)%')]
        private readonly ?string $mailerFromAddress,
        #[Autowire('%env(default::MAILER_FORCE_TO)%')]
        private readonly ?string $mailerForceTo,
        #[Autowire('%env(default::APP_PUBLIC_BASE_URL)%')]
        private readonly ?string $appPublicBaseUrl,
    ) {
    }

    /**
     * @return array{file_name:string, file_path:string, sent_mail:bool, mail_error:?string, sent_sms:bool, sms_error:?string, auto_sms_count:int}
     */
    public function generateForUser(
        User $user,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        bool $sendMail,
        bool $sendSms,
    ): array {
        $historiques = $this->historiqueRepository->findByOwnerAndPeriod($user, $periodStart, $periodEnd);

        $html = $this->twig->render('systeme_irrigation/rapport_pdf.html.twig', [
            'user' => $user,
            'generated_at' => new \DateTimeImmutable(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'historiques' => $historiques,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $reportsDir = $this->projectDir.'\\public\\rapports';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0775, true);
        }

        $fileName = sprintf(
            'rapport_irrigation_%d_%s_%s.pdf',
            (int) ($user->getIdUser() ?? 0),
            (new \DateTimeImmutable())->format('Ymd_His'),
            bin2hex(random_bytes(4))
        );
        $filePath = $reportsDir.'\\'.$fileName;
        file_put_contents($filePath, $dompdf->output());

        $expiresAt = new \DateTimeImmutable('+24 hours');

        $sentMail = false;
        $mailError = null;
        if ($sendMail && null !== $user->getEmailUser() && '' !== trim($user->getEmailUser())) {
            $fromAddress = $this->resolveFromAddress();

            $email = (new TemplatedEmail())
                ->from($fromAddress)
                ->to($this->resolveToAddress((string) $user->getEmailUser()))
                ->subject('Votre rapport AgriGo — '.$periodStart->format('d/m/Y').' au '.$periodEnd->format('d/m/Y'))
                ->htmlTemplate('systeme_irrigation/rapport_email.html.twig')
                ->context([
                    'user' => $user,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'download_url' => '',
                ])
                ->attachFromPath($filePath, $fileName, 'application/pdf');

            try {
                $this->mailer->send($email);
                $sentMail = true;
            } catch (TransportExceptionInterface $e) {
                $mailError = $e->getMessage();
            }
        }

        $sentSms = false;
        $smsError = null;
        $autoSmsCount = 0;
        if ($sendSms) {
            $phone = $this->normalizePhone($user->getNumUser());
            error_log("Report SMS: User ID {$user->getIdUser()}, Raw phone: {$user->getNumUser()}, Normalized: {$phone}");
            if (null !== $phone) {
                $autoSmsCount = $this->sendAutomaticIrrigationAlerts($user, $phone);

                // Send PDF report SMS notification
                $smsBody = '📎 Rapport PDF sent to your email - Check it!';
                $textResult = $this->smsService->sendWithStatus($phone, $smsBody);
                $sentSms = $textResult['success'];
                $smsError = $textResult['error'];
            } else {
                $smsError = 'Numéro utilisateur introuvable ou invalide';
            }
        }

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'sent_mail' => $sentMail,
            'mail_error' => $mailError,
            'sent_sms' => $sentSms,
            'sms_error' => $smsError,
            'auto_sms_count' => $autoSmsCount,
        ];
    }

    public function isDownloadSignatureValid(string $fileName, int $expires, string $signature): bool
    {
        if ($expires < time()) {
            return false;
        }

        return hash_equals($this->sign($fileName, $expires), $signature);
    }

    private function sign(string $fileName, int $expires): string
    {
        return hash_hmac('sha256', $fileName.'|'.$expires, $this->appSecret);
    }

    private function normalizePhone(?int $numUser): ?string
    {
        if (null === $numUser) {
            return null;
        }

        $raw = trim((string) $numUser);
        $digits = preg_replace('/\D+/', '', $raw);
        if (null === $digits || '' === $digits) {
            return null;
        }

        // Keep international format when country code is already present.
        if (strlen($digits) > 8) {
            return '+'.$digits;
        }

        // Local 8-digit numbers are treated as Tunisian numbers.
        if (8 === strlen($digits)) {
            return '+216'.$digits;
        }

        return null;
    }

    private function sendAutomaticIrrigationAlerts(User $user, string $phone): int
    {
        $count = 0;
        $activeSystems = $this->systemeRepository->findActiveByOwner($user);

        foreach ($activeSystems as $systeme) {
            $last = $this->historiqueRepository->findLatestForSysteme($systeme);

            $threshold = $systeme->getSeuilHumidite();
            $thresholdFloat = (null !== $threshold && '' !== $threshold) ? (float) $threshold : null;
            if ($last && null !== $thresholdFloat) {
                $humiditeAvant = $last->getHumiditeAvant();
                if (null !== $humiditeAvant && '' !== $humiditeAvant && (float) $humiditeAvant < $thresholdFloat) {
                    $ok = $this->smsService->sendWithStatus(
                        $phone,
                        sprintf('Humidité critique sur %s', (string) ($systeme->getNomSysteme() ?? 'système'))
                    )['success'];
                    if (true === $ok) {
                        ++$count;
                    }
                }
            }

            if (!$last || $last->getDateIrrigation() < new \DateTime('-3 days')) {
                $ok = $this->smsService->sendWithStatus(
                    $phone,
                    sprintf('Vérifiez le système %s', (string) ($systeme->getNomSysteme() ?? 'système'))
                )['success'];
                if (true === $ok) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    private function resolveFromAddress(): string
    {
        $configured = trim((string) $this->mailerFromAddress);
        if ('' !== $configured) {
            return $configured;
        }

        return 'no-reply@example.org';
    }

    private function resolveToAddress(string $userEmail): string
    {
        $forced = trim((string) $this->mailerForceTo);
        if ('' !== $forced) {
            return $forced;
        }

        return $userEmail;
    }

    private function buildPublicUrl(string $absoluteUrl, string $path): string
    {
        $publicBase = trim((string) $this->appPublicBaseUrl);
        if ('' !== $publicBase) {
            return rtrim($publicBase, '/').$path;
        }

        return $absoluteUrl;
    }

    private function buildLanFallbackUrl(string $path): ?string
    {
        $host = gethostbyname(gethostname());
        if (filter_var($host, FILTER_VALIDATE_IP) &&
            (str_starts_with($host, '192.168.') || str_starts_with($host, '10.') || str_starts_with($host, '172.16.'))) {
            return 'http://'.$host.':8000'.$path;
        }

        return null;
    }

}