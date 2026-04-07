<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Entity\User;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tache')]
#[IsGranted('ROLE_USER')]
class TacheController extends AbstractController
{
    #[Route('/', name: 'app_tache_index', methods: ['GET'])]
    public function index(TacheRepository $tacheRepository): Response
    {
        $user = $this->requireUser();

        return $this->render('tache/index.html.twig', [
            'taches' => $tacheRepository->findByOwnerId($user->getIdUser()),
        ]);
    }

    #[Route('/new', name: 'app_tache_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireUser();
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tache->setIdUser($user->getIdUser());

            $entityManager->persist($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche créée avec succès!');

            return $this->redirectToRoute('app_tache_index');
        }

        return $this->render('tache/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tache_show', methods: ['GET'])]
    public function show(Tache $tache): Response
    {
        $this->denyTacheAccessUnlessOwner($tache);

        return $this->render('tache/show.html.twig', [
            'tache' => $tache,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tache_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        $this->denyTacheAccessUnlessOwner($tache);

        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Tâche mise à jour avec succès!');

            return $this->redirectToRoute('app_tache_index');
        }

        return $this->render('tache/edit.html.twig', [
            'form' => $form,
            'tache' => $tache,
        ]);
    }

    #[Route('/{id}', name: 'app_tache_delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        $this->denyTacheAccessUnlessOwner($tache);

        if ($this->isCsrfTokenValid('delete' . $tache->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tache);
            $entityManager->flush();
            $this->addFlash('success', 'Tâche supprimée avec succès!');
        }

        return $this->redirectToRoute('app_tache_index');
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function denyTacheAccessUnlessOwner(Tache $tache): void
    {
        $user = $this->requireUser();
        if ($tache->getIdUser() !== $user->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos tâches.');
        }
    }
}
