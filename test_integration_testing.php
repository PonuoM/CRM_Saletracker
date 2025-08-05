<?php
/**
 * Integration Testing Suite สำหรับ CRM SalesTracker
 * ทดสอบ API endpoints และ database operations
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Auth.php';
require_once __DIR__ . '/app/controllers/CustomerController.php';
require_once __DIR__ . '/app/controllers/OrderController.php';
require_once __DIR__ . '/app/controllers/AdminController.php';

class IntegrationTestSuite {
    private $db;
    private $auth;
    private $customerController;
    private $orderController;
    private $adminController;
    private $testResults = [];
    private $testData = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
        $this->customerController = new CustomerController();
        $this->orderController = new OrderController();
        $this->adminController = new AdminController();
    }
    
    /**
     * รันการทดสอบทั้งหมด
     */
    public function runAllTests() {
        echo "<h1>🔗 Integration Testing Suite - CRM SalesTracker</h1>";
        echo "<p><strong>วันที่ทดสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>";
        echo "<hr>";
        
        $this->testAPICustomerEndpoints();
        $this->testAPIOrderEndpoints();
        $this->testAPIAdminEndpoints();
        $this->testDatabaseTransactions();
        $this->testFileUploadOperations();
        $this->testRoleBasedAccessControl();
        
        $this->displayResults();
    }
    
    /**
     * ทดสอบ Customer API Endpoints
     */
    private function testAPICustomerEndpoints() {
        echo "<h2>1. ทดสอบ Customer API Endpoints</h2>";
        
        // ทดสอบ GET /api/customers.php
        try {
            $_GET['basket_type'] = 'distribution';
            $response = $this->customerController->index();
            
            if (is_array($response) && isset($response['success'])) {
                $this->addResult('GET /api/customers.php', 'PASS', 'API response valid');
                echo "✅ GET /api/customers.php - สำเร็จ<br>";
            } else {
                $this->addResult('GET /api/customers.php', 'FAIL', 'Invalid API response');
                echo "❌ GET /api/customers.php - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('GET /api/customers.php', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ GET /api/customers.php - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ POST /api/customers.php (assign customers)
        try {
            $_POST['action'] = 'assign';
            $_POST['telesales_id'] = 1;
            $_POST['customer_ids'] = [1, 2, 3];
            
            $response = $this->customerController->assignCustomers();
            
            if (is_array($response) && isset($response['success'])) {
                $this->addResult('POST /api/customers.php (assign)', 'PASS', 'Assign customers successful');
                echo "✅ POST /api/customers.php (assign) - สำเร็จ<br>";
            } else {
                $this->addResult('POST /api/customers.php (assign)', 'FAIL', 'Assign customers failed');
                echo "❌ POST /api/customers.php (assign) - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('POST /api/customers.php (assign)', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ POST /api/customers.php (assign) - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ POST /api/customers.php (recall)
        try {
            $_POST['action'] = 'recall';
            $_POST['customer_id'] = 1;
            $_POST['reason'] = 'Test recall';
            
            $response = $this->customerController->recallCustomer();
            
            if (is_array($response) && isset($response['success'])) {
                $this->addResult('POST /api/customers.php (recall)', 'PASS', 'Recall customer successful');
                echo "✅ POST /api/customers.php (recall) - สำเร็จ<br>";
            } else {
                $this->addResult('POST /api/customers.php (recall)', 'FAIL', 'Recall customer failed');
                echo "❌ POST /api/customers.php (recall) - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('POST /api/customers.php (recall)', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ POST /api/customers.php (recall) - Error: " . $e->getMessage() . "<br>";
        }
        
        echo "<br>";
    }
    
    /**
     * ทดสอบ Order API Endpoints
     */
    private function testAPIOrderEndpoints() {
        echo "<h2>2. ทดสอบ Order API Endpoints</h2>";
        
        // ทดสอบ GET /orders.php
        try {
            $_GET['action'] = 'index';
            $response = $this->orderController->index();
            
            if (is_array($response) && isset($response['orders'])) {
                $this->addResult('GET /orders.php', 'PASS', 'Get orders successful');
                echo "✅ GET /orders.php - สำเร็จ<br>";
            } else {
                $this->addResult('GET /orders.php', 'FAIL', 'Get orders failed');
                echo "❌ GET /orders.php - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('GET /orders.php', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ GET /orders.php - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ POST /orders.php (create order)
        try {
            $_POST['action'] = 'store';
            $_POST['customer_id'] = 1;
            $_POST['order_date'] = date('Y-m-d');
            $_POST['total_amount'] = 1000;
            $_POST['discount_amount'] = 100;
            $_POST['net_amount'] = 900;
            $_POST['payment_method'] = 'cash';
            $_POST['delivery_address'] = 'Test Address';
            $_POST['items'] = [
                ['product_id' => 1, 'quantity' => 2, 'unit_price' => 500, 'total_price' => 1000]
            ];
            
            $response = $this->orderController->store();
            
            if (is_array($response) && isset($response['success'])) {
                $this->addResult('POST /orders.php (create)', 'PASS', 'Create order successful');
                echo "✅ POST /orders.php (create) - สำเร็จ<br>";
                $this->testData['order_id'] = $response['order_id'] ?? null;
            } else {
                $this->addResult('POST /orders.php (create)', 'FAIL', 'Create order failed');
                echo "❌ POST /orders.php (create) - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('POST /orders.php (create)', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ POST /orders.php (create) - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ GET /orders.php?action=show
        if (isset($this->testData['order_id'])) {
            try {
                $_GET['action'] = 'show';
                $_GET['id'] = $this->testData['order_id'];
                
                $response = $this->orderController->show();
                
                if (is_array($response) && isset($response['order'])) {
                    $this->addResult('GET /orders.php (show)', 'PASS', 'Show order successful');
                    echo "✅ GET /orders.php (show) - สำเร็จ<br>";
                } else {
                    $this->addResult('GET /orders.php (show)', 'FAIL', 'Show order failed');
                    echo "❌ GET /orders.php (show) - ไม่สำเร็จ<br>";
                }
            } catch (Exception $e) {
                $this->addResult('GET /orders.php (show)', 'FAIL', 'Error: ' . $e->getMessage());
                echo "❌ GET /orders.php (show) - Error: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<br>";
    }
    
    /**
     * ทดสอบ Admin API Endpoints
     */
    private function testAPIAdminEndpoints() {
        echo "<h2>3. ทดสอบ Admin API Endpoints</h2>";
        
        // ทดสอบ GET /admin.php
        try {
            $_GET['action'] = 'index';
            $response = $this->adminController->index();
            
            if (is_array($response) && isset($response['stats'])) {
                $this->addResult('GET /admin.php', 'PASS', 'Admin dashboard successful');
                echo "✅ GET /admin.php - สำเร็จ<br>";
            } else {
                $this->addResult('GET /admin.php', 'FAIL', 'Admin dashboard failed');
                echo "❌ GET /admin.php - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('GET /admin.php', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ GET /admin.php - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ GET /admin.php?action=users
        try {
            $_GET['action'] = 'users';
            $response = $this->adminController->users();
            
            if (is_array($response) && isset($response['users'])) {
                $this->addResult('GET /admin.php (users)', 'PASS', 'User management successful');
                echo "✅ GET /admin.php (users) - สำเร็จ<br>";
            } else {
                $this->addResult('GET /admin.php (users)', 'FAIL', 'User management failed');
                echo "❌ GET /admin.php (users) - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('GET /admin.php (users)', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ GET /admin.php (users) - Error: " . $e->getMessage() . "<br>";
        }
        
        echo "<br>";
    }
    
    /**
     * ทดสอบ Database Transactions
     */
    private function testDatabaseTransactions() {
        echo "<h2>4. ทดสอบ Database Transactions</h2>";
        
        // ทดสอบ Transaction Rollback
        try {
            $this->db->beginTransaction();
            
            // Insert test data
            $this->db->query("INSERT INTO test_transaction (name, value) VALUES ('test1', 100)");
            $this->db->query("INSERT INTO test_transaction (name, value) VALUES ('test2', 200)");
            
            // Intentionally cause an error
            $this->db->query("INSERT INTO test_transaction (invalid_column) VALUES ('error')");
            
            $this->db->commit();
            $this->addResult('Transaction Rollback', 'FAIL', 'Transaction should have rolled back');
            echo "❌ Transaction Rollback - ไม่สำเร็จ<br>";
        } catch (Exception $e) {
            $this->db->rollback();
            $this->addResult('Transaction Rollback', 'PASS', 'Transaction rolled back successfully');
            echo "✅ Transaction Rollback - สำเร็จ<br>";
        }
        
        // ทดสอบ Data Integrity
        try {
            // Test foreign key constraint
            $this->db->query("INSERT INTO orders (customer_id, order_number) VALUES (99999, 'TEST-001')");
            $this->addResult('Foreign Key Constraint', 'FAIL', 'Should not allow invalid customer_id');
            echo "❌ Foreign Key Constraint - ไม่สำเร็จ<br>";
        } catch (Exception $e) {
            $this->addResult('Foreign Key Constraint', 'PASS', 'Foreign key constraint working');
            echo "✅ Foreign Key Constraint - สำเร็จ<br>";
        }
        
        // ทดสอบ Unique Constraint
        try {
            $this->db->query("INSERT INTO users (username, password_hash, full_name, role_id) VALUES ('admin', 'test', 'Test User', 1)");
            $this->db->query("INSERT INTO users (username, password_hash, full_name, role_id) VALUES ('admin', 'test', 'Test User 2', 1)");
            $this->addResult('Unique Constraint', 'FAIL', 'Should not allow duplicate username');
            echo "❌ Unique Constraint - ไม่สำเร็จ<br>";
        } catch (Exception $e) {
            $this->addResult('Unique Constraint', 'PASS', 'Unique constraint working');
            echo "✅ Unique Constraint - สำเร็จ<br>";
        }
        
        echo "<br>";
    }
    
    /**
     * ทดสอบ File Upload Operations
     */
    private function testFileUploadOperations() {
        echo "<h2>5. ทดสอบ File Upload Operations</h2>";
        
        // ทดสอบ CSV Import
        try {
            $testFile = 'test_import.csv';
            $csvContent = "first_name,last_name,phone,email\n";
            $csvContent .= "Test,User,081-123-4567,test@example.com\n";
            
            if (file_put_contents($testFile, $csvContent)) {
                $this->addResult('CSV File Creation', 'PASS', 'Test CSV file created');
                echo "✅ CSV File Creation - สำเร็จ<br>";
                
                // Test file reading
                $content = file_get_contents($testFile);
                if ($content === $csvContent) {
                    $this->addResult('CSV File Reading', 'PASS', 'CSV file read successfully');
                    echo "✅ CSV File Reading - สำเร็จ<br>";
                } else {
                    $this->addResult('CSV File Reading', 'FAIL', 'CSV file read failed');
                    echo "❌ CSV File Reading - ไม่สำเร็จ<br>";
                }
                
                // Clean up
                unlink($testFile);
            } else {
                $this->addResult('CSV File Creation', 'FAIL', 'Could not create test CSV file');
                echo "❌ CSV File Creation - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('File Upload Operations', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ File Upload Operations - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ File Permissions
        try {
            $testDir = 'test_upload_dir';
            if (!is_dir($testDir)) {
                mkdir($testDir, 0755);
            }
            
            if (is_writable($testDir)) {
                $this->addResult('Directory Permissions', 'PASS', 'Upload directory is writable');
                echo "✅ Directory Permissions - สำเร็จ<br>";
            } else {
                $this->addResult('Directory Permissions', 'FAIL', 'Upload directory is not writable');
                echo "❌ Directory Permissions - ไม่สำเร็จ<br>";
            }
            
            // Clean up
            if (is_dir($testDir)) {
                rmdir($testDir);
            }
        } catch (Exception $e) {
            $this->addResult('Directory Permissions', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Directory Permissions - Error: " . $e->getMessage() . "<br>";
        }
        
        echo "<br>";
    }
    
    /**
     * ทดสอบ Role-Based Access Control
     */
    private function testRoleBasedAccessControl() {
        echo "<h2>6. ทดสอบ Role-Based Access Control</h2>";
        
        // ทดสอบ Admin Permissions
        try {
            $adminUser = $this->auth->getUserById(1); // Assuming user 1 is admin
            if ($adminUser) {
                $hasAdminPermission = $this->auth->hasPermission($adminUser['role_id'], 'user_management');
                if ($hasAdminPermission) {
                    $this->addResult('Admin Permissions', 'PASS', 'Admin has user management permission');
                    echo "✅ Admin Permissions - สำเร็จ<br>";
                } else {
                    $this->addResult('Admin Permissions', 'FAIL', 'Admin missing user management permission');
                    echo "❌ Admin Permissions - ไม่สำเร็จ<br>";
                }
            } else {
                $this->addResult('Admin Permissions', 'FAIL', 'Could not find admin user');
                echo "❌ Admin Permissions - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('Admin Permissions', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Admin Permissions - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ Telesales Permissions
        try {
            $telesalesUser = $this->auth->getUserById(3); // Assuming user 3 is telesales
            if ($telesalesUser) {
                $hasCustomerPermission = $this->auth->hasPermission($telesalesUser['role_id'], 'customer_management');
                $hasAdminPermission = $this->auth->hasPermission($telesalesUser['role_id'], 'user_management');
                
                if ($hasCustomerPermission && !$hasAdminPermission) {
                    $this->addResult('Telesales Permissions', 'PASS', 'Telesales has correct permissions');
                    echo "✅ Telesales Permissions - สำเร็จ<br>";
                } else {
                    $this->addResult('Telesales Permissions', 'FAIL', 'Telesales has incorrect permissions');
                    echo "❌ Telesales Permissions - ไม่สำเร็จ<br>";
                }
            } else {
                $this->addResult('Telesales Permissions', 'FAIL', 'Could not find telesales user');
                echo "❌ Telesales Permissions - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('Telesales Permissions', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Telesales Permissions - Error: " . $e->getMessage() . "<br>";
        }
        
        // ทดสอบ Unauthorized Access
        try {
            // Simulate unauthorized access to admin function
            $unauthorizedUser = ['role_id' => 4]; // Assuming role 4 has limited permissions
            $hasAdminAccess = $this->auth->hasPermission($unauthorizedUser['role_id'], 'user_management');
            
            if (!$hasAdminAccess) {
                $this->addResult('Unauthorized Access', 'PASS', 'Unauthorized access properly blocked');
                echo "✅ Unauthorized Access - สำเร็จ<br>";
            } else {
                $this->addResult('Unauthorized Access', 'FAIL', 'Unauthorized access not blocked');
                echo "❌ Unauthorized Access - ไม่สำเร็จ<br>";
            }
        } catch (Exception $e) {
            $this->addResult('Unauthorized Access', 'FAIL', 'Error: ' . $e->getMessage());
            echo "❌ Unauthorized Access - Error: " . $e->getMessage() . "<br>";
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
        echo "<h2>📊 สรุปผลการทดสอบ Integration</h2>";
        
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, function($r) { return $r['status'] === 'PASS'; }));
        $failed = $total - $passed;
        
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>สถิติการทดสอบ Integration</h3>";
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
        echo "<p><strong>หมายเหตุ:</strong> การทดสอบนี้เป็นการทดสอบการทำงานร่วมกันของระบบ CRM SalesTracker</p>";
        echo "<p><strong>คำแนะนำ:</strong> หากมีการทดสอบที่ไม่ผ่าน กรุณาตรวจสอบการเชื่อมต่อระหว่าง components</p>";
    }
}

// รันการทดสอบ
$testSuite = new IntegrationTestSuite();
$testSuite->runAllTests();
?> 