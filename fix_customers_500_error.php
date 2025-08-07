<?php
/**
 * Fix 500 Error in customers.php
 * แก้ไขปัญหา 500 error ใน customers.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>แก้ไขปัญหา 500 Error ใน customers.php</h1>";

// Test 1: Check and fix config.php
echo "<h2>1. ตรวจสอบและแก้ไข config.php</h2>";

if (file_exists('config/config.php')) {
    echo "✅ ไฟล์ config.php พบ<br>";
    
    // Read config content
    $configContent = file_get_contents('config/config.php');
    
    // Check if APP_VIEWS is defined
    if (strpos($configContent, 'APP_VIEWS') === false) {
        echo "❌ ไม่พบการกำหนด APP_VIEWS ใน config.php<br>";
        
        // Add APP_VIEWS definition
        $newConfigContent = $configContent . "\n\n// Define APP_VIEWS if not exists\nif (!defined('APP_VIEWS')) {\n    define('APP_VIEWS', __DIR__ . '/../app/views/');\n}\n";
        
        file_put_contents('config/config.php', $newConfigContent);
        echo "✅ เพิ่มการกำหนด APP_VIEWS ใน config.php<br>";
    } else {
        echo "✅ APP_VIEWS ถูกกำหนดแล้วใน config.php<br>";
    }
    
} else {
    echo "❌ ไม่พบไฟล์ config.php<br>";
}

// Test 2: Check and fix CustomerController.php
echo "<h2>2. ตรวจสอบและแก้ไข CustomerController.php</h2>";

if (file_exists('app/controllers/CustomerController.php')) {
    echo "✅ ไฟล์ CustomerController.php พบ<br>";
    
    // Check if getTeamCustomerIds method exists
    $controllerContent = file_get_contents('app/controllers/CustomerController.php');
    
    if (strpos($controllerContent, 'getTeamCustomerIds') === false) {
        echo "❌ ไม่พบ method getTeamCustomerIds ใน CustomerController.php<br>";
        
        // Add the missing method
        $methodToAdd = '
    /**
     * ดึง user_id ของสมาชิกทีมทั้งหมด
     * @param int $supervisorId ID ของ Supervisor
     * @return array รายการ user_id ของสมาชิกทีม
     */
    private function getTeamCustomerIds($supervisorId) {
        $teamMembers = $this->db->fetchAll(
            "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
            [\'supervisor_id\' => $supervisorId]
        );
        $teamCustomerIds = [];
        foreach ($teamMembers as $member) {
            $teamCustomerIds[] = $member[\'user_id\'];
        }
        return $teamCustomerIds;
    }';
        
        // Insert method before the last closing brace
        $lastBracePos = strrpos($controllerContent, '}');
        $newControllerContent = substr($controllerContent, 0, $lastBracePos) . $methodToAdd . "\n}\n";
        
        file_put_contents('app/controllers/CustomerController.php', $newControllerContent);
        echo "✅ เพิ่ม method getTeamCustomerIds ใน CustomerController.php<br>";
    } else {
        echo "✅ method getTeamCustomerIds มีอยู่แล้วใน CustomerController.php<br>";
    }
    
} else {
    echo "❌ ไม่พบไฟล์ CustomerController.php<br>";
}

// Test 3: Check and fix view files
echo "<h2>3. ตรวจสอบและแก้ไขไฟล์ view</h2>";

$viewFiles = [
    'app/views/customers/index.php',
    'app/views/layouts/main.php'
];

foreach ($viewFiles as $viewFile) {
    if (file_exists($viewFile)) {
        echo "✅ {$viewFile} พบ<br>";
    } else {
        echo "❌ ไม่พบ {$viewFile}<br>";
        
        // Create basic view file if missing
        if ($viewFile === 'app/views/customers/index.php') {
            $basicViewContent = '<?php
/**
 * Customer Management Index View
 */
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1>จัดการลูกค้า</h1>
            <p>หน้าแสดงรายการลูกค้า</p>
        </div>
    </div>
</div>';
            
            // Create directory if not exists
            $dir = dirname($viewFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($viewFile, $basicViewContent);
            echo "✅ สร้างไฟล์ {$viewFile}<br>";
        }
    }
}

// Test 4: Check and fix Database class
echo "<h2>4. ตรวจสอบและแก้ไข Database class</h2>";

if (file_exists('app/core/Database.php')) {
    echo "✅ ไฟล์ Database.php พบ<br>";
    
    $dbContent = file_get_contents('app/core/Database.php');
    
    // Check if required methods exist
    $requiredMethods = ['fetchAll', 'fetchOne', 'insert', 'update', 'delete'];
    
    foreach ($requiredMethods as $method) {
        if (strpos($dbContent, "public function {$method}") !== false) {
            echo "✅ method {$method} มีอยู่แล้วใน Database.php<br>";
        } else {
            echo "❌ ไม่พบ method {$method} ใน Database.php<br>";
        }
    }
    
} else {
    echo "❌ ไม่พบไฟล์ Database.php<br>";
}

// Test 5: Test the fixed customers.php
echo "<h2>5. ทดสอบ customers.php หลังแก้ไข</h2>";

// Create a simple test version of customers.php
$testCustomersContent = '<?php
/**
 * Customer Management Entry Point - Fixed Version
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

file_put_contents('customers_fixed.php', $testCustomersContent);
echo "✅ สร้างไฟล์ customers_fixed.php สำหรับทดสอบ<br>";

echo "<h2>สรุปการแก้ไข</h2>";
echo "การแก้ไขเสร็จสิ้นแล้ว<br>";
echo "<br><strong>ขั้นตอนการทดสอบ:</strong><br>";
echo "1. <a href='debug_customers_500_error.php'>รันไฟล์ debug เพื่อตรวจสอบปัญหา</a><br>";
echo "2. <a href='customers_fixed.php'>ทดสอบ customers_fixed.php</a><br>";
echo "3. <a href='customers.php'>ทดสอบ customers.php ต้นฉบับ</a><br>";
echo "4. <a href='test_team_access.php'>ทดสอบการเข้าถึงหน้า team.php</a><br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
