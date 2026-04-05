<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\User;
use App\Form\CultureFormType;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CultureController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/culture', name: 'app_culture_list', methods: ['GET'])]
    public function frontList(Request $request, CultureRepository $cultureRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'dateSemis');
        $direction = (string) $request->query->get('direction', 'DESC');

        $currentUser = $this->getCurrentUserEntity();
        $cultures = $this->isGranted('ROLE_ADMIN')
            ? $cultureRepository->findFiltered($search, $sort, $direction)
            : $cultureRepository->findFilteredByOwner($currentUser, $search, $sort, $direction);

        return $this->render('front/culture/list.html.twig', [
            'cultures' => $cultures,
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'sortOptions' => [
                'dateSemis' => 'Date semis',
                'nomCulture' => 'Nom',
                'etatCroissance' => 'Etat',
                'rendementPrevu' => 'Rendement',
                'parcelle' => 'Parcelle',
            ],
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/culture/new', name: 'app_culture_new', methods: ['GET', 'POST'])]
    public function frontNew(Request $request, EntityManagerInterface $entityManager, ParcelleRepository $parcelleRepository): Response
    {
        $currentUser = $this->getCurrentUserEntity();
        $parcelleChoices = $this->isGranted('ROLE_ADMIN')
            ? $parcelleRepository->findAllOrderedByName()
            : $parcelleRepository->findFilteredByOwner($currentUser);

        $culture = new Culture();
        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_choices' => $parcelleChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $selectedParcelle = $culture->getParcelle();
                if (!$selectedParcelle instanceof Parcelle || $selectedParcelle->getOwner() !== $currentUser) {
                    throw $this->createAccessDeniedException('Vous ne pouvez selectionner que vos parcelles.');
                }
            }

            $culture->setOwner($this->isGranted('ROLE_ADMIN') ? $culture->getParcelle()?->getOwner() : $currentUser);

            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTimeImmutable('now'));
            }

            $entityManager->persist($culture);
            $entityManager->flush();
            $this->addFlash('success', 'Culture creee avec succes.');

            return $this->redirectToRoute('app_culture_list');
        }

        return $this->render('front/culture/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/culture/{id}', name: 'app_culture_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function frontShow(Culture $culture): Response
    {
        $this->denyCultureAccessIfNeeded($culture);

        return $this->render('front/culture/show.html.twig', [
            'culture' => $culture,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/culture/{id}/edit', name: 'app_culture_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function frontEdit(Request $request, Culture $culture, EntityManagerInterface $entityManager, ParcelleRepository $parcelleRepository): Response
    {
        $this->denyCultureAccessIfNeeded($culture);

        $currentUser = $this->getCurrentUserEntity();
        $parcelleChoices = $this->isGranted('ROLE_ADMIN')
            ? $parcelleRepository->findAllOrderedByName()
            : $parcelleRepository->findFilteredByOwner($currentUser);

        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_choices' => $parcelleChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $selectedParcelle = $culture->getParcelle();
                if (!$selectedParcelle instanceof Parcelle || $selectedParcelle->getOwner() !== $currentUser) {
                    throw $this->createAccessDeniedException('Vous ne pouvez selectionner que vos parcelles.');
                }
            }

            $culture->setOwner($this->isGranted('ROLE_ADMIN') ? $culture->getParcelle()?->getOwner() : $currentUser);

            $entityManager->flush();
            $this->addFlash('success', 'Culture mise a jour avec succes.');

            return $this->redirectToRoute('app_culture_list');
        }

        return $this->render('front/culture/edit.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/culture/{id}/delete', name: 'app_culture_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function frontDelete(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        $this->denyCultureAccessIfNeeded($culture);

        if ($this->isCsrfTokenValid('delete_front_culture_' . $culture->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($culture);
            $entityManager->flush();
            $this->addFlash('success', 'Culture supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_culture_list');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/culture', name: 'admin_culture_index', methods: ['GET'])]
    public function backIndex(Request $request, CultureRepository $cultureRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'dateSemis');
        $direction = (string) $request->query->get('direction', 'DESC');

        return $this->render('back/culture/index.html.twig', [
            'cultures' => $cultureRepository->findFiltered($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'sortOptions' => [
                'dateSemis' => 'Date semis',
                'nomCulture' => 'Nom',
                'etatCroissance' => 'Etat',
                'rendementPrevu' => 'Rendement',
                'parcelle' => 'Parcelle',
            ],
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/culture/new', name: 'admin_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParcelleRepository $parcelleRepository): Response
    {
        $culture = new Culture();
        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_choices' => $parcelleRepository->findAllOrderedByName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTimeImmutable('now'));
            }

            $culture->setOwner($culture->getParcelle()?->getOwner());
            $entityManager->persist($culture);
            $entityManager->flush();

            $this->addFlash('success', 'Culture creee avec succes.');

            return $this->redirectToRoute('admin_culture_index');
        }

        return $this->render('back/culture/new.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/culture/{id}/edit', name: 'admin_culture_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Culture $culture, EntityManagerInterface $entityManager, ParcelleRepository $parcelleRepository): Response
    {
        $form = $this->createForm(CultureFormType::class, $culture, [
            'parcelle_choices' => $parcelleRepository->findAllOrderedByName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTimeImmutable('now'));
            }

            $culture->setOwner($culture->getParcelle()?->getOwner());

            $entityManager->flush();

            $this->addFlash('success', 'Culture mise a jour avec succes.');

            return $this->redirectToRoute('admin_culture_index');
        }

        return $this->render('back/culture/edit.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/culture/{id}', name: 'admin_culture_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_culture_' . $culture->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($culture);
            $entityManager->flush();
            $this->addFlash('success', 'Culture supprimee avec succes.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_culture_index');
    }

    private function denyCultureAccessIfNeeded(Culture $culture): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($culture->getOwner() !== $this->getCurrentUserEntity()) {
            throw $this->createAccessDeniedException('Vous ne pouvez acceder qu a vos cultures.');
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
