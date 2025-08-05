<?php
/**
 * Customer Distribution API
 * จัดการ API calls สำหรับระบบแจกลูกค้าตามคำขอ
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/CustomerDistributionService.php';

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

// ตรวจสอบการยืนยันตัวตน
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ตรวจสอบสิทธิ์ (เฉพาะ Admin และ Supervisor)
$allowedRoles = ['admin', 'supervisor', 'super_admin'];
if (!in_array($_SESSION['role_name'] ?? '', $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Insufficient permissions']);
    exit;
}

try {
    $distributionService = new CustomerDistributionService();

    // Get action from query string
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'stats':
            // ดึงสถิติการแจกลูกค้า
            $stats = $distributionService->getDistributionStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'available_telesales':
            // ดึงรายการ Telesales ที่พร้อมรับงาน
            $telesales = $distributionService->getAvailableTelesales();
            echo json_encode(['success' => true, 'data' => $telesales]);
            break;

        case 'available_customers':
            // ดึงรายการลูกค้าที่พร้อมแจก
            $priority = $_GET['priority'] ?? 'hot_warm_cold';
            $limit = $_GET['limit'] ?? 10;
            $customers = $distributionService->getAvailableCustomers($priority, $limit);
            echo json_encode(['success' => true, 'data' => $customers]);
            break;

        case 'distribute':
            // แจกลูกค้าตามคำขอ
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['quantity']) || !isset($input['priority']) || !isset($input['telesales_ids'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $result = $distributionService->distributeCustomers(
                $input['quantity'],
                $input['priority'],
                $input['telesales_ids'],
                $_SESSION['user_id']
            );

            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    error_log("Customer Distribution API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 