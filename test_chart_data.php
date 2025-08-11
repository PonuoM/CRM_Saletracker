<?php
/**
 * ทดสอบข้อมูลกราฟ Dashboard
 * ตรวจสอบการดึงข้อมูลยอดขายตาม category
 */

session_start();

// Set up test session (simulate login as telesales)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['username'] = 'telesales';
$_SESSION['full_name'] = 'Telesales User';

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/services/DashboardService.php';

$pageTitle = 'ทดสอบข้อมูลกราฟ - CRM SalesTracker';
$currentPage = 'test';

// Get dashboard service
$dashboardService = new DashboardService($db);
$selectedMonth = $_GET['month'] ?? date('Y-m');
$userId = $_SESSION['user_id'];

// Get data
$dailyPerformance = $dashboardService->getDailyPerformanceForMonth((int)$userId, $selectedMonth);
$monthlyKpis = $dashboardService->getMonthlyKpisForTelesales((int)$userId, $selectedMonth);

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบข้อมูลกราฟ Dashboard</h1>
            </div>

            <!-- Month Selector -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <form method="GET" class="d-flex align-items-end gap-2">
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

            <!-- Data Debug -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-database me-2"></i>
                                Daily Performance Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Labels (วันที่):</h6>
                            <pre class="bg-light p-2 small"><?php echo json_encode($dailyPerformance['labels'] ?? [], JSON_PRETTY_PRINT); ?></pre>
                            
                            <h6>ปุ๋ยกระสอบใหญ่:</h6>
                            <pre class="bg-light p-2 small"><?php echo json_encode($dailyPerformance['fertilizer_large'] ?? [], JSON_PRETTY_PRINT); ?></pre>
                            
                            <h6>ปุ๋ยกระสอบเล็ก:</h6>
                            <pre class="bg-light p-2 small"><?php echo json_encode($dailyPerformance['fertilizer_small'] ?? [], JSON_PRETTY_PRINT); ?></pre>
                            
                            <h6>ชีวภัณฑ์:</h6>
                            <pre class="bg-light p-2 small"><?php echo json_encode($dailyPerformance['bio_products'] ?? [], JSON_PRETTY_PRINT); ?></pre>
                            
                            <h6>ของแถม:</h6>
                            <pre class="bg-light p-2 small"><?php echo json_encode($dailyPerformance['freebies'] ?? [], JSON_PRETTY_PRINT); ?></pre>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Monthly KPIs Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Monthly KPIs:</h6>
                            <pre class="bg-light p-2 small"><?php echo json_encode($monthlyKpis, JSON_PRETTY_PRINT); ?></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-column me-2"></i>
                                ทดสอบกราฟยอดขายตามประเภท
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="testSalesChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Query Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-database me-2"></i>
                                Database Query Test
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Test the actual query
                            $startDate = $selectedMonth . '-01';
                            $endDate = date('Y-m-t', strtotime($startDate));
                            
                            try {
                                $testQuery = "SELECT DATE(o.order_date) as d, p.category, SUM(oi.total_price) as category_sales
                                             FROM orders o
                                             JOIN order_items oi ON o.order_id = oi.order_id
                                             JOIN products p ON oi.product_id = p.product_id
                                             WHERE o.created_by = :user_id
                                               AND o.payment_status = 'paid'
                                               AND o.order_date BETWEEN :start_date AND :end_date
                                             GROUP BY DATE(o.order_date), p.category
                                             ORDER BY d, p.category";
                                
                                $testResults = $db->fetchAll($testQuery, [
                                    'user_id' => $userId,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                ]);
                                
                                echo "<h6>Query Results:</h6>";
                                echo "<pre class='bg-light p-2 small'>" . json_encode($testResults, JSON_PRETTY_PRINT) . "</pre>";
                                
                                if (empty($testResults)) {
                                    echo "<div class='alert alert-warning'>";
                                    echo "<h6><i class='fas fa-exclamation-triangle me-2'></i>ไม่พบข้อมูล</h6>";
                                    echo "<p>เป็นไปได้ว่า:</p>";
                                    echo "<ul>";
                                    echo "<li>ไม่มีคำสั่งซื้อในเดือนนี้</li>";
                                    echo "<li>ไม่มีคำสั่งซื้อที่ payment_status = 'paid'</li>";
                                    echo "<li>ไม่มีข้อมูลใน order_items หรือ products</li>";
                                    echo "<li>category ในตาราง products ไม่ตรงกับที่กำหนด</li>";
                                    echo "</ul>";
                                    echo "</div>";
                                }
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'>";
                                echo "<h6><i class='fas fa-times-circle me-2'></i>Database Error</h6>";
                                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                                echo "</div>";
                            }
                            ?>
                            
                            <h6>SQL Query:</h6>
                            <pre class="bg-dark text-light p-2 small"><?php echo htmlspecialchars($testQuery ?? ''); ?></pre>
                            
                            <h6>Parameters:</h6>
                            <ul>
                                <li><strong>user_id:</strong> <?php echo $userId; ?></li>
                                <li><strong>start_date:</strong> <?php echo $startDate; ?></li>
                                <li><strong>end_date:</strong> <?php echo $endDate; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tools me-2"></i>
                                Troubleshooting
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>ตรวจสอบข้อมูลในตาราง:</h6>
                            
                            <?php
                            // Check orders table
                            $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE created_by = ?", [$userId]);
                            echo "<p><strong>Orders:</strong> " . ($orderCount['count'] ?? 0) . " รายการ</p>";
                            
                            // Check order_items table
                            $itemCount = $db->fetchOne("SELECT COUNT(*) as count FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE o.created_by = ?", [$userId]);
                            echo "<p><strong>Order Items:</strong> " . ($itemCount['count'] ?? 0) . " รายการ</p>";
                            
                            // Check products table
                            $productCount = $db->fetchOne("SELECT COUNT(*) as count FROM products");
                            echo "<p><strong>Products:</strong> " . ($productCount['count'] ?? 0) . " รายการ</p>";
                            
                            // Check categories
                            $categories = $db->fetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
                            echo "<p><strong>Categories ที่มี:</strong></p>";
                            echo "<ul>";
                            foreach ($categories as $cat) {
                                echo "<li>" . htmlspecialchars($cat['category']) . "</li>";
                            }
                            echo "</ul>";
                            ?>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>วิธีแก้ไข:</h6>
                                <ol>
                                    <li>ตรวจสอบว่ามีข้อมูลในตาราง orders, order_items, products</li>
                                    <li>ตรวจสอบว่า products.category มีค่า: ปุ๋ยกระสอบใหญ่, ปุ๋ยกระสอบเล็ก, ชีวภัณฑ์, ของแถม</li>
                                    <li>ตรวจสอบว่า orders.payment_status = 'paid'</li>
                                    <li>ตรวจสอบว่า orders.created_by ตรงกับ user_id</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Chart Data Test ===');
    
    // Test chart data
    const chartData = {
        labels: <?php echo json_encode($dailyPerformance['labels'] ?? []); ?>,
        fertilizer_large: <?php echo json_encode($dailyPerformance['fertilizer_large'] ?? []); ?>,
        fertilizer_small: <?php echo json_encode($dailyPerformance['fertilizer_small'] ?? []); ?>,
        bio_products: <?php echo json_encode($dailyPerformance['bio_products'] ?? []); ?>,
        freebies: <?php echo json_encode($dailyPerformance['freebies'] ?? []); ?>
    };
    
    console.log('Chart data:', chartData);
    
    // Create test chart
    const ctx = document.getElementById('testSalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'ปุ๋ยกระสอบใหญ่',
                data: chartData.fertilizer_large,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }, {
                label: 'ปุ๋ยกระสอบเล็ก',
                data: chartData.fertilizer_small,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }, {
                label: 'ชีวภัณฑ์',
                data: chartData.bio_products,
                backgroundColor: 'rgba(255, 206, 86, 0.8)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }, {
                label: 'ของแถม',
                data: chartData.freebies,
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
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
