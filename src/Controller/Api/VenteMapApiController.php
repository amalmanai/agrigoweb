<?php

namespace App\Controller\Api;

use App\Repository\MarketplaceOrderRepository;
use App\Entity\User;
use App\Entity\Vente;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/vente', name: 'api_vente')]
class VenteMapApiController extends AbstractController
{
    /**
     * Get location data for a vente (seller and delivery locations)
     * Uses OpenStreetMap Nominatim for geocoding
     */
    #[Route('/{id}/locations', name: '_map_locations', methods: ['GET'])]
    public function getMapLocations(Vente $vente, MarketplaceOrderRepository $marketplaceOrderRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Authentication required',
            ], 401);
        }

        try {
            $recolte = $vente->getRecolte();
            if (!$recolte) {
                return $this->json([
                    'error' => 'No harvest associated with this sale',
                    'seller' => null,
                ], 400);
            }

            // Allow seller owner OR buyer who has placed a marketplace order for this vente.
            $isOwner = $recolte->getUserId() === $user->getIdUser();
            $hasOrderAsBuyer = $marketplaceOrderRepository->count([
                'vente' => $vente,
                'buyer' => $user,
            ]) > 0;

            if (!$isOwner && !$hasOrderAsBuyer) {
                return $this->json([
                    'error' => 'Access denied for this sale',
                ], 403);
            }

            // Pickup location must come from the parcelle selected on the recolte when available.
            $sellerAddress = $recolte->getAdresse();
            $sellerCoordinates = null;

            if ($recolte->getParcelle() !== null) {
                $parcelle = $recolte->getParcelle();
                $sellerAddress = $parcelle->getNomParcelle() ?: 'Parcelle sans nom';

                if ($parcelle->getCoordonneesGps()) {
                    $sellerCoordinates = $this->extractCoordinatesFromGps((string) $parcelle->getCoordonneesGps());
                    if ($sellerCoordinates !== null) {
                        $sellerAddress .= ' (GPS: ' . $parcelle->getCoordonneesGps() . ')';
                    }
                }
            }

            if ($sellerCoordinates === null && $sellerAddress) {
                $sellerCoordinates = $this->geocodeAddress($sellerAddress);
            }

            if (!$sellerAddress) {
                $sellerAddress = 'Adresse de recolte non renseignee';
            }

            if ($sellerCoordinates === null) {
                $sellerCoordinates = [
                    'lat' => 35.8,
                    'lng' => 10.6,
                ];
            }

            // Get delivery location (for marketplace buyers, prefer their order delivery address).
            $viewerOrder = $marketplaceOrderRepository->findOneBy([
                'vente' => $vente,
                'buyer' => $user,
            ], ['orderedAt' => 'DESC']);

            $deliveryAddress = $viewerOrder?->getDeliveryAddress() ?: $vente->getDeliveryLocation();
            $deliveryCoordinates = null;
            
            if ($deliveryAddress) {
                // Check if coordinates are already stored
                if ($vente->getDeliveryLatitude() && $vente->getDeliveryLongitude()) {
                    $deliveryCoordinates = [
                        'lat' => (float)$vente->getDeliveryLatitude(),
                        'lng' => (float)$vente->getDeliveryLongitude(),
                    ];
                } else {
                    // Try to geocode
                    $coords = $this->geocodeAddress($deliveryAddress);
                    if ($coords) {
                        $deliveryCoordinates = $coords;
                        // Optionally store coordinates in database for future use
                    }
                }
            }

            return $this->json([
                'seller' => [
                    'name' => $recolte->getName() ?? 'Harvest #' . $recolte->getId(),
                    'address' => $sellerAddress,
                    'coordinates' => $sellerCoordinates,
                ],
                'delivery' => $deliveryCoordinates ? [
                    'address' => $deliveryAddress,
                    'coordinates' => $deliveryCoordinates,
                ] : null,
                'sale' => [
                    'id' => $vente->getId(),
                    'description' => $vente->getDescription(),
                    'price' => $vente->getPrice(),
                    'buyer' => $vente->getBuyerName() ?? 'Unknown',
                    'date' => $vente->getSaleDate()?->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Geocode an address using OpenStreetMap Nominatim API
     * Returns latitude and longitude
     */
    private function geocodeAddress(?string $address): ?array
    {
        if (!$address || empty(trim($address))) {
            return null;
        }

        $gpsCoordinates = $this->extractCoordinatesFromGps($address);
        if ($gpsCoordinates !== null) {
            return $gpsCoordinates;
        }

        try {
            // Use Tunisia as bias to improve results
            $query = trim($address);
            if (stripos($query, 'tunisia') === false && stripos($query, 'tunisie') === false) {
                $query .= ', Tunisia';
            }

            $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($query) 
                   . '&format=json&limit=1&timeout=10';
            
            // Create a stream context with timeout
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: AgriGoWeb/1.0 (+https://agrigoweb.local)',
                    'timeout' => 10,
                ]
            ];
            
            $context = stream_context_create($opts);
            
            // Set to use the context
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new \Exception('Failed to reach geocoding service');
            }

            $data = json_decode($response, true);
            
            if (!is_array($data) || count($data) === 0) {
                // Try without Tunisia suffix as fallback
                if (stripos($query, 'tunisia') !== false) {
                    return $this->geocodeAddress(str_ireplace(', Tunisia', '', $address));
                }
                return null;
            }

            $coords = [
                'lat' => (float)$data[0]['lat'],
                'lng' => (float)$data[0]['lon'],
            ];
            
            error_log("Geocoded '$address' to: " . json_encode($coords));
            return $coords;

        } catch (\Exception $e) {
            error_log("Geocoding error for '$address': " . $e->getMessage());
            return null;
        }
    }

    private function extractCoordinatesFromGps(string $value): ?array
    {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $value, $matches)) {
            return null;
        }

        return [
            'lat' => (float) $matches[1],
            'lng' => (float) $matches[2],
        ];
    }
}
