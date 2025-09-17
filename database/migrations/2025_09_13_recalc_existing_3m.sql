-- Recalculate existing_3m customer status with correct precedence/logic
-- Rules:
-- - existing_3m only if:
--   * assigned_to IS NOT NULL
--   * latest paid/partial order within 90 days exists
--   * latest order created_by = assigned_to
--   * current status NOT IN ('followup','call_followup','daily_distribution')
-- - Demote to existing if any condition fails

START TRANSACTION;

-- Promote
UPDATE customers c
JOIN (
  SELECT o.customer_id, o.created_by, o.order_date
  FROM orders o
  WHERE o.payment_status IN ('paid','partial')
  AND (o.order_id, o.order_date) IN (
      SELECT o2.order_id, o2.order_date
      FROM orders o2
      WHERE o2.payment_status IN ('paid','partial')
      ORDER BY o2.order_date DESC
  )
) last_paid ON last_paid.customer_id = c.customer_id
SET c.customer_status = 'existing_3m', c.updated_at = NOW()
WHERE c.is_active = 1
  AND c.assigned_to IS NOT NULL
  AND c.customer_status NOT IN ('followup','call_followup','daily_distribution')
  AND last_paid.order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
  AND last_paid.created_by = c.assigned_to;

-- Demote
UPDATE customers c
LEFT JOIN (
  SELECT o.customer_id, o.created_by, o.order_date
  FROM orders o
  WHERE o.payment_status IN ('paid','partial')
  ORDER BY o.order_date DESC, o.order_id DESC
) last_paid ON last_paid.customer_id = c.customer_id
SET c.customer_status = 'existing'
WHERE c.is_active = 1
  AND c.customer_status = 'existing_3m'
  AND (
      c.assigned_to IS NULL
      OR last_paid.order_date IS NULL
      OR last_paid.order_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
      OR last_paid.created_by <> c.assigned_to
  );

COMMIT;

