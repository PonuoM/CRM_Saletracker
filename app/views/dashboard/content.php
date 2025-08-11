<?php
/**
 * CRM SalesTracker - Dashboard Content
 * เนื้อหาหน้าหลักสำหรับผู้ใช้ที่เข้าสู่ระบบแล้ว (เฉพาะ content)
 */

$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role'] ?? 'user';
?>

<!-- Dashboard Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">แดชบอร์ด</h1>
</div>

<?php if ($_SESSION['role_name'] === 'telesales' || $_SESSION['role_name'] === 'supervisor'): ?>
    <!-- Telesales Dashboard -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" action="dashboard.php" class="d-flex align-items-end gap-2">
                <div class="flex-grow-1">
                    <label for="month" class="form-label small">เดือน/ปี</label>
                    <input type="month" class="form-control form-control-sm" id="month" name="month"
                           value="<?php echo htmlspecialchars($selectedMonth); ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>ดูข้อมูล
                </button>
            </form>
        </div>
    </div>

    <!-- KPI Cards for Telesales -->
    <div class="row mb-4">
        <!-- Row 1: Original 4 cards -->
        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ลูกค้าที่ได้รับมอบหมาย</h6>
                            <h4 class="mb-0 text-primary"><?php echo number_format($assignedCustomers); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-lg text-primary opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ลูกค้าที่ต้องติดตาม</h6>
                            <h4 class="mb-0 text-warning"><?php echo number_format($followUpCustomers); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-phone fa-lg text-warning opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">คำสั่งซื้อประจำเดือน</h6>
                            <h4 class="mb-0 text-success"><?php echo number_format($monthlyKpis['total_orders'] ?? 0); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-lg text-success opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ยอดขายเดือนนี้</h6>
                            <h4 class="mb-0 text-info">฿<?php echo number_format($monthlyKpis['total_sales'] ?? 0, 2); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-lg text-info opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Product category cards -->
        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ปุ๋ยกระสอบใหญ่</h6>
                            <h4 class="mb-0 text-primary">฿<?php echo number_format($monthlyKpis['fertilizer_large_sales'] ?? 0, 2); ?></h4>
                            <small class="text-muted"><?php echo number_format($monthlyKpis['fertilizer_large_qty'] ?? 0); ?> กระสอบ</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-seedling fa-lg text-primary opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ปุ๋ยกระสอบเล็ก</h6>
                            <h4 class="mb-0 text-success">฿<?php echo number_format($monthlyKpis['fertilizer_small_sales'] ?? 0, 2); ?></h4>
                            <small class="text-muted"><?php echo number_format($monthlyKpis['fertilizer_small_qty'] ?? 0); ?> กระสอบ</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-leaf fa-lg text-success opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ชีวภัณฑ์</h6>
                            <h4 class="mb-0 text-warning">฿<?php echo number_format($monthlyKpis['bio_products_sales'] ?? 0, 2); ?></h4>
                            <small class="text-muted"><?php echo number_format($monthlyKpis['bio_products_qty'] ?? 0); ?> ชิ้น</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-flask fa-lg text-warning opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted small">ของแถม</h6>
                            <h4 class="mb-0 text-danger">฿<?php echo number_format($monthlyKpis['freebies_sales'] ?? 0, 2); ?></h4>
                            <small class="text-muted"><?php echo number_format($monthlyKpis['freebies_qty'] ?? 0); ?> ชิ้น</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-gift fa-lg text-danger opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts for Telesales -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        ผลงานรายวัน - <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>
                    </h5>
                    <!-- Chart Tabs -->
                    <ul class="nav nav-tabs card-header-tabs mt-2" id="chartTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-chart" type="button" role="tab">
                                <i class="fas fa-chart-column me-1"></i>ยอดขายตามประเภท
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-chart" type="button" role="tab">
                                <i class="fas fa-chart-mixed me-1"></i>คำสั่งซื้อ + ติดต่อ
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="chartTabContent">
                        <!-- Sales by Category Chart -->
                        <div class="tab-pane fade show active" id="sales-chart" role="tabpanel">
                            <canvas id="dailySalesChart" width="400" height="120"></canvas>
                        </div>
                        <!-- Orders + Contacts Chart -->
                        <div class="tab-pane fade" id="orders-chart" role="tabpanel">
                            <canvas id="dailyOrdersChart" width="400" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Admin/Supervisor Dashboard -->
    <div class="row mb-4">
        <!-- KPI Cards -->
        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">ลูกค้าทั้งหมด</h6>
                            <h3 class="mb-0 text-primary"><?php echo number_format($totalCustomers); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x text-primary opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">ลูกค้า Hot</h6>
                            <h3 class="mb-0 text-danger"><?php echo number_format($hotCustomers); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-fire fa-2x text-danger opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">คำสั่งซื้อทั้งหมด</h6>
                            <h3 class="mb-0 text-success"><?php echo number_format($totalOrders); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-2x text-success opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">ยอดขายรวม</h6>
                            <h3 class="mb-0 text-info">฿<?php echo number_format($totalSales, 2); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x text-info opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        ยอดขายรายเดือน
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlySalesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        เกรดลูกค้า
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="customerGradesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        กิจกรรมล่าสุด
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivities)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($recentActivities, 0, 10) as $activity): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?php echo htmlspecialchars($activity['activity_description']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($activity['user_name'] ?? 'ระบบ'); ?> - 
                                            <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">ไม่มีกิจกรรมล่าสุด</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart configurations will be added here
<?php if ($_SESSION['role_name'] === 'telesales'): ?>
    // Daily Sales by Category Chart (Stack Column)
    const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(dailySalesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dailyPerformance['labels'] ?? []); ?>,
            datasets: [{
                label: 'ปุ๋ยกระสอบใหญ่',
                data: <?php echo json_encode($dailyPerformance['fertilizer_large'] ?? []); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }, {
                label: 'ปุ๋ยกระสอบเล็ก',
                data: <?php echo json_encode($dailyPerformance['fertilizer_small'] ?? []); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }, {
                label: 'ชีวภัณฑ์',
                data: <?php echo json_encode($dailyPerformance['bio_products'] ?? []); ?>,
                backgroundColor: 'rgba(255, 206, 86, 0.8)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }, {
                label: 'ของแถม',
                data: <?php echo json_encode($dailyPerformance['freebies'] ?? []); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Daily Orders + Contacts Chart (Mixed Chart)
    const dailyOrdersCtx = document.getElementById('dailyOrdersChart').getContext('2d');
    new Chart(dailyOrdersCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dailyPerformance['labels'] ?? []); ?>,
            datasets: [{
                label: 'จำนวนคำสั่งซื้อ',
                data: <?php echo json_encode($dailyPerformance['orders'] ?? []); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                type: 'bar'
            }, {
                label: 'จำนวนผู้ติดต่อ',
                data: <?php echo json_encode($dailyPerformance['contacts'] ?? []); ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderWidth: 2,
                type: 'line',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
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
                        text: 'จำนวนผู้ติดต่อ'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
<?php else: ?>
    // Monthly Sales Chart
    const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
    new Chart(monthlySalesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($monthlySales)); ?>,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: <?php echo json_encode(array_values($monthlySales)); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Customer Grades Chart
    const customerGradesCtx = document.getElementById('customerGradesChart').getContext('2d');
    new Chart(customerGradesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($customerGrades)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($customerGrades)); ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true
        }
    });
<?php endif; ?>
</script>
