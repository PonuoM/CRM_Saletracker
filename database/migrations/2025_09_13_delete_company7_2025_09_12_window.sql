-- Cleanup import window for company 7 between 2025-09-12 18:00:00 and 21:00:00
-- Deletes related rows in correct FK order: order_items -> orders -> child-of-customers -> customers

START TRANSACTION;

SET @start   := '2025-09-12 18:00:00';
SET @end     := '2025-09-12 21:00:00';
SET @company := 7;

-- Customers created in the window
DROP TEMPORARY TABLE IF EXISTS tmp_c_ids;
CREATE TEMPORARY TABLE tmp_c_ids
SELECT c.customer_id
FROM customers c
WHERE c.company_id = @company
  AND c.created_at BETWEEN @start AND @end;

-- Orders to delete: orders in the window + all orders belonging to customers created in the window
DROP TEMPORARY TABLE IF EXISTS tmp_o_ids;
CREATE TEMPORARY TABLE tmp_o_ids
SELECT o.order_id
FROM orders o
WHERE o.company_id = @company
  AND o.created_at BETWEEN @start AND @end
UNION
SELECT o.order_id
FROM orders o
JOIN tmp_c_ids t ON t.customer_id = o.customer_id;

-- 1) order_items
DELETE oi
FROM order_items oi
JOIN tmp_o_ids t ON t.order_id = oi.order_id;

-- 2) orders
DELETE o
FROM orders o
JOIN tmp_o_ids t ON t.order_id = o.order_id;

-- 3) optional child tables of customers (safe to skip if not exist)
-- follow-up queue
DELETE cfq
FROM call_followup_queue cfq
JOIN tmp_c_ids t ON t.customer_id = cfq.customer_id;

-- call logs
DELETE cl
FROM call_logs cl
JOIN tmp_c_ids t ON t.customer_id = cl.customer_id;

-- appointment activities then appointments
DELETE aa
FROM appointment_activities aa
JOIN appointments ap ON ap.appointment_id = aa.appointment_id
JOIN tmp_c_ids t ON t.customer_id = ap.customer_id;

DELETE ap
FROM appointments ap
JOIN tmp_c_ids t ON t.customer_id = ap.customer_id;

-- customer activities
DELETE ca
FROM customer_activities ca
JOIN tmp_c_ids t ON t.customer_id = ca.customer_id;

-- activity logs that explicitly point to customers (if schema used)
DELETE al
FROM activity_logs al
JOIN tmp_c_ids t ON al.table_name = 'customers' AND al.record_id = t.customer_id;

-- 4) customers themselves
DELETE c
FROM customers c
JOIN tmp_c_ids t ON t.customer_id = c.customer_id;

COMMIT;

