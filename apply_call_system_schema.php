<?php
/**
 * Apply Call Follow-up System Schema
 * นำเข้าโครงสร้างฐานข้อมูลสำหรับระบบการโทรติดตาม
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== เริ่มต้นการนำเข้าโครงสร้างฐานข้อมูลระบบการโทรติดตาม ===\n\n";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/app/core/Database.php';
    
    $db = new Database();
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n\n";
    
    // 1. ขยาย customer_status
    echo "1. ขยาย customer_status...\n";
    try {
        $db->execute("ALTER TABLE customers MODIFY COLUMN customer_status ENUM('new', 'existing', 'followup', 'call_followup') DEFAULT 'new'");
        echo "   ✅ สำเร็จ\n";
    } catch (Exception $e) {
        echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
    }
    
    // 2. เพิ่มคอลัมน์ใน call_logs
    echo "\n2. เพิ่มคอลัมน์ใน call_logs...\n";
    $columns = [
        "ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS followup_notes TEXT NULL COMMENT 'หมายเหตุการติดตาม'",
        "ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS followup_days INT DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ'",
        "ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS followup_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม'"
    ];
    
    foreach ($columns as $sql) {
        try {
            $db->execute($sql);
            echo "   ✅ สำเร็จ\n";
        } catch (Exception $e) {
            echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. สร้างตาราง call_followup_rules
    echo "\n3. สร้างตาราง call_followup_rules...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `call_followup_rules` (
        `rule_id` int(11) NOT NULL AUTO_INCREMENT,
        `call_result` enum('interested','not_interested','callback','order','complaint') NOT NULL,
        `followup_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ',
        `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`rule_id`),
        UNIQUE KEY `unique_call_result` (`call_result`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $db->execute($sql);
        echo "   ✅ สำเร็จ\n";
    } catch (Exception $e) {
        echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
    }
    
    // 4. เพิ่มข้อมูลเริ่มต้น
    echo "\n4. เพิ่มข้อมูลเริ่มต้นใน call_followup_rules...\n";
    $rules = [
        ['not_interested', 30, 'low'],
        ['callback', 3, 'high'],
        ['interested', 7, 'medium'],
        ['complaint', 1, 'urgent'],
        ['order', 0, 'low']
    ];
    
    foreach ($rules as $rule) {
        $sql = "INSERT INTO call_followup_rules (call_result, followup_days, priority, is_active) 
                VALUES (?, ?, ?, 1) 
                ON DUPLICATE KEY UPDATE 
                followup_days = VALUES(followup_days),
                priority = VALUES(priority),
                updated_at = NOW()";
        
        try {
            $db->execute($sql, $rule);
            echo "   ✅ เพิ่มกฎ: {$rule[0]}\n";
        } catch (Exception $e) {
            echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
        }
    }
    
    // 5. สร้างตาราง call_followup_queue
    echo "\n5. สร้างตาราง call_followup_queue...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `call_followup_queue` (
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
        KEY `idx_priority` (`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $db->execute($sql);
        echo "   ✅ สำเร็จ\n";
    } catch (Exception $e) {
        echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
    }
    
    // 6. สร้าง View
    echo "\n6. สร้าง View customer_call_followup_list...\n";
    $sql = "CREATE OR REPLACE VIEW `customer_call_followup_list` AS
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
        AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)";
    
    try {
        $db->execute($sql);
        echo "   ✅ สำเร็จ\n";
    } catch (Exception $e) {
        echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
    }
    
    // 7. เพิ่ม Indexes
    echo "\n7. เพิ่ม Indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_call_logs_customer_followup ON call_logs(customer_id, next_followup_at)",
        "CREATE INDEX IF NOT EXISTS idx_call_logs_result_followup ON call_logs(call_result, next_followup_at)",
        "CREATE INDEX IF NOT EXISTS idx_customers_status_followup ON customers(customer_status, next_followup_at)"
    ];
    
    foreach ($indexes as $sql) {
        try {
            $db->execute($sql);
            echo "   ✅ สำเร็จ\n";
        } catch (Exception $e) {
            echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
        }
    }
    
    // 8. เพิ่ม system_settings
    echo "\n8. เพิ่ม system_settings...\n";
    $settings = [
        ['call_followup_enabled', '1', 'เปิดใช้งานระบบติดตามการโทร'],
        ['call_followup_auto_queue', '1', 'สร้างคิวติดตามอัตโนมัติ'],
        ['call_followup_notification', '1', 'แจ้งเตือนการติดตาม']
    ];
    
    foreach ($settings as $setting) {
        $sql = "INSERT INTO system_settings (setting_key, setting_value, description) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                description = VALUES(description)";
        
        try {
            $db->execute($sql, $setting);
            echo "   ✅ เพิ่มการตั้งค่า: {$setting[0]}\n";
        } catch (Exception $e) {
            echo "   ⚠️  อาจมีอยู่แล้ว: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== การนำเข้าโครงสร้างฐานข้อมูลเสร็จสิ้น ===\n";
    echo "✅ ระบบการโทรติดตามพร้อมใช้งานแล้ว\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
