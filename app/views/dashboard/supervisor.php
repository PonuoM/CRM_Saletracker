<?php
/**
 * CRM SalesTracker - Supervisor Dashboard
 * หน้าหลักสำหรับ Supervisor
 */

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;

// Get team data for supervisor
require_once 'app/core/Database.php';
$db = new Database();

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

$teamMembers = $db->fetchAll($sql, ['supervisor_id' => $_SESSION['user_id']]);

// Get team performance summary
$sql = "SELECT 
        COUNT(DISTINCT u.user_id) as total_team_members,
        SUM(customer_count) as total_customers,
        SUM(order_count) as total_orders,
        SUM(total_sales) as total_sales_amount
        FROM (
            SELECT u.user_id,
            (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
            (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
            FROM users u 
            WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1
        ) as team_stats";

$teamSummary = $db->fetchOne($sql, ['supervisor_id' => $_SESSION['user_id']]);

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

$recentActivities = $db->fetchAll($sql, ['supervisor_id' => $_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #e9ecef;
            --hot-color: #dc2626;
            --warm-color: #f59e0b;
            --cold-color: #3b82f6;
            --frozen-color: #6b7280;
        }
        
        body {
            background-color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            color: var(--text-color);
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .navbar-brand {
            color: var(--text-color) !important;
            font-weight: 600;
        }
        
        .navbar-nav .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
        }
        
        .sidebar {
            background: white;
            border-right: 1px solid var(--border-color);
            min-height: calc(100vh - 56px);
        }
        
        .sidebar .nav-link {
            color: var(--text-color);
            padding: 12px 16px;
            border-radius: 6px;
            margin: 2px 8px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .main-content {
            padding: 24px;
            background: white;
        }
        
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .stats-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stats-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stats-card .stats-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-color);
        }
        
        .stats-card .stats-info p {
            margin: 0;
            color: var(--secondary-color);
            font-size: 0.875rem;
        }
        
        .team-overview {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .team-member {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-bottom: 8px;
            background: var(--light-gray);
        }
        
        .team-member .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .team-member .info {
            flex: 1;
        }
        
        .team-member .info h4 {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .team-member .info p {
            margin: 0;
            font-size: 0.75rem;
            color: var(--secondary-color);
        }
        
        .team-member .performance {
            text-align: right;
        }
        
        .team-member .performance .badge {
            font-size: 0.75rem;
        }
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .breadcrumb {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .kpi-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .kpi-card h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .kpi-change {
            font-size: 0.75rem;
            color: var(--secondary-color);
        }
        
        .card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            background: white;
        }
        
        .card:hover {
            transform: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            color: var(--text-color);
        }
        
        .team-performance {
            margin-top: 24px;
        }
        
        .performance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .performance-table th,
        .performance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .performance-table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--text-color);
        }
        
        .performance-table tr:hover {
            background-color: var(--light-gray);
        }
        
        .chart-container {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            margin-top: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                CRM SalesTracker
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i>
                        <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>โปรไฟล์</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>ตั้งค่า</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h6 class="text-muted mb-3">เมนูหลัก</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php">
                                <i class="fas fa-users me-2"></i>
                                จัดการลูกค้า
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                จัดการคำสั่งซื้อ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="team.php">
                                <i class="fas fa-users-cog me-2"></i>
                                จัดการทีม
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="page-header">
                    <h1>Supervisor Dashboard</h1>
                    <div class="breadcrumb">หน้าแรก > แดชบอร์ด</div>
                </div>
                
                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>สมาชิกทีม</h3>
                            <div class="kpi-value"><?php echo $teamSummary['total_team_members'] ?? 0; ?></div>
                            <div class="kpi-change">พนักงานขายทั้งหมด</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>ลูกค้าทั้งหมด</h3>
                            <div class="kpi-value"><?php echo $teamSummary['total_customers'] ?? 0; ?></div>
                            <div class="kpi-change">ลูกค้าที่ทีมดูแล</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>คำสั่งซื้อทั้งหมด</h3>
                            <div class="kpi-value"><?php echo $teamSummary['total_orders'] ?? 0; ?></div>
                            <div class="kpi-change">คำสั่งซื้อของทีม</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>ยอดขายรวม</h3>
                            <div class="kpi-value">฿<?php echo number_format($teamSummary['total_sales_amount'] ?? 0, 0); ?></div>
                            <div class="kpi-change">ยอดขายของทีม</div>
                        </div>
                    </div>
                </div>
                
                <!-- Team Overview -->
                <div class="team-overview">
                    <h5 class="mb-3">
                        <i class="fas fa-users me-2"></i>
                        ภาพรวมทีม
                    </h5>
                    <?php if (!empty($teamMembers)): ?>
                        <?php foreach ($teamMembers as $member): ?>
                        <div class="team-member">
                            <div class="avatar">
                                <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                            </div>
                            <div class="info">
                                <h4><?php echo htmlspecialchars($member['full_name']); ?></h4>
                                <p><?php echo htmlspecialchars($member['username']); ?> • <?php echo htmlspecialchars($member['email'] ?? '-'); ?></p>
                            </div>
                            <div class="performance">
                                <div class="badge bg-info me-2">ลูกค้า: <?php echo $member['customer_count'] ?? 0; ?></div>
                                <div class="badge bg-success me-2">คำสั่งซื้อ: <?php echo $member['order_count'] ?? 0; ?></div>
                                <div class="badge bg-warning">ยอดขาย: ฿<?php echo number_format($member['total_sales'] ?? 0, 0); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>ยังไม่มีสมาชิกในทีม</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            กิจกรรมล่าสุดของทีม
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentActivities)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
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
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history fa-3x mb-3"></i>
                                <p>ยังไม่มีกิจกรรมล่าสุด</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
        $(document).ready(function() {
            // Add fade-in animation to main content
            $('.main-content').addClass('fadeIn');
            
            // Smooth page transitions for all links
            $('a[href*="dashboard.php"], a[href*="customers.php"], a[href*="orders.php"], a[href*="team.php"]').on('click', function(e) {
                const href = $(this).attr('href');
                if (href && !href.includes('#')) {
                    e.preventDefault();
                    
                    // Add fade-out animation
                    $('.main-content').css({
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
            
            // Hover effects for team member cards
            $('.team-member').hover(
                function() {
                    $(this).css('transform', 'translateY(-2px)');
                    $(this).css('box-shadow', '0 4px 12px rgba(0,0,0,0.1)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                    $(this).css('box-shadow', 'none');
                }
            );
        });
    </script>
</body>
</html> 