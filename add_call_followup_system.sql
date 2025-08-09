-- =====================================================
-- เพิ่มระบบการโทรติดตาม (Call Follow-up System)
-- =====================================================

-- 1. ขยาย customer_status เพื่อรองรับ call_followup
ALTER TABLE customers MODIFY COLUMN customer_status 
ENUM('new', 'existing', 'followup', 'call_followup') DEFAULT 'new';

-- 2. เพิ่มฟิลด์ใน call_logs สำหรับการติดตาม
ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS followup_notes TEXT NULL COMMENT 'หมายเหตุการติดตาม';
ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS followup_days INT DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ';
ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS followup_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม';

-- 3. สร้างตาราง call_followup_rules สำหรับกำหนดกฎการติดตาม
CREATE TABLE IF NOT EXISTS `call_followup_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `call_result` enum('interested','not_interested','callback','order','complaint') NOT NULL,
  `followup_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`rule_id`),
  UNIQUE KEY `unique_call_result` (`call_result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. เพิ่มข้อมูลเริ่มต้นสำหรับ call_followup_rules
INSERT INTO `call_followup_rules` (`call_result`, `followup_days`, `priority`, `is_active`) VALUES
('not_interested', 30, 'low', 1),
('callback', 3, 'high', 1),
('interested', 7, 'medium', 1),
('complaint', 1, 'urgent', 1),
('order', 0, 'low', 1)
ON DUPLICATE KEY UPDATE 
followup_days = VALUES(followup_days),
priority = VALUES(priority),
updated_at = NOW();

-- 5. สร้างตาราง call_followup_queue สำหรับคิวการติดตาม
CREATE TABLE IF NOT EXISTS `call_followup_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `call_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ผู้ที่ต้องติดตาม',
  `followup_date` date NOT NULL COMMENT 'วันที่ต้องติดตาม',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`queue_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_followup_date` (`followup_date`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`call_log_id`) REFERENCES `call_logs`(`log_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. สร้าง View สำหรับลูกค้าที่ต้องติดตามการโทร
CREATE OR REPLACE VIEW `customer_call_followup_list` AS
SELECT 
    c.customer_id,
    c.customer_code,
    c.first_name,
    c.last_name,
    c.phone,
    c.email,
    c.province,
    c.temperature_status,
    c.customer_grade,
    c.assigned_to,
    u.full_name as assigned_to_name,
    cl.log_id as call_log_id,
    cl.call_result,
    cl.call_status,
    cl.created_at as last_call_date,
    cl.next_followup_at,
    cl.followup_notes,
    cl.followup_days,
    cl.followup_priority,
    cfq.queue_id,
    cfq.followup_date,
    cfq.status as queue_status,
    cfq.priority as queue_priority,
    DATEDIFF(cl.next_followup_at, NOW()) as days_until_followup,
    CASE 
        WHEN cl.next_followup_at <= NOW() THEN 'overdue'
        WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'urgent'
        WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'soon'
        ELSE 'normal'
    END as urgency_status
FROM customers c
LEFT JOIN users u ON c.assigned_to = u.user_id
LEFT JOIN call_logs cl ON c.customer_id = cl.customer_id
LEFT JOIN call_followup_queue cfq ON c.customer_id = cfq.customer_id AND cfq.status = 'pending'
WHERE c.is_active = 1 
    AND c.assigned_to IS NOT NULL
    AND cl.next_followup_at IS NOT NULL
    AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
    AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
ORDER BY cl.next_followup_at ASC, cl.followup_priority DESC;

-- 7. เพิ่ม Indexes เพื่อเพิ่มประสิทธิภาพ
CREATE INDEX IF NOT EXISTS idx_call_logs_customer_followup ON call_logs(customer_id, next_followup_at);
CREATE INDEX IF NOT EXISTS idx_call_logs_result_followup ON call_logs(call_result, next_followup_at);
CREATE INDEX IF NOT EXISTS idx_customers_status_followup ON customers(customer_status, next_followup_at);

-- 8. อัปเดต system_settings เพิ่มการตั้งค่าการติดตาม
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`, `setting_type`) VALUES
('call_followup_enabled', '1', 'เปิดใช้งานระบบติดตามการโทร', 'boolean'),
('call_followup_auto_queue', '1', 'สร้างคิวการติดตามอัตโนมัติ', 'boolean'),
('call_followup_notification', '1', 'แจ้งเตือนการติดตาม', 'boolean'),
('call_followup_max_days', '30', 'จำนวนวันสูงสุดในการติดตาม', 'integer')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = NOW();

-- 9. เพิ่ม stored procedure สำหรับสร้างคิวการติดตาม
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `CreateCallFollowupQueue`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_customer_id, v_call_log_id, v_user_id INT;
    DECLARE v_followup_date DATE;
    DECLARE v_priority ENUM('low', 'medium', 'high', 'urgent');
    
    -- Cursor สำหรับลูกค้าที่ต้องติดตาม
    DECLARE followup_cursor CURSOR FOR
        SELECT 
            cl.customer_id,
            cl.log_id,
            c.assigned_to,
            cl.next_followup_at,
            cl.followup_priority
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        WHERE cl.next_followup_at IS NOT NULL
            AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
            AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
            AND NOT EXISTS (
                SELECT 1 FROM call_followup_queue cfq 
                WHERE cfq.customer_id = cl.customer_id 
                AND cfq.status = 'pending'
            );
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN followup_cursor;
    
    read_loop: LOOP
        FETCH followup_cursor INTO v_customer_id, v_call_log_id, v_user_id, v_followup_date, v_priority;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- สร้างคิวการติดตาม
        INSERT INTO call_followup_queue (
            customer_id, call_log_id, user_id, followup_date, priority, status
        ) VALUES (
            v_customer_id, v_call_log_id, v_user_id, v_followup_date, v_priority, 'pending'
        );
        
    END LOOP;
    
    CLOSE followup_cursor;
END$$

DELIMITER ;

-- 10. เพิ่ม trigger สำหรับอัปเดต customer_status เมื่อมีการโทร
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `update_customer_status_on_call` 
AFTER INSERT ON `call_logs`
FOR EACH ROW
BEGIN
    DECLARE v_customer_status VARCHAR(20);
    
    -- ดึงสถานะปัจจุบันของลูกค้า
    SELECT customer_status INTO v_customer_status 
    FROM customers 
    WHERE customer_id = NEW.customer_id;
    
    -- อัปเดตสถานะเป็น call_followup ถ้าจำเป็น
    IF NEW.call_result IN ('not_interested', 'callback', 'interested', 'complaint') 
       AND v_customer_status NOT IN ('call_followup') THEN
        UPDATE customers 
        SET customer_status = 'call_followup',
            last_contact_at = NOW(),
            updated_at = NOW()
        WHERE customer_id = NEW.customer_id;
    END IF;
END$$

DELIMITER ;

-- สรุปการเปลี่ยนแปลง
SELECT 'Call Follow-up System Setup Complete' as status;
