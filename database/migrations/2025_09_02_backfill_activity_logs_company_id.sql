-- Backfill company_id for activity_logs from related entities
-- This script populates activity_logs.company_id where possible by joining
-- to the referenced tables based on (table_name, record_id). It also includes
-- a final fallback via users.user_id.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1) From customers (most direct)
UPDATE `activity_logs` al
JOIN `customers` c
  ON al.table_name = 'customers' AND al.record_id = c.customer_id
SET al.company_id = c.company_id
WHERE al.company_id IS NULL AND c.company_id IS NOT NULL;

-- 2) From orders
UPDATE `activity_logs` al
JOIN `orders` o
  ON al.table_name = 'orders' AND al.record_id = o.order_id
SET al.company_id = o.company_id
WHERE al.company_id IS NULL AND o.company_id IS NOT NULL;

-- 3) From call_logs
UPDATE `activity_logs` al
JOIN `call_logs` cl
  ON al.table_name = 'call_logs' AND al.record_id = cl.log_id
SET al.company_id = cl.company_id
WHERE al.company_id IS NULL AND cl.company_id IS NOT NULL;

-- 4) From products
UPDATE `activity_logs` al
JOIN `products` p
  ON al.table_name = 'products' AND al.record_id = p.product_id
SET al.company_id = p.company_id
WHERE al.company_id IS NULL AND p.company_id IS NOT NULL;

-- 5) From appointments
UPDATE `activity_logs` al
JOIN `appointments` a
  ON al.table_name = 'appointments' AND al.record_id = a.appointment_id
SET al.company_id = a.company_id
WHERE al.company_id IS NULL AND a.company_id IS NOT NULL;

-- 6) Fallback: from users.user_id (for logs that are not entity-bound)
UPDATE `activity_logs` al
JOIN `users` u ON al.user_id = u.user_id
SET al.company_id = u.company_id
WHERE al.company_id IS NULL AND al.user_id IS NOT NULL AND u.company_id IS NOT NULL;

COMMIT;

-- Notes:
-- - Logs without record_id (e.g., some aggregated cron logs) cannot be reliably
--   backfilled; consider updating cron to write one row per company with company_id,
--   or add a trigger to derive company_id when possible.
