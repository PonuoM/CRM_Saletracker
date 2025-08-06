<?php
/**
 * ทดสอบการแก้ไขปัญหาการต่อเวลาอัตโนมัติ
 * ตรวจสอบว่า autoExtendTimeOnActivity ทำงานได้กับลูกค้าทุกประเภท basket_type เมื่อสร้างคำสั่งซื้อ
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/WorkflowService.php';
require_once __DIR__ . '/app/services/OrderService.php';

echo "🧪 ทดสอบการแก้ไขปัญหาการต่อเวลาอัตโนมัติ\n";

try {
    $db = new Database();
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n\n";
    
    $workflowService = new WorkflowService();
    $orderService = new OrderService();
    
    // 1. ตรวจสอบลูกค้าที่มี basket_type ต่างๆ
    echo "1. ตรวจสอบลูกค้าที่มี basket_type ต่างๆ\n";
    $customers = $db->fetchAll("
        SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, 
               basket_type, assigned_at, customer_time_expiry, customer_time_extension
        FROM customers 
        WHERE assigned_at IS NOT NULL 
        ORDER BY customer_id
    ");
    
    foreach ($customers as $customer) {
        echo "- {$customer['customer_name']} (ID: {$customer['customer_id']}) basket_type: {$customer['basket_type']}\n";
    }
    echo "\n";
    
    // 2. ทดสอบ autoExtendTimeOnActivity กับลูกค้าแต่ละประเภท
    echo "2. ทดสอบ autoExtendTimeOnActivity กับลูกค้าแต่ละประเภท\n";
    
    foreach ($customers as $customer) {
        echo "🧪 ทดสอบกับลูกค้า: {$customer['customer_name']} (ID: {$customer['customer_id']}) basket_type: {$customer['basket_type']}\n";
        
        // ข้อมูลก่อนการทดสอบ
        echo "📅 ข้อมูลก่อนการทดสอบ:\n";
        echo "- assigned_at: {$customer['assigned_at']}\n";
        echo "- customer_time_expiry: {$customer['customer_time_expiry']}\n";
        echo "- customer_time_extension: {$customer['customer_time_extension']}\n";
        
        // ทดสอบ autoExtendTimeOnActivity
        $result = $workflowService->autoExtendTimeOnActivity($customer['customer_id'], 'order', 1);
        
        echo "🔄 ผลการทดสอบ autoExtendTimeOnActivity:\n";
        echo "- success: " . ($result['success'] ? 'true' : 'false') . "\n";
        echo "- message: {$result['message']}\n";
        
        // ข้อมูลหลังการทดสอบ
        $updatedCustomer = $db->fetchOne("
            SELECT assigned_at, customer_time_expiry, customer_time_extension
            FROM customers WHERE customer_id = ?
        ", [$customer['customer_id']]);
        
        echo "📅 ข้อมูลหลังการทดสอบ:\n";
        echo "- assigned_at: {$updatedCustomer['assigned_at']}\n";
        echo "- customer_time_expiry: {$updatedCustomer['customer_time_expiry']}\n";
        echo "- customer_time_extension: {$updatedCustomer['customer_time_extension']}\n";
        
        if ($result['success']) {
            echo "✅ การต่อเวลาสำเร็จ\n";
        } else {
            echo "❌ การต่อเวลาไม่สำเร็จ\n";
        }
        echo "\n";
    }
    
    // 3. ทดสอบการสร้างคำสั่งซื้อจำลอง
    echo "3. ทดสอบการสร้างคำสั่งซื้อจำลอง\n";
    
    // เลือกลูกค้าที่มี basket_type = 'waiting' เพื่อทดสอบ
    $testCustomer = $db->fetchOne("
        SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, basket_type
        FROM customers 
        WHERE basket_type = 'waiting' AND assigned_at IS NOT NULL
        LIMIT 1
    ");
    
    if ($testCustomer) {
        echo "🧪 ทดสอบสร้างคำสั่งซื้อกับลูกค้า: {$testCustomer['customer_name']} (ID: {$testCustomer['customer_id']}) basket_type: {$testCustomer['basket_type']}\n";
        
        // ข้อมูลก่อนการสร้างคำสั่งซื้อ
        $beforeOrder = $db->fetchOne("
            SELECT assigned_at, customer_time_expiry, customer_time_extension
            FROM customers WHERE customer_id = ?
        ", [$testCustomer['customer_id']]);
        
        echo "📅 ข้อมูลก่อนการสร้างคำสั่งซื้อ:\n";
        echo "- assigned_at: {$beforeOrder['assigned_at']}\n";
        echo "- customer_time_expiry: {$beforeOrder['customer_time_expiry']}\n";
        echo "- customer_time_extension: {$beforeOrder['customer_time_extension']}\n";
        
        // สร้างคำสั่งซื้อจำลอง
        $orderData = [
            'customer_id' => $testCustomer['customer_id'],
            'order_date' => date('Y-m-d H:i:s'),
            'total_amount' => 1000,
            'discount_amount' => 0,
            'net_amount' => 1000,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
            'notes' => 'ทดสอบการต่อเวลาอัตโนมัติ'
        ];
        
        $orderItems = [
            [
                'product_id' => 1,
                'quantity' => 1,
                'unit_price' => 1000
            ]
        ];
        
        $orderResult = $orderService->createOrder($orderData, $orderItems, 1);
        
        echo "🔄 ผลการสร้างคำสั่งซื้อ:\n";
        echo "- success: " . ($orderResult['success'] ? 'true' : 'false') . "\n";
        echo "- message: {$orderResult['message']}\n";
        if (isset($orderResult['order_id'])) {
            echo "- order_id: {$orderResult['order_id']}\n";
        }
        
        // ข้อมูลหลังการสร้างคำสั่งซื้อ
        $afterOrder = $db->fetchOne("
            SELECT assigned_at, customer_time_expiry, customer_time_extension
            FROM customers WHERE customer_id = ?
        ", [$testCustomer['customer_id']]);
        
        echo "📅 ข้อมูลหลังการสร้างคำสั่งซื้อ:\n";
        echo "- assigned_at: {$afterOrder['assigned_at']}\n";
        echo "- customer_time_expiry: {$afterOrder['customer_time_expiry']}\n";
        echo "- customer_time_extension: {$afterOrder['customer_time_extension']}\n";
        
        // ตรวจสอบการเปลี่ยนแปลง
        if ($beforeOrder['assigned_at'] !== $afterOrder['assigned_at']) {
            echo "✅ assigned_at เปลี่ยนแล้ว\n";
        } else {
            echo "❌ assigned_at ไม่เปลี่ยน\n";
        }
        
        if ($beforeOrder['customer_time_expiry'] !== $afterOrder['customer_time_expiry']) {
            echo "✅ customer_time_expiry เปลี่ยนแล้ว\n";
        } else {
            echo "❌ customer_time_expiry ไม่เปลี่ยน\n";
        }
        
        if ($beforeOrder['customer_time_extension'] !== $afterOrder['customer_time_extension']) {
            echo "✅ customer_time_extension เปลี่ยนแล้ว\n";
        } else {
            echo "❌ customer_time_extension ไม่เปลี่ยน\n";
        }
        
        // ตรวจสอบกิจกรรม
        echo "\n4. ตรวจสอบกิจกรรม\n";
        
        // ตรวจสอบ customer_activities (จาก WorkflowService)
        $customerActivities = $db->fetchAll("
            SELECT activity_type, description, created_at
            FROM customer_activities 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ", [$testCustomer['customer_id']]);
        
        echo "📋 กิจกรรมลูกค้า (customer_activities):\n";
        if (empty($customerActivities)) {
            echo "❌ ไม่พบกิจกรรม\n";
        } else {
            foreach ($customerActivities as $activity) {
                echo "- {$activity['activity_type']}: {$activity['description']} ({$activity['created_at']})\n";
            }
        }
        
                 // ตรวจสอบ order_activities (จาก OrderService)
         if (isset($orderResult['order_id'])) {
             $orderActivities = $db->fetchAll("
                 SELECT activity_type, description, created_at
                 FROM order_activities 
                 WHERE order_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5
             ", [$orderResult['order_id']]);
             
             echo "📋 กิจกรรมคำสั่งซื้อ (order_activities):\n";
             if (empty($orderActivities)) {
                 echo "❌ ไม่พบกิจกรรม\n";
             } else {
                 foreach ($orderActivities as $activity) {
                     echo "- {$activity['activity_type']}: {$activity['description']} ({$activity['created_at']})\n";
                 }
             }
         }
        
    } else {
        echo "❌ ไม่พบลูกค้าที่มี basket_type = 'waiting' สำหรับทดสอบ\n";
    }
    
    echo "\n✅ การทดสอบเสร็จสิ้น\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    error_log("Test error: " . $e->getMessage());
}
?> 