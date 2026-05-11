<?php

require __DIR__.'/vendor/autoload.php';

$dbUrl = "mysql:host=127.0.0.1;port=3306;dbname=agri_go_db;charset=utf8mb4";
$user = "root";
$password = "";

try {
    $pdo = new PDO($dbUrl, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $queries = [
        "ALTER TABLE vente ADD prix DOUBLE DEFAULT NULL",
        "ALTER TABLE vente ADD date_vente DATE DEFAULT NULL",
        "ALTER TABLE vente ADD id_user INT DEFAULT NULL"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "Successfully executed: $query\n";
        } catch (PDOException $e) {
            echo "Skipped or failed: $query - " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
