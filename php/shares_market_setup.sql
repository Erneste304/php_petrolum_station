-- ============================================================
-- Share Market System Setup
-- Run this SQL on petroleum_station_db
-- ============================================================

-- Global configuration for shares (Admin managed)
CREATE TABLE IF NOT EXISTS `share_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `current_price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial default price
INSERT INTO `share_config` (`current_price`) VALUES (1000.00);

-- Partner share ownership
CREATE TABLE IF NOT EXISTS `partner_shares` (
    `partner_id` INT PRIMARY KEY,
    `total_shares` DECIMAL(15, 4) NOT NULL DEFAULT 0.0000,
    `last_balance_update` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`partner_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Share requests (Buy/Sell)
CREATE TABLE IF NOT EXISTS `share_requests` (
    `request_id` INT AUTO_INCREMENT PRIMARY KEY,
    `partner_id` INT NOT NULL,
    `request_type` ENUM('buy', 'sell') NOT NULL,
    `number_of_shares` DECIMAL(15, 4) NOT NULL,
    `price_at_request` DECIMAL(15, 2) NOT NULL,
    `total_amount` DECIMAL(15, 2) NOT NULL,
    `commission_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `status` ENUM('Pending', 'Staff Approved', 'Accountant Verified', 'Completed', 'Rejected') DEFAULT 'Pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `staff_id` INT,
    `accountant_id` INT,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`partner_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`staff_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`accountant_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
