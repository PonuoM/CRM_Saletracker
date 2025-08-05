-- ปิด foreign key checks ชั่วคราวเพื่อหลีกเลี่ยงปัญหา constraint
SET FOREIGN_KEY_CHECKS = 0;

-- ตารางสำหรับเก็บข้อมูลการแจ้งเตือน
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- ตารางสำหรับเก็บรายการลูกค้าที่ต้องติดตาม
CREATE TABLE IF NOT EXISTS customer_recall_list (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    priority INT NOT NULL DEFAULT 0,
    days_since_contact INT NOT NULL,
    created_date DATE NOT NULL,
    assigned_to INT NULL,
    status ENUM('pending', 'assigned', 'contacted', 'completed') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_created_date (created_date),
    INDEX idx_priority (priority),
    INDEX idx_status (status)
);

-- ตารางสำหรับเก็บ log การรัน cron jobs
CREATE TABLE IF NOT EXISTS cron_job_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_name VARCHAR(100) NOT NULL,
    status ENUM('running', 'success', 'failed') NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    execution_time DECIMAL(8,2) NULL,
    output TEXT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_name (job_name),
    INDEX idx_status (status),
    INDEX idx_start_time (start_time)
);

-- ตารางสำหรับเก็บ log กิจกรรมทั่วไป
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    activity_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NULL,
    record_id INT NULL,
    action VARCHAR(20) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
);

-- ตารางสำหรับเก็บการตั้งค่า cron jobs
CREATE TABLE IF NOT EXISTS cron_job_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_name VARCHAR(100) NOT NULL UNIQUE,
    cron_expression VARCHAR(100) NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_name (job_name),
    INDEX idx_is_enabled (is_enabled),
    INDEX idx_next_run (next_run)
);

-- เปิด foreign key checks กลับมา
SET FOREIGN_KEY_CHECKS = 1;

-- ลบ foreign key constraints เก่าถ้ามี (เผื่อมีการรันซ้ำ)
-- ALTER TABLE notifications DROP FOREIGN KEY IF EXISTS notifications_ibfk_1;
-- ALTER TABLE customer_recall_list DROP FOREIGN KEY IF EXISTS customer_recall_list_ibfk_1;
-- ALTER TABLE customer_recall_list DROP FOREIGN KEY IF EXISTS customer_recall_list_ibfk_2;
-- ALTER TABLE activity_logs DROP FOREIGN KEY IF EXISTS activity_logs_ibfk_1;

-- เพิ่ม foreign key constraints ใหม่
ALTER TABLE notifications 
ADD CONSTRAINT fk_notifications_user_id 
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

ALTER TABLE customer_recall_list 
ADD CONSTRAINT fk_customer_recall_customer_id 
FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;

ALTER TABLE customer_recall_list 
ADD CONSTRAINT fk_customer_recall_assigned_to 
FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL;

ALTER TABLE activity_logs 
ADD CONSTRAINT fk_activity_logs_user_id 
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- เพิ่มข้อมูลการตั้งค่า cron jobs เริ่มต้น
INSERT IGNORE INTO cron_job_settings (job_name, cron_expression, description) VALUES
('update_customer_grades', '0 2 * * *', 'อัปเดตเกรดลูกค้าอัตโนมัติทุก 2:00 น.'),
('update_customer_temperatures', '30 2 * * *', 'อัปเดตอุณหภูมิลูกค้าอัตโนมัติทุก 2:30 น.'),
('create_recall_list', '0 3 * * *', 'สร้างรายการลูกค้าที่ต้องติดตามทุก 3:00 น.'),
('send_notifications', '30 3 * * *', 'ส่งการแจ้งเตือนทุก 3:30 น.'),
('cleanup_old_data', '0 4 * * 0', 'ทำความสะอาดข้อมูลเก่าทุกวันอาทิตย์ 4:00 น.');

-- แสดงผลตารางที่สร้างเสร็จแล้ว
SELECT 'Cron tables created successfully!' as Status;
SHOW TABLES LIKE '%cron%';
SHOW TABLES LIKE 'notifications';
SHOW TABLES LIKE 'activity_logs';
SHOW TABLES LIKE 'customer_recall_list';