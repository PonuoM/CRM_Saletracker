-- =====================================================
-- Appointment Extension System - Database Setup
-- =====================================================

-- เพิ่มคอลัมน์ใหม่ในตาราง customers
ALTER TABLE customers 
ADD COLUMN appointment_count INT DEFAULT 0 COMMENT 'จำนวนการนัดหมายทั้งหมด',
ADD COLUMN appointment_extension_count INT DEFAULT 0 COMMENT 'จำนวนครั้งที่ต่อเวลาแล้ว',
ADD COLUMN last_appointment_date TIMESTAMP NULL COMMENT 'วันที่นัดหมายล่าสุด',
ADD COLUMN appointment_extension_expiry TIMESTAMP NULL COMMENT 'วันหมดอายุการต่อเวลา',
ADD COLUMN max_appointment_extensions INT DEFAULT 3 COMMENT 'จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้',
ADD COLUMN appointment_extension_days INT DEFAULT 30 COMMENT 'จำนวนวันที่จะต่อเวลาต่อการนัดหมาย 1 ครั้ง';

-- สร้างตารางสำหรับบันทึกประวัติการต่อเวลา
CREATE TABLE IF NOT EXISTS appointment_extensions (
    extension_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    appointment_id INT NULL COMMENT 'ID การนัดหมาย (ถ้ามี)',
    extension_type ENUM('appointment', 'sale', 'manual') NOT NULL COMMENT 'ประเภทการต่อเวลา',
    extension_days INT NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ต่อ',
    extension_reason TEXT COMMENT 'เหตุผลการต่อเวลา',
    previous_expiry TIMESTAMP NULL COMMENT 'วันหมดอายุเดิม',
    new_expiry TIMESTAMP NULL COMMENT 'วันหมดอายุใหม่',
    extension_count_before INT NOT NULL COMMENT 'จำนวนครั้งก่อนต่อเวลา',
    extension_count_after INT NOT NULL COMMENT 'จำนวนครั้งหลังต่อเวลา',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- สร้างตารางสำหรับกำหนดกฎการต่อเวลา
CREATE TABLE IF NOT EXISTS appointment_extension_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL COMMENT 'ชื่อกฎ',
    extension_days INT NOT NULL DEFAULT 30 COMMENT 'จำนวนวันที่ต่อต่อการนัดหมาย 1 ครั้ง',
    max_extensions INT NOT NULL DEFAULT 3 COMMENT 'จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้',
    reset_on_sale BOOLEAN DEFAULT TRUE COMMENT 'รีเซ็ตตัวนับเมื่อมีการขาย',
    required_appointment_status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'completed' COMMENT 'สถานะการนัดหมายที่ต้องมีเพื่อต่อเวลา',
    min_customer_grade ENUM('D', 'C', 'B', 'A', 'A+') DEFAULT 'D' COMMENT 'เกรดลูกค้าขั้นต่ำที่สามารถต่อเวลาได้',
    temperature_status_filter JSON DEFAULT '["hot", "warm", "cold"]' COMMENT 'สถานะอุณหภูมิที่สามารถต่อเวลาได้',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'สถานะการใช้งาน',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่มกฎเริ่มต้น
INSERT INTO appointment_extension_rules (
    rule_name, 
    extension_days, 
    max_extensions, 
    reset_on_sale, 
    required_appointment_status, 
    min_customer_grade, 
    temperature_status_filter
) VALUES (
    'กฎเริ่มต้นการต่อเวลา',
    30,
    3,
    TRUE,
    'completed',
    'D',
    '["hot", "warm", "cold"]'
);

-- สร้าง View สำหรับดูข้อมูลการต่อเวลาของลูกค้า
CREATE VIEW customer_appointment_extensions AS
SELECT 
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.customer_grade,
    c.temperature_status,
    c.appointment_count,
    c.appointment_extension_count,
    c.max_appointment_extensions,
    c.appointment_extension_expiry,
    c.appointment_extension_days,
    c.last_appointment_date,
    CASE 
        WHEN c.appointment_extension_expiry IS NULL THEN 'ไม่มีวันหมดอายุ'
        WHEN c.appointment_extension_expiry < NOW() THEN 'หมดอายุแล้ว'
        ELSE 'ยังไม่หมดอายุ'
    END as expiry_status,
    CASE 
        WHEN c.appointment_extension_count >= c.max_appointment_extensions THEN 'ไม่สามารถต่อเวลาได้แล้ว'
        ELSE 'สามารถต่อเวลาได้'
    END as extension_status,
    u.username as assigned_user
FROM customers c
LEFT JOIN users u ON c.assigned_to = u.user_id
WHERE c.is_active = TRUE;

-- สร้าง Trigger สำหรับอัปเดต appointment_count และ last_appointment_date
DELIMITER //

-- ลบ trigger เดิม (ถ้ามี)
DROP TRIGGER IF EXISTS after_appointment_insert//
DROP TRIGGER IF EXISTS after_appointment_delete//

CREATE TRIGGER after_appointment_insert
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    UPDATE customers 
    SET appointment_count = appointment_count + 1,
        last_appointment_date = NEW.appointment_date,
        updated_at = NOW()
    WHERE customer_id = NEW.customer_id;
END//

CREATE TRIGGER after_appointment_delete
AFTER DELETE ON appointments
FOR EACH ROW
BEGIN
    UPDATE customers 
    SET appointment_count = GREATEST(appointment_count - 1, 0),
        updated_at = NOW()
    WHERE customer_id = OLD.customer_id;
END//
DELIMITER ;

-- สร้าง Stored Procedure สำหรับต่อเวลาอัตโนมัติเมื่อมีการนัดหมายเสร็จสิ้น
DELIMITER //

-- ลบ stored procedure เดิม (ถ้ามี)
DROP PROCEDURE IF EXISTS ExtendCustomerTimeFromAppointment//
DROP PROCEDURE IF EXISTS ResetAppointmentExtensionOnSale//

CREATE PROCEDURE ExtendCustomerTimeFromAppointment(
    IN p_customer_id INT,
    IN p_appointment_id INT,
    IN p_user_id INT,
    IN p_extension_days INT
)
BEGIN
    DECLARE v_current_extension_count INT DEFAULT 0;
    DECLARE v_max_extensions INT DEFAULT 3;
    DECLARE v_current_expiry TIMESTAMP NULL;
    DECLARE v_new_expiry TIMESTAMP;
    DECLARE v_extension_days_actual INT;
    
    -- เริ่ม transaction
    START TRANSACTION;
    
    -- ดึงข้อมูลปัจจุบันของลูกค้า
    SELECT 
        appointment_extension_count,
        max_appointment_extensions,
        appointment_extension_expiry
    INTO 
        v_current_extension_count,
        v_max_extensions,
        v_current_expiry
    FROM customers 
    WHERE customer_id = p_customer_id;
    
    -- ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่
    IF v_current_extension_count >= v_max_extensions THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ไม่สามารถต่อเวลาได้แล้ว เนื่องจากเกินจำนวนครั้งสูงสุดที่กำหนด';
    END IF;
    
    -- คำนวณวันหมดอายุใหม่
    IF v_current_expiry IS NULL OR v_current_expiry < NOW() THEN
        -- ถ้าไม่มีวันหมดอายุหรือหมดอายุแล้ว ให้เริ่มใหม่
        SET v_new_expiry = DATE_ADD(NOW(), INTERVAL p_extension_days DAY);
    ELSE
        -- ถ้ายังไม่หมดอายุ ให้ต่อจากวันหมดอายุเดิม
        SET v_new_expiry = DATE_ADD(v_current_expiry, INTERVAL p_extension_days DAY);
    END IF;
    
    -- อัปเดตข้อมูลลูกค้า
    UPDATE customers 
    SET appointment_extension_count = appointment_extension_count + 1,
        appointment_extension_expiry = v_new_expiry,
        updated_at = NOW()
    WHERE customer_id = p_customer_id;
    
    -- บันทึกประวัติการต่อเวลา
    INSERT INTO appointment_extensions (
        customer_id,
        user_id,
        appointment_id,
        extension_type,
        extension_days,
        extension_reason,
        previous_expiry,
        new_expiry,
        extension_count_before,
        extension_count_after
    ) VALUES (
        p_customer_id,
        p_user_id,
        p_appointment_id,
        'appointment',
        p_extension_days,
        CONCAT('ต่อเวลาจากการนัดหมาย #', p_appointment_id, ' - ต่อเวลา ', p_extension_days, ' วัน'),
        v_current_expiry,
        v_new_expiry,
        v_current_extension_count,
        v_current_extension_count + 1
    );
    
    -- Commit transaction
    COMMIT;
    
    -- ส่งคืนผลลัพธ์
    SELECT 
        'success' as status,
        CONCAT('ต่อเวลาสำเร็จ: ', p_extension_days, ' วัน') as message,
        v_new_expiry as new_expiry_date,
        (v_current_extension_count + 1) as new_extension_count;
        
END//

-- สร้าง Stored Procedure สำหรับรีเซ็ตตัวนับเมื่อมีการขาย
CREATE PROCEDURE ResetAppointmentExtensionOnSale(
    IN p_customer_id INT,
    IN p_user_id INT,
    IN p_order_id INT
)
BEGIN
    DECLARE v_previous_extension_count INT DEFAULT 0;
    DECLARE v_previous_expiry TIMESTAMP NULL;
    
    -- เริ่ม transaction
    START TRANSACTION;
    
    -- ดึงข้อมูลปัจจุบัน
    SELECT 
        appointment_extension_count,
        appointment_extension_expiry
    INTO 
        v_previous_extension_count,
        v_previous_expiry
    FROM customers 
    WHERE customer_id = p_customer_id;
    
    -- รีเซ็ตตัวนับการต่อเวลา
    UPDATE customers 
    SET appointment_extension_count = 0,
        appointment_extension_expiry = NULL,
        updated_at = NOW()
    WHERE customer_id = p_customer_id;
    
    -- บันทึกประวัติการรีเซ็ต
    INSERT INTO appointment_extensions (
        customer_id,
        user_id,
        appointment_id,
        extension_type,
        extension_days,
        extension_reason,
        previous_expiry,
        new_expiry,
        extension_count_before,
        extension_count_after
    ) VALUES (
        p_customer_id,
        p_user_id,
        NULL,
        'sale',
        0,
        CONCAT('รีเซ็ตตัวนับการต่อเวลาจากการขาย #', p_order_id),
        v_previous_expiry,
        NULL,
        v_previous_extension_count,
        0
    );
    
    -- Commit transaction
    COMMIT;
    
    -- ส่งคืนผลลัพธ์
    SELECT 
        'success' as status,
        'รีเซ็ตตัวนับการต่อเวลาสำเร็จ' as message,
        v_previous_extension_count as previous_count,
        0 as new_count;
        
END//
DELIMITER ;

-- เพิ่มข้อมูลตัวอย่างสำหรับทดสอบ
INSERT INTO customers (
    customer_name, 
    customer_grade, 
    temperature_status, 
    appointment_count, 
    appointment_extension_count, 
    max_appointment_extensions,
    appointment_extension_days
) VALUES 
('ลูกค้าทดสอบ 1', 'A', 'hot', 2, 1, 3, 30),
('ลูกค้าทดสอบ 2', 'B', 'warm', 1, 0, 3, 30),
('ลูกค้าทดสอบ 3', 'C', 'cold', 3, 2, 3, 30);

-- เพิ่มข้อมูลตัวอย่างในตาราง appointment_extensions
INSERT INTO appointment_extensions (
    customer_id,
    user_id,
    appointment_id,
    extension_type,
    extension_days,
    extension_reason,
    previous_expiry,
    new_expiry,
    extension_count_before,
    extension_count_after
) VALUES 
(1, 1, 1, 'appointment', 30, 'ต่อเวลาจากการนัดหมาย #1 - ต่อเวลา 30 วัน', NULL, DATE_ADD(NOW(), INTERVAL 30 DAY), 0, 1),
(3, 1, 3, 'appointment', 30, 'ต่อเวลาจากการนัดหมาย #3 - ต่อเวลา 30 วัน', NULL, DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 2);

-- สร้าง Index เพิ่มเติมสำหรับประสิทธิภาพ
CREATE INDEX idx_customers_appointment_extension ON customers(appointment_extension_count, appointment_extension_expiry);
CREATE INDEX idx_appointment_extensions_customer_date ON appointment_extensions(customer_id, created_at); 