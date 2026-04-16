<?php
require 'config/database.php';
$stmt = $pdo->query('SHOW TABLES');
foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
    echo $t . PHP_EOL;
}
