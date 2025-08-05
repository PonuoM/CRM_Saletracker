<?php
/**
 * CRM SalesTracker - Dashboard
 * หน้าหลักสำหรับผู้ใช้ที่เข้าสู่ระบบแล้ว
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
    <link href="assets/css/app.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Include Header Component -->
    <?php include APP_VIEWS . 'components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar Component -->
            <?php include APP_VIEWS . 'components/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">แดชบอร์ด</h1>
                </div>

                <!-- KPI Cards -->
                <div class="row mb-4">
                    <?php if ($roleName === 'telesales'): ?>
                        <!-- Telesales Dashboard -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ลูกค้าที่มอบหมาย</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['assigned_customers'] ?? 0); ?></div>
                                <div class="kpi-change positive">
                                    <i class="fas fa-users me-1"></i>ลูกค้าที่รับผิดชอบ
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ลูกค้าต้องติดตาม</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['follow_up_customers'] ?? 0); ?></div>
                                <div class="kpi-change warning">
                                    <i class="fas fa-clock me-1"></i>รอการติดตาม
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>คำสั่งซื้อวันนี้</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['today_orders'] ?? 0); ?></div>
                                <div class="kpi-change positive">
                                    <i class="fas fa-shopping-cart me-1"></i>คำสั่งซื้อใหม่
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ประสิทธิภาพ</h3>
                                <div class="kpi-value"><?php echo number_format(count($dashboardData['monthly_performance'] ?? [])); ?></div>
                                <div class="kpi-change info">
                                    <i class="fas fa-chart-line me-1"></i>เดือนที่ทำงาน
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Admin/Supervisor Dashboard -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ลูกค้าทั้งหมด</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['total_customers'] ?? 0); ?></div>
                                <div class="kpi-change positive">
                                    <i class="fas fa-arrow-up me-1"></i>+12% จากเดือนที่แล้ว
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>คำสั่งซื้อทั้งหมด</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['total_orders'] ?? 0); ?></div>
                                <div class="kpi-change positive">
                                    <i class="fas fa-arrow-up me-1"></i>+8% จากเดือนที่แล้ว
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ยอดขายรวม</h3>
                                <div class="kpi-value">฿<?php echo number_format($dashboardData['total_sales'] ?? 0, 2); ?></div>
                                <div class="kpi-change positive">
                                    <i class="fas fa-arrow-up me-1"></i>+15% จากเดือนที่แล้ว
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ลูกค้าร้อน</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['hot_customers'] ?? 0); ?></div>
                                <div class="kpi-change negative">
                                    <i class="fas fa-arrow-down me-1"></i>-3% จากเดือนที่แล้ว
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <?php if ($roleName === 'telesales'): ?>
                        <div class="col-lg-12 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-3">ประสิทธิภาพรายเดือน</h5>
                                <canvas id="performanceChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-lg-8 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-3">ยอดขายรายเดือน</h5>
                                <canvas id="salesChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-3">สถานะคำสั่งซื้อ</h5>
                                <canvas id="orderStatusChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activities & Customer Grades -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">กิจกรรมล่าสุด</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboardData['recent_activities'])): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach (array_slice($dashboardData['recent_activities'], 0, 5) as $activity): ?>
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($activity['customer_name']); ?> - 
                                                            <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge badge-primary"><?php echo htmlspecialchars($activity['activity_type']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">ไม่มีกิจกรรมล่าสุด</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">เกรดลูกค้า</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboardData['customer_grades'])): ?>
                                    <div class="row">
                                        <?php foreach ($dashboardData['customer_grades'] as $grade => $count): ?>
                                            <div class="col-6 mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="grade-<?php echo strtolower($grade); ?> fw-bold">
                                                        เกรด <?php echo $grade; ?>
                                                    </span>
                                                    <span class="badge badge-primary"><?php echo $count; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">ไม่มีข้อมูลเกรดลูกค้า</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($roleName === 'telesales'): ?>
        // Performance Chart for Telesales
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($dashboardData['monthly_performance'] ?? [], 'month')); ?>,
                datasets: [{
                    label: 'จำนวนคำสั่งซื้อ',
                    data: <?php echo json_encode(array_column($dashboardData['monthly_performance'] ?? [], 'orders')); ?>,
                    backgroundColor: '#1a5f3c',
                    borderColor: '#1a5f3c',
                    borderWidth: 1
                }, {
                    label: 'ยอดขาย (พันบาท)',
                    data: <?php echo json_encode(array_map(function($item) { return $item['sales'] / 1000; }, $dashboardData['monthly_performance'] ?? [])); ?>,
                    backgroundColor: '#38a169',
                    borderColor: '#38a169',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'จำนวนคำสั่งซื้อ'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'ยอดขาย (พันบาท)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
        <?php else: ?>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($dashboardData['monthly_sales'] ?? [])); ?>,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: <?php echo json_encode(array_values($dashboardData['monthly_sales'] ?? [])); ?>,
                    borderColor: '#1a5f3c',
                    backgroundColor: 'rgba(26, 95, 60, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($dashboardData['order_status'] ?? [])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($dashboardData['order_status'] ?? [])); ?>,
                    backgroundColor: [
                        '#38a169',
                        '#d69e2e',
                        '#e53e3e',
                        '#718096'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html> 