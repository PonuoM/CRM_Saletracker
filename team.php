<?php
/**
 * Team Management for Supervisor
 * หน้าจัดการทีมสำหรับ Supervisor ดูข้อมูลลูกทีมและประสิทธิภาพ
 */

session_start();

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';
require_once 'app/services/CustomerService.php';

$db = new Database();
$auth = new Auth($db);
$customerService = new CustomerService();

// ตรวจสอบการยืนยันตัวตน
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$roleName = $user['role_name'];
$roleId = $user['role_id'];
$supervisorId = $user['user_id'];
$companyId = $user['company_id'];

// ตรวจสอบสิทธิ์ - เฉพาะ supervisor (role = 3) เท่านั้น
if ($roleName !== 'supervisor' || $roleId != 3) {
    http_response_code(403);
    include APP_VIEWS . 'errors/error.php';
    exit;
}

$pageTitle = 'จัดการทีม - CRM SalesTracker';
$currentPage = 'team';

// Get selected month for filtering
$selectedMonth = $_GET['month'] ?? date('Y-m');

// ดึงข้อมูลทีม
try {
    // ข้อมูลสมาชิกทีม
    $teamMembers = $db->fetchAll("
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.created_at,
            u.last_login,
            u.is_active,
            r.role_name,
            COUNT(DISTINCT c.customer_id) as total_customers,
            COUNT(DISTINCT CASE WHEN c.basket_type = 'assigned' THEN c.customer_id END) as assigned_customers,
            COUNT(DISTINCT CASE WHEN o.order_id IS NOT NULL AND DATE_FORMAT(o.created_at, '%Y-%m') = ? THEN o.order_id END) as monthly_orders,
            COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created_at, '%Y-%m') = ? THEN o.total_amount ELSE 0 END), 0) as monthly_sales
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        LEFT JOIN customers c ON u.user_id = c.assigned_to AND c.company_id = ? AND c.is_active = 1
        LEFT JOIN orders o ON u.user_id = o.created_by AND o.company_id = ? AND o.is_active = 1
        WHERE u.supervisor_id = ? AND u.company_id = ? AND u.is_active = 1 AND u.role_id = 4
        GROUP BY u.user_id, u.full_name, u.email, u.phone, u.created_at, u.last_login, u.is_active, r.role_name
        ORDER BY u.full_name ASC
    ", [$selectedMonth, $selectedMonth, $companyId, $companyId, $supervisorId, $companyId]);

    // สถิติรวมของทีม
    $teamStats = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT u.user_id) as total_members,
            COUNT(DISTINCT c.customer_id) as total_customers,
            COUNT(DISTINCT CASE WHEN c.basket_type = 'assigned' THEN c.customer_id END) as assigned_customers,
            COUNT(DISTINCT CASE WHEN o.order_id IS NOT NULL AND DATE_FORMAT(o.created_at, '%Y-%m') = ? THEN o.order_id END) as monthly_orders,
            COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created_at, '%Y-%m') = ? THEN o.total_amount ELSE 0 END), 0) as monthly_sales
        FROM users u
        LEFT JOIN customers c ON u.user_id = c.assigned_to AND c.company_id = ? AND c.is_active = 1
        LEFT JOIN orders o ON u.user_id = o.created_by AND o.company_id = ? AND o.is_active = 1
        WHERE u.supervisor_id = ? AND u.company_id = ? AND u.is_active = 1 AND u.role_id = 4
    ", [$selectedMonth, $selectedMonth, $companyId, $companyId, $supervisorId, $companyId]);

    // ประสิทธิภาพรายวันของทีม
    $dailyPerformance = $db->fetchAll("
        SELECT 
            DATE(o.created_at) as date,
            u.full_name,
            COUNT(o.order_id) as daily_orders,
            COALESCE(SUM(o.total_amount), 0) as daily_sales
        FROM orders o
        JOIN users u ON o.created_by = u.user_id
        WHERE u.supervisor_id = ? AND u.company_id = ? AND o.company_id = ? AND u.role_id = 4
        AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        GROUP BY DATE(o.created_at), u.user_id, u.full_name
        ORDER BY date DESC, u.full_name ASC
    ", [$supervisorId, $companyId, $companyId, $selectedMonth]);

    // กิจกรรมล่าสุดของทีม
    $recentActivities = $db->fetchAll("
        SELECT 
            u.full_name,
            c.first_name,
            c.last_name,
            c.last_contact_at,
            c.customer_grade,
            c.temperature_status,
            'ติดต่อลูกค้า' as activity_type
        FROM customers c
        JOIN users u ON c.assigned_to = u.user_id
        WHERE u.supervisor_id = ? AND u.company_id = ? AND c.company_id = ? AND u.role_id = 4
        AND c.last_contact_at IS NOT NULL
        ORDER BY c.last_contact_at DESC
        LIMIT 20
    ", [$supervisorId, $companyId, $companyId]);

} catch (Exception $e) {
    $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    error_log("Team page error: " . $e->getMessage());
}

// Start content capture
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-users me-2"></i>
                    จัดการทีม
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form method="GET" class="d-flex align-items-center gap-2 me-3">
                        <label for="month" class="form-label mb-0">เดือน:</label>
                        <input type="month" class="form-control form-control-sm" id="month" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i>ดู
                        </button>
                    </form>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ Dashboard
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Team Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-primary"><?php echo number_format($teamStats['total_members'] ?? 0); ?></h4>
                                    <p class="mb-0 text-muted">สมาชิกในทีม</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-friends fa-2x text-primary opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-info"><?php echo number_format($teamStats['total_customers'] ?? 0); ?></h4>
                                    <p class="mb-0 text-muted">ลูกค้าทั้งหมด</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x text-info opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-success"><?php echo number_format($teamStats['monthly_orders'] ?? 0); ?></h4>
                                    <p class="mb-0 text-muted">คำสั่งซื้อเดือนนี้</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x text-success opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-warning">฿<?php echo number_format($teamStats['monthly_sales'] ?? 0, 0); ?></h4>
                                    <p class="mb-0 text-muted">ยอดขายเดือนนี้</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x text-warning opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Members Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>รายชื่อสมาชิกในทีม
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teamMembers)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>ไม่มีสมาชิกในทีม</h5>
                                    <p class="text-muted">ยังไม่มีพนักงานที่ถูกมอบหมายให้อยู่ภายใต้การดูแลของคุณ</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ชื่อพนักงาน</th>
                                                <th>อีเมล</th>
                                                <th>เบอร์โทร</th>
                                                <th class="text-center">ลูกค้าทั้งหมด</th>
                                                <th class="text-center">ลูกค้าที่ได้รับมอบหมาย</th>
                                                <th class="text-center">คำสั่งซื้อเดือนนี้</th>
                                                <th class="text-center">ยอดขายเดือนนี้</th>
                                                <th class="text-center">เข้าสู่ระบบล่าสุด</th>
                                                <th class="text-center">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teamMembers as $member): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($member['phone'] ?? '-'); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo number_format($member['total_customers'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo number_format($member['assigned_customers'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?php echo number_format($member['monthly_orders'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning text-dark">฿<?php echo number_format($member['monthly_sales'] ?? 0, 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($member['last_login']): ?>
                                                        <small><?php echo date('d/m/Y H:i', strtotime($member['last_login'])); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">ไม่เคยเข้าสู่ระบบ</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($member['is_active']): ?>
                                                        <span class="badge bg-success">เปิดใช้งาน</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">ปิดใช้งาน</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>กิจกรรมล่าสุดของทีม
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                    <h5>ไม่มีกิจกรรมล่าสุด</h5>
                                    <p class="text-muted">ยังไม่มีกิจกรรมของทีมในระบบ</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>พนักงาน</th>
                                                <th>ลูกค้า</th>
                                                <th>เกรด</th>
                                                <th>Temperature</th>
                                                <th>วันที่ติดต่อล่าสุด</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($activity['customer_grade'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $tempClass = '';
                                                    switch ($activity['temperature_status']) {
                                                        case 'hot': $tempClass = 'bg-danger'; break;
                                                        case 'warm': $tempClass = 'bg-warning'; break;
                                                        case 'cold': $tempClass = 'bg-info'; break;
                                                        default: $tempClass = 'bg-secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $tempClass; ?>"><?php echo htmlspecialchars($activity['temperature_status'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m/Y H:i', strtotime($activity['last_contact_at'])); ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
