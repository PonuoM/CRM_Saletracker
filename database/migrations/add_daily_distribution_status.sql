-- Migration: เพิ่มประเภทลูกค้า "daily_distribution" (แจกรายวัน)
-- วันที่: $(date)
-- คำอธิบาย: เพิ่มตัวเลือก 'daily_distribution' ใน customer_status ENUM

-- ตรวจสอบโครงสร้างปัจจุบัน
SHOW COLUMNS FROM customers LIKE 'customer_status';

-- อัปเดต ENUM ให้รวม 'daily_distribution'
ALTER TABLE customers 
MODIFY COLUMN customer_status ENUM('new','existing','followup','call_followup','daily_distribution') 
DEFAULT 'new';

-- ตรวจสอบการเปลี่ยนแปลง
SHOW COLUMNS FROM customers LIKE 'customer_status';

-- แสดงจำนวนลูกค้าแต่ละประเภท
SELECT 
    customer_status,
    COUNT(*) as count
FROM customers 
GROUP BY customer_status
ORDER BY count DESC;
