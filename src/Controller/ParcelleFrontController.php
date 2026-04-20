<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Parcelle;
use App\Entity\User;
use App\Form\ParcelleFormType;
use App\Repository\ParcelleRepository;
use App\Service\AgriData\ClimateService;
use App\Service\AgriData\SoilService;
use App\Service\CropRecommendationService;
use App\Service\ParcelWeatherService;
use App\Service\RiskAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParcelleFrontController extends AbstractController
{
    #[Route('/parcelle', name: 'app_parcelle_list', methods: ['GET'])]
    public function list(
        Request $request,
        ParcelleRepository $parcelleRepository,
        PaginatorInterface $paginator,
        ParcelWeatherService $parcelWeatherService,
    ): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sortField', 'nomParcelle');
        $direction = (string) $request->query->get('sortDirection', 'ASC');
        $owner = $this->getUser();
        if (!$owner instanceof User) {
            $owner = null;
        }

        $queryBuilder = $parcelleRepository->findFilteredQueryBuilder($search, $sort, $direction, $owner);
        $parcelles = $paginator->paginate(
            $queryBuilder,
            max(1, $request->query->getInt('page', 1)),
            8,
            [
                'sortFieldParameterName' => '_knp_sort',
                'sortDirectionParameterName' => '_knp_direction',
            ]
        );

        $weatherByParcelle = [];
        foreach ($parcelles as $parcelle) {
            $weatherByParcelle[$parcelle->getId()] = null;
            if ($parcelle->getCoordonneesGps() !== null) {
                $weatherByParcelle[$parcelle->getId()] = $parcelWeatherService->fetchCurrentByParcelle($parcelle);
            }
        }

        $currentPageParcelles = iterator_to_array($parcelles->getItems());
        $parcelleMapPoints = [];
        foreach ($currentPageParcelles as $parcelle) {
            $coords = $this->parseCoordinates((string) ($parcelle->getCoordonneesGps() ?? ''));
            if ($coords === null) {
                continue;
            }

            $parcelleMapPoints[] = [
                'id' => $parcelle->getId(),
                'name' => $parcelle->getNomParcelle(),
                'surface' => $parcelle->getSurface(),
                'soil' => $parcelle->getTypeSol(),
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
            ];
        }

        return $this->render('front/parcelle/list.html.twig', [
            'parcelles' => $parcelles,
            'recommendationsByParcelle' => [],
            'weatherByParcelle' => $weatherByParcelle,
            'parcelleMapPoints' => $parcelleMapPoints,
            'parcellesCount' => $parcelleRepository->countAll($owner),
            'totalSurface' => $parcelleRepository->getTotalSurface($owner),
            'parcellesWithGpsCount' => count($parcelleMapPoints),
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'sortOptions' => [
                'nomParcelle' => 'Nom',
                'surface' => 'Surface',
                'typeSol' => 'Type de sol',
                'coordonneesGps' => 'Coordonnees',
            ],
        ]);
    }

    #[Route('/parcelle/new', name: 'app_parcelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parcelle = new Parcelle();
        $owner = $this->getUser();
        if ($owner instanceof User) {
            $parcelle->setOwner($owner);
        }

        $form = $this->createForm(ParcelleFormType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parcelle);
            $entityManager->flush();

            $this->addFlash('success', 'Parcelle creee avec succes.');

            return $this->redirectToRoute('app_parcelle_list');
        }

        return $this->render('front/parcelle/new.html.twig', [
            'parcelle' => $parcelle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/parcelle/{id}', name: 'app_parcelle_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(
        int $id,
        ParcelleRepository $parcelleRepository,
        ParcelWeatherService $parcelWeatherService,
        CropRecommendationService $cropRecommendationService,
        SoilService $soilService,
        ClimateService $climateService,
        RiskAnalysisService $riskAnalysisService,
    ): Response
    {
        $owner = $this->getUser();
        if (!$owner instanceof User) {
            $owner = null;
        }

        $parcelle = $parcelleRepository->findOneBy(['id' => $id, 'owner' => $owner]);
        if (!$parcelle instanceof Parcelle) {
            throw $this->createNotFoundException();
        }

        foreach ($parcelle->getCultures() as $culture) {
            $riskAnalysisService->analyzeCulture($culture);
        }

        return $this->render('front/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
            'weather' => $parcelWeatherService->fetchCurrentByParcelle($parcelle),
            'soil' => $soilService->fetchByParcelle($parcelle),
            'climate' => $climateService->fetchSolarRadiationByParcelle($parcelle),
            'recommendation' => $cropRecommendationService->recommendForParcelle($parcelle, new \DateTimeImmutable('now')),
        ]);
    }

    /**
     * @return array{lat:float, lon:float}|null
     */
    private function parseCoordinates(string $gps): ?array
    {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $gps, $matches)) {
            return null;
        }

        return [
            'lat' => (float) $matches[1],
            'lon' => (float) $matches[2],
        ];
    }
}
