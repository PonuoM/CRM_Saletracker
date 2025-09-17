<?php
/**
 * Dashboard สำหรับ Supervisor
 * แสดงข้อมูลแบบง่ายและชัดเจน
 */

session_start();

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

$db = new Database();
$auth = new Auth($db);

// Check if user is supervisor
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role_name'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$supervisorId = $user['user_id'];
$companyId = $user['company_id']; // เพิ่มการดึง company_id

// Get selected month
$selectedMonth = $_GET['month'] ?? date('Y-m');

$pageTitle = 'แดชบอร์ด Supervisor - CRM SalesTracker';
$currentPage = 'dashboard';

// Get data
try {
    // แถวที่ 1: จำนวนลูกค้าทั้งหมด, ยอดคำสั่งซื้อทั้งหมด (เดือน), ยอดขายเดือนนี้, สมาชิกทีม
    $totalCustomers = $db->fetchOne("SELECT COUNT(*) as count FROM customers WHERE assigned_to = ? AND company_id = ? AND is_active = 1", [$supervisorId, $companyId]);
    $monthlyOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE created_by = ? AND company_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?", [$supervisorId, $companyId, $selectedMonth]);
    $monthlySales = $db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE created_by = ? AND company_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?", [$supervisorId, $companyId, $selectedMonth]);
    $teamMembers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE supervisor_id = ? AND company_id = ? AND is_active = 1", [$supervisorId, $companyId]);

    // แถวที่ 2: ยอดขาย/จำนวนชิ้น ตามประเภทสินค้า
    // ปุ๋ยใหญ่ (ปุ๋ยกระสอบใหญ่)
    $fertilizer_large = $db->fetchOne("
        SELECT COALESCE(SUM(oi.total_price), 0) as sales, COALESCE(SUM(oi.quantity), 0) as quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.created_by = ? AND o.company_id = ? AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        AND p.category = 'ปุ๋ยกระสอบใหญ่'
    ", [$supervisorId, $companyId, $selectedMonth]);

    // ปุ๋ยเล็ก (ปุ๋ยกระสอบเล็ก)
    $fertilizer_small = $db->fetchOne("
        SELECT COALESCE(SUM(oi.total_price), 0) as sales, COALESCE(SUM(oi.quantity), 0) as quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.created_by = ? AND o.company_id = ? AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        AND p.category = 'ปุ๋ยกระสอบเล็ก'
    ", [$supervisorId, $companyId, $selectedMonth]);

    // ชีวิภัณฑ์
    $biological = $db->fetchOne("
        SELECT COALESCE(SUM(oi.total_price), 0) as sales, COALESCE(SUM(oi.quantity), 0) as quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.created_by = ? AND o.company_id = ? AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        AND p.category = 'ชีวภัณฑ์'
    ", [$supervisorId, $companyId, $selectedMonth]);

    // ข้อมูลสำหรับกราฟ
    // กราฟที่ 1: ยอดขายประจำเดือนตามประเภทสินค้า
    $monthlySalesByCategory = $db->fetchAll("
        SELECT
            CASE
                WHEN p.category = 'ปุ๋ยกระสอบใหญ่' THEN 'ปุ๋ยใหญ่'
                WHEN p.category = 'ปุ๋ยกระสอบเล็ก' THEN 'ปุ๋ยเล็ก'
                WHEN p.category = 'ชีวภัณฑ์' THEN 'ชีวภัณฑ์'
                WHEN p.category = 'ของแถม' THEN 'ของแถม'
                ELSE 'อื่นๆ'
            END as category_name,
            COALESCE(SUM(oi.total_price), 0) as sales
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.created_by = ? AND o.company_id = ? AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        GROUP BY CASE
            WHEN p.category = 'ปุ๋ยกระสอบใหญ่' THEN 'ปุ๋ยใหญ่'
            WHEN p.category = 'ปุ๋ยกระสอบเล็ก' THEN 'ปุ๋ยเล็ก'
            WHEN p.category = 'ชีวภัณฑ์' THEN 'ชีวภัณฑ์'
            WHEN p.category = 'ของแถม' THEN 'ของแถม'
            ELSE 'อื่นๆ'
        END
        HAVING sales > 0
        ORDER BY sales DESC
    ", [$supervisorId, $companyId, $selectedMonth]);

    // กราฟที่ 2: จำนวนคำสั่งซื้อและลูกค้าที่ติดต่อรายเดือน
    $monthlyStats = $db->fetchAll("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as orders_count
        FROM orders
        WHERE created_by = ? AND company_id = ?
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ", [$supervisorId, $companyId]);

    $monthlyContacts = $db->fetchAll("
        SELECT
            DATE_FORMAT(last_contact_at, '%Y-%m') as month,
            COUNT(DISTINCT customer_id) as contacts_count
        FROM customers
        WHERE assigned_to = ? AND company_id = ?
        AND last_contact_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND last_contact_at IS NOT NULL
        GROUP BY DATE_FORMAT(last_contact_at, '%Y-%m')
        ORDER BY month ASC
    ", [$supervisorId, $companyId]);

    // กราฟที่ 3: ยอดขายรายวันของทีม
    $teamDailySales = $db->fetchAll("
        SELECT
            DATE(o.created_at) as date,
            u.full_name,
            COALESCE(SUM(o.total_amount), 0) as sales
        FROM orders o
        JOIN users u ON o.created_by = u.user_id
        WHERE u.supervisor_id = ? AND u.company_id = ? AND o.company_id = ?
        AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        GROUP BY DATE(o.created_at), u.user_id, u.full_name
        ORDER BY date ASC, u.full_name ASC
    ", [$supervisorId, $companyId, $companyId, $selectedMonth]);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Start content capture
ob_start();
?>

<!-- Supervisor Dashboard -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-chart-line me-2"></i>
                    แดชบอร์ด Supervisor
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label for="month" class="form-label mb-0">เดือน:</label>
                        <input type="month" class="form-control form-control-sm" id="month" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i>ดู
                        </button>
                    </form>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    เกิดข้อผิดพลาด: <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- KPI Cards แถวที่ 1 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-primary"><?php echo number_format($totalCustomers['count'] ?? 0); ?></h4>
                                    <p class="mb-0 text-muted">จำนวนลูกค้าทั้งหมด</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x text-primary opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-success"><?php echo number_format($monthlyOrders['count'] ?? 0); ?></h4>
                                    <p class="mb-0 text-muted">ยอดคำสั่งซื้อทั้งหมด (เดือน)</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x text-success opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-info">฿<?php echo number_format($monthlySales['total'] ?? 0, 0); ?></h4>
                                    <p class="mb-0 text-muted">ยอดขายเดือนนี้</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x text-info opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="text-warning"><?php echo number_format($teamMembers['count'] ?? 0); ?></h4>
                                    <p class="mb-0 text-muted">สมาชิกทีม</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-friends fa-2x text-warning opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards แถวที่ 2 -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="text-success">฿<?php echo number_format($fertilizer_large['sales'] ?? 0, 0); ?></h5>
                                    <p class="mb-1 text-muted">ยอดขาย (ปุ๋ยใหญ่)</p>
                                    <small class="text-secondary"><?php echo number_format($fertilizer_large['quantity'] ?? 0); ?> ชิ้น</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-seedling fa-2x text-success opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="text-primary">฿<?php echo number_format($fertilizer_small['sales'] ?? 0, 0); ?></h5>
                                    <p class="mb-1 text-muted">ยอดขาย (ปุ๋ยเล็ก)</p>
                                    <small class="text-secondary"><?php echo number_format($fertilizer_small['quantity'] ?? 0); ?> ชิ้น</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-leaf fa-2x text-primary opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="text-info">฿<?php echo number_format($biological['sales'] ?? 0, 0); ?></h5>
                                    <p class="mb-1 text-muted">ยอดขาย (ชีวิภัณฑ์)</p>
                                    <small class="text-secondary"><?php echo number_format($biological['quantity'] ?? 0); ?> ชิ้น</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-microscope fa-2x text-info opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="chartTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="sales-category-tab" data-bs-toggle="tab" data-bs-target="#sales-category" type="button" role="tab">
                                        <i class="fas fa-chart-bar me-1"></i>ยอดขายตามประเภทสินค้า
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="orders-contacts-tab" data-bs-toggle="tab" data-bs-target="#orders-contacts" type="button" role="tab">
                                        <i class="fas fa-chart-line me-1"></i>คำสั่งซื้อ & การติดต่อ
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="team-sales-tab" data-bs-toggle="tab" data-bs-target="#team-sales" type="button" role="tab">
                                        <i class="fas fa-users me-1"></i>ยอดขายทีม
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="chartTabContent">
                                <!-- กราฟที่ 1: ยอดขายตามประเภทสินค้า -->
                                <div class="tab-pane fade show active" id="sales-category" role="tabpanel">
                                    <canvas id="salesCategoryChart" height="100"></canvas>
                                </div>

                                <!-- กราฟที่ 2: คำสั่งซื้อและการติดต่อ -->
                                <div class="tab-pane fade" id="orders-contacts" role="tabpanel">
                                    <canvas id="ordersContactsChart" height="100"></canvas>
                                </div>

                                <!-- กราฟที่ 3: ยอดขายทีม -->
                                <div class="tab-pane fade" id="team-sales" role="tabpanel">
                                    <canvas id="teamSalesChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ข้อมูลสำหรับกราฟ
const salesCategoryData = <?php echo json_encode($monthlySalesByCategory ?? []); ?>;
const monthlyStatsData = <?php echo json_encode($monthlyStats ?? []); ?>;
const monthlyContactsData = <?php echo json_encode($monthlyContacts ?? []); ?>;
const teamDailySalesData = <?php echo json_encode($teamDailySales ?? []); ?>;

// กราฟที่ 1: ยอดขายตามประเภทสินค้า (Column Stack)
const ctx1 = document.getElementById('salesCategoryChart').getContext('2d');
const categoryLabels = salesCategoryData.map(item => item.category_name || 'ไม่ระบุ');
const categorySales = salesCategoryData.map(item => parseFloat(item.sales));

new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: categoryLabels,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: categorySales,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 205, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'ยอดขายประจำเดือนตามประเภทสินค้า'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'ยอดขาย: ฿' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '฿' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// กราฟที่ 2: คำสั่งซื้อและการติดต่อ (Bar + Line)
const ctx2 = document.getElementById('ordersContactsChart').getContext('2d');

// รวมข้อมูลเดือน
const allMonths = [...new Set([
    ...monthlyStatsData.map(item => item.month),
    ...monthlyContactsData.map(item => item.month)
])].sort();

const ordersData = allMonths.map(month => {
    const found = monthlyStatsData.find(item => item.month === month);
    return found ? parseInt(found.orders_count) : 0;
});

const contactsData = allMonths.map(month => {
    const found = monthlyContactsData.find(item => item.month === month);
    return found ? parseInt(found.contacts_count) : 0;
});

new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: allMonths.map(month => {
            const date = new Date(month + '-01');
            return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short' });
        }),
        datasets: [{
            type: 'bar',
            label: 'จำนวนคำสั่งซื้อ',
            data: ordersData,
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            type: 'line',
            label: 'จำนวนลูกค้าที่ติดต่อ',
            data: contactsData,
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'จำนวนคำสั่งซื้อและลูกค้าที่ติดต่อรายเดือน'
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
                    text: 'จำนวนลูกค้าที่ติดต่อ'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// กราฟที่ 3: ยอดขายทีมรายวัน
const ctx3 = document.getElementById('teamSalesChart').getContext('2d');

// จัดกลุ่มข้อมูลตามวันและสมาชิกทีม
const teamMembers = [...new Set(teamDailySalesData.map(item => item.full_name))];
const dates = [...new Set(teamDailySalesData.map(item => item.date))].sort();

const teamDatasets = teamMembers.map((member, index) => {
    const memberData = dates.map(date => {
        const found = teamDailySalesData.find(item => item.date === date && item.full_name === member);
        return found ? parseFloat(found.sales) : 0;
    });

    const colors = [
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 205, 86, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)'
    ];

    return {
        label: member,
        data: memberData,
        backgroundColor: colors[index % colors.length],
        borderColor: colors[index % colors.length].replace('0.8', '1'),
        borderWidth: 1
    };
});

new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: dates.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit' });
        }),
        datasets: teamDatasets
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'ยอดขายรายวันของทีม'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ฿' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                stacked: false
            },
            y: {
                beginAtZero: true,
                stacked: false,
                ticks: {
                    callback: function(value) {
                        return '฿' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// เพิ่ม event listener สำหรับ tabs เพื่อ resize กราหเมื่อเปลี่ยน tab
document.addEventListener('DOMContentLoaded', function() {
    const chartTabs = document.querySelectorAll('#chartTabs button[data-bs-toggle="tab"]');
    chartTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            // Trigger resize event เพื่อให้กราฟปรับขนาดใหม่
            window.dispatchEvent(new Event('resize'));
        });
    });
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
