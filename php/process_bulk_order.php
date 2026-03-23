<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fuel_id'])) {
    $customer_id = $_SESSION['user_id'];
    $fuel_id = $_POST['fuel_id'];
    $quantity = $_POST['quantity'];
    
    // In a real system, fetch price from DB. Here we use a simplification.
    $price_map = [1 => 4500, 10 => 1800, 5 => 1600, 8 => 12000];
    $unit_price = $price_map[$fuel_id] ?? 0;
    $total_cost = $unit_price * $quantity;

    try {
        $stmt = $pdo->prepare("INSERT INTO bulk_fuel_order (customer_id, fuel_id, quantity_liters, total_estimated_cost, status, delivery_date) VALUES (?, ?, ?, ?, 'Pending', DATE_ADD(NOW(), INTERVAL 7 DAY))");
        $stmt->execute([$customer_id, $fuel_id, $quantity, $total_cost]);
        
        $_SESSION['success'] = "Bulk acquisition request submitted successfully. Awaiting Admin certification.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error placing order: " . $e->getMessage();
    }
}
header("Location: bulk_products.php");
exit();
