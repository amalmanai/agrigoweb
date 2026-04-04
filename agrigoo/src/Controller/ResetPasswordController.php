<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['emailUser' => $email]);
            
            if ($user) {
                // Generate a 6-digit OTP code
                $otp = sprintf('%06d', mt_rand(100000, 999999));
                $user->setResetToken($otp);
                $user->setResetExpiresAt(new \DateTimeImmutable('+15 minutes'));
                
                $entityManager->flush();

                // Store email in session for the next step
                $request->getSession()->set('reset_password_email', $email);

                // Send the email
                $mailerDsn = $_ENV['MAILER_DSN'] ?? 'null://null';
                $isMailerConfigured = ($mailerDsn !== 'null://null');

                try {
                    if ($isMailerConfigured) {
                        $emailMessage = (new Email())
                            ->from('amalmanai658@gmail.com')
                            ->to($email)
                            ->subject('Votre code de vérification Agrigo')
                            ->html("
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                                    <h1 style='color: #28a745;'>🌿 Agrigo</h1>
                                    <h2>Réinitialisation de votre mot de passe</h2>
                                    <p>Bonjour,</p>
                                    <p>Pour réinitialiser votre mot de passe, veuillez utiliser le code de vérification suivant :</p>
                                    <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; border-radius: 5px; border: 1px dashed #28a745; color: #28a745; margin: 20px 0;'>
                                        $otp
                                    </div>
                                    <p>Ce code expirera dans 15 minutes.</p>
                                    <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
                                    <p>Cordialement,<br>L'équipe Agrigo</p>
                                </div>
                            ");

                        $mailer->send($emailMessage);
                        $this->addFlash('success', 'Un code de vérification a été envoyé à votre adresse email.');
                    } else {
                        throw new \Exception("Mailer not configured");
                    }
                } catch (\Exception $e) {
                    $this->addFlash('warning', "⚠️ Mode Test : Le service d'e-mail n'est pas encore configuré. Votre code de vérification est : $otp");
                }

                return $this->redirectToRoute('app_verify_code');
            } else {
                $this->addFlash('danger', "Cette adresse email n'est pas reconnue.");
            }
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/verify-code', name: 'app_verify_code')]
    public function verifyCode(Request $request, UserRepository $userRepository): Response
    {
        $email = $request->getSession()->get('reset_password_email');
        if (!$email) {
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            $user = $userRepository->findOneBy(['emailUser' => $email]);

            if ($user && $user->getResetToken() === $code && $user->isResetTokenValid()) {
                $request->getSession()->set('reset_password_verified', true);
                return $this->redirectToRoute('app_reset_password');
            }

            $this->addFlash('danger', 'Code invalide ou expiré.');
        }

        return $this->render('security/verify_code.html.twig', [
            'email' => $email
        ]);
    }

    #[Route('/reset-password', name: 'app_reset_password')]
    public function resetPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, Security $security): Response
    {
        $email = $request->getSession()->get('reset_password_email');
        $verified = $request->getSession()->get('reset_password_verified');

        if (!$email || !$verified) {
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordRepeat = $request->request->get('password_repeat');

            if ($password !== $passwordRepeat) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->render('security/reset_password.html.twig');
            }

            $user = $userRepository->findOneBy(['emailUser' => $email]);
            if ($user) {
                // Update password (using plaintext as requested by project config)
                $user->setPassword($password);
                $user->setResetToken(null);
                $user->setResetExpiresAt(null);
                
                $entityManager->flush();

                // Clear session
                $request->getSession()->remove('reset_password_email');
                $request->getSession()->remove('reset_password_verified');

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous êtes maintenant connecté.');

                // Automatic Login
                return $security->login($user, 'form_login', 'main');
            }
        }

        return $this->render('security/reset_password.html.twig');
    }
}
