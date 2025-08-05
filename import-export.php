<?php
/**
 * Import/Export Entry Point
 * จุดเข้าถึงระบบนำเข้าและส่งออกข้อมูล
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load controller
require_once 'app/controllers/ImportExportController.php';

// Initialize controller
$controller = new ImportExportController();

// Route actions
$action = $_GET['action'] ?? 'index';

try {
    switch ($action) {
        case 'index':
            $controller->index();
            break;
            
        case 'importCustomers':
            $controller->importCustomers();
            break;
            
        case 'exportCustomers':
            $controller->exportCustomers();
            break;
            
        case 'exportOrders':
            $controller->exportOrders();
            break;
            
        case 'exportSummaryReport':
            $controller->exportSummaryReport();
            break;
            
        case 'createBackup':
            $controller->createBackup();
            break;
            
        case 'restoreBackup':
            $controller->restoreBackup();
            break;
            
        case 'downloadTemplate':
            $controller->downloadTemplate();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
} 