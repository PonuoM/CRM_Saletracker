-- CRM SalesTracker Database Setup
-- สร้างตารางและข้อมูลตัวอย่าง

-- 1. สร้างตาราง roles
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    role_description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_role_name (role_name)
);

-- 2. สร้างตาราง companies
CREATE TABLE IF NOT EXISTS companies (
    company_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    company_code VARCHAR(20) UNIQUE,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_code (company_code)
);

-- 3. สร้างตาราง users
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role_id INT NOT NULL,
    company_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id),
    INDEX idx_username (username),
    INDEX idx_role (role_id),
    INDEX idx_active (is_active)
);

-- 4. สร้างตาราง customers
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(50) UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    district VARCHAR(50),
    province VARCHAR(50),
    postal_code VARCHAR(10),
    
    -- Customer Status
    temperature_status ENUM('hot', 'warm', 'cold', 'frozen') DEFAULT 'hot',
    customer_grade ENUM('A+', 'A', 'B', 'C', 'D') DEFAULT 'D',
    total_purchase_amount DECIMAL(12,2) DEFAULT 0.00,
    
    -- Assignment and Tracking
    assigned_to INT NULL,
    basket_type ENUM('distribution', 'waiting', 'assigned') DEFAULT 'distribution',
    assigned_at TIMESTAMP NULL,
    last_contact_at TIMESTAMP NULL,
    next_followup_at TIMESTAMP NULL,
    recall_at TIMESTAMP NULL,
    
    -- Metadata
    source VARCHAR(50),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_basket_type (basket_type),
    INDEX idx_temperature (temperature_status),
    INDEX idx_grade (customer_grade),
    INDEX idx_province (province),
    INDEX idx_recall_at (recall_at),
    INDEX idx_next_followup (next_followup_at),
    INDEX idx_phone (phone),
    INDEX idx_created_at (created_at)
);

-- 5. สร้างตาราง products
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    unit VARCHAR(20) DEFAULT 'ชิ้น',
    cost_price DECIMAL(10,2) DEFAULT 0.00,
    selling_price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_product_code (product_code),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
);

-- 6. สร้างตาราง orders
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    created_by INT NOT NULL,
    
    -- Order Details
    order_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    net_amount DECIMAL(12,2) NOT NULL,
    
    -- Payment and Delivery
    payment_method ENUM('cash', 'transfer', 'cod', 'credit', 'other') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'partial', 'cancelled') DEFAULT 'pending',
    delivery_date DATE,
    delivery_address TEXT,
    delivery_status ENUM('pending', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    
    -- Metadata
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_created_by (created_by),
    INDEX idx_order_date (order_date),
    INDEX idx_payment_status (payment_status),
    INDEX idx_delivery_status (delivery_status)
);

-- 7. สร้างตาราง order_details
CREATE TABLE IF NOT EXISTS order_details (
    detail_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    net_price DECIMAL(12,2) NOT NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- 7.1 สร้างตาราง order_items (สำหรับ OrderService)
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- 7.2 สร้างตาราง order_activities (สำหรับ OrderService)
CREATE TABLE IF NOT EXISTS order_activities (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('created', 'status_update', 'payment_update', 'delivery_update', 'cancelled') NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_activity_date (created_at)
);

-- 8. สร้างตาราง call_logs
CREATE TABLE IF NOT EXISTS call_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    call_type ENUM('outbound', 'inbound') DEFAULT 'outbound',
    call_status ENUM('answered', 'no_answer', 'busy', 'invalid') NOT NULL,
    call_result ENUM('interested', 'not_interested', 'callback', 'order', 'complaint') NULL,
    duration_minutes INT DEFAULT 0,
    notes TEXT,
    next_action VARCHAR(200),
    next_followup_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_id (user_id),
    INDEX idx_call_date (created_at),
    INDEX idx_next_followup (next_followup_at)
);

-- 9. สร้างตาราง customer_activities
CREATE TABLE IF NOT EXISTS customer_activities (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('status_change', 'assignment', 'call', 'order', 'note', 'recall') NOT NULL,
    activity_description TEXT NOT NULL,
    old_value VARCHAR(200),
    new_value VARCHAR(200),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

-- 10. สร้างตาราง sales_history
CREATE TABLE IF NOT EXISTS sales_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at TIMESTAMP NOT NULL,
    unassigned_at TIMESTAMP NULL,
    reason VARCHAR(200),
    total_orders INT DEFAULT 0,
    total_sales DECIMAL(12,2) DEFAULT 0.00,
    is_current BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_id (user_id),
    INDEX idx_assigned_at (assigned_at),
    INDEX idx_is_current (is_current)
);

-- 11. สร้างตาราง system_settings
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_editable BOOLEAN DEFAULT TRUE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(user_id),
    INDEX idx_setting_key (setting_key)
);

-- เพิ่มข้อมูลตัวอย่าง

-- 1. เพิ่ม Roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('super_admin', 'Super Administrator', '["all"]'),
('admin', 'Company Administrator', '["user_management", "product_management", "data_import", "reports"]'),
('supervisor', 'Team Supervisor', '["team_overview", "lead_distribution", "team_reports"]'),
('telesales', 'Telesales Representative', '["customer_management", "order_creation", "personal_reports"]');

-- 2. เพิ่ม Company
INSERT INTO companies (company_name, company_code, address, phone, email) VALUES
('บริษัท พรีม่าแพสชั่น 49 จำกัด', 'PRIMA49', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', '02-123-4567', 'info@prima49.com');

-- 3. เพิ่ม Users (รหัสผ่าน: 123456)
INSERT INTO users (username, password_hash, full_name, email, phone, role_id, company_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@prima49.com', '081-234-5678', 1, 1),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'หัวหน้าทีมขาย', 'supervisor@prima49.com', '081-234-5679', 3, 1),
('telesales1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 1', 'telesales1@prima49.com', '081-234-5680', 4, 1),
('telesales2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 2', 'telesales2@prima49.com', '081-234-5681', 4, 1);

-- 4. เพิ่ม Products
INSERT INTO products (product_code, product_name, category, description, unit, cost_price, selling_price, stock_quantity) VALUES
('P001', 'เสื้อโปโล', 'เสื้อผ้า', 'เสื้อโปโลคุณภาพดี', 'ตัว', 150.00, 250.00, 100),
('P002', 'กางเกงยีนส์', 'เสื้อผ้า', 'กางเกงยีนส์สไตล์สวย', 'ตัว', 300.00, 450.00, 50),
('P003', 'รองเท้าผ้าใบ', 'รองเท้า', 'รองเท้าผ้าใบสไตล์สปอร์ต', 'คู่', 400.00, 600.00, 30),
('P004', 'กระเป๋าถือ', 'กระเป๋า', 'กระเป๋าถือสไตล์แฟชั่น', 'ใบ', 200.00, 350.00, 25);

-- 5. เพิ่ม Customers
INSERT INTO customers (customer_code, first_name, last_name, phone, email, address, district, province, temperature_status, customer_grade, basket_type, source) VALUES
('C001', 'สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', 'hot', 'A', 'assigned', 'facebook'),
('C002', 'สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดาภิเษก', 'ดินแดง', 'กรุงเทพฯ', 'warm', 'B', 'distribution', 'import'),
('C003', 'สมศักดิ์', 'มั่งมี', '081-333-3333', 'somsak@email.com', '789 ถนนลาดพร้าว', 'วังทองหลาง', 'กรุงเทพฯ', 'cold', 'C', 'waiting', 'manual'),
('C004', 'สมปอง', 'ใจเย็น', '081-444-4444', 'sompong@email.com', '321 ถนนเพชรบุรี', 'ห้วยขวาง', 'กรุงเทพฯ', 'frozen', 'D', 'distribution', 'facebook'),
('C005', 'สมใจ', 'รักงาน', '081-555-5555', 'somjai@email.com', '654 ถนนพระราม 9', 'ห้วยขวาง', 'กรุงเทพฯ', 'hot', 'A+', 'assigned', 'import');

-- 6. เพิ่ม System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('customer_grade_a_plus', '50000', 'number', 'Minimum purchase amount for A+ grade'),
('customer_grade_a', '10000', 'number', 'Minimum purchase amount for A grade'),
('customer_grade_b', '5000', 'number', 'Minimum purchase amount for B grade'),
('customer_grade_c', '2000', 'number', 'Minimum purchase amount for C grade'),
('new_customer_recall_days', '30', 'number', 'Days before recalling new customers'),
('existing_customer_recall_days', '90', 'number', 'Days before recalling existing customers'),
('waiting_basket_days', '30', 'number', 'Days customers stay in waiting basket');

-- 7. มอบหมายลูกค้าให้ telesales
UPDATE customers SET assigned_to = 3, basket_type = 'assigned', assigned_at = NOW() WHERE customer_id = 1;
UPDATE customers SET assigned_to = 4, basket_type = 'assigned', assigned_at = NOW() WHERE customer_id = 5; 