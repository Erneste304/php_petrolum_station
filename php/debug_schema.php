<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE customer");
file_put_contents('tmp_schema.txt', print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
?>
