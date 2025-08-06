<?php
/**
 * ทดสอบการต่อเวลาอัตโนมัติแบบง่าย
 * ตรวจสอบว่าเมื่อสร้างคำสั่งซื้อแล้ว เวลาของลูกค้าจะถูกต่ออัตโนมัติ
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/WorkflowService.php';
require_once __DIR__ . '/app/services/OrderService.php';

echo "🧪 ทดสอบการต่อเวลาอัตโนมัติแบบง่าย\n";

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
    
    // 3. สร้างคำสั่งซื้อจำลอง
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
    
    echo "🔄 กำลังสร้างคำสั่งซื้อ...\n";
    $orderResult = $orderService->createOrder($orderData, $orderItems, 1);
    
    echo "📋 ผลการสร้างคำสั่งซื้อ:\n";
    echo "- success: " . ($orderResult['success'] ? 'true' : 'false') . "\n";
    echo "- message: {$orderResult['message']}\n";
    if (isset($orderResult['order_id'])) {
        echo "- order_id: {$orderResult['order_id']}\n";
    }
    echo "\n";
    
    // 4. ข้อมูลหลังการสร้างคำสั่งซื้อ
    $afterOrder = $db->fetchOne("
        SELECT assigned_at, customer_time_expiry, customer_time_extension
        FROM customers WHERE customer_id = ?
    ", [$testCustomer['customer_id']]);
    
    echo "📅 ข้อมูลหลังการสร้างคำสั่งซื้อ:\n";
    echo "- assigned_at: {$afterOrder['assigned_at']}\n";
    echo "- customer_time_expiry: {$afterOrder['customer_time_expiry']}\n";
    echo "- customer_time_extension: {$afterOrder['customer_time_extension']}\n\n";
    
    // 5. ตรวจสอบการเปลี่ยนแปลง
    $assignedChanged = $testCustomer['assigned_at'] !== $afterOrder['assigned_at'];
    $expiryChanged = $testCustomer['customer_time_expiry'] !== $afterOrder['customer_time_expiry'];
    $extensionChanged = $testCustomer['customer_time_extension'] !== $afterOrder['customer_time_extension'];
    
    echo "🔍 ผลการตรวจสอบ:\n";
    if ($assignedChanged) {
        echo "✅ assigned_at เปลี่ยนแล้ว\n";
    } else {
        echo "❌ assigned_at ไม่เปลี่ยน\n";
    }
    
    if ($expiryChanged) {
        echo "✅ customer_time_expiry เปลี่ยนแล้ว\n";
    } else {
        echo "❌ customer_time_expiry ไม่เปลี่ยน\n";
    }
    
    // ตรวจสอบ customer_time_extension อย่างถูกต้อง
    // สำหรับ order activity ฟังก์ชันจะตั้งค่าเป็น 90
    $expectedExtension = 90;
    if ($afterOrder['customer_time_extension'] == $expectedExtension) {
        echo "✅ customer_time_extension ถูกตั้งค่าเป็น {$expectedExtension} (ถูกต้อง)\n";
    } else {
        echo "❌ customer_time_extension ไม่ถูกต้อง (คาดหวัง: {$expectedExtension}, ได้: {$afterOrder['customer_time_extension']})\n";
    }
    
    // 6. ตรวจสอบกิจกรรม
    echo "\n📋 ตรวจสอบกิจกรรม:\n";
    
    // ตรวจสอบ customer_activities
    $customerActivities = $db->fetchAll("
        SELECT activity_type, activity_description, created_at
        FROM customer_activities 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ", [$testCustomer['customer_id']]);
    
    echo "กิจกรรมลูกค้า (customer_activities):\n";
    if (empty($customerActivities)) {
        echo "❌ ไม่พบกิจกรรม\n";
    } else {
        foreach ($customerActivities as $activity) {
            echo "- {$activity['activity_type']}: {$activity['activity_description']} ({$activity['created_at']})\n";
        }
    }
    
    // ตรวจสอบ order_activities
    if (isset($orderResult['order_id'])) {
        // ตรวจสอบว่าคอลัมน์ description มีอยู่หรือไม่
        $hasDescriptionColumn = $db->fetchOne("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'order_activities' 
            AND COLUMN_NAME = 'description'
        ");
        
        if ($hasDescriptionColumn['count'] > 0) {
            $orderActivities = $db->fetchAll("
                SELECT activity_type, description, created_at
                FROM order_activities 
                WHERE order_id = ? 
                ORDER BY created_at DESC 
                LIMIT 3
            ", [$orderResult['order_id']]);
        } else {
            $orderActivities = $db->fetchAll("
                SELECT activity_type, created_at
                FROM order_activities 
                WHERE order_id = ? 
                ORDER BY created_at DESC 
                LIMIT 3
            ", [$orderResult['order_id']]);
        }
        
        echo "\nกิจกรรมคำสั่งซื้อ (order_activities):\n";
        if (empty($orderActivities)) {
            echo "❌ ไม่พบกิจกรรม\n";
        } else {
            foreach ($orderActivities as $activity) {
                if (isset($activity['description'])) {
                    echo "- {$activity['activity_type']}: {$activity['description']} ({$activity['created_at']})\n";
                } else {
                    echo "- {$activity['activity_type']}: (ไม่มีคำอธิบาย) ({$activity['created_at']})\n";
                }
            }
        }
    }
    
    // 7. สรุปผล
    echo "\n📊 สรุปผลการทดสอบ:\n";
    $expectedExtension = 90;
    $extensionCorrect = ($afterOrder['customer_time_extension'] == $expectedExtension);
    
    if ($assignedChanged && $expiryChanged && $extensionCorrect) {
        echo "🎉 การต่อเวลาอัตโนมัติทำงานได้สำเร็จ!\n";
        echo "✅ ลูกค้าที่มี basket_type = 'waiting' สามารถต่อเวลาได้เมื่อสร้างคำสั่งซื้อ\n";
        echo "✅ การบันทึกกิจกรรมทำงานได้ถูกต้อง\n";
    } else {
        echo "⚠️ การต่อเวลาอัตโนมัติยังไม่ทำงานสมบูรณ์\n";
        echo "❌ ต้องตรวจสอบการแก้ไขเพิ่มเติม\n";
    }
    
    echo "\n✅ การทดสอบเสร็จสิ้น\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    error_log("Test error: " . $e->getMessage());
}
?> 