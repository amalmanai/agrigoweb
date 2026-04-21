<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Vente;
use App\Repository\MarketplaceOrderRepository;
use App\Repository\VenteRepository;

class MarketplaceRecommendationService
{
    public function __construct(
        private readonly VenteRepository $venteRepository,
        private readonly MarketplaceOrderRepository $orderRepository,
    ) {
    }

    /**
     * @return Vente[]
     */
    public function recommendForBuyer(User $buyer, int $limit = 6): array
    {
        $buyerId = (int) $buyer->getIdUser();
        if ($buyerId <= 0) {
            return [];
        }

        $orderedVenteIds = $this->orderRepository->findOrderedVenteIdsForBuyer($buyerId);
        $purchasedNames = $this->orderRepository->findPurchasedProductNames($buyerId);
        $keywords = $this->extractKeywords($purchasedNames);

        $recommended = $this->venteRepository->findRecommendedForBuyer($buyerId, $keywords, $orderedVenteIds, $limit);
        if (count($recommended) >= $limit) {
            return $recommended;
        }

        $fallback = $this->venteRepository->findMarketplaceListingsForBuyer($buyerId, null, $limit * 2);
        $byId = [];
        foreach (array_merge($recommended, $fallback) as $vente) {
            if ($vente->getId() === null) {
                continue;
            }

            if (in_array($vente->getId(), $orderedVenteIds, true)) {
                continue;
            }

            $byId[$vente->getId()] = $vente;
            if (count($byId) >= $limit) {
                break;
            }
        }

        return array_values($byId);
    }

    /**
     * @param string[] $names
     *
     * @return string[]
     */
    private function extractKeywords(array $names): array
    {
        $stopWords = [
            'de', 'du', 'des', 'le', 'la', 'les', 'et', 'avec', 'sans', 'pour', 'par', 'sur', 'dans',
        ];

        $keywords = [];
        foreach ($names as $name) {
            $parts = preg_split('/[^a-zA-Z0-9]+/', mb_strtolower($name));
            if (!is_array($parts)) {
                continue;
            }

            foreach ($parts as $part) {
                if (mb_strlen($part) < 3 || in_array($part, $stopWords, true)) {
                    continue;
                }

                $keywords[$part] = true;
            }
        }

        return array_keys($keywords);
    }
}
