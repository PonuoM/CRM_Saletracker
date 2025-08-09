<?php
require_once 'config/config.php';

echo "=== ตรวจสอบและแก้ไข Schema Database ===\n\n";

try {
    $db = new Database();
    
    // 1. ตรวจสอบโครงสร้าง call_logs table
    echo "1. ตรวจสอบโครงสร้าง call_logs table:\n";
    $columns = $db->fetchAll("DESCRIBE call_logs");
    $columnNames = array_column($columns, 'Field');
    
    echo "คอลัมน์ที่มีอยู่: " . implode(', ', $columnNames) . "\n\n";
    
    // ตรวจสอบคอลัมน์ที่จำเป็น
    $requiredColumns = ['followup_priority', 'followup_days', 'followup_notes'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columnNames)) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "❌ คอลัมน์ที่ขาดหายไป: " . implode(', ', $missingColumns) . "\n";
        echo "กำลังเพิ่มคอลัมน์ที่ขาดหายไป...\n\n";
        
        // เพิ่มคอลัมน์ที่ขาดหายไป
        if (in_array('followup_priority', $missingColumns)) {
            $db->execute("ALTER TABLE call_logs ADD COLUMN followup_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม'");
            echo "✅ เพิ่มคอลัมน์ followup_priority เรียบร้อย\n";
        }
        
        if (in_array('followup_days', $missingColumns)) {
            $db->execute("ALTER TABLE call_logs ADD COLUMN followup_days INT DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ'");
            echo "✅ เพิ่มคอลัมน์ followup_days เรียบร้อย\n";
        }
        
        if (in_array('followup_notes', $missingColumns)) {
            $db->execute("ALTER TABLE call_logs ADD COLUMN followup_notes TEXT NULL COMMENT 'หมายเหตุการติดตาม'");
            echo "✅ เพิ่มคอลัมน์ followup_notes เรียบร้อย\n";
        }
        
        echo "\n";
    } else {
        echo "✅ คอลัมน์ทั้งหมดมีอยู่แล้ว\n\n";
    }
    
    // 2. ตรวจสอบ call_followup_rules table
    echo "2. ตรวจสอบ call_followup_rules table:\n";
    try {
        $rules = $db->fetchAll("SELECT * FROM call_followup_rules LIMIT 5");
        echo "✅ call_followup_rules table มีอยู่แล้ว (พบ " . count($rules) . " กฎ)\n";
    } catch (Exception $e) {
        echo "❌ call_followup_rules table ไม่มีอยู่\n";
        echo "กำลังสร้าง call_followup_rules table...\n";
        
        $db->execute("
            CREATE TABLE `call_followup_rules` (
              `rule_id` int(11) NOT NULL AUTO_INCREMENT,
              `call_result` enum('interested','not_interested','callback','order','complaint') NOT NULL,
              `followup_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ',
              `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
              `is_active` tinyint(1) DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`rule_id`),
              UNIQUE KEY `unique_call_result` (`call_result`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // เพิ่มข้อมูลเริ่มต้น
        $db->execute("
            INSERT INTO `call_followup_rules` (`call_result`, `followup_days`, `priority`, `is_active`) VALUES
            ('not_interested', 30, 'low', 1),
            ('callback', 3, 'high', 1),
            ('interested', 7, 'medium', 1),
            ('complaint', 1, 'urgent', 1),
            ('order', 0, 'low', 1)
        ");
        
        echo "✅ สร้าง call_followup_rules table และข้อมูลเริ่มต้นเรียบร้อย\n";
    }
    echo "\n";
    
    // 3. ตรวจสอบ call_followup_queue table
    echo "3. ตรวจสอบ call_followup_queue table:\n";
    try {
        $queue = $db->fetchAll("SELECT * FROM call_followup_queue LIMIT 5");
        echo "✅ call_followup_queue table มีอยู่แล้ว (พบ " . count($queue) . " รายการในคิว)\n";
    } catch (Exception $e) {
        echo "❌ call_followup_queue table ไม่มีอยู่\n";
        echo "กำลังสร้าง call_followup_queue table...\n";
        
        $db->execute("
            CREATE TABLE `call_followup_queue` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "✅ สร้าง call_followup_queue table เรียบร้อย\n";
    }
    echo "\n";
    
    // 4. ตรวจสอบ customer_call_followup_list view
    echo "4. ตรวจสอบ customer_call_followup_list view:\n";
    try {
        $view = $db->fetchAll("SELECT * FROM customer_call_followup_list LIMIT 1");
        echo "✅ customer_call_followup_list view มีอยู่แล้ว\n";
    } catch (Exception $e) {
        echo "❌ customer_call_followup_list view ไม่มีอยู่\n";
        echo "กำลังสร้าง customer_call_followup_list view...\n";
        
        $db->execute("
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
            ORDER BY cl.next_followup_at ASC, cl.followup_priority DESC
        ");
        
        echo "✅ สร้าง customer_call_followup_list view เรียบร้อย\n";
    }
    echo "\n";
    
    // 5. ตรวจสอบข้อมูล call_logs ที่มี next_followup_at
    echo "5. ตรวจสอบข้อมูล call_logs:\n";
    $callLogsCount = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE next_followup_at IS NOT NULL");
    echo "จำนวน call_logs ที่มี next_followup_at: " . $callLogsCount['count'] . "\n";
    
    if ($callLogsCount['count'] == 0) {
        echo "⚠️ ไม่มีข้อมูล call_logs ที่มี next_followup_at\n";
        echo "กำลังสร้างข้อมูลทดสอบ...\n";
        
        // สร้างข้อมูลทดสอบ
        $customers = $db->fetchAll("SELECT customer_id, assigned_to FROM customers WHERE assigned_to IS NOT NULL LIMIT 3");
        
        if (!empty($customers)) {
            foreach ($customers as $customer) {
                $db->execute("
                    INSERT INTO call_logs (customer_id, user_id, call_type, call_status, call_result, notes, next_followup_at, followup_priority, followup_days)
                    VALUES (?, ?, 'outbound', 'answered', 'callback', 'ทดสอบการติดตาม', DATE_ADD(NOW(), INTERVAL 2 DAY), 'high', 3)
                ", [$customer['customer_id'], $customer['assigned_to']]);
            }
            echo "✅ สร้างข้อมูลทดสอบเรียบร้อย\n";
        } else {
            echo "❌ ไม่มีลูกค้าที่มี assigned_to\n";
        }
    }
    echo "\n";
    
    // 6. ทดสอบ API
    echo "6. ทดสอบ API calls.php:\n";
    try {
        // Simulate session
        session_start();
        $_SESSION['user_id'] = 1;
        $_SESSION['role_name'] = 'admin';
        
        // Include API file
        ob_start();
        include 'api/calls.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && isset($response['success']) && $response['success']) {
            echo "✅ API calls.php ทำงานปกติ\n";
            echo "จำนวนข้อมูลที่ส่งกลับ: " . count($response['data']) . "\n";
            
            if (!empty($response['data'])) {
                $firstRecord = $response['data'][0];
                echo "ตัวอย่างข้อมูล:\n";
                echo "- customer_id: " . ($firstRecord['customer_id'] ?? 'N/A') . "\n";
                echo "- next_followup_at: " . ($firstRecord['next_followup_at'] ?? 'N/A') . "\n";
                echo "- assigned_to_name: " . ($firstRecord['assigned_to_name'] ?? 'N/A') . "\n";
                echo "- followup_priority: " . ($firstRecord['followup_priority'] ?? 'N/A') . "\n";
                echo "- urgency_status: " . ($firstRecord['urgency_status'] ?? 'N/A') . "\n";
            }
        } else {
            echo "❌ API calls.php มีปัญหา: " . ($response['error'] ?? 'Unknown error') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ เกิดข้อผิดพลาดในการทดสอบ API: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== การตรวจสอบและแก้ไขเสร็จสิ้น ===\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}
?>
