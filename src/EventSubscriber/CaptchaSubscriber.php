<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CaptchaSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
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

        // Debug logging
        $this->logger->debug('CAPTCHA Validation', [
            'expected' => $expectedAnswer,
            'user_answer' => $userAnswer,
            'session_id' => $session->getId(),
        ]);

        $normalizedExpected = strtoupper(trim((string)$expectedAnswer));
        $normalizedUser = strtoupper(trim((string)$userAnswer));

        if ($expectedAnswer === null || $userAnswer === null || $normalizedUser !== $normalizedExpected) {
            $this->logger->warning('CAPTCHA validation failed', [
                'expected' => $normalizedExpected,
                'user_answer' => $normalizedUser,
                'reason' => $expectedAnswer === null ? 'no_expected' : ($userAnswer === null ? 'no_user_answer' : 'mismatch'),
            ]);
            throw new CustomUserMessageAuthenticationException('Code de sécurité (Captcha) incorrect.');
        }

        // Réinitialiser le captcha après une validation réussie
        $session->remove('captcha_answer');
        $this->logger->debug('CAPTCHA validation passed');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }
}
