<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Vente;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vente')]
#[IsGranted('ROLE_USER')]
class VenteController extends AbstractController
{
    #[Route('/legacy', name: 'app_vente_legacy_index', methods: ['GET'])]
    public function index(VenteRepository $venteRepository): Response
    {
        $user = $this->requireUser();

        return $this->render('vente/index.html.twig', [
            'ventes' => $venteRepository->findForUser($user->getIdUser()),
            'total_revenue' => $venteRepository->getTotalRevenueForUser($user->getIdUser()),
        ]);
    }

    #[Route('/legacy/new', name: 'app_vente_legacy_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireUser();
        $vente = new Vente();
        $form = $this->createForm(VenteType::class, $vente, [
            'recolte_owner' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recolte = $vente->getRecolte();
            if (!$recolte || $recolte->getUserId() !== $user->getIdUser()) {
                throw $this->createAccessDeniedException('Sélectionnez une de vos récoltes.');
            }

            $entityManager->persist($vente);
            $entityManager->flush();

            $this->addFlash('success', 'La vente a été ajoutée avec succès.');

            return $this->redirectToRoute('app_vente_legacy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/legacy/{id}', name: 'app_vente_legacy_show', methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        $this->denyVenteAccessUnlessOwner($vente);

        return $this->render('vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/legacy/{id}/edit', name: 'app_vente_legacy_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        $this->denyVenteAccessUnlessOwner($vente);
        $user = $this->requireUser();

        $form = $this->createForm(VenteType::class, $vente, [
            'recolte_owner' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recolte = $vente->getRecolte();
            if (!$recolte || $recolte->getUserId() !== $user->getIdUser()) {
                throw $this->createAccessDeniedException('Sélectionnez une de vos récoltes.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_vente_legacy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/legacy/{id}', name: 'app_vente_legacy_delete', methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        $this->denyVenteAccessUnlessOwner($vente);

        if ($this->isCsrfTokenValid('delete' . $vente->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vente_legacy_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_vente_pdf', methods: ['GET'])]
    public function generatePdf(Vente $vente): Response
    {
        $this->denyVenteAccessUnlessOwner($vente);

        $html = $this->renderView('vente/facture_pdf.html.twig', [
            'vente' => $vente,
            'numero_facture' => 'FAC-' . date('Y') . '-' . str_pad((string) $vente->getId(), 5, '0', STR_PAD_LEFT),
            'date_edition' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'facture_' . str_pad((string) $vente->getId(), 5, '0', STR_PAD_LEFT) . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/{id}/rate', name: 'app_vente_rate', methods: ['POST'])]
    public function rate(Request $request, Vente $vente, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\JsonResponse
    {
        try {
            $this->denyVenteAccessUnlessOwner($vente);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Unauthorized access.'], 403);
        }

        if ($vente->getStatus() !== 'Completed') {
            return $this->json(['error' => 'Only completed sales can be rated.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['rating'])) {
            return $this->json(['error' => 'Rating value missing.'], 400);
        }

        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            return $this->json(['error' => 'Rating must be between 1 and 5.'], 400);
        }

        $vente->setRating($rating);
        $entityManager->flush();

        return $this->json(['success' => true, 'rating' => $rating]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function denyVenteAccessUnlessOwner(Vente $vente): void
    {
        $user = $this->requireUser();
        $recolte = $vente->getRecolte();
        if (!$recolte || $recolte->getUserId() !== $user->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos ventes.');
        }
    }
}
