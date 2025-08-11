<?php
/**
 * Call Management API Endpoint
 * จัดการ API calls สำหรับ Call Management
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

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
    $db = new Database();
    
    // Get action from query string
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_stats':
            getCallStats($db);
            break;
            
        case 'get_followup_customers':
            getFollowupCustomers($db);
            break;
            
        case 'log_call':
            logCall($db);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
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
 * Get call statistics
 */
function getCallStats($db) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Get total calls for the user
        $totalCalls = $db->fetchOne(
            "SELECT COUNT(*) as count FROM call_logs WHERE user_id = ?",
            [$userId]
        );
        
        // Get answered calls
        $answeredCalls = $db->fetchOne(
            "SELECT COUNT(*) as count FROM call_logs WHERE user_id = ? AND call_status = 'answered'",
            [$userId]
        );
        
        // Get calls that need follow-up (ใช้เงื่อนไขเดียวกับ getFollowupCustomers)
        $needFollowup = $db->fetchOne(
            "SELECT COUNT(*) as count FROM call_logs WHERE user_id = ? AND next_followup_at IS NOT NULL AND call_result IN ('not_interested', 'callback', 'interested', 'complaint')",
            [$userId]
        );
        
        // Get overdue follow-ups (ใช้เงื่อนไขเดียวกับ getFollowupCustomers)
        $overdueFollowup = $db->fetchOne(
            "SELECT COUNT(*) as count FROM call_logs WHERE user_id = ? AND next_followup_at IS NOT NULL AND next_followup_at <= NOW() AND call_result IN ('not_interested', 'callback', 'interested', 'complaint')",
            [$userId]
        );
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_calls' => $totalCalls['count'] ?? 0,
                'answered_calls' => $answeredCalls['count'] ?? 0,
                'need_followup' => $needFollowup['count'] ?? 0,
                'overdue_followup' => $overdueFollowup['count'] ?? 0
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Get follow-up customers
 */
function getFollowupCustomers($db) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $roleName = $_SESSION['role_name'] ?? '';
        
        // ใช้ customer_call_followup_list view ถ้ามีอยู่
        try {
            $sql = "SELECT * FROM customer_call_followup_list WHERE 1=1";
            $params = [];
            
            // กรองตาม user role
            if ($roleName === 'telesales' || $roleName === 'supervisor') {
                $sql .= " AND assigned_to = ?";
                $params[] = $userId;
            }
            
            // กรองตาม urgency status
            $urgency = $_GET['urgency'] ?? '';
            if ($urgency) {
                switch ($urgency) {
                    case 'overdue':
                        $sql .= " AND urgency_status = 'overdue'";
                        break;
                    case 'urgent':
                        $sql .= " AND urgency_status = 'urgent'";
                        break;
                    case 'soon':
                        $sql .= " AND urgency_status = 'soon'";
                        break;
                }
            }
            
            // กรองตาม call result
            $callResult = $_GET['call_result'] ?? '';
            if ($callResult) {
                $sql .= " AND call_result = ?";
                $params[] = $callResult;
            }
            
            // กรองตาม priority
            $priority = $_GET['priority'] ?? '';
            if ($priority) {
                $sql .= " AND followup_priority = ?";
                $params[] = $priority;
            }
            
            $sql .= " ORDER BY next_followup_at ASC, followup_priority DESC LIMIT 50";
            
            $customers = $db->fetchAll($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $customers
            ]);
            
        } catch (Exception $e) {
            // ถ้า view ไม่มีอยู่ ใช้ query แบบเดิมแต่ปรับปรุง
            $sql = "SELECT 
                        c.customer_id,
                        c.customer_code,
                        c.first_name,
                        c.last_name,
                        c.phone,
                        c.email,
                        c.province,
                        c.temperature_status,
                        c.customer_grade,
                        u.full_name as assigned_to_name,
                        cl.call_result,
                        cl.call_status,
                        cl.created_at as last_call_date,
                        cl.next_followup_at,
                        cl.notes,
                        cl.followup_priority,
                        cfq.status as queue_status,
                        DATEDIFF(cl.next_followup_at, NOW()) as days_until_followup,
                        CASE 
                            WHEN cl.next_followup_at <= NOW() THEN 'overdue'
                            WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'urgent'
                            WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'soon'
                            ELSE 'normal'
                        END as urgency_status
                    FROM call_logs cl
                    JOIN customers c ON cl.customer_id = c.customer_id
                    LEFT JOIN users u ON c.assigned_to = u.user_id
                    LEFT JOIN call_followup_queue cfq ON c.customer_id = cfq.customer_id AND cfq.status = 'pending'
                    WHERE cl.next_followup_at IS NOT NULL
                    AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')";
            
            $params = [];
            
            // กรองตาม user role
            if ($roleName === 'telesales' || $roleName === 'supervisor') {
                $sql .= " AND c.assigned_to = ?";
                $params[] = $userId;
            }
            
            // กรองตาม urgency status
            $urgency = $_GET['urgency'] ?? '';
            if ($urgency) {
                switch ($urgency) {
                    case 'overdue':
                        $sql .= " AND cl.next_followup_at <= NOW()";
                        break;
                    case 'urgent':
                        $sql .= " AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) AND cl.next_followup_at > NOW()";
                        break;
                    case 'soon':
                        $sql .= " AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND cl.next_followup_at > DATE_ADD(NOW(), INTERVAL 3 DAY)";
                        break;
                }
            }
            
            // กรองตาม call result
            $callResult = $_GET['call_result'] ?? '';
            if ($callResult) {
                $sql .= " AND cl.call_result = ?";
                $params[] = $callResult;
            }
            
            // กรองตาม priority
            $priority = $_GET['priority'] ?? '';
            if ($priority) {
                $sql .= " AND cl.followup_priority = ?";
                $params[] = $priority;
            }
            
            $sql .= " ORDER BY cl.next_followup_at ASC, cl.followup_priority DESC LIMIT 50";
            
            $customers = $db->fetchAll($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $customers
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Log a call
 */
function logCall($db) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $data = [
            'customer_id' => $input['customer_id'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'call_type' => $input['call_type'] ?? 'outbound',
            'call_status' => $input['call_status'] ?? null,
            'call_result' => $input['call_result'] ?? null,
            'duration_minutes' => $input['duration_minutes'] ?? 0,
            'notes' => $input['notes'] ?? null,
            'next_action' => $input['next_action'] ?? null
        ];
        
        // Validate required fields
        if (!$data['customer_id'] || !$data['call_status'] || !$data['call_result']) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        // Insert call log
        $callData = [
            'customer_id' => $data['customer_id'],
            'user_id' => $data['user_id'],
            'call_type' => $data['call_type'],
            'call_status' => $data['call_status'],
            'call_result' => $data['call_result'],
            'duration_minutes' => $data['duration_minutes'],
            'notes' => $data['notes'],
            'next_action' => $data['next_action'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $callLogId = $db->insert('call_logs', $callData);
        
        // Update customer's last_contact_at
        $db->execute(
            "UPDATE customers SET last_contact_at = NOW() WHERE customer_id = ?",
            [$data['customer_id']]
        );
        
        echo json_encode([
            'success' => true,
            'call_log_id' => $callLogId,
            'message' => 'บันทึกการโทรสำเร็จ'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
