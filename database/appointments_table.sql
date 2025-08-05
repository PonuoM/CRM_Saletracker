-- CRM SalesTracker - Appointments Table
-- สร้างตารางสำหรับเก็บข้อมูลนัดหมาย

-- สร้างตาราง appointments
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Appointment Details
    appointment_date DATETIME NOT NULL,
    appointment_type ENUM('call', 'meeting', 'presentation', 'followup', 'other') NOT NULL,
    appointment_status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    
    -- Location and Contact
    location VARCHAR(200),
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    
    -- Description and Notes
    title VARCHAR(200),
    description TEXT,
    notes TEXT,
    
    -- Reminder Settings
    reminder_sent BOOLEAN DEFAULT FALSE,
    reminder_sent_at TIMESTAMP NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_id (user_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_appointment_status (appointment_status),
    INDEX idx_appointment_type (appointment_type),
    INDEX idx_created_at (created_at)
);

-- สร้างตาราง appointment_activities สำหรับติดตามกิจกรรม
CREATE TABLE IF NOT EXISTS appointment_activities (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('created', 'updated', 'confirmed', 'completed', 'cancelled', 'reminder_sent') NOT NULL,
    activity_description TEXT NOT NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

-- เพิ่มข้อมูลตัวอย่าง
INSERT INTO appointments (customer_id, user_id, appointment_date, appointment_type, appointment_status, title, description, notes) VALUES
(1, 3, DATE_ADD(NOW(), INTERVAL 2 DAY), 'meeting', 'scheduled', 'ประชุมนำเสนอสินค้าใหม่', 'ประชุมกับลูกค้าเพื่อนำเสนอสินค้าใหม่ที่เพิ่งเปิดตัว', 'ลูกค้าสนใจสินค้าใหม่มาก'),
(2, 4, DATE_ADD(NOW(), INTERVAL 1 DAY), 'call', 'scheduled', 'โทรติดตามการสั่งซื้อ', 'โทรติดตามลูกค้าเกี่ยวกับการสั่งซื้อที่ค้างอยู่', 'ลูกค้าบอกว่าจะโทรกลับมา'),
(5, 3, DATE_ADD(NOW(), INTERVAL 3 DAY), 'presentation', 'scheduled', 'นำเสนอโปรโมชั่นพิเศษ', 'นำเสนอโปรโมชั่นพิเศษสำหรับลูกค้าเกรด A+', 'โปรโมชั่นพิเศษ 20% สำหรับลูกค้าเกรด A+');

-- เพิ่มข้อมูลกิจกรรม
INSERT INTO appointment_activities (appointment_id, user_id, activity_type, activity_description) VALUES
(1, 3, 'created', 'สร้างนัดหมายประชุมนำเสนอสินค้าใหม่'),
(2, 4, 'created', 'สร้างนัดหมายโทรติดตามการสั่งซื้อ'),
(3, 3, 'created', 'สร้างนัดหมายนำเสนอโปรโมชั่นพิเศษ'); 