-- CRM SalesTracker - Appointment Extension System
-- ระบบการนับจำนวนการนัดหมายและการต่อเวลาอัตโนมัติ

-- เพิ่มฟิลด์ใหม่ในตาราง customers สำหรับระบบการนัดหมาย
ALTER TABLE customers 
ADD COLUMN appointment_count INT DEFAULT 0 COMMENT 'จำนวนการนัดหมายที่ทำไปแล้ว',
ADD COLUMN appointment_extension_count INT DEFAULT 0 COMMENT 'จำนวนครั้งที่ต่อเวลาจากการนัดหมาย',
ADD COLUMN last_appointment_date TIMESTAMP NULL COMMENT 'วันที่นัดหมายล่าสุด',
ADD COLUMN appointment_extension_expiry TIMESTAMP NULL COMMENT 'วันหมดอายุการต่อเวลาจากการนัดหมาย',
ADD COLUMN max_appointment_extensions INT DEFAULT 3 COMMENT 'จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้ (default: 3)',
ADD COLUMN appointment_extension_days INT DEFAULT 30 COMMENT 'จำนวนวันที่ต่อเวลาต่อการนัดหมาย 1 ครั้ง (default: 30 วัน)';

-- สร้างตาราง appointment_extensions สำหรับติดตามประวัติการต่อเวลา
CREATE TABLE IF NOT EXISTS appointment_extensions (
    extension_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    appointment_id INT NULL COMMENT 'ID ของการนัดหมายที่ทำให้เกิดการต่อเวลา (NULL ถ้าเป็นการต่อเวลาอัตโนมัติ)',
    
    -- Extension Details
    extension_type ENUM('appointment', 'sale', 'manual') NOT NULL COMMENT 'ประเภทการต่อเวลา: appointment=จากนัดหมาย, sale=จากการขาย, manual=ต่อเวลาด้วยตนเอง',
    extension_days INT NOT NULL COMMENT 'จำนวนวันที่ต่อเวลา',
    extension_reason VARCHAR(200) COMMENT 'เหตุผลการต่อเวลา',
    
    -- Previous and New Dates
    previous_expiry TIMESTAMP NULL COMMENT 'วันหมดอายุเดิม',
    new_expiry TIMESTAMP NOT NULL COMMENT 'วันหมดอายุใหม่',
    
    -- Extension Count
    extension_count_before INT NOT NULL COMMENT 'จำนวนครั้งที่ต่อเวลาก่อนการต่อเวลานี้',
    extension_count_after INT NOT NULL COMMENT 'จำนวนครั้งที่ต่อเวลาหลังการต่อเวลานี้',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_id (user_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_extension_type (extension_type),
    INDEX idx_created_at (created_at)
);

-- สร้างตาราง appointment_extension_rules สำหรับตั้งค่ากฎการต่อเวลา
CREATE TABLE IF NOT EXISTS appointment_extension_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    rule_description TEXT,
    
    -- Rule Settings
    extension_days INT NOT NULL DEFAULT 30 COMMENT 'จำนวนวันที่ต่อเวลาต่อการนัดหมาย 1 ครั้ง',
    max_extensions INT NOT NULL DEFAULT 3 COMMENT 'จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้',
    reset_on_sale BOOLEAN DEFAULT TRUE COMMENT 'รีเซ็ตตัวนับเมื่อมีการขาย',
    
    -- Conditions
    min_appointment_duration INT DEFAULT 0 COMMENT 'ระยะเวลาขั้นต่ำของการนัดหมาย (นาที)',
    required_appointment_status ENUM('completed', 'confirmed', 'scheduled') DEFAULT 'completed' COMMENT 'สถานะการนัดหมายที่จำเป็น',
    
    -- Customer Filters
    min_customer_grade ENUM('A+', 'A', 'B', 'C', 'D') DEFAULT 'D' COMMENT 'เกรดลูกค้าขั้นต่ำ',
    temperature_status_filter JSON COMMENT 'สถานะอุณหภูมิที่ใช้ได้ (JSON array)',
    
    -- Metadata
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_rule_name (rule_name),
    INDEX idx_is_active (is_active)
);

-- เพิ่มข้อมูลเริ่มต้นสำหรับกฎการต่อเวลา
INSERT INTO appointment_extension_rules (
    rule_name, 
    rule_description, 
    extension_days, 
    max_extensions, 
    reset_on_sale, 
    required_appointment_status,
    min_customer_grade,
    temperature_status_filter
) VALUES (
    'Default Appointment Extension Rule',
    'กฎการต่อเวลามาตรฐาน: ต่อเวลา 30 วันต่อการนัดหมาย 1 ครั้ง สูงสุด 3 ครั้ง รีเซ็ตเมื่อมีการขาย',
    30,
    3,
    TRUE,
    'completed',
    'D',
    '["hot", "warm", "cold"]'
);

-- สร้าง Trigger สำหรับอัปเดต appointment_count เมื่อมีการสร้างนัดหมาย
DELIMITER //
CREATE TRIGGER after_appointment_insert
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    -- อัปเดตจำนวนการนัดหมายในตาราง customers
    UPDATE customers 
    SET appointment_count = appointment_count + 1,
        last_appointment_date = NEW.appointment_date
    WHERE customer_id = NEW.customer_id;
END//

-- สร้าง Trigger สำหรับอัปเดต appointment_count เมื่อมีการลบนัดหมาย
CREATE TRIGGER after_appointment_delete
AFTER DELETE ON appointments
FOR EACH ROW
BEGIN
    -- ลดจำนวนการนัดหมายในตาราง customers
    UPDATE customers 
    SET appointment_count = GREATEST(appointment_count - 1, 0)
    WHERE customer_id = OLD.customer_id;
END//

DELIMITER ;

-- เพิ่มข้อมูลตัวอย่างสำหรับทดสอบ
-- อัปเดตลูกค้าบางคนให้มีข้อมูลการนัดหมาย
UPDATE customers 
SET appointment_count = 1,
    appointment_extension_count = 0,
    last_appointment_date = DATE_SUB(NOW(), INTERVAL 15 DAY),
    appointment_extension_expiry = DATE_ADD(NOW(), INTERVAL 15 DAY)
WHERE customer_id IN (1, 2, 3);

UPDATE customers 
SET appointment_count = 2,
    appointment_extension_count = 1,
    last_appointment_date = DATE_SUB(NOW(), INTERVAL 10 DAY),
    appointment_extension_expiry = DATE_ADD(NOW(), INTERVAL 20 DAY)
WHERE customer_id = 4;

UPDATE customers 
SET appointment_count = 3,
    appointment_extension_count = 2,
    last_appointment_date = DATE_SUB(NOW(), INTERVAL 5 DAY),
    appointment_extension_expiry = DATE_ADD(NOW(), INTERVAL 25 DAY)
WHERE customer_id = 5;

-- เพิ่มข้อมูลตัวอย่างการต่อเวลา
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
(4, 3, 1, 'appointment', 30, 'นัดหมายเสร็จสิ้น - ต่อเวลา 30 วัน', 
 DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 0, 1),
(5, 3, 3, 'appointment', 30, 'นัดหมายเสร็จสิ้น - ต่อเวลา 30 วัน', 
 DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 1, 2);

-- สร้าง View สำหรับดูข้อมูลการต่อเวลาของลูกค้า
CREATE VIEW customer_appointment_extensions AS
SELECT 
    c.customer_id,
    c.first_name,
    c.last_name,
    c.phone,
    c.customer_grade,
    c.temperature_status,
    c.appointment_count,
    c.appointment_extension_count,
    c.max_appointment_extensions,
    c.appointment_extension_days,
    c.last_appointment_date,
    c.appointment_extension_expiry,
    c.assigned_to,
    u.full_name as assigned_to_name,
    CASE 
        WHEN c.appointment_extension_expiry IS NULL THEN 'ไม่มีวันหมดอายุ'
        WHEN c.appointment_extension_expiry < NOW() THEN 'หมดอายุแล้ว'
        WHEN c.appointment_extension_expiry < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'ใกล้หมดอายุ (ภายใน 7 วัน)'
        ELSE 'ยังไม่หมดอายุ'
    END as expiry_status,
    CASE 
        WHEN c.appointment_extension_count >= c.max_appointment_extensions THEN 'ไม่สามารถต่อเวลาได้แล้ว'
        ELSE CONCAT('สามารถต่อเวลาได้อีก ', (c.max_appointment_extensions - c.appointment_extension_count), ' ครั้ง')
    END as extension_availability
FROM customers c
LEFT JOIN users u ON c.assigned_to = u.user_id
WHERE c.is_active = TRUE;

-- สร้าง Stored Procedure สำหรับต่อเวลาอัตโนมัติเมื่อมีการนัดหมายเสร็จสิ้น
DELIMITER //
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

DELIMITER ;

-- สร้าง Stored Procedure สำหรับรีเซ็ตตัวนับเมื่อมีการขาย
DELIMITER //
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

-- สร้าง Index เพิ่มเติมสำหรับประสิทธิภาพ
CREATE INDEX idx_customers_appointment_extension ON customers(appointment_extension_count, appointment_extension_expiry);
CREATE INDEX idx_appointment_extensions_customer_date ON appointment_extensions(customer_id, created_at); 