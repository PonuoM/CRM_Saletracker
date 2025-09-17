<?php
/**
 * Customer Transfer API
 * API สำหรับจัดการการโอนย้ายลูกค้าระหว่างพนักงานขาย
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include required files
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/CustomerTransferService.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Security: Check if user is logged in and has proper role
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาเข้าสู่ระบบก่อน'
    ]);
    exit();
}

// Check user role (admin or supervisor only)
$allowedRoles = ['admin', 'super_admin', 'supervisor'];
if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], $allowedRoles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'คุณไม่มีสิทธิ์ในการเข้าถึงฟังก์ชันนี้'
    ]);
    exit();
}

try {
    // Get request method and action first
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    // Debug logging
    error_log("CustomerTransfer API called - Action: " . $action);
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("User Role: " . ($_SESSION['role_name'] ?? 'not set'));
    
    // Initialize database and service
    $database = new Database();
    $db = $database->getConnection();
    error_log("CustomerTransfer API - Database connected");
    
    $transferService = new CustomerTransferService($db);
    error_log("CustomerTransfer API - Service created");

    switch ($method) {
        case 'GET':
            handleGetRequest($action, $transferService);
            break;
        
        case 'POST':
            handlePostRequest($action, $transferService);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Customer Transfer API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์',
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($action, $transferService) {
    switch ($action) {
        case 'telesales_list':
            getTelesalesList($transferService);
            break;
            
        case 'telesales_stats':
            getTelesalesStats($transferService);
            break;
            
        case 'customer_list':
            getCustomerList($transferService);
            break;
            
        case 'search_customers':
            searchCustomers($transferService);
            break;
            
        case 'transfer_history':
            getTransferHistory($transferService);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action parameter'
            ]);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action, $transferService) {
    switch ($action) {
        case 'transfer_customers':
            transferCustomers($transferService);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action parameter'
            ]);
            break;
    }
}

/**
 * Get list of telesales users
 */
function getTelesalesList($transferService) {
    try {
        $companyId = null;
        
        // For supervisors, get their company
        if ($_SESSION['role_name'] === 'supervisor') {
            $companyId = $_SESSION['company_id'] ?? null;
        }
        
        $telesales = $transferService->getTelesalesList($companyId);
        
        echo json_encode([
            'success' => true,
            'data' => $telesales,
            'message' => 'ดึงข้อมูลพนักงานขายสำเร็จ'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get telesales statistics
 */
function getTelesalesStats($transferService) {
    try {
        $telesalesId = $_GET['telesales_id'] ?? null;
        
        if (!$telesalesId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุ ID พนักงานขาย'
            ]);
            return;
        }
        
        $stats = $transferService->getTelesalesStats($telesalesId);
        
        echo json_encode([
            'success' => true,
            'data' => $stats,
            'message' => 'ดึงสถิติพนักงานขายสำเร็จ'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get customer list for specific telesales
 */
function getCustomerList($transferService) {
    try {
        $telesalesId = $_GET['telesales_id'] ?? null;
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $grade = $_GET['grade'] ?? '';
        $status = $_GET['status'] ?? '';
        
        if (!$telesalesId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุ ID พนักงานขาย'
            ]);
            return;
        }
        
        $result = $transferService->getCustomerList($telesalesId, $page, $limit, $search, $grade, $status);
        
        echo json_encode([
            'success' => true,
            'data' => $result['customers'],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $result['total_pages'],
                'total_records' => $result['total_records'],
                'per_page' => $limit
            ],
            'message' => 'ดึงข้อมูลลูกค้าสำเร็จ'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Search customers for transfer
 */
function searchCustomers($transferService) {
    try {
        $sourceTelesalesId = $_GET['source_telesales_id'] ?? null;
        $searchTerm = $_GET['search'] ?? '';
        $grade = $_GET['grade'] ?? '';
        $limit = intval($_GET['limit'] ?? 20);
        
        if (!$sourceTelesalesId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุ ID พนักงานขายต้นทาง'
            ]);
            return;
        }
        
        if (strlen($searchTerm) < 3) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาพิมพ์อย่างน้อย 3 ตัวอักษรเพื่อค้นหา'
            ]);
            return;
        }
        
        $result = $transferService->searchCustomers($sourceTelesalesId, $searchTerm, $grade, $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'ค้นหาลูกค้าสำเร็จ'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get transfer history
 */
function getTransferHistory($transferService) {
    try {
        $limit = intval($_GET['limit'] ?? 50);
        $companyId = null;
        
        // For supervisors, get their company
        if ($_SESSION['role_name'] === 'supervisor') {
            $companyId = $_SESSION['company_id'] ?? null;
        }
        
        $history = $transferService->getTransferHistory($limit, $companyId);
        
        echo json_encode([
            'success' => true,
            'data' => $history,
            'message' => 'ดึงประวัติการโอนสำเร็จ'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Transfer customers between telesales
 */
function transferCustomers($transferService) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง'
            ]);
            return;
        }
        
        $sourceTelesalesId = $input['source_telesales_id'] ?? null;
        $targetTelesalesId = $input['target_telesales_id'] ?? null;
        $customerIds = $input['customer_ids'] ?? [];
        $reason = trim($input['reason'] ?? '');
        
        // Validation
        if (!$sourceTelesalesId || !$targetTelesalesId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาเลือกพนักงานต้นทางและปลายทาง'
            ]);
            return;
        }
        
        if ($sourceTelesalesId == $targetTelesalesId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่สามารถโอนให้ตัวเองได้'
            ]);
            return;
        }
        
        if (empty($customerIds) || !is_array($customerIds)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาเลือกลูกค้าที่ต้องการโอน'
            ]);
            return;
        }
        
        if (strlen($reason) < 10) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุเหตุผลในการโอนอย่างน้อย 10 ตัวอักษร'
            ]);
            return;
        }
        
        // Perform transfer with new logic
        $result = performCustomerTransfer(
            $sourceTelesalesId,
            $targetTelesalesId,
            $customerIds,
            $reason,
            $_SESSION['user_id']
        );
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => "โอนย้ายลูกค้า {$result['transferred_count']} คน สำเร็จ"
        ]);
        
    } catch (Exception $e) {
        // Log the error
        error_log("Transfer error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการโอนย้าย: ' . $e->getMessage()
        ]);
    }
}

/**
 * Utility function to validate user permissions for specific company
 */
function validateUserAccess($telesalesId) {
    // For supervisors, ensure telesales belongs to their company
    if ($_SESSION['role_name'] === 'supervisor') {
        // Check if telesales belongs to supervisor's company
        // This will be implemented in the service layer
        return true; // Placeholder
    }
    
    // Admins and super_admins have full access
    return true;
}

/**
 * Log transfer activity for audit trail
 */
function logTransferActivity($action, $details) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'Unknown',
        'role' => $_SESSION['role_name'],
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
    
    error_log("Customer Transfer Log: " . json_encode($logData));
}

/**
 * Perform customer transfer with correct status logic
 */
function performCustomerTransfer($sourceTelesalesId, $targetTelesalesId, $customerIds, $reason, $transferredBy) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $database->beginTransaction();
        
        $transferredCount = 0;
        $errors = [];
        
        foreach ($customerIds as $customerId) {
            try {
                // ตรวจสอบว่าลูกค้าอยู่กับ source telesales จริงหรือไม่
                $customer = $database->fetchOne(
                    "SELECT customer_id, assigned_to, customer_status, first_name, last_name 
                     FROM customers 
                     WHERE customer_id = ? AND assigned_to = ?",
                    [$customerId, $sourceTelesalesId]
                );
                
                if (!$customer) {
                    $errors[] = "ลูกค้า ID {$customerId} ไม่ได้อยู่กับพนักงานขายต้นทาง";
                    continue;
                }
                
                // ตรวจสอบว่าพนักงานปลายทางเคยขายให้ลูกค้านี้หรือไม่
                $hasRecentSales = checkRecentSales($database, $customerId, $targetTelesalesId);
                
                // กำหนดสถานะลูกค้าใหม่
                $newStatus = determineNewCustomerStatus($database, $customerId, $targetTelesalesId, $hasRecentSales);
                
                // อัปเดตข้อมูลลูกค้า
                $database->execute(
                    "UPDATE customers 
                     SET assigned_to = ?, 
                         assigned_at = NOW(), 
                         customer_status = ?,
                         updated_at = NOW()
                     WHERE customer_id = ?",
                    [$targetTelesalesId, $newStatus, $customerId]
                );
                
                // บันทึกประวัติการโอนย้าย (ถ้าตารางมีอยู่)
                logTransfer($database, $customerId, $sourceTelesalesId, $targetTelesalesId, $reason, $transferredBy, $newStatus);
                
                $transferredCount++;
                
                error_log("Customer Transfer - Customer {$customerId}: {$customer['customer_status']} → {$newStatus} (hasRecentSales: " . ($hasRecentSales ? 'true' : 'false') . ")");
                
            } catch (Exception $e) {
                $errors[] = "เกิดข้อผิดพลาดในการโอนลูกค้า ID {$customerId}: " . $e->getMessage();
                error_log("Customer Transfer Error - Customer {$customerId}: " . $e->getMessage());
            }
        }
        
        $database->commit();
        
        return [
            'success' => true,
            'transferred_count' => $transferredCount,
            'errors' => $errors,
            'message' => "โอนย้ายลูกค้า {$transferredCount} คนสำเร็จ"
        ];
        
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }
}

/**
 * ตรวจสอบว่าพนักงานเคยขายให้ลูกค้าในช่วง 3 เดือนล่าสุดหรือไม่
 */
function checkRecentSales($database, $customerId, $telesalesId) {
    try {
        $result = $database->fetchOne(
            "SELECT COUNT(*) as sales_count
             FROM orders 
             WHERE customer_id = ? 
               AND created_by = ? 
               AND payment_status IN ('paid', 'partial')
               AND order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            [$customerId, $telesalesId]
        );
        
        return ($result['sales_count'] ?? 0) > 0;
        
    } catch (Exception $e) {
        error_log("Error checking recent sales: " . $e->getMessage());
        return false;
    }
}

/**
 * กำหนดสถานะลูกค้าใหม่ตามหลักการที่ถูกต้อง
 */
function determineNewCustomerStatus($database, $customerId, $telesalesId, $hasRecentSales) {
    try {
        // ถ้าพนักงานเคยขายให้ลูกค้าในช่วง 3 เดือนล่าสุด = existing_3m
        if ($hasRecentSales) {
            return 'existing_3m';
        }
        
        // ถ้าไม่เคยขาย = ตรวจสอบว่าลูกค้ามีประวัติการขายหรือไม่
        $hasAnySales = $database->fetchOne(
            "SELECT COUNT(*) as sales_count
             FROM orders 
             WHERE customer_id = ? 
               AND payment_status IN ('paid', 'partial')
               AND order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            [$customerId]
        );
        
        if (($hasAnySales['sales_count'] ?? 0) > 0) {
            // มีการขายในช่วง 3 เดือน แต่ไม่ใช่พนักงานคนนี้ = existing
            return 'existing';
        } else {
            // ไม่มีการขายในช่วง 3 เดือน = new
            return 'new';
        }
        
    } catch (Exception $e) {
        error_log("Error determining customer status: " . $e->getMessage());
        return 'new'; // default to new if error
    }
}

/**
 * บันทึกประวัติการโอนย้าย (ถ้าตารางมีอยู่)
 */
function logTransfer($database, $customerId, $sourceTelesalesId, $targetTelesalesId, $reason, $transferredBy, $newStatus) {
    try {
        // ตรวจสอบว่าตาราง customer_transfers มีอยู่หรือไม่
        $tableExists = $database->fetchOne("SHOW TABLES LIKE 'customer_transfers'");
        
        if ($tableExists) {
            $database->execute(
                "INSERT INTO customer_transfers (
                    customer_id, 
                    source_telesales_id, 
                    target_telesales_id, 
                    reason, 
                    transferred_by, 
                    new_status,
                    transferred_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$customerId, $sourceTelesalesId, $targetTelesalesId, $reason, $transferredBy, $newStatus]
            );
        }
    } catch (Exception $e) {
        error_log("Error logging transfer: " . $e->getMessage());
    }
}
?>
