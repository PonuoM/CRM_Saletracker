<?php
/**
 * Order Management System
 * จัดการ routing สำหรับระบบจัดการคำสั่งซื้อ
 */

// Enable error reporting for debugging (remove in production)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    // Include configuration first
    require_once __DIR__ . '/config/config.php';

    session_start();

    // ตรวจสอบการยืนยันตัวตน
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // Include required files
    require_once APP_ROOT . '/app/controllers/OrderController.php';

    // สร้าง instance ของ OrderController
    $orderController = new OrderController();

    // รับ action จาก URL
    $action = $_GET['action'] ?? 'index';

    // จัดการ routing ตาม action
    switch ($action) {
        case 'index':
            $orderController->index();
            break;
            
        case 'show':
            $orderId = $_GET['id'] ?? 0;
            if ($orderId) {
                $orderController->show($orderId);
            } else {
                header('Location: orders.php');
                exit;
            }
            break;
            
        case 'edit':
            $orderId = $_GET['id'] ?? 0;
            if ($orderId) {
                $orderController->edit($orderId);
            } else {
                header('Location: orders.php');
                exit;
            }
            break;
            
        case 'update':
            $orderController->update();
            break;
            
        case 'create':
            $orderController->create();
            break;
            
        case 'store':
            // Enable debug mode for store method
            if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
            }
            $orderController->store();
            break;
            
        case 'update_status':
            $orderController->updateStatus();
            break;
            
        case 'export':
            $orderController->export();
            break;
            
        case 'get_products':
            $orderController->getProducts();
            break;
            
        case 'delete':
            $orderId = $_GET['id'] ?? 0;
            if ($orderId) {
                $orderController->delete($orderId);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่พบ ID คำสั่งซื้อ']);
            }
            break;
            
        default:
            $orderController->index();
            break;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Order Management Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Show error page or redirect
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<h1>Order Management System Error</h1>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>Trace:</strong></p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        // Redirect to error page or show generic error
        header('Location: error.php?message=order_system_error');
        exit;
    }
}
?> 