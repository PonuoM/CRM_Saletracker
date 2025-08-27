<?php
/**
 * Search Page
 * ระบบค้นหาลูกค้าและยอดขาย
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include configuration first
    require_once __DIR__ . '/config/config.php';

    session_start();

    // ตรวจสอบการยืนยันตัวตน
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // ตรวจสอบสิทธิ์การเข้าถึง (Role 3, 4, 5)
    $roleName = $_SESSION['role_name'] ?? '';
    if (!in_array($roleName, ['supervisor', 'telesales', 'admin', 'super_admin'])) {
        header('Location: dashboard.php');
        exit;
    }

    // Include required files
    require_once APP_ROOT . '/app/controllers/SearchController.php';

    // สร้าง instance ของ SearchController
    $searchController = new SearchController();

    // รับ action จาก URL
    $action = $_GET['action'] ?? 'index';

    // จัดการ routing ตาม action
    switch ($action) {
        case 'search':
            // AJAX search request
            header('Content-Type: application/json');
            $searchController->search();
            break;
            
        case 'customer_details':
            // AJAX get customer details
            header('Content-Type: application/json');
            $searchController->getCustomerDetails();
            break;
            
        case 'order_details':
            // AJAX get order details
            header('Content-Type: application/json');
            $searchController->getOrderDetails();
            break;
            
        default:
            // Default action: show search page
            $searchController->index();
            break;
    }

} catch (Exception $e) {
    // แสดง error ในกรณีที่มีปัญหา
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Search Error</title></head><body>";
    echo "<h1>เกิดข้อผิดพลาด</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p><a href='dashboard.php'>กลับไปหน้าแดชบอร์ด</a></p>";
    echo "</body></html>";
}
?>
