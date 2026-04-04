<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Culture;
use App\Form\CultureFormType;
use App\Repository\CultureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CultureController extends AbstractController
{
    #[Route('/culture', name: 'app_culture_list', methods: ['GET'])]
    public function frontList(Request $request, CultureRepository $cultureRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'dateSemis');
        $direction = (string) $request->query->get('direction', 'DESC');

        return $this->render('front/culture/list.html.twig', [
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

    #[Route('/culture/{id}', name: 'app_culture_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function frontShow(Culture $culture): Response
    {
        return $this->render('front/culture/show.html.twig', [
            'culture' => $culture,
        ]);
    }

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

    #[Route('/admin/culture/new', name: 'admin_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $culture = new Culture();
        $form = $this->createForm(CultureFormType::class, $culture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTimeImmutable('now'));
            }

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

    #[Route('/admin/culture/{id}/edit', name: 'admin_culture_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CultureFormType::class, $culture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($culture->getDateSemis() === null) {
                $culture->setDateSemis(new \DateTimeImmutable('now'));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Culture mise a jour avec succes.');

            return $this->redirectToRoute('admin_culture_index');
        }

        return $this->render('back/culture/edit.html.twig', [
            'culture' => $culture,
            'form' => $form->createView(),
        ]);
    }

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
}
