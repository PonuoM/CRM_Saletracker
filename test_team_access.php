<?php
/**
 * Test Team Access
 * ทดสอบการเข้าถึงหน้า team.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ทดสอบการเข้าถึงหน้า Team</h1>";

// Test 1: Check if team.php exists
echo "<h2>1. ตรวจสอบไฟล์ team.php</h2>";
if (file_exists('team.php')) {
    echo "✅ ไฟล์ team.php พบ<br>";
    
    // Check file size
    $fileSize = filesize('team.php');
    echo "ขนาดไฟล์: " . $fileSize . " bytes<br>";
    
    // Check file permissions
    $permissions = fileperms('team.php');
    echo "สิทธิ์ไฟล์: " . substr(sprintf('%o', $permissions), -4) . "<br>";
    
} else {
    echo "❌ ไม่พบไฟล์ team.php<br>";
}

// Test 2: Check if Router.php has handleTeam method
echo "<h2>2. ตรวจสอบ Router.php</h2>";
if (file_exists('app/core/Router.php')) {
    echo "✅ ไฟล์ Router.php พบ<br>";
    
    $routerContent = file_get_contents('app/core/Router.php');
    
    if (strpos($routerContent, 'handleTeam') !== false) {
        echo "✅ Router.php มี method handleTeam()<br>";
    } else {
        echo "❌ Router.php ไม่มี method handleTeam()<br>";
    }
    
    if (strpos($routerContent, 'team.php') !== false) {
        echo "✅ Router.php มีการจัดการ route สำหรับ team.php<br>";
    } else {
        echo "❌ Router.php ไม่มีการจัดการ route สำหรับ team.php<br>";
    }
    
} else {
    echo "❌ ไม่พบไฟล์ Router.php<br>";
}

// Test 3: Check if sidebar has team link
echo "<h2>3. ตรวจสอบ Sidebar</h2>";
if (file_exists('app/views/components/sidebar.php')) {
    echo "✅ ไฟล์ sidebar.php พบ<br>";
    
    $sidebarContent = file_get_contents('app/views/components/sidebar.php');
    
    if (strpos($sidebarContent, 'team.php') !== false) {
        echo "✅ Sidebar มีลิงก์ไปยัง team.php<br>";
    } else {
        echo "❌ Sidebar ไม่มีลิงก์ไปยัง team.php<br>";
    }
    
    if (strpos($sidebarContent, 'supervisor') !== false) {
        echo "✅ Sidebar มีการตรวจสอบ role supervisor<br>";
    } else {
        echo "❌ Sidebar ไม่มีการตรวจสอบ role supervisor<br>";
    }
    
} else {
    echo "❌ ไม่พบไฟล์ sidebar.php<br>";
}

// Test 4: Check database connection and supervisor data
echo "<h2>4. ตรวจสอบฐานข้อมูลและข้อมูล Supervisor</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    // Check supervisor users
    $supervisors = $db->fetchAll(
        "SELECT u.user_id, u.username, u.full_name, r.role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.role_id 
         WHERE r.role_name = 'supervisor' AND u.is_active = 1"
    );
    
    if (empty($supervisors)) {
        echo "❌ ไม่พบผู้ใช้ Supervisor ในระบบ<br>";
    } else {
        echo "✅ พบผู้ใช้ Supervisor " . count($supervisors) . " คน:<br>";
        foreach ($supervisors as $supervisor) {
            echo "- {$supervisor['full_name']} (ID: {$supervisor['user_id']})<br>";
        }
    }
    
    // Check supervisor_id column
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'supervisor_id'");
    if (empty($columns)) {
        echo "❌ ไม่พบคอลัมน์ supervisor_id ในตาราง users<br>";
    } else {
        echo "✅ พบคอลัมน์ supervisor_id ในตาราง users<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error: " . $e->getMessage() . "<br>";
}

// Test 5: Test direct access to team.php
echo "<h2>5. ทดสอบการเข้าถึง team.php โดยตรง</h2>";
echo "ลองเข้าถึง: <a href='team.php' target='_blank'>team.php</a><br>";
echo "หรือ: <a href='https://www.prima49.com/Customer/team.php' target='_blank'>https://www.prima49.com/Customer/team.php</a><br>";

// Test 6: Check if team.php has proper authentication
echo "<h2>6. ตรวจสอบการ Authentication ใน team.php</h2>";
if (file_exists('team.php')) {
    $teamContent = file_get_contents('team.php');
    
    if (strpos($teamContent, 'isLoggedIn') !== false) {
        echo "✅ team.php มีการตรวจสอบการ login<br>";
    } else {
        echo "❌ team.php ไม่มีการตรวจสอบการ login<br>";
    }
    
    if (strpos($teamContent, 'supervisor') !== false) {
        echo "✅ team.php มีการตรวจสอบ role supervisor<br>";
    } else {
        echo "❌ team.php ไม่มีการตรวจสอบ role supervisor<br>";
    }
    
    if (strpos($teamContent, 'header(') !== false) {
        echo "✅ team.php มีการ redirect<br>";
    } else {
        echo "❌ team.php ไม่มีการ redirect<br>";
    }
}

echo "<h2>สรุปการทดสอบ</h2>";
echo "การทดสอบเสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบน<br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
