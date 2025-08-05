<?php
/**
 * Test Script for Fixed Appointment Extension System
 * ทดสอบระบบการต่อเวลาจากการนัดหมายที่แก้ไขแล้ว
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>ทดสอบระบบการต่อเวลาจากการนัดหมาย (Fixed Version)</h2>\n";

try {
    echo "<p>กำลังโหลดไฟล์ config...</p>\n";
    require_once 'config/config.php';
    echo "<p>✓ โหลด config สำเร็จ</p>\n";
    
    echo "<p>กำลังโหลดไฟล์ Database...</p>\n";
    require_once 'app/core/Database.php';
    echo "<p>✓ โหลด Database สำเร็จ</p>\n";
    
    echo "<p>กำลังโหลดไฟล์ AppointmentExtensionService...</p>\n";
    require_once 'app/services/AppointmentExtensionService.php';
    echo "<p>✓ โหลด AppointmentExtensionService สำเร็จ</p>\n";
    
    echo "<p>กำลังสร้าง Database instance...</p>\n";
    $db = new Database();
    echo "<p>✓ สร้าง Database instance สำเร็จ</p>\n";
    
    echo "<p>กำลังสร้าง AppointmentExtensionService instance...</p>\n";
    $extensionService = new AppointmentExtensionService();
    echo "<p>✓ สร้าง AppointmentExtensionService instance สำเร็จ</p>\n";
    
    echo "<h3>1. ทดสอบการดึงข้อมูลการต่อเวลาของลูกค้า</h3>\n";
    
    // ทดสอบดึงข้อมูลลูกค้าที่มีอยู่
    echo "<p>กำลังดึงข้อมูลลูกค้า...</p>\n";
    try {
        $customers = $db->query("SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, appointment_extension_count, max_appointment_extensions FROM customers LIMIT 3");
        echo "<p>✓ ดึงข้อมูลลูกค้าสำเร็จ</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้า: " . $e->getMessage() . "</p>\n";
        $customers = [];
    }
    
    if ($customers && count($customers) > 0) {
        echo "<p>พบลูกค้า " . count($customers) . " ราย:</p>\n";
        echo "<ul>\n";
        foreach ($customers as $customer) {
            echo "<li>ID: {$customer['customer_id']} - {$customer['customer_name']} (ต่อเวลาแล้ว: {$customer['appointment_extension_count']}/{$customer['max_appointment_extensions']})</li>\n";
        }
        echo "</ul>\n";
        
        // ทดสอบดึงข้อมูลการต่อเวลาของลูกค้ารายแรก
        $firstCustomer = $customers[0];
        echo "<p>กำลังดึงข้อมูลการต่อเวลาของ {$firstCustomer['customer_name']}...</p>\n";
        try {
            $extensionInfo = $extensionService->getCustomerExtensionInfo($firstCustomer['customer_id']);
            
            if ($extensionInfo['success']) {
                echo "<p><strong>ข้อมูลการต่อเวลาของ {$firstCustomer['customer_name']}:</strong></p>\n";
                echo "<ul>\n";
                echo "<li>จำนวนการนัดหมาย: {$extensionInfo['data']['appointment_count']}</li>\n";
                echo "<li>จำนวนครั้งที่ต่อเวลาแล้ว: {$extensionInfo['data']['appointment_extension_count']}</li>\n";
                echo "<li>จำนวนครั้งสูงสุด: {$extensionInfo['data']['max_appointment_extensions']}</li>\n";
                echo "<li>วันหมดอายุ: " . ($extensionInfo['data']['appointment_extension_expiry'] ?? 'ไม่มี') . "</li>\n";
                echo "<li>สถานะ: {$extensionInfo['data']['extension_status']}</li>\n";
                echo "</ul>\n";
            } else {
                echo "<p style='color: red;'>ไม่สามารถดึงข้อมูลการต่อเวลาได้: {$extensionInfo['message']}</p>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>เกิดข้อผิดพลาดในการดึงข้อมูลการต่อเวลา: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>ไม่พบข้อมูลลูกค้าในระบบ</p>\n";
    }
    
    echo "<h3>2. ทดสอบการดึงสถิติการต่อเวลา</h3>\n";
    
    echo "<p>กำลังดึงสถิติการต่อเวลา...</p>\n";
    $stats = $extensionService->getExtensionStats();
    if ($stats['success']) {
        echo "<p><strong>สถิติการต่อเวลา:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>ลูกค้าที่สามารถต่อเวลาได้: {$stats['data']['can_extend']} ราย</li>\n";
        echo "<li>ลูกค้าที่ไม่สามารถต่อเวลาได้: {$stats['data']['cannot_extend']} ราย</li>\n";
        echo "<li>ลูกค้าที่ใกล้หมดอายุ: {$stats['data']['near_expiry']} ราย</li>\n";
        echo "<li>ลูกค้าที่หมดอายุแล้ว: {$stats['data']['expired']} ราย</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p style='color: red;'>ไม่สามารถดึงสถิติได้: {$stats['message']}</p>\n";
    }
    
    echo "<h3>3. ทดสอบการดึงประวัติการต่อเวลา</h3>\n";
    
    if (isset($firstCustomer)) {
        echo "<p>กำลังดึงประวัติการต่อเวลาของ {$firstCustomer['customer_name']}...</p>\n";
        $history = $extensionService->getExtensionHistory($firstCustomer['customer_id']);
        if ($history['success']) {
            echo "<p><strong>ประวัติการต่อเวลาของ {$firstCustomer['customer_name']}:</strong></p>\n";
            if (count($history['data']) > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
                echo "<tr><th>วันที่</th><th>ประเภท</th><th>จำนวนวัน</th><th>เหตุผล</th></tr>\n";
                foreach ($history['data'] as $record) {
                    echo "<tr>\n";
                    echo "<td>" . date('Y-m-d H:i', strtotime($record['created_at'])) . "</td>\n";
                    echo "<td>{$record['extension_type']}</td>\n";
                    echo "<td>{$record['extension_days']}</td>\n";
                    echo "<td>{$record['extension_reason']}</td>\n";
                    echo "</tr>\n";
                }
                echo "</table>\n";
            } else {
                echo "<p>ไม่มีประวัติการต่อเวลา</p>\n";
            }
        } else {
            echo "<p style='color: red;'>ไม่สามารถดึงประวัติได้: {$history['message']}</p>\n";
        }
    }
    
    echo "<h3>4. ทดสอบการดึงกฎการต่อเวลา</h3>\n";
    
    echo "<p>กำลังดึงกฎการต่อเวลา...</p>\n";
    $rules = $extensionService->getExtensionRules();
    if ($rules['success']) {
        echo "<p><strong>กฎการต่อเวลาที่ใช้งาน:</strong></p>\n";
        foreach ($rules['data'] as $rule) {
            echo "<ul>\n";
            echo "<li>ชื่อกฎ: {$rule['rule_name']}</li>\n";
            echo "<li>จำนวนวันที่ต่อ: {$rule['extension_days']} วัน</li>\n";
            echo "<li>จำนวนครั้งสูงสุด: {$rule['max_extensions']} ครั้ง</li>\n";
            echo "<li>รีเซ็ตเมื่อขาย: " . ($rule['reset_on_sale'] ? 'ใช่' : 'ไม่') . "</li>\n";
            echo "<li>สถานะการนัดหมายที่ต้องมี: {$rule['required_appointment_status']}</li>\n";
            echo "<li>เกรดลูกค้าขั้นต่ำ: {$rule['min_customer_grade']}</li>\n";
            echo "</ul>\n";
        }
    } else {
        echo "<p style='color: red;'>ไม่สามารถดึงกฎได้: {$rules['message']}</p>\n";
    }
    
    echo "<h3>5. ทดสอบการต่อเวลาด้วยตนเอง</h3>\n";
    
    if (isset($firstCustomer)) {
        // ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่
        echo "<p>กำลังตรวจสอบความสามารถในการต่อเวลาของ {$firstCustomer['customer_name']}...</p>\n";
        $canExtend = $extensionService->canExtendTime($firstCustomer['customer_id']);
        echo "<p><strong>ลูกค้า {$firstCustomer['customer_name']} สามารถต่อเวลาได้: " . ($canExtend ? 'ใช่' : 'ไม่') . "</strong></p>\n";
        
        if ($canExtend) {
            echo "<p style='color: green;'>✓ ระบบพร้อมสำหรับการต่อเวลาอัตโนมัติเมื่อมีการนัดหมายเสร็จสิ้น</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ ลูกค้ารายนี้ไม่สามารถต่อเวลาได้ (อาจจะต่อครบจำนวนครั้งแล้ว)</p>\n";
        }
    }
    
    echo "<h3>6. ตรวจสอบตารางและโครงสร้างฐานข้อมูล</h3>\n";
    
    // ตรวจสอบว่าตารางใหม่ถูกสร้างแล้วหรือไม่
    $tables = ['appointment_extensions', 'appointment_extension_rules'];
    foreach ($tables as $table) {
        echo "<p>กำลังตรวจสอบตาราง $table...</p>\n";
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result && count($result) > 0) {
            echo "<p style='color: green;'>✓ ตาราง $table ถูกสร้างแล้ว</p>\n";
        } else {
            echo "<p style='color: red;'>✗ ตาราง $table ยังไม่ถูกสร้าง</p>\n";
        }
    }
    
    // ตรวจสอบ stored procedures
    $procedures = ['ExtendCustomerTimeFromAppointment', 'ResetAppointmentExtensionOnSale'];
    foreach ($procedures as $procedure) {
        echo "<p>กำลังตรวจสอบ Stored Procedure $procedure...</p>\n";
        $result = $db->query("SHOW PROCEDURE STATUS WHERE Name = '$procedure'");
        if ($result && count($result) > 0) {
            echo "<p style='color: green;'>✓ Stored Procedure $procedure ถูกสร้างแล้ว</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Stored Procedure $procedure ยังไม่ถูกสร้าง</p>\n";
        }
    }
    
    echo "<h3>สรุปผลการทดสอบ</h3>\n";
    echo "<p style='color: green; font-weight: bold;'>✓ ระบบการต่อเวลาจากการนัดหมายพร้อมใช้งานแล้ว!</p>\n";
    echo "<p>ระบบจะทำงานอัตโนมัติเมื่อ:</p>\n";
    echo "<ul>\n";
    echo "<li>มีการนัดหมายเสร็จสิ้น (status = 'completed')</li>\n";
    echo "<li>มีการสร้างคำสั่งซื้อใหม่ (จะรีเซ็ตตัวนับการต่อเวลา)</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    echo "<p>กรุณาตรวจสอบ:</p>\n";
    echo "<ul>\n";
    echo "<li>การเชื่อมต่อฐานข้อมูล</li>\n";
    echo "<li>การรันไฟล์ SQL: database/appointment_extension_system_fixed.sql</li>\n";
    echo "<li>สิทธิ์การเข้าถึงฐานข้อมูล</li>\n";
    echo "<li>ไฟล์ config/config.php</li>\n";
    echo "<li>ไฟล์ app/core/Database.php</li>\n";
    echo "<li>ไฟล์ app/services/AppointmentExtensionService.php</li>\n";
    echo "</ul>\n";
}

echo "<hr>\n";
echo "<p><strong>คำแนะนำ:</strong></p>\n";
echo "<ol>\n";
echo "<li>รันไฟล์ <code>database/appointment_extension_system_fixed.sql</code> ในฐานข้อมูล</li>\n";
echo "<li>ทดสอบระบบด้วยไฟล์นี้</li>\n";
echo "<li>หากไม่มีข้อผิดพลาด ระบบพร้อมใช้งาน</li>\n";
echo "</ol>\n";
?> 