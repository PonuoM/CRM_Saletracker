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
require_once __DIR__ . '/app/services/CompanyContext.php';
require_once __DIR__ . '/app/services/CustomerService.php';
require_once __DIR__ . '/app/services/OrderService.php';

$db = new Database();
$customerService = new CustomerService();
$orderService = new OrderService();

$userId = $_SESSION['user_id'];
$roleName = $_SESSION['role_name'] ?? '';
// อนุญาตให้ override เฉพาะ super_admin เท่านั้น
if ($roleName === 'super_admin' && isset($_REQUEST['company_override_id'])) {
    $id = (int)$_REQUEST['company_override_id'];
    if ($id > 0) {
        $_SESSION['override_company_id'] = $id;
    } else {
        unset($_SESSION['override_company_id']);
    }
}

$companyId = CompanyContext::getCompanyId($db);

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
    if ($roleName === 'super_admin') {
        $sql = "SELECT COUNT(*) as count FROM customers";
        $result = $db->fetchOne($sql);
    } else {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE company_id = ?";
        $result = $db->fetchOne($sql, [$companyId]);
    }
    $stats['total_customers'] = $result['count'];

    // สถิติคำสั่งซื้อ
    if ($roleName === 'super_admin') {
        $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders";
        $result = $db->fetchOne($sql);
    } else {
        $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE company_id = ?";
        $result = $db->fetchOne($sql, [$companyId]);
    }
    $stats['total_orders'] = $result['count'];
    $stats['total_revenue'] = $result['total'] ?? 0;

    // คำสั่งซื้อรายเดือน
    if ($roleName === 'super_admin') {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
                FROM orders 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month 
                ORDER BY month DESC";
        $stats['monthly_orders'] = $db->fetchAll($sql);
    } else {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
                FROM orders 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND company_id = ?
                GROUP BY month 
                ORDER BY month DESC";
        $stats['monthly_orders'] = $db->fetchAll($sql, [$companyId]);
    }

    // เกรดลูกค้า (ใช้คอลัมน์ customer_grade)
    if ($roleName === 'super_admin') {
        $sql = "SELECT customer_grade AS grade, COUNT(*) as count FROM customers GROUP BY customer_grade ORDER BY customer_grade";
        $stats['customer_grades'] = $db->fetchAll($sql);
    } else {
        $sql = "SELECT customer_grade AS grade, COUNT(*) as count FROM customers WHERE company_id = ? GROUP BY customer_grade ORDER BY customer_grade";
        $stats['customer_grades'] = $db->fetchAll($sql, [$companyId]);
    }

    // สถานะคำสั่งซื้อ
    if ($roleName === 'super_admin') {
        $sql = "SELECT delivery_status, COUNT(*) as count FROM orders GROUP BY delivery_status";
        $stats['order_statuses'] = $db->fetchAll($sql);
    } else {
        $sql = "SELECT delivery_status, COUNT(*) as count FROM orders WHERE company_id = ? GROUP BY delivery_status";
        $stats['order_statuses'] = $db->fetchAll($sql, [$companyId]);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

// Set page title and prepare content for layout
$pageTitle = 'รายงาน - CRM SalesTracker';
$currentPage = 'reports';

// Capture reports content
ob_start();
include __DIR__ . '/app/views/reports/index.php';
$content = ob_get_clean();

// Use main layout
include __DIR__ . '/app/views/layouts/main.php';
?>
