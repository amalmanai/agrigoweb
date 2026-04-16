<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ParcelleRepository;
use App\Service\AnomalieIrrigationService;
use App\Service\PlanningIrrigationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IrrigationInsightsController extends AbstractController
{
    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route(path: '/systeme-irrigation/planning', name: 'front_systeme_irrigation_planning', methods: ['GET'])]
    public function planning(Request $request, ParcelleRepository $parcelleRepository, PlanningIrrigationService $planningService): Response
    {
        $user = $this->requireUser();
        $parcelles = $parcelleRepository->findFilteredByOwner($user, null, 'nomParcelle', 'ASC');
        $parcelleIds = array_map(static fn ($parcelle): int => (int) $parcelle->getId(), $parcelles);

        $parcelleId = $request->query->getInt('parcelle', $parcelleIds[0] ?? 0);
        if (0 === $parcelleId && $parcelleIds === []) {
            $plannings = [];
        } else {
            if ($parcelleId !== 0 && !in_array($parcelleId, $parcelleIds, true)) {
                throw $this->createNotFoundException();
            }

            if (0 === $parcelleId) {
                $parcelleId = $parcelleIds[0] ?? 0;
            }

            $plannings = 0 === $parcelleId ? [] : $planningService->suggestPlanningForParcelle($parcelleId);
        }

        $selectedParcelle = null;
        foreach ($parcelles as $parcelle) {
            if ((int) $parcelle->getId() === $parcelleId) {
                $selectedParcelle = $parcelle;
                break;
            }
        }

        return $this->render('systeme_irrigation/planning.html.twig', [
            'layout' => 'front/base.html.twig',
            'parcelles' => $parcelles,
            'parcelle_selectionnee' => $selectedParcelle,
            'plannings' => $plannings,
            'parcelle_id_courant' => $parcelleId,
        ]);
    }

    #[Route(path: '/systeme-irrigation/anomalies', name: 'front_systeme_irrigation_anomalies', methods: ['GET'])]
    public function anomalies(ParcelleRepository $parcelleRepository, AnomalieIrrigationService $anomalieService): Response
    {
        $user = $this->requireUser();
        $parcelles = $parcelleRepository->findFilteredByOwner($user, null, 'nomParcelle', 'ASC');
        $parcelleIds = array_map(static fn ($parcelle): int => (int) $parcelle->getId(), $parcelles);

        $anomalies = $anomalieService->detectAnomaliesForParcelles($parcelleIds);

        foreach ($anomalies as &$anomalie) {
            $anomalie['badge_class'] = 'sur-irrigation' === $anomalie['type_anomalie']
                ? 'bg-danger'
                : 'bg-warning text-dark';
        }
        unset($anomalie);

        return $this->render('systeme_irrigation/anomalies.html.twig', [
            'layout' => 'front/base.html.twig',
            'anomalies' => $anomalies,
        ]);
    }
}