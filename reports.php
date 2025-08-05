<?php
/**
 * Reports Entry Point
 * จัดการการเรียกใช้ระบบรายงาน
 */

// โหลด configuration
require_once __DIR__ . '/config/config.php';

session_start();

// ตรวจสอบการยืนยันตัวตน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/CustomerService.php';
require_once __DIR__ . '/app/services/OrderService.php';

$db = new Database();
$customerService = new CustomerService();
$orderService = new OrderService();

$userId = $_SESSION['user_id'];
$roleName = $_SESSION['role_name'] ?? '';

// รับ action จาก URL
$action = $_GET['action'] ?? 'index';

// ดึงข้อมูลสำหรับรายงาน
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
    $sql = "SELECT COUNT(*) as count FROM customers";
    $result = $db->fetchOne($sql);
    $stats['total_customers'] = $result['count'];

    // สถิติคำสั่งซื้อ
    $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders";
    $result = $db->fetchOne($sql);
    $stats['total_orders'] = $result['count'];
    $stats['total_revenue'] = $result['total'] ?? 0;

    // คำสั่งซื้อรายเดือน
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month 
            ORDER BY month DESC";
    $stats['monthly_orders'] = $db->fetchAll($sql);

    // เกรดลูกค้า
    $sql = "SELECT grade, COUNT(*) as count FROM customers GROUP BY grade ORDER BY grade";
    $stats['customer_grades'] = $db->fetchAll($sql);

    // สถานะคำสั่งซื้อ
    $sql = "SELECT delivery_status, COUNT(*) as count FROM orders GROUP BY delivery_status";
    $stats['order_statuses'] = $db->fetchAll($sql);

} catch (Exception $e) {
    $error = $e->getMessage();
}

// แสดงหน้า reports
include __DIR__ . '/app/views/reports/index.php';
?> 