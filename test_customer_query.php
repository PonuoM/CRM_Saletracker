<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>ทดสอบการดึงข้อมูลลูกค้า</h2>\n";

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    
    echo "<p>1. ทดสอบการนับจำนวนลูกค้า...</p>\n";
    $result = $db->query("SELECT COUNT(*) as count FROM customers");
    echo "<p>✓ พบลูกค้า {$result[0]['count']} ราย</p>\n";
    
    echo "<p>2. ทดสอบการดึงข้อมูลลูกค้าแบบง่าย...</p>\n";
    $result = $db->query("SELECT customer_id, first_name, last_name FROM customers LIMIT 3");
    echo "<p>✓ ดึงข้อมูลลูกค้าแบบง่ายสำเร็จ</p>\n";
    foreach ($result as $customer) {
        echo "<p>- ID: {$customer['customer_id']}, ชื่อ: {$customer['first_name']} {$customer['last_name']}</p>\n";
    }
    
    echo "<p>3. ทดสอบการดึงข้อมูลลูกค้าพร้อม CONCAT...</p>\n";
    $result = $db->query("SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name FROM customers LIMIT 3");
    echo "<p>✓ ดึงข้อมูลลูกค้าพร้อม CONCAT สำเร็จ</p>\n";
    foreach ($result as $customer) {
        echo "<p>- ID: {$customer['customer_id']}, ชื่อ: {$customer['customer_name']}</p>\n";
    }
    
    echo "<p>4. ทดสอบการดึงข้อมูลลูกค้าพร้อมคอลัมน์ใหม่...</p>\n";
    $result = $db->query("SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, appointment_extension_count, max_appointment_extensions FROM customers LIMIT 3");
    echo "<p>✓ ดึงข้อมูลลูกค้าพร้อมคอลัมน์ใหม่สำเร็จ</p>\n";
    foreach ($result as $customer) {
        echo "<p>- ID: {$customer['customer_id']}, ชื่อ: {$customer['customer_name']}, ต่อเวลาแล้ว: {$customer['appointment_extension_count']}/{$customer['max_appointment_extensions']}</p>\n";
    }
    
    echo "<p style='color: green; font-weight: bold;'>✓ การทดสอบการดึงข้อมูลลูกค้าสำเร็จ!</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?> 