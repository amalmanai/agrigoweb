<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\RecolteRepository;
use App\Repository\VenteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

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
}
