<?php
/**
 * Simple test script - no Symfony dependencies
 * Tests if we can reach Nominatim API
 */

echo "=== Testing Nominatim Geocoding API ===\n\n";

// Test address geocoding
$address = "Tunis, Tunisia";  
$url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($address) . '&format=json&limit=1&timeout=10';

echo "Testing URL: $url\n\n";

// Try with stream context
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: AgriGoWeb/1.0 (+https://agrigoweb.local)',
        'timeout' => 10,
    ]
];

$context = stream_context_create($opts);
echo "Attempting to fetch...\n";

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to fetch from Nominatim\n";
    echo "Error: " . error_get_last()['message'] ?? "Unknown error" . "\n";
    
    // Try without SSL verification
    echo "\nTrying with SSL verification disabled...\n";
    $opts = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: AgriGoWeb/1.0 (+https://agrigoweb.local)',
            'timeout' => 10,
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        die("❌ Still failed to fetch\n");
    }
}

echo "✅ Got response\n\n";
$data = json_decode($response, true);

if (is_array($data) && count($data) > 0) {
    echo "✅ Geocoding successful!\n\n";
    echo "Result:\n";
    echo "  Name: " . $data[0]['display_name'] . "\n";
    echo "  Latitude: " . $data[0]['lat'] . "\n";
    echo "  Longitude: " . $data[0]['lon'] . "\n";
} else {
    echo "❌ No results from Nominatim\n";
    echo "Response: " . $response . "\n";
}
?>
