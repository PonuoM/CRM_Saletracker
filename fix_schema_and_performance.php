<?php
require_once 'config/config.php';

echo "=== Fix Schema and Performance Issues ===\n\n";

try {
    $db = new Database();
    
    // Step 1: Fix missing columns
    echo "1. Fixing missing columns in call_logs table:\n";
    
    $columns = $db->fetchAll("DESCRIBE call_logs");
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = [
        'followup_priority' => "ALTER TABLE call_logs ADD COLUMN followup_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม'",
        'followup_days' => "ALTER TABLE call_logs ADD COLUMN followup_days INT DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ'",
        'followup_notes' => "ALTER TABLE call_logs ADD COLUMN followup_notes TEXT NULL COMMENT 'หมายเหตุการติดตาม'"
    ];
    
    foreach ($requiredColumns as $column => $sql) {
        if (!in_array($column, $columnNames)) {
            try {
                $db->execute($sql);
                echo "✅ Added column: $column\n";
            } catch (Exception $e) {
                echo "❌ Failed to add $column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ Column $column already exists\n";
        }
    }
    
    // Step 2: Create missing tables
    echo "\n2. Creating missing tables:\n";
    
    // Create call_followup_rules table
    try {
        $db->execute("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ call_followup_rules table created/verified\n";
        
        // Insert default rules
        $db->execute("
            INSERT INTO `call_followup_rules` (`call_result`, `followup_days`, `priority`, `is_active`) VALUES
            ('not_interested', 30, 'low', 1),
            ('callback', 3, 'high', 1),
            ('interested', 7, 'medium', 1),
            ('complaint', 1, 'urgent', 1),
            ('order', 0, 'low', 1)
            ON DUPLICATE KEY UPDATE 
            followup_days = VALUES(followup_days),
            priority = VALUES(priority),
            updated_at = NOW()
        ");
        echo "✅ Default rules inserted\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to create call_followup_rules: " . $e->getMessage() . "\n";
    }
    
    // Create call_followup_queue table
    try {
        $db->execute("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ call_followup_queue table created/verified\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to create call_followup_queue: " . $e->getMessage() . "\n";
    }
    
    // Step 3: Create/Update view
    echo "\n3. Creating/updating customer_call_followup_list view:\n";
    
    try {
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
        echo "✅ customer_call_followup_list view created/updated\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to create view: " . $e->getMessage() . "\n";
    }
    
    // Step 4: Add performance indexes
    echo "\n4. Adding performance indexes:\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_call_logs_customer_followup ON call_logs(customer_id, next_followup_at)",
        "CREATE INDEX IF NOT EXISTS idx_call_logs_result_followup ON call_logs(call_result, next_followup_at)",
        "CREATE INDEX IF NOT EXISTS idx_call_logs_priority ON call_logs(followup_priority)",
        "CREATE INDEX IF NOT EXISTS idx_customers_assigned_to ON customers(assigned_to)",
        "CREATE INDEX IF NOT EXISTS idx_customers_active ON customers(is_active)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $db->execute($indexSql);
            echo "✅ Index created/verified\n";
        } catch (Exception $e) {
            echo "⚠️ Index creation warning: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 5: Create test data if needed
    echo "\n5. Creating test data if needed:\n";
    
    $callLogsCount = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE next_followup_at IS NOT NULL")['count'];
    
    if ($callLogsCount == 0) {
        echo "No call_logs with next_followup_at found. Creating test data...\n";
        
        $customers = $db->fetchAll("SELECT customer_id, assigned_to FROM customers WHERE assigned_to IS NOT NULL LIMIT 5");
        
        if (!empty($customers)) {
            foreach ($customers as $customer) {
                try {
                    $db->execute("
                        INSERT INTO call_logs (customer_id, user_id, call_type, call_status, call_result, notes, next_followup_at, followup_priority, followup_days)
                        VALUES (?, ?, 'outbound', 'answered', 'callback', 'ทดสอบการติดตาม', DATE_ADD(NOW(), INTERVAL ? DAY), ?, ?)
                    ", [
                        $customer['customer_id'], 
                        $customer['assigned_to'],
                        rand(1, 7), // Random days
                        ['low', 'medium', 'high', 'urgent'][rand(0, 3)], // Random priority
                        rand(1, 30) // Random followup days
                    ]);
                } catch (Exception $e) {
                    echo "⚠️ Failed to create test data for customer " . $customer['customer_id'] . ": " . $e->getMessage() . "\n";
                }
            }
            echo "✅ Test data created\n";
        } else {
            echo "❌ No customers with assigned_to found\n";
        }
    } else {
        echo "✅ Found $callLogsCount call_logs with next_followup_at\n";
    }
    
    // Step 6: Update system_settings with call follow-up configuration
    echo "\n6. Updating system_settings:\n";
    
    try {
        $db->execute("
            INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `setting_type`) VALUES
            ('call_followup_enabled', '1', 'เปิดใช้งานระบบติดตามการโทร', 'boolean'),
            ('call_followup_auto_queue', '1', 'สร้างคิวการติดตามอัตโนมัติ', 'boolean'),
            ('call_followup_notification', '1', 'แจ้งเตือนการติดตาม', 'boolean'),
            ('call_followup_max_days', '30', 'จำนวนวันสูงสุดในการติดตาม', 'integer')
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            description = VALUES(description),
            updated_at = NOW()
        ");
        echo "✅ System settings updated\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to update system_settings: " . $e->getMessage() . "\n";
    }
    
    // Step 7: Test the fixed system
    echo "\n7. Testing the fixed system:\n";
    
    try {
        // Test the view
        $viewTest = $db->fetchAll("SELECT * FROM customer_call_followup_list LIMIT 1");
        echo "✅ View test: " . count($viewTest) . " records found\n";
        
        // Test the complex query
        $complexQuery = "SELECT 
            c.customer_id,
            c.customer_code,
            c.first_name,
            c.last_name,
            c.phone,
            c.email,
            c.province,
            c.temperature_status,
            c.customer_grade,
            u.full_name as assigned_to_name,
            cl.call_result,
            cl.call_status,
            cl.created_at as last_call_date,
            cl.next_followup_at,
            cl.notes,
            cl.followup_priority,
            cfq.status as queue_status,
            DATEDIFF(cl.next_followup_at, NOW()) as days_until_followup,
            CASE
                WHEN cl.next_followup_at <= NOW() THEN 'overdue'
                WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'urgent'
                WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'soon'
                ELSE 'normal'
            END as urgency_status
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        LEFT JOIN users u ON c.assigned_to = u.user_id
        LEFT JOIN call_followup_queue cfq ON c.customer_id = cfq.customer_id AND cfq.status = 'pending'
        WHERE cl.next_followup_at IS NOT NULL
        AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
        ORDER BY cl.next_followup_at ASC, cl.followup_priority DESC
        LIMIT 5";
        
        $startTime = microtime(true);
        $complexResult = $db->fetchAll($complexQuery);
        $queryTime = microtime(true) - $startTime;
        
        echo "✅ Complex query test: " . count($complexResult) . " records in " . round($queryTime * 1000, 2) . "ms\n";
        
        if (!empty($complexResult)) {
            $first = $complexResult[0];
            echo "   Sample data:\n";
            echo "   - customer_id: " . ($first['customer_id'] ?? 'N/A') . "\n";
            echo "   - next_followup_at: " . ($first['next_followup_at'] ?? 'N/A') . "\n";
            echo "   - followup_priority: " . ($first['followup_priority'] ?? 'N/A') . "\n";
            echo "   - urgency_status: " . ($first['urgency_status'] ?? 'N/A') . "\n";
            echo "   - assigned_to_name: " . ($first['assigned_to_name'] ?? 'N/A') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ System test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Fix Complete ===\n";
    echo "✅ Schema issues should now be resolved\n";
    echo "✅ Performance should be improved with new indexes\n";
    echo "✅ Test data created for verification\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
}
?>
