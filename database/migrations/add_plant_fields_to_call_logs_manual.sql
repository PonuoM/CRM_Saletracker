-- Migration: เพิ่มฟิลด์พืชพันธุ์และขนาดสวนในตาราง call_logs
-- วันที่: 2025-01-11
-- รันไฟล์นี้ผ่าน phpMyAdmin หรือ MySQL client

-- ตรวจสอบว่าฟิลด์มีอยู่แล้วหรือไม่ก่อนเพิ่ม
-- ถ้าฟิลด์มีอยู่แล้ว จะเกิด error แต่ไม่เป็นไร

-- เพิ่มฟิลด์ plant_variety
ALTER TABLE `call_logs` 
ADD COLUMN `plant_variety` varchar(255) DEFAULT NULL COMMENT 'พืชพันธุ์ที่ลูกค้าปลูก' AFTER `followup_priority`;

-- เพิ่มฟิลด์ garden_size
ALTER TABLE `call_logs` 
ADD COLUMN `garden_size` varchar(100) DEFAULT NULL COMMENT 'ขนาดสวน (ไร่/ตารางวา/ตารางเมตร)' AFTER `plant_variety`;

-- เพิ่ม index สำหรับฟิลด์ใหม่
ALTER TABLE `call_logs` 
ADD KEY `idx_plant_variety` (`plant_variety`);

ALTER TABLE `call_logs` 
ADD KEY `idx_garden_size` (`garden_size`);

-- ตรวจสอบผลลัพธ์
SHOW COLUMNS FROM `call_logs`;

-- ตรวจสอบ index
SHOW INDEX FROM `call_logs`;
