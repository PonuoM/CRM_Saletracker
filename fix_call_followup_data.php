<?php
/**
 * Fix Call Followup Data
 * แก้ไขข้อมูล call_logs ที่ไม่มี next_followup_at
 */

require_once __DIR__ . '/app/core/Database.php';

echo "<h2>การแก้ไขข้อมูล Call Followup</h2>\n";

try {
    $db = new Database();
    
    echo "<h3>1. ตรวจสอบ call_logs ที่ไม่มี next_followup_at</h3>\n";
    
    // ตรวจสอบ call_logs ที่ไม่มี next_followup_at
    $callLogsWithoutFollowup = $db->fetchAll(
        "SELECT cl.*, c.first_name, c.last_name 
         FROM call_logs cl
         JOIN customers c ON cl.customer_id = c.customer_id
         WHERE cl.next_followup_at IS NULL 
         AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
         ORDER BY cl.created_at DESC"
    );
    
    echo "<p>พบ call_logs ที่ไม่มี next_followup_at: " . count($callLogsWithoutFollowup) . " รายการ</p>\n";
    
    foreach ($callLogsWithoutFollowup as $log) {
        echo "- {$log['first_name']} {$log['last_name']}<br>\n";
        echo "  - call_result: {$log['call_result']}<br>\n";
        echo "  - created_at: {$log['created_at']}<br>\n";
        echo "  - log_id: {$log['log_id']}<br>\n";
    }
    
    echo "<h3>2. กำหนด next_followup_at ตาม call_result</h3>\n";
    
    $updateCount = 0;
    
    foreach ($callLogsWithoutFollowup as $log) {
        $nextFollowupAt = null;
        
        // กำหนดวันติดตามตาม call_result
        switch ($log['call_result']) {
            case 'callback':
                // callback = ติดตามใน 3 วัน
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +3 days'));
                break;
            case 'interested':
                // interested = ติดตามใน 7 วัน
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +7 days'));
                break;
            case 'not_interested':
                // not_interested = ติดตามใน 30 วัน
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +30 days'));
                break;
            case 'complaint':
                // complaint = ติดตามใน 1 วัน
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +1 day'));
                break;
        }
        
        if ($nextFollowupAt) {
            // อัปเดต next_followup_at
            $result = $db->execute(
                "UPDATE call_logs SET next_followup_at = ? WHERE log_id = ?",
                [$nextFollowupAt, $log['log_id']]
            );
            
            if ($result) {
                $updateCount++;
                echo "✅ อัปเดต log_id {$log['log_id']} ({$log['first_name']} {$log['last_name']})<br>\n";
                echo "   - call_result: {$log['call_result']}<br>\n";
                echo "   - next_followup_at: {$nextFollowupAt}<br>\n";
            } else {
                echo "❌ ไม่สามารถอัปเดต log_id {$log['log_id']}<br>\n";
            }
        }
    }
    
    echo "<h3>3. ผลการอัปเดต</h3>\n";
    echo "<p>อัปเดตสำเร็จ: {$updateCount} รายการ</p>\n";
    
    echo "<h3>4. ตรวจสอบผลลัพธ์หลังการแก้ไข</h3>\n";
    
    // ตรวจสอบ call_logs ที่มี next_followup_at แล้ว
    $callLogsWithFollowup = $db->fetchAll(
        "SELECT cl.*, c.first_name, c.last_name 
         FROM call_logs cl
         JOIN customers c ON cl.customer_id = c.customer_id
         WHERE cl.next_followup_at IS NOT NULL 
         AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
         ORDER BY cl.next_followup_at ASC"
    );
    
    echo "<p>call_logs ที่มี next_followup_at: " . count($callLogsWithFollowup) . " รายการ</p>\n";
    
    foreach ($callLogsWithFollowup as $log) {
        echo "- {$log['first_name']} {$log['last_name']}<br>\n";
        echo "  - call_result: {$log['call_result']}<br>\n";
        echo "  - next_followup_at: {$log['next_followup_at']}<br>\n";
        echo "  - created_at: {$log['created_at']}<br>\n";
    }
    
    echo "<h3>5. ทดสอบเงื่อนไขใหม่</h3>\n";
    
    // ทดสอบเงื่อนไขใหม่
    $newCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM call_logs WHERE user_id = 6 AND next_followup_at IS NOT NULL AND call_result IN ('not_interested', 'callback', 'interested', 'complaint')"
    );
    
    echo "<p>เงื่อนไขใหม่ (KPI Card): " . $newCount['count'] . "</p>\n";
    
    // ทดสอบเงื่อนไขตาราง
    $tableCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM call_logs cl
         JOIN customers c ON cl.customer_id = c.customer_id
         WHERE cl.user_id = 6 
         AND cl.next_followup_at IS NOT NULL
         AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')"
    );
    
    echo "<p>เงื่อนไขตาราง: " . $tableCount['count'] . "</p>\n";
    
    if ($newCount['count'] == $tableCount['count']) {
        echo "<p style='color: green;'>✅ การแก้ไขสำเร็จ! KPI Card และตารางจะแสดงจำนวนเดียวกัน</p>\n";
    } else {
        echo "<p style='color: red;'>❌ ยังมีปัญหา</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
