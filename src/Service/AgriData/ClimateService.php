<?php

declare(strict_types=1);

namespace App\Service\AgriData;

use App\Entity\Parcelle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClimateService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{solarRadiation:float|null, unit:string, source:string, period:string}
     */
    public function fetchSolarRadiationByParcelle(Parcelle $parcelle): array
    {
        $fallback = [
            'solarRadiation' => null,
            'unit' => 'kWh/m2/day',
            'source' => 'db_fallback',
            'period' => '30d',
        ];

        $gps = (string) ($parcelle->getCoordonneesGps() ?? '');
        $coords = $this->extractCoordinates($gps);
        if ($coords === null) {
            return $fallback;
        }

        $cacheKey = sprintf('agri.climate.nasa.%0.4f.%0.4f', $coords['lat'], $coords['lon']);

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($coords, $fallback): array {
                $item->expiresAfter(21600);

                $end = new \DateTimeImmutable('today');
                $start = $end->modify('-30 days');

                $response = $this->httpClient->request('GET', 'https://power.larc.nasa.gov/api/temporal/daily/point', [
                    'query' => [
                        'parameters' => 'ALLSKY_SFC_SW_DWN',
                        'community' => 'AG',
                        'longitude' => $coords['lon'],
                        'latitude' => $coords['lat'],
                        'start' => $start->format('Ymd'),
                        'end' => $end->format('Ymd'),
                        'format' => 'JSON',
                    ],
                    'timeout' => 3,
                ]);

                if ($response->getStatusCode() !== 200) {
                    return $fallback;
                }

                $data = $response->toArray(false);
                $series = $data['properties']['parameter']['ALLSKY_SFC_SW_DWN'] ?? null;

                if (!is_array($series) || $series === []) {
                    return $fallback;
                }

                $values = array_values(array_filter($series, static fn ($v): bool => is_numeric($v) && (float) $v >= 0));

                if ($values === []) {
                    return $fallback;
                }

                $average = array_sum($values) / count($values);

                return [
                    'solarRadiation' => round((float) $average, 2),
                    'unit' => 'kWh/m2/day',
                    'source' => 'nasa_power',
                    'period' => '30d',
                ];
            });
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array{lat:float, lon:float}|null
     */
    private function extractCoordinates(string $gps): ?array
    {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $gps, $matches)) {
            return null;
        }

        return [
            'lat' => (float) $matches[1],
            'lon' => (float) $matches[2],
        ];
    }
}
