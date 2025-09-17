-- Migration: เพิ่มฟิลด์พืชพันธุ์และขนาดสวนในตาราง call_logs
-- วันที่: 2025-01-11

-- เพิ่มฟิลด์ plant_variety และ garden_size ในตาราง call_logs
ALTER TABLE `call_logs` 
ADD COLUMN `plant_variety` varchar(255) DEFAULT NULL COMMENT 'พืชพันธุ์ที่ลูกค้าปลูก' AFTER `followup_priority`,
ADD COLUMN `garden_size` varchar(100) DEFAULT NULL COMMENT 'ขนาดสวน (ไร่/ตารางวา/ตารางเมตร)' AFTER `plant_variety`;

-- เพิ่ม index สำหรับฟิลด์ใหม่
ALTER TABLE `call_logs` 
ADD KEY `idx_plant_variety` (`plant_variety`),
ADD KEY `idx_garden_size` (`garden_size`);

-- อัปเดต comment ของตาราง
ALTER TABLE `call_logs` COMMENT = 'ตารางบันทึกการโทรลูกค้า - เพิ่มฟิลด์พืชพันธุ์และขนาดสวน';
