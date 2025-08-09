<?php
require_once 'config/config.php';

echo "=== Debug Call Follow-up Data ===\n\n";

try {
    $db = new Database();
    
    // 1. ตรวจสอบข้อมูล call_logs ที่มี next_followup_at
    echo "1. ตรวจสอบ call_logs ที่มี next_followup_at:\n";
    $callLogs = $db->fetchAll("
        SELECT 
            cl.log_id,
            cl.customer_id,
            cl.call_result,
            cl.next_followup_at,
            cl.followup_priority,
            c.first_name,
            c.last_name,
            c.assigned_to,
            u.full_name as assigned_to_name
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        LEFT JOIN users u ON c.assigned_to = u.user_id
        WHERE cl.next_followup_at IS NOT NULL
        ORDER BY cl.next_followup_at ASC
    ");
    
    echo "พบ call_logs: " . count($callLogs) . " รายการ\n";
    foreach ($callLogs as $log) {
        echo "- customer_id: {$log['customer_id']}, name: {$log['first_name']} {$log['last_name']}, assigned_to: {$log['assigned_to_name']}, next_followup_at: {$log['next_followup_at']}, priority: {$log['followup_priority']}\n";
    }
    
    // 2. ตรวจสอบ view customer_call_followup_list
    echo "\n2. ตรวจสอบ view customer_call_followup_list:\n";
    try {
        $viewData = $db->fetchAll("SELECT * FROM customer_call_followup_list LIMIT 10");
        echo "พบข้อมูลใน view: " . count($viewData) . " รายการ\n";
        foreach ($viewData as $row) {
            echo "- customer_id: {$row['customer_id']}, name: {$row['first_name']} {$row['last_name']}, next_followup_at: {$row['next_followup_at']}, urgency_status: {$row['urgency_status']}\n";
        }
    } catch (Exception $e) {
        echo "❌ View ไม่มีอยู่: " . $e->getMessage() . "\n";
    }
    
    // 3. ตรวจสอบ API endpoint
    echo "\n3. ตรวจสอบ API endpoint:\n";
    try {
        // Simulate API call
        $apiQuery = "
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
        ";
        
        $apiResult = $db->fetchAll($apiQuery);
        echo "API query result: " . count($apiResult) . " รายการ\n";
        foreach ($apiResult as $row) {
            echo "- customer_id: {$row['customer_id']}, name: {$row['first_name']} {$row['last_name']}, next_followup_at: {$row['next_followup_at']}, urgency_status: {$row['urgency_status']}, assigned_to_name: {$row['assigned_to_name']}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ API query failed: " . $e->getMessage() . "\n";
    }
    
    // 4. ตรวจสอบการนับจำนวน
    echo "\n4. ตรวจสอบการนับจำนวน:\n";
    
    $totalCalls = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs")['count'];
    $contactedCalls = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE call_result = 'order'")['count'];
    $followupCalls = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE next_followup_at IS NOT NULL AND call_result IN ('not_interested', 'callback', 'interested', 'complaint')")['count'];
    $overdueCalls = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE next_followup_at <= NOW() AND call_result IN ('not_interested', 'callback', 'interested', 'complaint')")['count'];
    
    echo "Total calls: $totalCalls\n";
    echo "Contacted calls: $contactedCalls\n";
    echo "Follow-up calls: $followupCalls\n";
    echo "Overdue calls: $overdueCalls\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
