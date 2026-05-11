<?php

declare(strict_types=1);

namespace App\Service\AgriData;

use App\Entity\Culture;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PlantEncyclopediaService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        #[Autowire('%env(default::PERENUAL_API_KEY)%')]
        private readonly string $perenualApiKey,
    ) {
    }

    /**
     * @return array{name:string, scientificName:string|null, watering:string|null, sunlight:string|null, careLevel:string|null, growthRate:string|null, source:string}
     */
    public function fetchByCulture(Culture $culture): array
    {
        return $this->fetchByName(trim((string) $culture->getNomCulture()));
    }

    /**
     * @return array{name:string, scientificName:string|null, watering:string|null, sunlight:string|null, careLevel:string|null, growthRate:string|null, source:string}
     */
    public function fetchByName(string $name): array
    {
        $name = trim($name);

        $fallback = [
            'name' => $name,
            'scientificName' => null,
            'watering' => null,
            'sunlight' => null,
            'careLevel' => null,
            'growthRate' => null,
            'source' => 'db_fallback',
        ];

        if ($name === '' || $this->perenualApiKey === '') {
            return $fallback;
        }

        $cacheKey = 'agri.perenual.' . sha1(mb_strtolower($name));

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($name, $fallback): array {
                $item->expiresAfter(43200);

                $searchTerm = $this->normalizeSearchTerm($name);

                $searchResponse = $this->httpClient->request('GET', 'https://perenual.com/api/species-list', [
                    'query' => [
                        'key' => $this->perenualApiKey,
                        'q' => $searchTerm,
                        'page' => 1,
                    ],
                    'timeout' => 3,
                ]);

                if ($searchResponse->getStatusCode() !== 200) {
                    return $fallback;
                }

                $searchData = $searchResponse->toArray(false);
                $first = $searchData['data'][0] ?? null;

                if (!is_array($first) || !isset($first['id'])) {
                    return $fallback;
                }

                $detailsResponse = $this->httpClient->request('GET', sprintf('https://perenual.com/api/species/details/%s', (string) $first['id']), [
                    'query' => [
                        'key' => $this->perenualApiKey,
                    ],
                    'timeout' => 3,
                ]);

                if ($detailsResponse->getStatusCode() !== 200) {
                    return [
                        'name' => (string) ($first['common_name'] ?? $name),
                        'scientificName' => isset($first['scientific_name'][0]) ? (string) $first['scientific_name'][0] : null,
                        'watering' => null,
                        'sunlight' => null,
                        'careLevel' => null,
                        'growthRate' => null,
                        'source' => 'perenual_partial',
                    ];
                }

                $details = $detailsResponse->toArray(false);

                $sunlightValue = $details['sunlight'] ?? null;
                $sunlight = null;
                if (is_array($sunlightValue)) {
                    $sunlight = implode(', ', array_map(static fn ($item): string => (string) $item, $sunlightValue));
                } elseif (is_string($sunlightValue)) {
                    $sunlight = $sunlightValue;
                }

                return [
                    'name' => (string) ($details['common_name'] ?? $first['common_name'] ?? $name),
                    'scientificName' => isset($details['scientific_name'][0]) ? (string) $details['scientific_name'][0] : null,
                    'watering' => isset($details['watering']) ? (string) $details['watering'] : null,
                    'sunlight' => $sunlight,
                    'careLevel' => isset($details['care_level']) ? (string) $details['care_level'] : null,
                    'growthRate' => isset($details['growth_rate']) ? (string) $details['growth_rate'] : null,
                    'source' => 'perenual_api',
                ];
            });
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function normalizeSearchTerm(string $name): string
    {
        $value = mb_strtolower(trim($name));

        return match (true) {
            str_contains($value, 'ble') || str_contains($value, 'bl') => 'wheat',
            str_contains($value, 'tomate') => 'tomato',
            str_contains($value, 'pomme de terre') || str_contains($value, 'patate') => 'potato',
            str_contains($value, 'olive') || str_contains($value, 'olivier') => 'olive tree',
            default => $name,
        };
    }
}
