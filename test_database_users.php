<?php
/**
 * Test Database Users Table
 * ทดสอบตาราง users ในฐานข้อมูล
 */

// โหลด configuration
require_once __DIR__ . '/config/config.php';

echo "<h1>🔍 ตรวจสอบตาราง users ในฐานข้อมูล</h1>";

try {
    require_once __DIR__ . '/app/core/Database.php';
    $db = new Database();
    
    echo "<h2>1. ตรวจสอบโครงสร้างตาราง users</h2>";
    $sql = "DESCRIBE users";
    $columns = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>2. ตรวจสอบข้อมูลผู้ใช้ทั้งหมด</h2>";
    $sql = "SELECT u.*, r.role_name, c.company_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            ORDER BY u.created_at DESC";
    $users = $db->fetchAll($sql);
    
    echo "<p>พบผู้ใช้ทั้งหมด: " . count($users) . " คน</p>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>อีเมล</th><th>เบอร์โทร</th><th>บทบาท</th><th>บริษัท</th><th>สถานะ</th><th>วันที่สร้าง</th></tr>";
        
        foreach ($users as $user) {
            $status = $user['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            $email = $user['email'] ?? '-';
            $phone = $user['phone'] ?? '-';
            $company = $user['company_name'] ?? '-';
            
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$email}</td>";
            echo "<td>{$phone}</td>";
            echo "<td>{$user['role_name']}</td>";
            echo "<td>{$company}</td>";
            echo "<td>{$status}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>3. ตรวจสอบตาราง roles</h2>";
    $sql = "SELECT * FROM roles ORDER BY role_id";
    $roles = $db->fetchAll($sql);
    
    echo "<p>พบบทบาททั้งหมด: " . count($roles) . " บทบาท</p>";
    
    if (count($roles) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อบทบาท</th><th>คำอธิบาย</th><th>สิทธิ์</th></tr>";
        
        foreach ($roles as $role) {
            echo "<tr>";
            echo "<td>{$role['role_id']}</td>";
            echo "<td>{$role['role_name']}</td>";
            echo "<td>{$role['description']}</td>";
            echo "<td>{$role['permissions']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>4. ตรวจสอบตาราง companies</h2>";
    $sql = "SELECT * FROM companies ORDER BY company_id";
    $companies = $db->fetchAll($sql);
    
    echo "<p>พบบริษัททั้งหมด: " . count($companies) . " บริษัท</p>";
    
    if (count($companies) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อบริษัท</th><th>ที่อยู่</th><th>เบอร์โทร</th><th>อีเมล</th></tr>";
        
        foreach ($companies as $company) {
            echo "<tr>";
            echo "<td>{$company['company_id']}</td>";
            echo "<td>{$company['company_name']}</td>";
            echo "<td>{$company['address']}</td>";
            echo "<td>{$company['phone']}</td>";
            echo "<td>{$company['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>5. สถิติผู้ใช้ตามบทบาท</h2>";
    $sql = "SELECT r.role_name, COUNT(u.user_id) as user_count 
            FROM roles r 
            LEFT JOIN users u ON r.role_id = u.role_id 
            GROUP BY r.role_id, r.role_name 
            ORDER BY r.role_id";
    $roleStats = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>บทบาท</th><th>จำนวนผู้ใช้</th></tr>";
    
    foreach ($roleStats as $stat) {
        echo "<tr>";
        echo "<td>{$stat['role_name']}</td>";
        echo "<td>{$stat['user_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>6. ผู้ใช้ที่สร้างล่าสุด</h2>";
    $sql = "SELECT username, full_name, role_name, created_at 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            ORDER BY created_at DESC 
            LIMIT 5";
    $recentUsers = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>บทบาท</th><th>วันที่สร้าง</th></tr>";
    
    foreach ($recentUsers as $user) {
        echo "<tr>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role_name']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='test_user_management.php'>← กลับไปทดสอบระบบจัดการผู้ใช้</a></p>";
echo "<p><a href='admin.php?action=users'>← ไปหน้าจัดการผู้ใช้</a></p>";
?>
