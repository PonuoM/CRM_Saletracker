<?php
/**
 * Test User Management System
 * ทดสอบระบบจัดการผู้ใช้
 */

// โหลด configuration
require_once __DIR__ . '/config/config.php';

session_start();

// ตรวจสอบการยืนยันตัวตน
if (!isset($_SESSION['user_id'])) {
    echo "❌ ไม่ได้เข้าสู่ระบบ กรุณาเข้าสู่ระบบก่อน<br>";
    echo "<a href='login.php'>เข้าสู่ระบบ</a>";
    exit;
}

// ตรวจสอบสิทธิ์ Admin
$roleName = $_SESSION['role_name'] ?? '';
if (!in_array($roleName, ['admin', 'super_admin'])) {
    echo "❌ ไม่มีสิทธิ์เข้าถึง ต้องเป็น Admin หรือ Super Admin<br>";
    echo "<a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
    exit;
}

require_once __DIR__ . '/app/controllers/AdminController.php';

$adminController = new AdminController();

echo "<h1>🧪 ทดสอบระบบจัดการผู้ใช้</h1>";
echo "<p><strong>ผู้ใช้ปัจจุบัน:</strong> {$_SESSION['full_name']} ({$_SESSION['role_name']})</p>";

// ทดสอบการเข้าถึงหน้า users
echo "<h2>1. ทดสอบการเข้าถึงหน้า Users</h2>";
echo "<a href='admin.php?action=users' target='_blank'>📋 ดูรายการผู้ใช้</a><br>";
echo "<a href='admin.php?action=users&subaction=create' target='_blank'>➕ สร้างผู้ใช้ใหม่</a><br>";

// ทดสอบการดึงข้อมูลผู้ใช้
echo "<h2>2. ทดสอบการดึงข้อมูลผู้ใช้</h2>";
try {
    require_once __DIR__ . '/app/core/Auth.php';
    require_once __DIR__ . '/app/core/Database.php';
    $db = new Database();
    $auth = new Auth($db);
    $users = $auth->getAllUsers();
    echo "✅ ดึงข้อมูลผู้ใช้สำเร็จ: " . count($users) . " คน<br>";
    
    if (count($users) > 0) {
        echo "<h3>รายการผู้ใช้:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>บทบาท</th><th>สถานะ</th></tr>";
        
        foreach (array_slice($users, 0, 5) as $user) {
            $status = $user['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['role_name']}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (count($users) > 5) {
            echo "<p>... และอีก " . (count($users) - 5) . " คน</p>";
        }
    }
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
}

// ทดสอบการดึงข้อมูลบทบาท
echo "<h2>3. ทดสอบการดึงข้อมูลบทบาท</h2>";
try {
    $sql = "SELECT * FROM roles ORDER BY role_id";
    $roles = $db->fetchAll($sql);
    echo "✅ ดึงข้อมูลบทบาทสำเร็จ: " . count($roles) . " บทบาท<br>";
    
    echo "<h3>รายการบทบาท:</h3>";
    echo "<ul>";
    foreach ($roles as $role) {
        echo "<li>{$role['role_name']} (ID: {$role['role_id']})</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
}

// ทดสอบการดึงข้อมูลบริษัท
echo "<h2>4. ทดสอบการดึงข้อมูลบริษัท</h2>";
try {
    $sql = "SELECT * FROM companies ORDER BY company_id";
    $companies = $db->fetchAll($sql);
    echo "✅ ดึงข้อมูลบริษัทสำเร็จ: " . count($companies) . " บริษัท<br>";
    
    if (count($companies) > 0) {
        echo "<h3>รายการบริษัท:</h3>";
        echo "<ul>";
        foreach ($companies as $company) {
            echo "<li>{$company['company_name']} (ID: {$company['company_id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>ไม่มีข้อมูลบริษัท</p>";
    }
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
}

// ทดสอบการสร้างผู้ใช้ทดสอบ
echo "<h2>5. ทดสอบการสร้างผู้ใช้ทดสอบ</h2>";
echo "<form method='POST' action='test_user_management.php'>";
echo "<input type='hidden' name='test_action' value='create_test_user'>";
echo "<label>ชื่อผู้ใช้ทดสอบ: <input type='text' name='test_username' value='test_user_" . time() . "' required></label><br>";
echo "<label>รหัสผ่าน: <input type='password' name='test_password' value='password123' required></label><br>";
echo "<label>ชื่อ-นามสกุล: <input type='text' name='test_full_name' value='ผู้ใช้ทดสอบ' required></label><br>";
echo "<label>บทบาท: <select name='test_role_id' required>";
foreach ($roles as $role) {
    echo "<option value='{$role['role_id']}'>{$role['role_name']}</option>";
}
echo "</select></label><br>";
echo "<button type='submit'>สร้างผู้ใช้ทดสอบ</button>";
echo "</form>";

// จัดการการสร้างผู้ใช้ทดสอบ
if (isset($_POST['test_action']) && $_POST['test_action'] === 'create_test_user') {
    echo "<h3>ผลการสร้างผู้ใช้ทดสอบ:</h3>";
    
    try {
        $userData = [
            'username' => $_POST['test_username'],
            'password' => $_POST['test_password'],
            'full_name' => $_POST['test_full_name'],
            'email' => $_POST['test_username'] . '@test.com',
            'phone' => '0812345678',
            'role_id' => $_POST['test_role_id'],
            'company_id' => null
        ];
        
        $result = $auth->createUser($userData);
        
        if ($result['success']) {
            echo "✅ สร้างผู้ใช้ทดสอบสำเร็จ!<br>";
            echo "ID: {$result['user_id']}<br>";
            echo "ชื่อผู้ใช้: {$userData['username']}<br>";
            echo "รหัสผ่าน: {$userData['password']}<br>";
        } else {
            echo "❌ สร้างผู้ใช้ทดสอบไม่สำเร็จ: {$result['message']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>6. ลิงก์ทดสอบเพิ่มเติม</h2>";
echo "<a href='admin.php?action=users&subaction=edit&id=1' target='_blank'>✏️ แก้ไขผู้ใช้ ID 1</a><br>";
echo "<a href='admin.php?action=users&subaction=delete&id=1' target='_blank'>🗑️ ลบผู้ใช้ ID 1</a><br>";

echo "<h2>7. ตรวจสอบ Database</h2>";
echo "<a href='test_database_users.php' target='_blank'>🔍 ตรวจสอบตาราง users ในฐานข้อมูล</a><br>";

echo "<hr>";
echo "<p><a href='admin.php'>← กลับไปหน้า Admin</a></p>";
echo "<p><a href='dashboard.php'>← กลับไปหน้า Dashboard</a></p>";
?>
