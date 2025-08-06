-- Fix order_activities table schema
-- เพิ่มคอลัมน์ description ที่หายไปในตาราง order_activities

-- ตรวจสอบว่าคอลัมน์ description มีอยู่หรือไม่
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'order_activities' 
    AND COLUMN_NAME = 'description'
);

-- เพิ่มคอลัมน์ description ถ้ายังไม่มี
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE order_activities ADD COLUMN description TEXT NOT NULL AFTER activity_type',
    'SELECT "Column description already exists in order_activities table" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ตรวจสอบโครงสร้างตารางหลังการแก้ไข
DESCRIBE order_activities; 