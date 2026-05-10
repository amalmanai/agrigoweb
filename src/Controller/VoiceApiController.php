<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AiChatService;
use App\Service\VoiceCommandService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * 🎤🧠 AgriGo Voice Task AI - API Controller
 * 
 * Point d'entrée pour les commandes vocales intelligentes.
 * Supporte: tâches, cultures, stock, ventes, navigation, météo, etc.
 */
#[Route('/api/voice')]
#[IsGranted('ROLE_USER')]
final class VoiceApiController extends AbstractController
{
    public function __construct(
        private readonly VoiceCommandService $voiceCommandService,
        private readonly AiChatService $aiService,
    ) {
    }

    /**
     * Endpoint principal: Analyse et exécute une commande vocale
     */
    #[Route('/parse', name: 'api_voice_parse', methods: ['POST'])]
    public function parseCommand(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        $text = trim($data['text'] ?? '');

        if (empty($text)) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun texte fourni. Veuillez parler plus clairement.'
            ], 400);
        }

        // Étape 1: L'IA analyse l'intention
        $intent = $this->aiService->parseVoiceCommand($user, $text);

        if (!$intent || ($intent['action'] ?? 'unknown') === 'unknown') {
            return $this->json([
                'success' => false,
                'message' => $intent['message'] ?? "🤔 Désolé, je n'ai pas compris. Essayez: 'aide' pour voir les commandes.",
                'transcript' => $text,
                'suggestions' => [
                    'Créer une tâche...',
                    'Ajouter du stock...',
                    'Aller aux cultures...',
                    'Météo...'
                ]
            ]);
        }

        // Étape 2: Exécuter la commande via le service intelligent
        $result = $this->voiceCommandService->executeCommand($user, $intent);
        $result['transcript'] = $text;
        $result['intent'] = $intent;

        // Sauvegarder dans l'historique
        $this->saveToHistory($request, $text, $result);

        return $this->json($result);
    }

    /**
     * Récupère l'historique des commandes vocales de l'utilisateur
     */
    #[Route('/history', name: 'api_voice_history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        // Stocké en session pour l'instant
        $session = $request->getSession();
        $history = $session->get('voice_command_history', []);

        return $this->json([
            'success' => true,
            'history' => array_slice($history, -20) // 20 dernières commandes
        ]);
    }

    /**
     * Sauvegarde une commande réussie dans l'historique
     */
    private function saveToHistory(Request $request, string $command, array $result): void
    {
        $session = $request->getSession();
        $history = $session->get('voice_command_history', []);
        
        $history[] = [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'command' => $command,
            'action' => $result['action'] ?? 'unknown',
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? ''
        ];

        $session->set('voice_command_history', array_slice($history, -50));
    }

    /**
     * Endpoint de test pour vérifier que le service vocal fonctionne
     */
    #[Route('/test', name: 'api_voice_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => '🎤 AgriGo Voice Task AI est opérationnel !',
            'supported_actions' => [
                'create_task', 'update_task', 'delete_task', 'list_tasks', 'complete_task',
                'create_culture', 'update_culture_status', 'list_cultures',
                'add_stock', 'remove_stock', 'check_stock', 'list_low_stock',
                'create_vente', 'list_ventes',
                'navigate', 'get_stats', 'get_weather', 'get_risks', 
                'get_recommendations', 'schedule_irrigation', 'help'
            ]
        ]);
    }

    /**
     * Empty suggestions endpoint to prevent 404 errors
     * This endpoint exists only to prevent errors from cached JavaScript
     */
    #[Route('/suggestions', name: 'api_voice_suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request): JsonResponse
    {
        // Return empty response to prevent errors
        return $this->json([
            'success' => true,
            'page' => $request->query->get('page', 'dashboard'),
            'suggestions' => []
        ]);
    }
}
