<?php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\MouvementStockRepository;
use App\Repository\ProduitRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InventoryAiService
{
    public function __construct(
        private readonly ProduitRepository $produitRepository,
        private readonly MouvementStockRepository $mouvementStockRepository,
        private readonly HttpClientInterface $httpClient,
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
        $monthlyOutbound = $this->mouvementStockRepository->monthlyOutboundByProduct(12);

        $payload = [];
        foreach ($produits as $produit) {
            $produitId = $produit->getIdProduit();
            if ($produitId === null) {
                continue;
            }

            $series = $monthlyOutbound[$produitId] ?? [];
            ksort($series);

            $payload[] = [
                'id' => $produitId,
                'nom' => $produit->getNomProduit(),
                'categorie' => $produit->getCategorie(),
                'unite' => $produit->getUnite(),
                'stock_actuel' => (int) ($produit->getQuantiteDisponible() ?? 0),
                'historique_mensuel' => $series,
            ];
        }

        $apiResult = $this->callOpenAiWasteDetection($payload);
        if ($apiResult !== null) {
            return $apiResult;
        }

        return $this->analyzeWasteDetectionFallback($produits, $monthlyOutbound);
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

            $anomaly = $count >= 2 && $lastValue > $threshold;
            if ($anomaly) {
                $anomaliesCount++;
            }

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
                'historiqueMois' => $count,
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
        $mouvements = $this->mouvementStockRepository->findAll();
        $monthlyOutbound = $this->buildMonthlyOutboundByProduct($mouvements);

        $payload = [];
        foreach ($produits as $produit) {
            $produitId = $produit->getIdProduit();
            if ($produitId === null) {
                continue;
            }

            $series = $monthlyOutbound[$produitId] ?? [];
            ksort($series);

            $payload[] = [
                'id' => $produitId,
                'nom' => $produit->getNomProduit(),
                'categorie' => $produit->getCategorie(),
                'unite' => $produit->getUnite(),
                'stock_actuel' => (int) ($produit->getQuantiteDisponible() ?? 0),
                'historique_mensuel' => $series,
            ];
        }

        $apiResult = $this->callGroqStockRecommendation($payload, $delaiLivraison, $margeSecuriteJours);
        if ($apiResult !== null) {
            return $apiResult;
        }

        return $this->recommendOptimalStockFallback($produits, $monthlyOutbound, $delaiLivraison, $margeSecuriteJours);
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

    private function analyzeWasteDetectionFallback(array $produits, array $monthlyOutbound): array
    {
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

        usort($analyses, static fn (array $a, array $b): int => (int) $b['consommationDernierMois'] <=> (int) $a['consommationDernierMois']);

        return [
            'analyses' => $analyses,
            'anomaliesCount' => $anomaliesCount,
        ];
    }

    private function recommendOptimalStockFallback(array $produits, array $monthlyOutbound, int $delaiLivraison, int $margeSecuriteJours): array
    {
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

        usort($recommandations, static fn (array $a, array $b): int => ($a['ecart']) <=> ($b['ecart']));

        return $recommandations;
    }

    private function callOpenAiWasteDetection(array $productsPayload): ?array
    {
        $apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un analyste de stock. Réponds uniquement en JSON valide avec les clés: anomaliesCount et analyses. Chaque element analyses doit contenir produit_id, produit_nom, moyenne, ecartType, seuil, consommationDernierMois, anomalie, confiance, zScore, explication.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'task' => 'detect_waste',
                                'products' => $productsPayload,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $content = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded) || !isset($decoded['analyses']) || !is_array($decoded['analyses'])) {
                return null;
            }

            $produitById = [];
            foreach ($this->produitRepository->findAll() as $produit) {
                if ($produit->getIdProduit() !== null) {
                    $produitById[(string) $produit->getIdProduit()] = $produit;
                }
            }

            $analyses = [];
            foreach ($decoded['analyses'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $produitId = (string) ($row['produit_id'] ?? '');
                if ($produitId === '' || !isset($produitById[$produitId])) {
                    continue;
                }

                $analyses[] = [
                    'produit' => $produitById[$produitId],
                    'moyenne' => (float) ($row['moyenne'] ?? 0),
                    'ecartType' => (float) ($row['ecartType'] ?? 0),
                    'seuil' => (float) ($row['seuil'] ?? 0),
                    'consommationDernierMois' => (float) ($row['consommationDernierMois'] ?? 0),
                    'anomalie' => (bool) ($row['anomalie'] ?? false),
                    'confiance' => (string) ($row['confiance'] ?? 'Faible'),
                    'zScore' => (float) ($row['zScore'] ?? 0),
                    'explication' => (string) ($row['explication'] ?? ''),
                ];
            }

            usort($analyses, static fn (array $a, array $b): int => (int) $b['consommationDernierMois'] <=> (int) $a['consommationDernierMois']);

            return [
                'analyses' => $analyses,
                'anomaliesCount' => (int) ($decoded['anomaliesCount'] ?? 0),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function callGroqStockRecommendation(array $productsPayload, int $delaiLivraison, int $margeSecuriteJours): ?array
    {
        $apiKey = trim((string) ($_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-70b-versatile',
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en optimisation de stock. Réponds uniquement en JSON valide avec les clés: recommandations. Chaque element recommandations doit contenir produit_id, produit_nom, consommationJour, delaiLivraison, margeSecurite, stockOptimal, stockActuel, risqueRupture, tendanceMensuelle, historiqueMois, ecart.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'task' => 'optimal_stock',
                                'delaiLivraison' => $delaiLivraison,
                                'margeSecuriteJours' => $margeSecuriteJours,
                                'products' => $productsPayload,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $content = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded) || !isset($decoded['recommandations']) || !is_array($decoded['recommandations'])) {
                return null;
            }

            $produitById = [];
            foreach ($this->produitRepository->findAll() as $produit) {
                if ($produit->getIdProduit() !== null) {
                    $produitById[(string) $produit->getIdProduit()] = $produit;
                }
            }

            $recommandations = [];
            foreach ($decoded['recommandations'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $produitId = (string) ($row['produit_id'] ?? '');
                if ($produitId === '' || !isset($produitById[$produitId])) {
                    continue;
                }

                $recommandations[] = [
                    'produit' => $produitById[$produitId],
                    'consommationJour' => (float) ($row['consommationJour'] ?? 0),
                    'delaiLivraison' => (int) ($row['delaiLivraison'] ?? $delaiLivraison),
                    'margeSecurite' => (float) ($row['margeSecurite'] ?? 0),
                    'stockOptimal' => (int) ($row['stockOptimal'] ?? 0),
                    'stockActuel' => (int) ($row['stockActuel'] ?? 0),
                    'risqueRupture' => (bool) ($row['risqueRupture'] ?? false),
                    'tendanceMensuelle' => (float) ($row['tendanceMensuelle'] ?? 0),
                    'historiqueMois' => (int) ($row['historiqueMois'] ?? 0),
                    'ecart' => (int) ($row['ecart'] ?? 0),
                ];
            }

            usort($recommandations, static fn (array $a, array $b): int => ($a['ecart']) <=> ($b['ecart']));

            return $recommandations;
        } catch (\Throwable) {
            return null;
        }
    }
}

