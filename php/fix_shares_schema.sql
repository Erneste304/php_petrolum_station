-- Fix for missing columns in Share Market system
ALTER TABLE `share_requests` ADD COLUMN `admin_id` INT AFTER `accountant_id`;
ALTER TABLE `share_requests` ADD CONSTRAINT `fk_share_requests_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`);

ALTER TABLE `share_config` ADD COLUMN `total_available_shares` DECIMAL(15, 4) NOT NULL DEFAULT 0.0000;
UPDATE `share_config` SET `total_available_shares` = 1000.0000 WHERE id = 1;
