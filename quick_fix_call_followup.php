<?php
/**
 * Quick Fix Call Followup Data
 * แก้ไขข้อมูล call_logs ที่ไม่มี next_followup_at แบบง่ายๆ
 */

// ใช้ config ที่ถูกต้อง
require_once __DIR__ . '/config/config.php';

try {
    // ใช้ PDO กับ config ที่ถูกต้อง
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>การแก้ไขข้อมูล Call Followup</h2>\n";
    echo "<p>Environment: " . ENVIRONMENT . "</p>\n";
    echo "<p>Database: " . DB_NAME . " on " . DB_HOST . ":" . DB_PORT . "</p>\n";
    
    // 1. ตรวจสอบ call_logs ที่ไม่มี next_followup_at
    $stmt = $pdo->prepare("
        SELECT cl.*, c.first_name, c.last_name 
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        WHERE cl.next_followup_at IS NULL 
        AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
        ORDER BY cl.created_at DESC
    ");
    $stmt->execute();
    $callLogsWithoutFollowup = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>1. ตรวจสอบ call_logs ที่ไม่มี next_followup_at</h3>\n";
    echo "<p>พบ call_logs ที่ไม่มี next_followup_at: " . count($callLogsWithoutFollowup) . " รายการ</p>\n";
    
    foreach ($callLogsWithoutFollowup as $log) {
        echo "- {$log['first_name']} {$log['last_name']}<br>\n";
        echo "  - call_result: {$log['call_result']}<br>\n";
        echo "  - created_at: {$log['created_at']}<br>\n";
        echo "  - log_id: {$log['log_id']}<br>\n";
    }
    
    // 2. อัปเดต next_followup_at
    echo "<h3>2. อัปเดต next_followup_at</h3>\n";
    
    $updateCount = 0;
    $updateStmt = $pdo->prepare("UPDATE call_logs SET next_followup_at = ? WHERE log_id = ?");
    
    foreach ($callLogsWithoutFollowup as $log) {
        $nextFollowupAt = null;
        
        // กำหนดวันติดตามตาม call_result
        switch ($log['call_result']) {
            case 'callback':
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +3 days'));
                break;
            case 'interested':
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +7 days'));
                break;
            case 'not_interested':
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +30 days'));
                break;
            case 'complaint':
                $nextFollowupAt = date('Y-m-d H:i:s', strtotime($log['created_at'] . ' +1 day'));
                break;
        }
        
        if ($nextFollowupAt) {
            $result = $updateStmt->execute([$nextFollowupAt, $log['log_id']]);
            
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
    
    // 4. ตรวจสอบผลลัพธ์
    echo "<h3>4. ตรวจสอบผลลัพธ์หลังการแก้ไข</h3>\n";
    
    $stmt = $pdo->prepare("
        SELECT cl.*, c.first_name, c.last_name 
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        WHERE cl.next_followup_at IS NOT NULL 
        AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
        ORDER BY cl.next_followup_at ASC
    ");
    $stmt->execute();
    $callLogsWithFollowup = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>call_logs ที่มี next_followup_at: " . count($callLogsWithFollowup) . " รายการ</p>\n";
    
    foreach ($callLogsWithFollowup as $log) {
        echo "- {$log['first_name']} {$log['last_name']}<br>\n";
        echo "  - call_result: {$log['call_result']}<br>\n";
        echo "  - next_followup_at: {$log['next_followup_at']}<br>\n";
        echo "  - created_at: {$log['created_at']}<br>\n";
    }
    
    // 5. ทดสอบเงื่อนไขใหม่
    echo "<h3>5. ทดสอบเงื่อนไขใหม่</h3>\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM call_logs 
        WHERE user_id = 6 
        AND next_followup_at IS NOT NULL 
        AND call_result IN ('not_interested', 'callback', 'interested', 'complaint')
    ");
    $stmt->execute();
    $newCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>เงื่อนไขใหม่ (KPI Card): " . $newCount['count'] . "</p>\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        WHERE cl.user_id = 6 
        AND cl.next_followup_at IS NOT NULL
        AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
    ");
    $stmt->execute();
    $tableCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>เงื่อนไขตาราง: " . $tableCount['count'] . "</p>\n";
    
    if ($newCount['count'] == $tableCount['count']) {
        echo "<p style='color: green;'>✅ การแก้ไขสำเร็จ! KPI Card และตารางจะแสดงจำนวนเดียวกัน</p>\n";
    } else {
        echo "<p style='color: red;'>❌ ยังมีปัญหา</p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Config: " . DB_HOST . ":" . DB_PORT . " - " . DB_NAME . " - " . DB_USER . "</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
