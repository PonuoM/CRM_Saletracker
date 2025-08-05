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

if ($roleName === 'telesales') {
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
if ($roleName === 'telesales') {
    $assignedCustomers = $dashboardData['assigned_customers'] ?? 0;
    $followUpCustomers = $dashboardData['follow_up_customers'] ?? 0;
    $todayOrders = $dashboardData['today_orders'] ?? 0;
    $monthlyPerformance = $dashboardData['monthly_performance'] ?? [];
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

// ส่งข้อมูลไปยัง view
$dashboardData = $dashboardData; // ใช้ตัวแปรเดิมเพื่อความเข้ากันได้

// Include dashboard view
include APP_VIEWS . 'dashboard/index.php';
?> 