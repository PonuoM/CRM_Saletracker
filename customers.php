<?php
/**
 * Customer Management Entry Point
 * จัดการการเรียกใช้ CustomerController
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
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
    
    // Basic profile edit page for customer info (name/phone/address)
    case 'edit_basic':
        if ($customerId) {
            $controller->editBasic($customerId);
        } else {
            header('Location: customers.php');
            exit;
        }
        break;

    // Handle update basic info (AJAX JSON)
    case 'update_basic':
        $controller->updateBasic();
        break;
        
    default:
        $controller->index();
        break;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
}
?> 