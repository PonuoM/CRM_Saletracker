<?php
/**
 * สร้างลูกค้าสำหรับ Supervisor
 */

session_start();

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

$db = new Database();
$auth = new Auth($db);

// Check if user is supervisor
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role_name'] !== 'supervisor') {
    header('Location: dashboard.php');
    exit;
}

$user = $auth->getCurrentUser();
$supervisorId = $user['user_id'];

try {
    // สร้างลูกค้าสำหรับ supervisor
    $customers = [
        [
            'customer_code' => 'SUP001',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'phone' => '0812345001',
            'email' => 'somchai@example.com',
            'province' => 'กรุงเทพมหานคร',
            'customer_status' => 'new',
            'basket_type' => 'assigned'
        ],
        [
            'customer_code' => 'SUP002',
            'first_name' => 'สมหญิง',
            'last_name' => 'รักดี',
            'phone' => '0812345002',
            'email' => 'somying@example.com',
            'province' => 'นนทบุรี',
            'customer_status' => 'existing',
            'basket_type' => 'assigned'
        ],
        [
            'customer_code' => 'SUP003',
            'first_name' => 'วิชัย',
            'last_name' => 'มั่นคง',
            'phone' => '0812345003',
            'email' => 'wichai@example.com',
            'province' => 'ปทุมธานี',
            'customer_status' => 'new',
            'basket_type' => 'assigned',
            'next_followup_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
        ],
        [
            'customer_code' => 'SUP004',
            'first_name' => 'มาลี',
            'last_name' => 'สวยงาม',
            'phone' => '0812345004',
            'email' => 'malee@example.com',
            'province' => 'สมุทรปราการ',
            'customer_status' => 'existing',
            'basket_type' => 'assigned',
            'customer_time_expiry' => date('Y-m-d H:i:s', strtotime('+3 days'))
        ],
        [
            'customer_code' => 'SUP005',
            'first_name' => 'ประยุทธ',
            'last_name' => 'เก่งกาจ',
            'phone' => '0812345005',
            'email' => 'prayuth@example.com',
            'province' => 'ชลบุรี',
            'customer_status' => 'new',
            'basket_type' => 'assigned'
        ]
    ];
    
    $createdCount = 0;
    
    foreach ($customers as $customerData) {
        // ตรวจสอบว่ามีลูกค้านี้แล้วหรือไม่
        $existing = $db->fetchOne("SELECT customer_id FROM customers WHERE customer_code = ?", [$customerData['customer_code']]);
        
        if (!$existing) {
            $sql = "INSERT INTO customers (
                customer_code, first_name, last_name, phone, email, province,
                customer_status, basket_type, assigned_to, is_active,
                next_followup_at, customer_time_expiry, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())";
            
            $db->execute($sql, [
                $customerData['customer_code'],
                $customerData['first_name'],
                $customerData['last_name'],
                $customerData['phone'],
                $customerData['email'],
                $customerData['province'],
                $customerData['customer_status'],
                $customerData['basket_type'],
                $supervisorId,
                $customerData['next_followup_at'] ?? null,
                $customerData['customer_time_expiry'] ?? null
            ]);
            
            $customerId = $db->getConnection()->lastInsertId();
            
            // สร้างคำสั่งซื้อตัวอย่างสำหรับลูกค้าบางคน
            if (in_array($customerData['customer_code'], ['SUP002', 'SUP004'])) {
                $orderNumber = 'ORD' . date('Ymd') . str_pad($customerId, 4, '0', STR_PAD_LEFT);
                $amount = rand(1000, 5000);
                
                $orderSql = "INSERT INTO orders (
                    order_number, customer_id, order_date, total_amount, net_amount,
                    order_status, payment_status, created_by, is_active, created_at
                ) VALUES (?, ?, CURDATE(), ?, ?, 'completed', 'paid', ?, 1, NOW())";
                
                $db->execute($orderSql, [
                    $orderNumber,
                    $customerId,
                    $amount,
                    $amount,
                    $supervisorId
                ]);
            }
            
            $createdCount++;
        }
    }
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>สร้างลูกค้าสำเร็จ</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body>";
    echo "<div class='container mt-5'>";
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ สร้างลูกค้าสำเร็จ!</h4>";
    echo "<p>สร้างลูกค้าใหม่ {$createdCount} คน สำหรับ Supervisor (ID: {$supervisorId})</p>";
    echo "</div>";
    
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>ลูกค้าที่สร้าง</h5></div>";
    echo "<div class='card-body'>";
    echo "<ul>";
    foreach ($customers as $customer) {
        echo "<li><strong>{$customer['first_name']} {$customer['last_name']}</strong> ({$customer['customer_code']}) - {$customer['customer_status']}</li>";
    }
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='mt-3'>";
    echo "<a href='dashboard.php' class='btn btn-primary'>ทดสอบ Dashboard</a> ";
    echo "<a href='customers.php' class='btn btn-success'>ทดสอบ Customers</a> ";
    echo "<a href='debug_supervisor_data.php' class='btn btn-info'>Debug Data</a>";
    echo "</div>";
    
    echo "</div>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>เกิดข้อผิดพลาด</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body>";
    echo "<div class='container mt-5'>";
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ เกิดข้อผิดพลาด</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    echo "<a href='dashboard.php' class='btn btn-secondary'>กลับหน้าหลัก</a>";
    echo "</div>";
    echo "</body></html>";
}
?>
