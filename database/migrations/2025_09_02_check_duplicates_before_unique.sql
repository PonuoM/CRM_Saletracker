-- Check duplicates before enforcing unique constraints
-- Customers duplicates by (company_id, phone)
SELECT 
  company_id,
  phone,
  COUNT(*) AS dup_count,
  GROUP_CONCAT(customer_id ORDER BY customer_id) AS customer_ids
FROM customers
WHERE phone IS NOT NULL AND phone <> ''
GROUP BY company_id, phone
HAVING COUNT(*) > 1;

-- Products duplicates by (company_id, product_code)
SELECT 
  company_id,
  product_code,
  COUNT(*) AS dup_count,
  GROUP_CONCAT(product_id ORDER BY product_id) AS product_ids
FROM products
WHERE product_code IS NOT NULL AND product_code <> ''
GROUP BY company_id, product_code
HAVING COUNT(*) > 1;

-- Optional: show sample conflicting rows for customers
-- SELECT c.* FROM customers c
-- JOIN (
--   SELECT company_id, phone FROM customers
--   WHERE phone IS NOT NULL AND phone <> ''
--   GROUP BY company_id, phone HAVING COUNT(*) > 1
-- ) d ON d.company_id = c.company_id AND d.phone = c.phone
-- ORDER BY c.company_id, c.phone, c.customer_id;

-- Optional: show sample conflicting rows for products
-- SELECT p.* FROM products p
-- JOIN (
--   SELECT company_id, product_code FROM products
--   WHERE product_code IS NOT NULL AND product_code <> ''
--   GROUP BY company_id, product_code HAVING COUNT(*) > 1
-- ) d ON d.company_id = p.company_id AND d.product_code = p.product_code
-- ORDER BY p.company_id, p.product_code, p.product_id;

