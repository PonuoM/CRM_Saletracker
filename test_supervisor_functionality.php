<?php
/**
 * Test Supervisor Functionality
 * ทดสอบฟังก์ชันการทำงานของ Supervisor
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

$db = new Database();
$auth = new Auth($db);

echo "<h1>ทดสอบฟังก์ชัน Supervisor</h1>";

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>1. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $testQuery = $db->fetchOne("SELECT 1 as test");
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
    exit;
}

// ตรวจสอบข้อมูลผู้ใช้ Supervisor
echo "<h2>2. ตรวจสอบข้อมูลผู้ใช้ Supervisor</h2>";
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

// ตรวจสอบสมาชิกทีมของ Supervisor
echo "<h2>3. ตรวจสอบสมาชิกทีมของ Supervisor</h2>";
if (!empty($supervisors)) {
    $supervisorId = $supervisors[0]['user_id'];
    
    $teamMembers = $db->fetchAll(
        "SELECT u.user_id, u.full_name, r.role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.role_id 
         WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1",
        ['supervisor_id' => $supervisorId]
    );
    
    if (empty($teamMembers)) {
        echo "❌ ไม่พบสมาชิกทีมสำหรับ Supervisor ID: {$supervisorId}<br>";
    } else {
        echo "✅ พบสมาชิกทีม " . count($teamMembers) . " คนสำหรับ Supervisor ID: {$supervisorId}:<br>";
        foreach ($teamMembers as $member) {
            echo "- {$member['full_name']} (Role: {$member['role_name']})<br>";
        }
    }
}

// ตรวจสอบข้อมูลลูกค้าที่เกี่ยวข้องกับทีม
echo "<h2>4. ตรวจสอบข้อมูลลูกค้าที่เกี่ยวข้องกับทีม</h2>";
if (!empty($supervisors)) {
    $supervisorId = $supervisors[0]['user_id'];
    
    // ดึง user_id ของสมาชิกทีม
    $teamMemberIds = $db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        ['supervisor_id' => $supervisorId]
    );
    
    if (!empty($teamMemberIds)) {
        $memberIds = array_column($teamMemberIds, 'user_id');
        $placeholders = str_repeat('?,', count($memberIds) - 1) . '?';
        
        $teamCustomers = $db->fetchAll(
            "SELECT c.customer_id, c.first_name, c.last_name, u.full_name as assigned_to_name
             FROM customers c
             JOIN users u ON c.assigned_to = u.user_id
             WHERE c.assigned_to IN ($placeholders) AND c.is_active = 1
             LIMIT 10",
            $memberIds
        );
        
        echo "✅ พบลูกค้าของทีม " . count($teamCustomers) . " คน (แสดง 10 คนแรก):<br>";
        foreach ($teamCustomers as $customer) {
            echo "- {$customer['first_name']} {$customer['last_name']} (มอบหมายให้: {$customer['assigned_to_name']})<br>";
        }
    } else {
        echo "❌ ไม่พบสมาชิกทีม<br>";
    }
}

// ตรวจสอบข้อมูลคำสั่งซื้อที่เกี่ยวข้องกับทีม
echo "<h2>5. ตรวจสอบข้อมูลคำสั่งซื้อที่เกี่ยวข้องกับทีม</h2>";
if (!empty($supervisors)) {
    $supervisorId = $supervisors[0]['user_id'];
    
    // ดึง user_id ของสมาชิกทีม
    $teamMemberIds = $db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        ['supervisor_id' => $supervisorId]
    );
    
    if (!empty($teamMemberIds)) {
        $memberIds = array_column($teamMemberIds, 'user_id');
        $placeholders = str_repeat('?,', count($memberIds) - 1) . '?';
        
        $teamOrders = $db->fetchAll(
            "SELECT o.order_id, o.order_number, o.total_amount, u.full_name as created_by_name
             FROM orders o
             JOIN users u ON o.created_by = u.user_id
             WHERE o.created_by IN ($placeholders) AND o.is_active = 1
             ORDER BY o.created_at DESC
             LIMIT 10",
            $memberIds
        );
        
        echo "✅ พบคำสั่งซื้อของทีม " . count($teamOrders) . " รายการ (แสดง 10 รายการล่าสุด):<br>";
        foreach ($teamOrders as $order) {
            echo "- Order #{$order['order_number']} (฿{$order['total_amount']}) - สร้างโดย: {$order['created_by_name']}<br>";
        }
    } else {
        echo "❌ ไม่พบสมาชิกทีม<br>";
    }
}

// ตรวจสอบการเข้าถึงไฟล์ team.php
echo "<h2>6. ตรวจสอบการเข้าถึงไฟล์ team.php</h2>";
if (file_exists('team.php')) {
    echo "✅ ไฟล์ team.php พบ<br>";
    
    // ตรวจสอบเนื้อหาของไฟล์
    $teamFileContent = file_get_contents('team.php');
    if (strpos($teamFileContent, 'supervisor') !== false) {
        echo "✅ ไฟล์ team.php มีการตรวจสอบสิทธิ์ supervisor<br>";
    } else {
        echo "❌ ไฟล์ team.php ไม่มีการตรวจสอบสิทธิ์ supervisor<br>";
    }
} else {
    echo "❌ ไม่พบไฟล์ team.php<br>";
}

// ตรวจสอบการ routing ใน Router.php
echo "<h2>7. ตรวจสอบการ routing ใน Router.php</h2>";
if (file_exists('app/core/Router.php')) {
    echo "✅ ไฟล์ Router.php พบ<br>";
    
    $routerContent = file_get_contents('app/core/Router.php');
    if (strpos($routerContent, 'team.php') !== false) {
        echo "✅ Router.php มีการจัดการ route สำหรับ team.php<br>";
    } else {
        echo "❌ Router.php ไม่มีการจัดการ route สำหรับ team.php<br>";
    }
    
    if (strpos($routerContent, 'handleTeam') !== false) {
        echo "✅ Router.php มี method handleTeam()<br>";
    } else {
        echo "❌ Router.php ไม่มี method handleTeam()<br>";
    }
} else {
    echo "❌ ไม่พบไฟล์ Router.php<br>";
}

echo "<h2>สรุปการทดสอบ</h2>";
echo "การทดสอบเสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบนเพื่อยืนยันว่าฟังก์ชัน Supervisor ทำงานได้ถูกต้อง<br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
