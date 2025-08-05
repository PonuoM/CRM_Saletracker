<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>ทดสอบพื้นฐาน</h2>\n";

try {
    echo "<p>1. กำลังโหลด config...</p>\n";
    require_once 'config/config.php';
    echo "<p>✓ โหลด config สำเร็จ</p>\n";
    
    echo "<p>2. กำลังโหลด Database...</p>\n";
    require_once 'app/core/Database.php';
    echo "<p>✓ โหลด Database สำเร็จ</p>\n";
    
    echo "<p>3. กำลังสร้าง Database instance...</p>\n";
    $db = new Database();
    echo "<p>✓ สร้าง Database instance สำเร็จ</p>\n";
    
    echo "<p>4. กำลังทดสอบการเชื่อมต่อฐานข้อมูล...</p>\n";
    $result = $db->query("SELECT COUNT(*) as count FROM customers");
    if ($result) {
        echo "<p>✓ เชื่อมต่อฐานข้อมูลสำเร็จ - พบลูกค้า {$result[0]['count']} ราย</p>\n";
    } else {
        echo "<p>✗ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</p>\n";
    }
    
    echo "<p>5. กำลังตรวจสอบตาราง appointment_extensions...</p>\n";
    $result = $db->query("SHOW TABLES LIKE 'appointment_extensions'");
    if ($result && count($result) > 0) {
        echo "<p>✓ ตาราง appointment_extensions มีอยู่</p>\n";
    } else {
        echo "<p>✗ ตาราง appointment_extensions ไม่มีอยู่</p>\n";
    }
    
    echo "<p>6. กำลังตรวจสอบ stored procedures...</p>\n";
    $result = $db->query("SHOW PROCEDURE STATUS WHERE Name = 'ExtendCustomerTimeFromAppointment'");
    if ($result && count($result) > 0) {
        echo "<p>✓ Stored Procedure ExtendCustomerTimeFromAppointment มีอยู่</p>\n";
    } else {
        echo "<p>✗ Stored Procedure ExtendCustomerTimeFromAppointment ไม่มีอยู่</p>\n";
    }
    
    echo "<p style='color: green; font-weight: bold;'>✓ การทดสอบพื้นฐานสำเร็จ!</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?> 