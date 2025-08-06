<?php
/**
 * Import/Export Entry Point
 * จุดเข้าถึงระบบนำเข้าและส่งออกข้อมูล
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
    // Log the request
    error_log("Import/Export Request - Action: " . $action . ", Method: " . $_SERVER['REQUEST_METHOD']);
    
    switch ($action) {
        case 'index':
            // For HTML pages, clear buffer and don't set JSON content type
            ob_end_clean();
            $controller->index();
            break;
            
        case 'importCustomers':
            // Clear any previous output
            ob_end_clean();
            // Set JSON content type for API calls
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            $controller->importCustomers();
            break;
            
        case 'importSales':
            // Clear any previous output
            ob_end_clean();
            // Set JSON content type for API calls
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            $controller->importSales();
            break;
            
        case 'importCustomersOnly':
            // Clear any previous output
            ob_end_clean();
            // Set JSON content type for API calls
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            $controller->importCustomersOnly();
            break;
            
        case 'exportCustomers':
            ob_end_clean();
            $controller->exportCustomers();
            break;
            
        case 'exportOrders':
            ob_end_clean();
            $controller->exportOrders();
            break;
            
        case 'exportSummaryReport':
            ob_end_clean();
            $controller->exportSummaryReport();
            break;
            
        case 'createBackup':
            // Clear any previous output
            ob_end_clean();
            // Set JSON content type for API calls
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            $controller->createBackup();
            break;
            
        case 'restoreBackup':
            // Clear any previous output
            ob_end_clean();
            // Set JSON content type for API calls
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            $controller->restoreBackup();
            break;
            
        case 'downloadTemplate':
            ob_end_clean();
            $controller->downloadTemplate();
            break;
            
        default:
            // Clear any previous output
            ob_end_clean();
            error_log("Unknown action: " . $action);
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Action not found']);
            break;
    }
} catch (Exception $e) {
    // Clear any previous output
    ob_end_clean();
    
    // Log the error for debugging
    error_log("Import/Export Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Set proper error response
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์: ' . $e->getMessage(),
        'success' => 0,
        'total' => 0,
        'customers_updated' => 0,
        'customers_created' => 0,
        'orders_created' => 0,
        'errors' => [$e->getMessage()]
    ]);
} 