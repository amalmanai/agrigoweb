<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
             return $this->redirectToRoute('app_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $session = $request->getSession();
        $failures = $session->get('login_failures', 0);

        // Generate Random String Captcha
        $randomString = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5);
        $session->set('captcha_answer', $randomString);

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'captcha_question' => $randomString,
            'app_name' => 'Agrigo',
            'failure_count' => $failures,
        ]);
    }

    #[Route(path: '/refresh-captcha', name: 'app_captcha_refresh', methods: ['GET'])]
    public function refreshCaptcha(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $randomString = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5);
        $session->set('captcha_answer', $randomString);

        return new JsonResponse(['captcha' => $randomString]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/login/qr', name: 'api_login_qr', methods: ['POST'])]
    public function loginQr(Request $request, UserRepository $userRepository, Security $security): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun QR Code fourni.'], 400);
        }

        // Parse token format: AGRIGO-USER:{id}:{email}
        if (str_starts_with($token, 'AGRIGO-USER:')) {
            $parts = explode(':', $token);
            if (count($parts) >= 3) {
                $email = $parts[2];
                $user = $userRepository->findOneBy(['emailUser' => $email]);
            } else {
                $user = null;
            }
        } else {
            // Fallback for old loginTokens
            $user = $userRepository->findOneBy(['loginToken' => $token]);
        }

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Ce QR Code est invalide ou expiré.'], 404);
        }

           if (!$user->isActive() || $user->getBadWordCommentStrikes() >= 3) {
             return new JsonResponse(['success' => false, 'message' => 'Ce compte est inactif.'], 403);
        }

        // Log the user in manually
        $security->login($user, 'security.authenticator.form_login.main');

        return new JsonResponse([
            'success' => true, 
            'message' => 'Connexion réussie !',
            'redirect' => $this->generateUrl('app_home')
        ]);
    }

    #[Route(path: '/login/security-alert', name: 'app_login_security_alert', methods: ['POST'])]
    public function securityAlert(Request $request, UserRepository $userRepository, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $image = $data['image'] ?? null;
        $email = $data['email'] ?? null;

        if (!$image || !$email) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes.'], 400);
        }

        $user = $userRepository->findOneBy(['emailUser' => $email]);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé.'], 404);
        }

        // Process image base64
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageBinary = base64_decode($image);

        $emailObj = (new Email())
            ->from('security@agrigoweb.tn')
            ->to($user->getEmailUser())
            ->subject('⚠️ Alerte de sécurité : Tentatives de connexion suspectes')
            ->html('
                <div style="font-family: sans-serif; padding: 20px; color: #333;">
                    <h2 style="color: #d32f2f;">Alerte de Sécurité AgriGo</h2>
                    <p>Bonjour <strong>' . htmlspecialchars($user->getFullName()) . '</strong>,</p>
                    <p>Nous avons détecté <strong>3 tentatives de connexion consécutives échouées</strong> sur votre compte.</p>
                    <p>Conformément à nos protocoles de sécurité, une capture d\'image a été prise depuis la caméra de l\'appareil utilisé. Vous la trouverez en pièce jointe.</p>
                    <p>Si vous n\'êtes pas à l\'origine de ces tentatives, nous vous recommandons de sécuriser votre compte immédiatement.</p>
                    <hr>
                    <p style="font-size: 0.8em; color: #666;">Ceci est un message automatique, veuillez ne pas y répondre.</p>
                </div>
            ')
            ->attach($imageBinary, 'capture-securite.png', 'image/png');

        try {
            $mailer->send($emailObj);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'envoi de l\'email.'], 500);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/login/face', name: 'api_login_face', methods: ['POST'])]
    public function loginFace(Request $request, UserRepository $userRepository, Security $security): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!$descriptor || !is_array($descriptor)) {
            return new JsonResponse(['success' => false, 'message' => 'Descripteur facial invalide.'], 400);
        }

        $users = $userRepository->findAll();
        $bestMatch = null;
        $minDistance = 0.6; // Seuil recommandé par face-api.js

        foreach ($users as $user) {
            $storedDescriptor = $user->getFaceDescriptor();
            if (!$storedDescriptor) continue;

            $storedArray = json_decode($storedDescriptor, true);
            if (!$storedArray) continue;

            // Calcul de la distance euclidienne
            $distance = 0;
            for ($i = 0; $i < count($descriptor); $i++) {
                $distance += pow($descriptor[$i] - $storedArray[$i], 2);
            }
            $distance = sqrt($distance);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatch = $user;
            }
        }

        if ($bestMatch) {
            if (!$bestMatch->isActive() || $bestMatch->getBadWordCommentStrikes() >= 3) {
                return new JsonResponse(['success' => false, 'message' => 'Ce compte est inactif.'], 403);
            }

            $security->login($bestMatch, 'security.authenticator.form_login.main');
            return new JsonResponse([
                'success' => true,
                'message' => 'Visage reconnu ! Connexion en cours...',
                'redirect' => $this->generateUrl('app_home')
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Aucune correspondance trouvée.'], 404);
    }
}
