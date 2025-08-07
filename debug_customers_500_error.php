<?php
/**
 * Debug 500 Error in customers.php
 * ทดสอบหาสาเหตุของ 500 error
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug 500 Error in customers.php</h1>";

// Test 1: Check if config file exists
echo "<h2>1. ตรวจสอบไฟล์ config</h2>";
if (file_exists('config/config.php')) {
    echo "✅ ไฟล์ config/config.php พบ<br>";
    
    // Test include config
    try {
        require_once 'config/config.php';
        echo "✅ ไฟล์ config/config.php include สำเร็จ<br>";
        
        // Check if constants are defined
        if (defined('APP_VIEWS')) {
            echo "✅ APP_VIEWS constant ถูกกำหนด: " . APP_VIEWS . "<br>";
        } else {
            echo "❌ APP_VIEWS constant ไม่ถูกกำหนด<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ เกิด error เมื่อ include config: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ ไม่พบไฟล์ config/config.php<br>";
}

// Test 2: Check if CustomerController exists
echo "<h2>2. ตรวจสอบไฟล์ CustomerController</h2>";
if (file_exists('app/controllers/CustomerController.php')) {
    echo "✅ ไฟล์ app/controllers/CustomerController.php พบ<br>";
    
    // Test include CustomerController
    try {
        require_once 'app/controllers/CustomerController.php';
        echo "✅ ไฟล์ CustomerController.php include สำเร็จ<br>";
        
        // Test instantiate CustomerController
        try {
            $controller = new CustomerController();
            echo "✅ สร้าง CustomerController instance สำเร็จ<br>";
        } catch (Exception $e) {
            echo "❌ เกิด error เมื่อสร้าง CustomerController: " . $e->getMessage() . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ เกิด error เมื่อ include CustomerController: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ ไม่พบไฟล์ app/controllers/CustomerController.php<br>";
}

// Test 3: Check if required files exist
echo "<h2>3. ตรวจสอบไฟล์ที่จำเป็น</h2>";
$requiredFiles = [
    'app/core/Auth.php',
    'app/core/Database.php',
    'app/services/CustomerService.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} พบ<br>";
    } else {
        echo "❌ ไม่พบ {$file}<br>";
    }
}

// Test 4: Check if view files exist
echo "<h2>4. ตรวจสอบไฟล์ view</h2>";
$viewFiles = [
    'app/views/customers/index.php',
    'app/views/layouts/main.php'
];

foreach ($viewFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} พบ<br>";
    } else {
        echo "❌ ไม่พบ {$file}<br>";
    }
}

// Test 5: Test database connection
echo "<h2>5. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    require_once 'app/core/Database.php';
    $db = new Database();
    $testQuery = $db->fetchOne("SELECT 1 as test");
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
}

// Test 6: Test session
echo "<h2>6. ทดสอบ Session</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session ทำงานปกติ<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "❌ Session ไม่ทำงาน<br>";
}

echo "<h2>สรุปการทดสอบ</h2>";
echo "การทดสอบเสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบนเพื่อหาสาเหตุของ 500 error<br>";
echo "<br><a href='customers.php'>ทดสอบ customers.php อีกครั้ง</a>";
?>
