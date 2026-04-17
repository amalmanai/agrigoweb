<?php

namespace App\Controller;

use App\Entity\MouvementStock;
use App\Form\MouvementStockType;
use App\Repository\MouvementStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/mouvement-stock')]
#[IsGranted('ROLE_ADMIN')]
class AdminMouvementStockController extends AbstractController
{
    #[Route('/', name: 'admin_mouvement_stock_index', methods: ['GET'])]
    public function index(Request $request, MouvementStockRepository $mouvementStockRepository): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id_mouvement');
        $direction = $request->query->get('direction', 'ASC');

        return $this->render('admin/mouvement_stock/index.html.twig', [
            'mouvements' => $mouvementStockRepository->adminSearch($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'admin_mouvement_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $mouvementStock = new MouvementStock();
        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set current admin user id for the mouvement
            if ($this->getUser() && method_exists($this->getUser(), 'getIdUser')) {
                $mouvementStock->setIdUser($this->getUser()->getIdUser());
            } else {
                $mouvementStock->setIdUser(0); // fallback
            }

            $entityManager->persist($mouvementStock);
            $entityManager->flush();

            $this->addFlash('success', 'Mouvement de stock créé avec succès.');
            return $this->redirectToRoute('admin_mouvement_stock_index');
        }

        return $this->render('admin/mouvement_stock/new.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_mouvement}', name: 'admin_mouvement_stock_show', methods: ['GET'])]
    public function show(MouvementStock $mouvementStock): Response
    {
        return $this->render('admin/mouvement_stock/show.html.twig', [
            'mouvement_stock' => $mouvementStock,
        ]);
    }

    #[Route('/{id_mouvement}/edit', name: 'admin_mouvement_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Mouvement de stock mis à jour avec succès.');
            return $this->redirectToRoute('admin_mouvement_stock_index');
        }

        return $this->render('admin/mouvement_stock/edit.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_mouvement}', name: 'admin_mouvement_stock_delete', methods: ['POST'])]
    public function delete(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $mouvementStock->getIdMouvement(), $request->request->get('_token'))) {
            $entityManager->remove($mouvementStock);
            $entityManager->flush();
            $this->addFlash('success', 'Mouvement de stock supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_mouvement_stock_index');
    }
}
