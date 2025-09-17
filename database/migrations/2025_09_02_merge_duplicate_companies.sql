-- Merge duplicate company rows by remapping references, then delete duplicates
-- Current situation:
--   Canonical: PRIMA49 => company_id = 1,  PRIONIC(A02) => company_id = 2
--   Duplicates to merge: PRIMA => 3 -> 1,  PRIONIC => 4 -> 2
-- Adjust mapping below if your IDs differ.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- Mapping
SET @PRIMA_CANON = 1;   -- keep
SET @PRIMA_DUP   = 3;   -- delete after remap
SET @PRIONIC_CANON = 2; -- keep
SET @PRIONIC_DUP   = 4; -- delete after remap

-- 1) Users
UPDATE `users` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `users` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 2) Customers
UPDATE `customers` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `customers` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 3) Orders
UPDATE `orders` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `orders` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 4) Products
UPDATE `products` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `products` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 5) Appointments
UPDATE `appointments` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `appointments` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 6) Call logs
UPDATE `call_logs` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `call_logs` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 7) Activity logs
UPDATE `activity_logs` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `activity_logs` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 8) Appointment activities
UPDATE `appointment_activities` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `appointment_activities` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 9) Notifications
UPDATE `notifications` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `notifications` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- 10) Customer transfers (header + details)
UPDATE `customer_transfers` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `customer_transfers` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

UPDATE `customer_transfer_details` SET company_id = @PRIMA_CANON   WHERE company_id = @PRIMA_DUP;
UPDATE `customer_transfer_details` SET company_id = @PRIONIC_CANON WHERE company_id = @PRIONIC_DUP;

-- Optionally normalize companies table display names/codes for canonical rows
-- UPDATE `companies` SET company_name = 'PRIMA',   company_code = 'PRIMA49' WHERE company_id = @PRIMA_CANON;
-- UPDATE `companies` SET company_name = 'PRIONIC', company_code = 'A02'     WHERE company_id = @PRIONIC_CANON;

-- Delete duplicate company rows (after all remaps)
DELETE FROM `companies` WHERE company_id IN (@PRIMA_DUP, @PRIONIC_DUP);

-- Hardening: prevent future duplicates on company_code
ALTER TABLE `companies` ADD UNIQUE KEY `uq_companies_company_code` (`company_code`);

COMMIT;

-- Rollback guideline (manual):
-- If needed, re-insert deleted rows and remap back, but recommended to backup before running.
