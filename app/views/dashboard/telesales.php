<?php
/**
 * CRM SalesTracker - Telesales Dashboard
 * หน้าหลักสำหรับ Telesales
 */

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role'] ?? 'user';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CRM SalesTracker</title>
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
            margin-bottom: 4px;
        }
        
        .breadcrumb {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .telesales-dashboard {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .personal-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
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
        
        .do-section {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .do-section h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 16px;
        }
        
        .urgent-customers {
            display: grid;
            gap: 12px;
        }
        
        .customer-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .customer-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
        }
        
        .customer-info {
            flex: 1;
        }
        
        .customer-name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 2px;
        }
        
        .customer-details {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .customer-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 12px;
        }
        
        .status-hot {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--hot-color);
        }
        
        .status-warm {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warm-color);
        }
        
        .status-cold {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--cold-color);
        }
        
        .status-frozen {
            background-color: rgba(107, 114, 128, 0.1);
            color: var(--frozen-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-left: 12px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: white;
            color: var(--text-color);
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--secondary-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin: 0;
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
                                คำสั่งซื้อ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                รายงาน
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="page-header">
                    <h1>Dashboard ประจำวัน</h1>
                    <div class="breadcrumb">หน้าแรก > แดชบอร์ด</div>
                </div>
                
                <div class="telesales-dashboard">
                    <!-- Personal KPIs -->
                    <div class="personal-kpis">
                        <div class="kpi-card">
                            <h3>ยอดขายเดือนนี้</h3>
                            <div class="kpi-value">฿45,678</div>
                            <div class="kpi-change">+12% จากเดือนที่แล้ว</div>
                        </div>
                        <div class="kpi-card">
                            <h3>ลูกค้าในมือ</h3>
                            <div class="kpi-value">23</div>
                            <div class="kpi-change">+3 จากสัปดาห์ที่แล้ว</div>
                        </div>
                        <div class="kpi-card">
                            <h3>นัดหมายวันนี้</h3>
                            <div class="kpi-value">5</div>
                            <div class="kpi-change">2 รายการที่เหลือ</div>
                        </div>
                        <div class="kpi-card">
                            <h3>การโทรวันนี้</h3>
                            <div class="kpi-value">12/20</div>
                            <div class="kpi-change">60% เสร็จสิ้น</div>
                        </div>
                    </div>
                    
                    <!-- Do Section -->
                    <div class="do-section">
                        <h2>สิ่งที่ต้องทำวันนี้</h2>
                        <div class="urgent-customers">
                            <div class="customer-item">
                                <div class="customer-info">
                                    <div class="customer-name">สมชาย ใจดี</div>
                                    <div class="customer-details">081-111-1111 • กรุงเทพฯ • ต้องติดต่อกลับภายใน 2 ชั่วโมง</div>
                                </div>
                                <span class="customer-status status-hot">🔥 Hot</span>
                                <div class="action-buttons">
                                    <a href="#" class="btn-action">โทร</a>
                                    <a href="#" class="btn-action">ดูรายละเอียด</a>
                                </div>
                            </div>
                            
                            <div class="customer-item">
                                <div class="customer-info">
                                    <div class="customer-name">สมหญิง รักดี</div>
                                    <div class="customer-details">081-222-2222 • กรุงเทพฯ • นัดหมาย 14:00 น.</div>
                                </div>
                                <span class="customer-status status-warm">🌤️ Warm</span>
                                <div class="action-buttons">
                                    <a href="#" class="btn-action">โทร</a>
                                    <a href="#" class="btn-action">ดูรายละเอียด</a>
                                </div>
                            </div>
                            
                            <div class="customer-item">
                                <div class="customer-info">
                                    <div class="customer-name">วิชัย สุขใจ</div>
                                    <div class="customer-details">081-333-3333 • กรุงเทพฯ • ต้องติดตามออเดอร์</div>
                                </div>
                                <span class="customer-status status-cold">❄️ Cold</span>
                                <div class="action-buttons">
                                    <a href="#" class="btn-action">โทร</a>
                                    <a href="#" class="btn-action">ดูรายละเอียด</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh dashboard data
        setInterval(function() {
            // TODO: Load dashboard data via AJAX
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html> 