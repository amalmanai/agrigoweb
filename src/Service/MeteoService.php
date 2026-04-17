<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(OPENWEATHER_API_KEY)%')]
        private readonly string $apiKey,
    ) {
    }

    /**
     * @return array{temperature:?float, humidite:?int, pluie_prevue_prochaines_6h:bool, description_meteo:string, avertissement:?string, disponible:bool}
     */
    public function getMeteoPourParcelle(string $coordonneesGps, bool $modeAuto = false): array
    {
        [$latitude, $longitude] = $this->parseCoordinates($coordonneesGps);
        if (null === $latitude || null === $longitude) {
            return $this->unavailable('Coordonnées invalides');
        }

        if ('' === trim($this->apiKey)) {
            return $this->unavailable('Clé OpenWeatherMap manquante');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/forecast', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
                'timeout' => 10,
            ]);
            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (ExceptionInterface) {
            return $this->unavailable('Service météo temporairement indisponible');
        }

        if ($statusCode >= 400) {
            return $this->unavailable('Réponse météo invalide');
        }

        if (isset($data['cod']) && (string) $data['cod'] !== '200') {
            return $this->unavailable('Prévisions météo indisponibles');
        }

        $list = isset($data['list']) && is_array($data['list']) ? array_values($data['list']) : [];
        if ($list === []) {
            return $this->unavailable('Météo indisponible');
        }

        $current = $list[0];
        $nextSixHours = $this->extractNextSixHours($list);
        $rainForecast = false;

        foreach ($nextSixHours as $item) {
            if ($this->forecastHasRain($item)) {
                $rainForecast = true;
                break;
            }
        }

        $temperature = isset($current['main']['temp']) ? (float) $current['main']['temp'] : null;
        $humidity = isset($current['main']['humidity']) ? (int) $current['main']['humidity'] : null;
        $description = isset($current['weather'][0]['description']) ? (string) $current['weather'][0]['description'] : '—';

        return [
            'temperature' => $temperature,
            'humidite' => $humidity,
            'pluie_prevue_prochaines_6h' => $rainForecast,
            'description_meteo' => $description,
            'avertissement' => $modeAuto && $rainForecast ? 'Irrigation déconseillée' : null,
            'disponible' => true,
        ];
    }

    /**
     * @param list<array<string, mixed>> $list
     * @return list<array<string, mixed>>
     */
    private function extractNextSixHours(array $list): array
    {
        $cutoff = (new \DateTimeImmutable('+6 hours'))->getTimestamp();
        $result = [];

        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }

            $dt = isset($item['dt']) && is_numeric($item['dt']) ? (int) $item['dt'] : null;
            if (null === $dt) {
                continue;
            }

            if ($dt <= $cutoff) {
                $result[] = $item;
            }
        }

        return [] !== $result ? $result : array_slice($list, 0, 2);
    }

    /**
     * @return array{0:?float,1:?float}
     */
    private function parseCoordinates(string $coordonneesGps): array
    {
        $parts = array_map('trim', explode(',', $coordonneesGps));
        if (count($parts) < 2) {
            return [null, null];
        }

        $latitude = is_numeric($parts[0]) ? (float) $parts[0] : null;
        $longitude = is_numeric($parts[1]) ? (float) $parts[1] : null;

        return [$latitude, $longitude];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function forecastHasRain(array $item): bool
    {
        if (isset($item['rain']) && is_array($item['rain'])) {
            foreach ($item['rain'] as $value) {
                if (is_numeric($value) && (float) $value > 0) {
                    return true;
                }
            }
        }

        if (!isset($item['weather']) || !is_array($item['weather'])) {
            return false;
        }

        foreach ($item['weather'] as $weather) {
            if (!is_array($weather)) {
                continue;
            }

            $main = strtolower((string) ($weather['main'] ?? ''));
            if (in_array($main, ['rain', 'drizzle', 'thunderstorm'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{temperature:?float, humidite:?int, pluie_prevue_prochaines_6h:bool, description_meteo:string, avertissement:?string, disponible:bool}
     */
    private function unavailable(string $description): array
    {
        return [
            'temperature' => null,
            'humidite' => null,
            'pluie_prevue_prochaines_6h' => false,
            'description_meteo' => $description,
            'avertissement' => null,
            'disponible' => false,
        ];
    }
}