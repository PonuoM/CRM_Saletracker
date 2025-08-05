<?php
/**
 * CRM SalesTracker - Admin Dashboard
 * หน้าหลักสำหรับ Admin
 */

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CRM SalesTracker</title>
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
        
        .stat-card {
            background: white;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .stat-card.success {
            border-left: 4px solid var(--success-color);
        }
        
        .stat-card.warning {
            border-left: 4px solid var(--warning-color);
        }
        
        .stat-card.danger {
            border-left: 4px solid var(--danger-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #666;
            font-weight: 500;
        }
        
        .welcome-section {
            background: white;
            color: var(--text-color);
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }
        
        .welcome-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .welcome-section p {
            color: #666;
            margin: 0;
        }
        
        .text-muted {
            color: #666 !important;
        }
        
        .dropdown-menu {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .dropdown-item {
            font-size: 14px;
            padding: 8px 16px;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-gray);
        }
        
        .admin-badge {
            background: var(--danger-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                CRM SalesTracker
                <span class="admin-badge ms-2">Admin</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i>
                        <?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>โปรไฟล์</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>ตั้งค่าระบบ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
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
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users-cog me-2"></i>
                                จัดการผู้ใช้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php">
                                <i class="fas fa-users me-2"></i>
                                จัดการลูกค้า
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box me-2"></i>
                                จัดการสินค้า
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
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i>
                                ตั้งค่าระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>ยินดีต้อนรับ, <?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?>!</h2>
                            <p class="mb-0">ระบบจัดการลูกค้าสัมพันธ์ - บริษัท พรีม่าแพสชั่น 49 จำกัด</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-shield-alt fa-2x text-muted"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-users fa-2x text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="stat-number">5</div>
                                        <div class="stat-label">ลูกค้าทั้งหมด</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-tie fa-2x text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="stat-number">4</div>
                                        <div class="stat-label">ผู้ใช้งาน</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-box fa-2x text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="stat-number">4</div>
                                        <div class="stat-label">สินค้า</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-cog fa-2x text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="stat-number">7</div>
                                        <div class="stat-label">การตั้งค่า</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tasks me-2"></i>
                                    การดำเนินการล่าสุด
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                    <p>ยังไม่มีข้อมูลการดำเนินการ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cog me-2"></i>
                                    การตั้งค่าด่วน
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="users.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-users-cog me-2"></i>จัดการผู้ใช้
                                    </a>
                                    <a href="products.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-box me-2"></i>จัดการสินค้า
                                    </a>
                                    <a href="settings.php" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-cog me-2"></i>ตั้งค่าระบบ
                                    </a>
                                    <a href="reports.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-chart-bar me-2"></i>ดูรายงาน
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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