<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Parcelle;
use App\Form\ParcelleFormType;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/parcelle')]
class ParcelleController extends AbstractController
{
    #[Route('/', name: 'admin_parcelle_index', methods: ['GET'])]
    public function backIndex(Request $request, ParcelleRepository $parcelleRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'nomParcelle');
        $direction = (string) $request->query->get('direction', 'ASC');

        return $this->render('back/parcelle/index.html.twig', [
            'parcelles' => $parcelleRepository->findFiltered($search, $sort, $direction),
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
        return $this->render('back/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_parcelle_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->isCsrfTokenValid('delete_parcelle_' . $parcelle->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($parcelle);
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_parcelle_index');
    }
}
