<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=agri_go_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $stmt = $pdo->prepare("SELECT id_user, nom_user, prenom_user, email_user, adresse_user, num_user FROM user WHERE LOWER(TRIM(role_user)) LIKE ? ORDER BY nom_user, prenom_user");
    $stmt->execute(['%']);
    echo "Count: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
