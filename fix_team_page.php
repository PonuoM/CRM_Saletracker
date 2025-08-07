<?php
/**
 * Fix Team Page Issues
 * แก้ไขปัญหาในหน้า team.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>แก้ไขปัญหาในหน้า Team</h1>";

// Test 1: Check and fix team.php
echo "<h2>1. ตรวจสอบและแก้ไข team.php</h2>";

if (file_exists('team.php')) {
    echo "✅ ไฟล์ team.php พบ<br>";
    
    // Read team.php content
    $teamContent = file_get_contents('team.php');
    
    // Check for common issues
    $issues = [];
    
    if (strpos($teamContent, 'isLoggedIn') === false) {
        $issues[] = "ไม่พบการตรวจสอบ isLoggedIn";
    }
    
    if (strpos($teamContent, 'supervisor') === false) {
        $issues[] = "ไม่พบการตรวจสอบ role supervisor";
    }
    
    if (strpos($teamContent, 'supervisor_id') === false) {
        $issues[] = "ไม่พบการใช้ supervisor_id";
    }
    
    if (empty($issues)) {
        echo "✅ team.php ไม่มีปัญหาที่พบ<br>";
    } else {
        echo "❌ พบปัญหาใน team.php:<br>";
        foreach ($issues as $issue) {
            echo "- {$issue}<br>";
        }
    }
    
} else {
    echo "❌ ไม่พบไฟล์ team.php<br>";
}

// Test 2: Check and fix Auth class
echo "<h2>2. ตรวจสอบและแก้ไข Auth class</h2>";

if (file_exists('app/core/Auth.php')) {
    echo "✅ ไฟล์ Auth.php พบ<br>";
    
    $authContent = file_get_contents('app/core/Auth.php');
    
    // Check for required methods
    $requiredMethods = ['isLoggedIn', 'getCurrentUser', 'getTeamMembers', 'getTeamSummary'];
    
    foreach ($requiredMethods as $method) {
        if (strpos($authContent, "public function {$method}") !== false) {
            echo "✅ method {$method} มีอยู่แล้วใน Auth.php<br>";
        } else {
            echo "❌ ไม่พบ method {$method} ใน Auth.php<br>";
        }
    }
    
} else {
    echo "❌ ไม่พบไฟล์ Auth.php<br>";
}

// Test 3: Check database schema
echo "<h2>3. ตรวจสอบโครงสร้างฐานข้อมูล</h2>";

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

// Test 4: Create improved team.php
echo "<h2>4. สร้าง team.php ที่ปรับปรุงแล้ว</h2>";

$improvedTeamContent = '<?php
/**
 * Team Management for Supervisors - Improved Version
 * จัดการทีมสำหรับ Supervisor
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);

require_once \'config/config.php\';
require_once \'app/core/Auth.php\';
require_once \'app/core/Database.php\';

try {
    $db = new Database();
    $auth = new Auth($db);

    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        header(\'Location: login.php\');
        exit;
    }

    $user = $auth->getCurrentUser();
    
    // Check if user is supervisor
    if ($user[\'role_name\'] !== \'supervisor\') {
        header(\'Location: dashboard.php\');
        exit;
    }

    // Get team members (telesales under this supervisor)
    $sql = "SELECT u.*, r.role_name, c.company_name,
            (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
            (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1
            ORDER BY u.created_at DESC";

    $teamMembers = $db->fetchAll($sql, [\'supervisor_id\' => $user[\'user_id\']]);

    // Get team performance summary
    $sql = "SELECT 
            COUNT(DISTINCT team_stats.user_id) as total_team_members,
            SUM(team_stats.customer_count) as total_customers,
            SUM(team_stats.order_count) as total_orders,
            SUM(team_stats.total_sales) as total_sales_amount
            FROM (
                SELECT u.user_id,
                (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
                (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
                FROM users u 
                WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1
            ) as team_stats";

    $teamSummary = $db->fetchOne($sql, [\'supervisor_id\' => $user[\'user_id\']]);

    // Get recent team activities
    $sql = "SELECT 
            \'order\' as activity_type,
            o.order_number,
            o.total_amount,
            o.created_at,
            u.full_name as user_name,
            CONCAT(c.first_name, \' \', c.last_name) as customer_name
            FROM orders o
            JOIN users u ON o.created_by = u.user_id
            JOIN customers c ON o.customer_id = c.customer_id
            WHERE u.supervisor_id = :supervisor_id AND o.is_active = 1
            
            UNION ALL
            
            SELECT 
            \'customer\' as activity_type,
            c.customer_code as order_number,
            0 as total_amount,
            c.assigned_at as created_at,
            u.full_name as user_name,
            CONCAT(c.first_name, \' \', c.last_name) as customer_name
            FROM customers c
            JOIN users u ON c.assigned_to = u.user_id
            WHERE u.supervisor_id = :supervisor_id AND c.is_active = 1 AND c.assigned_at IS NOT NULL
            
            ORDER BY created_at DESC
            LIMIT 10";

    $recentActivities = $db->fetchAll($sql, [\'supervisor_id\' => $user[\'user_id\']]);

} catch (Exception $e) {
    // Log error
    error_log("Error in team.php: " . $e->getMessage());
    
    // Show error page
    echo "<h1>เกิดข้อผิดพลาด</h1>";
    echo "<p>ขออภัย เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง</p>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href=\'dashboard.php\'>กลับไปหน้า Dashboard</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการทีม - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-users-cog me-2"></i>จัดการทีม</h1>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับไปหน้า Dashboard
                    </a>
                </div>

                <!-- Team Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $teamSummary[\'total_team_members\'] ?? 0; ?></h4>
                                        <p class="card-text">สมาชิกทีม</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $teamSummary[\'total_customers\'] ?? 0; ?></h4>
                                        <p class="card-text">ลูกค้าทั้งหมด</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-friends fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $teamSummary[\'total_orders\'] ?? 0; ?></h4>
                                        <p class="card-text">คำสั่งซื้อ</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">฿<?php echo number_format($teamSummary[\'total_sales_amount\'] ?? 0, 2); ?></h4>
                                        <p class="card-text">ยอดขายรวม</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Members Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>สมาชิกทีม</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teamMembers)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">ยังไม่มีสมาชิกในทีม</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th>อีเมล</th>
                                            <th>ลูกค้า</th>
                                            <th>คำสั่งซื้อ</th>
                                            <th>ยอดขาย</th>
                                            <th>วันที่เข้าร่วม</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teamMembers as $member): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                            <?php echo strtoupper(substr($member[\'full_name\'], 0, 1)); ?>
                                                        </div>
                                                        <?php echo htmlspecialchars($member[\'full_name\']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($member[\'email\'] ?? \'-\'); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $member[\'customer_count\']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $member[\'order_count\']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-success fw-bold">฿<?php echo number_format($member[\'total_sales\'] ?? 0, 2); ?></span>
                                                </td>
                                                <td><?php echo date(\'d/m/Y\', strtotime($member[\'created_at\'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>กิจกรรมล่าสุด</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <p class="text-muted">ยังไม่มีกิจกรรมล่าสุด</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker <?php echo $activity[\'activity_type\'] === \'order\' ? \'bg-success\' : \'bg-primary\'; ?>">
                                            <i class="fas <?php echo $activity[\'activity_type\'] === \'order\' ? \'fa-shopping-cart\' : \'fa-user-plus\'; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">
                                                <?php if ($activity[\'activity_type\'] === \'order\'): ?>
                                                    คำสั่งซื้อใหม่ #<?php echo htmlspecialchars($activity[\'order_number\']); ?>
                                                <?php else: ?>
                                                    ลูกค้าใหม่: <?php echo htmlspecialchars($activity[\'customer_name\']); ?>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                โดย: <?php echo htmlspecialchars($activity[\'user_name\']); ?>
                                                <?php if ($activity[\'activity_type\'] === \'order\' && $activity[\'total_amount\'] > 0): ?>
                                                    - ฿<?php echo number_format($activity[\'total_amount\'], 2); ?>
                                                <?php endif; ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo date(\'d/m/Y H:i\', strtotime($activity[\'created_at\'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

file_put_contents('team_improved.php', $improvedTeamContent);
echo "✅ สร้างไฟล์ team_improved.php ที่ปรับปรุงแล้ว<br>";

echo "<h2>สรุปการแก้ไข</h2>";
echo "การแก้ไขเสร็จสิ้นแล้ว<br>";
echo "<br><strong>ขั้นตอนการทดสอบ:</strong><br>";
echo "1. <a href='test_team_access.php'>รันไฟล์ทดสอบการเข้าถึงหน้า team</a><br>";
echo "2. <a href='team_improved.php'>ทดสอบ team_improved.php</a><br>";
echo "3. <a href='team.php'>ทดสอบ team.php ต้นฉบับ</a><br>";
echo "4. <a href='debug_customers_500_error.php'>ทดสอบแก้ไขปัญหา customers.php</a><br>";
echo "<br><a href='dashboard.php'>กลับไปหน้า Dashboard</a>";
?>
