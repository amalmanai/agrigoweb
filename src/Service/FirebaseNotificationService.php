<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseNotificationService
{
    private const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(User $recipient, string $title, string $body, array $data = []): bool
    {
        $token = trim((string) $recipient->getFcmToken());
        if ($token === '') {
            $this->logger->info('Firebase notification skipped: missing recipient token', [
                'user_id' => $recipient->getIdUser(),
            ]);

            return false;
        }

        $serverKey = trim((string) ($_ENV['FIREBASE_SERVER_KEY'] ?? $_SERVER['FIREBASE_SERVER_KEY'] ?? ''));
        if ($serverKey === '') {
            $this->logger->warning('Firebase notification skipped: missing FIREBASE_SERVER_KEY', [
                'user_id' => $recipient->getIdUser(),
            ]);

            return false;
        }

        $payload = [
            'to' => $token,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/images/logo.png',
                'click_action' => $data['click_action'] ?? '/vente/',
            ],
            'data' => array_merge([
                'channel' => 'vente',
            ], $data),
        ];

        try {
            $response = $this->httpClient->request('POST', self::FCM_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $content = $response->toArray(false);

            $this->logger->info('Firebase notification sent', [
                'user_id' => $recipient->getIdUser(),
                'title' => $title,
                'response' => $content,
            ]);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('Firebase notification failed', [
                'user_id' => $recipient->getIdUser(),
                'title' => $title,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}