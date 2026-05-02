<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AiChatService
{
    private const GROQ_MODEL = 'llama-3.1-70b-versatile';
    private const OPENAI_MODEL = 'gpt-4o-mini';

    private ?string $openaiApiKey;
    private ?string $groqApiKey;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestStack $requestStack,
        ?string $openaiApiKey = null,
        ?string $groqApiKey = null
    ) {
        $this->openaiApiKey = $openaiApiKey;
        $this->groqApiKey = $groqApiKey;
    }

    public function getChatResponse(User $user, string $userMessage): string
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('ai_chat_history', []);

        // Priority to Real AI if keys are present
        if ($this->isKeyValid($this->groqApiKey)) {
            return $this->callAiProvider('https://api.groq.com/openai/v1/chat/completions', $this->groqApiKey, self::GROQ_MODEL, $user, $userMessage);
        }

        if ($this->isKeyValid($this->openaiApiKey)) {
            return $this->callAiProvider('https://api.openai.com/v1/chat/completions', $this->openaiApiKey, self::OPENAI_MODEL, $user, $userMessage);
        }

        // FALLBACK: Automatic Intelligent Assistant (No Key Required)
        return $this->getMockResponse($userMessage, $user);
    }

    private function isKeyValid(?string $key): bool
    {
        return $key && !str_contains($key, 'YOUR_') && !str_contains($key, 'votre_');
    }

    private function callAiProvider(string $url, string $key, string $model, User $user, string $userMessage): string
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('ai_chat_history', []);
        $contextSummary = $this->buildUserContext($user);

        $systemPrompt = "Tu es l'assistant IA d'AgriGo. Tu aides " . $user->getFullName() . " avec ses parcelles et cultures. " . $contextSummary;

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach (array_slice($history, -5) as $msg) {
            $messages[] = $msg;
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['model' => $model, 'messages' => $messages, 'temperature' => 0.7],
            ]);

            $data = $response->toArray();
            $aiResponse = $data['choices'][0]['message']['content'] ?? null;

            if ($aiResponse) {
                $this->saveToHistory($userMessage, $aiResponse);
                return $aiResponse;
            }
        } catch (\Exception $e) {
        }

        return $this->getMockResponse($userMessage, $user);
    }

    private function getMockResponse(string $message, User $user): string
    {
        $msg = mb_strtolower($message);

        if (str_contains($msg, 'bonjour') || str_contains($msg, 'salut')) {
            return "Bonjour " . $user->getPrenomUser() . " ! Je suis votre assistant AgriGo en mode local. Comment puis-je vous aider aujourd'hui ? ";
        }

        if (str_contains($msg, 'culture') || str_contains($msg, 'plant') || str_contains($msg, 'tomate') || str_contains($msg, 'olive')) {
            return "Pour vos cultures, je vous conseille de surveiller l'humidité du sol via l'onglet 'Culture'. AgriGo recommande une irrigation régulière le matin pour éviter le gaspillage d'eau.";
        }

        if (str_contains($msg, 'stock') || str_contains($msg, 'engrais')) {
            return "Vous pouvez gérer vos stocks dans l'onglet 'Stock'. AgriGo vous alertera automatiquement si une ressource devient critique.";
        }

        if (str_contains($msg, 'aide') || str_contains($msg, 'marche') || str_contains($msg, 'comment')) {
            return "AgriGo vous permet de suivre vos parcelles, vos cultures et vos stocks. Utilisez le menu latéral pour naviguer. Si vous avez une question spécifique, je suis là !";
        }

        return "C'est noté ! En tant qu'assistant AgriGo, je vous recommande d'utiliser les outils de planification pour optimiser votre rendement.";
    }

    private function saveToHistory(string $userMsg, string $aiMsg): void
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('ai_chat_history', []);
        $history[] = ['role' => 'user', 'content' => $userMsg];
        $history[] = ['role' => 'assistant', 'content' => $aiMsg];
        $session->set('ai_chat_history', array_slice($history, -10));
    }

    private function buildUserContext(User $user): string
    {
        $parcelles = $user->getParcelles();
        $cultures = $user->getCultures();
        $summary = "Données : ";
        foreach ($parcelles as $p) {
            $summary .= $p->getNomParcelle() . ", ";
        }
        foreach ($cultures as $c) {
            $summary .= $c->getNomCulture() . "(" . $c->getEtatCroissance() . "), ";
        }
        return $summary;
    }
}
