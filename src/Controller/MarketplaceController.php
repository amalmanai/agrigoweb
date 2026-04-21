<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\MarketplaceOrder;
use App\Entity\User;
use App\Entity\Vente;
use App\Form\MarketplaceOrderType;
use App\Repository\MarketplaceOrderRepository;
use App\Repository\VenteRepository;
use App\Service\MarketplaceRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'app_marketplace_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository, MarketplaceRecommendationService $recommendationService, HttpClientInterface $httpClient): Response
    {
        $user = $this->getCurrentUser();
        $search = trim((string) $request->query->get('search', ''));

        $listings = $venteRepository->findMarketplaceListingsForBuyer((int) $user->getIdUser(), $search, 30);
        $recommended = $recommendationService->recommendForBuyer($user, 8);

        $dealQualities = $this->evaluateDealsQuality(array_merge($listings, $recommended), $httpClient);

        return $this->render('front/marketplace/index.html.twig', [
            'search' => $search,
            'listings' => $listings,
            'recommended' => $recommended,
            'deal_qualities' => $dealQualities,
        ]);
    }

    #[Route('/vente/{id}/order', name: 'app_marketplace_order_new', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function order(
        Request $request,
        Vente $vente,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getCurrentUser();
        $sellerId = $vente->getRecolte()?->getUserId();
        if ($sellerId === null || $sellerId === $user->getIdUser()) {
            throw $this->createAccessDeniedException('Cette annonce n\'est pas commandable.');
        }

        if (!$vente->isMarketplaceListing()) {
            $this->addFlash('danger', 'Cette vente n\'est pas publiee sur le marketplace.');

            return $this->redirectToRoute('app_marketplace_index');
        }

        $availableQuantity = $this->resolveAvailableQuantity($vente);
        if ($availableQuantity <= 0.0) {
            $this->addFlash('danger', 'Stock indisponible pour cette vente.');

            return $this->redirectToRoute('app_marketplace_index');
        }

        $order = new MarketplaceOrder();
        $form = $this->createForm(MarketplaceOrderType::class, $order, [
            'max_quantity' => $availableQuantity,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = (float) $order->getQuantity();
            if ($quantity <= 0 || $quantity > $availableQuantity) {
                $this->addFlash('danger', 'La quantite commandee depasse le stock disponible.');

                return $this->render('front/marketplace/order_new.html.twig', [
                    'vente' => $vente,
                    'available_quantity' => $availableQuantity,
                    'form' => $form->createView(),
                ]);
            }

            $unitPrice = (float) ($vente->getPrice() ?? 0);
            $totalPrice = $quantity * $unitPrice;

            $order
                ->setVente($vente)
                ->setBuyer($user)
                ->setSellerId($sellerId)
                ->setUnitPrice(number_format($unitPrice, 2, '.', ''))
                ->setTotalPrice(number_format($totalPrice, 2, '.', ''))
                ->setStatus('pending');

            if ($order->getDeliveryAddress() === null || trim((string) $order->getDeliveryAddress()) === '') {
                $order->setDeliveryAddress($user->getAdresseUser());
            }

            $vente->setAvailableQuantity(max(0.0, $availableQuantity - $quantity));
            if ((float) $vente->getAvailableQuantity() <= 0.0) {
                $vente->setIsMarketplaceListing(false);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Commande creee avec succes.');

            return $this->redirectToRoute('app_marketplace_orders_buying');
        }

        return $this->render('front/marketplace/order_new.html.twig', [
            'vente' => $vente,
            'available_quantity' => $availableQuantity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/orders/buying', name: 'app_marketplace_orders_buying', methods: ['GET'])]
    public function buyingOrders(MarketplaceOrderRepository $orderRepository): Response
    {
        $user = $this->getCurrentUser();

        return $this->render('front/marketplace/orders_buying.html.twig', [
            'orders' => $orderRepository->findPurchasesForBuyer((int) $user->getIdUser()),
        ]);
    }

    #[Route('/orders/selling', name: 'app_marketplace_orders_selling', methods: ['GET'])]
    public function sellingOrders(MarketplaceOrderRepository $orderRepository): Response
    {
        $user = $this->getCurrentUser();

        return $this->render('front/marketplace/orders_selling.html.twig', [
            'orders' => $orderRepository->findSalesForSeller((int) $user->getIdUser()),
        ]);
    }

    #[Route('/orders/{id}/status', name: 'app_marketplace_order_status', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function updateOrderStatus(
        Request $request,
        MarketplaceOrder $order,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getCurrentUser();
        if ($order->getSellerId() !== $user->getIdUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos commandes vendeurs.');
        }

        if (!$this->isCsrfTokenValid('marketplace_order_status_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_marketplace_orders_selling');
        }

        $status = mb_strtolower(trim((string) $request->request->get('status', '')));
        $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            $this->addFlash('danger', 'Statut invalide.');

            return $this->redirectToRoute('app_marketplace_orders_selling');
        }

        $order->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Statut de commande mis a jour.');

        return $this->redirectToRoute('app_marketplace_orders_selling');
    }

    private function getCurrentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        return $user;
    }

    private function resolveAvailableQuantity(Vente $vente): float
    {
        if ($vente->getAvailableQuantity() !== null) {
            return (float) $vente->getAvailableQuantity();
        }

        return (float) ($vente->getRecolte()?->getQuantity() ?? 0.0);
    }

    private function evaluateDealsQuality(array $ventes, HttpClientInterface $httpClient): array
    {
        $qualities = [];
        $openAiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? ''));

        foreach ($ventes as $vente) {
            $qualities[(int) $vente->getId()] = $this->assessDealWithOpenAI($vente, $openAiKey, $httpClient);
        }

        return $qualities;
    }

    private function assessDealWithOpenAI(Vente $vente, string $openAiKey, HttpClientInterface $httpClient): array
    {
        // If no API key, use fallback
        if ($openAiKey === '') {
            return $this->calculateDealQualityFallback($vente);
        }

        $rating = (int) ($vente->getRating() ?? 0);
        $availableQty = $this->resolveAvailableQuantity($vente);
        $price = (float) ($vente->getPrice() ?? 0);
        $productName = $vente->getRecolte()?->getName() ?? 'Product';

        $prompt = sprintf(
            'You are a marketplace deal evaluator. Rate this deal: Price: %.2f TND, Stock: %.0f units, Seller Rating: %d/5, Product: %s. Respond ONLY with JSON: {"quality":"good"|"bad"|"neutral","score":number} based on value for money and availability.',
            $price,
            $availableQty,
            $rating,
            $productName
        );

        try {
            $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openAiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 50,
                ],
                'timeout' => 3,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->calculateDealQualityFallback($vente);
            }

            $data = $response->toArray(false);
            $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

            // Clean up markdown code blocks
            $content = preg_replace('/^```json\s*/', '', $content) ?: $content;
            $content = preg_replace('/```\s*$/', '', $content) ?: $content;
            $content = trim($content);

            $decoded = json_decode($content, true);

            if (is_array($decoded) && isset($decoded['quality'])) {
                $quality = mb_strtolower($decoded['quality']);
                $score = (int) ($decoded['score'] ?? 50);

                return [
                    'quality' => in_array($quality, ['good', 'bad', 'neutral']) ? $quality : 'neutral',
                    'score' => min(100, max(0, $score)),
                ];
            }
        } catch (\Throwable $e) {
            // Log or silently fall through on timeout/error
        }

        return $this->calculateDealQualityFallback($vente);
    }

    private function calculateDealQualityFallback(Vente $vente): array
    {
        $rating = (int) ($vente->getRating() ?? 0);
        $availableQty = $this->resolveAvailableQuantity($vente);
        $price = (float) ($vente->getPrice() ?? 0);

        // Calculate quality score (0-100)
        $score = 50; // baseline

        // Rating boost (0-40 points)
        if ($rating >= 4) {
            $score += 30;
        } elseif ($rating >= 3) {
            $score += 15;
        } elseif ($rating <= 1) {
            $score -= 30;
        }

        // Stock boost (0-40 points)
        if ($availableQty > 50) {
            $score += 25;
        } elseif ($availableQty > 10) {
            $score += 15;
        } elseif ($availableQty <= 0) {
            $score -= 40;
        }

        // Clamp score between 0-100
        $score = min(100, max(0, $score));

        // Determine quality label
        if ($availableQty <= 0) {
            $quality = 'bad';
        } elseif ($rating >= 4 && $availableQty > 5 && $score >= 70) {
            $quality = 'good';
        } elseif ($rating <= 1 || $score <= 30) {
            $quality = 'bad';
        } else {
            $quality = 'neutral';
        }

        return [
            'quality' => $quality,
            'score' => $score,
        ];
    }
}
