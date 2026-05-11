<?php

namespace App\Controller;

use App\Service\AiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    #[Route('/api/ai/chat', name: 'api_ai_chat', methods: ['POST'])]
    public function chat(Request $request, AiChatService $aiChatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message vide.'], 400);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Utilisateur non identifié.'], 401);
        }

        $response = $aiChatService->getChatResponse($user, $userMessage);

        return new JsonResponse(['response' => $response]);
    }
}
