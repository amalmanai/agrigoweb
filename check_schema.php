<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'agri_go_db');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}
$result = $mysqli->query('DESC recolte');
if ($result) {
    echo "Columns in recolte table:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo 'Error: ' . $mysqli->error . "\n";
}
$mysqli->close();
?>
