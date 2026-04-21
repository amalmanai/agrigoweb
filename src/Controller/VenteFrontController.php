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
use Symfony\Component\HttpFoundation\JsonResponse;
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
            'purchase_orders' => [], // marketplace buy orders (future feature)
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'sortOptions' => [
                'saleDate' => 'Date de vente',
                'price' => 'Prix',
                'buyerName' => 'Acheteur',
                'status' => 'Statut',
            ],
            'firebase_vapid_key' => $this->getFirebaseVapidKey(),
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
            'stripe_publishable_key' => (string) $this->getParameter('stripe_publishable_key'),
        ]);
    }

    #[Route('/{id}', name: 'app_vente_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        $this->denyVenteAccessIfNeeded($vente);

        $currentUser = $this->getCurrentUserEntity();
        $isOwner = $vente->getRecolte()
            ? $vente->getRecolte()->getUserId() === $currentUser->getIdUser()
            : true;

        return $this->render('front/vente/show.html.twig', [
            'vente' => $vente,
            'is_owner' => $isOwner,
            'pickup_location_label' => $vente->getRecolte() ? 'Ferme (' . $vente->getRecolte()->getName() . ')' : 'Domaine agricole',
            'delivery_location_label' => 'A convenir avec l\'acheteur',
            'map_fallback_view_url' => null,
            'firebase_vapid_key' => $this->getFirebaseVapidKey(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vente_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager, FirebaseNotificationService $firebaseNotificationService): Response
    {
        $this->denyVenteAccessIfNeeded($vente);

        $previousPrice = $vente->getPrice();
        $previousStatus = (string) $vente->getStatus();

        $form = $this->createForm(VenteType::class, $vente, [
            'recolte_owner' => $this->getCurrentUserEntity(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $currentUser = $this->getCurrentUserEntity();
            $newPrice = $vente->getPrice();
            $newStatus = (string) $vente->getStatus();

            if (abs($newPrice - $previousPrice) > 0.001) {
                $firebaseNotificationService->send(
                    $currentUser,
                    'Prix de vente mis à jour',
                    sprintf('Le prix de "%s" a changé de %.2f TND à %.2f TND.', (string) $vente->getDescription(), $previousPrice, $newPrice),
                    [
                        'event' => 'vente_price_changed',
                        'vente_id' => (string) $vente->getId(),
                        'click_action' => $this->generateUrl('app_vente_show', ['id' => $vente->getId()]),
                    ]
                );

                $this->addFlash('vente_notification', [
                    'title' => 'Prix de vente mis à jour',
                    'body' => sprintf('Le prix de "%s" a changé de %.2f TND à %.2f TND.', (string) $vente->getDescription(), $previousPrice, $newPrice),
                    'tone' => 'success',
                ]);
            }

            if ($previousStatus !== 'Completed' && $newStatus === 'Completed') {
                $firebaseNotificationService->send(
                    $currentUser,
                    'Récolte prête',
                    sprintf('La vente "%s" est passée au statut complété.', (string) $vente->getDescription()),
                    [
                        'event' => 'harvest_ready',
                        'vente_id' => (string) $vente->getId(),
                        'click_action' => $this->generateUrl('app_vente_show', ['id' => $vente->getId()]),
                    ]
                );

                $this->addFlash('vente_notification', [
                    'title' => 'Récolte prête',
                    'body' => sprintf('La vente "%s" est passée au statut complété.', (string) $vente->getDescription()),
                    'tone' => 'success',
                ]);
            }

            $this->addFlash('success', 'Vente mise à jour avec succès.');

            return $this->redirectToRoute('app_vente_list');
        }

        return $this->render('front/vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form->createView(),
            'stripe_publishable_key' => (string) $this->getParameter('stripe_publishable_key'),
            'firebase_vapid_key' => $this->getFirebaseVapidKey(),
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
    #[Route('/assistant', name: 'app_vente_assistant', methods: ['POST'])]
    public function assistant(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true) ?? [];
        $message = trim((string) ($data['message'] ?? ''));

        if (!$message) {
            return $this->json(['error' => 'Message vide'], Response::HTTP_BAD_REQUEST);
        }

        $messageLower = mb_strtolower($message);

        $reply = "Bonjour ! Je suis l'assistant Vente. Mentionnez 'vendre', 'acheter', 'prix' ou 'statut' pour plus d'informations.";

        if (str_contains($messageLower, 'acheter') || str_contains($messageLower, 'quoi acheter')) {
            $reply = "Pour acheter du nouveau matériel ou des semences, n'hésitez pas à consulter notre section Marketplace.";
        } elseif (str_contains($messageLower, 'vendre') || str_contains($messageLower, 'vente')) {
            $reply = "Pour enregistrer une nouvelle transaction, cliquez sur le bouton 'Ajouter une Vente'.";
        } elseif (str_contains($messageLower, 'prix') || str_contains($messageLower, 'combien')) {
            $reply = "Les prix sont affichés en TND pour chaque vente et dépendent des coûts de production de la récolte liée.";
        } elseif (str_contains($messageLower, 'statut')) {
            $reply = "Vos ventes peuvent avoir le statut 'En attente' ou 'Complétée'. Modifiez-les si nécessaire en cliquant sur l'icône de crayon.";
        } elseif (str_contains($messageLower, 'quoi')) {
            $reply = "Vous pouvez consulter la Marketplace pour vérifier la disponibilité.";
        }

        return $this->json(['reply' => $reply]);
    }

    #[Route('/notifications/token', name: 'app_vente_fcm_register_token', methods: ['POST'])]
    public function registerFirebaseToken(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUser = $this->getCurrentUserEntity();
        $data = json_decode((string) $request->getContent(), true) ?? [];
        $token = trim((string) ($data['token'] ?? ''));

        if ($token === '') {
            return $this->json(['error' => 'Jeton Firebase manquant.'], Response::HTTP_BAD_REQUEST);
        }

        $currentUser->setFcmToken($token);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Jeton Firebase enregistré.',
            'token' => $token,
        ]);
    }

    #[Route('/payment-intent', name: 'app_vente_payment_intent', methods: ['POST'])]
    public function paymentIntent(Request $request, \Symfony\Contracts\HttpClient\HttpClientInterface $client): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('stripe_payment', $token)) {
            return $this->json(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $amount = (float) $request->request->get('amount', 0);
        if ($amount <= 0) {
            $amount = 10;
        }

        $secret = trim((string) $this->getParameter('stripe_secret_key'));

        if ($secret === '') {
            return $this->json([
                'error' => 'Configuration Stripe manquante (STRIPE_SECRET_KEY).',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $response = $client->request('POST', 'https://api.stripe.com/v1/payment_intents', [
                'auth_basic' => [$secret, ''],
                'body' => [
                    'amount' => (int) ($amount * 100),
                    'currency' => 'usd',
                ],
            ]);

            $data = $response->toArray(false);

            if (($response->getStatusCode() >= 400) || !isset($data['client_secret'])) {
                $stripeError = (string) ($data['error']['message'] ?? 'Echec de creation du paiement Stripe.');

                return $this->json([
                    'error' => $stripeError,
                ], Response::HTTP_BAD_GATEWAY);
            }

            return $this->json(['clientSecret' => (string) $data['client_secret']]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Impossible de contacter Stripe. Verifiez votre connexion et vos cles API.',
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/{id}/map-locations', name: 'api_vente_map_locations', methods: ['GET'])]
    public function mapLocations(Vente $vente): JsonResponse
    {
        return $this->json([
            'seller' => [
                'name' => 'Fermier (Point de collecte)',
                'address' => 'Gouvernorat, Tunisie',
                'coordinates' => ['lat' => 36.8065, 'lng' => 10.1815] // Tunis mock
            ],
            'delivery' => [
                'address' => 'Adresse de livraison demandée',
                'coordinates' => ['lat' => 35.8256, 'lng' => 10.63699] // Sousse mock
            ],
            'sale' => [
                'id' => $vente->getId(),
                'buyer' => $vente->getBuyerName() ?: 'Acheteur Anonyme',
                'price' => $vente->getPrice() ?: 0,
                'date' => ($vente->getSaleDate() ?: new \DateTime())->format('Y-m-d')
            ]
        ]);
    }

    #[Route('/{id}/rate', name: 'app_vente_rate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rate(Request $request, Vente $vente): JsonResponse
    {
        $rating = (int) $request->request->get('rating', 0);
        if ($rating < 1 || $rating > 5) {
            return $this->json(['error' => 'Invalid rating'], 400);
        }
        // Rating is a virtual property – store it in session for the demo
        $request->getSession()->set('vente_rating_' . $vente->getId(), $rating);
        return $this->json(['success' => true, 'rating' => $rating]);
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

    private function getFirebaseVapidKey(): string
    {
        return trim((string) ($_ENV['FIREBASE_VAPID_KEY'] ?? $_SERVER['FIREBASE_VAPID_KEY'] ?? getenv('FIREBASE_VAPID_KEY') ?: ''));
    }
}
