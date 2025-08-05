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

// ตรวจสอบสิทธิ์ Admin
$roleName = $_SESSION['role_name'] ?? '';
if (!in_array($roleName, ['admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit;
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