<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SystemeIrrigation;
use App\Entity\User;
use App\Repository\ParcelleRepository;
use App\Repository\SystemeIrrigationRepository;
use App\Service\MeteoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MeteoIrrigationController extends AbstractController
{
    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route(path: '/systeme-irrigation/meteo', name: 'front_systeme_irrigation_meteo', methods: ['GET'])]
    public function index(
        ParcelleRepository $parcelleRepository,
        SystemeIrrigationRepository $systemeIrrigationRepository,
        MeteoService $meteoService,
    ): Response {
        $user = $this->requireUser();
        $parcelles = $parcelleRepository->findFilteredByOwner($user, null, 'nomParcelle', 'ASC');
        $cards = [];

        foreach ($parcelles as $parcelle) {
            $coordonnees = $parcelle->getCoordonneesGps();
            $systemes = $systemeIrrigationRepository->findBy([
                'id_parcelle' => $parcelle->getId(),
                'statut' => 'ACTIF',
            ], [
                'nom_systeme' => 'ASC',
            ]);

            $hasAuto = false;
            foreach ($systemes as $systeme) {
                if (!$systeme instanceof SystemeIrrigation) {
                    continue;
                }

                if ('AUTO' === strtoupper((string) $systeme->getMode())) {
                    $hasAuto = true;
                    break;
                }
            }

            $meteo = null;
            if (null !== $coordonnees && '' !== trim($coordonnees)) {
                $meteo = $meteoService->getMeteoPourParcelle($coordonnees, $hasAuto);
            }

            $cards[] = [
                'parcelle' => $parcelle,
                'systemes' => $systemes,
                'meteo' => $meteo,
                'has_auto' => $hasAuto,
            ];
        }

        return $this->render('systeme_irrigation/meteo.html.twig', [
            'layout' => 'front/base.html.twig',
            'cards' => $cards,
        ]);
    }
}