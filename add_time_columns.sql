-- เพิ่มคอลัมน์ที่จำเป็นสำหรับการต่อเวลาอัตโนมัติ
-- รันไฟล์นี้ใน phpMyAdmin หรือ MySQL client

USE crm_development;

-- 1. เพิ่มคอลัมน์ customer_time_expiry
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS customer_time_expiry TIMESTAMP NULL DEFAULT NULL 
COMMENT 'วันหมดอายุการดูแลลูกค้า';

-- 2. เพิ่มคอลัมน์ customer_time_extension
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS customer_time_extension INT DEFAULT 0 
COMMENT 'จำนวนวันที่ต่อเวลาล่าสุด';

-- 3. เพิ่มคอลัมน์ customer_time_base
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS customer_time_base TIMESTAMP NULL DEFAULT NULL 
COMMENT 'วันที่เริ่มต้นการดูแลลูกค้า';

-- 4. เพิ่มคอลัมน์ customer_status
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS customer_status ENUM('new', 'existing') DEFAULT 'new' 
COMMENT 'สถานะลูกค้า (ใหม่/เก่า)';

-- 5. เพิ่ม Index สำหรับการค้นหาที่มีประสิทธิภาพ
ALTER TABLE customers 
ADD INDEX IF NOT EXISTS idx_customer_time_expiry (customer_time_expiry),
ADD INDEX IF NOT EXISTS idx_customer_status (customer_status),
ADD INDEX IF NOT EXISTS idx_assigned_at (assigned_at);

-- 6. อัปเดตข้อมูลลูกค้าที่มีอยู่
-- ตั้งค่า customer_time_expiry สำหรับลูกค้าที่มี assigned_at
UPDATE customers 
SET customer_time_expiry = DATE_ADD(assigned_at, INTERVAL 30 DAY),
    customer_time_base = assigned_at,
    customer_status = CASE 
        WHEN customer_id IN (SELECT DISTINCT customer_id FROM orders) THEN 'existing'
        ELSE 'new'
    END
WHERE assigned_at IS NOT NULL 
AND customer_time_expiry IS NULL;

-- 7. แสดงผลการอัปเดต
SELECT 
    'คอลัมน์ที่เพิ่มแล้ว' as status,
    COUNT(*) as total_customers,
    COUNT(CASE WHEN customer_time_expiry IS NOT NULL THEN 1 END) as customers_with_expiry,
    COUNT(CASE WHEN customer_status = 'new' THEN 1 END) as new_customers,
    COUNT(CASE WHEN customer_status = 'existing' THEN 1 END) as existing_customers
FROM customers; 