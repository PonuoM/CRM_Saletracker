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
    error_log("Import/Export: No user_id in session, redirecting to login");
    header('Location: login.php');
    exit;
}

// ตรวจสอบ session ที่จำเป็น
if (!isset($_SESSION['role_id']) || !isset($_SESSION['role_name'])) {
    error_log("Import/Export: Missing role_id or role_name in session");
    header('Location: login.php');
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึง import/export
$roleId = $_SESSION['role_id'] ?? 0;
$roleName = $_SESSION['role_name'] ?? '';

// Debug log สำหรับตรวจสอบ session data
error_log("Import/Export Access Check - User ID: " . ($_SESSION['user_id'] ?? 'null') . 
          ", Role ID: " . $roleId . 
          ", Role Name: " . $roleName . 
          ", Session ID: " . session_id() .
          ", Request Method: " . $_SERVER['REQUEST_METHOD'] .
          ", Action: " . ($_GET['action'] ?? 'index'));

// ป้องกัน telesales (role_id = 4) เข้าถึง import/export
if ($roleId == 4) {
    error_log("Access denied: Telesales (role_id = 4) attempted to access import/export");
    header('Location: dashboard.php');
    exit;
}

// อนุญาต role_id = 1 (super_admin), 2 (admin), 6
if (!in_array($roleId, [1, 2, 6])) {
    error_log("Access denied: User with role_id = " . $roleId . " and role_name = " . $roleName . " attempted to access import/export");
    header('Location: dashboard.php');
    exit;
}

error_log("Access granted: User with role_id = " . $roleId . " and role_name = " . $roleName . " accessing import/export");

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
            // Enforce permission
            $controller->requireImportPermission();
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
            // Enforce permission
            $controller->requireImportPermission();
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
            // Enforce permission
            $controller->requireImportPermission();
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
            // Clear any previous output
            ob_end_clean();
            $controller->downloadTemplate();
            break;

        case 'importCallLogs':
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            $controller->requireImportPermission();
            $controller->importCallLogs();
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