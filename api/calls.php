<?php
/**
 * Call Management API Endpoint
 * จัดการ API calls สำหรับ Call Management
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
// Disable caching to ensure latest filter logic is applied immediately
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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
        
        // Get calls that need follow-up (ชุดใหม่)
        $followupSet = "('interested','add_line','buy_on_page','callback','appointment','not_convenient','flood')";
        $needFollowup = $db->fetchOne(
            "SELECT COUNT(*) as count FROM call_logs WHERE user_id = ? AND (next_followup_at IS NOT NULL OR call_result IN $followupSet)",
            [$userId]
        );
        
        // Get overdue follow-ups (นับเฉพาะมีนัดติดตามและถึงเวลา/เกินเวลา)
        $overdueFollowup = $db->fetchOne(
            "SELECT COUNT(*) as count FROM call_logs WHERE user_id = ? AND next_followup_at IS NOT NULL AND next_followup_at <= NOW()",
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

        // ใช้ unified query เสมอ เพื่อหลีกเลี่ยง view เก่าที่ไม่อัปเดต
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
                WHERE 1=1";

        $params = [];

        if ($roleName === 'telesales' || $roleName === 'supervisor') {
            $sql .= " AND c.assigned_to = ?";
            $params[] = $userId;
        }

        $filter = $_GET['filter'] ?? 'all';
        if ($filter === 'answered') {
            $sql .= " AND cl.call_status = 'answered'";
        } elseif ($filter === 'need_followup') {
            $sql .= " AND (cl.next_followup_at IS NOT NULL OR cl.call_result IN ('interested','add_line','buy_on_page','callback','appointment','not_convenient','flood'))";
        } elseif ($filter === 'dnc') {
            $sql .= " AND cl.call_result IN ('do_not_call','invalid_number','unable_to_contact')";
        }
        if ($filter === 'all') {
            $sql .= " AND (cl.next_followup_at IS NOT NULL OR cl.call_result IN ('interested','add_line','buy_on_page','callback','appointment','not_convenient','flood'))";
        }

        $callResult = $_GET['call_result'] ?? '';
        if ($callResult) {
            $sql .= " AND cl.call_result = ?";
            $params[] = $callResult;
        }

        $priority = $_GET['priority'] ?? '';
        if ($priority) {
            $sql .= " AND cl.followup_priority = ?";
            $params[] = $priority;
        }

        $sql .= " ORDER BY cl.next_followup_at ASC, cl.followup_priority DESC LIMIT 50";

        $customers = $db->fetchAll($sql, $params);

        echo json_encode(['success' => true, 'data' => $customers]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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

        // Map Thai labels to internal codes (status/result)
        $statusMap = [
            'รับสาย' => 'answered',
            'ไม่รับสาย' => 'no_answer',
            'สายไม่ว่าง' => 'busy',
            'ตัดสายทิ้ง' => 'hangup',
            'ติดต่อไม่ได้' => 'unable_to_contact',
        ];
        $resultMap = [
            'สั่งซื้อ' => 'order',
            'สนใจ' => 'interested',
            'Add Line แล้ว' => 'add_line',
            'ต้องการซื้อทางเพจ' => 'buy_on_page',
            'น้ำท่วม' => 'flood',
            'รอติดต่อใหม่' => 'callback',
            'นัดหมาย' => 'appointment',
            'เบอร์ไม่ถูก' => 'invalid_number',
            'ไม่สะดวกคุย' => 'not_convenient',
            'ไม่สนใจ' => 'not_interested',
            'อย่าโทรมาอีก' => 'do_not_call',
        ];

        $callStatus = $input['call_status'] ?? null;
        $callResult = $input['call_result'] ?? null;
        // Do not force-normalize result; allow full set based on selection
        if (isset($statusMap[$callStatus])) $callStatus = $statusMap[$callStatus];
        if (isset($resultMap[$callResult])) $callResult = $resultMap[$callResult];

        $data = [
            'customer_id' => $input['customer_id'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'call_type' => $input['call_type'] ?? 'outbound',
            'call_status' => $callStatus,
            'call_result' => $callResult,
            'duration_minutes' => $input['duration_minutes'] ?? ($input['duration'] ?? 0),
            'notes' => $input['notes'] ?? null,
            'next_action' => $input['next_action'] ?? null,
            'next_followup_at' => $input['next_followup'] ?? ($input['next_followup_at'] ?? null),
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
            'next_followup_at' => $data['next_followup_at'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $callLogId = $db->insert('call_logs', $callData);
        
        // Update customer's last_contact_at, next_followup_at, and extend time_expiry with 90-day cap
        if ($data['next_followup_at']) {
            // ถ้ามีการนัดติดตาม = เพิ่มเวลา 30 วัน แต่ไม่เกิน 90 วัน
            $db->execute(
                "UPDATE customers SET 
                    last_contact_at = NOW(), 
                    next_followup_at = ?,
                    customer_time_expiry = LEAST(DATE_ADD(customer_time_expiry, INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 90 DAY))
                WHERE customer_id = ?",
                [$data['next_followup_at'], $data['customer_id']]
            );
            
            // 🔄 SYNC: สร้าง appointment อัตโนมัติเมื่อมี next_followup_at
            createAppointmentFromCall($data['customer_id'], $data['next_followup_at'], $data['notes'], $callLogId);
            
        } else {
            $db->execute(
                "UPDATE customers SET last_contact_at = NOW() WHERE customer_id = ?",
                [$data['customer_id']]
            );
        }
        
        // Clear follow-up for call results that indicate customer interaction is complete
        $clearFollowupResults = ['ไม่สนใจ', 'เบอร์ผิด'];
        if (in_array($data['call_result'], $clearFollowupResults)) {
            try {
                // ล้าง next_followup_at ในตาราง customers
                $db->execute(
                    "UPDATE customers SET next_followup_at = NULL WHERE customer_id = ?",
                    [$data['customer_id']]
                );
                
                // ล้าง next_followup_at ใน call_logs ที่ยังค้างอยู่
                $db->execute(
                    "UPDATE call_logs SET next_followup_at = NULL 
                     WHERE customer_id = ? AND next_followup_at IS NOT NULL",
                    [$data['customer_id']]
                );
            } catch (Exception $e) { /* ignore */ }
        }
        
        // Handle customer status changes based on call result
        try {
            $cust = $db->fetchOne("SELECT customer_status FROM customers WHERE customer_id = ?", [$data['customer_id']]);
            
            // If first activity for NEW customer, move to followup (except for final results)
            if (($cust['customer_status'] ?? '') === 'new' && !in_array($data['call_result'], $clearFollowupResults)) {
                $db->execute("UPDATE customers SET customer_status = 'followup' WHERE customer_id = ?", [$data['customer_id']]);
            }
            
            // For NEW customers with final call results, mark as existing to remove from Do tab
            if (($cust['customer_status'] ?? '') === 'new' && in_array($data['call_result'], ['ไม่สนใจ', 'เบอร์ผิด'])) {
                $db->execute("UPDATE customers SET customer_status = 'existing' WHERE customer_id = ?", [$data['customer_id']]);
            }
        } catch (Exception $e) { /* ignore */ }

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

/**
 * สร้าง appointment อัตโนมัติจากการบันทึกการโทร
 */
function createAppointmentFromCall($customerId, $appointmentDateTime, $notes, $callLogId) {
    global $db;
    
    try {
        // ตรวจสอบว่ามี appointment ที่วันเวลาเดียวกันแล้วหรือไม่
        $existingAppointment = $db->fetchOne(
            "SELECT appointment_id FROM appointments 
             WHERE customer_id = ? AND appointment_date = ? AND appointment_status != 'cancelled'",
            [$customerId, $appointmentDateTime]
        );
        
        if ($existingAppointment) {
            // มี appointment อยู่แล้ว ไม่ต้องสร้างใหม่
            return $existingAppointment['appointment_id'];
        }
        
        // สร้าง appointment ใหม่
        $appointmentData = [
            'customer_id' => $customerId,
            'user_id' => $_SESSION['user_id'] ?? 1,
            'appointment_date' => $appointmentDateTime,
            'appointment_type' => 'follow_up_call', // ประเภทนัดหมายจากการโทร
            'appointment_status' => 'scheduled',
            'description' => $notes ? "ติดตาม: {$notes}" : 'ติดตามจากการบันทึกการโทร',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $appointmentId = $db->insert('appointments', $appointmentData);
        
        // Log กิจกรรม
        $activityData = [
            'customer_id' => $customerId,
            'user_id' => $_SESSION['user_id'] ?? 1,
            'activity_type' => 'appointment_created',
            'description' => "สร้างนัดหมายจากการบันทึกการโทร: " . date('d/m/Y H:i', strtotime($appointmentDateTime)),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('activity_logs', $activityData);
        
        return $appointmentId;
        
    } catch (Exception $e) {
        error_log("Error creating appointment from call: " . $e->getMessage());
        return false;
    }
}
?>
