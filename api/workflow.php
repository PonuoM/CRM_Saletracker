<?php
/**
 * Workflow API Endpoint
 * จัดการ API calls สำหรับ Workflow Management
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/WorkflowService.php';

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

// Debug mode - check if debug parameter is set
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

if ($debug) {
    // Debug session information
    error_log("Workflow API Debug - Session data: " . print_r($_SESSION, true));
    error_log("Workflow API Debug - User ID: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("Workflow API Debug - Role: " . ($_SESSION['role_name'] ?? 'not set'));
}

// ตรวจสอบการยืนยันตัวตน
if (!isset($_SESSION['user_id'])) {
    if ($debug) {
        echo json_encode([
            'error' => 'Unauthorized',
            'debug' => [
                'session_id' => session_id(),
                'session_data' => $_SESSION,
                'message' => 'No user_id in session'
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Unauthorized']);
    }
    http_response_code(401);
    exit;
}

// ตรวจสอบสิทธิ์ (เฉพาะ Admin และ Supervisor)
$allowedRoles = ['admin', 'supervisor', 'super_admin'];
if (!in_array($_SESSION['role_name'] ?? '', $allowedRoles)) {
    if ($debug) {
        echo json_encode([
            'error' => 'Forbidden - Insufficient permissions',
            'debug' => [
                'user_role' => $_SESSION['role_name'] ?? 'not set',
                'allowed_roles' => $allowedRoles,
                'session_data' => $_SESSION
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Forbidden - Insufficient permissions']);
    }
    http_response_code(403);
    exit;
}

try {
    $workflowService = new WorkflowService();

    // Get action from query string
    $action = $_GET['action'] ?? '';

    if ($debug) {
        error_log("Workflow API Debug - Action: " . $action);
    }

    switch ($action) {
        case 'stats':
            // ดึงสถิติ Workflow
            $stats = $workflowService->getWorkflowStats();

            if ($debug) {
                error_log("Workflow API Debug - Stats result: " . print_r($stats, true));
                echo json_encode([
                    'success' => true,
                    'data' => $stats,
                    'debug' => [
                        'action' => $action,
                        'user_id' => $_SESSION['user_id'],
                        'role' => $_SESSION['role_name'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                echo json_encode(['success' => true, 'data' => $stats]);
            }
            break;
            
        case 'new_customer_timeout':
            // ดึงรายการลูกค้าใหม่ที่เกิน 30 วัน
            $limit = $_GET['limit'] ?? 10;
            $customers = $workflowService->getNewCustomerTimeout($limit);
            echo json_encode(['success' => true, 'data' => $customers]);
            break;
            
        case 'existing_customer_timeout':
            // ดึงรายการลูกค้าเก่าที่เกิน 90 วัน
            $limit = $_GET['limit'] ?? 10;
            $customers = $workflowService->getExistingCustomerTimeout($limit);
            echo json_encode(['success' => true, 'data' => $customers]);
            break;
            
        case 'recent_activities':
            // ดึงกิจกรรมล่าสุด
            $limit = $_GET['limit'] ?? 20;
            $activities = $workflowService->getRecentActivities($limit);
            echo json_encode(['success' => true, 'data' => $activities]);
            break;
            
        case 'run_recall':
            // รัน Manual Recall
            $result = $workflowService->runManualRecall();
            echo json_encode($result);
            break;
            
        case 'extend_time':
            // ต่อเวลาลูกค้า
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['customer_id']) || !isset($input['extension_days']) || !isset($input['reason'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }
            
            $result = $workflowService->extendCustomerTime(
                $input['customer_id'],
                $input['extension_days'],
                $input['reason'],
                $_SESSION['user_id']
            );
            
            echo json_encode($result);
            break;
            
        case 'auto_extend':
            // ต่อเวลาอัตโนมัติเมื่อมีการ Active
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['customer_id']) || !isset($input['activity_type'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }
            
            $result = $workflowService->autoExtendTimeOnActivity(
                $input['customer_id'],
                $input['activity_type'],
                $_SESSION['user_id']
            );
            
            echo json_encode($result);
            break;
            
        case 'customers_for_extension':
            // ดึงรายการลูกค้าที่พร้อมต่อเวลา
            $customers = $workflowService->getCustomersForExtension();
            echo json_encode(['success' => true, 'data' => $customers]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Workflow API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 