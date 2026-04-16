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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/recolte')]
#[IsGranted('ROLE_ADMIN')]
class AdminRecolteController extends AbstractController
{
    #[Route('/', name: 'admin_recolte_index', methods: ['GET'])]
    public function index(Request $request, RecolteRepository $recolteRepository): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');

        return $this->render('admin/recolte/index.html.twig', [
            'recoltes' => $recolteRepository->adminSearch($search, $sort, $direction),
            'total_cost' => $recolteRepository->getTotalProductionCost(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'admin_recolte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recolte = new Recolte();
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recolte->setHarvestDate(new \DateTime());
            $entityManager->persist($recolte);
            $entityManager->flush();

            $this->addFlash('success', 'Récolte créée avec succès.');
            return $this->redirectToRoute('admin_recolte_index');
        }

        return $this->render('admin/recolte/new.html.twig', [
            'recolte' => $recolte,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_recolte_show', methods: ['GET'])]
    public function show(Recolte $recolte): Response
    {
        return $this->render('admin/recolte/show.html.twig', [
            'recolte' => $recolte,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_recolte_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Récolte mise à jour avec succès.');
            return $this->redirectToRoute('admin_recolte_index');
        }

        return $this->render('admin/recolte/edit.html.twig', [
            'recolte' => $recolte,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_recolte_delete', methods: ['POST'])]
    public function delete(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recolte->getId(), $request->request->get('_token'))) {
            $entityManager->remove($recolte);
            $entityManager->flush();
            $this->addFlash('success', 'Récolte supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_recolte_index');
    }
}
