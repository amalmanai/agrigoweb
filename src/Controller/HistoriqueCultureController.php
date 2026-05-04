<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HistoriqueCulture;
use App\Form\HistoriqueCultureFormType;
use App\Repository\HistoriqueCultureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/historique-culture')]
class HistoriqueCultureController extends AbstractController
{
    #[Route('/', name: 'admin_historique_culture_index', methods: ['GET'])]
    public function index(Request $request, HistoriqueCultureRepository $repository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sortField', 'dateRecolteEffective');
        $direction = (string) $request->query->get('sortDirection', 'DESC');

        return $this->render('back/historique_culture/index.html.twig', [
            'historiques' => $repository->findFiltered($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'sortOptions' => [
                'dateRecolteEffective' => 'Date recolte',
                'ancienneCulture' => 'Ancienne culture',
                'rendementFinal' => 'Rendement',
                'parcelle' => 'Parcelle',
            ],
        ]);
    }

    #[Route('/new', name: 'admin_historique_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $historique = new HistoriqueCulture();
        $form = $this->createForm(HistoriqueCultureFormType::class, $historique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($historique);
            $entityManager->flush();

            $this->addFlash('success', 'Historique enregistre avec succes.');

            return $this->redirectToRoute('admin_historique_culture_index');
        }

        return $this->render('back/historique_culture/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_historique_culture_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(HistoriqueCulture $historiqueCulture): Response
    {
        return $this->render('back/historique_culture/show.html.twig', [
            'historique' => $historiqueCulture,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_historique_culture_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, HistoriqueCulture $historiqueCulture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HistoriqueCultureFormType::class, $historiqueCulture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Historique mis a jour avec succes.');

            return $this->redirectToRoute('admin_historique_culture_index');
        }

        return $this->render('back/historique_culture/edit.html.twig', [
            'historique' => $historiqueCulture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_historique_culture_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, HistoriqueCulture $historiqueCulture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_historique_' . $historiqueCulture->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($historiqueCulture);
            $entityManager->flush();
            $this->addFlash('success', 'Historique supprime avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_historique_culture_index');
    }
}
