<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\ProduitComment;
use App\Entity\User;
use App\Form\ProduitCommentType;
use App\Form\ProduitType;
use App\Repository\ProduitCommentRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/produit')]
#[IsGranted('ROLE_USER')]
class ProduitFrontController extends AbstractController
{
    #[Route('/', name: 'app_produit_list', methods: ['GET'])]
    public function list(Request $request, ProduitRepository $produitRepository): Response
    {
        // Add search or sorting here similarly to Vente
        $search = trim((string) $request->query->get('search', ''));
        $produits = $produitRepository->findAll();

        if ($search) {
            $produits = array_filter($produits, function(Produit $produit) use ($search) {
                return stripos($produit->getNomProduit(), $search) !== false 
                    || stripos($produit->getCategorie(), $search) !== false;
            });
        }

        return $this->render('front/produit/list.html.twig', [
            'produits' => $produits,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès.');
            return $this->redirectToRoute('app_produit_list');
        }

        return $this->render('front/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_produit}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit, ProduitCommentRepository $commentRepository): Response
    {
        $commentaire = new ProduitComment();
        $commentForm = $this->createForm(ProduitCommentType::class, $commentaire);

        return $this->render('front/produit/show.html.twig', [
            'produit' => $produit,
            'comments' => $commentRepository->findByProduitNewestFirst($produit),
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/{id_produit}/comment', name: 'app_produit_comment', methods: ['POST'])]
    public function comment(Request $request, Produit $produit, EntityManagerInterface $entityManager, ProduitCommentRepository $commentRepository): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour commenter.');
        }

        $commentaire = new ProduitComment();
        $form = $this->createForm(ProduitCommentType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setProduit($produit);
            $commentaire->setUser($currentUser);
            $entityManager->persist($commentaire);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a bien été ajouté.');
            return $this->redirectToRoute('app_produit_show', ['id_produit' => $produit->getIdProduit()]);
        }

        return $this->render('front/produit/show.html.twig', [
            'produit' => $produit,
            'comments' => $commentRepository->findByProduitNewestFirst($produit),
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/{id_produit}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('app_produit_list');
        }

        return $this->render('front/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_produit}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_front_produit_'.$produit->getIdProduit(), (string) $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_produit_list');
    }

    #[Route('/{id_produit}/comment/{id_commentaire}/edit', name: 'app_produit_comment_edit', methods: ['GET', 'POST'])]
    public function editComment(Request $request, Produit $produit, ProduitComment $commentaire, EntityManagerInterface $entityManager, ProduitCommentRepository $commentRepository): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        if ($commentaire->getUser()->getIdUser() !== $currentUser->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres commentaires.');
        }

        $form = $this->createForm(ProduitCommentType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setDateCommentaire(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a bien été modifié.');
            return $this->redirectToRoute('app_produit_show', ['id_produit' => $produit->getIdProduit()]);
        }

        return $this->render('front/produit/comment_edit.html.twig', [
            'produit' => $produit,
            'commentaire' => $commentaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_produit}/comment/{id_commentaire}/delete', name: 'app_produit_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Produit $produit, ProduitComment $commentaire, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        if ($commentaire->getUser()->getIdUser() !== $currentUser->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        if ($this->isCsrfTokenValid('delete_comment_'.$commentaire->getIdCommentaire(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a bien été supprimé.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_produit_show', ['id_produit' => $produit->getIdProduit()]);
    }
}
