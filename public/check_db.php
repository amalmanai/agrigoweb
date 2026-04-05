<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=agri_go_db_corrected;charset=utf8mb4', 'root', '');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schema = [];
foreach ($tables as $table) {
    if ($table === 'messenger_messages' || $table === 'doctrine_migration_versions') continue;
    $schema[$table] = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
}
file_put_contents(__DIR__ . '/db.json', json_encode($schema, JSON_PRETTY_PRINT));
