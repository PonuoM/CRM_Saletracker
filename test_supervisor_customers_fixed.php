<?php
/**
 * Test Supervisor Customers - Fixed Version
 * ทดสอบการทำงานของ customers.php สำหรับ Supervisor หลังแก้ไข
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ทดสอบ Supervisor Customers - หลังแก้ไข</h1>";

// Check if user is logged in as supervisor
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'supervisor') {
    echo "<p>กรุณาเข้าสู่ระบบด้วยบัญชี Supervisor</p>";
    echo "<a href='login.php'>ไปหน้า Login</a>";
    exit;
}

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/CustomerService.php';
    
    $db = new Database();
    $customerService = new CustomerService();
    
    $userId = $_SESSION['user_id'];
    
    echo "<h2>ข้อมูล Supervisor</h2>";
    echo "User ID: {$userId}<br>";
    echo "Role: {$_SESSION['role_name']}<br>";
    
    // Get team members
    $teamMembers = $db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        ['supervisor_id' => $userId]
    );
    
    $teamCustomerIds = [];
    foreach ($teamMembers as $member) {
        $teamCustomerIds[] = $member['user_id'];
    }
    
    echo "<h2>ข้อมูลทีม</h2>";
    echo "จำนวนสมาชิกทีม: " . count($teamCustomerIds) . " คน<br>";
    
    if (!empty($teamCustomerIds)) {
        echo "User IDs: " . implode(', ', $teamCustomerIds) . "<br>";
        
        // Test 1: Test with array assigned_to (Supervisor case)
        echo "<h3>ทดสอบ 1: ใช้ array assigned_to (Supervisor)</h3>";
        try {
            $customers = $customerService->getCustomersByBasket('assigned', ['assigned_to' => $teamCustomerIds]);
            echo "✅ ดึงข้อมูลลูกค้าสำเร็จ: " . count($customers) . " คน<br>";
            
            if (!empty($customers)) {
                echo "<h4>ลูกค้า 5 คนแรก:</h4>";
                echo "<ul>";
                foreach (array_slice($customers, 0, 5) as $customer) {
                    echo "<li>{$customer['first_name']} {$customer['last_name']} (ID: {$customer['customer_id']}) - มอบหมายให้: {$customer['assigned_to_name']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>ไม่มีลูกค้าในทีม</p>";
            }
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
            echo "<p>Stack trace:</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        
        // Test 2: Test with single assigned_to (Telesales case)
        echo "<h3>ทดสอบ 2: ใช้ single assigned_to (Telesales)</h3>";
        try {
            $singleCustomer = $customerService->getCustomersByBasket('assigned', ['assigned_to' => $teamCustomerIds[0]]);
            echo "✅ ดึงข้อมูลลูกค้า single สำเร็จ: " . count($singleCustomer) . " คน<br>";
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
        }
        
        // Test 3: Test without assigned_to filter
        echo "<h3>ทดสอบ 3: ไม่ใช้ assigned_to filter</h3>";
        try {
            $allCustomers = $customerService->getCustomersByBasket('assigned');
            echo "✅ ดึงข้อมูลลูกค้าทั้งหมดสำเร็จ: " . count($allCustomers) . " คน<br>";
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
        }
        
        // Test 4: Test with other filters
        echo "<h3>ทดสอบ 4: ใช้ตัวกรองอื่นๆ</h3>";
        try {
            $filteredCustomers = $customerService->getCustomersByBasket('assigned', [
                'assigned_to' => $teamCustomerIds,
                'temperature' => 'hot'
            ]);
            echo "✅ ดึงข้อมูลลูกค้าพร้อมตัวกรองสำเร็จ: " . count($filteredCustomers) . " คน<br>";
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "<p>ไม่มีสมาชิกในทีม</p>";
    }
    
    echo "<h2>ทดสอบเสร็จสิ้น</h2>";
    echo "<p>หากทุกการทดสอบผ่าน ✅ แสดงว่าปัญหาได้รับการแก้ไขแล้ว</p>";
    echo "<a href='customers.php'>ไปหน้า customers.php</a><br>";
    echo "<a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
    
} catch (Exception $e) {
    echo "<h2>เกิดข้อผิดพลาด</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
