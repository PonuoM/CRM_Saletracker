<?php
/**
 * Test Supervisor System
 * ทดสอบระบบ Supervisor
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

echo "<h1>ทดสอบระบบ Supervisor</h1>";

try {
    $db = new Database();
    $auth = new Auth($db);
    
    echo "<h2>1. ทดสอบการเพิ่มคอลัมน์ supervisor_id</h2>";
    
    // ตรวจสอบว่าคอลัมน์ supervisor_id มีอยู่หรือไม่
    $sql = "SHOW COLUMNS FROM users LIKE 'supervisor_id'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "✅ คอลัมน์ supervisor_id มีอยู่แล้ว<br>";
    } else {
        echo "❌ คอลัมน์ supervisor_id ยังไม่มี ต้องรัน SQL script ก่อน<br>";
        echo "ให้รันไฟล์: add_supervisor_team_management.sql<br><br>";
    }
    
    echo "<h2>2. ทดสอบข้อมูลผู้ใช้ปัจจุบัน</h2>";
    
    $sql = "SELECT u.*, r.role_name, c.company_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            ORDER BY u.user_id";
    
    $users = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Company</th><th>Supervisor ID</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role_name']}</td>";
        echo "<td>{$user['company_name']}</td>";
        echo "<td>" . ($user['supervisor_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($user['is_active'] ? 'Active' : 'Inactive') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    echo "<h2>3. ทดสอบฟังก์ชันทีมของ Supervisor</h2>";
    
    // ทดสอบกับ supervisor (user_id = 2)
    $supervisorId = 2;
    
    echo "<h3>3.1 ทดสอบ getTeamMembers()</h3>";
    $teamMembers = $auth->getTeamMembers($supervisorId);
    
    if (!empty($teamMembers)) {
        echo "✅ พบสมาชิกทีม " . count($teamMembers) . " คน<br>";
        echo "<ul>";
        foreach ($teamMembers as $member) {
            echo "<li>{$member['full_name']} ({$member['username']}) - ลูกค้า: {$member['customer_count']}, คำสั่งซื้อ: {$member['order_count']}, ยอดขาย: ฿" . number_format($member['total_sales'] ?? 0, 2) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ ไม่พบสมาชิกในทีม<br>";
    }
    
    echo "<h3>3.2 ทดสอบ getTeamSummary()</h3>";
    $teamSummary = $auth->getTeamSummary($supervisorId);
    
    if ($teamSummary) {
        echo "✅ สรุปทีม:<br>";
        echo "- จำนวนสมาชิก: {$teamSummary['total_team_members']}<br>";
        echo "- ลูกค้าทั้งหมด: {$teamSummary['total_customers']}<br>";
        echo "- คำสั่งซื้อทั้งหมด: {$teamSummary['total_orders']}<br>";
        echo "- ยอดขายรวม: ฿" . number_format($teamSummary['total_sales_amount'] ?? 0, 2) . "<br>";
    } else {
        echo "❌ ไม่สามารถดึงข้อมูลสรุปทีมได้<br>";
    }
    
    echo "<h3>3.3 ทดสอบ getRecentTeamActivities()</h3>";
    $recentActivities = $auth->getRecentTeamActivities($supervisorId, 5);
    
    if (!empty($recentActivities)) {
        echo "✅ พบกิจกรรมล่าสุด " . count($recentActivities) . " รายการ<br>";
        echo "<ul>";
        foreach ($recentActivities as $activity) {
            $type = $activity['activity_type'] === 'order' ? 'คำสั่งซื้อ' : 'ลูกค้าใหม่';
            echo "<li>{$type}: {$activity['order_number']} - {$activity['user_name']} - {$activity['customer_name']} - " . date('d/m/Y H:i', strtotime($activity['created_at'])) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ ไม่พบกิจกรรมล่าสุด<br>";
    }
    
    echo "<h2>4. ทดสอบการมอบหมายทีม</h2>";
    
    // ทดสอบมอบหมาย telesales ให้ supervisor
    $telesalesId = 3; // telesales1
    
    echo "<h3>4.1 ทดสอบ assignToSupervisor()</h3>";
    $result = $auth->assignToSupervisor($telesalesId, $supervisorId);
    
    if ($result['success']) {
        echo "✅ " . $result['message'] . "<br>";
    } else {
        echo "❌ " . $result['message'] . "<br>";
    }
    
    echo "<h3>4.2 ทดสอบ removeFromSupervisor()</h3>";
    $result = $auth->removeFromSupervisor($telesalesId);
    
    if ($result['success']) {
        echo "✅ " . $result['message'] . "<br>";
    } else {
        echo "❌ " . $result['message'] . "<br>";
    }
    
    // มอบหมายกลับ
    $auth->assignToSupervisor($telesalesId, $supervisorId);
    
    echo "<h2>5. ทดสอบสิทธิ์ของ Supervisor</h2>";
    
    echo "<h3>5.1 เมนูที่ Supervisor ควรเห็น:</h3>";
    echo "<ul>";
    echo "<li>✅ แดชบอร์ด</li>";
    echo "<li>✅ จัดการลูกค้า</li>";
    echo "<li>✅ จัดการคำสั่งซื้อ</li>";
    echo "<li>✅ จัดการทีม</li>";
    echo "</ul>";
    
    echo "<h3>5.2 เมนูที่ Supervisor ไม่ควรเห็น:</h3>";
    echo "<ul>";
    echo "<li>❌ Admin Dashboard</li>";
    echo "<li>❌ จัดการผู้ใช้</li>";
    echo "<li>❌ จัดการสินค้า</li>";
    echo "<li>❌ ตั้งค่าระบบ</li>";
    echo "<li>❌ รายงาน</li>";
    echo "<li>❌ นำเข้า/ส่งออก</li>";
    echo "<li>❌ Workflow Management</li>";
    echo "<li>❌ ระบบแจกลูกค้า</li>";
    echo "</ul>";
    
    echo "<h2>6. สรุปการทดสอบ</h2>";
    
    echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ สิ่งที่ทำเสร็จแล้ว:</h3>";
    echo "<ul>";
    echo "<li>สร้าง SQL script สำหรับเพิ่มคอลัมน์ supervisor_id</li>";
    echo "<li>ปรับปรุง sidebar ให้ Supervisor เห็นเฉพาะเมนูที่จำเป็น</li>";
    echo "<li>สร้างหน้า team.php สำหรับจัดการทีม</li>";
    echo "<li>ปรับปรุง supervisor dashboard ให้แสดงข้อมูลทีม</li>";
    echo "<li>เพิ่มเมธอดใน Auth class สำหรับจัดการทีม</li>";
    echo "</ul>";
    
    echo "<h3>📋 สิ่งที่ต้องทำต่อไป:</h3>";
    echo "<ul>";
    echo "<li>รัน SQL script: add_supervisor_team_management.sql</li>";
    echo "<li>ทดสอบการเข้าสู่ระบบด้วย Supervisor</li>";
    echo "<li>ทดสอบการเข้าถึงหน้า team.php</li>";
    echo "<li>ทดสอบการแสดงข้อมูลทีมใน dashboard</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
