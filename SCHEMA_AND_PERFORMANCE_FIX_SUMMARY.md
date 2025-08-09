# Schema and Performance Fix Summary

## ปัญหาที่พบ (Issues Found)

### 1. Schema Issues
- **Missing Column Error**: `Column not found: 1054 Unknown column 'followup_priority' in 'field list'`
- **Root Cause**: The `add_call_followup_system.sql` script was not fully applied to the database
- **Missing Components**:
  - `followup_priority` column in `call_logs` table
  - `followup_days` column in `call_logs` table  
  - `followup_notes` column in `call_logs` table
  - `call_followup_rules` table
  - `call_followup_queue` table
  - `customer_call_followup_list` view

### 2. Performance Issues
- **User Report**: "มีความหน่วงเกิดขึ้น ซึ่ง ผิดวิสัย ลองตรวจสอบด่วน" (There is a delay, which is unusual. Please check immediately)
- **Potential Causes**:
  - Missing database indexes on frequently queried columns
  - Complex queries without proper optimization
  - Large datasets without proper indexing

## การแก้ไข (Fixes Applied)

### 1. Schema Fixes (`fix_schema_and_performance.php`)

#### 1.1 เพิ่มคอลัมน์ที่ขาดหายไปใน call_logs table
```sql
ALTER TABLE call_logs ADD COLUMN followup_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม';
ALTER TABLE call_logs ADD COLUMN followup_days INT DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ';
ALTER TABLE call_logs ADD COLUMN followup_notes TEXT NULL COMMENT 'หมายเหตุการติดตาม';
```

#### 1.2 สร้าง call_followup_rules table
```sql
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
```

#### 1.3 สร้าง call_followup_queue table
```sql
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
```

#### 1.4 สร้าง customer_call_followup_list view
```sql
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
```

### 2. Performance Fixes

#### 2.1 เพิ่ม Database Indexes
```sql
CREATE INDEX IF NOT EXISTS idx_call_logs_customer_followup ON call_logs(customer_id, next_followup_at);
CREATE INDEX IF NOT EXISTS idx_call_logs_result_followup ON call_logs(call_result, next_followup_at);
CREATE INDEX IF NOT EXISTS idx_call_logs_priority ON call_logs(followup_priority);
CREATE INDEX IF NOT EXISTS idx_customers_assigned_to ON customers(assigned_to);
CREATE INDEX IF NOT EXISTS idx_customers_active ON customers(is_active);
```

#### 2.2 สร้างข้อมูลทดสอบ
- สร้างข้อมูล call_logs ที่มี next_followup_at สำหรับการทดสอบ
- เพิ่มข้อมูลเริ่มต้นใน call_followup_rules

## ไฟล์ที่สร้างขึ้น (Files Created)

### 1. `fix_schema_and_performance.php`
- **Purpose**: Comprehensive fix script that addresses both schema and performance issues
- **Features**:
  - Adds missing columns to call_logs table
  - Creates missing tables (call_followup_rules, call_followup_queue)
  - Creates/updates customer_call_followup_list view
  - Adds performance indexes
  - Creates test data if needed
  - Tests the fixed system

### 2. `debug_schema_issue.php`
- **Purpose**: Simple debug script to identify schema issues
- **Features**:
  - Checks call_logs table structure
  - Tests the failing query
  - Adds missing columns if needed

### 3. `test_performance.php`
- **Purpose**: Performance testing script
- **Features**:
  - Tests database connection performance
  - Tests query performance
  - Tests API endpoint performance
  - Checks for missing indexes
  - Provides performance recommendations

## ผลลัพธ์ที่คาดหวัง (Expected Results)

### 1. Schema Issues Resolved
- ✅ `followup_priority` column error should be fixed
- ✅ All required tables and views should be available
- ✅ API calls should work without errors

### 2. Performance Improvements
- ✅ Database queries should be faster with new indexes
- ✅ API response times should be reduced
- ✅ The reported "delay" should be resolved

### 3. Functionality Restored
- ✅ "วันที่ติดตาม" (Follow-up Date) column should display correctly
- ✅ "ผู้รับผิดชอบ" (Responsible Person) column should display correctly
- ✅ "สถานะ" (Status) column should display correctly
- ✅ All urgency and priority calculations should work properly

## วิธีการทดสอบ (Testing Instructions)

### 1. Run the Fix Script
```bash
php fix_schema_and_performance.php
```

### 2. Test the API
```bash
php test_performance.php
```

### 3. Test the Original Issue
```bash
php test_call_followup_date_fix.php
```

### 4. Check the Web Interface
- Visit the "การโทรติดตาม" (Call Follow-up) page
- Verify that all columns display correctly
- Check that there are no delays in loading

## หมายเหตุ (Notes)

1. **Database Backup**: Always backup the database before running schema changes
2. **Test Environment**: Test these changes in a development environment first
3. **Monitoring**: Monitor performance after applying the fixes
4. **Rollback Plan**: Keep the original `add_call_followup_system.sql` for reference

## สรุป (Summary)

ปัญหาหลักคือ database schema ไม่สมบูรณ์เนื่องจาก `add_call_followup_system.sql` ไม่ได้ถูก apply อย่างสมบูรณ์ การแก้ไขนี้จะ:

1. **แก้ไข Schema Issues**: เพิ่มคอลัมน์และตารางที่ขาดหายไป
2. **ปรับปรุง Performance**: เพิ่ม indexes เพื่อเพิ่มความเร็วในการ query
3. **สร้างข้อมูลทดสอบ**: เพื่อให้สามารถทดสอบระบบได้ทันที
4. **ทดสอบระบบ**: ตรวจสอบว่าการแก้ไขทำงานได้ถูกต้อง

หลังจากรัน `fix_schema_and_performance.php` แล้ว ระบบควรจะทำงานได้ปกติและไม่มี delay อีกต่อไป
