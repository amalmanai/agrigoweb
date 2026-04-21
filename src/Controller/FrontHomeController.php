<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\RecolteRepository;
use App\Repository\VenteRepository;
use App\Service\InventoryAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FrontHomeController extends AbstractController
{
    #[Route('/', name: 'app_front_home', methods: ['GET'])]
    public function index(RecolteRepository $recolteRepository, VenteRepository $venteRepository): Response
    {
        $user = $this->getUser();
        
        $recoltes = [];
        $ventes = [];
        $totalCost = 0;
        $totalRevenue = 0;

        if ($user instanceof User) {
            $userId = (int) $user->getIdUser();
            $recoltes = $recolteRepository->searchAndSortForUser($userId, null, 'DESC');
            $ventes = $venteRepository->findForUser($userId);
            $totalCost = $recolteRepository->getTotalProductionCostForUser($userId);
            $totalRevenue = $venteRepository->getTotalRevenueForUser($userId);
        }

        return $this->render('front/home.html.twig', [
            'recoltes' => $recoltes,
            'ventes' => $ventes,
            'total_cost' => $totalCost,
            'total_revenue' => $totalRevenue,
        ]);
    }

    #[Route('/statistiques', name: 'app_front_statistics', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function statistics(RecolteRepository $recolteRepository, VenteRepository $venteRepository): Response
    {
        $user = $this->getUser();
        $userId = $user instanceof User ? (int) $user->getIdUser() : null;

        $recoltes = $userId !== null ? $recolteRepository->searchAndSortForUser($userId, null, 'DESC') : [];
        $ventes = $userId !== null ? $venteRepository->findForUser($userId) : [];
        $totalCost = $userId !== null ? $recolteRepository->getTotalProductionCostForUser($userId) : 0;
        $totalRevenue = $userId !== null ? $venteRepository->getTotalRevenueForUser($userId) : 0;

        return $this->render('front/statistics.html.twig', [
            'recoltes' => $recoltes,
            'ventes' => $ventes,
            'total_cost' => $totalCost,
            'total_revenue' => $totalRevenue,
        ]);
    }

    #[Route('/ia/gaspillage', name: 'app_front_ai_gaspillage', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function gaspillage(InventoryAiService $inventoryAiService): Response
    {
        return $this->render('front/ai/gaspillage.html.twig', [
            'analysis' => $inventoryAiService->analyzeWasteDetectionSimple(),
        ]);
    }

    #[Route('/ia/stock-optimal', name: 'app_front_ai_stock_optimal', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function stockOptimal(Request $request, InventoryAiService $inventoryAiService): Response
    {
        $delai = max(1, (int) $request->query->get('delai', 7));
        $marge = max(0, (int) $request->query->get('marge', 3));

        return $this->render('front/ai/stock_optimal.html.twig', [
            'delai' => $delai,
            'marge' => $marge,
            'recommendations' => $inventoryAiService->recommendOptimalStock($delai, $marge),
        ]);
    }
}
