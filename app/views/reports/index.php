<?php
/**
 * Reports Index
 * หน้ารายงาน
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" style="font-family: 'Sukhumvit Set', sans-serif;">
        <i class="fas fa-tachometer-alt me-2"></i>
        <?php echo ($currentPage === 'dashboard') ? 'แดชบอร์ด' : 'รายงาน'; ?>
    </h1>
    <?php if (($_SESSION['role_name'] ?? '') === 'super_admin'): ?>
    <?php 
        require_once __DIR__ . '/../../core/Database.php';
        $db = $db ?? new Database();
        try {
            $companies = $db->fetchAll("SELECT company_id, company_name FROM companies WHERE is_active = 1 ORDER BY company_name");
        } catch (Exception $e) { $companies = []; }
        $currentCompany = $_SESSION['override_company_id'] ?? ($_SESSION['company_id'] ?? null);
    ?>
    <form method="get" class="d-flex align-items-center">
        <label class="me-2 mb-0"><i class="fas fa-building me-1"></i>บริษัท</label>
        <select class="form-select form-select-sm" name="company_override_id" onchange="this.form.submit()">
            <option value="">ทั้งหมด</option>
            <?php foreach ($companies as $co): ?>
                <option value="<?php echo (int)$co['company_id']; ?>" <?php echo ($currentCompany == $co['company_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($co['company_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
    <?php if ($currentPage === 'dashboard'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <select id="monthFilter" class="form-select form-select-sm" style="width: 150px;">
                <option value="">ทั้งหมด</option>
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $monthValue = date('Y') . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                    $monthName = [
                        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
                        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
                        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
                    ][str_pad($i, 2, '0', STR_PAD_LEFT)];
                    echo "<option value='$monthValue'>$monthName " . date('Y') . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="btn-group me-2">
            <a href="reports.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chart-bar me-1"></i>รายงานแบบเต็ม
            </a>
        </div>
    </div>
    <?php endif; ?>
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
                            <div class="kpi-value"><?php echo number_format($dashboardData['total_customers'] ?? 0); ?></div>
                            <div class="kpi-change positive">
                                <i class="fas fa-users me-1"></i>ลูกค้าทั้งหมดในระบบ
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>คำสั่งซื้อทั้งหมด</h3>
                            <div class="kpi-value"><?php echo number_format($dashboardData['total_orders'] ?? 0); ?></div>
                            <div class="kpi-change positive">
                                <i class="fas fa-shopping-cart me-1"></i>คำสั่งซื้อทั้งหมด
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>รายได้รวม</h3>
                            <div class="kpi-value">฿<?php echo number_format($dashboardData['total_revenue'] ?? 0, 2); ?></div>
                            <div class="kpi-change positive">
                                <i class="fas fa-money-bill-wave me-1"></i>รายได้รวมทั้งหมด
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>ยอดเฉลี่ย/คำสั่ง</h3>
                            <div class="kpi-value">฿<?php echo ($dashboardData['total_orders'] ?? 0) > 0 ? number_format(($dashboardData['total_revenue'] ?? 0) / ($dashboardData['total_orders'] ?? 1), 2) : '0.00'; ?></div>
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
                                        <tbody id="orderStatusTable">
                                            <?php
                                            // สถานะทั้งหมดที่ต้องแสดง
                                            $allStatuses = [
                                                'pending' => 'รอดำเนินการ',
                                                'processing' => 'กำลังดำเนินการ',
                                                'shipped' => 'จัดส่งแล้ว',
                                                'delivered' => 'จัดส่งสำเร็จ',
                                                'cancelled' => 'ยกเลิก'
                                            ];

                                            $existingStatuses = [];
                                            if (!empty($dashboardData['order_statuses'])) {
                                                foreach ($dashboardData['order_statuses'] as $status) {
                                                    $existingStatuses[$status['delivery_status']] = $status['count'];
                                                }
                                            }

                                            foreach ($allStatuses as $statusKey => $statusName):
                                                $count = $existingStatuses[$statusKey] ?? 0;
                                                $percentage = ($dashboardData['total_orders'] ?? 0) > 0 ? ($count / ($dashboardData['total_orders'] ?? 1)) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong style="color: <?php echo getStatusDarkColor($statusKey); ?>;">
                                                            <?php echo $statusName; ?>
                                                        </strong>
                                                    </td>
                                                    <td><?php echo number_format($count); ?></td>
                                                    <td><?php echo number_format($percentage, 1); ?>%</td>
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
                                            <?php foreach (($dashboardData['customer_grades'] ?? []) as $grade): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php echo getGradeColor($grade['grade']); ?>">
                                                            <?php echo $grade['grade']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($grade['count']); ?></td>
                                                    <td><?php echo ($dashboardData['total_customers'] ?? 0) > 0 ? number_format(($grade['count'] / ($dashboardData['total_customers'] ?? 1)) * 100, 1) : '0'; ?>%</td>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/page-transitions.js"></script>

    <script>
        // กราฟคำสั่งซื้อรายเดือน
        const monthlyOrdersCtx = document.getElementById('monthlyOrdersChart')?.getContext('2d');
        if (monthlyOrdersCtx) new Chart(monthlyOrdersCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dashboardData['monthly_orders'] ?? [], 'month')); ?>,
                datasets: [{
                    label: 'จำนวนคำสั่งซื้อ',
                    data: <?php echo json_encode(array_column($dashboardData['monthly_orders'] ?? [], 'count')); ?>,
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
        const customerGradesCtx = document.getElementById('customerGradesChart')?.getContext('2d');
        if (customerGradesCtx) new Chart(customerGradesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($dashboardData['customer_grades'] ?? [], 'grade')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($dashboardData['customer_grades'] ?? [], 'count')); ?>,
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

        // ตัวกรองเดือน (สำหรับ Dashboard)
        <?php if ($currentPage === 'dashboard'): ?>
        document.getElementById('monthFilter')?.addEventListener('change', function() {
            const selectedMonth = this.value;
            // ป้องกันการเรียก AJAX เมื่อหน้าเว็บโหลดครั้งแรก
            if (this.dataset.initialized !== 'true') {
                this.dataset.initialized = 'true';
                return;
            }
            filterByMonth(selectedMonth);
        });

        function filterByMonth(month) {
            // แสดง loading
            showLoading();

            // เรียก API เพื่อดึงข้อมูลตามเดือน
            fetch(`dashboard.php?ajax=1&month=${month}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text(); // ใช้ text() ก่อนเพื่อดู response จริง
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        updateKPICards(data);
                        updateCharts(data);
                        updateTables(data);
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        console.error('Raw Response:', text);
                        // ใช้ข้อมูลเริ่มต้นจาก PHP
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    hideLoading();
                });
        }

        function updateKPICards(data) {
            // อัปเดต KPI cards
            document.querySelector('.kpi-card:nth-child(1) .kpi-value').textContent = data.total_customers.toLocaleString();
            document.querySelector('.kpi-card:nth-child(2) .kpi-value').textContent = data.total_orders.toLocaleString();
            document.querySelector('.kpi-card:nth-child(3) .kpi-value').textContent = '฿' + data.total_revenue.toLocaleString();

            const avgOrder = data.total_orders > 0 ? data.total_revenue / data.total_orders : 0;
            document.querySelector('.kpi-card:nth-child(4) .kpi-value').textContent = '฿' + avgOrder.toLocaleString();
        }

        function updateCharts(data) {
            // อัปเดตกราฟ (จะเพิ่มในอนาคต)
        }

        function updateTables(data) {
            // อัปเดตตาราง (จะเพิ่มในอนาคต)
        }

        function showLoading() {
            // แสดง loading indicator
            document.body.style.cursor = 'wait';
        }

        function hideLoading() {
            // ซ่อน loading indicator
            document.body.style.cursor = 'default';
        }
        <?php endif; ?>
    </script>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info'; // not used in schema, kept for compatibility
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getStatusDarkColor($status) {
    switch ($status) {
        case 'pending': return '#856404';      // สีเหลืองเข้ม
        case 'processing': return '#0c5460';   // สีฟ้าเข้ม
        case 'shipped': return '#004085';      // สีน้ำเงินเข้ม
        case 'delivered': return '#155724';    // สีเขียวเข้ม
        case 'cancelled': return '#721c24';    // สีแดงเข้ม
        default: return '#495057';             // สีเทาเข้ม
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
?>



<?php
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
