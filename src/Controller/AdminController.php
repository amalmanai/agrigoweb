<?php

namespace App\Controller;

use App\Repository\RecolteRepository;
use App\Repository\VenteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(RecolteRepository $recolteRepository, VenteRepository $venteRepository): Response
    {
        // ---- Stats Récoltes ----
        $recoltes = $recolteRepository->findAll();
        $totalRecoltes = count($recoltes);
        $totalCoutProduction = $recolteRepository->getTotalProductionCost();

        // Quantité totale récoltée
        $totalQuantiteRecoltee = array_reduce($recoltes, fn ($carry, $r) => $carry + ($r->getQuantity() ?? 0), 0.0);

        // ---- Stats Ventes ----
        $ventes = $venteRepository->findAll();
        $totalVentes = count($ventes);
        $totalRevenu = $venteRepository->getTotalRevenue();

        // Marge brute (Revenu - Coûts)
        $margeBrute = $totalRevenu - $totalCoutProduction;

        // Ventes récentes (5 dernières)
        $ventesRecentes = $venteRepository->findBy([], ['id' => 'DESC'], 5);

        // Récoltes récentes (5 dernières)
        $recoltesRecentes = $recolteRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total_recoltes'         => $totalRecoltes,
                'total_cout_production'  => $totalCoutProduction,
                'total_quantite_recoltee'=> $totalQuantiteRecoltee,
                'total_ventes'           => $totalVentes,
                'total_revenu'           => $totalRevenu,
                'marge_brute'            => $margeBrute,
            ],
            'ventes_recentes'   => $ventesRecentes,
            'recoltes_recentes' => $recoltesRecentes,
        ]);
    }
}
