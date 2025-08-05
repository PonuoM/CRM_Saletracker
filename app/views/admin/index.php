<?php
/**
 * Admin Dashboard
 * หน้าหลักสำหรับ Admin
 */

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
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-cogs me-2"></i>
                        Admin Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="admin.php?action=users" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-users me-1"></i>จัดการผู้ใช้
                            </a>
                            <a href="admin.php?action=products" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-box me-1"></i>จัดการสินค้า
                            </a>
                            <a href="admin.php?action=settings" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-cog me-1"></i>ตั้งค่าระบบ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            ผู้ใช้ทั้งหมด
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_users']); ?>
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
                                            <?php echo number_format($stats['total_customers']); ?>
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
                                            <?php echo number_format($stats['total_orders']); ?>
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
                                            สินค้าทั้งหมด
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_products']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-heartbeat me-2"></i>สถานะระบบ
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td><strong>การเชื่อมต่อฐานข้อมูล</strong></td>
                                                <td>
                                                    <?php if ($stats['system_health']['database_connection']): ?>
                                                        <span class="badge bg-success">เชื่อมต่อสำเร็จ</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">เชื่อมต่อล้มเหลว</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>PHP Version</strong></td>
                                                <td><?php echo $stats['system_health']['php_version']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Memory Limit</strong></td>
                                                <td><?php echo $stats['system_health']['memory_limit']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Max Execution Time</strong></td>
                                                <td><?php echo $stats['system_health']['max_execution_time']; ?> วินาที</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Upload Max Filesize</strong></td>
                                                <td><?php echo $stats['system_health']['upload_max_filesize']; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-clock me-2"></i>กิจกรรมล่าสุด
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ผู้ใช้</th>
                                                <th>กิจกรรม</th>
                                                <th>เวลา</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['recent_activities'] as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['user_name'] ?? 'ไม่ระบุ'); ?></td>
                                                <td>
                                                    <?php 
                                                    $customerName = htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']);
                                                    echo htmlspecialchars($activity['activity_description']) . ' - ' . $customerName;
                                                    ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt me-2"></i>การดำเนินการด่วน
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin.php?action=users&action=create" class="btn btn-primary btn-block w-100">
                                            <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้ใหม่
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin.php?action=products&action=create" class="btn btn-success btn-block w-100">
                                            <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin.php?action=products&action=import" class="btn btn-info btn-block w-100">
                                            <i class="fas fa-upload me-2"></i>นำเข้าสินค้า
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin.php?action=settings" class="btn btn-warning btn-block w-100">
                                            <i class="fas fa-cog me-2"></i>ตั้งค่าระบบ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
</body>
</html> 