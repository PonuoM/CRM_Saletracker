<?php
/**
 * Fix Supervisor Customers Issue
 * แก้ไขปัญหาเฉพาะ Supervisor ใน customers.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>แก้ไขปัญหา Supervisor ใน customers.php</h1>";

// Test 1: Check and fix CustomerController for supervisor
echo "<h2>1. ตรวจสอบและแก้ไข CustomerController สำหรับ Supervisor</h2>";

if (file_exists('app/controllers/CustomerController.php')) {
    echo "✅ ไฟล์ CustomerController.php พบ<br>";
    
    $controllerContent = file_get_contents('app/controllers/CustomerController.php');
    
    // Check if supervisor case exists
    if (strpos($controllerContent, "case 'supervisor':") !== false) {
        echo "✅ มี case supervisor ใน switch statement<br>";
    } else {
        echo "❌ ไม่มี case supervisor ใน switch statement<br>";
    }
    
    // Check if getTeamCustomerIds method exists
    if (strpos($controllerContent, 'getTeamCustomerIds') !== false) {
        echo "✅ method getTeamCustomerIds มีอยู่แล้ว<br>";
    } else {
        echo "❌ ไม่พบ method getTeamCustomerIds<br>";
        
        // Add the missing method
        $methodToAdd = '
    /**
     * ดึง user_id ของสมาชิกทีมทั้งหมด
     * @param int $supervisorId ID ของ Supervisor
     * @return array รายการ user_id ของสมาชิกทีม
     */
    private function getTeamCustomerIds($supervisorId) {
        try {
            $teamMembers = $this->db->fetchAll(
                "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
                [\'supervisor_id\' => $supervisorId]
            );
            
            $teamCustomerIds = [];
            foreach ($teamMembers as $member) {
                $teamCustomerIds[] = $member[\'user_id\'];
            }
            
            return $teamCustomerIds;
        } catch (Exception $e) {
            error_log("Error in getTeamCustomerIds: " . $e->getMessage());
            return [];
        }
    }';
        
        // Insert method before the last closing brace
        $lastBracePos = strrpos($controllerContent, '}');
        $newControllerContent = substr($controllerContent, 0, $lastBracePos) . $methodToAdd . "\n}\n";
        
        file_put_contents('app/controllers/CustomerController.php', $newControllerContent);
        echo "✅ เพิ่ม method getTeamCustomerIds ใน CustomerController.php<br>";
    }
    
} else {
    echo "❌ ไม่พบไฟล์ CustomerController.php<br>";
}

// Test 2: Check and fix CustomerService for supervisor
echo "<h2>2. ตรวจสอบและแก้ไข CustomerService สำหรับ Supervisor</h2>";

if (file_exists('app/services/CustomerService.php')) {
    echo "✅ ไฟล์ CustomerService.php พบ<br>";
    
    $serviceContent = file_get_contents('app/services/CustomerService.php');
    
    // Check if getCustomersByBasket handles array for assigned_to
    if (strpos($serviceContent, 'is_array($filters[\'assigned_to\'])') !== false) {
        echo "✅ CustomerService รองรับ array สำหรับ assigned_to<br>";
    } else {
        echo "❌ CustomerService ไม่รองรับ array สำหรับ assigned_to<br>";
        
        // Fix the getCustomersByBasket method
        $pattern = '/if \(!empty\(\$filters\[\'assigned_to\'\]\)\) \{/';
        $replacement = 'if (!empty($filters[\'assigned_to\'])) {
            if (is_array($filters[\'assigned_to\'])) {
                // สำหรับ supervisor ที่ส่ง array ของ user_id
                $placeholders = str_repeat(\'?,\', count($filters[\'assigned_to\']) - 1) . \'?\';
                $sql .= " AND c.assigned_to IN ($placeholders)";
                $params = array_merge($params, $filters[\'assigned_to\']);
            } else {
                // สำหรับ telesales ที่ส่ง user_id เดียว
                $sql .= " AND c.assigned_to = :assigned_to";
                $params[\'assigned_to\'] = $filters[\'assigned_to\'];
            }';
        
        $newServiceContent = preg_replace($pattern, $replacement, $serviceContent);
        
        if ($newServiceContent !== $serviceContent) {
            file_put_contents('app/services/CustomerService.php', $newServiceContent);
            echo "✅ แก้ไข CustomerService ให้รองรับ array สำหรับ assigned_to<br>";
        } else {
            echo "❌ ไม่สามารถแก้ไข CustomerService ได้<br>";
        }
    }
    
} else {
    echo "❌ ไม่พบไฟล์ CustomerService.php<br>";
}

// Test 3: Create a safe version of customers.php for supervisor
echo "<h2>3. สร้าง customers.php ที่ปลอดภัยสำหรับ Supervisor</h2>";

$safeCustomersContent = '<?php
/**
 * Customer Management Entry Point - Safe Version for Supervisor
 */

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);

try {
    // Include required files
    require_once __DIR__ . \'/config/config.php\';
    require_once __DIR__ . \'/app/controllers/CustomerController.php\';

    // Initialize controller
    $controller = new CustomerController();

    // Get action from query string
    $action = $_GET[\'action\'] ?? \'index\';
    $customerId = $_GET[\'id\'] ?? null;

    // Route to appropriate method
    switch ($action) {
        case \'show\':
            if ($customerId) {
                $controller->show($customerId);
            } else {
                header(\'Location: customers.php\');
                exit;
            }
            break;
            
        case \'get_customer_address\':
            $controller->getCustomerAddress();
            break;
            
        default:
            $controller->index();
            break;
    }
} catch (Exception $e) {
    // Log error
    error_log("Error in customers.php: " . $e->getMessage());
    
    // Show error page
    echo "<h1>เกิดข้อผิดพลาด</h1>";
    echo "<p>ขออภัย เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง</p>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href=\'dashboard.php\'>กลับไปหน้า Dashboard</a>";
}
?>';

file_put_contents('customers_safe.php', $safeCustomersContent);
echo "✅ สร้างไฟล์ customers_safe.php สำหรับทดสอบ<br>";

// Test 4: Create a supervisor-specific test
echo "<h2>4. สร้างไฟล์ทดสอบเฉพาะ Supervisor</h2>";

$supervisorTestContent = '<?php
/**
 * Supervisor Customers Test
 * ทดสอบการทำงานของ customers.php สำหรับ Supervisor
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);

echo "<h1>ทดสอบ Supervisor Customers</h1>";

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
        
        // Get customers for team
        $customers = $customerService->getCustomersByBasket(\'assigned\', [\'assigned_to\' => $teamCustomerIds]);
        
        echo "<h2>ข้อมูลลูกค้า</h2>";
        echo "จำนวนลูกค้า: " . count($customers) . " คน<br>";
        
        if (!empty($customers)) {
            echo "<h3>รายการลูกค้า:</h3>";
            echo "<ul>";
            foreach (array_slice($customers, 0, 10) as $customer) {
                echo "<li>{$customer[\'first_name\']} {$customer[\'last_name\']} (ID: {$customer[\'customer_id\']})</li>";
            }
            echo "</ul>";
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

file_put_contents('supervisor_customers_test.php', $supervisorTestContent);
echo "✅ สร้างไฟล์ supervisor_customers_test.php<br>";

// Test 5: Check database schema
echo "<h2>5. ตรวจสอบโครงสร้างฐานข้อมูล</h2>";

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    // Check supervisor_id column
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
    
    // Check supervisor users
    $supervisors = $db->fetchAll(
        "SELECT u.user_id, u.username, u.full_name, r.role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.role_id 
         WHERE r.role_name = 'supervisor' AND u.is_active = 1"
    );
    
    if (empty($supervisors)) {
        echo "❌ ไม่พบผู้ใช้ Supervisor ในระบบ<br>";
        
        // Create supervisor user
        $supervisorData = [
            'username' => 'supervisor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Supervisor User',
            'email' => 'supervisor@example.com',
            'role_id' => 3,
            'company_id' => 1,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $db->insert('users', $supervisorData);
            echo "✅ สร้างผู้ใช้ Supervisor สำเร็จ<br>";
        } catch (Exception $e) {
            echo "❌ ไม่สามารถสร้างผู้ใช้ Supervisor: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ พบผู้ใช้ Supervisor " . count($supervisors) . " คน<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิด error: " . $e->getMessage() . "<br>";
}

echo "<h2>สรุปการแก้ไข</h2>";
echo "การแก้ไขเสร็จสิ้นแล้ว<br>";
echo "<br><strong>ขั้นตอนการทดสอบ:</strong><br>";
echo "1. <a href='debug_supervisor_customers.php'>รันไฟล์ debug เพื่อตรวจสอบปัญหา</a><br>";
echo "2. <a href='supervisor_customers_test.php'>ทดสอบ supervisor_customers_test.php</a><br>";
echo "3. <a href='customers_safe.php'>ทดสอบ customers_safe.php</a><br>";
echo "4. <a href='customers.php'>ทดสอบ customers.php ต้นฉบับ</a><br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
