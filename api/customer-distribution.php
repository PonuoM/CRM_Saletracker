<?php
/**
 * Customer Distribution API
 * จัดการ API calls สำหรับระบบแจกลูกค้าตามคำขอ
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    error_log("customer-distribution API: Action: {$action}, User: {$_SESSION['user_id']}, Role: {$_SESSION['role_name']}");

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

        case 'company_stats':
            // ดึงสถิติของบริษัทเฉพาะ
            $company = $_GET['company'] ?? '';
            if (!$company) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุบริษัท']);
                exit;
            }
            error_log("customer-distribution API: Getting company stats for: {$company}");
            $stats = $distributionService->getCompanyStats($company);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'telesales_by_company':
            // ดึงรายการ Telesales ตามบริษัท
            $company = $_GET['company'] ?? '';
            if (!$company) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุบริษัท']);
                exit;
            }
            error_log("customer-distribution API: Getting telesales for company: {$company}");
            $telesales = $distributionService->getTelesalesByCompany($company);
            echo json_encode(['success' => true, 'data' => $telesales]);
            break;

        case 'available_customers_by_date':
            // ดึงจำนวนลูกค้าพร้อมแจกตามวันที่
            $company = $_GET['company'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';

            if (!$company || !$dateFrom || !$dateTo) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            error_log("customer-distribution API: Getting available customers for company: {$company}, dateFrom: {$dateFrom}, dateTo: {$dateTo}");
            $result = $distributionService->getAvailableCustomersByDate($company, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'check_quota':
            // ตรวจสอบโควต้าของ Telesales
            $company = $_GET['company'] ?? '';
            $telesalesId = $_GET['telesales_id'] ?? '';

            if (!$company || !$telesalesId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $quota = $distributionService->checkTelesalesQuota($company, $telesalesId);
            echo json_encode(['success' => true, 'data' => $quota]);
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

        case 'distribute_average':
            // แจกลูกค้าแบบเฉลี่ย
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['company']) || !isset($input['customer_count']) || !isset($input['telesales_ids'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            // Validate inputs to avoid server errors (e.g., division by zero when no telesales selected)
            $company = trim((string)($input['company'] ?? ''));
            $customerCount = (int)($input['customer_count'] ?? 0);
            $telesalesIds = $input['telesales_ids'];
            if ($customerCount <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุจำนวนลูกค้าที่ต้องการแจก']);
                exit;
            }
            if (!is_array($telesalesIds) || count($telesalesIds) === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาเลือก Telesales อย่างน้อย 1 คน']);
                exit;
            }

            $result = $distributionService->distributeAverage(
                $company,
                $customerCount,
                $input['date_from'] ?? null,
                $input['date_to'] ?? null,
                $telesalesIds,
                $_SESSION['user_id']
            );

            echo json_encode($result);

            // If success, log a lightweight debug file for last distribution
            if (isset($result['success']) && $result['success'] === true) {
                $debug = [
                    'at' => date('Y-m-d H:i:s'),
                    'action' => 'distribute_average',
                    'company' => $company,
                    'customer_count' => $customerCount,
                    'telesales_ids' => $telesalesIds
                ];
                @file_put_contents(__DIR__ . '/../cron.log', "[DIST-AVG] " . json_encode($debug, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            }
            break;

        case 'distribute_request':
            // แจกลูกค้าตามคำขอ
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['company']) || !isset($input['quantity']) || !isset($input['telesales_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $result = $distributionService->distributeRequest(
                $input['company'],
                $input['quantity'],
                $input['priority'] ?? 'hot_warm_cold',
                $input['telesales_id'],
                $_SESSION['user_id']
            );

            echo json_encode($result);

            // If success, log a lightweight debug file for last distribution
            if (isset($result['success']) && $result['success'] === true) {
                $debug = [
                    'at' => date('Y-m-d H:i:s'),
                    'action' => 'distribute_request',
                    'company' => $input['company'],
                    'quantity' => $input['quantity'],
                    'priority' => $input['priority'] ?? 'hot_warm_cold',
                    'telesales_id' => $input['telesales_id']
                ];
                @file_put_contents(__DIR__ . '/../cron.log', "[DIST-REQ] " . json_encode($debug, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            }
            break;

        case 'grade_a_stats':
            // ดึงสถิติลูกค้าเกรด A
            $company = $_GET['company'] ?? '';
            error_log("customer-distribution API: Getting Grade A stats for company: {$company}");
            $result = $distributionService->getGradeAStats($company);
            echo json_encode($result);
            break;

        case 'distribute_grade_a':
            // แจกลูกค้าเกรด A
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                exit;
            }

            $result = $distributionService->distributeGradeA(
                $input['company'] ?? null,
                $input['allocations'] ?? [],
                $input['selected_grades'] ?? ['A'] // ค่าเริ่มต้นเป็น A เท่านั้น
            );

            echo json_encode($result);

            // Log Grade A distribution for debugging
            if (isset($result['success']) && $result['success'] === true) {
                $debug = [
                    'at' => date('Y-m-d H:i:s'),
                    'action' => 'distribute_grade_a',
                    'company' => $input['company'],
                    'allocations_count' => count($input['allocations'] ?? []),
                    'total_customers' => array_sum(array_column($input['allocations'] ?? [], 'count'))
                ];
                @file_put_contents(__DIR__ . '/../cron.log', "[DIST-GRADE-A] " . json_encode($debug, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    error_log("customer-distribution API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}