-- Enforce company scoping rules and backfill
-- 1) Backfill products.company_id (set to 2 where NULL)
-- 2) Add unique constraints per company
-- 3) Add triggers to enforce cross-company consistency

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1) Backfill products without company
UPDATE `products` SET company_id = 2 WHERE company_id IS NULL;

-- 2) Unique constraints per company
-- Customers: phone unique within company (NULLs allowed across rows by MySQL)
ALTER TABLE `customers` ADD UNIQUE KEY `uq_customers_company_phone` (`company_id`, `phone`);

-- Products: product_code unique within company
ALTER TABLE `products` ADD UNIQUE KEY `uq_products_company_code` (`company_id`, `product_code`);

-- 3) Triggers
DELIMITER $$

-- Prevent assigning customer to a user from a different company
DROP TRIGGER IF EXISTS `bi_customers_enforce_company`$$
CREATE TRIGGER `bi_customers_enforce_company` BEFORE INSERT ON `customers`
FOR EACH ROW
BEGIN
    DECLARE u_company INT DEFAULT NULL;
    IF NEW.assigned_to IS NOT NULL THEN
        SELECT company_id INTO u_company FROM users WHERE user_id = NEW.assigned_to LIMIT 1;
        IF u_company IS NOT NULL AND NEW.company_id IS NOT NULL AND u_company <> NEW.company_id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'assigned_to user must belong to the same company';
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS `bu_customers_enforce_company`$$
CREATE TRIGGER `bu_customers_enforce_company` BEFORE UPDATE ON `customers`
FOR EACH ROW
BEGIN
    DECLARE u_company2 INT DEFAULT NULL;
    IF NEW.assigned_to IS NOT NULL THEN
        SELECT company_id INTO u_company2 FROM users WHERE user_id = NEW.assigned_to LIMIT 1;
        IF u_company2 IS NOT NULL AND NEW.company_id IS NOT NULL AND u_company2 <> NEW.company_id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'assigned_to user must belong to the same company';
        END IF;
    END IF;
END$$

-- Ensure orders.company_id matches customer
DROP TRIGGER IF EXISTS `bi_orders_set_company`$$
CREATE TRIGGER `bi_orders_set_company` BEFORE INSERT ON `orders`
FOR EACH ROW
BEGIN
    DECLARE c_company INT DEFAULT NULL;
    IF NEW.customer_id IS NOT NULL THEN
        SELECT company_id INTO c_company FROM customers WHERE customer_id = NEW.customer_id LIMIT 1;
        SET NEW.company_id = c_company;
    END IF;
END$$

-- Ensure appointments.company_id matches customer
DROP TRIGGER IF EXISTS `bi_appointments_set_company`$$
CREATE TRIGGER `bi_appointments_set_company` BEFORE INSERT ON `appointments`
FOR EACH ROW
BEGIN
    DECLARE c_company2 INT DEFAULT NULL;
    IF NEW.customer_id IS NOT NULL THEN
        SELECT company_id INTO c_company2 FROM customers WHERE customer_id = NEW.customer_id LIMIT 1;
        SET NEW.company_id = c_company2;
    END IF;
END$$

-- Optionally ensure call_logs.company_id matches customer
DROP TRIGGER IF EXISTS `bi_call_logs_set_company`$$
CREATE TRIGGER `bi_call_logs_set_company` BEFORE INSERT ON `call_logs`
FOR EACH ROW
BEGIN
    DECLARE c_company3 INT DEFAULT NULL;
    IF NEW.customer_id IS NOT NULL THEN
        SELECT company_id INTO c_company3 FROM customers WHERE customer_id = NEW.customer_id LIMIT 1;
        SET NEW.company_id = c_company3;
    END IF;
END$$

DELIMITER ;

COMMIT;

-- End rules
