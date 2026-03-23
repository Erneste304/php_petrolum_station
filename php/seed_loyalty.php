<?php
require_once 'config/database.php';

// Seed rewards if empty
$stmt = $pdo->query("SELECT COUNT(*) FROM loyalty_reward");
if ($stmt->fetchColumn() == 0) {
    $rewards = [
        ['Basic Car Wash', 'One free basic exterior car wash.', 300],
        ['Free 5 Liters of Fuel', 'Redeem points for 5 free liters of any fuel type.', 500],
        ['10% Off Next Servicing', 'Get a 10% discount on your next full car detailing.', 800]
    ];
    $stmt = $pdo->prepare("INSERT INTO loyalty_reward (name, description, points_cost) VALUES (?, ?, ?)");
    foreach ($rewards as $r) {
        $stmt->execute($r);
    }
    echo "Rewards seeded.";
} else {
    echo "Rewards already exist.";
}
?>
