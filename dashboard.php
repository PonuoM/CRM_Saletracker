<?php
/**
 * CRM SalesTracker - Dashboard Entry Point
 * จุดเข้าถึงหน้าหลักของระบบ
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get dashboard data based on user role
$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// Redirect supervisor to dedicated dashboard
if ($roleName === 'supervisor') {
    header('Location: dashboard_supervisor.php');
    exit;
}

// For admin, company_admin and super_admin, use reports-style dashboard
if (in_array($roleName, ['admin', 'company_admin', 'super_admin'])) {
    // Load required services for reports
    require_once __DIR__ . '/app/core/Database.php';
    require_once __DIR__ . '/app/services/CompanyContext.php';

    $db = new Database();
    
    // อนุญาตให้ override เฉพาะ super_admin เท่านั้น
    if ($roleName === 'super_admin' && isset($_REQUEST['company_override_id'])) {
        $id = (int)$_REQUEST['company_override_id'];
        if ($id > 0) { $_SESSION['override_company_id'] = $id; } else { unset($_SESSION['override_company_id']); }
    }
    
    $companyId = CompanyContext::getCompanyId($db);

    // Handle AJAX requests for month filtering
    if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
        $selectedMonth = $_GET['month'] ?? '';

        // ดึงข้อมูลตามเดือนที่เลือก
        $monthStats = [
            'total_customers' => 0,
            'total_orders' => 0,
            'total_revenue' => 0
        ];

        try {
            if ($selectedMonth) {
                // ข้อมูลตามเดือนที่เลือก
                $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?" . ($companyId ? " AND company_id = ?" : "");
                $params = [$selectedMonth];
                if ($companyId) { $params[] = $companyId; }
                $result = $db->fetchOne($sql, $params);
                $monthStats['total_orders'] = $result['count'];
                $monthStats['total_revenue'] = $result['total'] ?? 0;

                // ลูกค้าทั้งหมด (ไม่เปลี่ยนตามเดือน)
                $sql = "SELECT COUNT(*) as count FROM customers" . ($companyId ? " WHERE company_id = ?" : "");
                $result = $db->fetchOne($sql, $companyId ? [$companyId] : []);
                $monthStats['total_customers'] = $result['count'];
            } else {
                // ข้อมูลทั้งหมด
                $sql = "SELECT COUNT(*) as count FROM customers" . ($companyId ? " WHERE company_id = ?" : "");
                $result = $db->fetchOne($sql, $companyId ? [$companyId] : []);
                $monthStats['total_customers'] = $result['count'];

                $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders" . ($companyId ? " WHERE company_id = ?" : "");
                $result = $db->fetchOne($sql, $companyId ? [$companyId] : []);
                $monthStats['total_orders'] = $result['count'];
                $monthStats['total_revenue'] = $result['total'] ?? 0;
            }
        } catch (Exception $e) {
            // ใช้ค่าเริ่มต้นในกรณีเกิด error
        }

        header('Content-Type: application/json');
        echo json_encode($monthStats);
        exit;
    }

    // ดึงข้อมูลสำหรับ dashboard (แบบ reports)
    $stats = [
        'total_customers' => 0,
        'total_orders' => 0,
        'total_revenue' => 0,
        'monthly_orders' => [],
        'customer_grades' => [],
        'order_statuses' => []
    ];

    try {
        // สถิติลูกค้า
        $sql = "SELECT COUNT(*) as count FROM customers" . ($companyId ? " WHERE company_id = ?" : "");
        $result = $db->fetchOne($sql, $companyId ? [$companyId] : []);
        $stats['total_customers'] = $result['count'];

        // สถิติคำสั่งซื้อ
        $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders" . ($companyId ? " WHERE company_id = ?" : "");
        $result = $db->fetchOne($sql, $companyId ? [$companyId] : []);
        $stats['total_orders'] = $result['count'];
        $stats['total_revenue'] = $result['total'] ?? 0;

        // คำสั่งซื้อรายเดือน
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total
                FROM orders
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)" . ($companyId ? " AND company_id = ?" : "") . "
                GROUP BY month
                ORDER BY month DESC";
        $stats['monthly_orders'] = $db->fetchAll($sql, $companyId ? [$companyId] : []);

        // เกรดลูกค้า
        $sql = "SELECT customer_grade AS grade, COUNT(*) as count FROM customers" . ($companyId ? " WHERE company_id = ?" : "") . " GROUP BY customer_grade ORDER BY customer_grade";
        $stats['customer_grades'] = $db->fetchAll($sql, $companyId ? [$companyId] : []);

        // สถานะคำสั่งซื้อ
        $sql = "SELECT delivery_status, COUNT(*) as count FROM orders" . ($companyId ? " WHERE company_id = ?" : "") . " GROUP BY delivery_status";
        $stats['order_statuses'] = $db->fetchAll($sql, $companyId ? [$companyId] : []);

    } catch (Exception $e) {
        $error = $e->getMessage();
        // กำหนดค่าเริ่มต้นในกรณีที่เกิด error
        $stats = [
            'total_customers' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'monthly_orders' => [],
            'customer_grades' => [],
            'order_statuses' => []
        ];
    }

    // Set page title and prepare content for layout
    $pageTitle = 'แดชบอร์ด - Customer Sales';
    $currentPage = 'dashboard';

    // Capture reports content for admin dashboard
    ob_start();
    // ส่งตัวแปร $stats ไปยัง view
    $dashboardData = $stats; // ใช้ชื่อตัวแปรที่ view คาดหวัง
    include __DIR__ . '/app/views/reports/index.php';
    $content = ob_get_clean();

    // Use main layout
    include __DIR__ . '/app/views/layouts/main.php';
    exit;
}

// For other roles, use original dashboard service
require_once APP_ROOT . '/app/services/DashboardService.php';
$dashboardService = new DashboardService();
// Selected month for dashboards that support filtering (YYYY-MM)
$selectedMonth = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])
    ? $_GET['month']
    : date('Y-m');

if ($roleName === 'telesales' || $roleName === 'supervisor') {
    $result = $dashboardService->getTelesalesDashboard($userId);
    if ($result['success']) {
        $dashboardData = $result['data'];
    } else {
        // ถ้าเกิดข้อผิดพลาด ให้ใช้ข้อมูลว่าง
        $dashboardData = [
            'assigned_customers' => 0,
            'follow_up_customers' => 0,
            'today_orders' => 0,
            'monthly_performance' => []
        ];
    }
    // Daily performance for selected month (sales per day and contacts per day)
    $dailyPerformance = $dashboardService->getDailyPerformanceForMonth((int)$userId, $selectedMonth);
    // Monthly KPIs for selected month
    $monthlyKpis = $dashboardService->getMonthlyKpisForTelesales((int)$userId, $selectedMonth);
    // Monthly orders count
    $monthlyOrders = $monthlyKpis['total_orders'] ?? 0;
} else {
    $result = $dashboardService->getDashboardData($userId, $roleName);
    if ($result['success']) {
        $dashboardData = $result['data'];
    } else {
        // ถ้าเกิดข้อผิดพลาด ให้ใช้ข้อมูลว่าง
        $dashboardData = [
            'total_customers' => 0,
            'hot_customers' => 0,
            'total_orders' => 0,
            'total_sales' => 0,
            'monthly_sales' => [],
            'recent_activities' => [],
            'customer_grades' => [],
            'order_status' => []
        ];
    }
}

// Extract data for the view
if ($roleName === 'telesales' || $roleName === 'supervisor') {
    $assignedCustomers = $dashboardData['assigned_customers'] ?? 0;
    $followUpCustomers = $dashboardData['follow_up_customers'] ?? 0;
    $todayOrders = $dashboardData['today_orders'] ?? 0;
    $monthlyPerformance = $dashboardData['monthly_performance'] ?? [];
    // expose daily performance to the view
    $dailyPerformance = $dailyPerformance ?? [
        'labels' => [],
        'sales' => [],
        'contacts' => [],
        'start_date' => null,
        'end_date' => null,
    ];

    // Ensure monthlyKpis is available
    $monthlyKpis = $monthlyKpis ?? [
        'total_orders' => 0,
        'total_sales' => 0,
        'total_contacts' => 0,
        'conversion_rate' => 0
    ];

    // Ensure monthlyOrders is available
    $monthlyOrders = $monthlyOrders ?? 0;

} else {
    $totalCustomers = $dashboardData['total_customers'] ?? 0;
    $hotCustomers = $dashboardData['hot_customers'] ?? 0;
    $totalOrders = $dashboardData['total_orders'] ?? 0;
    $totalSales = $dashboardData['total_sales'] ?? 0;
    $monthlySales = $dashboardData['monthly_sales'] ?? [];
    $recentActivities = $dashboardData['recent_activities'] ?? [];
    $customerGrades = $dashboardData['customer_grades'] ?? [];
    $orderStatus = $dashboardData['order_status'] ?? [];
}

// Set page title and prepare content for layout
$pageTitle = 'แดชบอร์ด - Customer Sales';
$currentPage = 'dashboard';

// Capture dashboard content
ob_start();
include APP_VIEWS . 'dashboard/content.php';
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
