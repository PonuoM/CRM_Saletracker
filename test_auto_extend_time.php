<?php
/**
 * ทดสอบการต่อเวลาอัตโนมัติเมื่อสร้างคำสั่งซื้อ
 */

require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/OrderService.php';
require_once 'app/services/WorkflowService.php';

echo "<h2>🧪 ทดสอบการต่อเวลาอัตโนมัติเมื่อสร้างคำสั่งซื้อ</h2>\n";

try {
    $db = new Database();
    $orderService = new OrderService();
    $workflowService = new WorkflowService();
    
    echo "<h3>1. ตรวจสอบโครงสร้างตาราง customers</h3>\n";
    
    // ตรวจสอบคอลัมน์ที่จำเป็น
    $columns = $db->fetchAll("DESCRIBE customers");
    $requiredColumns = ['customer_id', 'assigned_at', 'customer_time_expiry', 'customer_time_extension'];
    $missingColumns = [];
    
    $existingColumns = [];
    foreach ($columns as $col) {
        $existingColumns[] = $col['Field'];
    }
    
    foreach ($requiredColumns as $required) {
        if (!in_array($required, $existingColumns)) {
            $missingColumns[] = $required;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✅ คอลัมน์ที่จำเป็นครบถ้วน\n";
    } else {
        echo "❌ คอลัมน์ที่ขาดหายไป: " . implode(', ', $missingColumns) . "\n";
    }
    
    echo "<h3>2. ตรวจสอบข้อมูลลูกค้าทดสอบ</h3>\n";
    
    // หาลูกค้าที่มี assigned_at
    $testCustomer = $db->fetchOne(
        "SELECT customer_id, first_name, last_name, assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE assigned_at IS NOT NULL 
         LIMIT 1"
    );
    
    if ($testCustomer) {
        echo "✅ พบลูกค้าทดสอบ: {$testCustomer['first_name']} {$testCustomer['last_name']} (ID: {$testCustomer['customer_id']})\n";
        echo "   - assigned_at: {$testCustomer['assigned_at']}\n";
        echo "   - customer_time_expiry: {$testCustomer['customer_time_expiry']}\n";
        echo "   - customer_time_extension: {$testCustomer['customer_time_extension']}\n";
        
        $customerId = $testCustomer['customer_id'];
    } else {
        echo "❌ ไม่พบลูกค้าที่มี assigned_at\n";
        exit;
    }
    
    echo "<h3>3. ทดสอบฟังก์ชัน autoExtendTimeOnActivity</h3>\n";
    
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
    
    echo "<h3>4. ทดสอบการสร้างคำสั่งซื้อจำลอง</h3>\n";
    
    // สร้างข้อมูลคำสั่งซื้อจำลอง
    $orderData = [
        'customer_id' => $customerId,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'delivery_status' => 'pending',
        'discount_percentage' => 0,
        'notes' => 'ทดสอบการต่อเวลาอัตโนมัติ'
    ];
    
    $orderItems = [
        [
            'product_id' => 1,
            'quantity' => 1,
            'unit_price' => 1000
        ]
    ];
    
    // บันทึกข้อมูลก่อนการสร้างคำสั่งซื้อ
    $beforeOrderData = $db->fetchOne(
        "SELECT assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE customer_id = ?",
        [$customerId]
    );
    
    echo "📅 ข้อมูลก่อนการสร้างคำสั่งซื้อ:\n";
    echo "   - assigned_at: {$beforeOrderData['assigned_at']}\n";
    echo "   - customer_time_expiry: {$beforeOrderData['customer_time_expiry']}\n";
    echo "   - customer_time_extension: {$beforeOrderData['customer_time_extension']}\n";
    
    // สร้างคำสั่งซื้อ
    $orderResult = $orderService->createOrder($orderData, $orderItems, 1);
    
    echo "🔄 ผลการสร้างคำสั่งซื้อ:\n";
    echo "   - success: " . ($orderResult['success'] ? 'true' : 'false') . "\n";
    echo "   - message: {$orderResult['message']}\n";
    
    if ($orderResult['success']) {
        echo "   - order_id: {$orderResult['order_id']}\n";
        echo "   - order_number: {$orderResult['order_number']}\n";
    }
    
    // ตรวจสอบข้อมูลหลังการสร้างคำสั่งซื้อ
    $afterOrderData = $db->fetchOne(
        "SELECT assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE customer_id = ?",
        [$customerId]
    );
    
    echo "📅 ข้อมูลหลังการสร้างคำสั่งซื้อ:\n";
    echo "   - assigned_at: {$afterOrderData['assigned_at']}\n";
    echo "   - customer_time_expiry: {$afterOrderData['customer_time_expiry']}\n";
    echo "   - customer_time_extension: {$afterOrderData['customer_time_extension']}\n";
    
    // ตรวจสอบการเปลี่ยนแปลง
    if ($beforeOrderData['assigned_at'] !== $afterOrderData['assigned_at']) {
        echo "✅ assigned_at เปลี่ยนแล้ว (ผ่านการสร้างคำสั่งซื้อ)\n";
    } else {
        echo "❌ assigned_at ไม่เปลี่ยน (การสร้างคำสั่งซื้อ)\n";
    }
    
    if ($beforeOrderData['customer_time_expiry'] !== $afterOrderData['customer_time_expiry']) {
        echo "✅ customer_time_expiry เปลี่ยนแล้ว (ผ่านการสร้างคำสั่งซื้อ)\n";
    } else {
        echo "❌ customer_time_expiry ไม่เปลี่ยน (การสร้างคำสั่งซื้อ)\n";
    }
    
    if ($beforeOrderData['customer_time_extension'] !== $afterOrderData['customer_time_extension']) {
        echo "✅ customer_time_extension เปลี่ยนแล้ว (ผ่านการสร้างคำสั่งซื้อ)\n";
    } else {
        echo "❌ customer_time_extension ไม่เปลี่ยน (การสร้างคำสั่งซื้อ)\n";
    }
    
    echo "<h3>5. ตรวจสอบ Log กิจกรรม</h3>\n";
    
    $activities = $db->fetchAll(
        "SELECT * FROM customer_activities 
         WHERE customer_id = ? 
         ORDER BY created_at DESC 
         LIMIT 5",
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
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 