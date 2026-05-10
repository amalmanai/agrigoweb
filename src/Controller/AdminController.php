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
    /**
     * Point d’entrée back-office : même layout que les écrans /admin/* (sidebar AgriGo Admin).
     */
    #[Route('', name: 'app_admin_entry', methods: ['GET'])]
    public function entry(): Response
    {
        return $this->redirectToRoute('admin_culture_index');
    }

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

        // Google Suggestions - Simuler les suggestions basées sur l'activité utilisateur
        $googleSuggestions = $this->generateGoogleSuggestions($totalRecoltes, $totalVentes, $recoltesRecentes);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total_recoltes'         => $totalRecoltes,
                'total_quantite_recoltee'=> $totalQuantiteRecoltee,
                'total_cout_production'  => $totalCoutProduction,
                'total_ventes'           => $totalVentes,
                'total_revenu'           => $totalRevenu,
                'marge_brute'            => $margeBrute,
            ],
            'recoltes_recentes' => $recoltesRecentes,
            'ventes_recentes'   => $ventesRecentes,
            'google_suggestions' => $googleSuggestions,
        ]);
    }

    /**
     * Génère des suggestions intelligentes basées sur l'activité de l'utilisateur
     */
    private function generateGoogleSuggestions(int $totalRecoltes, int $totalVentes, array $recoltesRecentes): array
    {
        $suggestions = [
            'Aller aux cultures',
            'Créer une tâche', 
            'Statistiques de ma ferme',
            'Météo aujourd\'hui',
            'Aide'
        ];

        // Adapter les suggestions en fonction de l'activité
        if ($totalRecoltes === 0) {
            // Nouvel utilisateur - suggérer de commencer
            array_unshift($suggestions, 'Créer votre première culture');
            array_unshift($suggestions, 'Visiter le tutoriel');
        } elseif ($totalRecoltes < 5) {
            // Utilisateur débutant - suggérer d'explorer
            array_unshift($suggestions, 'Ajouter plus de cultures');
            array_unshift($suggestions, 'Optimiser vos récoltes');
        } else {
            // Utilisateur expérimenté - suggérer d'optimiser
            array_unshift($suggestions, 'Analyser les tendances');
            array_unshift($suggestions, 'Exporter les rapports');
        }

        if ($totalVentes === 0 && $totalRecoltes > 0) {
            // A des récoltes mais pas de ventes
            array_unshift($suggestions, 'Créer votre première vente');
        }

        // Vérifier s'il y a des récoltes récentes sans ventes
        $recentRecoltesWithoutSales = 0;
        foreach ($recoltesRecentes as $recolte) {
            if ($recolte->getHarvestDate() && $recolte->getHarvestDate() > new \DateTime('-7 days')) {
                $recentRecoltesWithoutSales++;
            }
        }

        if ($recentRecoltesWithoutSales > 0) {
            array_unshift($suggestions, 'Vendre vos récoltes récentes');
        }

        // Limiter à 5 suggestions principales
        return [
            'success' => true,
            'page' => 'dashboard',
            'suggestions' => array_slice($suggestions, 0, 5)
        ];
    }
}
