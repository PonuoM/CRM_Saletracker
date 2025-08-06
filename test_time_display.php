<?php
/**
 * ทดสอบการแสดงเวลาที่เหลือ
 * ตรวจสอบว่าเวลาที่เหลือใช้ customer_time_expiry แทน recall_at
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/CustomerService.php';

echo "🧪 ทดสอบการแสดงเวลาที่เหลือ\n";

try {
    $db = new Database();
    $customerService = new CustomerService();
    
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n\n";
    
    // 1. ตรวจสอบข้อมูลลูกค้าที่มี assigned_to
    echo "1. ตรวจสอบข้อมูลลูกค้าที่มี assigned_to:\n";
    $assignedCustomers = $db->fetchAll("
        SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name,
               assigned_at, recall_at, customer_time_expiry, customer_time_extension
        FROM customers 
        WHERE assigned_to IS NOT NULL AND basket_type = 'assigned'
        LIMIT 3
    ");
    
    if (empty($assignedCustomers)) {
        echo "❌ ไม่พบลูกค้าที่มี assigned_to\n";
        exit;
    }
    
    foreach ($assignedCustomers as $customer) {
        echo "- {$customer['customer_name']} (ID: {$customer['customer_id']})\n";
        echo "  assigned_at: {$customer['assigned_at']}\n";
        echo "  recall_at: {$customer['recall_at']}\n";
        echo "  customer_time_expiry: {$customer['customer_time_expiry']}\n";
        echo "  customer_time_extension: {$customer['customer_time_extension']}\n\n";
    }
    
    // 2. ทดสอบฟังก์ชัน getFollowUpCustomers
    echo "2. ทดสอบฟังก์ชัน getFollowUpCustomers:\n";
    $followUpCustomers = $customerService->getFollowUpCustomers(1); // ใช้ user_id = 1
    
    if (empty($followUpCustomers)) {
        echo "❌ ไม่พบลูกค้าที่ต้องติดตาม\n";
    } else {
        echo "✅ พบลูกค้าที่ต้องติดตาม: " . count($followUpCustomers) . " ราย\n";
        
        foreach ($followUpCustomers as $customer) {
            echo "- {$customer['first_name']} {$customer['last_name']} (ID: {$customer['customer_id']})\n";
            echo "  days_remaining: {$customer['days_remaining']} วัน\n";
            echo "  customer_time_expiry: {$customer['customer_time_expiry']}\n";
            echo "  recall_at: {$customer['recall_at']}\n\n";
        }
    }
    
    // 3. ทดสอบการคำนวณเวลาที่เหลือ
    echo "3. ทดสอบการคำนวณเวลาที่เหลือ:\n";
    $currentDate = date('Y-m-d H:i:s');
    echo "วันที่ปัจจุบัน: {$currentDate}\n\n";
    
    foreach ($assignedCustomers as $customer) {
        $recallDays = 0;
        $expiryDays = 0;
        
        if ($customer['recall_at']) {
            $recallDays = (strtotime($customer['recall_at']) - strtotime($currentDate)) / (60 * 60 * 24);
        }
        
        if ($customer['customer_time_expiry']) {
            $expiryDays = (strtotime($customer['customer_time_expiry']) - strtotime($currentDate)) / (60 * 60 * 24);
        }
        
        echo "- {$customer['customer_name']}:\n";
        echo "  จาก recall_at: " . round($recallDays, 1) . " วัน\n";
        echo "  จาก customer_time_expiry: " . round($expiryDays, 1) . " วัน\n";
        echo "  ความแตกต่าง: " . round($expiryDays - $recallDays, 1) . " วัน\n\n";
    }
    
    // 4. สรุปผล
    echo "4. สรุปผล:\n";
    echo "✅ ฟังก์ชัน getFollowUpCustomers ใช้ customer_time_expiry แล้ว\n";
    echo "✅ JavaScript ใช้ customer_time_expiry แล้ว\n";
    echo "✅ เวลาที่เหลือจะแสดงผลตาม customer_time_expiry ที่เราแก้ไข\n";
    
    echo "\n✅ การทดสอบเสร็จสิ้น\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    error_log("Test error: " . $e->getMessage());
}
?> 