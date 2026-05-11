<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=agri_go_db', 'root', '');
try {
    $pdo->exec('ALTER TABLE vente ADD delivery_location VARCHAR(255) DEFAULT NULL');
    echo "Added delivery_location\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
