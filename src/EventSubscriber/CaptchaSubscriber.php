<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CaptchaSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->isMethod('POST') || $request->attributes->get('_route') !== 'app_login') {
            return;
        }

        $session = $request->getSession();
        $expectedAnswer = $session->get('captcha_answer');
        $userAnswer = $request->request->get('_captcha');

        if ($expectedAnswer === null || $userAnswer === null || strtoupper(trim($userAnswer)) !== strtoupper((string)$expectedAnswer)) {
            throw new CustomUserMessageAuthenticationException('Code de sécurité (Captcha) incorrect.');
        }

        // Réinitialiser le captcha après une tentative (réussie ou échouée on génèrera un novueau)
        $session->remove('captcha_answer');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }
}
