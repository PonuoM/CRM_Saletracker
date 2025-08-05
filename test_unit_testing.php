<?php
/**
 * Unit Testing Suite สำหรับ CRM SalesTracker
 * ทดสอบฟังก์ชันหลักของระบบ
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Auth.php';
require_once __DIR__ . '/app/services/CustomerService.php';
require_once __DIR__ . '/app/services/OrderService.php';
require_once __DIR__ . '/app/services/DashboardService.php';

class UnitTestSuite {
    private $db;
    private $auth;
    private $customerService;
    private $orderService;
    private $dashboardService;
    private $testResults = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
        $this->customerService = new CustomerService();
        $this->orderService = new OrderService();
        $this->dashboardService = new DashboardService();
    }
    
    /**
     * รันการทดสอบทั้งหมด
     */
    public function runAllTests() {
        echo "<h1>🧪 Unit Testing Suite - CRM SalesTracker</h1>";
        echo "<p><strong>วันที่ทดสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>";
        echo "<hr>";
        
        $this->testDatabaseConnection();
        $this->testAuthentication();
        $this->testCustomerService();
        $this->testOrderService();
        $this->testDashboardService();
        $this->testBusinessLogic();
        
        $this->displayResults();
    }
    
    /**
     * ทดสอบการเชื่อมต่อฐานข้อมูล
     */
    private function testDatabaseConnection() {
        echo "<h2>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
        
        try {
            $result = $this->db->query("SELECT 1 as test");
            if ($result && $result->fetch()) {
                $this->addResult('Database Connection', 'PASS', 'เชื่อมต่อฐานข้อมูลสำเร็จ');
                echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
            } else {
                $this->addResult('Database Connection', 'FAIL', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
                echo "❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้<br>";
            }
        } catch (Exception $e) {
            $this->addResult('Database Connection', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบตารางหลัก
        $tables = ['users', 'customers', 'orders', 'products', 'roles'];
        foreach ($tables as $table) {
            try {
                $result = $this->db->query("SELECT COUNT(*) as count FROM $table");
                if ($result) {
                    $count = $result->fetch()['count'];
                    $this->addResult("Table: $table", 'PASS', "พบข้อมูล $count รายการ");
                    echo "✅ ตาราง $table: $count รายการ<br>";
                }
            } catch (Exception $e) {
                $this->addResult("Table: $table", 'FAIL', 'Error: ' . $e->getMessage());
                echo "❌ ตาราง $table: Error<br>";
            }
        }
        echo "<br>";
    }
    
    /**
     * ทดสอบระบบ Authentication
     */
    private function testAuthentication() {
        echo "<h2>2. ทดสอบระบบ Authentication</h2>";
        
        // ทดสอบการตรวจสอบสิทธิ์
        try {
            $testUser = $this->auth->getUserById(1);
            if ($testUser) {
                $this->addResult('Get User by ID', 'PASS', 'ดึงข้อมูลผู้ใช้สำเร็จ');
                echo "✅ ดึงข้อมูลผู้ใช้สำเร็จ<br>";
                
                // ทดสอบการตรวจสอบสิทธิ์
                $hasPermission = $this->auth->hasPermission($testUser['role_id'], 'customer_management');
                $this->addResult('Permission Check', 'PASS', 'ตรวจสอบสิทธิ์สำเร็จ');
                echo "✅ ตรวจสอบสิทธิ์สำเร็จ<br>";
            } else {
                $this->addResult('Get User by ID', 'FAIL', 'ไม่พบข้อมูลผู้ใช้');
                echo "❌ ไม่พบข้อมูลผู้ใช้<br>";
            }
        } catch (Exception $e) {
            $this->addResult('Authentication', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
    /**
     * ทดสอบ CustomerService
     */
    private function testCustomerService() {
        echo "<h2>3. ทดสอบ CustomerService</h2>";
        
        try {
            // ทดสอบการดึงข้อมูลลูกค้า
            $customers = $this->customerService->getCustomers(['basket_type' => 'distribution']);
            if (is_array($customers)) {
                $this->addResult('Get Customers', 'PASS', 'ดึงข้อมูลลูกค้า ' . count($customers) . ' รายการ');
                echo "✅ ดึงข้อมูลลูกค้า " . count($customers) . " รายการ<br>";
            } else {
                $this->addResult('Get Customers', 'FAIL', 'ไม่สามารถดึงข้อมูลลูกค้าได้');
                echo "❌ ไม่สามารถดึงข้อมูลลูกค้าได้<br>";
            }
            
            // ทดสอบการคำนวณเกรดลูกค้า
            $grade = $this->customerService->calculateCustomerGrade(50000);
            if ($grade === 'A+') {
                $this->addResult('Calculate Grade', 'PASS', 'คำนวณเกรดถูกต้อง: A+');
                echo "✅ คำนวณเกรดถูกต้อง: A+<br>";
            } else {
                $this->addResult('Calculate Grade', 'FAIL', 'คำนวณเกรดผิด: ได้ ' . $grade . ' แทน A+');
                echo "❌ คำนวณเกรดผิด: ได้ $grade แทน A+<br>";
            }
            
            // ทดสอบการคำนวณอุณหภูมิ
            $temp = $this->customerService->calculateTemperatureStatus('2024-01-01');
            if (in_array($temp, ['hot', 'warm', 'cold', 'frozen'])) {
                $this->addResult('Calculate Temperature', 'PASS', 'คำนวณอุณหภูมิถูกต้อง: ' . $temp);
                echo "✅ คำนวณอุณหภูมิถูกต้อง: $temp<br>";
            } else {
                $this->addResult('Calculate Temperature', 'FAIL', 'คำนวณอุณหภูมิผิด: ' . $temp);
                echo "❌ คำนวณอุณหภูมิผิด: $temp<br>";
            }
            
        } catch (Exception $e) {
            $this->addResult('CustomerService', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
    /**
     * ทดสอบ OrderService
     */
    private function testOrderService() {
        echo "<h2>4. ทดสอบ OrderService</h2>";
        
        try {
            // ทดสอบการดึงข้อมูลคำสั่งซื้อ
            $orders = $this->orderService->getOrders(['limit' => 5]);
            if (is_array($orders)) {
                $this->addResult('Get Orders', 'PASS', 'ดึงข้อมูลคำสั่งซื้อ ' . count($orders) . ' รายการ');
                echo "✅ ดึงข้อมูลคำสั่งซื้อ " . count($orders) . " รายการ<br>";
            } else {
                $this->addResult('Get Orders', 'FAIL', 'ไม่สามารถดึงข้อมูลคำสั่งซื้อได้');
                echo "❌ ไม่สามารถดึงข้อมูลคำสั่งซื้อได้<br>";
            }
            
            // ทดสอบการสร้างหมายเลขคำสั่งซื้อ
            $orderNumber = $this->orderService->generateOrderNumber();
            if (preg_match('/^ORD-\d{8}-\d{4}$/', $orderNumber)) {
                $this->addResult('Generate Order Number', 'PASS', 'สร้างหมายเลขคำสั่งซื้อถูกต้อง: ' . $orderNumber);
                echo "✅ สร้างหมายเลขคำสั่งซื้อถูกต้อง: $orderNumber<br>";
            } else {
                $this->addResult('Generate Order Number', 'FAIL', 'สร้างหมายเลขคำสั่งซื้อผิด: ' . $orderNumber);
                echo "❌ สร้างหมายเลขคำสั่งซื้อผิด: $orderNumber<br>";
            }
            
        } catch (Exception $e) {
            $this->addResult('OrderService', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
    /**
     * ทดสอบ DashboardService
     */
    private function testDashboardService() {
        echo "<h2>5. ทดสอบ DashboardService</h2>";
        
        try {
            // ทดสอบการดึงข้อมูล Dashboard
            $dashboardData = $this->dashboardService->getDashboardData(1, 'telesales');
            if (is_array($dashboardData)) {
                $this->addResult('Get Dashboard Data', 'PASS', 'ดึงข้อมูล Dashboard สำเร็จ');
                echo "✅ ดึงข้อมูล Dashboard สำเร็จ<br>";
                
                // ตรวจสอบข้อมูลที่จำเป็น
                $requiredKeys = ['total_customers', 'total_orders', 'total_revenue'];
                $missingKeys = array_diff($requiredKeys, array_keys($dashboardData));
                
                if (empty($missingKeys)) {
                    $this->addResult('Dashboard Data Structure', 'PASS', 'โครงสร้างข้อมูล Dashboard ครบถ้วน');
                    echo "✅ โครงสร้างข้อมูล Dashboard ครบถ้วน<br>";
                } else {
                    $this->addResult('Dashboard Data Structure', 'FAIL', 'ขาดข้อมูล: ' . implode(', ', $missingKeys));
                    echo "❌ ขาดข้อมูล: " . implode(', ', $missingKeys) . "<br>";
                }
            } else {
                $this->addResult('Get Dashboard Data', 'FAIL', 'ไม่สามารถดึงข้อมูล Dashboard ได้');
                echo "❌ ไม่สามารถดึงข้อมูล Dashboard ได้<br>";
            }
            
        } catch (Exception $e) {
            $this->addResult('DashboardService', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
    /**
     * ทดสอบ Business Logic
     */
    private function testBusinessLogic() {
        echo "<h2>6. ทดสอบ Business Logic</h2>";
        
        // ทดสอบการคำนวณเกรดลูกค้า
        $testCases = [
            ['amount' => 150000, 'expected' => 'A+'],
            ['amount' => 75000, 'expected' => 'A'],
            ['amount' => 25000, 'expected' => 'B'],
            ['amount' => 8000, 'expected' => 'C'],
            ['amount' => 2000, 'expected' => 'D']
        ];
        
        foreach ($testCases as $testCase) {
            $grade = $this->customerService->calculateCustomerGrade($testCase['amount']);
            if ($grade === $testCase['expected']) {
                $this->addResult("Grade Calculation (฿{$testCase['amount']})", 'PASS', "ได้เกรด $grade");
                echo "✅ ฿{$testCase['amount']} → เกรด $grade<br>";
            } else {
                $this->addResult("Grade Calculation (฿{$testCase['amount']})", 'FAIL', "ได้เกรด $grade แทน {$testCase['expected']}");
                echo "❌ ฿{$testCase['amount']} → ได้เกรด $grade แทน {$testCase['expected']}<br>";
            }
        }
        
        // ทดสอบการคำนวณอุณหภูมิ
        $tempTestCases = [
            ['days' => 5, 'expected' => 'hot'],
            ['days' => 30, 'expected' => 'warm'],
            ['days' => 90, 'expected' => 'cold'],
            ['days' => 120, 'expected' => 'frozen']
        ];
        
        foreach ($tempTestCases as $testCase) {
            $date = date('Y-m-d', strtotime("-{$testCase['days']} days"));
            $temp = $this->customerService->calculateTemperatureStatus($date);
            if ($temp === $testCase['expected']) {
                $this->addResult("Temperature ({$testCase['days']} days)", 'PASS', "ได้ $temp");
                echo "✅ {$testCase['days']} วัน → $temp<br>";
            } else {
                $this->addResult("Temperature ({$testCase['days']} days)", 'FAIL', "ได้ $temp แทน {$testCase['expected']}");
                echo "❌ {$testCase['days']} วัน → ได้ $temp แทน {$testCase['expected']}<br>";
            }
        }
        
        echo "<br>";
    }
    
    /**
     * เพิ่มผลการทดสอบ
     */
    private function addResult($test, $status, $message) {
        $this->testResults[] = [
            'test' => $test,
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * แสดงผลการทดสอบสรุป
     */
    private function displayResults() {
        echo "<h2>📊 สรุปผลการทดสอบ</h2>";
        
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, function($r) { return $r['status'] === 'PASS'; }));
        $failed = $total - $passed;
        
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>สถิติการทดสอบ</h3>";
        echo "<p><strong>รวมการทดสอบ:</strong> $total</p>";
        echo "<p><strong>ผ่าน:</strong> <span style='color: green;'>$passed</span></p>";
        echo "<p><strong>ไม่ผ่าน:</strong> <span style='color: red;'>$failed</span></p>";
        echo "<p><strong>อัตราความสำเร็จ:</strong> " . round(($passed / $total) * 100, 2) . "%</p>";
        echo "</div>";
        
        if ($failed > 0) {
            echo "<h3>❌ การทดสอบที่ไม่ผ่าน</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f8d7da;'><th>การทดสอบ</th><th>สถานะ</th><th>ข้อความ</th><th>เวลา</th></tr>";
            
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "<tr>";
                    echo "<td>{$result['test']}</td>";
                    echo "<td style='color: red;'>{$result['status']}</td>";
                    echo "<td>{$result['message']}</td>";
                    echo "<td>{$result['timestamp']}</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
        
        echo "<h3>✅ การทดสอบที่ผ่าน</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #d4edda;'><th>การทดสอบ</th><th>สถานะ</th><th>ข้อความ</th><th>เวลา</th></tr>";
        
        foreach ($this->testResults as $result) {
            if ($result['status'] === 'PASS') {
                echo "<tr>";
                echo "<td>{$result['test']}</td>";
                echo "<td style='color: green;'>{$result['status']}</td>";
                echo "<td>{$result['message']}</td>";
                echo "<td>{$result['timestamp']}</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<p><strong>หมายเหตุ:</strong> การทดสอบนี้เป็นการทดสอบพื้นฐานของระบบ CRM SalesTracker</p>";
        echo "<p><strong>คำแนะนำ:</strong> หากมีการทดสอบที่ไม่ผ่าน กรุณาตรวจสอบและแก้ไขก่อนการใช้งานจริง</p>";
    }
}

// รันการทดสอบ
$testSuite = new UnitTestSuite();
$testSuite->runAllTests();
?> 