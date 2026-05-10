<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\InventoryAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ia')]
#[IsGranted('ROLE_ADMIN')]
final class AiApiController extends AbstractController
{
    #[Route('/gaspillage-invisible', name: 'api_ia_gaspillage_invisible', methods: ['GET'])]
    public function gaspillageInvisible(InventoryAiService $ai): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'data' => $ai->analyzeWasteDetectionSimple(),
        ]);
    }

    #[Route('/stock-optimal', name: 'api_ia_stock_optimal', methods: ['GET'])]
    public function stockOptimal(Request $request, InventoryAiService $ai): JsonResponse
    {
        $delaiLivraison = max(1, (int) $request->query->get('delai', 7));
        $margeSecuriteJours = max(0, (int) $request->query->get('marge', 3));

        return $this->json([
            'ok' => true,
            'params' => [
                'delaiLivraison' => $delaiLivraison,
                'margeSecuriteJours' => $margeSecuriteJours,
            ],
            'data' => $ai->recommendOptimalStockApi($delaiLivraison, $margeSecuriteJours),
        ]);
    }
}

