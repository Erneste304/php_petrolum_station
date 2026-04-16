<?php
require 'config/database.php';
$stmt = $pdo->query('DESCRIBE users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
