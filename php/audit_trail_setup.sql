-- ============================================================
-- Audit Trail for Transaction Changes
-- Run this SQL on petroleum_station_db
-- ============================================================

CREATE TABLE IF NOT EXISTS `transaction_audit` (
  `audit_id`          INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_type`  VARCHAR(50)    NOT NULL COMMENT 'e.g. sale, fuel_type, tank, pump',
  `record_id`         INT            NOT NULL COMMENT 'The PK of the changed record',
  `changed_by`        INT            NOT NULL COMMENT 'user_id of the person who made the change',
  `changed_by_role`   VARCHAR(30)    NOT NULL COMMENT 'Role of the changer at time of change',
  `previous_data`     JSON           NOT NULL COMMENT 'Snapshot before update',
  `updated_data`      JSON           NOT NULL COMMENT 'Snapshot after update',
  `change_date`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_type_record` (`transaction_type`, `record_id`),
  INDEX `idx_changed_by`  (`changed_by`),
  INDEX `idx_change_date` (`change_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores before/after snapshots for every transaction change';
