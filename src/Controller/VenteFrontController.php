<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Vente;
use App\Entity\User;
use App\Form\VenteType;
use App\Repository\MarketplaceOrderRepository;
use App\Repository\VenteRepository;
use App\Repository\RecolteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function list(
        Request $request,
        VenteRepository $venteRepository,
        MarketplaceOrderRepository $marketplaceOrderRepository
    ): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'saleDate');
        $direction = (string) $request->query->get('direction', 'DESC');

        $currentUser = $this->getCurrentUserEntity();

        // Get ventes linked to user's recoltes
        $ventes = $venteRepository->findForUser($currentUser->getIdUser());
        $purchaseOrders = $marketplaceOrderRepository->findPurchasesForBuyer((int) $currentUser->getIdUser());

        // Filter by search if provided
        if ($search) {
            $ventes = array_filter($ventes, function (Vente $vente) use ($search) {
                return stripos($vente->getDescription(), $search) !== false
                    || stripos((string) $vente->getBuyerName(), $search) !== false;
            });

            $purchaseOrders = array_filter($purchaseOrders, static function ($order) use ($search) {
                $vente = $order->getVente();
                $recolteName = $vente?->getRecolte()?->getName() ?? '';
                $description = $vente?->getDescription() ?? '';
                $status = $order->getStatus() ?? '';
                $deliveryAddress = $order->getDeliveryAddress() ?? '';

                return stripos($recolteName, $search) !== false
                    || stripos($description, $search) !== false
                    || stripos($status, $search) !== false
                    || stripos($deliveryAddress, $search) !== false;
            });
        }

        $chatbotCatalog = array_map(fn (Vente $vente) => $this->buildChatbotCatalogItem($vente), $ventes);

        return $this->render('front/vente/list.html.twig', [
            'ventes' => $ventes,
            'purchase_orders' => $purchaseOrders,
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'chatbot_catalog' => array_values($chatbotCatalog),
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
            $paymentEnabled = $request->request->getBoolean('payment_enabled');
            $paymentStatus = (string) $request->request->get('payment_status', '');

            if ($vente->getAvailableQuantity() === null && $vente->getRecolte()?->getQuantity() !== null) {
                $vente->setAvailableQuantity($vente->getRecolte()?->getQuantity());
            }

            if ($paymentEnabled && $paymentStatus !== 'succeeded') {
                // Do not block the sale creation when optional payment is not completed.
                $this->addFlash('warning', 'Le paiement Stripe n\'a pas ete confirme. La vente a ete enregistree sans paiement en ligne.');
            }

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

    #[Route('/assistant', name: 'app_vente_assistant', methods: ['POST'])]
    public function assistant(Request $request, VenteRepository $venteRepository, HttpClientInterface $httpClient): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Corps de requete invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message == '') {
            return $this->json(['error' => 'Message vide.'], Response::HTTP_BAD_REQUEST);
        }

        $historyPayload = $payload['history'] ?? [];
        $history = [];
        if (is_array($historyPayload)) {
            foreach (array_slice($historyPayload, -16) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $role = (string) ($entry['role'] ?? 'user');
                $content = trim((string) ($entry['content'] ?? ''));
                if ($content === '' || !in_array($role, ['user', 'assistant'], true)) {
                    continue;
                }

                $history[] = [
                    'role' => $role,
                    'content' => $content,
                ];
            }
        }

        $currentUser = $this->getCurrentUserEntity();
        $ventes = $venteRepository->findForUser($currentUser->getIdUser());
        $catalog = array_values(array_map(fn (Vente $vente) => $this->buildChatbotCatalogItem($vente), $ventes));

        $reply = $this->generateAssistantReply($message, $history, $catalog, $httpClient);

        return $this->json([
            'reply' => $reply,
            'provider' => $this->resolveAiProvider(),
        ]);
    }

    #[Route('/payment-intent', name: 'app_vente_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        if (!$this->isCsrfTokenValid('stripe_payment', (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $amountValue = (float) $request->request->get('amount', 0);
        if ($amountValue <= 0) {
            return $this->json(['error' => 'Montant invalide pour le paiement.'], Response::HTTP_BAD_REQUEST);
        }

        $secretKey = trim((string) $this->getParameter('stripe_secret_key'));
        if ($secretKey === '') {
            return $this->json(['error' => 'Clé Stripe secrète manquante. Configurez STRIPE_SECRET_KEY.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $amount = (int) round($amountValue * 100);

        try {
            $stripeResponse = $httpClient->request('POST', 'https://api.stripe.com/v1/payment_intents', [
                'auth_bearer' => $secretKey,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'amount' => $amount,
                    'currency' => 'usd',
                    'payment_method_types[]' => 'card',
                    'description' => (string) $request->request->get('description', 'Paiement vente AgriGo'),
                ],
            ]);

            $statusCode = $stripeResponse->getStatusCode();
            $payload = $stripeResponse->toArray(false);

            if ($statusCode >= 400 || !isset($payload['client_secret'])) {
                return $this->json([
                    'error' => $payload['error']['message'] ?? 'Impossible de créer le PaymentIntent Stripe.',
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'clientSecret' => $payload['client_secret'],
            ]);
        } catch (\Throwable $exception) {
            return $this->json([
                'error' => 'Erreur lors de la connexion à Stripe.',
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/{id}', name: 'app_vente_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Vente $vente, MarketplaceOrderRepository $marketplaceOrderRepository): Response
    {
        $this->denyVenteAccessIfNeeded($vente, true, $marketplaceOrderRepository);

        $currentUser = $this->getCurrentUserEntity();
        $isOwner = $vente->getRecolte()?->getUserId() === $currentUser->getIdUser();
        $viewerOrder = $marketplaceOrderRepository->findOneBy([
            'vente' => $vente,
            'buyer' => $currentUser,
        ], ['orderedAt' => 'DESC']);

        $pickupLocationLabel = $this->resolvePickupLocationLabel($vente);
        $deliveryLocationLabel = $viewerOrder?->getDeliveryAddress() ?: $vente->getDeliveryLocation();

        $mapFallbackCoordinates = $this->resolveMapFallbackCoordinates($vente);
        $mapFallbackEmbedUrl = null;
        $mapFallbackViewUrl = null;

        if ($mapFallbackCoordinates !== null) {
            $lat = $mapFallbackCoordinates['lat'];
            $lon = $mapFallbackCoordinates['lng'];
            $delta = 0.02;

            $left = $lon - $delta;
            $right = $lon + $delta;
            $top = $lat + $delta;
            $bottom = $lat - $delta;

            $mapFallbackEmbedUrl = sprintf(
                'https://www.openstreetmap.org/export/embed.html?bbox=%s,%s,%s,%s&layer=mapnik&marker=%s,%s',
                $left,
                $bottom,
                $right,
                $top,
                $lat,
                $lon
            );

            $mapFallbackViewUrl = sprintf('https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=13/%s/%s', $lat, $lon, $lat, $lon);
        }

        return $this->render('front/vente/show.html.twig', [
            'vente' => $vente,
            'is_owner' => $isOwner,
            'pickup_location_label' => $pickupLocationLabel,
            'delivery_location_label' => $deliveryLocationLabel,
            'map_fallback_embed_url' => $mapFallbackEmbedUrl,
            'map_fallback_view_url' => $mapFallbackViewUrl,
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
            if ($vente->getAvailableQuantity() === null && $vente->getRecolte()?->getQuantity() !== null) {
                $vente->setAvailableQuantity($vente->getRecolte()?->getQuantity());
            }

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

    private function denyVenteAccessIfNeeded(
        Vente $vente,
        bool $allowBuyerWithOrder = false,
        ?MarketplaceOrderRepository $marketplaceOrderRepository = null
    ): void
    {
        $currentUser = $this->getCurrentUserEntity();
        $recolte = $vente->getRecolte();

        if ($recolte && $recolte->getUserId() === $currentUser->getIdUser()) {
            return;
        }

        if ($allowBuyerWithOrder && $marketplaceOrderRepository && $vente->getId() !== null) {
            $orderCount = $marketplaceOrderRepository->count([
                'vente' => $vente,
                'buyer' => $currentUser,
            ]);

            if ($orderCount > 0) {
                return;
            }
        }

        throw $this->createAccessDeniedException('Vous ne pouvez accéder qu\'à vos ventes ou commandes marketplace.');
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        return $user;
    }

    private function resolveMapFallbackCoordinates(Vente $vente): ?array
    {
        $parcelleGps = $vente->getRecolte()?->getParcelle()?->getCoordonneesGps();
        if (is_string($parcelleGps)) {
            $parsed = $this->extractCoordinatesFromGps($parcelleGps);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if ($vente->getDeliveryLatitude() !== null && $vente->getDeliveryLongitude() !== null) {
            return [
                'lat' => (float) $vente->getDeliveryLatitude(),
                'lng' => (float) $vente->getDeliveryLongitude(),
            ];
        }

        return null;
    }

    private function resolvePickupLocationLabel(Vente $vente): string
    {
        $parcelle = $vente->getRecolte()?->getParcelle();
        if ($parcelle !== null) {
            $label = (string) ($parcelle->getNomParcelle() ?: 'Parcelle');
            if ($parcelle->getCoordonneesGps()) {
                $label .= ' (GPS: ' . $parcelle->getCoordonneesGps() . ')';
            }

            return $label;
        }

        $adresse = $vente->getRecolte()?->getAdresse();

        return $adresse && trim($adresse) !== '' ? $adresse : 'Lieu de collecte non renseigne';
    }

    private function extractCoordinatesFromGps(string $value): ?array
    {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $value, $matches)) {
            return null;
        }

        return [
            'lat' => (float) $matches[1],
            'lng' => (float) $matches[2],
        ];
    }

    private function buildChatbotCatalogItem(Vente $vente): array
    {
        $status = mb_strtolower(trim((string) $vente->getStatus()));
        $availableQuantity = $vente->getAvailableQuantity();

        if ($availableQuantity === null) {
            $availableQuantity = (float) ($vente->getRecolte()?->getQuantity() ?? 0.0);
        }

        $soldOutStatuses = ['completed', 'terminee', 'terminee', 'terminée', 'sold', 'sold out', 'epuise', 'epuisee', 'epuisee', 'épuisé', 'épuisée', 'indisponible'];
        $isAvailable = $availableQuantity > 0 && !in_array($status, $soldOutStatuses, true);

        return [
            'id' => $vente->getId(),
            'name' => (string) ($vente->getRecolte()?->getName() ?? $vente->getDescription() ?? ('Vente #' . (string) $vente->getId())),
            'description' => (string) ($vente->getDescription() ?? ''),
            'status' => (string) ($vente->getStatus() ?? 'unknown'),
            'available_qty' => round((float) $availableQuantity, 2),
            'is_available' => $isAvailable,
            'price' => round((float) ($vente->getPrice() ?? 0), 2),
            'rating' => (int) ($vente->getRating() ?? 0),
        ];
    }

    private function resolveAiProvider(): string
    {
        $openAiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? ''));

        return $openAiKey !== '' ? 'openai' : 'fallback';
    }

    private function generateAssistantReply(string $message, array $history, array $catalog, HttpClientInterface $httpClient): string
    {
        $openAiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? ''));

        if ($openAiKey !== '') {
            $llmReply = $this->queryOpenAiAssistant($message, $history, $catalog, $openAiKey, $httpClient);
            if ($llmReply !== null) {
                return $llmReply;
            }
        }

        return $this->buildFallbackSupportReply($message, $history, $catalog);
    }

    private function queryOpenAiAssistant(
        string $message,
        array $history,
        array $catalog,
        string $openAiKey,
        HttpClientInterface $httpClient
    ): ?string {
        $systemPrompt = implode("\n", [
            'You are a human-like support assistant for a ventes page in an agriculture marketplace.',
            'Rules:',
            '- Be warm, concise, and helpful like real human support.',
            '- Always ground product facts (price, stock, availability, quality) in the provided catalog only.',
            '- If a product is not in catalog, say it is not found and suggest close options from catalog.',
            '- Do not invent products, prices, stock, or statuses.',
            '- You can answer in mixed casual French/English style matching the user language.',
            '- Keep answer between 1 and 6 short lines.',
        ]);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role' => 'system',
                'content' => 'Catalog JSON: ' . json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        ];

        foreach (array_slice($history, -10) as $entry) {
            $messages[] = [
                'role' => $entry['role'],
                'content' => $entry['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        try {
            $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openAiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.5,
                    'messages' => $messages,
                ],
                'timeout' => 15,
            ]);

            $payload = $response->toArray(false);
            $content = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));

            return $content !== '' ? $content : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildFallbackSupportReply(string $message, array $history, array $catalog): string
    {
        $msg = $this->normalizeAssistantText($message);
        if ($msg === '') {
            return 'Ecris-moi ta question et je te reponds tout de suite sur les produits de cette page.';
        }

        $isGreeting = $this->containsOneOf($msg, ['salut', 'hello', 'bonjour', 'bonsoir', 'salam', 'cc']);
        if ($isGreeting) {
            return 'Salut, je suis la pour t aider comme un support humain. Tu peux me demander prix, qualite, dispo, comparaison, ou meilleur deal.';
        }

        $matches = $this->findMatchingCatalogItems($msg, $catalog);
        $topMatch = $matches[0] ?? null;

        if ($this->containsOneOf($msg, ['best deal', 'bon plan', 'moins cher', 'cheap', 'pas cher'])) {
            $available = array_values(array_filter($catalog, static fn (array $item) => (bool) ($item['is_available'] ?? false)));
            if ($available === []) {
                return 'Pour le moment je ne vois aucun produit disponible.';
            }

            usort($available, static fn (array $a, array $b) => (float) $a['price'] <=> (float) $b['price']);
            $bestDeal = $available[0];

            return sprintf(
                'Best deal actuel: %s a %.2f TND (stock %.2f). Si tu veux, je te donne aussi l option meilleure qualite.',
                (string) $bestDeal['name'],
                (float) $bestDeal['price'],
                (float) $bestDeal['available_qty']
            );
        }

        if ($this->containsOneOf($msg, ['meilleur', 'best', 'recommande', 'acheter', 'recommend'])) {
            $available = array_values(array_filter($catalog, static fn (array $item) => (bool) ($item['is_available'] ?? false)));
            if ($available === []) {
                return 'Je ne vois aucun produit disponible maintenant.';
            }

            usort($available, static function (array $a, array $b): int {
                $scoreA = ((int) ($a['rating'] ?? 0) * 10) - (float) ($a['price'] ?? 0);
                $scoreB = ((int) ($b['rating'] ?? 0) * 10) - (float) ($b['price'] ?? 0);

                return $scoreB <=> $scoreA;
            });

            $best = $available[0];

            return sprintf(
                'Je te recommande %s: %.2f TND, stock %.2f, note %d/5. Bon equilibre prix/qualite.',
                (string) $best['name'],
                (float) $best['price'],
                (float) $best['available_qty'],
                (int) $best['rating']
            );
        }

        if ($this->containsOneOf($msg, ['qualite', 'good', 'bonne', 'est bonne', 'quality'])) {
            if ($topMatch !== null) {
                $rating = (int) ($topMatch['rating'] ?? 0);
                $quality = $rating >= 4 ? 'tres bonne' : ($rating >= 3 ? 'correcte' : ($rating > 0 ? 'moyenne' : 'pas evaluee'));

                return sprintf(
                    '%s est %s. Note: %d/5, prix %.2f TND, stock %.2f, statut %s.',
                    (string) $topMatch['name'],
                    $quality,
                    $rating,
                    (float) $topMatch['price'],
                    (float) $topMatch['available_qty'],
                    (bool) ($topMatch['is_available'] ?? false) ? 'disponible' : 'indisponible'
                );
            }

            return 'Oui, je peux te dire si un produit est bon. Envoie juste son nom exact ou approximatif.';
        }

        if ($this->containsOneOf($msg, ['prix', 'price', 'combien', 'tarif'])) {
            if ($topMatch !== null) {
                return sprintf(
                    '%s coute %.2f TND. Stock %.2f, statut %s.',
                    (string) $topMatch['name'],
                    (float) $topMatch['price'],
                    (float) $topMatch['available_qty'],
                    (bool) ($topMatch['is_available'] ?? false) ? 'disponible' : 'indisponible'
                );
            }

            return 'Donne-moi le nom du produit et je te donne le prix direct.';
        }

        if ($this->containsOneOf($msg, ['disponible', 'stock', 'available'])) {
            $available = array_values(array_filter($catalog, static fn (array $item) => (bool) ($item['is_available'] ?? false)));
            if ($available === []) {
                return 'Aucun produit disponible actuellement.';
            }

            $names = array_map(static fn (array $item) => (string) $item['name'], array_slice($available, 0, 5));

            return 'Disponibles maintenant: ' . implode(', ', $names) . '.';
        }

        if ($topMatch !== null) {
            return sprintf(
                '%s: %.2f TND, stock %.2f, note %d/5, statut %s. Si tu veux je compare avec un autre produit.',
                (string) $topMatch['name'],
                (float) $topMatch['price'],
                (float) $topMatch['available_qty'],
                (int) $topMatch['rating'],
                (bool) ($topMatch['is_available'] ?? false) ? 'disponible' : 'indisponible'
            );
        }

        $suggestions = array_map(static fn (array $item) => (string) $item['name'], array_slice($catalog, 0, 4));
        $historyHint = $history !== [] ? ' On continue la discussion quand tu veux.' : '';

        return 'Je suis la pour aider comme support humain. Demande prix, qualite, dispo, meilleur deal, ou comparaison. Exemples: ' . implode(', ', $suggestions) . '.' . $historyHint;
    }

    private function findMatchingCatalogItems(string $normalizedMessage, array $catalog): array
    {
        $messageTokens = $this->tokenizeAssistantText($normalizedMessage);
        $results = [];

        foreach ($catalog as $item) {
            $name = $this->normalizeAssistantText((string) ($item['name'] ?? ''));
            $description = $this->normalizeAssistantText((string) ($item['description'] ?? ''));
            $nameTokens = $this->tokenizeAssistantText($name);
            $descTokens = $this->tokenizeAssistantText($description);
            $itemTokens = array_unique(array_merge($nameTokens, $descTokens));

            $score = 0;

            if ($name !== '' && str_contains($normalizedMessage, $name)) {
                $score += 100;
            }

            foreach ($messageTokens as $token) {
                if (strlen($token) < 3) {
                    continue;
                }

                if (in_array($token, $itemTokens, true)) {
                    $score += 20;
                    continue;
                }

                foreach ($itemTokens as $itemToken) {
                    if (levenshtein($token, $itemToken) <= 2) {
                        $score += 8;
                        break;
                    }
                }
            }

            if ($name !== '') {
                $distance = levenshtein($normalizedMessage, $name);
                if ($distance <= 2) {
                    $score += 30;
                } elseif ($distance <= 4) {
                    $score += 12;
                }
            }

            if ($score >= 12) {
                $results[] = [
                    'score' => $score,
                    'item' => $item,
                ];
            }
        }

        usort($results, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_map(static fn (array $entry) => $entry['item'], $results);
    }

    private function tokenizeAssistantText(string $value): array
    {
        $tokens = preg_split('/\s+/', $value) ?: [];

        return array_values(array_filter(array_map(static function (string $token): string {
            $token = trim($token);
            if (strlen($token) > 4 && str_ends_with($token, 'es')) {
                return substr($token, 0, -2);
            }

            if (strlen($token) > 3 && str_ends_with($token, 's')) {
                return substr($token, 0, -1);
            }

            return $token;
        }, $tokens), static fn (string $token) => $token !== ''));
    }

    private function normalizeAssistantText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function containsOneOf(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
