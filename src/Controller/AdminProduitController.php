<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\ProduitComment;
use App\Form\ProduitType;
use App\Repository\ProduitCommentRepository;
use App\Repository\ProduitRepository;
use App\Service\InventoryAiService;
use App\Service\StockMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/produit')]
#[IsGranted('ROLE_ADMIN')]
class AdminProduitController extends AbstractController
{
    #[Route('/', name: 'admin_produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id_produit');
        $direction = $request->query->get('direction', 'ASC');

        return $this->render('admin/produit/index.html.twig', [
            'produits' => $produitRepository->adminSearch($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/stats', name: 'admin_produit_stats', methods: ['GET'])]
    public function stats(ProduitRepository $produitRepository): Response
    {
        $statistics = $produitRepository->getStatistics();
        $categoryCounts = $produitRepository->getCategoryCounts();

        return $this->render('admin/produit/stats.html.twig', [
            'statistics' => $statistics,
            'categoryCounts' => $categoryCounts,
        ]);
    }

    #[Route('/ia/gaspillage-invisible', name: 'admin_produit_ai_waste_detection', methods: ['GET'])]
    public function aiWasteDetection(InventoryAiService $inventoryAiService): Response
    {
        $result = $inventoryAiService->analyzeWasteDetection();

        return $this->render('admin/produit/ia_waste_detection.html.twig', [
            'analyses' => $result['analyses'],
            'anomaliesCount' => $result['anomaliesCount'],
        ]);
    }

    #[Route('/ia/stock-optimal', name: 'admin_produit_ai_stock_recommendation', methods: ['GET'])]
    public function aiStockRecommendation(
        Request $request,
        InventoryAiService $inventoryAiService,
    ): Response {
        $delaiLivraison = max(1, (int) $request->query->get('delai', 7));
        $margeSecuriteJours = max(0, (int) $request->query->get('marge', 3));

        $recommandations = $inventoryAiService->recommendOptimalStock($delaiLivraison, $margeSecuriteJours);

        return $this->render('admin/produit/ia_stock_recommendation.html.twig', [
            'recommandations' => $recommandations,
            'delaiLivraison' => $delaiLivraison,
            'margeSecuriteJours' => $margeSecuriteJours,
        ]);
    }

    #[Route('/new', name: 'admin_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, StockMailerService $stockMailer): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();

            if ($produit->getQuantiteDisponible() > 200 || $produit->getQuantiteDisponible() < 20) {
                $stockMailer->sendStockAlert($produit);
            }

            $this->addFlash('success', 'Produit créé avec succès.');
            return $this->redirectToRoute('admin_produit_index');
        }

        return $this->render('admin/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_produit}/comment/{id_commentaire}/delete', name: 'admin_produit_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Request $request,
        Produit $produit,
        ProduitComment $commentaire,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($commentaire->getProduit()?->getIdProduit() !== $produit->getIdProduit()) {
            throw $this->createNotFoundException('Commentaire introuvable pour ce produit.');
        }

        if ($this->isCsrfTokenValid('delete_admin_comment_'.$commentaire->getIdCommentaire(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('admin_produit_show', ['id_produit' => $produit->getIdProduit()]);
    }

    #[Route('/{id_produit}', name: 'admin_produit_show', methods: ['GET'])]
    public function show(Produit $produit, ProduitCommentRepository $commentRepository): Response
    {
        return $this->render('admin/produit/show.html.twig', [
            'produit' => $produit,
            'comments' => $commentRepository->findByProduitNewestFirst($produit),
        ]);
    }

    #[Route('/{id_produit}/edit', name: 'admin_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager, StockMailerService $stockMailer): Response
    {
        $oldQuantity = $produit->getQuantiteDisponible();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if (
                ($produit->getQuantiteDisponible() > 200 && $oldQuantity <= 200)
                || ($produit->getQuantiteDisponible() < 20 && $oldQuantity >= 20)
            ) {
                $stockMailer->sendStockAlert($produit);
            }

            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('admin_produit_index');
        }

        return $this->render('admin/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_produit}', name: 'admin_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getIdProduit(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_produit_index');
    }
}
