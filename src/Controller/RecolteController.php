<?php

namespace App\Controller;

use App\Entity\Recolte;
use App\Form\RecolteType;
use App\Repository\RecolteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recolte')]
class RecolteController extends AbstractController
{
    #[Route('/', name: 'app_recolte_index', methods: ['GET'])]
    public function index(Request $request, RecolteRepository $recolteRepository): Response
    {
        $query = $request->query->get('q');
        $sort = $request->query->get('sort', 'ASC');

        return $this->render('recolte/index.html.twig', [
            'recoltes' => $recolteRepository->searchAndSort($query, $sort),
            'total_cost' => $recolteRepository->getTotalProductionCost(),
            'current_query' => $query,
            'current_sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_recolte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recolte = new Recolte();
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recolte->setUserId(1); // ID utilisateur par défaut (en attendant un système d'auth complet)
            $entityManager->persist($recolte);
            $entityManager->flush();

            $this->addFlash('success', 'La récolte a été ajoutée avec succès.');

            return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recolte/new.html.twig', [
            'recolte' => $recolte,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_recolte_show', methods: ['GET'])]
    public function show(Recolte $recolte): Response
    {
        return $this->render('recolte/show.html.twig', [
            'recolte' => $recolte,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recolte_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recolte/edit.html.twig', [
            'recolte' => $recolte,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_recolte_delete', methods: ['POST'])]
    public function delete(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recolte->getId(), $request->request->get('_token'))) {
            $entityManager->remove($recolte);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
    }
}
