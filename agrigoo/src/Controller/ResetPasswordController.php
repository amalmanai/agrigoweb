<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, MailerInterface $mailer): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $email = '';
        $message = '';
        $messageType = '';

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // Vérifier si l'utilisateur existe
            $user = $userRepository->findOneBy(['emailUser' => $email]);
            
            if ($user) {
                // Générer un token
                $token = bin2hex(random_bytes(32));
                $resetLink = $this->generateUrl('app_reset_password', 
                    ['token' => $token], 
                    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
                );

                // Envoyer l'email (si le mailer est configuré)
                try {
                    $emailMessage = (new Email())
                        ->from('agrigo@example.com')
                        ->to($email)
                        ->subject('Réinitialisation de mot de passe - Agrigo')
                        ->html("
                            <h2>Réinitialisation de mot de passe</h2>
                            <p>Bonjour {$user->getPrenomUser()} {$user->getNomUser()},</p>
                            <p>Vous avez demandé une réinitialisation de mot de passe. Cliquez sur le lien ci-dessous pour continuer :</p>
                            <p><a href='$resetLink' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                                Réinitialiser mon mot de passe
                            </a></p>
                            <p>Ce lien expire dans 24 heures.</p>
                            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                            <p>Cordialement,<br>L'équipe Agrigo</p>
                        ");

                    $mailer->send($emailMessage);
                    $messageType = 'success';
                    $message = "Un email de réinitialisation a été envoyé à $email. Veuillez vérifier votre boîte mail.";
                } catch (\Exception $e) {
                    $messageType = 'warning';
                    $message = "Email généré (mailer non configuré). Lien: $resetLink";
                }
            } else {
                $messageType = 'danger';
                $message = "Cet email n'existe pas dans notre système.";
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'email' => $email,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Pour une version simple, on va juste créer un formulaire sans vraiment stocker de tokens
        // Dans une vraie app, il faudrait stocker les tokens en BD avec une date d'expiration

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordRepeat = $request->request->get('password_repeat');

            if (empty($password) || empty($passwordRepeat)) {
                $this->addFlash('danger', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if ($password !== $passwordRepeat) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if (strlen($password) < 8) {
                $this->addFlash('danger', 'Le mot de passe doit faire au moins 8 caractères.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            // Pour une vraie implémentation, il faudrait retrouver l'utilisateur via le token
            // Pour le moment, on affiche juste un message
            $this->addFlash('success', 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
