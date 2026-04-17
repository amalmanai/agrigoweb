<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\RapportPDFService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class IrrigationRapportController extends AbstractController
{
    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route(path: '/systeme-irrigation/rapport', name: 'front_systeme_irrigation_rapport', methods: ['GET', 'POST'])]
    public function index(Request $request, RapportPDFService $rapportService): Response
    {
        $user = $this->requireUser();

        $startDefault = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
        $endDefault = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $result = null;
        if ($request->isMethod('POST')) {
            $startRaw = $request->request->getString('period_start', $startDefault);
            $endRaw = $request->request->getString('period_end', $endDefault);
            $periodStart = new \DateTimeImmutable($startRaw.' 00:00:00');
            $periodEnd = new \DateTimeImmutable($endRaw.' 23:59:59');

            if ($periodStart > $periodEnd) {
                [$periodStart, $periodEnd] = [$periodEnd, $periodStart];
            }

            $sendMail = $request->request->getBoolean('send_mail', false);
            $sendSms = $request->request->getBoolean('send_sms', false);

            $result = $rapportService->generateForUser($user, $periodStart, $periodEnd, $sendMail, $sendSms);
            $this->addFlash('success', 'Rapport généré avec succès.');
        }

        return $this->render('systeme_irrigation/rapport.html.twig', [
            'layout' => 'front/base.html.twig',
            'period_start_default' => $startDefault,
            'period_end_default' => $endDefault,
            'result' => $result,
        ]);
    }

    #[Route(path: '/systeme-irrigation/rapport/download/{file}/{expires}/{sig}', name: 'front_systeme_irrigation_rapport_download', methods: ['GET'])]
    public function download(string $file, int $expires, string $sig, RapportPDFService $rapportService): Response
    {
        if (!$rapportService->isDownloadSignatureValid($file, $expires, $sig)) {
            throw $this->createAccessDeniedException('Lien expiré ou invalide.');
        }

        $safeFile = basename($file);
        $path = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'rapports'.DIRECTORY_SEPARATOR.$safeFile;
        if (!is_file($path)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $safeFile);

        return $response;
    }
}