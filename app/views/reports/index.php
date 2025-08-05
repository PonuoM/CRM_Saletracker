<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - CRM Sales Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <?php include __DIR__ . '/../components/header.php'; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2" style="font-family: 'Sukhumvit Set', sans-serif;">
                        <i class="fas fa-chart-bar me-2"></i>
                        รายงาน
                    </h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        เกิดข้อผิดพลาด: <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- สถิติสรุป -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>ลูกค้าทั้งหมด</h3>
                            <div class="kpi-value"><?php echo number_format($stats['total_customers']); ?></div>
                            <div class="kpi-change positive">
                                <i class="fas fa-users me-1"></i>ลูกค้าทั้งหมดในระบบ
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>คำสั่งซื้อทั้งหมด</h3>
                            <div class="kpi-value"><?php echo number_format($stats['total_orders']); ?></div>
                            <div class="kpi-change positive">
                                <i class="fas fa-shopping-cart me-1"></i>คำสั่งซื้อทั้งหมด
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>รายได้รวม</h3>
                            <div class="kpi-value">฿<?php echo number_format($stats['total_revenue'], 2); ?></div>
                            <div class="kpi-change positive">
                                <i class="fas fa-money-bill-wave me-1"></i>รายได้รวมทั้งหมด
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>ยอดเฉลี่ย/คำสั่ง</h3>
                            <div class="kpi-value">฿<?php echo $stats['total_orders'] > 0 ? number_format($stats['total_revenue'] / $stats['total_orders'], 2) : '0.00'; ?></div>
                            <div class="kpi-change info">
                                <i class="fas fa-chart-line me-1"></i>ยอดเฉลี่ยต่อคำสั่ง
                            </div>
                        </div>
                    </div>
                </div>

                <!-- กราฟ -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    คำสั่งซื้อรายเดือน
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyOrdersChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    เกรดลูกค้า
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="customerGradesChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ตารางข้อมูล -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-table me-2"></i>
                                    สถานะคำสั่งซื้อ
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>สถานะ</th>
                                                <th>จำนวน</th>
                                                <th>เปอร์เซ็นต์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['order_statuses'] as $status): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php echo getStatusColor($status['delivery_status']); ?>">
                                                            <?php echo getStatusText($status['delivery_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($status['count']); ?></td>
                                                    <td><?php echo $stats['total_orders'] > 0 ? number_format(($status['count'] / $stats['total_orders']) * 100, 1) : '0'; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-table me-2"></i>
                                    เกรดลูกค้า
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>เกรด</th>
                                                <th>จำนวน</th>
                                                <th>เปอร์เซ็นต์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['customer_grades'] as $grade): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php echo getGradeColor($grade['grade']); ?>">
                                                            <?php echo $grade['grade']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($grade['count']); ?></td>
                                                    <td><?php echo $stats['total_customers'] > 0 ? number_format(($grade['count'] / $stats['total_customers']) * 100, 1) : '0'; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // กราฟคำสั่งซื้อรายเดือน
        const monthlyOrdersCtx = document.getElementById('monthlyOrdersChart').getContext('2d');
        new Chart(monthlyOrdersCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($stats['monthly_orders'], 'month')); ?>,
                datasets: [{
                    label: 'จำนวนคำสั่งซื้อ',
                    data: <?php echo json_encode(array_column($stats['monthly_orders'], 'count')); ?>,
                    borderColor: '#7c9885',
                    backgroundColor: 'rgba(124, 152, 133, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // กราฟเกรดลูกค้า
        const customerGradesCtx = document.getElementById('customerGradesChart').getContext('2d');
        new Chart(customerGradesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($stats['customer_grades'], 'grade')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats['customer_grades'], 'count')); ?>,
                    backgroundColor: [
                        '#9bbf8b', // Soft Mint Green
                        '#7c9885', // Soft Sage Green
                        '#a6c8f4', // Soft Blue
                        '#e6c27d', // Soft Peach
                        '#d4a5a5'  // Soft Rose
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch ($status) {
        case 'pending': return 'รอดำเนินการ';
        case 'processing': return 'กำลังดำเนินการ';
        case 'shipped': return 'จัดส่งแล้ว';
        case 'delivered': return 'จัดส่งสำเร็จ';
        case 'cancelled': return 'ยกเลิก';
        default: return $status;
    }
}

function getGradeColor($grade) {
    switch ($grade) {
        case 'A+': return 'success';
        case 'A': return 'primary';
        case 'B': return 'info';
        case 'C': return 'warning';
        case 'D': return 'danger';
        default: return 'secondary';
    }
}
?> 