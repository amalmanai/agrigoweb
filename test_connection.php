<?php
$host = 'smtp.gmail.com';
$port = 465;

echo "Testing SMTPS connection to $host:$port\n";

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

// Try with ssl:// for port 465
$fp = stream_socket_client("ssl://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
if (!$fp) {
    echo "SSL Error: $errstr ($errno)\n";
} else {
    echo "SSL Connected successfully\n";
    fclose($fp);
}
?>