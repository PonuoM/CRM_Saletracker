<?php
/**
 * Appointments API
 * API สำหรับจัดการข้อมูลนัดหมาย
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/AppointmentService.php';

// เริ่ม session
session_start();

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาเข้าสู่ระบบ'
    ]);
    exit;
}

$appointmentService = new AppointmentService();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            handleCreateAppointment($appointmentService);
            break;
            
        case 'update':
            handleUpdateAppointment($appointmentService);
            break;
            
        case 'delete':
            handleDeleteAppointment($appointmentService);
            break;
            
        case 'get_by_id':
            handleGetAppointmentById($appointmentService);
            break;
            
        case 'get_by_customer':
            handleGetAppointmentsByCustomer($appointmentService);
            break;
            
        case 'get_by_user':
            handleGetAppointmentsByUser($appointmentService);
            break;
            
        case 'get_upcoming':
            handleGetUpcomingAppointments($appointmentService);
            break;
            
        case 'update_status':
            handleUpdateAppointmentStatus($appointmentService);
            break;
            
        case 'get_activities':
            handleGetAppointmentActivities($appointmentService);
            break;
            
        case 'send_reminder':
            handleSendAppointmentReminder($appointmentService);
            break;
            
        case 'get_stats':
            handleGetAppointmentStats($appointmentService);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบ action ที่ระบุ'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

/**
 * สร้างนัดหมายใหม่
 */
function handleCreateAppointment($appointmentService) {
    // Log the raw input for debugging
    $rawInput = file_get_contents('php://input');
    error_log("Appointment creation - Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    // Log JSON decode result
    error_log("Appointment creation - JSON decode result: " . print_r($input, true));
    
    if (!$input) {
        $jsonError = json_last_error_msg();
        error_log("Appointment creation - JSON decode error: " . $jsonError);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง: ' . $jsonError
        ]);
        return;
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    $required = ['customer_id', 'appointment_date', 'appointment_type'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "กรุณากรอกข้อมูล: $field"
            ]);
            return;
        }
    }
    
    // เพิ่ม user_id จาก session
    $input['user_id'] = $_SESSION['user_id'];
    
    $result = $appointmentService->createAppointment($input);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * อัปเดตนัดหมาย
 */
function handleUpdateAppointment($appointmentService) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['appointment_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ appointment_id'
        ]);
        return;
    }
    
    $input['user_id'] = $_SESSION['user_id'];
    $result = $appointmentService->updateAppointment($input['appointment_id'], $input);
    
    if (!$result['success']) {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ลบนัดหมาย
 */
function handleDeleteAppointment($appointmentService) {
    $appointmentId = $_GET['id'] ?? null;
    
    if (!$appointmentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ appointment_id'
        ]);
        return;
    }
    
    $result = $appointmentService->deleteAppointment($appointmentId, $_SESSION['user_id']);
    
    if (!$result['success']) {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงข้อมูลนัดหมายตาม ID
 */
function handleGetAppointmentById($appointmentService) {
    $appointmentId = $_GET['id'] ?? null;
    
    if (!$appointmentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ appointment_id'
        ]);
        return;
    }
    
    $result = $appointmentService->getAppointmentById($appointmentId);
    
    if (!$result['success']) {
        http_response_code(404);
    }
    
    echo json_encode($result);
}

/**
 * ดึงรายการนัดหมายของลูกค้า
 */
function handleGetAppointmentsByCustomer($appointmentService) {
    $customerId = $_GET['customer_id'] ?? null;
    $limit = $_GET['limit'] ?? 10;
    
    if (!$customerId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ customer_id'
        ]);
        return;
    }
    
    $result = $appointmentService->getAppointmentsByCustomer($customerId, $limit);
    echo json_encode($result);
}

/**
 * ดึงรายการนัดหมายของผู้ใช้
 */
function handleGetAppointmentsByUser($appointmentService) {
    $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 20;
    
    $result = $appointmentService->getAppointmentsByUser($userId, $status, $limit);
    echo json_encode($result);
}

/**
 * ดึงรายการนัดหมายที่ใกล้ถึงกำหนด
 */
function handleGetUpcomingAppointments($appointmentService) {
    $userId = $_GET['user_id'] ?? null;
    $days = $_GET['days'] ?? 7;
    
    $result = $appointmentService->getUpcomingAppointments($userId, $days);
    echo json_encode($result);
}

/**
 * อัปเดตสถานะนัดหมาย
 */
function handleUpdateAppointmentStatus($appointmentService) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['appointment_id']) || empty($input['status'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ appointment_id และ status'
        ]);
        return;
    }
    
    $result = $appointmentService->updateAppointmentStatus(
        $input['appointment_id'], 
        $input['status'], 
        $_SESSION['user_id']
    );
    
    if (!$result['success']) {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงประวัติกิจกรรมนัดหมาย
 */
function handleGetAppointmentActivities($appointmentService) {
    $appointmentId = $_GET['appointment_id'] ?? null;
    
    if (!$appointmentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ appointment_id'
        ]);
        return;
    }
    
    $result = $appointmentService->getAppointmentActivities($appointmentId);
    echo json_encode($result);
}

/**
 * ส่งการแจ้งเตือนนัดหมาย
 */
function handleSendAppointmentReminder($appointmentService) {
    $appointmentId = $_GET['appointment_id'] ?? null;
    
    if (!$appointmentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ appointment_id'
        ]);
        return;
    }
    
    $result = $appointmentService->sendAppointmentReminder($appointmentId);
    
    if (!$result['success']) {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงสถิตินัดหมาย
 */
function handleGetAppointmentStats($appointmentService) {
    $userId = $_GET['user_id'] ?? null;
    $period = $_GET['period'] ?? 'month';
    
    $result = $appointmentService->getAppointmentStats($userId, $period);
    echo json_encode($result);
}
?> 