<?php
/**
 * Quick test to debug the API endpoint
 * Access at: http://localhost/test_map_api.php?id=11
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entity\Vente;
use Doctrine\ORM\EntityManager;

$kernel = \App\Kernel::bootKernel();
$entityManager = $kernel->getContainer()->get(EntityManager::class);

$venteId = $_GET['id'] ?? 11;
$vente = $entityManager->getRepository(Vente::class)->find($venteId);

if (!$vente) {
    die("Vente #$venteId not found\n");
}

echo "=== DEBUGGING VENTE #" . $vente->getId() . " ===\n\n";

echo "Buyer: " . ($vente->getBuyerName() ?? 'N/A') . "\n";
echo "Price: " . ($vente->getPrice() ?? 'N/A') . "\n";
echo "Delivery Location: " . ($vente->getDeliveryLocation() ?? 'N/A') . "\n";

$recolte = $vente->getRecolte();
if (!$recolte) {
    die("\n❌ No harvest associated with this sale\n");
}

echo "\n=== HARVEST INFO ===\n";
echo "ID: " . $recolte->getId() . "\n";
echo "Name: " . ($recolte->getName() ?? 'N/A') . "\n";
echo "Address: " . ($recolte->getAdresse() ?? 'N/A') . "\n";

if (!$recolte->getAdresse()) {
    die("\n❌ Harvest has no address\n");
}

echo "\n✅ All required data is present\n";

// Test geocoding
echo "\n=== TESTING GEOCODING ===\n";

$address = $recolte->getAdresse();
echo "Geocoding address: $address\n";

$url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($address . ', Tunisia') . '&format=json&limit=1&timeout=10';
echo "URL: $url\n\n";

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: AgriGoWeb/1.0 (+https://agrigoweb.local)',
        'timeout' => 10,
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to reach Nominatim API\n";
} else {
    echo "✅ Got response from Nominatim API\n";
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        echo "✅ Got geocoding result:\n";
        echo "  Lat: " . $data[0]['lat'] . "\n";
        echo "  Lng: " . $data[0]['lon'] . "\n";
        echo "  Name: " . $data[0]['display_name'] . "\n";
    } else {
        echo "❌ No geocoding results found\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
