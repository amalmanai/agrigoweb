<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AlerteRisque;
use App\Form\AlerteRisqueFormType;
use App\Repository\AlerteRisqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/alerte-risque')]
class AlerteRisqueController extends AbstractController
{
    #[Route('/', name: 'admin_alerte_risque_index', methods: ['GET'])]
    public function index(Request $request, AlerteRisqueRepository $repository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'dateAlerte');
        $direction = (string) $request->query->get('direction', 'DESC');

        return $this->render('back/alerte_risque/index.html.twig', [
            'alertes' => $repository->findFiltered($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'sortOptions' => [
                'dateAlerte' => 'Date',
                'typeAlerte' => 'Type',
                'culture' => 'Culture',
            ],
        ]);
    }

    #[Route('/new', name: 'admin_alerte_risque_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $alerteRisque = new AlerteRisque();
        $form = $this->createForm(AlerteRisqueFormType::class, $alerteRisque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($alerteRisque);
            $entityManager->flush();

            $this->addFlash('success', 'Alerte enregistree avec succes.');

            return $this->redirectToRoute('admin_alerte_risque_index');
        }

        return $this->render('back/alerte_risque/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_alerte_risque_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(AlerteRisque $alerteRisque): Response
    {
        return $this->render('back/alerte_risque/show.html.twig', [
            'alerte' => $alerteRisque,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_alerte_risque_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, AlerteRisque $alerteRisque, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AlerteRisqueFormType::class, $alerteRisque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Alerte mise a jour avec succes.');

            return $this->redirectToRoute('admin_alerte_risque_index');
        }

        return $this->render('back/alerte_risque/edit.html.twig', [
            'alerte' => $alerteRisque,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_alerte_risque_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, AlerteRisque $alerteRisque, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_alerte_' . $alerteRisque->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($alerteRisque);
            $entityManager->flush();
            $this->addFlash('success', 'Alerte supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_alerte_risque_index');
    }
}
