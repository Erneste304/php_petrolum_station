-- ============================================================
-- Workflow Enhancements Setup
-- ============================================================

-- Problem Reports and Service Requests
CREATE TABLE IF NOT EXISTS `problem_reports` (
    `report_id` INT AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT NOT NULL, -- Customer or Partner (user_id)
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `is_critical` TINYINT(1) DEFAULT 0, -- "Big problems"
    `status` ENUM('Pending', 'Staff Reviewing', 'Staff Handled', 'Awaiting Admin Approval', 'Resolved', 'Rejected') DEFAULT 'Pending',
    `staff_id` INT,
    `admin_id` INT,
    `admin_license_approved` TINYINT(1) DEFAULT 0, -- Admin approved license/form
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`staff_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update share_config to include available shares count
ALTER TABLE `share_config` ADD COLUMN `total_available_shares` DECIMAL(15, 4) DEFAULT 10000.0000 AFTER `current_price`;

-- (Updating share_requests status if needed, but the current schema already has enough placeholders)
-- Pending -> Staff Approved -> Accountant Verified (Paid) -> Completed (by Admin)
