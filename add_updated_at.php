<?php

require __DIR__.'/vendor/autoload.php';

$dbUrl = "mysql:host=127.0.0.1;port=3306;dbname=agri_go_db;charset=utf8mb4";
$user = "root";
$password = "";

try {
    $pdo = new PDO($dbUrl, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists, if not add it
    $stmt = $pdo->query("SHOW COLUMNS FROM user LIKE 'updated_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        echo "Column 'updated_at' added successfully.\n";
    } else {
        echo "Column 'updated_at' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
