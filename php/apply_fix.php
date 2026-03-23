<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE `share_requests` ADD COLUMN `admin_id` INT AFTER `accountant_id` ");
    $pdo->exec("ALTER TABLE `share_requests` ADD CONSTRAINT `fk_share_requests_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ");
    echo "Column admin_id added successfully.<br>";
} catch (Exception $e) {
    echo "Error adding admin_id (it might already exist): " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE `share_config` ADD COLUMN `total_available_shares` DECIMAL(15, 4) NOT NULL DEFAULT 0.0000 ");
    $pdo->exec("UPDATE `share_config` SET `total_available_shares` = 1000.0000 WHERE id = 1 ");
    echo "Column total_available_shares added successfully.<br>";
} catch (Exception $e) {
    echo "Error adding total_available_shares (it might already exist): " . $e->getMessage() . "<br>";
}

echo "Database fix completed.";
?>
