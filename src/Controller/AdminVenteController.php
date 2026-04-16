<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/vente')]
#[IsGranted('ROLE_ADMIN')]
class AdminVenteController extends AbstractController
{
    #[Route('/', name: 'admin_vente_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');

        return $this->render('admin/vente/index.html.twig', [
            'ventes' => $venteRepository->adminSearch($search, $sort, $direction),
            'total_revenue' => $venteRepository->getTotalRevenue(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'admin_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vente = new Vente();
        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vente->setSaleDate(new \DateTime());
            $entityManager->persist($vente);
            $entityManager->flush();

            $this->addFlash('success', 'Vente créée avec succès.');
            return $this->redirectToRoute('admin_vente_index');
        }

        return $this->render('admin/vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_vente_show', methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        return $this->render('admin/vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vente mise à jour avec succès.');
            return $this->redirectToRoute('admin_vente_index');
        }

        return $this->render('admin/vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_vente_delete', methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vente->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
            $this->addFlash('success', 'Vente supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_vente_index');
    }
}
