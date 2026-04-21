<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Vente;
use App\Entity\User;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use App\Repository\RecolteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/vente')]
class VenteFrontController extends AbstractController
{
    #[Route('/', name: 'app_vente_list', methods: ['GET'])]
    public function list(Request $request, VenteRepository $venteRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'saleDate');
        $direction = (string) $request->query->get('direction', 'DESC');

        $currentUser = $this->getCurrentUserEntity();

        // Get ventes linked to user's recoltes
        $ventes = $venteRepository->findForUser($currentUser->getIdUser());

        // Filter by search if provided
        if ($search) {
            $ventes = array_filter($ventes, function (Vente $vente) use ($search) {
                return stripos($vente->getDescription(), $search) !== false
                    || stripos((string) $vente->getBuyerName(), $search) !== false;
            });
        }

        return $this->render('front/vente/list.html.twig', [
            'ventes' => $ventes,
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'sortOptions' => [
                'saleDate' => 'Date de vente',
                'price' => 'Prix',
                'buyerName' => 'Acheteur',
                'status' => 'Statut',
            ],
        ]);
    }

    #[Route('/new', name: 'app_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vente = new Vente();
        $form = $this->createForm(VenteType::class, $vente, [
            'recolte_owner' => $this->getCurrentUserEntity(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vente);
            $entityManager->flush();

            $this->addFlash('success', 'Vente créée avec succès.');

            return $this->redirectToRoute('app_vente_list');
        }

        return $this->render('front/vente/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_vente_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        $this->denyVenteAccessIfNeeded($vente);

        return $this->render('front/vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vente_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        $this->denyVenteAccessIfNeeded($vente);

        $form = $this->createForm(VenteType::class, $vente, [
            'recolte_owner' => $this->getCurrentUserEntity(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vente mise à jour avec succès.');

            return $this->redirectToRoute('app_vente_list');
        }

        return $this->render('front/vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_vente_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        $this->denyVenteAccessIfNeeded($vente);

        if ($this->isCsrfTokenValid('delete_front_vente_' . $vente->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
            $this->addFlash('success', 'Vente supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_vente_list');
    }

    private function denyVenteAccessIfNeeded(Vente $vente): void
    {
        $currentUser = $this->getCurrentUserEntity();
        $recolte = $vente->getRecolte();

        if ($recolte && $recolte->getUserId() !== $currentUser->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu\'à vos ventes.');
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
