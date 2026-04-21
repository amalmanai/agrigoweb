<?php

declare(strict_types=1);

namespace App\Service\AgriData;

use App\Entity\Parcelle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SoilService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        #[Autowire('%env(KAEGRO_API_BASE_URL)%')]
        private readonly string $kaegroBaseUrl,
    ) {
    }

    /**
     * @return array{texture:string|null, ph:float|null, nitrogen:float|null, source:string}
     */
    public function fetchByParcelle(Parcelle $parcelle): array
    {
        $fallback = [
            'texture' => $parcelle->getTypeSol() ?: null,
            'ph' => null,
            'nitrogen' => null,
            'source' => 'db_fallback',
        ];

        $gps = (string) ($parcelle->getCoordonneesGps() ?? '');
        $coords = $this->extractCoordinates($gps);
        if ($coords === null) {
            return $fallback;
        }

        $cacheKey = sprintf('agri.soil.v2.%0.4f.%0.4f', $coords['lat'], $coords['lon']);

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($coords, $fallback): array {
                $item->expiresAfter(21600);

                $kaegroData = $this->fetchFromKaegro($coords['lat'], $coords['lon']);
                if ($kaegroData !== null) {
                    return $kaegroData;
                }

                $soilGridsData = $this->fetchFromSoilGrids($coords['lat'], $coords['lon']);
                if ($soilGridsData !== null) {
                    return $soilGridsData;
                }

                $soilClassData = $this->fetchFromSoilGridsClassification($coords['lat'], $coords['lon']);
                if ($soilClassData !== null) {
                    return $soilClassData;
                }

                return $fallback;
            });
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array{texture:string|null, ph:float|null, nitrogen:float|null, source:string}|null
     */
    private function fetchFromKaegro(float $lat, float $lon): ?array
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->kaegroBaseUrl, '/') . '/soil', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                ],
                'timeout' => 2,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray(false);

            $texture = $data['texture']
                ?? $data['soil']['texture']
                ?? $data['data']['texture']
                ?? null;

            $phRaw = $data['ph']
                ?? $data['pH']
                ?? $data['soil']['ph']
                ?? $data['data']['ph']
                ?? null;

            $nitrogenRaw = $data['nitrogen']
                ?? $data['N']
                ?? $data['soil']['nitrogen']
                ?? $data['soil']['n']
                ?? $data['data']['nitrogen']
                ?? null;

            $ph = is_numeric($phRaw) ? round((float) $phRaw, 2) : null;
            $nitrogen = is_numeric($nitrogenRaw) ? round((float) $nitrogenRaw, 2) : null;

            if ($texture === null && $ph === null && $nitrogen === null) {
                return null;
            }

            return [
                'texture' => $texture !== null ? (string) $texture : null,
                'ph' => $ph,
                'nitrogen' => $nitrogen,
                'source' => 'kaegro_api',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{texture:string|null, ph:float|null, nitrogen:float|null, source:string}|null
     */
    private function fetchFromSoilGrids(float $lat, float $lon): ?array
    {
        try {
            $url = sprintf(
                'https://rest.isric.org/soilgrids/v2.0/properties/query?lon=%s&lat=%s&property=phh2o&property=sand&property=silt&property=clay&depth=0-5cm&depth=5-15cm&depth=15-30cm&value=mean',
                rawurlencode((string) $lon),
                rawurlencode((string) $lat)
            );

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 3,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray(false);
            $layers = $data['properties']['layers'] ?? null;
            if (!is_array($layers)) {
                return null;
            }

            $ph = null;
            $sand = null;
            $silt = null;
            $clay = null;

            foreach ($layers as $layer) {
                if (!is_array($layer)) {
                    continue;
                }

                $name = (string) ($layer['name'] ?? '');
                $depthEntry = $this->firstNumericMean($layer['depths'] ?? []);
                if (!is_numeric($depthEntry)) {
                    continue;
                }

                $value = (float) $depthEntry;
                if ($name === 'phh2o') {
                    // SoilGrids pH is often scaled by 10.
                    $ph = round($value / 10.0, 2);
                } elseif ($name === 'sand') {
                    $sand = $value;
                } elseif ($name === 'silt') {
                    $silt = $value;
                } elseif ($name === 'clay') {
                    $clay = $value;
                }
            }

            $texture = null;
            $fractions = array_filter([
                'Sableux' => $sand,
                'Limoneux' => $silt,
                'Argileux' => $clay,
            ], static fn ($v): bool => is_numeric($v));

            if ($fractions !== []) {
                arsort($fractions);
                $texture = (string) array_key_first($fractions);
            }

            if ($texture === null && $ph === null) {
                return null;
            }

            return [
                'texture' => $texture,
                'ph' => $ph,
                'nitrogen' => null,
                'source' => 'soilgrids_api',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{texture:string|null, ph:float|null, nitrogen:float|null, source:string}|null
     */
    private function fetchFromSoilGridsClassification(float $lat, float $lon): ?array
    {
        try {
            $url = sprintf(
                'https://rest.isric.org/soilgrids/v2.0/classification/query?lon=%s&lat=%s',
                rawurlencode((string) $lon),
                rawurlencode((string) $lat)
            );

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 3,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray(false);
            $className = isset($data['wrb_class_name']) ? (string) $data['wrb_class_name'] : '';
            if ($className === '') {
                return null;
            }

            $profile = $this->classProfile($className);

            return [
                'texture' => $profile['texture'],
                'ph' => $profile['ph'],
                'nitrogen' => null,
                'source' => 'soilgrids_class_api',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, mixed> $depths
     */
    private function firstNumericMean(array $depths): ?float
    {
        foreach ($depths as $depth) {
            if (!is_array($depth)) {
                continue;
            }

            $mean = $depth['values']['mean'] ?? null;
            if (is_numeric($mean)) {
                return (float) $mean;
            }
        }

        return null;
    }

    /**
     * @return array{texture:string, ph:float}
     */
    private function classProfile(string $className): array
    {
        $value = mb_strtolower($className);

        return match (true) {
            str_contains($value, 'vertisol') => ['texture' => 'Argileux', 'ph' => 7.4],
            str_contains($value, 'calcisol') => ['texture' => 'Calcaire', 'ph' => 7.8],
            str_contains($value, 'arenosol') => ['texture' => 'Sableux', 'ph' => 6.3],
            str_contains($value, 'luvisol') => ['texture' => 'Limono-argileux', 'ph' => 6.8],
            str_contains($value, 'regosol') => ['texture' => 'Sablo-limoneux', 'ph' => 6.6],
            default => ['texture' => 'Limoneux', 'ph' => 6.9],
        };
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
