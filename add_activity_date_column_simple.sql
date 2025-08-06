-- Fix Customer Activities Schema - เพิ่มคอลัมน์ที่ขาดหายไปในตาราง customer_activities
-- 
-- ปัญหาที่พบ: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'activity_date' in 'field list'
-- 
-- วิธีแก้ไข: เพิ่มคอลัมน์ activity_date ในตาราง customer_activities
-- 
-- หมายเหตุ: ไฟล์นี้ไม่ใช้ information_schema เพื่อหลีกเลี่ยงปัญหา Access denied

-- เพิ่มคอลัมน์ activity_date
-- หากคอลัมน์มีอยู่แล้ว จะเกิด error "Duplicate column name" ซึ่งไม่เป็นปัญหา
ALTER TABLE customer_activities 
ADD COLUMN activity_date DATE NULL 
AFTER activity_type;

-- ตรวจสอบโครงสร้างตารางหลังจากเพิ่มคอลัมน์
DESCRIBE customer_activities;

-- ทดสอบการ INSERT ข้อมูล (ถ้ามีข้อมูลลูกค้าในระบบ)
INSERT INTO customer_activities (
    customer_id, 
    activity_type, 
    activity_date, 
    description, 
    amount, 
    created_at
) VALUES (
    (SELECT customer_id FROM customers LIMIT 1),
    'purchase',
    CURDATE(),
    'ทดสอบการซื้อสินค้าหลังจากแก้ไขโครงสร้าง',
    1000.00,
    NOW()
);

-- ตรวจสอบข้อมูลที่เพิ่มเข้าไป
SELECT * FROM customer_activities 
WHERE description = 'ทดสอบการซื้อสินค้าหลังจากแก้ไขโครงสร้าง' 
ORDER BY created_at DESC 
LIMIT 1; 