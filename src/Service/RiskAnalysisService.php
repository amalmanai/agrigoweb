<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlerteRisque;
use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\User;
use App\Repository\AlerteRisqueRepository;
use App\Repository\CultureRepository;
use App\Service\AgriData\ClimateService;
use App\Service\AgriData\PlantEncyclopediaService;
use App\Service\AgriData\SoilService;
use Doctrine\ORM\EntityManagerInterface;

class RiskAnalysisService
{
    public const ALERT_FROST_HEAT = 'Frost & Heat Risk';
    public const ALERT_WATER_STRESS = 'Water Stress Risk';
    public const ALERT_SOIL_COMPATIBILITY = 'Soil Compatibility Risk';

    public function __construct(
        private readonly ParcelWeatherService $parcelWeatherService,
        private readonly SoilService $soilService,
        private readonly ClimateService $climateService,
        private readonly PlantEncyclopediaService $plantEncyclopediaService,
        private readonly AlerteRisqueRepository $alerteRisqueRepository,
        private readonly CultureRepository $cultureRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{cultureId:int|null,status:string,highestSeverity:string,openAlerts:int,created:int,resolved:int}
     */
    public function analyzeCulture(Culture $culture, bool $flush = true): array
    {
        $parcelle = $culture->getParcelle();
        if ($parcelle === null) {
            return [
                'cultureId' => $culture->getId(),
                'status' => 'no_parcelle',
                'highestSeverity' => AlerteRisque::SEVERITY_GREEN,
                'openAlerts' => 0,
                'created' => 0,
                'resolved' => 0,
            ];
        }

        $weather = $this->parcelWeatherService->fetchCurrentByParcelle($parcelle);
        $climate = $this->climateService->fetchSolarRadiationByParcelle($parcelle);
        $soil = $this->soilService->fetchByParcelle($parcelle);
        $plant = $this->plantEncyclopediaService->fetchByCulture($culture);

        $rules = [
            self::ALERT_FROST_HEAT => $this->buildFrostHeatAlert($culture, $weather, $plant),
            self::ALERT_WATER_STRESS => $this->buildWaterStressAlert($culture, $weather, $climate, $plant),
            self::ALERT_SOIL_COMPATIBILITY => $this->buildSoilCompatibilityAlert($culture, $soil),
        ];

        $created = 0;
        $resolved = 0;

        foreach ($rules as $type => $payload) {
            if ($payload !== null) {
                if ($this->upsertOpenAlert($culture, $type, $payload['description'], $payload['severity'])) {
                    ++$created;
                }
            } else {
                if ($this->resolveOpenAlert($culture, $type)) {
                    ++$resolved;
                }
            }
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        $openAlerts = $this->alerteRisqueRepository->findUnresolvedByCulture($culture);

        return [
            'cultureId' => $culture->getId(),
            'status' => $openAlerts === [] ? 'healthy' : 'risk',
            'highestSeverity' => $this->highestSeverity($openAlerts),
            'openAlerts' => count($openAlerts),
            'created' => $created,
            'resolved' => $resolved,
        ];
    }

    /**
     * @return array{analyzed:int,created:int,resolved:int}
     */
    public function analyzeAllActiveCultures(?User $owner = null): array
    {
        $cultures = $this->cultureRepository->findActiveCultures($owner);

        $analyzed = 0;
        $created = 0;
        $resolved = 0;

        foreach ($cultures as $culture) {
            $result = $this->analyzeCulture($culture, false);
            ++$analyzed;
            $created += $result['created'];
            $resolved += $result['resolved'];
        }

        $this->entityManager->flush();

        return [
            'analyzed' => $analyzed,
            'created' => $created,
            'resolved' => $resolved,
        ];
    }

    /**
     * @return array{green:int,yellow:int,red:int,status:string}
     */
    public function getSystemHealthStatus(?User $owner = null): array
    {
        $red = $this->alerteRisqueRepository->countUnresolvedBySeverity(AlerteRisque::SEVERITY_RED, $owner);
        $yellow = $this->alerteRisqueRepository->countUnresolvedBySeverity(AlerteRisque::SEVERITY_YELLOW, $owner);

        $status = 'green';
        if ($red > 0) {
            $status = 'red';
        } elseif ($yellow > 0) {
            $status = 'yellow';
        }

        return [
            'green' => max(0, $this->cultureRepository->countAll($owner) - ($red + $yellow)),
            'yellow' => $yellow,
            'red' => $red,
            'status' => $status,
        ];
    }

    /**
     * @param array{temperature:float, feelsLike:float, humidity:int, windSpeed:float, description:string, city:string, rain1h:float, rain3h:float}|null $weather
     * @param array<string, mixed> $plant
     * @return array{severity:string, description:string}|null
     */
    private function buildFrostHeatAlert(Culture $culture, ?array $weather, array $plant): ?array
    {
        if ($weather === null) {
            return null;
        }

        $range = $this->resolveIdealTemperatureRange((string) ($culture->getNomCulture() ?? ''), $plant);
        $temperature = (float) $weather['temperature'];

        if ($temperature >= $range['min'] && $temperature <= $range['max']) {
            return null;
        }

        $mode = $temperature < $range['min'] ? 'gel' : 'chaleur excessive';

        return [
            'severity' => AlerteRisque::SEVERITY_RED,
            'description' => sprintf(
                'Risque %s detecte: %.1fC observe alors que la plage ideale est %.1fC - %.1fC.',
                $mode,
                $temperature,
                $range['min'],
                $range['max']
            ),
        ];
    }

    /**
     * @param array{temperature:float, feelsLike:float, humidity:int, windSpeed:float, description:string, city:string, rain1h:float, rain3h:float}|null $weather
     * @param array{solarRadiation:float|null, unit:string, source:string, period:string} $climate
     * @param array<string, mixed> $plant
     * @return array{severity:string, description:string}|null
     */
    private function buildWaterStressAlert(Culture $culture, ?array $weather, array $climate, array $plant): ?array
    {
        if ($weather === null) {
            return null;
        }

        $solar = $climate['solarRadiation'];
        if (!is_numeric($solar)) {
            return null;
        }

        $rain = max((float) ($weather['rain1h'] ?? 0.0), (float) ($weather['rain3h'] ?? 0.0));
        $needsFrequentWater = $this->isFrequentWatering((string) ($plant['watering'] ?? ''));

        if ((float) $solar < 6.2 || $rain > 0.2 || !$needsFrequentWater) {
            return null;
        }

        $severity = (float) $solar >= 7.5 ? AlerteRisque::SEVERITY_RED : AlerteRisque::SEVERITY_YELLOW;

        return [
            'severity' => $severity,
            'description' => sprintf(
                'Stress hydrique probable sur %s: radiation %.2f kWh/m2/day et pluie recente insuffisante (%.2f mm).',
                (string) ($culture->getNomCulture() ?? 'culture'),
                (float) $solar,
                $rain
            ),
        ];
    }

    /**
     * @param array{texture:string|null, ph:float|null, nitrogen:float|null, source:string} $soil
     * @return array{severity:string, description:string}|null
     */
    private function buildSoilCompatibilityAlert(Culture $culture, array $soil): ?array
    {
        $requirements = $this->resolveSoilRequirements((string) ($culture->getNomCulture() ?? ''));

        $ph = $soil['ph'] ?? null;
        $nitrogen = $soil['nitrogen'] ?? null;

        $phMismatch = is_numeric($ph) && ((float) $ph < $requirements['phMin'] || (float) $ph > $requirements['phMax']);
        $nitrogenLow = is_numeric($nitrogen) && (float) $nitrogen < $requirements['nitrogenMin'];

        if (!$phMismatch && !$nitrogenLow) {
            return null;
        }

        $severity = ($phMismatch && $nitrogenLow) ? AlerteRisque::SEVERITY_RED : AlerteRisque::SEVERITY_YELLOW;

        $issues = [];
        if ($phMismatch) {
            $issues[] = sprintf('pH %.2f hors plage %.1f - %.1f', (float) $ph, $requirements['phMin'], $requirements['phMax']);
        }
        if ($nitrogenLow) {
            $issues[] = sprintf('azote %.2f inferieur au seuil %.2f', (float) $nitrogen, $requirements['nitrogenMin']);
        }

        return [
            'severity' => $severity,
            'description' => sprintf('Incompatibilite sol detectee pour %s: %s.', (string) ($culture->getNomCulture() ?? 'culture'), implode('; ', $issues)),
        ];
    }

    private function upsertOpenAlert(Culture $culture, string $type, string $description, string $severity): bool
    {
        $existing = $this->alerteRisqueRepository->findOpenByCultureAndType($culture, $type);
        if ($existing !== null) {
            $existing->setDescription($description)
                ->setSeverity($severity)
                ->setDateAlerte(new \DateTimeImmutable('now'));

            return false;
        }

        $alert = (new AlerteRisque())
            ->setCulture($culture)
            ->setTypeAlerte($type)
            ->setDescription($description)
            ->setSeverity($severity)
            ->setIsResolved(false)
            ->setDateAlerte(new \DateTimeImmutable('now'));

        $this->entityManager->persist($alert);

        return true;
    }

    private function resolveOpenAlert(Culture $culture, string $type): bool
    {
        $existing = $this->alerteRisqueRepository->findOpenByCultureAndType($culture, $type);
        if ($existing === null) {
            return false;
        }

        $existing->setIsResolved(true)
            ->setResolvedAt(new \DateTimeImmutable('now'));

        return true;
    }

    /**
     * @param AlerteRisque[] $alerts
     */
    private function highestSeverity(array $alerts): string
    {
        $highest = AlerteRisque::SEVERITY_GREEN;

        foreach ($alerts as $alert) {
            if ($alert->getSeverity() === AlerteRisque::SEVERITY_RED) {
                return AlerteRisque::SEVERITY_RED;
            }

            if ($alert->getSeverity() === AlerteRisque::SEVERITY_YELLOW) {
                $highest = AlerteRisque::SEVERITY_YELLOW;
            }
        }

        return $highest;
    }

    /**
     * @param array<string, mixed> $plant
     * @return array{min:float,max:float}
     */
    private function resolveIdealTemperatureRange(string $cultureName, array $plant): array
    {
        $name = mb_strtolower(trim($cultureName));

        $preset = match (true) {
            str_contains($name, 'ble'), str_contains($name, 'orge') => ['min' => 8.0, 'max' => 27.0],
            str_contains($name, 'tomate'), str_contains($name, 'poivron') => ['min' => 16.0, 'max' => 32.0],
            str_contains($name, 'pomme de terre') => ['min' => 10.0, 'max' => 24.0],
            str_contains($name, 'olive'), str_contains($name, 'olivier') => ['min' => 5.0, 'max' => 36.0],
            default => ['min' => 7.0, 'max' => 35.0],
        };

        $sunlight = mb_strtolower((string) ($plant['sunlight'] ?? ''));
        if (str_contains($sunlight, 'full sun')) {
            $preset['max'] += 1.0;
        }

        return $preset;
    }

    private function isFrequentWatering(string $watering): bool
    {
        $value = mb_strtolower(trim($watering));
        if ($value === '') {
            return false;
        }

        return str_contains($value, 'frequent')
            || str_contains($value, 'high')
            || str_contains($value, 'daily')
            || str_contains($value, 'often')
            || str_contains($value, 'moyen')
            || str_contains($value, 'normal');
    }

    /**
     * @return array{phMin:float,phMax:float,nitrogenMin:float}
     */
    private function resolveSoilRequirements(string $cultureName): array
    {
        $name = mb_strtolower(trim($cultureName));

        return match (true) {
            str_contains($name, 'ble'), str_contains($name, 'orge') => ['phMin' => 6.0, 'phMax' => 7.5, 'nitrogenMin' => 18.0],
            str_contains($name, 'tomate'), str_contains($name, 'poivron') => ['phMin' => 6.0, 'phMax' => 6.9, 'nitrogenMin' => 22.0],
            str_contains($name, 'pomme de terre') => ['phMin' => 5.2, 'phMax' => 6.5, 'nitrogenMin' => 20.0],
            str_contains($name, 'olive'), str_contains($name, 'olivier') => ['phMin' => 6.0, 'phMax' => 8.0, 'nitrogenMin' => 12.0],
            default => ['phMin' => 6.0, 'phMax' => 7.5, 'nitrogenMin' => 18.0],
        };
    }
}
