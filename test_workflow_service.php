<?php
/**
 * ทดสอบ WorkflowService โดยเฉพาะ
 */

require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/WorkflowService.php';

echo "<h2>🧪 ทดสอบ WorkflowService</h2>\n";

try {
    $db = new Database();
    $workflowService = new WorkflowService();
    
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n";
    
    // 1. ทดสอบการดึงข้อมูลลูกค้า
    echo "<h3>1. ทดสอบการดึงข้อมูลลูกค้า</h3>\n";
    
    $customers = $db->fetchAll(
        "SELECT customer_id, first_name, last_name, basket_type, assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE assigned_at IS NOT NULL 
         LIMIT 3"
    );
    
    if ($customers) {
        echo "✅ พบลูกค้าที่มี assigned_at: " . count($customers) . " ราย\n";
        foreach ($customers as $customer) {
            echo "   - {$customer['first_name']} {$customer['last_name']} (ID: {$customer['customer_id']})\n";
            echo "     basket_type: {$customer['basket_type']}\n";
            echo "     assigned_at: {$customer['assigned_at']}\n";
            echo "     customer_time_expiry: {$customer['customer_time_expiry']}\n";
            echo "     customer_time_extension: {$customer['customer_time_extension']}\n";
        }
    } else {
        echo "❌ ไม่พบลูกค้าที่มี assigned_at\n";
        exit;
    }
    
    // 2. ทดสอบฟังก์ชัน autoExtendTimeOnActivity
    echo "<h3>2. ทดสอบฟังก์ชัน autoExtendTimeOnActivity</h3>\n";
    
    $testCustomer = $customers[0];
    $customerId = $testCustomer['customer_id'];
    
    echo "🧪 ทดสอบกับลูกค้า: {$testCustomer['first_name']} {$testCustomer['last_name']} (ID: {$customerId})\n";
    
    // บันทึกข้อมูลก่อนการทดสอบ
    $beforeData = $db->fetchOne(
        "SELECT assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE customer_id = ?",
        [$customerId]
    );
    
    echo "📅 ข้อมูลก่อนการทดสอบ:\n";
    echo "   - assigned_at: {$beforeData['assigned_at']}\n";
    echo "   - customer_time_expiry: {$beforeData['customer_time_expiry']}\n";
    echo "   - customer_time_extension: {$beforeData['customer_time_extension']}\n";
    
    // ทดสอบฟังก์ชัน autoExtendTimeOnActivity
    $result = $workflowService->autoExtendTimeOnActivity($customerId, 'order', 1);
    
    echo "🔄 ผลการทดสอบ autoExtendTimeOnActivity:\n";
    echo "   - success: " . ($result['success'] ? 'true' : 'false') . "\n";
    echo "   - message: {$result['message']}\n";
    
    if (isset($result['extension_days'])) {
        echo "   - extension_days: {$result['extension_days']}\n";
    }
    
    // ตรวจสอบข้อมูลหลังการทดสอบ
    $afterData = $db->fetchOne(
        "SELECT assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE customer_id = ?",
        [$customerId]
    );
    
    echo "📅 ข้อมูลหลังการทดสอบ:\n";
    echo "   - assigned_at: {$afterData['assigned_at']}\n";
    echo "   - customer_time_expiry: {$afterData['customer_time_expiry']}\n";
    echo "   - customer_time_extension: {$afterData['customer_time_extension']}\n";
    
    // ตรวจสอบการเปลี่ยนแปลง
    if ($beforeData['assigned_at'] !== $afterData['assigned_at']) {
        echo "✅ assigned_at เปลี่ยนแล้ว\n";
    } else {
        echo "❌ assigned_at ไม่เปลี่ยน\n";
    }
    
    if ($beforeData['customer_time_expiry'] !== $afterData['customer_time_expiry']) {
        echo "✅ customer_time_expiry เปลี่ยนแล้ว\n";
    } else {
        echo "❌ customer_time_expiry ไม่เปลี่ยน\n";
    }
    
    if ($beforeData['customer_time_extension'] !== $afterData['customer_time_extension']) {
        echo "✅ customer_time_extension เปลี่ยนแล้ว\n";
    } else {
        echo "❌ customer_time_extension ไม่เปลี่ยน\n";
    }
    
    // 3. ทดสอบการบันทึกกิจกรรม
    echo "<h3>3. ตรวจสอบการบันทึกกิจกรรม</h3>\n";
    
    $activities = $db->fetchAll(
        "SELECT * FROM customer_activities 
         WHERE customer_id = ? 
         ORDER BY created_at DESC 
         LIMIT 3",
        [$customerId]
    );
    
    if ($activities) {
        echo "📋 กิจกรรมล่าสุด:\n";
        foreach ($activities as $activity) {
            echo "   - {$activity['activity_type']}: {$activity['description']} ({$activity['created_at']})\n";
        }
    } else {
        echo "❌ ไม่พบกิจกรรม\n";
    }
    
    // 4. ทดสอบฟังก์ชัน getCustomersForExtension
    echo "<h3>4. ทดสอบฟังก์ชัน getCustomersForExtension</h3>\n";
    
    $extensionCustomers = $workflowService->getCustomersForExtension();
    
    if ($extensionCustomers) {
        echo "✅ พบลูกค้าที่พร้อมต่อเวลา: " . count($extensionCustomers) . " ราย\n";
        foreach (array_slice($extensionCustomers, 0, 3) as $customer) {
            echo "   - {$customer['customer_name']} (ID: {$customer['customer_id']})\n";
        }
    } else {
        echo "❌ ไม่พบลูกค้าที่พร้อมต่อเวลา\n";
    }
    
    // 5. ทดสอบฟังก์ชัน getWorkflowStats
    echo "<h3>5. ทดสอบฟังก์ชัน getWorkflowStats</h3>\n";
    
    $stats = $workflowService->getWorkflowStats();
    
    echo "📊 สถิติ Workflow:\n";
    echo "   - pending_recall: {$stats['pending_recall']}\n";
    echo "   - new_customer_timeout: {$stats['new_customer_timeout']}\n";
    echo "   - existing_customer_timeout: {$stats['existing_customer_timeout']}\n";
    echo "   - active_today: {$stats['active_today']}\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 