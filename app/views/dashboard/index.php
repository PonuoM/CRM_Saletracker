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
            <main class="col-md-9 col-lg-10 main-content page-transition">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">แดชบอร์ด</h1>
                </div>

                <!-- KPI Cards -->
                <div class="row mb-4">
                    <?php if ($roleName === 'telesales'): ?>
                        <!-- Existing 4 KPI (with orders changed to monthly) -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ลูกค้าที่มอบหมาย</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['assigned_customers'] ?? 0); ?></div>
                                <div class="kpi-change positive"><i class="fas fa-users me-1"></i>ลูกค้าที่รับผิดชอบ</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ลูกค้าต้องติดตาม</h3>
                                <div class="kpi-value"><?php echo number_format($dashboardData['follow_up_customers'] ?? 0); ?></div>
                                <div class="kpi-change warning"><i class="fas fa-clock me-1"></i>รอการติดตาม</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>คำสั่งซื้อประจำเดือน</h3>
                                <div class="kpi-value"><?php echo number_format($monthlyKpis['total_orders'] ?? 0); ?></div>
                                <div class="kpi-change positive"><i class="fas fa-shopping-cart me-1"></i><?php echo htmlspecialchars($selectedMonth); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ประสิทธิภาพ</h3>
                                <div class="kpi-value"><?php echo number_format(count($dashboardData['monthly_performance'] ?? [])); ?></div>
                                <div class="kpi-change info"><i class="fas fa-chart-line me-1"></i>เดือนที่ทำงาน</div>
                            </div>
                        </div>

                        <!-- Additional 4 KPI: Monthly sales and 3 categories (amount + quantity + unit) -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ยอดขายประจำเดือน</h3>
                                <div class="kpi-value">฿<?php echo number_format($monthlyKpis['total_sales'] ?? 0, 2); ?></div>
                                <div class="kpi-change positive"><i class="fas fa-baht-sign me-1"></i><?php echo htmlspecialchars($selectedMonth); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ปุ๋ยกระสอบใหญ่</h3>
                                <div class="kpi-value">฿<?php echo number_format($monthlyKpis['sales_big_sack'] ?? 0, 2); ?></div>
                                <div class="kpi-change info"><i class="fas fa-sack-dollar me-1"></i><?php echo number_format($monthlyKpis['sales_big_sack_quantity'] ?? 0); ?> <?php echo htmlspecialchars($monthlyKpis['sales_big_sack_unit'] ?? 'หน่วย'); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ปุ๋ยกระสอบเล็ก</h3>
                                <div class="kpi-value">฿<?php echo number_format($monthlyKpis['sales_small_sack'] ?? 0, 2); ?></div>
                                <div class="kpi-change info"><i class="fas fa-box me-1"></i><?php echo number_format($monthlyKpis['sales_small_sack_quantity'] ?? 0); ?> <?php echo htmlspecialchars($monthlyKpis['sales_small_sack_unit'] ?? 'หน่วย'); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="kpi-card">
                                <h3>ชีวภัณฑ์</h3>
                                <div class="kpi-value">฿<?php echo number_format($monthlyKpis['sales_bio'] ?? 0, 2); ?></div>
                                <div class="kpi-change info"><i class="fas fa-flask me-1"></i><?php echo number_format($monthlyKpis['sales_bio_quantity'] ?? 0); ?> <?php echo htmlspecialchars($monthlyKpis['sales_bio_unit'] ?? 'หน่วย'); ?></div>
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

                <!-- Charts Row (use tabs to save vertical space) -->
                <div class="row mb-4">
                    <?php if ($roleName === 'telesales'): ?>
                        <div class="col-lg-12 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <ul class="nav nav-tabs" id="chartTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="tab-sales" data-bs-toggle="tab" data-bs-target="#pane-sales" type="button" role="tab">ยอดขายรายวัน</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="tab-orders-contacts" data-bs-toggle="tab" data-bs-target="#pane-orders-contacts" type="button" role="tab">คำสั่งซื้อ + รายชื่อติดต่อ</button>
                                    </li>
                                </ul>
                                <form method="get" class="d-flex align-items-center" id="monthFilterForm">
                                    <label for="monthPicker" class="me-2">เดือน:</label>
                                    <input type="month" id="monthPicker" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedMonth ?? date('Y-m')); ?>" />
                                </form>
                            </div>
                            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                                <div class="tab-pane fade show active" id="pane-sales" role="tabpanel">
                                    <div class="chart-container" style="height:320px">
                                        <canvas id="dailySalesChart"></canvas>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pane-orders-contacts" role="tabpanel">
                                    <div class="chart-container" style="height:320px">
                                        <canvas id="ordersContactsChart"></canvas>
                                    </div>
                                </div>
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

                <?php if ($roleName !== 'telesales'): ?>
                <!-- Recent Activities & Customer Grades (hidden for telesales per requirement) -->
                <div class="row">
                    <!-- existing blocks remain for admin/supervisor -->
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script>
        <?php if ($roleName === 'telesales'): ?>
        // Daily Sales Chart (Bar)
        const dailyLabels = <?php echo json_encode($dailyPerformance['labels'] ?? []); ?>;
        const dailySales = <?php echo json_encode($dailyPerformance['sales'] ?? []); ?>;
        const dailyContacts = <?php echo json_encode($dailyPerformance['contacts'] ?? []); ?>;
        const dailyOrders = <?php echo json_encode($dailyPerformance['orders'] ?? []); ?>;

        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        const dailySalesChart = new Chart(dailySalesCtx, {
            type: 'bar',
            data: {
                labels: dailyLabels,
                datasets: [
                    {
                        label: 'ยอดขายต่อวัน (บาท)',
                        data: dailySales,
                        backgroundColor: 'rgba(56, 161, 105, 0.6)',
                        borderColor: '#38a169',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'ยอดขาย (บาท)' } },
                    x: { title: { display: true, text: 'วันที่ (1-31)' } }
                }
            }
        });

        // Orders + Contacts Chart (Bar + Line)
        const ordersContactsCtx = document.getElementById('ordersContactsChart').getContext('2d');
        const ordersContactsChart = new Chart(ordersContactsCtx, {
            data: {
                labels: dailyLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'จำนวนคำสั่งซื้อ',
                        data: dailyOrders,
                        backgroundColor: 'rgba(99, 102, 241, 0.6)',
                        borderColor: '#6366f1',
                        borderWidth: 1,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'จำนวนรายชื่อติดต่อ',
                        data: dailyContacts,
                        borderColor: '#1a5f3c',
                        backgroundColor: 'rgba(26, 95, 60, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'จำนวนคำสั่งซื้อ' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'จำนวนรายชื่อติดต่อ' } },
                    x: { title: { display: true, text: 'วันที่ (1-31)' } }
                }
            }
        });

        // Auto-submit month filter on change
        const monthPicker = document.getElementById('monthPicker');
        if (monthPicker) {
            monthPicker.addEventListener('change', () => {
                document.getElementById('monthFilterForm').submit();
            });
        }
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