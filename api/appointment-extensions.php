<?php
/**
 * Appointment Extensions API
 * API สำหรับจัดการระบบการต่อเวลาการนัดหมาย
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/services/AppointmentExtensionService.php';

// เริ่ม session
session_start();

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - กรุณาเข้าสู่ระบบ']);
    exit;
}

$auth = new Auth();
$extensionService = new AppointmentExtensionService();

try {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($action) {
        case 'get_customer_info':
            // GET /api/appointment-extensions.php?action=get_customer_info&customer_id=1
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $customerId = $_GET['customer_id'] ?? null;
            if (!$customerId) {
                throw new Exception('Customer ID is required');
            }
            
            $result = $extensionService->getCustomerExtensionInfo($customerId);
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลลูกค้า']);
            }
            break;
            
        case 'extend_from_appointment':
            // POST /api/appointment-extensions.php?action=extend_from_appointment
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $customerId = $input['customer_id'] ?? null;
            $appointmentId = $input['appointment_id'] ?? null;
            $extensionDays = $input['extension_days'] ?? null;
            
            if (!$customerId || !$appointmentId) {
                throw new Exception('Customer ID and Appointment ID are required');
            }
            
            $result = $extensionService->extendTimeFromAppointment(
                $customerId, 
                $appointmentId, 
                $_SESSION['user_id'], 
                $extensionDays
            );
            
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'reset_on_sale':
            // POST /api/appointment-extensions.php?action=reset_on_sale
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $customerId = $input['customer_id'] ?? null;
            $orderId = $input['order_id'] ?? null;
            
            if (!$customerId || !$orderId) {
                throw new Exception('Customer ID and Order ID are required');
            }
            
            $result = $extensionService->resetExtensionOnSale(
                $customerId, 
                $_SESSION['user_id'], 
                $orderId
            );
            
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'extend_manually':
            // POST /api/appointment-extensions.php?action=extend_manually
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $customerId = $input['customer_id'] ?? null;
            $extensionDays = $input['extension_days'] ?? null;
            $reason = $input['reason'] ?? '';
            
            if (!$customerId || !$extensionDays) {
                throw new Exception('Customer ID and Extension Days are required');
            }
            
            $result = $extensionService->extendTimeManually(
                $customerId, 
                $_SESSION['user_id'], 
                $extensionDays, 
                $reason
            );
            
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_extension_history':
            // GET /api/appointment-extensions.php?action=get_extension_history&customer_id=1&limit=10
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $customerId = $_GET['customer_id'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            if (!$customerId) {
                throw new Exception('Customer ID is required');
            }
            
            $result = $extensionService->getExtensionHistory($customerId, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_near_expiry':
            // GET /api/appointment-extensions.php?action=get_near_expiry&days=7&limit=50
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $days = $_GET['days'] ?? 7;
            $limit = $_GET['limit'] ?? 50;
            
            $result = $extensionService->getCustomersNearExpiry($days, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_expired':
            // GET /api/appointment-extensions.php?action=get_expired&limit=50
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $limit = $_GET['limit'] ?? 50;
            
            $result = $extensionService->getExpiredCustomers($limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_stats':
            // GET /api/appointment-extensions.php?action=get_stats
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $result = $extensionService->getExtensionStats();
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'ไม่สามารถดึงสถิติได้']);
            }
            break;
            
        case 'get_rules':
            // GET /api/appointment-extensions.php?action=get_rules
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $result = $extensionService->getExtensionRules();
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'update_rule':
            // POST /api/appointment-extensions.php?action=update_rule
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // ตรวจสอบสิทธิ์ (เฉพาะ Admin/Supervisor)
            if (!in_array($_SESSION['role_name'], ['admin', 'supervisor', 'super_admin'])) {
                throw new Exception('ไม่มีสิทธิ์ในการอัปเดตกฎ');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $ruleId = $input['rule_id'] ?? null;
            $ruleData = $input['rule_data'] ?? null;
            
            if (!$ruleId || !$ruleData) {
                throw new Exception('Rule ID and Rule Data are required');
            }
            
            $result = $extensionService->updateExtensionRule($ruleId, $ruleData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'อัปเดตกฎสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'error' => 'ไม่สามารถอัปเดตกฎได้']);
            }
            break;
            
        case 'can_extend':
            // GET /api/appointment-extensions.php?action=can_extend&customer_id=1
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $customerId = $_GET['customer_id'] ?? null;
            if (!$customerId) {
                throw new Exception('Customer ID is required');
            }
            
            $result = $extensionService->canExtendTime($customerId);
            echo json_encode(['success' => true, 'data' => ['can_extend' => $result]]);
            break;
            
        default:
            // ส่งคืนรายการ actions ที่ใช้ได้
            echo json_encode([
                'success' => true,
                'message' => 'Appointment Extensions API',
                'available_actions' => [
                    'get_customer_info' => 'GET - ดึงข้อมูลการต่อเวลาของลูกค้า',
                    'extend_from_appointment' => 'POST - ต่อเวลาจากการนัดหมาย',
                    'reset_on_sale' => 'POST - รีเซ็ตตัวนับเมื่อมีการขาย',
                    'extend_manually' => 'POST - ต่อเวลาด้วยตนเอง',
                    'get_extension_history' => 'GET - ดึงประวัติการต่อเวลา',
                    'get_near_expiry' => 'GET - ดึงรายการลูกค้าที่ใกล้หมดอายุ',
                    'get_expired' => 'GET - ดึงรายการลูกค้าที่หมดอายุแล้ว',
                    'get_stats' => 'GET - ดึงสถิติการต่อเวลา',
                    'get_rules' => 'GET - ดึงกฎการต่อเวลา',
                    'update_rule' => 'POST - อัปเดตกฎการต่อเวลา',
                    'can_extend' => 'GET - ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown'
    ]);
}
?> 