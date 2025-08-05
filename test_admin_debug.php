<?php
/**
 * Test Admin Debug
 * ทดสอบการเชื่อมต่อและดูปัญหาของ Admin System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin System Debug</h1>";

// 0. โหลด configuration
echo "<h2>0. โหลด Configuration</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ โหลด Configuration สำเร็จ<br>";
    echo "Environment: " . ENVIRONMENT . "<br>";
    echo "Database: " . DB_NAME . " @ " . DB_HOST . ":" . DB_PORT . "<br>";
} catch (Exception $e) {
    echo "❌ โหลด Configuration ล้มเหลว: " . $e->getMessage() . "<br>";
    exit;
}

// 1. ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    require_once __DIR__ . '/app/core/Database.php';
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
    exit;
}

// 2. ทดสอบการโหลด Auth class
echo "<h2>2. ทดสอบการโหลด Auth class</h2>";
try {
    require_once __DIR__ . '/app/core/Auth.php';
    $auth = new Auth($db);
    echo "✅ การโหลด Auth class สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ การโหลด Auth class ล้มเหลว: " . $e->getMessage() . "<br>";
}

// 3. ทดสอบการโหลด AdminController
echo "<h2>3. ทดสอบการโหลด AdminController</h2>";
try {
    require_once __DIR__ . '/app/controllers/AdminController.php';
    $adminController = new AdminController();
    echo "✅ การโหลด AdminController สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ การโหลด AdminController ล้มเหลว: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// 4. ทดสอบการตรวจสอบตาราง
echo "<h2>4. ทดสอบการตรวจสอบตาราง</h2>";
$tables = ['users', 'roles', 'companies', 'customers', 'products', 'system_settings'];
foreach ($tables as $table) {
    try {
        $exists = $db->tableExists($table);
        echo $exists ? "✅ ตาราง $table มีอยู่<br>" : "❌ ตาราง $table ไม่มีอยู่<br>";
    } catch (Exception $e) {
        echo "❌ ตรวจสอบตาราง $table ล้มเหลว: " . $e->getMessage() . "<br>";
    }
}

// 5. ทดสอบการดึงข้อมูล
echo "<h2>5. ทดสอบการดึงข้อมูล</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = $db->fetchOne($sql);
    echo "✅ จำนวนผู้ใช้: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ การดึงข้อมูลผู้ใช้ล้มเหลว: " . $e->getMessage() . "<br>";
}

try {
    $sql = "SELECT COUNT(*) as count FROM products";
    $result = $db->fetchOne($sql);
    echo "✅ จำนวนสินค้า: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ การดึงข้อมูลสินค้าล้มเหลว: " . $e->getMessage() . "<br>";
}

try {
    $sql = "SELECT COUNT(*) as count FROM system_settings";
    $result = $db->fetchOne($sql);
    echo "✅ จำนวนการตั้งค่าระบบ: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ การดึงข้อมูลการตั้งค่าระบบล้มเหลว: " . $e->getMessage() . "<br>";
}

// 6. ทดสอบการโหลด views
echo "<h2>6. ทดสอบการโหลด views</h2>";
$views = [
    'app/views/admin/index.php',
    'app/views/admin/users/index.php',
    'app/views/admin/products/index.php',
    'app/views/admin/settings.php',
    'app/views/components/header.php',
    'app/views/components/sidebar.php'
];

foreach ($views as $view) {
    if (file_exists($view)) {
        echo "✅ ไฟล์ $view มีอยู่<br>";
    } else {
        echo "❌ ไฟล์ $view ไม่มีอยู่<br>";
    }
}

echo "<h2>7. ลิงก์ทดสอบ</h2>";
echo "<a href='admin.php' target='_blank'>ทดสอบ Admin Dashboard</a><br>";
echo "<a href='admin.php?action=users' target='_blank'>ทดสอบ User Management</a><br>";
echo "<a href='admin.php?action=products' target='_blank'>ทดสอบ Product Management</a><br>";
echo "<a href='admin.php?action=settings' target='_blank'>ทดสอบ System Settings</a><br>";
echo "<a href='reports.php' target='_blank'>ทดสอบ Reports</a><br>";
?> 