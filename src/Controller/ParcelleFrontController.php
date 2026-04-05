<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Parcelle;
use App\Entity\User;
use App\Form\ParcelleFormType;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ParcelleFrontController extends AbstractController
{
    #[Route('/parcelle', name: 'app_parcelle_list', methods: ['GET'])]
    public function list(Request $request, ParcelleRepository $parcelleRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'nomParcelle');
        $direction = (string) $request->query->get('direction', 'ASC');

        $currentUser = $this->getCurrentUserEntity();
        $parcelles = $this->isGranted('ROLE_ADMIN')
            ? $parcelleRepository->findFiltered($search, $sort, $direction)
            : $parcelleRepository->findFilteredByOwner($currentUser, $search, $sort, $direction);

        return $this->render('front/parcelle/list.html.twig', [
            'parcelles' => $parcelles,
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'sortOptions' => [
                'nomParcelle' => 'Nom',
                'surface' => 'Surface',
                'typeSol' => 'Type de sol',
                'coordonneesGps' => 'Coordonnees',
            ],
        ]);
    }

    #[Route('/parcelle/new', name: 'app_parcelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parcelle = new Parcelle();
        $form = $this->createForm(ParcelleFormType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parcelle->setOwner($this->getCurrentUserEntity());
            $entityManager->persist($parcelle);
            $entityManager->flush();

            $this->addFlash('success', 'Parcelle creee avec succes.');

            return $this->redirectToRoute('app_parcelle_list');
        }

        return $this->render('front/parcelle/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/parcelle/{id}', name: 'app_parcelle_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Parcelle $parcelle): Response
    {
        $this->denyParcelleAccessIfNeeded($parcelle);

        return $this->render('front/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
        ]);
    }

    #[Route('/parcelle/{id}/edit', name: 'app_parcelle_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        $this->denyParcelleAccessIfNeeded($parcelle);

        $form = $this->createForm(ParcelleFormType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle mise a jour avec succes.');

            return $this->redirectToRoute('app_parcelle_list');
        }

        return $this->render('front/parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/parcelle/{id}/delete', name: 'app_parcelle_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        $this->denyParcelleAccessIfNeeded($parcelle);

        if ($this->isCsrfTokenValid('delete_front_parcelle_' . $parcelle->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($parcelle);
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_parcelle_list');
    }

    private function denyParcelleAccessIfNeeded(Parcelle $parcelle): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($parcelle->getOwner() !== $this->getCurrentUserEntity()) {
            throw $this->createAccessDeniedException('Vous ne pouvez acceder qu a vos parcelles.');
        }
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        return $user;
    }
}
