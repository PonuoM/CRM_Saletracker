<?php
/**
 * Tags API Endpoint
 * จัดการ Tags ของลูกค้า
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session
session_start();

// Load configuration
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Load controller
require_once '../app/controllers/CustomerController.php';

// Initialize controller
$controller = new CustomerController();

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any previous output
ob_end_clean();

// Route actions
$action = $_GET['action'] ?? '';

try {
    // Log the request
    error_log("Tags API Request - Action: " . $action . ", Method: " . $_SERVER['REQUEST_METHOD']);
    
    switch ($action) {
        case 'add':
            // เพิ่ม tag ให้ลูกค้า
            $controller->addTag();
            break;
            
        case 'remove':
            // ลบ tag ของลูกค้า
            $controller->removeTag();
            break;
            
        case 'get':
            // ดึง tags ของลูกค้า
            $controller->getCustomerTags();
            break;
            
        case 'user_tags':
            // ดึง tags ทั้งหมดที่ user เคยใช้
            $controller->getUserTags();
            break;
            
        case 'search':
            // ค้นหาลูกค้าตาม tags
            $controller->getCustomersByTags();
            break;
            
        case 'bulk_add':
            // เพิ่ม tags หลายอันพร้อมกัน
            $controller->bulkAddTags();
            break;
            
        case 'bulk_remove':
            // ลบ tags หลายอันพร้อมกัน
            $controller->bulkRemoveTags();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Tags API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
