-- Seed role: company_admin (view/manage like admin but scoped to own company)
START TRANSACTION;

INSERT INTO roles (role_name, permissions)
SELECT 'company_admin', JSON_ARRAY('manage_users','manage_customers','manage_products','manage_orders','view_reports')
WHERE NOT EXISTS (
  SELECT 1 FROM roles WHERE role_name = 'company_admin'
);

COMMIT;

