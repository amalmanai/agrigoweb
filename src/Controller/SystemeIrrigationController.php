<?php

namespace App\Controller;

use App\Entity\SystemeIrrigation;
use App\Entity\User;
use App\Form\SystemeIrrigationType;
use App\Repository\ParcelleRepository;
use App\Repository\SystemeIrrigationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SystemeIrrigationController extends AbstractController
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

    /**
     * @return array{0: list<SystemeIrrigation>, 1: array<int, \App\Entity\Parcelle>}
     */
    private function parcellesParId(SystemeIrrigationRepository $repository, ParcelleRepository $parcelleRepository, ?string $q, string $tri, ?User $parcelleOwner): array
    {
        $systemes = $repository->findAllWithExistingParcelle($q ?: null, $tri, $parcelleOwner);
        $ids = array_unique(array_filter(array_map(fn (SystemeIrrigation $s) => $s->getIdParcelle(), $systemes)));
        if ($ids === []) {
            return [$systemes, []];
        }
        $parcelles = $parcelleRepository->findBy(['id' => $ids]);
        $map = [];
        foreach ($parcelles as $p) {
            $map[$p->getId()] = $p;
        }

        return [$systemes, $map];
    }

    #[Route(path: '/systeme-irrigation', name: 'front_systeme_irrigation_index', methods: ['GET'])]
    #[Route(path: '/admin/systeme-irrigation', name: 'admin_systeme_irrigation_index', methods: ['GET'])]
    public function index(SystemeIrrigationRepository $repository, ParcelleRepository $parcelleRepository, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $tri = $request->query->getString('tri', 'nom_asc');
        $owner = $this->isAdminArea($request) ? null : $this->requireUser();
        [$systemes, $parcellesParId] = $this->parcellesParId($repository, $parcelleRepository, '' !== $q ? $q : null, $tri, $owner);

        return $this->render('systeme_irrigation/index.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'systemes' => $systemes,
            'parcellesParId' => $parcellesParId,
            'search_q' => $q,
            'tri_courant' => $tri,
        ]);
    }

    #[Route(path: '/systeme-irrigation/new', name: 'front_systeme_irrigation_new', methods: ['GET', 'POST'])]
    #[Route(path: '/admin/systeme-irrigation/new', name: 'admin_systeme_irrigation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $systeme = new SystemeIrrigation();
        $systeme->setDateCreation(new \DateTime());
        $systeme->setSeuilHumidite('30.00');
        $systeme->setMode('MANUEL');
        $systeme->setStatut('ACTIF');

        $form = $this->createForm(SystemeIrrigationType::class, $systeme, [
            'parcelle_owner' => $this->isAdminArea($request) ? null : $this->requireUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parcelle = $form->get('parcelle')->getData();
            if (!$parcelle) {
                // parcelle is unmapped so required:true alone won't catch null server-side
                $form->get('parcelle')->addError(
                    new \Symfony\Component\Form\FormError('Veuillez sélectionner une parcelle.')
                );
            } else {
                $systeme->setIdParcelle($parcelle->getId());
                $em->persist($systeme);
                $em->flush();
                $this->addFlash('success', 'Système d\'irrigation enregistré.');

                return $this->redirectToRoute($this->crudRoute($request, 'index'));
            }
        }

        return $this->render('systeme_irrigation/new.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'systeme' => $systeme,
            'form' => $form,
        ]);
    }

    #[Route(path: '/systeme-irrigation/{idSysteme}', name: 'front_systeme_irrigation_show', requirements: ['idSysteme' => '\d+'], methods: ['GET'])]
    #[Route(path: '/admin/systeme-irrigation/{idSysteme}', name: 'admin_systeme_irrigation_show', requirements: ['idSysteme' => '\d+'], methods: ['GET'])]
    public function show(int $idSysteme, SystemeIrrigationRepository $repository, ParcelleRepository $parcelleRepository, Request $request): Response
    {
        $owner = $this->isAdminArea($request) ? null : $this->requireUser();
        $systeme = $repository->findOneWithExistingParcelle($idSysteme, $owner);
        if (!$systeme) {
            throw $this->createNotFoundException();
        }
        $parcelle = $parcelleRepository->find($systeme->getIdParcelle());

        return $this->render('systeme_irrigation/show.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'systeme' => $systeme,
            'parcelle' => $parcelle,
        ]);
    }

    #[Route(path: '/systeme-irrigation/{idSysteme}/edit', name: 'front_systeme_irrigation_edit', requirements: ['idSysteme' => '\d+'], methods: ['GET', 'POST'])]
    #[Route(path: '/admin/systeme-irrigation/{idSysteme}/edit', name: 'admin_systeme_irrigation_edit', requirements: ['idSysteme' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $idSysteme, SystemeIrrigationRepository $repository, EntityManagerInterface $em): Response
    {
        $owner = $this->isAdminArea($request) ? null : $this->requireUser();
        $systeme = $repository->findOneWithExistingParcelle($idSysteme, $owner);
        if (!$systeme) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(SystemeIrrigationType::class, $systeme, [
            'parcelle_owner' => $this->isAdminArea($request) ? null : $this->requireUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parcelle = $form->get('parcelle')->getData();
            if (!$parcelle) {
                $form->get('parcelle')->addError(
                    new \Symfony\Component\Form\FormError('Veuillez sélectionner une parcelle.')
                );
            } else {
                $systeme->setIdParcelle($parcelle->getId());
                $em->flush();
                $this->addFlash('success', 'Système mis à jour.');

                return $this->redirectToRoute($this->crudRoute($request, 'show'), [
                    'idSysteme' => $systeme->getIdSysteme(),
                ]);
            }
        }

        return $this->render('systeme_irrigation/edit.html.twig', [
            'layout' => $this->layoutFromRequest($request),
            'systeme' => $systeme,
            'form' => $form,
        ]);
    }

    #[Route(path: '/systeme-irrigation/{idSysteme}', name: 'front_systeme_irrigation_delete', requirements: ['idSysteme' => '\d+'], methods: ['POST'])]
    #[Route(path: '/admin/systeme-irrigation/{idSysteme}', name: 'admin_systeme_irrigation_delete', requirements: ['idSysteme' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $idSysteme, SystemeIrrigationRepository $repository, EntityManagerInterface $em): Response
    {
        $owner = $this->isAdminArea($request) ? null : $this->requireUser();
        $systeme = $owner
            ? $repository->findOneWithExistingParcelle($idSysteme, $owner)
            : $repository->find($idSysteme);
        if (!$systeme) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete'.$systeme->getIdSysteme(), (string) $request->request->get('_token'))) {
            $em->remove($systeme);
            $em->flush();
            $this->addFlash('success', 'Système supprimé.');
        }

        return $this->redirectToRoute($this->crudRoute($request, 'index'));
    }
}
