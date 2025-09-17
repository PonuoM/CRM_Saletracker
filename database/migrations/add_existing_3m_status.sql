-- Migration: เพิ่มสถานะ "ลูกค้าเก่า 3 เดือน" (existing_3m)
-- วันที่: 2025-01-10
-- คำอธิบาย: เพิ่มสถานะใหม่สำหรับลูกค้าที่มีการขายในช่วง 3 เดือนที่ผ่านมา

-- 1. เพิ่มสถานะใหม่ใน customer_status ENUM
ALTER TABLE customers 
MODIFY COLUMN customer_status ENUM('new','existing','existing_3m','followup','call_followup','daily_distribution') DEFAULT 'new';

-- 2. เพิ่ม index สำหรับการค้นหาที่เร็วขึ้น
CREATE INDEX idx_customer_status_3m ON customers(customer_status, total_purchase_amount, updated_at);

-- 3. เพิ่ม index สำหรับการค้นหาการขายในช่วง 90 วัน
CREATE INDEX idx_orders_recent_sales ON orders(customer_id, order_date, is_active);

-- 4. สร้าง view สำหรับการแสดงผลลูกค้าเก่า 3 เดือน
CREATE OR REPLACE VIEW customer_3months_view AS
SELECT 
    c.*,
    CASE 
        WHEN c.customer_status = 'new' THEN 'ลูกค้าใหม่'
        WHEN c.customer_status = 'existing' THEN 'ลูกค้าเก่า'
        WHEN c.customer_status = 'existing_3m' THEN 'ลูกค้าเก่า 3 เดือน'
        WHEN c.customer_status = 'followup' THEN 'ติดตาม'
        WHEN c.customer_status = 'call_followup' THEN 'ติดตามโทร'
        WHEN c.customer_status = 'daily_distribution' THEN 'แจกรายวัน'
        ELSE 'ไม่ทราบสถานะ'
    END AS status_text,
    CASE 
        WHEN c.customer_status = 'existing_3m' THEN 1
        ELSE 0
    END AS is_3months_customer,
    COALESCE(recent_sales.total_amount, 0) as recent_3months_sales
FROM customers c
LEFT JOIN (
    SELECT 
        customer_id,
        SUM(total_amount) as total_amount
    FROM orders 
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    AND is_active = 1
    GROUP BY customer_id
) recent_sales ON c.customer_id = recent_sales.customer_id
WHERE c.is_active = 1;

-- 5. สร้าง view สำหรับการแสดงผลลูกค้าเก่าทั้งหมด (รวม existing และ existing_3m)
CREATE OR REPLACE VIEW customer_existing_list AS
SELECT 
    c.*,
    CASE 
        WHEN c.customer_status = 'existing' THEN 'ลูกค้าเก่า'
        WHEN c.customer_status = 'existing_3m' THEN 'ลูกค้าเก่า 3 เดือน'
        ELSE c.customer_status
    END AS status_text
FROM customers c
WHERE c.is_active = 1 
AND c.customer_status IN ('existing', 'existing_3m');

-- 6. อัพเดท customer_do_list view เพื่อรองรับสถานะใหม่
CREATE OR REPLACE VIEW customer_do_list AS
SELECT 
    c.*,
    DATEDIFF(c.customer_time_expiry, CURRENT_TIMESTAMP) AS days_remaining,
    CASE 
        WHEN c.customer_status = 'new' THEN 'ลูกค้าใหม่'
        WHEN c.customer_status = 'existing' THEN 'ลูกค้าเก่า'
        WHEN c.customer_status = 'existing_3m' THEN 'ลูกค้าเก่า 3 เดือน'
        WHEN c.customer_status = 'followup' THEN 'ติดตาม'
        WHEN c.customer_status = 'call_followup' THEN 'ติดตามโทร'
        WHEN c.customer_status = 'daily_distribution' THEN 'แจกรายวัน'
        ELSE 'ไม่ทราบสถานะ'
    END AS status_text,
    CASE 
        WHEN c.customer_time_expiry <= CURRENT_TIMESTAMP THEN 'เกินกำหนด'
        WHEN c.customer_time_expiry <= CURRENT_TIMESTAMP + INTERVAL 7 DAY THEN 'ใกล้หมดเวลา'
        ELSE 'ปกติ'
    END AS urgency_status
FROM customers c
WHERE c.assigned_to IS NOT NULL 
    AND c.basket_type = 'assigned' 
    AND c.is_active = 1
    AND (c.customer_time_expiry <= CURRENT_TIMESTAMP + INTERVAL 7 DAY 
         OR c.next_followup_at <= CURRENT_TIMESTAMP);
