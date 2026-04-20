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
        // Important: éviter findAll() sur mouvement_stock (peut être énorme => MySQL "server has gone away").
        $monthlyOutbound = $this->mouvementStockRepository->monthlyOutboundByProduct(12);
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
     * Détection "gaspillage invisible" selon la règle demandée :
     * - consommation mensuelle (sorties)
     * - moyenne (µ)
     * - écart-type (σ)
     * - seuil dynamique = µ + 2σ
     * - anomalie si dernier mois > seuil
     *
     * @return array{windowMonths:int, anomaliesCount:int, analyses:array<int,array<string,mixed>>}
     */
    public function analyzeWasteDetectionSimple(int $windowMonths = 12): array
    {
        $produits = $this->produitRepository->findAll();
        $monthlyOutbound = $this->mouvementStockRepository->monthlyOutboundByProduct($windowMonths);

        $analyses = [];
        $anomaliesCount = 0;

        foreach ($produits as $produit) {
            $produitId = $produit->getIdProduit();
            if ($produitId === null) {
                continue;
            }

            $series = $monthlyOutbound[$produitId] ?? [];
            ksort($series);

            if ($windowMonths > 0 && count($series) > $windowMonths) {
                $series = array_slice($series, -$windowMonths, null, true);
            }

            $values = array_values($series);
            $count = count($values);
            $lastMonth = $count > 0 ? array_key_last($series) : null;
            $lastValue = $count > 0 ? (float) $values[$count - 1] : 0.0;

            $historyWithoutLast = $count > 1 ? array_slice($values, 0, -1) : [];
            $mean = $this->mean($historyWithoutLast);
            $stdDev = $this->stdDev($historyWithoutLast);
            $threshold = $mean + (2.0 * $stdDev);
            $robustZ = $this->robustZScore($lastValue, $historyWithoutLast);

            // IA (détection d’anomalies) : décision + score
            $anomaly = $count >= 4 && $lastValue > $threshold && $robustZ >= 2.5;
            if ($anomaly) {
                $anomaliesCount++;
            }

            $confidence = $count >= 12 ? 'Élevée' : ($count >= 6 ? 'Moyenne' : 'Faible');
            $score = $this->anomalyScore($lastValue, $threshold, $robustZ, $count);

            $analyses[] = [
                'produit' => [
                    'id' => $produitId,
                    'nom' => $produit->getNomProduit(),
                    'categorie' => $produit->getCategorie(),
                    'unite' => $produit->getUnite(),
                ],
                'moisDernier' => $lastMonth,
                'consommationDernierMois' => round($lastValue, 2),
                'moyenne' => round($mean, 2),
                'ecartType' => round($stdDev, 2),
                'seuil' => round($threshold, 2),
                'anomalie' => $anomaly,
                'anomalyScore' => $score,
                'confiance' => $confidence,
                'robustZ' => round($robustZ, 2),
                'historiqueMois' => $count,
                'explication' => $anomaly
                    ? 'Consommation > µ + 2σ et z-score robuste élevé (MAD) → anomalie probable.'
                    : 'Consommation compatible avec la distribution historique.',
            ];
        }

        usort(
            $analyses,
            static fn (array $a, array $b): int => (int) $b['consommationDernierMois'] <=> (int) $a['consommationDernierMois']
        );

        return [
            'windowMonths' => $windowMonths,
            'anomaliesCount' => $anomaliesCount,
            'analyses' => $analyses,
        ];
    }

    /**
     * Version API JSON-safe (pas d'entités Doctrine dans la réponse).
     */
    public function recommendOptimalStockApi(int $delaiLivraison, int $margeSecuriteJours): array
    {
        $rows = $this->recommendOptimalStock($delaiLivraison, $margeSecuriteJours);

        $out = [];
        foreach ($rows as $row) {
            /** @var Produit $produit */
            $produit = $row['produit'];
            $out[] = [
                'produit' => [
                    'id' => $produit->getIdProduit(),
                    'nom' => $produit->getNomProduit(),
                    'categorie' => $produit->getCategorie(),
                    'unite' => $produit->getUnite(),
                ],
                'consommationJour' => $row['consommationJour'],
                'delaiLivraison' => $row['delaiLivraison'],
                'margeSecurite' => $row['margeSecurite'],
                'stockOptimal' => $row['stockOptimal'],
                'stockActuel' => $row['stockActuel'],
                'risqueRupture' => $row['risqueRupture'],
                'tendanceMensuelle' => $row['tendanceMensuelle'],
                'historiqueMois' => $row['historiqueMois'],
                'ecart' => $row['ecart'],
            ];
        }

        return $out;
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
        // Agrégation DB pour éviter de charger tous les mouvements
        $monthlyOutbound = $this->mouvementStockRepository->monthlyOutboundByProduct(24);

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

            // IA (prévision) : lissage exponentiel de Holt (niveau + tendance)
            $forecast = $this->holtLinearForecast($values, 0.45, 0.20);
            $adjustedMonthlyForecast = max(0.0, $forecast['forecastNext'] ?? 0.0);
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
                'tendanceMensuelle' => round((float) ($forecast['trend'] ?? 0.0), 2),
                'historiqueMois' => $count,
                'ecart' => $gap,
                'ia' => [
                    'method' => 'HoltLinear',
                    'forecastMonthlyNext' => round($adjustedMonthlyForecast, 2),
                    'level' => round((float) ($forecast['level'] ?? 0.0), 2),
                ],
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

    private function mean(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }

        return array_sum($values) / $n;
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

    /**
     * Score d'anomalie (0..100) basé sur dépassement de seuil + z-score robuste + taille d'historique.
     */
    private function anomalyScore(float $value, float $threshold, float $robustZ, int $historyCount): int
    {
        if ($historyCount <= 1) {
            return 0;
        }

        $excess = $threshold > 0.0 ? max(0.0, ($value - $threshold) / $threshold) : max(0.0, $value - $threshold);
        $zComponent = max(0.0, ($robustZ - 2.0) / 3.0); // ~0 à partir de z≈2
        $historyFactor = min(1.0, $historyCount / 12.0);

        $raw = (0.65 * $excess) + (0.35 * $zComponent);
        $scaled = 100.0 * (1.0 - exp(-3.0 * $raw)) * $historyFactor;

        return (int) max(0, min(100, round($scaled)));
    }

    /**
     * Lissage exponentiel double (Holt) pour séries mensuelles non saisonnières.
     *
     * @return array{forecastNext:float, level:float, trend:float}
     */
    private function holtLinearForecast(array $values, float $alpha, float $beta): array
    {
        $n = count($values);
        if ($n === 0) {
            return ['forecastNext' => 0.0, 'level' => 0.0, 'trend' => 0.0];
        }
        if ($n === 1) {
            $v = (float) $values[0];
            return ['forecastNext' => $v, 'level' => $v, 'trend' => 0.0];
        }

        $alpha = max(0.01, min(0.99, $alpha));
        $beta = max(0.01, min(0.99, $beta));

        $level = (float) $values[0];
        $trend = (float) $values[1] - (float) $values[0];

        for ($i = 1; $i < $n; $i++) {
            $value = (float) $values[$i];
            $prevLevel = $level;
            $level = $alpha * $value + (1.0 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $prevLevel) + (1.0 - $beta) * $trend;
        }

        return [
            'forecastNext' => max(0.0, $level + $trend),
            'level' => $level,
            'trend' => $trend,
        ];
    }
}

