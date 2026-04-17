<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vente')]
class VenteController extends AbstractController
{
    #[Route('/', name: 'app_vente_index', methods: ['GET'])]
    public function index(VenteRepository $venteRepository): Response
    {
        return $this->render('vente/index.html.twig', [
            'ventes' => $venteRepository->findAll(),
            'total_revenue' => $venteRepository->getTotalRevenue(),
        ]);
    }

    #[Route('/new', name: 'app_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vente = new Vente();
        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vente);
            $entityManager->flush();

            $this->addFlash('success', 'La vente a été ajoutée avec succès.');

            return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_vente_show', methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        return $this->render('vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_vente_delete', methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vente->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_vente_pdf', methods: ['GET'])]
    public function generatePdf(Vente $vente): Response
    {
        // Générer le HTML de la facture
        $html = $this->renderView('vente/facture_pdf.html.twig', [
            'vente' => $vente,
            'numero_facture' => 'FAC-' . date('Y') . '-' . str_pad($vente->getId(), 5, '0', STR_PAD_LEFT),
            'date_edition' => new \DateTime(),
        ]);

        // Configurer Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'facture_' . str_pad($vente->getId(), 5, '0', STR_PAD_LEFT) . '.pdf';

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
    public function rate(Request $request, Vente $vente, EntityManagerInterface $entityManager): JsonResponse
    {
        // 1. Ensure the sale is completed
        if ($vente->getStatus() !== 'Completed') {
            return new JsonResponse(['success' => false, 'message' => 'Seules les ventes terminées peuvent être notées.'], 403);
        }

        // 2. Parse the incoming JSON
        $data = json_decode($request->getContent(), true);
        $rating = isset($data['rating']) ? (int) $data['rating'] : null;

        // 3. Validate rating
        if ($rating === null || $rating < 1 || $rating > 5) {
            return new JsonResponse(['success' => false, 'message' => 'La note doit être comprise entre 1 et 5.'], 400);
        }

        // 4. Save
        $vente->setRating($rating);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Merci pour votre note !',
            'rating' => $rating
        ], 200);
    }
}