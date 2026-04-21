<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // 1) Have they logged in with Google before? Easy!
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);

                if ($existingUser) {
                    if (!$existingUser->isActive() || $existingUser->getBadWordCommentStrikes() >= 3) {
                        throw new CustomUserMessageAuthenticationException('Ce compte est bloqué après 3 tentatives non autorisées.');
                    }

                    return $existingUser;
                }

                // 2) Do we have a matching user by email?
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['emailUser' => $email]);

                if (!$user) {
                    // 3) No user? Create one!
                    $user = new User();
                    $user->setEmailUser($email);
                    $user->setGoogleId($googleUser->getId());
                    $user->setNomUser($googleUser->getLastName() ?? 'Utilisateur');
                    $user->setPrenomUser($googleUser->getFirstName() ?? 'Google');
                    
                    // Mandatory fields with placeholders for "completion obligatoire"
                    $user->setNumUser(0); // Marker for incomplete profile
                    $user->setAdresseUser('Compte Google - À compléter');
                    $user->setPassword(bin2hex(random_bytes(10))); // Dummy password
                    $user->setRoleUser('ROLE_USER');
                    $user->setIsActive(true);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                } else {
                    if (!$user->isActive() || $user->getBadWordCommentStrikes() >= 3) {
                        throw new CustomUserMessageAuthenticationException('Ce compte est bloqué après 3 tentatives non autorisées.');
                    }

                    // Update existing user with googleId if they match by email
                    $user->setGoogleId($googleUser->getId());
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        // If profile is incomplete, redirect to profile edit
        if ($user->getNumUser() === 0) {
            return new RedirectResponse($this->router->generate('app_profile_edit'));
        }

        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
