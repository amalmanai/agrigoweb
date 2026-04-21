<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Culture;
use App\Entity\User;
use App\Form\CultureFormType;
use App\Repository\AlerteRisqueRepository;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use App\Service\AgriData\PlantEncyclopediaService;
use App\Service\CropRecommendationService;
use App\Service\CultureMetricsService;
use App\Service\ParcelWeatherService;
use App\Service\RiskAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CultureController extends AbstractController
{
    #[Route('/culture', name: 'app_culture_list', methods: ['GET'])]
    public function frontList(
        Request $request,
        CultureRepository $cultureRepository,
        AlerteRisqueRepository $alerteRisqueRepository,
        RiskAnalysisService $riskAnalysisService,
        PaginatorInterface $paginator,
        ParcelWeatherService $parcelWeatherService,
    ): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sortField', 'dateSemis');
        $direction = (string) $request->query->get('sortDirection', 'DESC');
        $owner = $this->getUser();
        if (!$owner instanceof User) {
            $owner = null;
        }

        $queryBuilder = $cultureRepository->findFilteredQueryBuilder($search, $sort, $direction, $owner);
        $cultures = $paginator->paginate(
            $queryBuilder,
            max(1, $request->query->getInt('page', 1)),
            8,
            [
                'sortFieldParameterName' => '_knp_sort',
                'sortDirectionParameterName' => '_knp_direction',
            ]
        );

        $weatherByCulture = [];
        foreach ($cultures as $culture) {
            $weatherByCulture[$culture->getId()] = null;
            $parcelle = $culture->getParcelle();
            if ($parcelle !== null && $parcelle->getCoordonneesGps() !== null) {
                $weatherByCulture[$culture->getId()] = $parcelWeatherService->fetchCurrentByParcelle($parcelle);
            }
        }

        $healthStatus = $riskAnalysisService->getSystemHealthStatus($owner);
        $alertsTrend = $alerteRisqueRepository->getTrendLastDays(7, $owner);

        return $this->render('front/culture/list.html.twig', [
            'cultures' => $cultures,
            'recommendationsByCulture' => [],
            'weatherByCulture' => $weatherByCulture,
            'healthStatus' => $healthStatus,
            'alertsTrend' => $alertsTrend,
            'averageYield' => $cultureRepository->getAverageRendementPrevu($owner),
            'cultureCount' => $cultureRepository->countAll($owner),
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'sortOptions' => [
                'dateSemis' => 'Date semis',
                'dateRecolteEstimee' => 'Date recolte estimee',
                'nomCulture' => 'Nom',
                'etatCroissance' => 'Etat',
                'rendementPrevu' => 'Rendement',
                'rendementEstime' => 'Rendement estime',
                'parcelle' => 'Parcelle',
            ],
        ]);
    }

    #[Route('/culture/{id}', name: 'app_culture_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function frontShow(
        int $id,
        CultureRepository $cultureRepository,
        ParcelWeatherService $parcelWeatherService,
        CropRecommendationService $cropRecommendationService,
        PlantEncyclopediaService $plantEncyclopediaService,
        RiskAnalysisService $riskAnalysisService,
        AlerteRisqueRepository $alerteRisqueRepository,
    ): Response
    {
        $owner = $this->getUser();
        if (!$owner instanceof User) {
            $owner = null;
        }

        $culture = $cultureRepository->findOneBy(['id' => $id, 'owner' => $owner]);
        if (!$culture instanceof Culture) {
            throw $this->createNotFoundException();
        }

        $riskAnalysisService->analyzeCulture($culture);

        $weather = null;
        $recommendation = $cropRecommendationService->recommendNextForCulture($culture, new \DateTimeImmutable('now'));
        $careGuide = $plantEncyclopediaService->fetchByCulture($culture);

        if (($careGuide['source'] ?? 'db_fallback') === 'db_fallback' && isset($recommendation['crop']) && is_string($recommendation['crop'])) {
            $careGuide = $plantEncyclopediaService->fetchByName($recommendation['crop']);
        }

        if ($culture->getParcelle() !== null) {
            $weather = $parcelWeatherService->fetchCurrentByParcelle($culture->getParcelle());
        }

        return $this->render('front/culture/show.html.twig', [
            'culture' => $culture,
            'weather' => $weather,
            'recommendation' => $recommendation,
            'careGuide' => $careGuide,
            'activeAlerts' => $alerteRisqueRepository->findUnresolvedByCulture($culture),
        ]);
    }

    #[Route('/culture/new', name: 'app_culture_new', methods: ['GET', 'POST'])]
    public function frontNew(
        Request $request,
        EntityManagerInterface $entityManager,
        CultureMetricsService $cultureMetricsService,
        ParcelleRepository $parcelleRepository,
    ): Response
    {
        $culture = new Culture();
        $owner = $this->getUser();
        if ($owner instanceof User) {
            $culture->setOwner($owner);
        }

        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_query_builder' => static function ($repository) use ($owner) {
                $queryBuilder = $repository->createQueryBuilder('p')
                    ->orderBy('p.nomParcelle', 'ASC');

                if ($owner instanceof User) {
                    $queryBuilder->andWhere('p.owner = :owner')
                        ->setParameter('owner', $owner);
                }

                return $queryBuilder;
            },
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTime('now'));
            }

            $cultureMetricsService->applyEstimates($culture);

            $entityManager->persist($culture);
            $entityManager->flush();

            $this->addFlash('success', 'Culture creee avec succes.');

            return $this->redirectToRoute('app_culture_list');
        }

        return $this->render('front/culture/new.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
            'parcelle_choices' => $parcelleRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/admin/culture', name: 'admin_culture_index', methods: ['GET'])]
    public function backIndex(
        Request $request,
        CultureRepository $cultureRepository,
        PaginatorInterface $paginator,
        RiskAnalysisService $riskAnalysisService,
    ): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sortField', 'dateSemis');
        $direction = (string) $request->query->get('sortDirection', 'DESC');
        $owner = $this->getUser();
        if (!$owner instanceof User) {
            $owner = null;
        }

        $queryBuilder = $cultureRepository->findFilteredQueryBuilder($search, $sort, $direction, $owner);
        $cultures = $paginator->paginate(
            $queryBuilder,
            max(1, $request->query->getInt('page', 1)),
            10,
            [
                'sortFieldParameterName' => '_knp_sort',
                'sortDirectionParameterName' => '_knp_direction',
            ]
        );

        $healthStatus = $riskAnalysisService->getSystemHealthStatus($owner);
        if ($healthStatus['red'] > 0) {
            $this->addFlash('danger', sprintf('Alerte critique active: %d risque(s) rouge(s) detecte(s).', $healthStatus['red']));
        }

        return $this->render('back/culture/index.html.twig', [
            'cultures' => $cultures,
            'recommendationsByCulture' => [],
            'healthStatus' => $healthStatus,
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'sortOptions' => [
                'dateSemis' => 'Date semis',
                'dateRecolteEstimee' => 'Date recolte estimee',
                'nomCulture' => 'Nom',
                'etatCroissance' => 'Etat',
                'rendementPrevu' => 'Rendement',
                'rendementEstime' => 'Rendement estime',
                'parcelle' => 'Parcelle',
            ],
        ]);
    }

    #[Route('/admin/culture/new', name: 'admin_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CultureMetricsService $cultureMetricsService): Response
    {
        $culture = new Culture();
        $owner = $this->getUser();
        if ($owner instanceof User) {
            $culture->setOwner($owner);
        }
        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_query_builder' => static function ($repository) use ($owner) {
                $queryBuilder = $repository->createQueryBuilder('p')
                    ->orderBy('p.nomParcelle', 'ASC');

                if ($owner instanceof User) {
                    $queryBuilder->andWhere('p.owner = :owner')
                        ->setParameter('owner', $owner);
                }

                return $queryBuilder;
            },
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTime('now'));
            }

            $cultureMetricsService->applyEstimates($culture);

            $entityManager->persist($culture);
            $entityManager->flush();

            $this->addFlash('success', 'Culture creee avec succes.');

            return $this->redirectToRoute('admin_culture_index');
        }

        return $this->render('back/culture/new.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/culture/{id}/edit', name: 'admin_culture_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Culture $culture,
        EntityManagerInterface $entityManager,
        CultureMetricsService $cultureMetricsService,
    ): Response
    {
        $owner = $this->getUser();
        if ($owner instanceof User && $culture->getOwner() !== $owner) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_query_builder' => static function ($repository) use ($owner) {
                $queryBuilder = $repository->createQueryBuilder('p')
                    ->orderBy('p.nomParcelle', 'ASC');

                if ($owner instanceof User) {
                    $queryBuilder->andWhere('p.owner = :owner')
                        ->setParameter('owner', $owner);
                }

                return $queryBuilder;
            },
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTime('now'));
            }

            $cultureMetricsService->applyEstimates($culture);

            $entityManager->flush();

            $this->addFlash('success', 'Culture mise a jour avec succes.');

            return $this->redirectToRoute('admin_culture_index');
        }

        return $this->render('back/culture/edit.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/culture/{id}', name: 'admin_culture_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        $owner = $this->getUser();
        if ($owner instanceof User && $culture->getOwner() !== $owner) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_culture_' . $culture->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($culture);
            $entityManager->flush();
            $this->addFlash('success', 'Culture supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_culture_index');
    }
}
