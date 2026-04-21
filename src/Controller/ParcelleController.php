<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Parcelle;
use App\Entity\User;
use App\Form\ParcelleFormType;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/parcelle')]
class ParcelleController extends AbstractController
{
    #[Route('/', name: 'admin_parcelle_index', methods: ['GET'])]
    public function backIndex(
        Request $request,
        ParcelleRepository $parcelleRepository,
        PaginatorInterface $paginator,
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
            10,
            [
                'sortFieldParameterName' => '_knp_sort',
                'sortDirectionParameterName' => '_knp_direction',
            ]
        );

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

        return $this->render('back/parcelle/index.html.twig', [
            'parcelles' => $parcelles,
            'parcelleMapPoints' => $parcelleMapPoints,
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

    #[Route('/new', name: 'admin_parcelle_new', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('admin_parcelle_index');
        }

        return $this->render('back/parcelle/new.html.twig', [
            'parcelle' => $parcelle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_parcelle_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Parcelle $parcelle): Response
    {
        $owner = $this->getUser();
        if ($owner instanceof User && $parcelle->getOwner() !== $owner) {
            throw $this->createNotFoundException();
        }

        return $this->render('back/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_parcelle_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        $owner = $this->getUser();
        if ($owner instanceof User && $parcelle->getOwner() !== $owner) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ParcelleFormType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Parcelle mise a jour avec succes.');

            return $this->redirectToRoute('admin_parcelle_index');
        }

        return $this->render('back/parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_parcelle_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        $owner = $this->getUser();
        if ($owner instanceof User && $parcelle->getOwner() !== $owner) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_parcelle_' . $parcelle->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($parcelle);
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_parcelle_index');
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
