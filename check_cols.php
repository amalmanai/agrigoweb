<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=agri_go_db", "root", "");
foreach (['alertes_risques', 'cultures', 'historique_irrigation'] as $table) {
    echo "--- $table ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }
}
