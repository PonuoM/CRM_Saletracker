-- Add new customer status: existing_3m (ลูกค้าเก่า 3 เดือน)
-- Also backfill and add a helper view

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1) Extend ENUM for customers.customer_status
-- Keep ALL existing values and add 'existing_3m'
ALTER TABLE `customers`
  MODIFY `customer_status` ENUM('new','existing','existing_3m','followup','call_followup','daily_distribution') DEFAULT 'new';

-- 2) Backfill current data
-- 2.1 Promote to existing_3m when:
--   - Assigned (we manage the customer)
--   - NOT in follow-up states
--   - Has a paid/partial order within the last 90 days
UPDATE customers c
JOIN orders o ON o.customer_id = c.customer_id
SET c.customer_status = 'existing_3m'
WHERE c.is_active = 1
  AND c.assigned_to IS NOT NULL
  AND c.customer_status NOT IN ('followup','call_followup')
  AND o.payment_status IN ('paid','partial')
  AND o.created_by = c.assigned_to
  AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
  AND o.order_id = (
      SELECT o2.order_id FROM orders o2
      WHERE o2.customer_id = c.customer_id
        AND o2.payment_status IN ('paid','partial')
      ORDER BY o2.order_date DESC, o2.order_id DESC
      LIMIT 1
  );

-- 2.2 Demote from existing_3m back to existing if no qualifying recent order
UPDATE customers c
LEFT JOIN orders o ON o.customer_id = c.customer_id
  AND o.payment_status IN ('paid','partial')
  AND o.order_id = (
      SELECT o2.order_id FROM orders o2
      WHERE o2.customer_id = c.customer_id
        AND o2.payment_status IN ('paid','partial')
      ORDER BY o2.order_date DESC, o2.order_id DESC
      LIMIT 1
  )
SET c.customer_status = 'existing'
WHERE c.is_active = 1
  AND c.customer_status = 'existing_3m'
  AND (
      o.order_id IS NULL
      OR o.order_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
      OR o.created_by <> c.assigned_to
  );

-- 3) Helper view: customers with sales in last 90 days (existing_3m)
DROP VIEW IF EXISTS `customer_existing_3m_list`;
CREATE VIEW `customer_existing_3m_list` AS
SELECT 
  c.customer_id,
  c.customer_code,
  c.first_name,
  c.last_name,
  c.phone,
  c.email,
  c.address,
  c.district,
  c.province,
  c.postal_code,
  c.temperature_status,
  c.customer_grade,
  c.total_purchase_amount,
  c.assigned_to,
  c.basket_type,
  c.assigned_at,
  c.last_contact_at,
  c.next_followup_at,
  c.recall_at,
  c.source,
  c.notes,
  c.is_active,
  c.created_at,
  c.updated_at,
  c.customer_status
FROM customers c
WHERE c.is_active = 1
  AND c.assigned_to IS NOT NULL
  AND c.basket_type = 'assigned'
  AND c.customer_status = 'existing_3m';

COMMIT;
