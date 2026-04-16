<?php
require 'config/database.php';

echo "--- SHARE REQUEST STATUSES ---" . PHP_EOL;
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM share_requests GROUP BY status");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo PHP_EOL . "--- PAYROLL SUBMISSION STATUSES ---" . PHP_EOL;
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM payroll_submissions GROUP BY status");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
