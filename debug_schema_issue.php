<?php
require_once 'config/config.php';

echo "=== Debug Schema Issue ===\n\n";

try {
    $db = new Database();
    
    // 1. Check call_logs table structure
    echo "1. Checking call_logs table structure:\n";
    $columns = $db->fetchAll("DESCRIBE call_logs");
    $columnNames = array_column($columns, 'Field');
    
    echo "Available columns: " . implode(', ', $columnNames) . "\n\n";
    
    // Check if followup_priority exists
    if (in_array('followup_priority', $columnNames)) {
        echo "✅ followup_priority column exists\n";
    } else {
        echo "❌ followup_priority column missing\n";
    }
    
    // 2. Check if customer_call_followup_list view exists
    echo "\n2. Checking customer_call_followup_list view:\n";
    try {
        $view = $db->fetchAll("SELECT * FROM customer_call_followup_list LIMIT 1");
        echo "✅ customer_call_followup_list view exists\n";
    } catch (Exception $e) {
        echo "❌ customer_call_followup_list view missing: " . $e->getMessage() . "\n";
    }
    
    // 3. Test the exact query that's failing
    echo "\n3. Testing the failing query:\n";
    try {
        $testQuery = "SELECT 
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
        LIMIT 1";
        
        $result = $db->fetchAll($testQuery);
        echo "✅ Query executed successfully\n";
        echo "Found " . count($result) . " records\n";
        
        if (!empty($result)) {
            $first = $result[0];
            echo "Sample data:\n";
            echo "- customer_id: " . ($first['customer_id'] ?? 'N/A') . "\n";
            echo "- followup_priority: " . ($first['followup_priority'] ?? 'N/A') . "\n";
            echo "- next_followup_at: " . ($first['next_followup_at'] ?? 'N/A') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Query failed: " . $e->getMessage() . "\n";
    }
    
    // 4. Check if we need to add the missing column
    echo "\n4. Adding missing column if needed:\n";
    if (!in_array('followup_priority', $columnNames)) {
        try {
            $db->execute("ALTER TABLE call_logs ADD COLUMN followup_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม'");
            echo "✅ Added followup_priority column\n";
        } catch (Exception $e) {
            echo "❌ Failed to add column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ Column already exists\n";
    }
    
    // 5. Test the query again after adding column
    echo "\n5. Testing query again:\n";
    try {
        $testQuery2 = "SELECT followup_priority FROM call_logs LIMIT 1";
        $result2 = $db->fetchAll($testQuery2);
        echo "✅ followup_priority column is accessible\n";
    } catch (Exception $e) {
        echo "❌ Still can't access followup_priority: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
