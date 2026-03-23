<?php
require_once 'config/database.php';
$tables = ['fuel_type', 'tank', 'pump', 'sale'];
$output = "";
foreach ($tables as $table) {
    $stmt = $pdo->query("DESCRIBE $table");
    $output .= "--- Table: $table ---\n";
    $output .= print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "\n";
}
file_put_contents('tmp_fuel_schema.txt', $output);
?>
