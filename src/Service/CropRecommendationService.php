<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Service\AgriData\ClimateService;
use App\Service\AgriData\SoilService;

class CropRecommendationService
{
    public function __construct(
        private readonly SoilService $soilService,
        private readonly ClimateService $climateService,
    ) {
    }

    /**
     * @return array{crop:string, score:int, confidence:int, explanation:string, alternatives:list<array{crop:string, score:int}>, terrain:array{texture:string|null, ph:float|null, solarRadiation:float|null, sources:array{soil:string, climate:string}}}
     */
    public function recommendForParcelle(Parcelle $parcelle, \DateTimeInterface $referenceDate): array
    {
        $month = (int) $referenceDate->format('n');
        $surface = (float) $parcelle->getSurface();

        $soilData = $this->soilService->fetchByParcelle($parcelle);
        $climateData = $this->climateService->fetchSolarRadiationByParcelle($parcelle);

        $candidates = [
            ['crop' => 'Ble dur', 'textures' => ['limon', 'argile'], 'min' => 0.5, 'max' => 80.0, 'months' => [10, 11, 12], 'phMin' => 6.0, 'phMax' => 7.8, 'solarMin' => 4.0, 'solarMax' => 7.2],
            ['crop' => 'Tomate industrielle', 'textures' => ['limon', 'sable'], 'min' => 0.2, 'max' => 15.0, 'months' => [2, 3, 4], 'phMin' => 6.0, 'phMax' => 6.9, 'solarMin' => 5.0, 'solarMax' => 8.5],
            ['crop' => 'Pomme de terre', 'textures' => ['sable', 'limon'], 'min' => 0.2, 'max' => 20.0, 'months' => [1, 2, 3, 9], 'phMin' => 5.2, 'phMax' => 6.7, 'solarMin' => 3.8, 'solarMax' => 6.6],
            ['crop' => 'Olivier', 'textures' => ['calcaire', 'argile'], 'min' => 1.0, 'max' => 120.0, 'months' => [10, 11, 12, 1], 'phMin' => 6.0, 'phMax' => 8.2, 'solarMin' => 5.2, 'solarMax' => 9.0],
        ];

        $scored = [];

        foreach ($candidates as $candidate) {
            $score = $this->computeCompatibilityScore(
                $soilData['texture'],
                $soilData['ph'],
                $climateData['solarRadiation'],
                $candidate['textures'],
                (float) $candidate['phMin'],
                (float) $candidate['phMax'],
                (float) $candidate['solarMin'],
                (float) $candidate['solarMax'],
            );

            $score += ($surface >= (float) $candidate['min'] && $surface <= (float) $candidate['max']) ? 10 : -6;
            $score += in_array($month, $candidate['months'], true) ? 8 : -2;

            $scored[] = [
                'crop' => $candidate['crop'],
                'score' => max(0, min(100, $score)),
            ];
        }

        usort(
            $scored,
            static fn (array $a, array $b): int => $b['score'] <=> $a['score']
        );

        $best = $scored[0];
        $confidencePenalty = ($soilData['source'] !== 'kaegro_api' || $climateData['source'] !== 'nasa_power') ? 8 : 0;
        $confidence = max(0, $best['score'] - $confidencePenalty);

        return [
            'crop' => $best['crop'],
            'score' => $best['score'],
            'confidence' => $confidence,
            'explanation' => sprintf(
                'Suggestion basee sur texture (%s), pH (%s), rayonnement solaire (%s), surface (%.2f ha) et saison courante.',
                $soilData['texture'] ?? 'non defini',
                $soilData['ph'] !== null ? (string) $soilData['ph'] : 'n/a',
                $climateData['solarRadiation'] !== null ? (string) $climateData['solarRadiation'] . ' ' . $climateData['unit'] : 'n/a',
                $surface
            ),
            'alternatives' => array_slice($scored, 1, 2),
            'terrain' => [
                'texture' => $soilData['texture'],
                'ph' => $soilData['ph'],
                'solarRadiation' => $climateData['solarRadiation'],
                'sources' => [
                    'soil' => $soilData['source'],
                    'climate' => $climateData['source'],
                ],
            ],
        ];
    }

    /**
     * @return array{crop:string, score:int, confidence:int, explanation:string, alternatives:list<array{crop:string, score:int}>, compatibilityScore:int, compatibilityLabel:string, terrain:array{texture:string|null, ph:float|null, solarRadiation:float|null, sources:array{soil:string, climate:string}}}
     */
    public function recommendNextForCulture(Culture $culture, \DateTimeInterface $referenceDate): array
    {
        $parcelle = $culture->getParcelle();

        if ($parcelle === null) {
            return [
                'crop' => 'Ble dur',
                'score' => 50,
                'confidence' => 50,
                'explanation' => 'Aucune parcelle liee: recommandation par defaut.',
                'alternatives' => [
                    ['crop' => 'Tomate industrielle', 'score' => 45],
                    ['crop' => 'Pomme de terre', 'score' => 40],
                ],
                'compatibilityScore' => 50,
                'compatibilityLabel' => 'Moyenne',
                'terrain' => [
                    'texture' => null,
                    'ph' => null,
                    'solarRadiation' => null,
                    'sources' => ['soil' => 'db_fallback', 'climate' => 'db_fallback'],
                ],
            ];
        }

        $result = $this->recommendForParcelle($parcelle, $referenceDate);
        $profile = $this->findProfileByCultureName((string) $culture->getNomCulture());

        $compatibility = $this->computeCompatibilityScore(
            $result['terrain']['texture'],
            $result['terrain']['ph'],
            $result['terrain']['solarRadiation'],
            $profile['textures'],
            $profile['phMin'],
            $profile['phMax'],
            $profile['solarMin'],
            $profile['solarMax'],
        );

        $result['compatibilityScore'] = $compatibility;
        $result['compatibilityLabel'] = $this->compatibilityLabel($compatibility);
        $current = mb_strtolower((string) $culture->getNomCulture());

        if (str_contains($current, mb_strtolower($result['crop']))) {
            $alternatives = $result['alternatives'];
            if (isset($alternatives[0])) {
                $result['crop'] = $alternatives[0]['crop'];
                $result['score'] = max(0, $alternatives[0]['score'] - 5);
                $result['confidence'] = $result['score'];
                $result['explanation'] .= ' Rotation appliquee: culture differente de la campagne en cours.';
            }
        }

        $result['explanation'] .= sprintf(' Compatibilite culture actuelle (%s): %d/100 (%s).', (string) $culture->getNomCulture(), $compatibility, $result['compatibilityLabel']);

        return $result;
    }

    /**
     * @param list<string> $preferredTextures
     */
    private function computeCompatibilityScore(
        ?string $texture,
        ?float $ph,
        ?float $solarRadiation,
        array $preferredTextures,
        float $phMin,
        float $phMax,
        float $solarMin,
        float $solarMax,
    ): int {
        $textureScore = 60;
        if ($texture !== null) {
            $normalized = mb_strtolower($texture);
            $textureScore = 40;
            foreach ($preferredTextures as $preferred) {
                if (str_contains($normalized, mb_strtolower($preferred))) {
                    $textureScore = 100;
                    break;
                }
            }
        }

        $phScore = 60;
        if ($ph !== null) {
            if ($ph >= $phMin && $ph <= $phMax) {
                $phScore = 100;
            } else {
                $distance = $ph < $phMin ? $phMin - $ph : $ph - $phMax;
                $phScore = max(20, 100 - (int) round($distance * 35));
            }
        }

        $solarScore = 60;
        if ($solarRadiation !== null) {
            if ($solarRadiation >= $solarMin && $solarRadiation <= $solarMax) {
                $solarScore = 100;
            } else {
                $distance = $solarRadiation < $solarMin ? $solarMin - $solarRadiation : $solarRadiation - $solarMax;
                $solarScore = max(20, 100 - (int) round($distance * 14));
            }
        }

        $score = (int) round(($textureScore * 0.35) + ($phScore * 0.35) + ($solarScore * 0.30));

        return max(0, min(100, $score));
    }

    /**
     * @return array{textures:list<string>, phMin:float, phMax:float, solarMin:float, solarMax:float}
     */
    private function findProfileByCultureName(string $name): array
    {
        $value = mb_strtolower(trim($name));

        if (str_contains($value, 'ble') || str_contains($value, 'bl')) {
            return ['textures' => ['limon', 'argile'], 'phMin' => 6.0, 'phMax' => 7.8, 'solarMin' => 4.0, 'solarMax' => 7.2];
        }

        if (str_contains($value, 'tomate')) {
            return ['textures' => ['limon', 'sable'], 'phMin' => 6.0, 'phMax' => 6.9, 'solarMin' => 5.0, 'solarMax' => 8.5];
        }

        if (str_contains($value, 'pomme') || str_contains($value, 'patate')) {
            return ['textures' => ['sable', 'limon'], 'phMin' => 5.2, 'phMax' => 6.7, 'solarMin' => 3.8, 'solarMax' => 6.6];
        }

        if (str_contains($value, 'olive')) {
            return ['textures' => ['calcaire', 'argile'], 'phMin' => 6.0, 'phMax' => 8.2, 'solarMin' => 5.2, 'solarMax' => 9.0];
        }

        return ['textures' => ['limon'], 'phMin' => 6.0, 'phMax' => 7.4, 'solarMin' => 4.2, 'solarMax' => 7.5];
    }

    private function compatibilityLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Elevee',
            $score >= 60 => 'Moyenne',
            default => 'Faible',
        };
    }
}
