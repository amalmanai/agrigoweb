<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\ProduitComment;
use App\Entity\User;
use App\Form\ProduitCommentType;
use App\Form\ProduitType;
use App\Repository\ProduitCommentRepository;
use App\Repository\ProduitRepository;
use App\Service\BadWordStrikeService;
use App\Service\CommentAnimalModerationService;
use App\Service\CommentWarningMailerService;
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
        $form = $this->createForm(ProduitType::class, $produit, [
            'include_commentaire' => false,
        ]);
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
    public function comment(
        Request $request,
        Produit $produit,
        EntityManagerInterface $entityManager,
        ProduitCommentRepository $commentRepository,
        CommentAnimalModerationService $commentAnimalModeration,
        CommentWarningMailerService $commentWarningMailer,
        BadWordStrikeService $badWordStrikeService,
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour commenter.');
        }

        $commentaire = new ProduitComment();
        $form = $this->createForm(ProduitCommentType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($commentAnimalModeration->containsAnimalName((string) $commentaire->getContenu())) {
                $strikes = $this->processBadWordViolation($currentUser, $badWordStrikeService, $commentWarningMailer, false);
                if ($strikes >= 3) {
                    $request->getSession()->invalidate();

                    return $this->redirectToRoute('app_login');
                }

                return $this->redirectToRoute('app_produit_show', ['id_produit' => $produit->getIdProduit()]);
            }

            $commentsBefore = $commentRepository->countByUser($currentUser);

            $commentaire->setProduit($produit);
            $commentaire->setUser($currentUser);
            $entityManager->persist($commentaire);
            $entityManager->flush();

            if ($commentsBefore === 1 && !$this->isGranted('ROLE_ADMIN')) {
                $commentWarningMailer->sendSecondCommentWarning($currentUser, $produit);
            }

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
    public function editComment(
        Request $request,
        Produit $produit,
        ProduitComment $commentaire,
        EntityManagerInterface $entityManager,
        ProduitCommentRepository $commentRepository,
        CommentAnimalModerationService $commentAnimalModeration,
        CommentWarningMailerService $commentWarningMailer,
        BadWordStrikeService $badWordStrikeService,
    ): Response {
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
            if ($commentAnimalModeration->containsAnimalName((string) $commentaire->getContenu())) {
                $strikes = $this->processBadWordViolation($currentUser, $badWordStrikeService, $commentWarningMailer, true);
                $entityManager->remove($commentaire);
                $entityManager->flush();
                if ($strikes >= 3) {
                    $request->getSession()->invalidate();

                    return $this->redirectToRoute('app_login');
                }

                return $this->redirectToRoute('app_produit_show', ['id_produit' => $produit->getIdProduit()]);
            }

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
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(Request $request, Produit $produit, ProduitComment $commentaire, EntityManagerInterface $entityManager): Response
    {
        if ($commentaire->getProduit()?->getIdProduit() !== $produit->getIdProduit()) {
            throw $this->createNotFoundException('Commentaire introuvable pour ce produit.');
        }

        if ($this->isCsrfTokenValid('delete_comment_'.$commentaire->getIdCommentaire(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_produit_show', ['id_produit' => $produit->getIdProduit()]);
    }

    /**
     * 1re violation : refus (1/3). 2e : refus (2/3). 3e : compte bloqué (isActive=false) + e-mail + déconnexion (3/3).
     *
     * @return int nombre de strikes après incrément (0 si admin)
     */
    private function processBadWordViolation(
        User $user,
        BadWordStrikeService $badWordStrikeService,
        CommentWarningMailerService $commentWarningMailer,
        bool $isEditContext,
    ): int {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', $isEditContext
                ? 'Votre commentaire a été supprimé automatiquement : termes non autorisés.'
                : 'Votre commentaire n\'a pas été publié : termes non autorisés.');

            return 0;
        }

        $strikes = $badWordStrikeService->incrementStrikesForUser($user);
        if ($strikes === 1) {
            $this->addFlash('warning', $isEditContext
                ? 'Il est interdit d\'écrire un nom d\'animal. Votre commentaire a été supprimé automatiquement. 1/3'
                : 'Il est interdit d\'écrire un nom d\'animal. Votre commentaire n\'a pas été publié. 1/3');
        } elseif ($strikes === 2) {
            $commentWarningMailer->sendBadWordViolationWarning($user);
            $this->addFlash('warning', $isEditContext
                ? 'Votre commentaire a été supprimé automatiquement : termes non autorisés. Un e-mail d\'avertissement vous a été envoyé. La prochaine tentative entraînera le blocage du compte. 2/3'
                : 'Votre commentaire n\'a pas été publié : termes non autorisés. Un e-mail d\'avertissement vous a été envoyé. La prochaine tentative entraînera le blocage du compte. 2/3');
        } else {
            $commentWarningMailer->sendAccountBlockedDueToBadWords($user);
            $this->addFlash('danger', 'Votre compte a été bloqué après plusieurs violations. 3/3. Un e-mail vous a été envoyé. Vous allez être déconnecté.');
        }

        return $strikes;
    }
}
