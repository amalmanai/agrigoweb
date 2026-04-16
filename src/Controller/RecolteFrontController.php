<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Recolte;
use App\Entity\User;
use App\Form\RecolteType;
use App\Repository\RecolteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/recolte')]
class RecolteFrontController extends AbstractController
{
    #[Route('/', name: 'app_recolte_list', methods: ['GET'])]
    public function list(Request $request, RecolteRepository $recolteRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'name');
        $direction = (string) $request->query->get('direction', 'ASC');

        $currentUser = $this->getCurrentUserEntity();
        $recoltes = $recolteRepository->searchAndSortForUser(
            $currentUser->getIdUser(),
            $search,
            strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'
        );

        return $this->render('front/recolte/list.html.twig', [
            'recoltes' => $recoltes,
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'sortOptions' => [
                'name' => 'Nom du produit',
                'quantity' => 'Quantité',
                'harvestDate' => 'Date de récolte',
                'productionCost' => 'Coût de production',
            ],
        ]);
    }

    #[Route('/new', name: 'app_recolte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recolte = new Recolte();
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recolte->setUserId($this->getCurrentUserEntity()->getIdUser());
            $entityManager->persist($recolte);
            $entityManager->flush();

            $this->addFlash('success', 'Récolte créée avec succès.');

            return $this->redirectToRoute('app_recolte_list');
        }

        return $this->render('front/recolte/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_recolte_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Recolte $recolte): Response
    {
        $this->denyRecolteAccessIfNeeded($recolte);

        return $this->render('front/recolte/show.html.twig', [
            'recolte' => $recolte,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recolte_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $this->denyRecolteAccessIfNeeded($recolte);

        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Récolte mise à jour avec succès.');

            return $this->redirectToRoute('app_recolte_list');
        }

        return $this->render('front/recolte/edit.html.twig', [
            'recolte' => $recolte,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_recolte_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $this->denyRecolteAccessIfNeeded($recolte);

        if ($this->isCsrfTokenValid('delete_front_recolte_' . $recolte->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($recolte);
            $entityManager->flush();
            $this->addFlash('success', 'Récolte supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_recolte_list');
    }

    private function denyRecolteAccessIfNeeded(Recolte $recolte): void
    {
        if ($recolte->getUserId() !== $this->getCurrentUserEntity()->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu\'à vos récoltes.');
        }
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        return $user;
    }
}
