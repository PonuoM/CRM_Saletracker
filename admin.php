<?php
/**
 * Admin Entry Point
 * จัดการการเรียกใช้ AdminController
 */

// เพิ่ม error reporting (เฉพาะการ debug)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// โหลด configuration
require_once __DIR__ . '/config/config.php';

session_start();

// ตรวจสอบการยืนยันตัวตน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ตรวจสอบสิทธิ์ Admin (รวม supervisor และ company_admin สำหรับฟีเจอร์บางอย่าง)
$roleName = $_SESSION['role_name'] ?? '';
$roleId = $_SESSION['role_id'] ?? 0;
$action = $_GET['action'] ?? 'index';

// ป้องกัน telesales (role_id = 4) เข้าถึง admin pages
if ($roleId == 4) {
    header('Location: dashboard.php');
    exit;
}

// Customer transfer อนุญาตให้ supervisor เข้าได้
if ($action === 'customer_transfer') {
    if (!in_array($roleName, ['admin', 'company_admin', 'super_admin', 'supervisor']) && !in_array($roleId, [1, 6])) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    // ฟีเจอร์อื่น ๆ ต้องเป็น admin, company_admin, super_admin หรือ role ID 1, 6
    if (!in_array($roleName, ['admin', 'company_admin', 'super_admin']) && !in_array($roleId, [1, 6])) {
        header('Location: dashboard.php');
        exit;
    }
}

require_once __DIR__ . '/app/controllers/AdminController.php';

$adminController = new AdminController();

// รับ action จาก URL
$action = $_GET['action'] ?? 'index';

// จัดการ routing
switch ($action) {
    case 'users':
        $adminController->users();
        break;
    case 'products':
        $adminController->products();
        break;
    case 'settings':
        $adminController->settings();
        break;
    case 'companies':
        $adminController->companies();
        break;
    case 'workflow':
        $adminController->workflow();
        break;
    case 'customer_distribution':
        $adminController->customer_distribution();
        break;
    default:
        $adminController->index();
        break;
}
?> 