<?php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\MouvementStockRepository;
use App\Repository\ProduitRepository;

class InventoryAiService
{
    public function __construct(
        private readonly ProduitRepository $produitRepository,
        private readonly MouvementStockRepository $mouvementStockRepository,
    ) {
    }

    /**
     * Détection de gaspillage avec:
     * - baseline pondérée sur les 6 derniers mois
     * - score z robuste via MAD
     * - score de confiance selon la profondeur d'historique
     */
    public function analyzeWasteDetection(): array
    {
        $produits = $this->produitRepository->findAll();
        $mouvements = $this->mouvementStockRepository->findAll();

        $monthlyOutbound = $this->buildMonthlyOutboundByProduct($mouvements);
        $analyses = [];
        $anomaliesCount = 0;

        foreach ($produits as $produit) {
            $produitId = $produit->getIdProduit();
            if ($produitId === null) {
                continue;
            }

            $series = $monthlyOutbound[$produitId] ?? [];
            ksort($series);
            $values = array_values($series);
            $count = count($values);

            if ($count === 0) {
                $analyses[] = [
                    'produit' => $produit,
                    'moyenne' => 0.0,
                    'ecartType' => 0.0,
                    'seuil' => 0.0,
                    'consommationDernierMois' => 0.0,
                    'anomalie' => false,
                    'confiance' => 'Faible',
                    'zScore' => 0.0,
                    'explication' => 'Historique insuffisant.',
                ];
                continue;
            }

            $lastValue = (float) $values[$count - 1];
            $historyWithoutLast = $count > 1 ? array_slice($values, 0, -1) : $values;
            $weightedBaseline = $this->weightedMean($historyWithoutLast);
            $stdDev = $this->stdDev($historyWithoutLast);
            $dynamicThreshold = $weightedBaseline + (1.8 * $stdDev);
            $zScore = $this->robustZScore($lastValue, $historyWithoutLast);

            $anomaly = $count >= 4
                && $lastValue > $dynamicThreshold
                && $zScore >= 2.5;

            if ($anomaly) {
                $anomaliesCount++;
            }

            $confidence = $count >= 12 ? 'Élevée' : ($count >= 6 ? 'Moyenne' : 'Faible');
            $analyses[] = [
                'produit' => $produit,
                'moyenne' => round($weightedBaseline, 2),
                'ecartType' => round($stdDev, 2),
                'seuil' => round($dynamicThreshold, 2),
                'consommationDernierMois' => round($lastValue, 2),
                'anomalie' => $anomaly,
                'confiance' => $confidence,
                'zScore' => round($zScore, 2),
                'explication' => $anomaly
                    ? 'Pic de consommation anormal vs tendance récente.'
                    : 'Consommation cohérente avec la tendance.',
            ];
        }

        usort(
            $analyses,
            static fn (array $a, array $b): int => (int) $b['consommationDernierMois'] <=> (int) $a['consommationDernierMois']
        );

        return [
            'analyses' => $analyses,
            'anomaliesCount' => $anomaliesCount,
        ];
    }

    /**
     * Stock optimal via prévision simple:
     * - moyenne mobile pondérée des sorties récentes
     * - tendance (slope) par mois
     * - stock sécurité basé sur variabilité et niveau de service
     */
    public function recommendOptimalStock(int $delaiLivraison, int $margeSecuriteJours): array
    {
        $produits = $this->produitRepository->findAll();
        $mouvements = $this->mouvementStockRepository->findAll();
        $monthlyOutbound = $this->buildMonthlyOutboundByProduct($mouvements);

        $recommandations = [];
        foreach ($produits as $produit) {
            $produitId = $produit->getIdProduit();
            if ($produitId === null) {
                continue;
            }

            $series = $monthlyOutbound[$produitId] ?? [];
            ksort($series);
            $values = array_values($series);
            $count = count($values);

            $monthlyForecast = $count > 0 ? $this->weightedMean($values) : 0.0;
            $trend = $this->trendPerMonth($values);
            $adjustedMonthlyForecast = max(0.0, $monthlyForecast + $trend);
            $dailyForecast = $adjustedMonthlyForecast / 30.0;
            $variability = $this->stdDev($values);

            // Niveau de service ~95% => z = 1.65
            $safetyStock = 1.65 * ($variability / 30.0) * sqrt(max(1, $delaiLivraison + $margeSecuriteJours));
            $cycleStock = $dailyForecast * $delaiLivraison;
            $stockOptimal = (int) ceil(max(0.0, $cycleStock + $safetyStock));
            $stockActuel = (int) ($produit->getQuantiteDisponible() ?? 0);
            $gap = $stockActuel - $stockOptimal;

            $recommandations[] = [
                'produit' => $produit,
                'consommationJour' => round($dailyForecast, 2),
                'delaiLivraison' => $delaiLivraison,
                'margeSecurite' => round($safetyStock, 2),
                'stockOptimal' => $stockOptimal,
                'stockActuel' => $stockActuel,
                'risqueRupture' => $stockActuel < $stockOptimal,
                'tendanceMensuelle' => round($trend, 2),
                'historiqueMois' => $count,
                'ecart' => $gap,
            ];
        }

        usort(
            $recommandations,
            static fn (array $a, array $b): int => ($a['ecart']) <=> ($b['ecart'])
        );

        return $recommandations;
    }

    private function buildMonthlyOutboundByProduct(array $mouvements): array
    {
        $data = [];
        foreach ($mouvements as $mouvement) {
            $produit = $mouvement->getProduit();
            if ($produit === null || !$this->isSortie($mouvement->getTypeMouvement())) {
                continue;
            }

            $produitId = $produit->getIdProduit();
            $date = $mouvement->getDateMouvement()?->format('Y-m-d') ?? '';
            if ($produitId === null || strlen($date) < 7) {
                continue;
            }

            $month = substr($date, 0, 7);
            $data[$produitId][$month] = ($data[$produitId][$month] ?? 0) + (int) $mouvement->getQuantite();
        }

        return $data;
    }

    private function isSortie(?string $type): bool
    {
        if ($type === null) {
            return false;
        }

        $normalized = mb_strtolower(trim($type));
        return in_array($normalized, ['sortie'], true);
    }

    private function weightedMean(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }

        $weightedSum = 0.0;
        $weights = 0.0;
        foreach ($values as $idx => $value) {
            $weight = $idx + 1; // plus récent => poids plus fort
            $weightedSum += $value * $weight;
            $weights += $weight;
        }

        return $weights > 0 ? $weightedSum / $weights : 0.0;
    }

    private function stdDev(array $values): float
    {
        $n = count($values);
        if ($n <= 1) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $variance = 0.0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }
        $variance /= $n;

        return sqrt($variance);
    }

    private function robustZScore(float $value, array $history): float
    {
        if ($history === []) {
            return 0.0;
        }

        $median = $this->median($history);
        $absoluteDeviations = array_map(
            static fn (float|int $item): float => abs((float) $item - $median),
            $history
        );
        $mad = $this->median($absoluteDeviations);
        if ($mad === 0.0) {
            return 0.0;
        }

        return abs(0.6745 * ($value - $median) / $mad);
    }

    private function trendPerMonth(array $values): float
    {
        $n = count($values);
        if ($n < 3) {
            return 0.0;
        }

        $xMean = ($n - 1) / 2;
        $yMean = array_sum($values) / $n;
        $num = 0.0;
        $den = 0.0;

        foreach ($values as $i => $value) {
            $x = (float) $i;
            $num += ($x - $xMean) * ($value - $yMean);
            $den += ($x - $xMean) ** 2;
        }

        if ($den === 0.0) {
            return 0.0;
        }

        return $num / $den;
    }

    private function median(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }

        sort($values);
        $middle = intdiv($n, 2);

        if ($n % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2.0;
    }
}

