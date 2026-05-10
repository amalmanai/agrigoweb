<?php

namespace App\Controller;

use App\Service\AiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai')]
#[IsGranted('ROLE_USER')]
class AiChatController extends AbstractController
{
    #[Route('/chat', name: 'api_ai_chat', methods: ['POST'])]
    public function chat(Request $request, AiChatService $aiChatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return $this->json(['response' => 'Veuillez envoyer un message.']);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['response' => 'Utilisateur non connecté.'], 403);
        }

        $response = $aiChatService->getChatResponse($user, $message);

        return $this->json([
            'response' => $response
        ]);
    }
}
