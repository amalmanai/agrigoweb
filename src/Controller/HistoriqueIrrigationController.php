<?php

namespace App\Controller;

use App\Entity\HistoriqueIrrigation;
use App\Entity\User;
use App\Form\HistoriqueIrrigationType;
use App\Repository\HistoriqueIrrigationRepository;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HistoriqueIrrigationController extends AbstractController
{
    use CrudRoutesTrait;

    private function layoutFromRequest(Request $request): string
    {
        return str_starts_with($request->getPathInfo(), '/admin')
            ? 'back/base.html.twig'
            : 'front/base.html.twig';
    }

    private function isAdminArea(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/admin');
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route(path: '/historique-irrigation', name: 'front_historique_irrigation_index', methods: ['GET'])]
    #[Route(path: '/admin/historique-irrigation', name: 'admin_historique_irrigation_index', methods: ['GET'])]
    public function index(HistoriqueIrrigationRepository $repository, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $tri = $request->query->getString('tri', 'date_desc');
        $owner = $this->isAdminArea($request) ? null : $this->requireUser();

        return $this->render('historique_irrigation/index.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'historiques' => $repository->findAllFiltered('' !== $q ? $q : null, $tri, $owner),
            'search_q' => $q,
            'tri_courant' => $tri,
        ]);
    }

    #[Route(path: '/historique-irrigation/new', name: 'front_historique_irrigation_new', methods: ['GET', 'POST'])]
    #[Route(path: '/admin/historique-irrigation/new', name: 'admin_historique_irrigation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $historique = new HistoriqueIrrigation();
        $historique->setDateIrrigation(new \DateTime());

        $form = $this->createForm(HistoriqueIrrigationType::class, $historique, [
            'systeme_owner' => $this->isAdminArea($request) ? null : $this->requireUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($historique);
            $em->flush();
            $this->addFlash('success', 'Historique enregistré.');

            return $this->redirectToRoute($this->crudRoute($request, 'index'));
        }

        return $this->render('historique_irrigation/new.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'historique' => $historique,
            'form' => $form,
        ]);
    }

    #[Route(path: '/historique-irrigation/{id}', name: 'front_historique_irrigation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[Route(path: '/admin/historique-irrigation/{id}', name: 'admin_historique_irrigation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(HistoriqueIrrigation $historique, Request $request, ParcelleRepository $parcelleRepository): Response
    {
        $this->denyHistoriqueAccessUnlessFrontOwner($historique, $request, $parcelleRepository);

        return $this->render('historique_irrigation/show.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'historique' => $historique,
        ]);
    }

    #[Route(path: '/historique-irrigation/{id}/edit', name: 'front_historique_irrigation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[Route(path: '/admin/historique-irrigation/{id}/edit', name: 'admin_historique_irrigation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, HistoriqueIrrigation $historique, EntityManagerInterface $em, ParcelleRepository $parcelleRepository): Response
    {
        $this->denyHistoriqueAccessUnlessFrontOwner($historique, $request, $parcelleRepository);

        $form = $this->createForm(HistoriqueIrrigationType::class, $historique, [
            'systeme_owner' => $this->isAdminArea($request) ? null : $this->requireUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Historique mis à jour.');

            return $this->redirectToRoute($this->crudRoute($request, 'show'), [
                'id' => $historique->getId(),
            ]);
        }

        return $this->render('historique_irrigation/edit.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'historique' => $historique,
            'form' => $form,
        ]);
    }

    #[Route(path: '/historique-irrigation/{id}', name: 'front_historique_irrigation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Route(path: '/admin/historique-irrigation/{id}', name: 'admin_historique_irrigation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, HistoriqueIrrigation $historique, EntityManagerInterface $em, ParcelleRepository $parcelleRepository): Response
    {
        $this->denyHistoriqueAccessUnlessFrontOwner($historique, $request, $parcelleRepository);

        if ($this->isCsrfTokenValid('delete'.$historique->getId(), (string) $request->request->get('_token'))) {
            $em->remove($historique);
            $em->flush();
            $this->addFlash('success', 'Enregistrement supprimé.');
        }

        return $this->redirectToRoute($this->crudRoute($request, 'index'));
    }

    private function denyHistoriqueAccessUnlessFrontOwner(HistoriqueIrrigation $historique, Request $request, ParcelleRepository $parcelleRepository): void
    {
        if ($this->isAdminArea($request)) {
            return;
        }

        $user = $this->requireUser();
        $sys = $historique->getSystemeIrrigation();
        if (!$sys) {
            throw $this->createAccessDeniedException();
        }

        $parcelle = $parcelleRepository->find($sys->getIdParcelle());
        if (!$parcelle || $parcelle->getOwner() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos historiques d’irrigation.');
        }
    }
}
