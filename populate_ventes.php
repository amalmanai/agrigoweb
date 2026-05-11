<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=agri_go_db', 'root', '');

// Get all users
$stmt = $pdo->query('SELECT id_user FROM user');
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($users) > 0) {
    // Get all ventes
    $stmt = $pdo->query('SELECT id_vente FROM vente');
    $ventes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($ventes as $index => $id_vente) {
        $id_user = $users[$index % count($users)];
        $prix = rand(10, 150) + (rand(0, 99) / 100);
        $date_vente = date('Y-m-d', strtotime('-' . rand(0, 30) . ' days'));
        
        $pdo->exec("UPDATE vente SET id_user = $id_user, prix = $prix, date_vente = '$date_vente' WHERE id_vente = $id_vente");
    }
    echo "Updated " . count($ventes) . " ventes with random users and prices.\n";
} else {
    echo "No users found.\n";
}
