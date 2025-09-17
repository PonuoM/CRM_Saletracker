-- Migration: Add company_id to various tables and backfill
-- Server target: MariaDB 10.6+
-- Notes:
-- - Uses IF NOT EXISTS for columns/indexes where supported
-- - Foreign key additions are plain (run once). Backup before executing.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1) activity_logs: add company_id, index, FK, backfill from users
ALTER TABLE `activity_logs`
  ADD COLUMN IF NOT EXISTS `company_id` INT NULL AFTER `user_id`;

ALTER TABLE `activity_logs`
  ADD INDEX IF NOT EXISTS `idx_activity_logs_company_id` (`company_id`);

-- Backfill from users
UPDATE `activity_logs` al
LEFT JOIN `users` u ON al.user_id = u.user_id
SET al.company_id = u.company_id
WHERE al.company_id IS NULL AND u.company_id IS NOT NULL;

-- Add FK (run once only)
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_company_id`
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`company_id`) ON DELETE SET NULL;


-- 2) appointment_activities: add company_id, index, FK, backfill from appointments
ALTER TABLE `appointment_activities`
  ADD COLUMN IF NOT EXISTS `company_id` INT NULL AFTER `appointment_id`;

ALTER TABLE `appointment_activities`
  ADD INDEX IF NOT EXISTS `idx_appointment_activities_company_id` (`company_id`);

-- Backfill from appointments
UPDATE `appointment_activities` aa
LEFT JOIN `appointments` a ON aa.appointment_id = a.appointment_id
SET aa.company_id = a.company_id
WHERE aa.company_id IS NULL AND a.company_id IS NOT NULL;

-- Add FK
ALTER TABLE `appointment_activities`
  ADD CONSTRAINT `fk_appointment_activities_company_id`
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`company_id`) ON DELETE SET NULL;


-- 3) notifications: add company_id, index, FK, backfill from users
ALTER TABLE `notifications`
  ADD COLUMN IF NOT EXISTS `company_id` INT NULL AFTER `user_id`;

ALTER TABLE `notifications`
  ADD INDEX IF NOT EXISTS `idx_notifications_company_id` (`company_id`);

-- Backfill from users
UPDATE `notifications` n
LEFT JOIN `users` u ON n.user_id = u.user_id
SET n.company_id = u.company_id
WHERE n.company_id IS NULL AND u.company_id IS NOT NULL;

-- Add FK
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_company_id`
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`company_id`) ON DELETE SET NULL;


-- 4) customer_transfers: add company_id, index, FK, backfill from telesales users
ALTER TABLE `customer_transfers`
  ADD COLUMN IF NOT EXISTS `company_id` INT NULL AFTER `transfer_id`;

ALTER TABLE `customer_transfers`
  ADD INDEX IF NOT EXISTS `idx_customer_transfers_company_id` (`company_id`);

-- Backfill: prefer source_telesales company, fallback to target
UPDATE `customer_transfers` t
LEFT JOIN `users` us ON t.source_telesales_id = us.user_id
LEFT JOIN `users` ut ON t.target_telesales_id = ut.user_id
SET t.company_id = COALESCE(us.company_id, ut.company_id)
WHERE t.company_id IS NULL AND (us.company_id IS NOT NULL OR ut.company_id IS NOT NULL);

-- Add FK
ALTER TABLE `customer_transfers`
  ADD CONSTRAINT `fk_customer_transfers_company_id`
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`company_id`) ON DELETE SET NULL;


-- 5) customer_transfer_details: add company_id, index, FK, backfill from customers
ALTER TABLE `customer_transfer_details`
  ADD COLUMN IF NOT EXISTS `company_id` INT NULL AFTER `transfer_id`;

ALTER TABLE `customer_transfer_details`
  ADD INDEX IF NOT EXISTS `idx_customer_transfer_details_company_id` (`company_id`);

-- Backfill from customers
UPDATE `customer_transfer_details` d
LEFT JOIN `customers` c ON d.customer_id = c.customer_id
SET d.company_id = c.company_id
WHERE d.company_id IS NULL AND c.company_id IS NOT NULL;

-- Add FK
ALTER TABLE `customer_transfer_details`
  ADD CONSTRAINT `fk_customer_transfer_details_company_id`
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`company_id`) ON DELETE SET NULL;


COMMIT;

-- End of migration
