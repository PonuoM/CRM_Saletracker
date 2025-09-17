-- View to support "Do" page categories and priority ordering
-- Categories:
--  - followup_2d: next_followup_at within next 2 days (overdue included)
--  - expiry_5d: customer_time_expiry within next 5 days
--  - daily_distribution: daily distribution/newly distributed list
--  - other
-- Priority: followup_2d (1) > expiry_5d (2) > daily_distribution (3) > other (9)

DROP VIEW IF EXISTS customer_do_view;
CREATE VIEW customer_do_view AS
SELECT 
  c.*, 
  CASE 
    WHEN c.next_followup_at IS NOT NULL AND c.next_followup_at <= DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 'followup_2d'
    WHEN c.customer_time_expiry IS NOT NULL AND c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 5 DAY) THEN 'expiry_5d'
    WHEN c.customer_status = 'daily_distribution' OR c.basket_type = 'distribution' THEN 'daily_distribution'
    ELSE 'other'
  END AS do_category,
  CASE 
    WHEN c.next_followup_at IS NOT NULL AND c.next_followup_at <= DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1
    WHEN c.customer_time_expiry IS NOT NULL AND c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 5 DAY) THEN 2
    WHEN c.customer_status = 'daily_distribution' OR c.basket_type = 'distribution' THEN 3
    ELSE 9
  END AS do_priority,
  GREATEST(
    COALESCE(c.next_followup_at, '1970-01-01'),
    COALESCE(c.updated_at, '1970-01-01'),
    COALESCE(c.created_at, '1970-01-01')
  ) AS do_order_date
FROM customers c
WHERE c.is_active = 1;

-- Suggested SELECT ordering for the Do page:
-- SELECT * FROM customer_do_view 
-- WHERE company_id = ? AND assigned_to = ?
-- ORDER BY do_priority ASC, do_order_date DESC;

