<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class VerificationCodeService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Génère un code de vérification aléatoire de 6 chiffres
     */
    public function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Envoie un email avec le code de vérification pour la réinitialisation du mot de passe
     */
    public function sendPasswordResetCode(string $email, string $code, string $resetToken): bool
    {
        try {
            $resetUrl = $this->urlGenerator->generate('app_reset_password', [
                'token' => $resetToken
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // Créer un email HTML simple sans template Twig pour éviter les erreurs
            $emailContent = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>AgriGo - Code de vérification</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
    <h1 style='color: #28a745;'>🌿 AgriGo</h1>
    <h2>Réinitialisation de votre mot de passe</h2>
    <p>Bonjour,</p>
    <p>Pour réinitialiser votre mot de passe, veuillez utiliser le code de vérification suivant :</p>
    <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; border-radius: 5px; border: 1px dashed #28a745; color: #28a745; margin: 20px 0;'>
        $code
    </div>
    <p>Ce code expirera dans 15 minutes.</p>
    <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
    <p>Cordialement,<br>L'équipe Agrigo</p>
</body>
</html>";

            $emailMessage = (new Email())
                ->from('amalmanai658@gmail.com')
                ->to($email)
                ->subject('AgriGo - Code de vérification pour réinitialisation du mot de passe')
                ->html($emailContent);

            $this->mailer->send($emailMessage);
            
            // Log succès pour debug
            error_log("Email envoyé avec succès à $email. Code: $code");
            return true;
            
        } catch (\Exception $e) {
            // Log erreur détaillée pour debug
            error_log("Erreur envoi email: " . $e->getMessage());
            
            // Retourner false pour déclencher le mode test
            return false;
        }
    }

    /**
     * Vérifie si un code de 6 chiffres est valide
     */
    public function isValidCode(string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1;
    }
}
