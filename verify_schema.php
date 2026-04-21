<?php
$dsn = 'mysql:host=127.0.0.1;dbname=agri_go_db;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Final Database Verification ===\n\n";

// Check cultures table columns
echo "CULTURES table columns:\n";
$result = $pdo->query("DESCRIBE cultures");
$cols = $result->fetchAll();
$colNames = array_map(fn($r) => $r['Field'], $cols);
$requiredCols = ['id_culture', 'nom_culture', 'date_semis', 'date_recolte_estimee', 'rendement_estime', 'image_name', 'updated_at', 'owner_id'];
foreach ($requiredCols as $col) {
    echo ($in_array($col, $colNames) ? '✓' : '✗') . " $col\n";
}

echo "\nPRODUIT table columns:\n";
$result = $pdo->query("DESCRIBE produit");
$cols = $result->fetchAll();
foreach ($cols as $col) {
    echo "  " . $col['Field'] . " (" . $col['Type'] . ")" . ($col['Extra'] === 'auto_increment' ? " [AUTO_INCREMENT]" : "") . "\n";
}

echo "\n✓ Database schema verification complete!\n";
?>
