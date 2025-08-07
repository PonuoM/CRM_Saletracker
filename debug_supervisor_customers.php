<?php
/**
 * Debug Supervisor Customers Issue
 * ทดสอบปัญหาเฉพาะ Supervisor ใน customers.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Supervisor Customers Issue</h1>";

// Test 1: Check supervisor session data
echo "<h2>1. ตรวจสอบข้อมูล Session ของ Supervisor</h2>";
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    echo "✅ Session มีข้อมูลผู้ใช้<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Role Name: " . $_SESSION['role_name'] . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'N/A') . "<br>";
} else {
    echo "❌ ไม่มีข้อมูล Session<br>";
    echo "กรุณาเข้าสู่ระบบด้วยบัญชี Supervisor ก่อน<br>";
    echo "<a href='login.php'>ไปหน้า Login</a><br>";
    exit;
}

// Test 2: Check if user is supervisor
echo "<h2>2. ตรวจสอบว่าเป็น Supervisor หรือไม่</h2>";
$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if ($roleName === 'supervisor') {
    echo "✅ ผู้ใช้เป็น Supervisor<br>";
} else {
    echo "❌ ผู้ใช้ไม่ใช่ Supervisor (Role: {$roleName})<br>";
    echo "กรุณาเข้าสู่ระบบด้วยบัญชี Supervisor<br>";
    exit;
}

// Test 3: Check database connection and supervisor data
echo "<h2>3. ตรวจสอบข้อมูล Supervisor ในฐานข้อมูล</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    // Check supervisor user
    $supervisor = $db->fetchOne(
        "SELECT u.*, r.role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.role_id 
         WHERE u.user_id = :user_id AND r.role_name = 'supervisor'",
        ['user_id' => $userId]
    );
    
    if ($supervisor) {
        echo "✅ พบข้อมูล Supervisor ในฐานข้อมูล<br>";
        echo "ชื่อ: {$supervisor['full_name']}<br>";
        echo "Username: {$supervisor['username']}<br>";
        echo "Role: {$supervisor['role_name']}<br>";
    } else {
        echo "❌ ไม่พบข้อมูล Supervisor ในฐานข้อมูล<br>";
        exit;
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 4: Check supervisor_id column
echo "<h2>4. ตรวจสอบคอลัมน์ supervisor_id</h2>";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'supervisor_id'");
    if (empty($columns)) {
        echo "❌ ไม่พบคอลัมน์ supervisor_id ในตาราง users<br>";
        
        // Add supervisor_id column
        $sql = "ALTER TABLE `users` 
                ADD COLUMN `supervisor_id` INT NULL AFTER `company_id`,
                ADD FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
                ADD INDEX `idx_supervisor_id` (`supervisor_id`)";
        
        try {
            $db->execute($sql);
            echo "✅ เพิ่มคอลัมน์ supervisor_id สำเร็จ<br>";
        } catch (Exception $e) {
            echo "❌ ไม่สามารถเพิ่มคอลัมน์ supervisor_id: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ พบคอลัมน์ supervisor_id ในตาราง users<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error: " . $e->getMessage() . "<br>";
}

// Test 5: Test getTeamCustomerIds method
echo "<h2>5. ทดสอบ method getTeamCustomerIds</h2>";
try {
    // Simulate the method
    $teamMembers = $db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        ['supervisor_id' => $userId]
    );
    
    $teamCustomerIds = [];
    foreach ($teamMembers as $member) {
        $teamCustomerIds[] = $member['user_id'];
    }
    
    echo "✅ ดึงข้อมูลสมาชิกทีมสำเร็จ<br>";
    echo "จำนวนสมาชิกทีม: " . count($teamCustomerIds) . " คน<br>";
    
    if (!empty($teamCustomerIds)) {
        echo "User IDs ของสมาชิกทีม: " . implode(', ', $teamCustomerIds) . "<br>";
    } else {
        echo "⚠️ ไม่มีสมาชิกในทีม<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error ใน getTeamCustomerIds: " . $e->getMessage() . "<br>";
}

// Test 6: Test CustomerService getCustomersByBasket with supervisor data
echo "<h2>6. ทดสอบ CustomerService สำหรับ Supervisor</h2>";
try {
    require_once 'app/services/CustomerService.php';
    
    $customerService = new CustomerService();
    
    if (!empty($teamCustomerIds)) {
        $customers = $customerService->getCustomersByBasket('assigned', ['assigned_to' => $teamCustomerIds]);
        echo "✅ ดึงข้อมูลลูกค้าของทีมสำเร็จ<br>";
        echo "จำนวนลูกค้า: " . count($customers) . " คน<br>";
        
        if (!empty($customers)) {
            echo "ลูกค้า 5 คนแรก:<br>";
            for ($i = 0; $i < min(5, count($customers)); $i++) {
                $customer = $customers[$i];
                echo "- {$customer['first_name']} {$customer['last_name']} (ID: {$customer['customer_id']})<br>";
            }
        }
    } else {
        echo "⚠️ ไม่มีสมาชิกในทีม จึงไม่มีลูกค้า<br>";
        $customers = [];
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error ใน CustomerService: " . $e->getMessage() . "<br>";
    echo "Error details: " . $e->getTraceAsString() . "<br>";
}

// Test 7: Test view files inclusion
echo "<h2>7. ทดสอบการ include ไฟล์ view</h2>";

$viewFiles = [
    'app/views/customers/index.php',
    'app/views/layouts/main.php'
];

foreach ($viewFiles as $viewFile) {
    if (file_exists($viewFile)) {
        echo "✅ {$viewFile} พบ<br>";
        
        // Test if file is readable
        if (is_readable($viewFile)) {
            echo "✅ {$viewFile} อ่านได้<br>";
        } else {
            echo "❌ {$viewFile} อ่านไม่ได้<br>";
        }
    } else {
        echo "❌ ไม่พบ {$viewFile}<br>";
    }
}

// Test 8: Test APP_VIEWS constant
echo "<h2>8. ทดสอบ APP_VIEWS constant</h2>";
if (defined('APP_VIEWS')) {
    echo "✅ APP_VIEWS constant ถูกกำหนด: " . APP_VIEWS . "<br>";
    
    // Test if directory exists
    if (is_dir(APP_VIEWS)) {
        echo "✅ APP_VIEWS directory พบ<br>";
    } else {
        echo "❌ APP_VIEWS directory ไม่พบ<br>";
    }
} else {
    echo "❌ APP_VIEWS constant ไม่ถูกกำหนด<br>";
}

// Test 9: Simulate the exact error scenario
echo "<h2>9. ทดสอบสถานการณ์ที่เกิด error</h2>";
try {
    // Simulate what happens in CustomerController index method for supervisor
    $roleName = $_SESSION['role_name'];
    $userId = $_SESSION['user_id'];
    
    echo "Role: {$roleName}<br>";
    echo "User ID: {$userId}<br>";
    
    if ($roleName === 'supervisor') {
        echo "✅ เข้าสู่เงื่อนไข supervisor<br>";
        
        // Get team customer IDs
        $teamMembers = $db->fetchAll(
            "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
            ['supervisor_id' => $userId]
        );
        
        $teamCustomerIds = [];
        foreach ($teamMembers as $member) {
            $teamCustomerIds[] = $member['user_id'];
        }
        
        echo "Team Customer IDs: " . implode(', ', $teamCustomerIds) . "<br>";
        
        if (!empty($teamCustomerIds)) {
            $customers = $customerService->getCustomersByBasket('assigned', ['assigned_to' => $teamCustomerIds]);
            echo "✅ ดึงลูกค้าสำเร็จ: " . count($customers) . " คน<br>";
        } else {
            $customers = [];
            echo "✅ ไม่มีลูกค้า (ทีมว่าง)<br>";
        }
        
        // Test telesales list
        $telesalesList = $db->fetchAll(
            "SELECT user_id, full_name FROM users WHERE supervisor_id = :supervisor_id AND role_id = 4 AND is_active = 1 ORDER BY full_name",
            ['supervisor_id' => $userId]
        );
        echo "✅ ดึงรายการ Telesales สำเร็จ: " . count($telesalesList) . " คน<br>";
        
        // Test provinces
        $provinces = $db->fetchAll(
            "SELECT DISTINCT province FROM customers WHERE province IS NOT NULL AND province != '' ORDER BY province"
        );
        echo "✅ ดึงรายการจังหวัดสำเร็จ: " . count($provinces) . " จังหวัด<br>";
        
        echo "✅ ทุกขั้นตอนสำเร็จ ไม่มี error<br>";
        
    } else {
        echo "❌ ไม่ใช่ supervisor<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error ในสถานการณ์จำลอง: " . $e->getMessage() . "<br>";
    echo "Error details: " . $e->getTraceAsString() . "<br>";
}

echo "<h2>สรุปการทดสอบ</h2>";
echo "การทดสอบเสร็จสิ้นแล้ว<br>";
echo "<br><strong>ขั้นตอนต่อไป:</strong><br>";
echo "1. <a href='customers.php'>ทดสอบ customers.php อีกครั้ง</a><br>";
echo "2. <a href='fix_supervisor_customers.php'>แก้ไขปัญหาเฉพาะ Supervisor</a><br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
