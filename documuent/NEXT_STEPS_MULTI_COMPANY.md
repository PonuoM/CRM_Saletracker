# Next Steps — Multi‑Company Completion Plan

This document lists all remaining actions to fully complete and validate the multi‑company migration (switch from `source` to `company_id`) across database, backend, and UI, plus a safe run‑order for migrations and checks.

## Run Order (DB)

- Backup database (full dump) before changes.
- 1) Merge duplicates (if applicable): `database/migrations/2025_09_02_merge_duplicate_companies.sql`
  - Adjust mapping IDs if they differ in production (canonical vs. duplicate)
  - Confirms: only 1 row per company remains; `companies.company_code` is unique
- 2) Enforce rules: `database/migrations/2025_09_02_enforce_company_rules.sql`
  - Adds unique keys: `customers(company_id, phone)`, `products(company_id, product_code)`
  - Adds triggers: set `orders/appointments/call_logs.company_id` from customer; prevent cross‑company `assigned_to`
  - Backfills `products.company_id` to `2` (temporary default for pre‑existing products without company)
- 3) Backfill logs: `database/migrations/2025_09_02_backfill_activity_logs_company_id.sql`
  - Populates `activity_logs.company_id` from referenced entities
- 4) Seed company_admin role: `database/migrations/2025_09_02_add_company_admin_role.sql`
  - Assign this role to users who must act as company admins

## Data Hygiene (pre‑Unique)

- Customers: find duplicates with same `(company_id, phone)` and fix
- Products: find duplicates with same `(company_id, product_code)` and fix
- If unique key creation fails, clear duplicates first, then re‑run migration 02_enforce.

## Backend — Must Finish

- CustomerDistributionService (complete the conversion to `company_id`)
  - Replace residual `source` filters and variables with `company_id`.
  - Known lines to verify and update (based on scan):
    - `app/services/CustomerDistributionService.php:275, 308, 317, 364, 531, 545, 894, 904, 965`
  - Ensure all SQL add `AND c.company_id = ?` or `AND company_id = ?` as appropriate
  - Ensure parameters use the current `company_id` from `CompanyContext::getCompanyId()`

- AdminController::customer_distribution
  - Add handling for `company_override_id` (super_admin/company_admin) to set `$_SESSION['override_company_id']`
  - File: `app/controllers/AdminController.php:1080+`

- Search (secondary pages)
  - Verify `search_improved.php`, `search_fixed.php`, and `SearchController` paths use `company_id` scoping consistently
  - Code: `app/services/SearchService.php` already converted; audit controllers/views still referencing `source`

- Reports & Dashboard scoping
  - File: `reports.php` — add `company_id` filter for non‑super_admin users and a company dropdown for super_admin
  - Dashboard pages (telesales/supervisor): ensure queries include `company_id` filters

## UI — Must Finish

- Customer Distribution UI (dynamic companies)
  - File: `app/views/admin/customer_distribution_new.php`
  - Replace hardcoded 2 companies (Prima/Prionic) with dynamic `<select>` of companies (active)
  - On change, submit `company_override_id` to controller; reflect stats, telesales list, and counts by company

- Products pages (scope by company)
  - Already updated in controller: list/get/update/delete restricted by `company_id` for non‑super_admin
  - Confirm views do not expose other companies’ products

## Role & Permissions

- Role `company_admin` (same UX/UI as `admin` but scoped to own company)
  - Seed file added; map target users to this role
  - Confirm `checkAdminPermission()` accepts `company_admin`

## Acceptance Tests (after hours)

- Customers
  - Create same phone in same company → fails (unique)
  - Create same phone in different company → succeeds
  - Assign customer (company A) to telesales (company B) → blocked by trigger

- Orders/Appointments/Call logs
  - Create for a customer in company A → `orders/appointments/call_logs.company_id` auto‑set to A

- Products
  - Non‑super_admin sees only own company products; CRUD writes `company_id`
  - Duplicate product_code within same company → fails; different company → OK

- Search
  - Results show only customers of current company; customer_details and order_details restricted to company

- Distribution
  - With dropdown, switching company changes stats/telesales list/customers

- Reports
  - Non‑super_admin sees only own company data; super_admin can switch company

## Operational Notes

- Triggers rely on MariaDB features (DECLARE at block top, SIGNAL). Verified syntax in the updated migration.
- Consider adding indexes on `company_id` columns if missing on heavy tables for performance (`customers`, `orders`, `call_logs`, `appointments`, `products`).
- For importers, ensure session/company override is set correctly (`company_override_id`) so `company_id` is attached to new data.

## Known Potential Gaps

- Some deep methods in CustomerDistributionService still might refer to `$source`/`$companySource` variables; convert to `$companyId`.
- AdminController::customer_distribution currently not patched with the override handler in some environments; add small block to read `company_override_id`.
- search_improved/search_fixed pages may still display legacy “source” labels; switch to `company_name` from session if needed.

## Rollback Plan (DB)

- If new triggers cause unexpected rejects, drop them selectively, fix data, and re‑create.
- If unique constraints fail due to duplicates, remove duplicates and re‑apply the unique indexes.

## File References

- Migrations
  - `database/migrations/2025_09_02_merge_duplicate_companies.sql`
  - `database/migrations/2025_09_02_enforce_company_rules.sql`
  - `database/migrations/2025_09_02_backfill_activity_logs_company_id.sql`
  - `database/migrations/2025_09_02_add_company_admin_role.sql`
- Backend
  - `app/services/CompanyContext.php`
  - `app/services/ImportExportService.php`
  - `app/services/OrderService.php`
  - `app/services/AppointmentService.php`
  - `app/services/CronJobService.php`
  - `app/services/CustomerDistributionService.php`
  - `app/services/SearchService.php`
  - `app/controllers/AdminController.php:1080` (distribution page hook)
  - `search.php` (converted; verify details queries)
- UI
  - `app/views/admin/customer_distribution_new.php` (convert to dynamic company dropdown)

---

If you want, I can implement the remaining changes (distribution UI and full service conversion, report scoping, role seeding) and provide a one‑click verification script for after‑hours testing.

