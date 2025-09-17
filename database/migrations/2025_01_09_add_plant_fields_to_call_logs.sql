-- Migration: เพิ่มฟิลด์พืชพันธุ์และขนาดสวนในตาราง call_logs
-- Date: 2025-01-09
-- Description: เพิ่มฟิลด์สำหรับเก็บข้อมูลพืชพันธุ์และขนาดสวนในการบันทึกการโทร

-- เพิ่มฟิลด์พืชพันธุ์
ALTER TABLE `call_logs` 
ADD COLUMN `plant_variety` VARCHAR(255) DEFAULT NULL COMMENT 'พืชพันธุ์ที่ลูกค้าปลูก' AFTER `followup_priority`;

-- เพิ่มฟิลด์ขนาดสวน
ALTER TABLE `call_logs` 
ADD COLUMN `garden_size` VARCHAR(100) DEFAULT NULL COMMENT 'ขนาดสวน (ไร่/ตารางวา/ตารางเมตร)' AFTER `plant_variety`;

-- เพิ่ม index สำหรับการค้นหาตามพืชพันธุ์
ALTER TABLE `call_logs` 
ADD INDEX `idx_plant_variety` (`plant_variety`);

-- เพิ่ม index สำหรับการค้นหาตามขนาดสวน
ALTER TABLE `call_logs` 
ADD INDEX `idx_garden_size` (`garden_size`);
