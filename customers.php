<?php
/**
 * Customer Management Entry Point
 * จัดการการเรียกใช้ CustomerController
 */

session_start();

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/controllers/CustomerController.php';

// Initialize controller
$controller = new CustomerController();

// Get action from query string
$action = $_GET['action'] ?? 'index';
$customerId = $_GET['id'] ?? null;

// Route to appropriate method
switch ($action) {
    case 'show':
        if ($customerId) {
            $controller->show($customerId);
        } else {
            header('Location: customers.php');
            exit;
        }
        break;
        
    case 'get_customer_address':
        $controller->getCustomerAddress();
        break;
        
    default:
        $controller->index();
        break;
}
?> 