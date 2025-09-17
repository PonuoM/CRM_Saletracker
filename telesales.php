<?php
/**
 * Telesales Management System
 * ระบบจัดการสำหรับ role = 5 (telesales)
 */

session_start();

// Load configuration
require_once 'config/config.php';
require_once 'app/controllers/TelesalesController.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check role
$roleName = $_SESSION['role_name'] ?? '';
if ($roleName !== 'telesales') {
    header('Location: dashboard.php');
    exit;
}

// Initialize controller
$telesalesController = new TelesalesController();

// Get action
$action = $_GET['action'] ?? 'dashboard';

// Route to appropriate method
switch ($action) {
    case 'products':
        $telesalesController->products();
        break;
        
    case 'users':
        $telesalesController->users();
        break;
        
    case 'import':
        $telesalesController->import();
        break;
        
    case 'distribution':
        $telesalesController->distribution();
        break;
        
    default:
        // Redirect to dashboard if no valid action
        header('Location: dashboard.php');
        exit;
}
?>
