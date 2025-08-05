<?php
/**
 * Customer API Endpoint
 * จัดการ API calls สำหรับ Customer Management
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/CustomerController.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new CustomerController();
    
    // Get action from query string or request body
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'assign':
            $controller->assignCustomers();
            break;
            
        case 'recall':
            $controller->recallCustomer();
            break;
            
        case 'update_status':
            $controller->updateStatus();
            break;
            
        case 'log_call':
            $controller->logCall();
            break;
            
        case 'show':
            // Handle customer detail view (redirect to page)
            $customerId = $_GET['id'] ?? null;
            if ($customerId) {
                $controller->show($customerId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing customer ID']);
            }
            break;
            
        case 'export':
            // Handle export functionality
            handleExport($controller);
            break;
            
        default:
            // Default action: get customers by basket
            $controller->getCustomersByBasket();
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle export functionality
 */
function handleExport($controller) {
    // Set headers for file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Get basket type and filters
    $basketType = $_GET['basket_type'] ?? 'distribution';
    $filters = [];
    
    if (!empty($_GET['temperature'])) {
        $filters['temperature'] = $_GET['temperature'];
    }
    
    if (!empty($_GET['grade'])) {
        $filters['grade'] = $_GET['grade'];
    }
    
    if (!empty($_GET['province'])) {
        $filters['province'] = $_GET['province'];
    }
    
    // Get customers
    $customers = $controller->customerService->getCustomersByBasket($basketType, $filters);
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, [
        'ID',
        'รหัสลูกค้า',
        'ชื่อ',
        'นามสกุล',
        'เบอร์โทร',
        'อีเมล',
        'ที่อยู่',
        'อำเภอ',
        'จังหวัด',
        'รหัสไปรษณีย์',
        'สถานะ',
        'เกรด',
        'ยอดซื้อรวม',
        'ผู้รับมอบหมาย',
        'ประเภทตะกร้า',
        'วันที่ได้รับมอบหมาย',
        'วันที่ติดต่อล่าสุด',
        'วันที่ติดตามถัดไป',
        'วันที่ดึงกลับ',
        'แหล่งที่มา',
        'หมายเหตุ',
        'วันที่สร้าง',
        'วันที่อัปเดต'
    ]);
    
    // CSV data
    foreach ($customers as $customer) {
        fputcsv($output, [
            $customer['customer_id'],
            $customer['customer_code'],
            $customer['first_name'],
            $customer['last_name'],
            $customer['phone'],
            $customer['email'],
            $customer['address'],
            $customer['district'],
            $customer['province'],
            $customer['postal_code'],
            $customer['temperature_status'],
            $customer['customer_grade'],
            $customer['total_purchase_amount'],
            $customer['assigned_to_name'],
            $customer['basket_type'],
            $customer['assigned_at'],
            $customer['last_contact_at'],
            $customer['next_followup_at'],
            $customer['recall_at'],
            $customer['source'],
            $customer['notes'],
            $customer['created_at'],
            $customer['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
}
?> 