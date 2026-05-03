<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Culture;

class CultureMetricsService
{
    public function applyEstimates(Culture $culture): void
    {
        $dateSemis = $culture->getDateSemis();
        $parcelle = $culture->getParcelle();
        $nomCulture = trim((string) $culture->getNomCulture());

        if ($dateSemis === null || $parcelle === null || $nomCulture === '') {
            $culture->setDateRecolteEstimee(null);
            $culture->setRendementEstime(null);

            return;
        }

        $profile = $this->getCropProfile($nomCulture);
        $soilMultiplier = $this->getSoilMultiplier($parcelle->getTypeSol());
        $stateMultiplier = $this->getStateMultiplier($culture->getEtatCroissance());

        $dateReference = \DateTimeImmutable::createFromInterface($dateSemis);
        $culture->setDateRecolteEstimee($dateReference->modify(sprintf('+%d days', $profile['cycleDays'])));

        $surface = (float) $parcelle->getSurface();
        $estimatedYield = $surface * $profile['yieldPerHa'] * $soilMultiplier * $stateMultiplier;
        $culture->setRendementEstime(round(max(0.0, $estimatedYield), 2));
    }

    /**
     * @return array{cycleDays:int, yieldPerHa:float}
     */
    private function getCropProfile(string $nomCulture): array
    {
        $value = mb_strtolower($nomCulture);

        if (str_contains($value, 'ble') || str_contains($value, 'bl')) {
            return ['cycleDays' => 120, 'yieldPerHa' => 4.8];
        }

        if (str_contains($value, 'tomate')) {
            return ['cycleDays' => 95, 'yieldPerHa' => 35.0];
        }

        if (str_contains($value, 'pomme') || str_contains($value, 'patate')) {
            return ['cycleDays' => 110, 'yieldPerHa' => 28.0];
        }

        if (str_contains($value, 'olive')) {
            return ['cycleDays' => 240, 'yieldPerHa' => 6.5];
        }

        return ['cycleDays' => 130, 'yieldPerHa' => 12.0];
    }

    private function getSoilMultiplier(?string $typeSol): float
    {
        $soil = mb_strtolower(trim((string) $typeSol));

        return match (true) {
            str_contains($soil, 'argile') => 0.95,
            str_contains($soil, 'limon') => 1.12,
            str_contains($soil, 'sable') => 0.85,
            str_contains($soil, 'calcaire') => 0.9,
            default => 1.0,
        };
    }

    private function getStateMultiplier(?string $etatCroissance): float
    {
        $state = mb_strtolower(trim((string) $etatCroissance));

        return match ($state) {
            'semis' => 0.8,
            'croissance' => 0.95,
            'floraison' => 1.05,
            'recolte', 'recolte termine' => 1.0,
            default => 1.0,
        };
    }
}
