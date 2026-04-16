<?php
require 'config/database.php';

echo "--- ROLE PERMISSIONS ---" . PHP_EOL;
$stmt = $pdo->query("SELECT * FROM role_permission WHERE role_name = 'staff'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo PHP_EOL . "--- INTERNAL MESSAGES (CHAT) ---" . PHP_EOL;
$stmt = $pdo->query("SELECT * FROM users_internalmessage ORDER BY created_at DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
