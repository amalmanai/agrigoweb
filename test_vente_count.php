<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=agri_go_db', 'root', '');
$stmt = $pdo->query('SELECT COUNT(*) FROM vente');
echo "Ventes count: " . $stmt->fetchColumn() . "\n";
