<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=agri_go_db', 'root', '');
$stmt = $pdo->query('SELECT id_user, nom_user, role_user FROM user');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
if ($stmt->rowCount() == 0) {
    echo "No users found.\n";
}
