-- Add Workflow Columns to Customers Table
-- เพิ่มคอลัมน์สำหรับระบบ Workflow ในตาราง customers

-- เพิ่มคอลัมน์ basket_type สำหรับเก็บสถานะของลูกค้า
ALTER TABLE customers ADD COLUMN basket_type ENUM('distribution', 'assigned', 'waiting') DEFAULT 'distribution' COMMENT 'สถานะของลูกค้า: distribution=รอแจกจ่าย, assigned=มอบหมายแล้ว, waiting=รอการดำเนินการ';

-- เพิ่มคอลัมน์ assigned_at สำหรับเก็บวันที่มอบหมายลูกค้า
ALTER TABLE customers ADD COLUMN assigned_at DATETIME NULL COMMENT 'วันที่มอบหมายลูกค้าให้เซลส์';

-- เพิ่มคอลัมน์ assigned_to สำหรับเก็บ ID ของผู้ที่ได้รับมอบหมาย
ALTER TABLE customers ADD COLUMN assigned_to INT NULL COMMENT 'ID ของเซลส์ที่ได้รับมอบหมาย';

-- เพิ่มคอลัมน์ recall_at สำหรับเก็บวันที่เรียกคืนลูกค้า
ALTER TABLE customers ADD COLUMN recall_at DATETIME NULL COMMENT 'วันที่เรียกคืนลูกค้า';

-- เพิ่มคอลัมน์สำหรับการต่อเวลา
ALTER TABLE customers ADD COLUMN customer_time_expiry DATETIME NULL COMMENT 'วันที่หมดอายุของลูกค้า';
ALTER TABLE customers ADD COLUMN customer_time_extension INT DEFAULT 0 COMMENT 'จำนวนวันที่ต่อเวลา';

-- เพิ่ม Foreign Key สำหรับ assigned_to
ALTER TABLE customers ADD CONSTRAINT fk_customers_assigned_to 
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- เพิ่ม Index สำหรับการค้นหาที่เร็วขึ้น
CREATE INDEX idx_customers_basket_type ON customers(basket_type);
CREATE INDEX idx_customers_assigned_at ON customers(assigned_at);
CREATE INDEX idx_customers_assigned_to ON customers(assigned_to);
CREATE INDEX idx_customers_recall_at ON customers(recall_at);

-- อัปเดตข้อมูลเริ่มต้น: กำหนดลูกค้าที่มี assigned_to ให้เป็น assigned
UPDATE customers 
SET basket_type = 'assigned', 
    assigned_at = COALESCE(created_at, NOW())
WHERE assigned_to IS NOT NULL AND basket_type IS NULL;

-- อัปเดตข้อมูลเริ่มต้น: กำหนดลูกค้าที่ไม่มี assigned_to ให้เป็น distribution
UPDATE customers 
SET basket_type = 'distribution'
WHERE assigned_to IS NULL AND basket_type IS NULL;
