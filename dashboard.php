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

// Load dashboard data
require_once APP_ROOT . '/app/services/DashboardService.php';
$dashboardService = new DashboardService();

// Get dashboard data based on user role
$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// Redirect supervisor to dedicated dashboard
if ($roleName === 'supervisor') {
    header('Location: dashboard_supervisor.php');
    exit;
}
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
$pageTitle = 'แดชบอร์ด - CRM SalesTracker';
$currentPage = 'dashboard';

// Capture dashboard content
ob_start();
include APP_VIEWS . 'dashboard/content.php';
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>