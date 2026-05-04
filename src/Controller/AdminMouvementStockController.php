<?php

namespace App\Controller;

use App\Entity\MouvementStock;
use App\Form\MouvementStockType;
use App\Repository\MouvementStockRepository;
use App\Service\StockManager;
use App\Service\StockMailerService;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use LogicException;
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
    public function new(Request $request, EntityManagerInterface $entityManager, StockManager $stockManager, StockMailerService $stockMailer): Response
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

            try {
                $stockManager->applyMouvement($mouvementStock);
                $entityManager->persist($mouvementStock);
                $entityManager->flush();

                try {
                    $stockMailer->sendStockMovementNotification($mouvementStock);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Le mouvement de stock a été enregistré, mais l’e-mail n’a pas pu être envoyé.');
                }

                $this->addFlash('success', 'Mouvement de stock créé avec succès.');
                return $this->redirectToRoute('admin_mouvement_stock_index');
            } catch (DomainException | LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
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
    public function edit(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager, StockManager $stockManager): Response
    {
        $originalMouvementStock = clone $mouvementStock;
        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $stockManager->updateMouvement($mouvementStock, $originalMouvementStock);
                $entityManager->flush();
                $this->addFlash('success', 'Mouvement de stock mis à jour avec succès.');
                return $this->redirectToRoute('admin_mouvement_stock_index');
            } catch (DomainException | LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/mouvement_stock/edit.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_mouvement}', name: 'admin_mouvement_stock_delete', methods: ['POST'])]
    public function delete(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager, StockManager $stockManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mouvementStock->getIdMouvement(), $request->request->get('_token'))) {
            try {
                $stockManager->revertMouvement($mouvementStock);
                $entityManager->remove($mouvementStock);
                $entityManager->flush();
                $this->addFlash('success', 'Mouvement de stock supprimé avec succès.');
            } catch (DomainException | LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_mouvement_stock_index');
    }
}
