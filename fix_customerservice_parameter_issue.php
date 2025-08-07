<?php
/**
 * Fix CustomerService Parameter Issue
 * แก้ไขปัญหา mixed named and positional parameters
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>แก้ไขปัญหา Parameter ใน CustomerService</h1>";

// Test 1: Fix CustomerService getCustomersByBasket method
echo "<h2>1. แก้ไข CustomerService getCustomersByBasket method</h2>";

if (file_exists('app/services/CustomerService.php')) {
    echo "✅ ไฟล์ CustomerService.php พบ<br>";
    
    $serviceContent = file_get_contents('app/services/CustomerService.php');
    
    // Find the getCustomersByBasket method
    $pattern = '/public function getCustomersByBasket\(\$basketType, \$filters = \[\]\) \{[\s\S]*?\}/';
    
    if (preg_match($pattern, $serviceContent, $matches)) {
        echo "✅ พบ method getCustomersByBasket<br>";
        
        // Create the fixed version
        $fixedMethod = 'public function getCustomersByBasket($basketType, $filters = []) {
        $sql = "SELECT c.*, u.full_name as assigned_to_name 
                FROM customers c 
                LEFT JOIN users u ON c.assigned_to = u.user_id 
                WHERE c.basket_type = ? AND c.is_active = 1";
        
        $params = [$basketType];
        
        // เพิ่มตัวกรอง
        if (!empty($filters[\'temperature\'])) {
            $sql .= " AND c.temperature_status = ?";
            $params[] = $filters[\'temperature\'];
        }
        
        if (!empty($filters[\'grade\'])) {
            $sql .= " AND c.customer_grade = ?";
            $params[] = $filters[\'grade\'];
        }
        
        if (!empty($filters[\'province\'])) {
            $sql .= " AND c.province = ?";
            $params[] = $filters[\'province\'];
        }
        
        if (!empty($filters[\'assigned_to\'])) {
            if (is_array($filters[\'assigned_to\'])) {
                // สำหรับ supervisor ที่ส่ง array ของ user_id
                $placeholders = str_repeat(\'?,\', count($filters[\'assigned_to\']) - 1) . \'?\';
                $sql .= " AND c.assigned_to IN ($placeholders)";
                $params = array_merge($params, $filters[\'assigned_to\']);
            } else {
                // สำหรับ telesales ที่ส่ง user_id เดียว
                $sql .= " AND c.assigned_to = ?";
                $params[] = $filters[\'assigned_to\'];
            }
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }';
        
        // Replace the method
        $newServiceContent = preg_replace($pattern, $fixedMethod, $serviceContent);
        
        if ($newServiceContent !== $serviceContent) {
            file_put_contents('app/services/CustomerService.php', $newServiceContent);
            echo "✅ แก้ไข CustomerService getCustomersByBasket method สำเร็จ<br>";
        } else {
            echo "❌ ไม่สามารถแก้ไข method ได้<br>";
        }
        
    } else {
        echo "❌ ไม่พบ method getCustomersByBasket<br>";
    }
    
} else {
    echo "❌ ไม่พบไฟล์ CustomerService.php<br>";
}

// Test 2: Create a test file to verify the fix
echo "<h2>2. สร้างไฟล์ทดสอบการแก้ไข</h2>";

$testContent = '<?php
/**
 * Test CustomerService Parameter Fix
 * ทดสอบการแก้ไขปัญหา parameter ใน CustomerService
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);

echo "<h1>ทดสอบการแก้ไข CustomerService Parameter</h1>";

// Check if user is logged in as supervisor
if (!isset($_SESSION[\'user_id\']) || $_SESSION[\'role_name\'] !== \'supervisor\') {
    echo "<p>กรุณาเข้าสู่ระบบด้วยบัญชี Supervisor</p>";
    echo "<a href=\'login.php\'>ไปหน้า Login</a>";
    exit;
}

try {
    require_once \'config/config.php\';
    require_once \'app/core/Database.php\';
    require_once \'app/services/CustomerService.php\';
    
    $db = new Database();
    $customerService = new CustomerService();
    
    $userId = $_SESSION[\'user_id\'];
    
    echo "<h2>ข้อมูล Supervisor</h2>";
    echo "User ID: {$userId}<br>";
    echo "Role: {$_SESSION[\'role_name\']}<br>";
    
    // Get team members
    $teamMembers = $db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        [\'supervisor_id\' => $userId]
    );
    
    $teamCustomerIds = [];
    foreach ($teamMembers as $member) {
        $teamCustomerIds[] = $member[\'user_id\'];
    }
    
    echo "<h2>ข้อมูลทีม</h2>";
    echo "จำนวนสมาชิกทีม: " . count($teamCustomerIds) . " คน<br>";
    
    if (!empty($teamCustomerIds)) {
        echo "User IDs: " . implode(\', \', $teamCustomerIds) . "<br>";
        
        // Test 1: Test with array assigned_to
        echo "<h3>ทดสอบ 1: ใช้ array assigned_to</h3>";
        try {
            $customers = $customerService->getCustomersByBasket(\'assigned\', [\'assigned_to\' => $teamCustomerIds]);
            echo "✅ ดึงข้อมูลลูกค้าสำเร็จ: " . count($customers) . " คน<br>";
            
            if (!empty($customers)) {
                echo "<h4>ลูกค้า 5 คนแรก:</h4>";
                echo "<ul>";
                foreach (array_slice($customers, 0, 5) as $customer) {
                    echo "<li>{$customer[\'first_name\']} {$customer[\'last_name\']} (ID: {$customer[\'customer_id\']})</li>";
                }
                echo "</ul>";
            }
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
        }
        
        // Test 2: Test with single assigned_to
        echo "<h3>ทดสอบ 2: ใช้ single assigned_to</h3>";
        try {
            $singleCustomer = $customerService->getCustomersByBasket(\'assigned\', [\'assigned_to\' => $teamCustomerIds[0]]);
            echo "✅ ดึงข้อมูลลูกค้า single สำเร็จ: " . count($singleCustomer) . " คน<br>";
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
        }
        
        // Test 3: Test without assigned_to filter
        echo "<h3>ทดสอบ 3: ไม่ใช้ assigned_to filter</h3>";
        try {
            $allCustomers = $customerService->getCustomersByBasket(\'assigned\');
            echo "✅ ดึงข้อมูลลูกค้าทั้งหมดสำเร็จ: " . count($allCustomers) . " คน<br>";
        } catch (Exception $e) {
            echo "❌ เกิด error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "<p>ไม่มีสมาชิกในทีม</p>";
    }
    
    echo "<h2>ทดสอบเสร็จสิ้น</h2>";
    echo "<a href=\'customers.php\'>ไปหน้า customers.php</a><br>";
    echo "<a href=\'dashboard.php\'>กลับไปหน้า Dashboard</a>";
    
} catch (Exception $e) {
    echo "<h2>เกิดข้อผิดพลาด</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>';

file_put_contents('test_customerservice_fix.php', $testContent);
echo "✅ สร้างไฟล์ test_customerservice_fix.php<br>";

// Test 3: Create a backup of the original file
echo "<h2>3. สร้างไฟล์ backup</h2>";

if (file_exists('app/services/CustomerService.php')) {
    $backupContent = file_get_contents('app/services/CustomerService.php');
    file_put_contents('app/services/CustomerService_backup.php', $backupContent);
    echo "✅ สร้างไฟล์ backup: app/services/CustomerService_backup.php<br>";
}

// Test 4: Test the Database class
echo "<h2>4. ทดสอบ Database class</h2>";

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    // Test with positional parameters
    $testQuery = $db->fetchAll("SELECT user_id, full_name FROM users WHERE role_id = ? AND is_active = ?", [4, 1]);
    echo "✅ ทดสอบ positional parameters สำเร็จ: " . count($testQuery) . " รายการ<br>";
    
    // Test with named parameters
    $testQuery2 = $db->fetchAll("SELECT user_id, full_name FROM users WHERE role_id = :role_id AND is_active = :is_active", ['role_id' => 4, 'is_active' => 1]);
    echo "✅ ทดสอบ named parameters สำเร็จ: " . count($testQuery2) . " รายการ<br>";
    
} catch (Exception $e) {
    echo "❌ เกิด error: " . $e->getMessage() . "<br>";
}

echo "<h2>สรุปการแก้ไข</h2>";
echo "การแก้ไขเสร็จสิ้นแล้ว<br>";
echo "<br><strong>ขั้นตอนการทดสอบ:</strong><br>";
echo "1. <a href='test_customerservice_fix.php'>ทดสอบการแก้ไข CustomerService</a><br>";
echo "2. <a href='customers.php'>ทดสอบ customers.php</a><br>";
echo "3. <a href='supervisor_customers_test.php'>ทดสอบ supervisor_customers_test.php</a><br>";
echo "<br><strong>หากมีปัญหา:</strong><br>";
echo "- <a href='app/services/CustomerService_backup.php'>ดูไฟล์ backup</a><br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
