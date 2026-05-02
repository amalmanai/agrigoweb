<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twilio\Rest\Client;

class SMSService
{
    public function __construct(
        #[Autowire('%env(default::TWILIO_SID)%')]
        private readonly ?string $sid,
        #[Autowire('%env(default::TWILIO_TOKEN)%')]
        private readonly ?string $token,
        #[Autowire('%env(default::TWILIO_FROM)%')]
        private readonly ?string $from,
    ) {
    }

    public function send(string $to, string $message, ?string $mediaUrl = null): bool
    {
        return $this->sendWithStatus($to, $message, $mediaUrl)['success'];
    }

    /**
     * @return array{success:bool,error:?string}
     */
    public function sendWithStatus(string $to, string $message, ?string $mediaUrl = null): array
    {
        $sid = trim((string) $this->sid);
        $token = trim((string) $this->token);
        $from = trim((string) $this->from);

        if ('' === $sid || '' === $token || '' === $from) {
            return [
                'success' => false,
                'error' => 'Configuration Twilio incomplète',
            ];
        }

        try {
            $client = new Client($sid, $token);
            $payload = [
                'from' => $from,
                'body' => $message,
            ];
            if (null !== $mediaUrl && '' !== trim($mediaUrl)) {
                $payload['mediaUrl'] = [trim($mediaUrl)];
            }

            error_log("SMS: Sending to {$to} from {$from}, message: {$message}");
            $response = $client->messages->create($to, $payload);
            error_log("SMS: Success! SID = ".$response->sid);

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            error_log("SMS Error: {$errorMsg}");
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }
    }
}