<?php
require_once 'config/database.php';
$tables = ['loyalty_reward', 'loyalty_redemption'];
$output = "";
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $output .= "Table $table exists.\n";
    } catch (Exception $e) {
        $output .= "Table $table does NOT exist.\n";
    }
}
file_put_contents('tmp_loyalty_check.txt', $output);
?>
