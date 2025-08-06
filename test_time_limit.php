<?php
/**
 * ทดสอบการจำกัดเวลาไม่เกิน 90 วัน
 * ตรวจสอบว่าเมื่อสร้างคำสั่งซื้อแล้ว เวลาจะไม่เกิน 90 วันจากวันปัจจุบัน
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/WorkflowService.php';
require_once __DIR__ . '/app/services/OrderService.php';

echo "🧪 ทดสอบการจำกัดเวลาไม่เกิน 90 วัน\n";

try {
    $db = new Database();
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n\n";
    
    $workflowService = new WorkflowService();
    $orderService = new OrderService();
    
    // 1. เลือกลูกค้าที่มี basket_type = 'waiting' เพื่อทดสอบ
    $testCustomer = $db->fetchOne("
        SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, 
               basket_type, assigned_at, customer_time_expiry, customer_time_extension
        FROM customers 
        WHERE basket_type = 'waiting' AND assigned_at IS NOT NULL
        LIMIT 1
    ");
    
    if (!$testCustomer) {
        echo "❌ ไม่พบลูกค้าที่มี basket_type = 'waiting' สำหรับทดสอบ\n";
        exit;
    }
    
    echo "🧪 ทดสอบกับลูกค้า: {$testCustomer['customer_name']} (ID: {$testCustomer['customer_id']})\n";
    echo "basket_type: {$testCustomer['basket_type']}\n\n";
    
    // 2. ข้อมูลก่อนการสร้างคำสั่งซื้อ
    echo "📅 ข้อมูลก่อนการสร้างคำสั่งซื้อ:\n";
    echo "- assigned_at: {$testCustomer['assigned_at']}\n";
    echo "- customer_time_expiry: {$testCustomer['customer_time_expiry']}\n";
    echo "- customer_time_extension: {$testCustomer['customer_time_extension']}\n\n";
    
    // 3. คำนวณวันที่คาดหวัง (90 วันจากวันนี้)
    $expectedExpiry = date('Y-m-d H:i:s', strtotime('+90 days'));
    echo "📅 วันที่คาดหวัง (90 วันจากวันนี้): {$expectedExpiry}\n\n";
    
    // 4. สร้างคำสั่งซื้อจำลอง
    $orderData = [
        'customer_id' => $testCustomer['customer_id'],
        'order_date' => date('Y-m-d H:i:s'),
        'total_amount' => 1000,
        'discount_amount' => 0,
        'net_amount' => 1000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'delivery_status' => 'pending',
        'notes' => 'ทดสอบการจำกัดเวลา 90 วัน'
    ];
    
    $orderItems = [
        [
            'product_id' => 1,
            'quantity' => 1,
            'unit_price' => 1000
        ]
    ];
    
    echo "🔄 กำลังสร้างคำสั่งซื้อ...\n";
    $orderResult = $orderService->createOrder($orderData, $orderItems, 1);
    
    echo "📋 ผลการสร้างคำสั่งซื้อ:\n";
    echo "- success: " . ($orderResult['success'] ? 'true' : 'false') . "\n";
    echo "- message: {$orderResult['message']}\n";
    if (isset($orderResult['order_id'])) {
        echo "- order_id: {$orderResult['order_id']}\n";
    }
    echo "\n";
    
    // 5. ข้อมูลหลังการสร้างคำสั่งซื้อ
    $afterOrder = $db->fetchOne("
        SELECT assigned_at, customer_time_expiry, customer_time_extension
        FROM customers WHERE customer_id = ?
    ", [$testCustomer['customer_id']]);
    
    echo "📅 ข้อมูลหลังการสร้างคำสั่งซื้อ:\n";
    echo "- assigned_at: {$afterOrder['assigned_at']}\n";
    echo "- customer_time_expiry: {$afterOrder['customer_time_expiry']}\n";
    echo "- customer_time_extension: {$afterOrder['customer_time_extension']}\n\n";
    
    // 6. ตรวจสอบการจำกัดเวลา
    echo "🔍 ผลการตรวจสอบการจำกัดเวลา:\n";
    
    // ตรวจสอบว่า assigned_at เปลี่ยนแล้ว
    $assignedChanged = $testCustomer['assigned_at'] !== $afterOrder['assigned_at'];
    if ($assignedChanged) {
        echo "✅ assigned_at เปลี่ยนแล้ว\n";
    } else {
        echo "❌ assigned_at ไม่เปลี่ยน\n";
    }
    
    // ตรวจสอบว่า customer_time_expiry ไม่เกิน 90 วัน
    $currentDate = date('Y-m-d H:i:s');
    $expiryDate = $afterOrder['customer_time_expiry'];
    $daysDifference = (strtotime($expiryDate) - strtotime($currentDate)) / (60 * 60 * 24);
    
    echo "📊 จำนวนวันที่เหลือ: " . round($daysDifference, 1) . " วัน\n";
    
    if ($daysDifference <= 90) {
        echo "✅ customer_time_expiry ไม่เกิน 90 วัน (ถูกต้อง)\n";
    } else {
        echo "❌ customer_time_expiry เกิน 90 วัน (ไม่ถูกต้อง)\n";
    }
    
    // ตรวจสอบว่า customer_time_extension เป็น 90
    if ($afterOrder['customer_time_extension'] == 90) {
        echo "✅ customer_time_extension ถูกตั้งค่าเป็น 90 (ถูกต้อง)\n";
    } else {
        echo "❌ customer_time_extension ไม่ถูกต้อง (คาดหวัง: 90, ได้: {$afterOrder['customer_time_extension']})\n";
    }
    
    // 7. สรุปผล
    echo "\n📊 สรุปผลการทดสอบ:\n";
    if ($assignedChanged && $daysDifference <= 90 && $afterOrder['customer_time_extension'] == 90) {
        echo "🎉 การจำกัดเวลา 90 วันทำงานได้สำเร็จ!\n";
        echo "✅ เวลาจะไม่เกิน 90 วันจากวันปัจจุบัน\n";
        echo "✅ ไม่มีการสแต็กเวลา\n";
    } else {
        echo "⚠️ การจำกัดเวลา 90 วันยังไม่ทำงานสมบูรณ์\n";
        echo "❌ ต้องตรวจสอบการแก้ไขเพิ่มเติม\n";
    }
    
    echo "\n✅ การทดสอบเสร็จสิ้น\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    error_log("Test error: " . $e->getMessage());
}
?> 