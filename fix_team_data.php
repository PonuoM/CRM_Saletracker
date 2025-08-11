<?php
/**
 * แก้ไขข้อมูลทีม
 * แก้ปัญหาข้อมูลซ้ำและสถานะไม่ถูกต้อง
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

$pageTitle = 'แก้ไขข้อมูลทีม - CRM SalesTracker';
$currentPage = 'team';

// Handle actions
$message = '';
$messageType = '';

if ($_POST['action'] ?? '' === 'activate_team') {
    try {
        $sql = "UPDATE users SET is_active = 1 WHERE supervisor_id = ? AND role_id = (SELECT role_id FROM roles WHERE role_name = 'telesales')";
        $db->execute($sql, [$supervisorId]);
        
        $message = 'เปิดใช้งานสมาชิกทีมเสร็จสิ้น';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

if ($_POST['action'] ?? '' === 'create_sample_data') {
    try {
        // Get active telesales users
        $telesalesUsers = $db->fetchAll("
            SELECT user_id, username 
            FROM users 
            WHERE supervisor_id = ? AND role_id = (SELECT role_id FROM roles WHERE role_name = 'telesales') AND is_active = 1
        ", [$supervisorId]);
        
        $createdCustomers = 0;
        $createdOrders = 0;
        
        foreach ($telesalesUsers as $telesales) {
            // Create 2-3 customers per telesales
            for ($i = 1; $i <= 3; $i++) {
                $customerCode = 'CUST' . str_pad($telesales['user_id'], 3, '0', STR_PAD_LEFT) . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                // Check if customer already exists
                $existingCustomer = $db->fetchOne("SELECT customer_id FROM customers WHERE customer_code = ?", [$customerCode]);
                
                if (!$existingCustomer) {
                    $sql = "INSERT INTO customers (customer_code, first_name, last_name, phone, email, assigned_to, is_active, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
                    
                    $db->execute($sql, [
                        $customerCode,
                        'ลูกค้าทดสอบ ' . $i,
                        'ของ ' . $telesales['username'],
                        '081234567' . $i,
                        'customer' . $i . '@' . $telesales['username'] . '.com',
                        $telesales['user_id']
                    ]);
                    
                    $customerId = $db->getConnection()->lastInsertId();
                    $createdCustomers++;
                    
                    // Create 1-2 orders per customer
                    for ($j = 1; $j <= 2; $j++) {
                        $orderNumber = 'ORD' . date('Ymd') . str_pad($telesales['user_id'], 3, '0', STR_PAD_LEFT) . str_pad($i, 2, '0', STR_PAD_LEFT) . $j;
                        
                        // Check if order already exists
                        $existingOrder = $db->fetchOne("SELECT order_id FROM orders WHERE order_number = ?", [$orderNumber]);
                        
                        if (!$existingOrder) {
                            $amount = rand(500, 5000);
                            $sql = "INSERT INTO orders (order_number, customer_id, order_date, total_amount, net_amount, order_status, payment_status, created_by, is_active, created_at) 
                                    VALUES (?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, 'completed', 'paid', ?, 1, NOW())";
                            
                            $db->execute($sql, [
                                $orderNumber,
                                $customerId,
                                rand(1, 30), // Random date within last 30 days
                                $amount,
                                $amount,
                                $telesales['user_id']
                            ]);
                            
                            $createdOrders++;
                        }
                    }
                }
            }
        }
        
        $message = "สร้างข้อมูลทดสอบเสร็จสิ้น: ลูกค้า {$createdCustomers} คน, คำสั่งซื้อ {$createdOrders} รายการ";
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Start content capture
ob_start();
?>

<!-- Fix Team Data Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-tools me-2"></i>
                    แก้ไขข้อมูลทีม
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="team.php" class="btn btn-primary me-2">
                        <i class="fas fa-users me-2"></i>กลับไปหน้าจัดการทีม
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Current Team Status -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                สถานะทีมปัจจุบัน
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $teamMembers = $db->fetchAll("
                                    SELECT u.user_id, u.username, u.full_name, u.is_active, r.role_name,
                                           (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
                                           (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
                                           (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
                                    FROM users u 
                                    LEFT JOIN roles r ON u.role_id = r.role_id 
                                    WHERE u.supervisor_id = ?
                                    ORDER BY u.is_active DESC, u.username
                                ", [$supervisorId]);
                                
                                if (!empty($teamMembers)) {
                                    echo "<div class='table-responsive'>";
                                    echo "<table class='table table-striped'>";
                                    echo "<thead><tr><th>Username</th><th>ชื่อ</th><th>Role</th><th>สถานะ</th><th>ลูกค้า</th><th>คำสั่งซื้อ</th><th>ยอดขาย</th></tr></thead>";
                                    echo "<tbody>";
                                    foreach ($teamMembers as $member) {
                                        echo "<tr>";
                                        echo "<td><strong>" . htmlspecialchars($member['username']) . "</strong></td>";
                                        echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
                                        echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($member['role_name']) . "</span></td>";
                                        echo "<td>";
                                        if ($member['is_active']) {
                                            echo "<span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>ใช้งาน</span>";
                                        } else {
                                            echo "<span class='badge bg-danger'><i class='fas fa-times-circle me-1'></i>ปิดใช้งาน</span>";
                                        }
                                        echo "</td>";
                                        echo "<td><span class='badge bg-info'>" . $member['customer_count'] . "</span></td>";
                                        echo "<td><span class='badge bg-success'>" . $member['order_count'] . "</span></td>";
                                        echo "<td><strong>฿" . number_format($member['total_sales'], 2) . "</strong></td>";
                                        echo "</tr>";
                                    }
                                    echo "</tbody></table>";
                                    echo "</div>";
                                } else {
                                    echo "<div class='alert alert-warning'>";
                                    echo "<i class='fas fa-exclamation-triangle me-2'></i>";
                                    echo "ไม่พบสมาชิกทีมภายใต้การดูแลของคุณ (supervisor_id = {$supervisorId})";
                                    echo "</div>";
                                }
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'>";
                                echo "<i class='fas fa-times-circle me-2'></i>";
                                echo "เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fix Actions -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-user-check me-2"></i>
                                เปิดใช้งานสมาชิกทีม
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>เปิดใช้งานสมาชิกทีมทั้งหมดที่มีสถานะปิดใช้งาน</p>
                            
                            <form method="POST" onsubmit="return confirm('คุณต้องการเปิดใช้งานสมาชิกทีมทั้งหมดหรือไม่?')">
                                <input type="hidden" name="action" value="activate_team">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-user-check me-1"></i>เปิดใช้งานสมาชิกทีม
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-database me-2"></i>
                                สร้างข้อมูลทดสอบ
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>สร้างลูกค้าและคำสั่งซื้อตัวอย่างสำหรับสมาชิกทีม</p>
                            
                            <form method="POST" onsubmit="return confirm('คุณต้องการสร้างข้อมูลทดสอบหรือไม่?')">
                                <input type="hidden" name="action" value="create_sample_data">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i>สร้างข้อมูลทดสอบ
                                </button>
                            </form>
                            
                            <small class="text-muted">
                                จะสร้างลูกค้า 3 คน และคำสั่งซื้อ 6 รายการต่อสมาชิกทีม
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                คำแนะนำ
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>ปัญหาที่พบบ่อย:</h6>
                            <ul>
                                <li><strong>ข้อมูลซ้ำ:</strong> เกิดจากการ JOIN ตารางที่ไม่ถูกต้อง</li>
                                <li><strong>สถานะปิดใช้งาน:</strong> สมาชิกทีมมี is_active = 0</li>
                                <li><strong>ไม่มีข้อมูลลูกค้า/คำสั่งซื้อ:</strong> ยังไม่มีการสร้างข้อมูลทดสอบ</li>
                            </ul>
                            
                            <h6>วิธีแก้ไข:</h6>
                            <ol>
                                <li>เปิดใช้งานสมาชิกทีมก่อน</li>
                                <li>สร้างข้อมูลทดสอบ</li>
                                <li>ตรวจสอบผลลัพธ์ในหน้าจัดการทีม</li>
                            </ol>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>หมายเหตุ:</strong> ข้อมูลทดสอบจะไม่ซ้ำกัน ระบบจะตรวจสอบก่อนสร้าง
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
