<?php

namespace App\Controller;

use App\Entity\MouvementStock;
use App\Entity\User;
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

#[Route('/mouvement-stock')]
#[IsGranted('ROLE_USER')]
class MouvementStockFrontController extends AbstractController
{
    #[Route('/', name: 'app_mouvement_stock_list', methods: ['GET'])]
    public function list(Request $request, MouvementStockRepository $mouvementStockRepository): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $search = trim((string) $request->query->get('search', ''));
        
        $mouvements = $mouvementStockRepository->findBy(['id_user' => $currentUser->getIdUser()]);

        if ($search) {
            $mouvements = array_filter($mouvements, function(MouvementStock $m) use ($search) {
                return stripos($m->getMotif(), $search) !== false 
                    || stripos($m->getTypeMouvement(), $search) !== false;
            });
        }

        return $this->render('front/mouvement_stock/list.html.twig', [
            'mouvements' => $mouvements,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_mouvement_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, StockManager $stockManager, StockMailerService $stockMailer): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $mouvementStock = new MouvementStock();
        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mouvementStock->setIdUser($currentUser->getIdUser());

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
                return $this->redirectToRoute('app_mouvement_stock_list');
            } catch (DomainException | LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('front/mouvement_stock/new.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_mouvement}', name: 'app_mouvement_stock_show', methods: ['GET'])]
    public function show(MouvementStock $mouvementStock): Response
    {
        $this->denyAccessUnlessOwner($mouvementStock);

        return $this->render('front/mouvement_stock/show.html.twig', [
            'mouvement_stock' => $mouvementStock,
        ]);
    }

    #[Route('/{id_mouvement}/edit', name: 'app_mouvement_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager, StockManager $stockManager): Response
    {
        $this->denyAccessUnlessOwner($mouvementStock);

        $originalMouvementStock = clone $mouvementStock;
        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $stockManager->updateMouvement($mouvementStock, $originalMouvementStock);
                $entityManager->flush();
                $this->addFlash('success', 'Mouvement de stock mis à jour avec succès.');
                return $this->redirectToRoute('app_mouvement_stock_list');
            } catch (DomainException | LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('front/mouvement_stock/edit.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_mouvement}/delete', name: 'app_mouvement_stock_delete', methods: ['POST'])]
    public function delete(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager, StockManager $stockManager): Response
    {
        $this->denyAccessUnlessOwner($mouvementStock);

        if ($this->isCsrfTokenValid('delete_front_mouvement_stock_'.$mouvementStock->getIdMouvement(), (string) $request->request->get('_token'))) {
            try {
                $stockManager->revertMouvement($mouvementStock);
                $entityManager->remove($mouvementStock);
                $entityManager->flush();
                $this->addFlash('success', 'Mouvement de stock supprimé avec succès.');
            } catch (DomainException | LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_mouvement_stock_list');
    }

    private function denyAccessUnlessOwner(MouvementStock $mouvementStock): void
    {
        $user = $this->getUser();
        if (!$user instanceof User || $mouvementStock->getIdUser() !== $user->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez gérer que vos propres mouvements de stock.');
        }
    }
}
