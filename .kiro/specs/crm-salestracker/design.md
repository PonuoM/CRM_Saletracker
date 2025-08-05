# เอกสารการออกแบบระบบ

## ภาพรวม

ระบบ CRM SalesTracker เป็นเว็บแอปพลิเคชันที่ออกแบบมาเพื่อจัดการลูกค้าสัมพันธ์สำหรับบริษัท พรีม่าแพสชั่น 49 จำกัด ระบบใช้สถาปัตยกรรม MVC (Model-View-Controller) ด้วย PHP Backend, MySQL Database และ Responsive Frontend ที่รองรับการใช้งานบนทุกอุปกรณ์

## สถาปัตยกรรมระบบ

### โครงสร้างระบบโดยรวม

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Database      │
│   (HTML/CSS/JS) │◄──►│   (PHP)         │◄──►│   (MySQL)       │
│   - Dashboard   │    │   - Controllers │    │   - Tables      │
│   - Forms       │    │   - Models      │    │   - Relations   │
│   - Reports     │    │   - Services    │    │   - Indexes     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │   Cron Jobs     │
                       │   - Auto Recall │
                       │   - Status Upd  │
                       │   - Cleanup     │
                       └─────────────────┘
```

### เทคโนโลยีที่ใช้

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5 สำหรับ Responsive Design
- **Charts**: Chart.js สำหรับกราฟและรายงาน
- **Icons**: Font Awesome
- **Server**: Apache (XAMPP สำหรับ Development)

## การออกแบบ UX/UI

### หลักการออกแบบ

1. **Mobile-First Approach**: ออกแบบสำหรับมือถือก่อน แล้วขยายไปเดสก์ท็อป
2. **Role-Based Interface**: แสดงเฉพาะฟังก์ชันที่เกี่ยวข้องกับบทบาทผู้ใช้
3. **Data-Driven Dashboard**: เน้นการแสดงข้อมูลที่สำคัญอย่างชัดเจน
4. **Intuitive Navigation**: การนำทางที่เข้าใจง่าย
5. **Consistent Design Language**: ใช้สีและรูปแบบที่สอดคล้องกันทั้งระบบ

### Color Scheme

```css
:root {
  --primary-color: #2563eb;      /* Blue - Primary actions */
  --secondary-color: #64748b;    /* Gray - Secondary elements */
  --success-color: #10b981;      /* Green - Success states */
  --warning-color: #f59e0b;      /* Yellow - Warning states */
  --danger-color: #ef4444;       /* Red - Danger/Error states */
  --hot-color: #dc2626;          /* Hot customers 🔥 */
  --warm-color: #f59e0b;         /* Warm customers 🌤️ */
  --cold-color: #3b82f6;         /* Cold customers ❄️ */
  --frozen-color: #6b7280;       /* Frozen customers 🧊 */
}
```

### Layout Structure

#### 1. Header Navigation
```html
<header class="navbar">
  <div class="navbar-brand">
    <img src="logo.png" alt="Prima49 CRM">
    <span>SalesTracker</span>
  </div>
  <nav class="navbar-menu">
    <!-- Role-based menu items -->
  </nav>
  <div class="navbar-user">
    <span>สวัสดี, [ชื่อผู้ใช้]</span>
    <button class="logout-btn">ออกจากระบบ</button>
  </div>
</header>
```

#### 2. Sidebar Navigation (Desktop)
```html
<aside class="sidebar">
  <ul class="nav-menu">
    <li><a href="/dashboard">📊 แดชบอร์ด</a></li>
    <li><a href="/customers">👥 ลูกค้า</a></li>
    <li><a href="/orders">🛒 คำสั่งซื้อ</a></li>
    <li><a href="/reports">📈 รายงาน</a></li>
    <!-- Admin only -->
    <li><a href="/admin">⚙️ ตั้งค่า</a></li>
  </ul>
</aside>
```

#### 3. Main Content Area
```html
<main class="main-content">
  <div class="page-header">
    <h1>หน้าหลัก</h1>
    <div class="breadcrumb">หน้าแรก > แดชบอร์ด</div>
  </div>
  <div class="content-body">
    <!-- Page specific content -->
  </div>
</main>
```

### หน้าจอหลักตามบทบาท

#### 1. Dashboard - Admin/Supervisor
```html
<div class="dashboard-grid">
  <!-- KPI Cards -->
  <div class="kpi-cards">
    <div class="kpi-card">
      <h3>คำสั่งซื้อทั้งหมด</h3>
      <div class="kpi-value">1,234</div>
      <div class="kpi-change">+12% จากเดือนที่แล้ว</div>
    </div>
    <div class="kpi-card">
      <h3>ยอดขายรวม</h3>
      <div class="kpi-value">฿2,456,789</div>
      <div class="kpi-change">+8% จากเดือนที่แล้ว</div>
    </div>
    <div class="kpi-card">
      <h3>มูลค่าเฉลี่ย</h3>
      <div class="kpi-value">฿1,987</div>
      <div class="kpi-change">-3% จากเดือนที่แล้ว</div>
    </div>
  </div>
  
  <!-- Charts -->
  <div class="chart-container">
    <canvas id="salesChart"></canvas>
  </div>
  
  <!-- Team Performance -->
  <div class="team-performance">
    <table class="performance-table">
      <thead>
        <tr>
          <th>ชื่อ</th>
          <th>ยอดขาย</th>
          <th>จำนวนการโทร</th>
          <th>อัตราปิดการขาย</th>
        </tr>
      </thead>
      <tbody>
        <!-- Dynamic data -->
      </tbody>
    </table>
  </div>
</div>
```

#### 2. Dashboard - Telesales
```html
<div class="telesales-dashboard">
  <!-- Personal KPIs -->
  <div class="personal-kpis">
    <div class="kpi-card">
      <h3>ยอดขายเดือนนี้</h3>
      <div class="kpi-value">฿45,678</div>
    </div>
    <div class="kpi-card">
      <h3>ลูกค้าในมือ</h3>
      <div class="kpi-value">23</div>
    </div>
    <div class="kpi-card">
      <h3>นัดหมายวันนี้</h3>
      <div class="kpi-value">5</div>
    </div>
  </div>
  
  <!-- Do Section -->
  <div class="do-section">
    <h2>สิ่งที่ต้องทำวันนี้</h2>
    <div class="urgent-customers">
      <!-- List of urgent actions -->
    </div>
  </div>
</div>
```

#### 3. Customer Management Interface
```html
<div class="customer-management">
  <!-- Tabs -->
  <div class="tab-navigation">
    <button class="tab-btn active" data-tab="do">Do</button>
    <button class="tab-btn" data-tab="new">ลูกค้าใหม่</button>
    <button class="tab-btn" data-tab="followup">ติดตาม</button>
    <button class="tab-btn" data-tab="existing">ลูกค้าเก่า</button>
  </div>
  
  <!-- Filters -->
  <div class="filters">
    <select id="tempFilter">
      <option value="">สถานะทั้งหมด</option>
      <option value="hot">🔥 Hot</option>
      <option value="warm">🌤️ Warm</option>
      <option value="cold">❄️ Cold</option>
      <option value="frozen">🧊 Frozen</option>
    </select>
    <select id="gradeFilter">
      <option value="">เกรดทั้งหมด</option>
      <option value="A+">A+</option>
      <option value="A">A</option>
      <option value="B">B</option>
      <option value="C">C</option>
      <option value="D">D</option>
    </select>
    <select id="provinceFilter">
      <option value="">จังหวัดทั้งหมด</option>
      <!-- Dynamic provinces -->
    </select>
  </div>
  
  <!-- Customer Table -->
  <div class="customer-table">
    <table class="data-table">
      <thead>
        <tr>
          <th>วันที่ได้รับ</th>
          <th>ชื่อลูกค้า</th>
          <th>ที่อยู่</th>
          <th>จังหวัด</th>
          <th>เวลาที่เหลือ</th>
          <th>สถานะ</th>
          <th>เกรด</th>
          <th>การติดต่อ</th>
          <th>การดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <!-- Dynamic customer data -->
      </tbody>
    </table>
  </div>
</div>
```

## โครงสร้างฐานข้อมูล (Database Schema)

### ตารางหลักและความสัมพันธ์

#### 1. ตาราง users
```sql
CREATE TABLE users (
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
```

#### 2. ตาราง roles
```sql
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    role_description TEXT,
    permissions JSON, -- Store permissions as JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_role_name (role_name)
);

-- Insert default roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('super_admin', 'Super Administrator', '["all"]'),
('admin', 'Company Administrator', '["user_management", "product_management", "data_import", "reports"]'),
('supervisor', 'Team Supervisor', '["team_overview", "lead_distribution", "team_reports"]'),
('telesales', 'Telesales Representative', '["customer_management", "order_creation", "personal_reports"]');
```

#### 3. ตาราง companies
```sql
CREATE TABLE companies (
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
```

#### 4. ตาราง customers
```sql
CREATE TABLE customers (
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
    assigned_to INT NULL, -- Current telesales assigned
    basket_type ENUM('distribution', 'waiting', 'assigned') DEFAULT 'distribution',
    assigned_at TIMESTAMP NULL,
    last_contact_at TIMESTAMP NULL,
    next_followup_at TIMESTAMP NULL,
    recall_at TIMESTAMP NULL, -- When customer should be recalled
    
    -- Metadata
    source VARCHAR(50), -- facebook, import, manual, etc.
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
```

#### 5. ตาราง products
```sql
CREATE TABLE products (
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
```

#### 6. ตาราง orders
```sql
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    created_by INT NOT NULL, -- Telesales who created the order
    
    -- Order Details
    order_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    net_amount DECIMAL(12,2) NOT NULL,
    
    -- Payment and Delivery
    payment_method ENUM('cash', 'transfer', 'credit', 'other') DEFAULT 'cash',
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
```

#### 7. ตาราง order_details
```sql
CREATE TABLE order_details (
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
```

#### 8. ตาราง call_logs
```sql
CREATE TABLE call_logs (
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
```

#### 9. ตาราง customer_activities
```sql
CREATE TABLE customer_activities (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('status_change', 'assignment', 'call', 'order', 'note', 'recall') NOT NULL,
    activity_description TEXT NOT NULL,
    old_value VARCHAR(200),
    new_value VARCHAR(200),
    metadata JSON, -- Additional activity data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);
```

#### 10. ตาราง sales_history
```sql
CREATE TABLE sales_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at TIMESTAMP NOT NULL,
    unassigned_at TIMESTAMP NULL,
    reason VARCHAR(200), -- Why customer was unassigned
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
```

#### 11. ตาราง system_settings
```sql
CREATE TABLE system_settings (
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

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('customer_grade_a_plus', '50000', 'number', 'Minimum purchase amount for A+ grade'),
('customer_grade_a', '10000', 'number', 'Minimum purchase amount for A grade'),
('customer_grade_b', '5000', 'number', 'Minimum purchase amount for B grade'),
('customer_grade_c', '2000', 'number', 'Minimum purchase amount for C grade'),
('new_customer_recall_days', '30', 'number', 'Days before recalling new customers'),
('existing_customer_recall_days', '90', 'number', 'Days before recalling existing customers'),
('waiting_basket_days', '30', 'number', 'Days customers stay in waiting basket');
```

### ความสัมพันธ์ระหว่างตาราง

#### Primary Relationships
```
users (1) ←→ (N) customers (assigned_to)
customers (1) ←→ (N) orders
orders (1) ←→ (N) order_details
products (1) ←→ (N) order_details
customers (1) ←→ (N) call_logs
customers (1) ←→ (N) customer_activities
customers (1) ←→ (N) sales_history
users (1) ←→ (N) call_logs
users (1) ←→ (N) customer_activities
users (1) ←→ (N) sales_history
roles (1) ←→ (N) users
companies (1) ←→ (N) users
```

#### Business Logic Relationships
```
Customer Lifecycle:
Distribution Basket → Assigned → Working → Order/Recall
                 ↑                    ↓
                 ← Waiting Basket ←────
```

## Cron Jobs และการอัปเดตอัตโนมัติ

### 1. Customer Recall Job
```php
<?php
// File: /cron/customer_recall.php
// Run every hour: 0 * * * * php /path/to/cron/customer_recall.php

class CustomerRecallJob {
    
    public function run() {
        $this->recallNewCustomers();
        $this->recallExistingCustomers();
        $this->moveFromWaitingToDistribution();
        $this->logActivity();
    }
    
    /**
     * Recall new customers who haven't been updated in 30 days
     */
    private function recallNewCustomers() {
        $sql = "
            UPDATE customers 
            SET basket_type = 'distribution',
                assigned_to = NULL,
                recall_at = NOW()
            WHERE basket_type = 'assigned'
            AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND customer_id NOT IN (
                SELECT DISTINCT customer_id 
                FROM orders 
                WHERE created_at > assigned_at
            )
            AND customer_id NOT IN (
                SELECT DISTINCT customer_id 
                FROM call_logs 
                WHERE created_at > assigned_at 
                AND call_result IN ('interested', 'callback', 'order')
            )
        ";
        
        $affected = $this->db->execute($sql);
        
        // Log activities for recalled customers
        if ($affected > 0) {
            $this->logCustomerRecall('new_customer_timeout', $affected);
        }
    }
    
    /**
     * Recall existing customers with no orders in 90 days
     */
    private function recallExistingCustomers() {
        $sql = "
            UPDATE customers 
            SET basket_type = 'waiting',
                assigned_to = NULL,
                recall_at = NOW()
            WHERE basket_type = 'assigned'
            AND customer_id IN (
                SELECT customer_id FROM orders 
                GROUP BY customer_id 
                HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
            )
        ";
        
        $affected = $this->db->execute($sql);
        
        if ($affected > 0) {
            $this->logCustomerRecall('existing_customer_inactive', $affected);
        }
    }
    
    /**
     * Move customers from waiting basket to distribution after 30 days
     */
    private function moveFromWaitingToDistribution() {
        $sql = "
            UPDATE customers 
            SET basket_type = 'distribution'
            WHERE basket_type = 'waiting'
            AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        
        $affected = $this->db->execute($sql);
        
        if ($affected > 0) {
            $this->logCustomerRecall('waiting_to_distribution', $affected);
        }
    }
}
```

### 2. Customer Grade Update Job
```php
<?php
// File: /cron/update_customer_grades.php
// Run daily at 2 AM: 0 2 * * * php /path/to/cron/update_customer_grades.php

class CustomerGradeUpdateJob {
    
    public function run() {
        $this->updateCustomerGrades();
        $this->updateTemperatureStatus();
    }
    
    private function updateCustomerGrades() {
        // Get grade thresholds from settings
        $grades = $this->getGradeSettings();
        
        // Update customer grades based on total purchase amount
        $sql = "
            UPDATE customers c
            SET customer_grade = CASE
                WHEN c.total_purchase_amount >= {$grades['a_plus']} THEN 'A+'
                WHEN c.total_purchase_amount >= {$grades['a']} THEN 'A'
                WHEN c.total_purchase_amount >= {$grades['b']} THEN 'B'
                WHEN c.total_purchase_amount >= {$grades['c']} THEN 'C'
                ELSE 'D'
            END
            WHERE c.is_active = 1
        ";
        
        $this->db->execute($sql);
    }
    
    private function updateTemperatureStatus() {
        // Update temperature based on recent activity and purchase history
        $sql = "
            UPDATE customers c
            SET temperature_status = CASE
                -- Hot: New customers or high-value regular buyers
                WHEN (c.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)) 
                     OR (c.customer_grade IN ('A+', 'A') AND 
                         EXISTS(SELECT 1 FROM orders o WHERE o.customer_id = c.customer_id 
                                AND o.order_date > DATE_SUB(NOW(), INTERVAL 60 DAY))) 
                THEN 'hot'
                
                -- Warm: Existing customers with irregular purchases
                WHEN EXISTS(SELECT 1 FROM orders o WHERE o.customer_id = c.customer_id 
                           AND o.order_date > DATE_SUB(NOW(), INTERVAL 180 DAY))
                THEN 'warm'
                
                -- Cold: Old customers with low activity
                WHEN EXISTS(SELECT 1 FROM orders o WHERE o.customer_id = c.customer_id)
                THEN 'cold'
                
                -- Frozen: Customers with rejection history or multiple transfers
                ELSE 'frozen'
            END
            WHERE c.is_active = 1
        ";
        
        $this->db->execute($sql);
    }
}
```

### 3. Data Cleanup Job
```php
<?php
// File: /cron/data_cleanup.php
// Run weekly: 0 3 * * 0 php /path/to/cron/data_cleanup.php

class DataCleanupJob {
    
    public function run() {
        $this->cleanupOldLogs();
        $this->updateStatistics();
        $this->optimizeTables();
    }
    
    private function cleanupOldLogs() {
        // Keep only 1 year of call logs
        $sql = "DELETE FROM call_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $this->db->execute($sql);
        
        // Keep only 2 years of customer activities
        $sql = "DELETE FROM customer_activities WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)";
        $this->db->execute($sql);
    }
    
    private function updateStatistics() {
        // Update customer total purchase amounts
        $sql = "
            UPDATE customers c
            SET total_purchase_amount = (
                SELECT COALESCE(SUM(o.net_amount), 0)
                FROM orders o
                WHERE o.customer_id = c.customer_id
                AND o.payment_status = 'paid'
            )
        ";
        $this->db->execute($sql);
    }
}
```

### 4. Crontab Configuration
```bash
# /etc/crontab or user crontab
# Customer recall check - every hour
0 * * * * php /var/www/html/CRM/cron/customer_recall.php >> /var/log/crm_recall.log 2>&1

# Customer grade update - daily at 2 AM
0 2 * * * php /var/www/html/CRM/cron/update_customer_grades.php >> /var/log/crm_grades.log 2>&1

# Data cleanup - weekly on Sunday at 3 AM
0 3 * * 0 php /var/www/html/CRM/cron/data_cleanup.php >> /var/log/crm_cleanup.log 2>&1

# Database backup - daily at 1 AM
0 1 * * * mysqldump -u username -p password primacom_Customer > /backup/crm_backup_$(date +\%Y\%m\%d).sql
```

## คอมโพเนนต์และอินเทอร์เฟซ

### 1. Authentication Service
```php
<?php
class AuthService {
    
    public function login($username, $password) {
        // Validate credentials
        // Set session
        // Log login activity
        // Return user data with permissions
    }
    
    public function checkPermission($user, $permission) {
        // Check if user role has required permission
    }
    
    public function logout() {
        // Clear session
        // Log logout activity
    }
}
```

### 2. Customer Service
```php
<?php
class CustomerService {
    
    public function assignCustomers($supervisorId, $telesalesId, $customerIds) {
        // Move customers from distribution basket to assigned
        // Update assignment history
        // Log activity
    }
    
    public function recallCustomer($customerId, $reason) {
        // Move customer to appropriate basket
        // Log recall reason
        // Update sales history
    }
    
    public function updateCustomerStatus($customerId, $status, $notes) {
        // Update customer status
        // Log activity
        // Set next followup if needed
    }
}
```

### 3. Order Service
```php
<?php
class OrderService {
    
    public function createOrder($customerId, $orderData) {
        // Create order and order details
        // Update customer purchase amount
        // Recalculate customer grade
        // Log activity
    }
    
    public function updateOrderStatus($orderId, $status) {
        // Update order status
        // Log activity
        // Send notifications if needed
    }
}
```

## การจัดการข้อผิดพลาด

### Error Handling Strategy
```php
<?php
class ErrorHandler {
    
    public function handleDatabaseError($error) {
        // Log error details
        // Return user-friendly message
        // Send alert to admin if critical
    }
    
    public function handleValidationError($errors) {
        // Format validation errors
        // Return structured response
    }
    
    public function handleSystemError($error) {
        // Log system error
        // Return generic error message
        // Alert system administrator
    }
}
```

## กลยุทธ์การทดสอบ

### 1. Unit Testing
- Test individual functions and methods
- Mock database connections
- Test business logic validation

### 2. Integration Testing
- Test API endpoints
- Test database operations
- Test cron job functionality

### 3. User Acceptance Testing
- Test complete user workflows
- Test role-based access control
- Test mobile responsiveness

### 4. Performance Testing
- Load testing with multiple concurrent users
- Database query optimization
- Response time monitoring

## การปรับขนาดและประสิทธิภาพ

### Database Optimization
```sql
-- Indexes for common queries
CREATE INDEX idx_customers_assigned_recall ON customers(assigned_to, recall_at);
CREATE INDEX idx_orders_customer_date ON orders(customer_id, order_date);
CREATE INDEX idx_call_logs_customer_date ON call_logs(customer_id, created_at);

-- Partitioning for large tables
ALTER TABLE call_logs PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### Caching Strategy
```php
<?php
// Redis caching for frequently accessed data
class CacheService {
    
    public function cacheCustomerData($customerId, $data) {
        $this->redis->setex("customer:$customerId", 3600, json_encode($data));
    }
    
    public function getCachedCustomerData($customerId) {
        $cached = $this->redis->get("customer:$customerId");
        return $cached ? json_decode($cached, true) : null;
    }
}
```

การออกแบบนี้ครอบคลุมทุกแง่มุมของระบบ CRM SalesTracker ตั้งแต่ UX/UI, โครงสร้างฐานข้อมูลที่ละเอียด, ความสัมพันธ์ระหว่างตาราง, การใช้งาน Cron Jobs สำหรับการอัปเดตอัตโนมัติ และกลยุทธ์การจัดการประสิทธิภาพ