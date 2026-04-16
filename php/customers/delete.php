<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';

// Only users with 'customers' permission can delete
requirePermission('customers');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Delete the customer
        $stmt = $pdo->prepare("DELETE FROM customer WHERE customer_id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Customer deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Cannot delete customer: " . $e->getMessage();
    }
}

header("Location: index.php");
exit();
?>
