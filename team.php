<?php
/**
 * Team Management for Supervisors
 * จัดการทีมสำหรับ Supervisor
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

$db = new Database();

// Initialize Auth
$auth = new Auth($db);

// Check if user is logged in and is supervisor
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
if ($user['role_name'] !== 'supervisor') {
    header('Location: dashboard.php');
    exit;
}

// Get team members (telesales under this supervisor)
$sql = "SELECT u.*, r.role_name, c.company_name,
        (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
        (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
        (SELECT SUM(total_amount) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN companies c ON u.company_id = c.company_id 
        WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1
        ORDER BY u.created_at DESC";

$teamMembers = $db->fetchAll($sql, ['supervisor_id' => $user['user_id']]);

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

$teamSummary = $db->fetchOne($sql, ['supervisor_id' => $user['user_id']]);

// Get recent team activities
$sql = "SELECT 
        'order' as activity_type,
        o.order_number,
        o.total_amount,
        o.created_at,
        u.full_name as user_name,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name
        FROM orders o
        JOIN users u ON o.created_by = u.user_id
        JOIN customers c ON o.customer_id = c.customer_id
        WHERE u.supervisor_id = :supervisor_id AND o.is_active = 1
        
        UNION ALL
        
        SELECT 
        'customer' as activity_type,
        c.customer_code as order_number,
        0 as total_amount,
        c.assigned_at as created_at,
        u.full_name as user_name,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name
        FROM customers c
        JOIN users u ON c.assigned_to = u.user_id
        WHERE u.supervisor_id = :supervisor_id AND c.is_active = 1 AND c.assigned_at IS NOT NULL
        
        ORDER BY created_at DESC
        LIMIT 10";

$recentActivities = $db->fetchAll($sql, ['supervisor_id' => $user['user_id']]);
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
    <?php include 'app/views/components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'app/views/components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 page-transition">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users-cog me-2"></i>
                        จัดการทีม
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
                        </a>
                    </div>
                </div>

                <!-- Team Summary Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            จำนวนสมาชิกทีม
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $teamSummary['total_team_members'] ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            ลูกค้าทั้งหมด
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $teamSummary['total_customers'] ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            คำสั่งซื้อทั้งหมด
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $teamSummary['total_orders'] ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            ยอดขายรวม
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            ฿<?php echo number_format($teamSummary['total_sales_amount'] ?? 0, 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Members Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>สมาชิกทีม
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="teamTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>ชื่อผู้ใช้</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>อีเมล</th>
                                        <th>เบอร์โทร</th>
                                        <th>จำนวนลูกค้า</th>
                                        <th>จำนวนคำสั่งซื้อ</th>
                                        <th>ยอดขายรวม</th>
                                        <th>สถานะ</th>
                                        <th>วันที่เข้าร่วม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teamMembers as $member): ?>
                                    <tr>
                                        <td><?php echo $member['user_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $member['customer_count'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo $member['order_count'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                ฿<?php echo number_format($member['total_sales'] ?? 0, 2); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($member['is_active']): ?>
                                                <span class="badge bg-success">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($member['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Team Activities -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history me-2"></i>กิจกรรมล่าสุดของทีม
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ประเภท</th>
                                        <th>รายละเอียด</th>
                                        <th>สมาชิกทีม</th>
                                        <th>ลูกค้า</th>
                                        <th>จำนวนเงิน</th>
                                        <th>วันที่</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td>
                                            <?php if ($activity['activity_type'] === 'order'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-shopping-cart me-1"></i>คำสั่งซื้อ
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-user-plus me-1"></i>ลูกค้าใหม่
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($activity['activity_type'] === 'order'): ?>
                                                คำสั่งซื้อ #<?php echo htmlspecialchars($activity['order_number']); ?>
                                            <?php else: ?>
                                                รับมอบหมายลูกค้า #<?php echo htmlspecialchars($activity['order_number']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['customer_name']); ?></td>
                                        <td>
                                            <?php if ($activity['activity_type'] === 'order' && $activity['total_amount'] > 0): ?>
                                                <strong class="text-success">
                                                    ฿<?php echo number_format($activity['total_amount'], 2); ?>
                                                </strong>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
        $(document).ready(function() {
            // Add fade-in animation to main content
            $('.page-transition').addClass('fadeIn');
            
            // Initialize DataTable
            $('#teamTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
                },
                pageLength: 25,
                order: [[0, 'desc']]
            });
            
            // Smooth page transitions for all links
            $('a[href*="dashboard.php"], a[href*="customers.php"], a[href*="orders.php"], a[href*="team.php"]').on('click', function(e) {
                const href = $(this).attr('href');
                if (href && !href.includes('#')) {
                    e.preventDefault();
                    
                    // Add fade-out animation
                    $('.page-transition').css({
                        'opacity': '0',
                        'transform': 'translateY(-10px)',
                        'transition': 'all 0.2s ease-out'
                    });
                    
                    // Navigate after animation
                    setTimeout(function() {
                        window.location.href = href;
                    }, 200);
                }
            });
        });
    </script>
</body>
</html>
