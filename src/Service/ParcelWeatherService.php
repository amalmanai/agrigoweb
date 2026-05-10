<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Parcelle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ParcelWeatherService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        #[Autowire('%env(OPENWEATHER_API_KEY)%')]
        private readonly string $apiKey,
    ) {
    }

    /**
     * @return array{temperature:float, feelsLike:float, humidity:int, windSpeed:float, description:string, city:string, rain1h:float, rain3h:float}|null
     */
    public function fetchCurrentByParcelle(Parcelle $parcelle): ?array
    {
        if ($this->apiKey === '' || $parcelle->getCoordonneesGps() === null) {
            return null;
        }

        $coords = $this->extractCoordinates($parcelle->getCoordonneesGps());
        if ($coords === null) {
            return null;
        }

        try {
            $cacheKey = sprintf('agri.weather.%0.4f.%0.4f', $coords['lat'], $coords['lon']);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($coords): ?array {
                $item->expiresAfter(900);

                $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                    'query' => [
                        'lat' => $coords['lat'],
                        'lon' => $coords['lon'],
                        'units' => 'metric',
                        'lang' => 'fr',
                        'appid' => $this->apiKey,
                    ],
                    'timeout' => 2,
                ]);

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $data = $response->toArray(false);

                if (!isset($data['main'], $data['weather'][0], $data['wind'])) {
                    return null;
                }

                return [
                    'temperature' => (float) ($data['main']['temp'] ?? 0.0),
                    'feelsLike' => (float) ($data['main']['feels_like'] ?? 0.0),
                    'humidity' => (int) ($data['main']['humidity'] ?? 0),
                    'windSpeed' => (float) ($data['wind']['speed'] ?? 0.0),
                    'description' => (string) ($data['weather'][0]['description'] ?? 'indisponible'),
                    'city' => (string) ($data['name'] ?? 'zone parcelle'),
                    'rain1h' => (float) ($data['rain']['1h'] ?? 0.0),
                    'rain3h' => (float) ($data['rain']['3h'] ?? 0.0),
                ];
            });
        } catch (\Throwable) {
            return null;
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
