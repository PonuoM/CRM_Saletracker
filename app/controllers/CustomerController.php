<?php
/**
 * CustomerController Class
 * จัดการการเรียกใช้ CustomerService และหน้า Customer Management
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/CustomerService.php';

class CustomerController {
    private $db;
    private $auth;
    private $customerService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth($this->db);
        $this->customerService = new CustomerService();
    }
    
    /**
     * แสดงหน้าจัดการลูกค้าหลัก
     */
    public function index() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ดึงข้อมูลตามบทบาท
        $customers = [];
        $followUpCustomers = [];
        
        switch ($roleName) {
            case 'admin':
            case 'super_admin':
                // Admin เห็นลูกค้าทั้งหมด
                $customers = $this->customerService->getCustomersByBasket('distribution');
                break;

            case 'supervisor':
            case 'telesales':
                // Supervisor และ Telesales เห็นเฉพาะลูกค้าที่ได้รับมอบหมายให้ตัวเอง
                $customers = $this->customerService->getCustomersByBasket('assigned', ['assigned_to' => $userId]);
                $followUpCustomers = $this->customerService->getFollowUpCustomers($userId);
                break;
        }
        
        // ดึงข้อมูล Telesales สำหรับ Supervisor
        $telesalesList = [];
        if (in_array($roleName, ['admin', 'super_admin'])) {
            $telesalesList = $this->db->fetchAll(
                "SELECT user_id, full_name FROM users WHERE role_id = 4 AND is_active = 1 ORDER BY full_name"
            );
        } elseif ($roleName === 'supervisor') {
            // Supervisor เห็นเฉพาะ Telesales ในทีมตัวเอง
            $telesalesList = $this->db->fetchAll(
                "SELECT user_id, full_name FROM users WHERE supervisor_id = :supervisor_id AND role_id = 4 AND is_active = 1 ORDER BY full_name",
                ['supervisor_id' => $userId]
            );
        }
        
        // ดึงข้อมูลจังหวัด
        $provinces = $this->db->fetchAll(
            "SELECT DISTINCT province FROM customers WHERE province IS NOT NULL AND province != '' ORDER BY province"
        );
        
        // ใช้ layout ที่ถูกต้อง
        $pageTitle = 'จัดการลูกค้า - CRM SalesTracker';
        $bodyClass = 'customer-page-body';
        
        // เริ่ม output buffering
        ob_start();
        include APP_VIEWS . 'customers/index.php';
        $content = ob_get_clean();
        
        // ใช้ layout หลัก
        include APP_VIEWS . 'layouts/main.php';
    }
    
    /**
     * แสดงหน้ารายละเอียดลูกค้า
     */
    public function show($customerId) {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ดึงข้อมูลลูกค้า
        $customer = $this->db->fetchOne(
            "SELECT c.*, u.full_name as assigned_to_name 
             FROM customers c 
             LEFT JOIN users u ON c.assigned_to = u.user_id 
             WHERE c.customer_id = :customer_id",
            ['customer_id' => $customerId]
        );
        
        if (!$customer) {
            $this->showError('ไม่พบลูกค้า', 'ลูกค้าที่คุณค้นหาไม่มีอยู่ในระบบ');
            return;
        }
        
        // ตรวจสอบสิทธิ์การเข้าถึง
        if (($roleName === 'telesales' || $roleName === 'supervisor') && $customer['assigned_to'] != $userId) {
            $this->showError('ไม่มีสิทธิ์เข้าถึง', 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลลูกค้ารายนี้');
            return;
        }
        
        // ดึงประวัติการโทร
        $callLogs = $this->db->fetchAll(
            "SELECT cl.*, u.full_name as user_name 
             FROM call_logs cl 
             LEFT JOIN users u ON cl.user_id = u.user_id 
             WHERE cl.customer_id = :customer_id 
             ORDER BY cl.created_at DESC",
            ['customer_id' => $customerId]
        );
        
        // ดึงกิจกรรมลูกค้าแบบรวม (จากหลายแหล่ง)
        $activities = $this->getCombinedCustomerActivities($customerId);
        
        // ดึงคำสั่งซื้อ
        $orders = $this->db->fetchAll(
            "SELECT o.*, COUNT(oi.item_id) as item_count, u.full_name as salesperson_name 
             FROM orders o 
             LEFT JOIN order_items oi ON o.order_id = oi.order_id 
             LEFT JOIN users u ON o.created_by = u.user_id 
             WHERE o.customer_id = :customer_id 
             GROUP BY o.order_id 
             ORDER BY o.order_date DESC",
            ['customer_id' => $customerId]
        );
        
        // ส่งข้อมูลไปยังหน้า show.php
        $customerData = $customer;
        $orderData = $orders;
        // $callLogs และ $activities ถูกกำหนดไว้แล้วข้างบน
        
        // ใช้ layout ที่ถูกต้อง
        $pageTitle = 'รายละเอียดลูกค้า - CRM SalesTracker';
        
        // เริ่ม output buffering
        ob_start();
        include APP_VIEWS . 'customers/show.php';
        $content = ob_get_clean();
        
        // ใช้ layout หลัก
        include APP_VIEWS . 'layouts/main.php';
    }
    
    /**
     * API สำหรับมอบหมายลูกค้า
     */
    public function assignCustomers() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['supervisor', 'admin', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // สำหรับ supervisor ตรวจสอบว่า telesales อยู่ในทีมตัวเองหรือไม่
        if ($roleName === 'supervisor') {
            $teamMemberIds = $this->getTeamCustomerIds($_SESSION['user_id']);
            if (!in_array($input['telesales_id'] ?? null, $teamMemberIds)) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied - Telesales not in your team']);
                return;
            }
        }
        
        $supervisorId = $_SESSION['user_id'];
        $telesalesId = $input['telesales_id'] ?? null;
        $customerIds = $input['customer_ids'] ?? [];
        
        if (!$telesalesId || empty($customerIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        $result = $this->customerService->assignCustomers($supervisorId, $telesalesId, $customerIds);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }
    
    /**
     * API สำหรับดึงลูกค้ากลับ
     */
    public function recallCustomer() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $customerId = $input['customer_id'] ?? null;
        $reason = $input['reason'] ?? '';
        $userId = $_SESSION['user_id'];
        
        if (!$customerId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing customer_id']);
            return;
        }
        
        $result = $this->customerService->recallCustomer($customerId, $reason, $userId);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }
    
    /**
     * API สำหรับอัปเดตสถานะลูกค้า
     */
    public function updateStatus() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $customerId = $input['customer_id'] ?? null;
        $status = $input['status'] ?? '';
        $notes = $input['notes'] ?? '';
        $userId = $_SESSION['user_id'];
        
        if (!$customerId || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        $result = $this->customerService->updateCustomerStatus($customerId, $status, $notes, $userId);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }
    
    /**
     * API สำหรับบันทึกการโทร
     */
    public function logCall() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $customerId = $input['customer_id'] ?? null;
        $callType = $input['call_type'] ?? 'outbound';
        $callStatus = $input['call_status'] ?? '';
        $callResult = $input['call_result'] ?? null;
        $duration = $input['duration'] ?? 0;
        $notes = $input['notes'] ?? '';
        $nextAction = $input['next_action'] ?? '';
        $nextFollowup = $input['next_followup'] ?? null;
        
        $userId = $_SESSION['user_id'];
        
        if (!$customerId || !$callStatus) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        try {
            $data = [
                'customer_id' => $customerId,
                'user_id' => $userId,
                'call_type' => $callType,
                'call_status' => $callStatus,
                'call_result' => $callResult,
                'duration_minutes' => $duration,
                'notes' => $notes,
                'next_action' => $nextAction,
                'next_followup_at' => $nextFollowup,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $callLogId = $this->db->insert('call_logs', $data);
            
            // อัปเดต last_contact_at ของลูกค้า
            $this->db->update('customers', 
                ['last_contact_at' => date('Y-m-d H:i:s')], 
                'customer_id = :customer_id', 
                ['customer_id' => $customerId]
            );
            
            // บันทึกกิจกรรม
            $this->logCustomerActivity($customerId, $userId, 'call', 
                "บันทึกการโทร: {$callStatus} - {$notes}");
            
            echo json_encode([
                'success' => true,
                'call_log_id' => $callLogId,
                'message' => 'บันทึกการโทรสำเร็จ'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API สำหรับดึงลูกค้าตามตะกร้า
     */
    public function getCustomersByBasket() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $basketType = $_GET['basket_type'] ?? 'distribution';
        $filters = [];
        
        // ตัวกรอง
        if (!empty($_GET['temperature'])) {
            $filters['temperature'] = $_GET['temperature'];
        }
        
        if (!empty($_GET['grade'])) {
            $filters['grade'] = $_GET['grade'];
        }
        
        if (!empty($_GET['province'])) {
            $filters['province'] = $_GET['province'];
        }
        
        if (!empty($_GET['name'])) {
            $filters['name'] = $_GET['name'];
        }
        
        if (!empty($_GET['phone'])) {
            $filters['phone'] = $_GET['phone'];
        }
        
        if (!empty($_GET['customer_status'])) {
            $filters['customer_status'] = $_GET['customer_status'];
        }
        
        // สำหรับ Telesales และ Supervisor เห็นเฉพาะลูกค้าที่ได้รับมอบหมายให้ตัวเอง
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'telesales' || $roleName === 'supervisor') {
            $filters['assigned_to'] = $_SESSION['user_id'];
        }
        
        $customers = $this->customerService->getCustomersByBasket($basketType, $filters);
        
        echo json_encode([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * API: ดึงรายการลูกค้าที่มีนัดติดตาม (แท็บ "ติดตาม")
     */
    public function getFollowups() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];

        // สำหรับ telesales ใช้ service โดยตรง (ตามผู้ใช้)
        if ($roleName === 'telesales') {
            $data = $this->customerService->getFollowUpCustomers($userId);
            echo json_encode(['success' => true, 'data' => $data]);
            return;
        }

        // สำหรับ supervisor/admin แสดงตามทีม/ทั้งหมด
        $params = [];
        $where = "c.is_active = 1 AND (c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR c.next_followup_at <= NOW())";

        if ($roleName === 'supervisor' || $roleName === 'telesales') {
            $where .= " AND c.assigned_to = ?";
            $params[] = $userId;
        }

        $sql = "SELECT c.*, u.full_name as assigned_to_name,
                        DATEDIFF(c.customer_time_expiry, NOW()) as days_remaining,
                        DATEDIFF(c.next_followup_at, NOW()) as followup_days,
                        CASE 
                            WHEN c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiry'
                            WHEN c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'appointment'
                            ELSE 'other'
                        END as reason_type
                FROM customers c
                LEFT JOIN users u ON c.assigned_to = u.user_id
                WHERE $where
                ORDER BY c.customer_time_expiry ASC, c.next_followup_at ASC";

        $data = $this->db->fetchAll($sql, $params);
        echo json_encode(['success' => true, 'data' => $data]);
    }
    
    /**
     * ดึงข้อมูลที่อยู่ลูกค้า (API)
     */
    public function getCustomerAddress() {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
                return;
            }
            
            $customerId = $_GET['id'] ?? null;
            if (!$customerId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่พบ ID ลูกค้า']);
                return;
            }
            
            $roleName = $_SESSION['role_name'] ?? '';
            $userId = $_SESSION['user_id'];
            
            // ดึงข้อมูลลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT customer_id, first_name, last_name, address, province, phone, email 
                 FROM customers 
                 WHERE customer_id = :customer_id",
                ['customer_id' => $customerId]
            );
            
            if (!$customer) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลลูกค้า']);
                return;
            }
            
            // ตรวจสอบสิทธิ์การเข้าถึง
            if ($roleName === 'telesales') {
                $assignedCustomer = $this->db->fetchOne(
                    "SELECT customer_id FROM customers WHERE customer_id = :customer_id AND assigned_to = :user_id",
                    ['customer_id' => $customerId, 'user_id' => $userId]
                );
                
                if (!$assignedCustomer) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลลูกค้ารายนี้']);
                    return;
                }
            } elseif ($roleName === 'supervisor') {
                // Supervisor ตรวจสอบว่าลูกค้าได้รับมอบหมายให้ตัวเองหรือไม่
                $assignedCustomer = $this->db->fetchOne(
                    "SELECT customer_id FROM customers WHERE customer_id = ? AND assigned_to = ?",
                    [$customerId, $userId]
                );

                if (!$assignedCustomer) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลลูกค้ารายนี้']);
                    return;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'customer' => $customer
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * บันทึกกิจกรรมลูกค้า
     */
    private function logCustomerActivity($customerId, $userId, $activityType, $description) {
        try {
            $data = [
                'customer_id' => $customerId,
                'user_id' => $userId,
                'activity_type' => $activityType,
                'activity_description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('customer_activities', $data);
            return true;
        } catch (Exception $e) {
            error_log('Error logging customer activity: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ดึง user_id ของสมาชิกทีมทั้งหมด
     */
    private function getTeamCustomerIds($supervisorId) {
        $teamMembers = $this->db->fetchAll(
            "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
            ['supervisor_id' => $supervisorId]
        );
        
        $teamCustomerIds = [];
        foreach ($teamMembers as $member) {
            $teamCustomerIds[] = $member['user_id'];
        }
        
        return $teamCustomerIds;
    }
    
    /**
     * รวมกิจกรรมลูกค้าจากหลายแหล่งข้อมูล
     */
    private function getCombinedCustomerActivities($customerId) {
        $activities = [];

        try {
            // 1. ดึงข้อมูลการโทร (call_logs)
            $callLogs = $this->db->fetchAll(
                "SELECT
                    cl.log_id,
                    cl.call_status,
                    cl.call_result,
                    cl.notes,
                    cl.created_at,
                    u.full_name as user_name,
                    'call' as activity_type
                FROM call_logs cl
                LEFT JOIN users u ON cl.user_id = u.user_id
                WHERE cl.customer_id = :customer_id",
                ['customer_id' => $customerId]
            );

            foreach ($callLogs as $log) {
                $statusText = $this->getCallStatusText($log['call_status']);
                $resultText = $this->getCallResultText($log['call_result']);

                $description = "โทรหาลูกค้า ({$statusText})";
                if ($resultText) {
                    $description .= " - {$resultText}";
                }
                if ($log['notes']) {
                    $description .= " หมายเหตุ: " . $log['notes'];
                }

                $activities[] = [
                    'id' => 'call_' . $log['log_id'],
                    'activity_type' => 'call',
                    'activity_description' => $description,
                    'user_name' => $log['user_name'],
                    'created_at' => $log['created_at'],
                    'icon' => 'fas fa-phone',
                    'color' => $log['call_status'] === 'answered' ? 'success' : 'warning'
                ];
            }

            // 2. ดึงข้อมูลการนัดหมาย (appointments)
            $appointments = $this->db->fetchAll(
                "SELECT
                    a.appointment_id,
                    a.appointment_date,
                    a.appointment_type,
                    a.appointment_status,
                    a.notes,
                    a.created_at,
                    u.full_name as user_name,
                    'appointment' as activity_type
                FROM appointments a
                LEFT JOIN users u ON a.user_id = u.user_id
                WHERE a.customer_id = :customer_id",
                ['customer_id' => $customerId]
            );

            foreach ($appointments as $appointment) {
                $statusText = $this->getAppointmentStatusText($appointment['appointment_status']);
                $typeText = $this->getAppointmentTypeText($appointment['appointment_type']);

                $description = "นัดหมาย{$typeText} ({$statusText}) วันที่ " .
                              date('d/m/Y H:i', strtotime($appointment['appointment_date']));

                if ($appointment['notes']) {
                    $description .= " หมายเหตุ: " . $appointment['notes'];
                }

                $activities[] = [
                    'id' => 'appointment_' . $appointment['appointment_id'],
                    'activity_type' => 'appointment',
                    'activity_description' => $description,
                    'user_name' => $appointment['user_name'],
                    'created_at' => $appointment['created_at'],
                    'icon' => 'fas fa-calendar',
                    'color' => $appointment['appointment_status'] === 'completed' ? 'success' : 'info'
                ];
            }

            // 3. ดึงข้อมูลคำสั่งซื้อ (orders)
            $orders = $this->db->fetchAll(
                "SELECT
                    o.order_id,
                    o.order_number,
                    o.net_amount,
                    o.payment_status,
                    o.created_at,
                    u.full_name as user_name,
                    'order' as activity_type
                FROM orders o
                LEFT JOIN users u ON o.created_by = u.user_id
                WHERE o.customer_id = :customer_id",
                ['customer_id' => $customerId]
            );

            foreach ($orders as $order) {
                $statusText = $this->getPaymentStatusText($order['payment_status']);
                $amount = number_format($order['net_amount'], 2);

                $description = "สร้างคำสั่งซื้อใหม่ หมายเลข {$order['order_number']} " .
                              "ยอดสุทธิ ฿{$amount} ({$statusText})";

                $activities[] = [
                    'id' => 'order_' . $order['order_id'],
                    'activity_type' => 'order',
                    'activity_description' => $description,
                    'user_name' => $order['user_name'],
                    'created_at' => $order['created_at'],
                    'icon' => 'fas fa-shopping-cart',
                    'color' => $order['payment_status'] === 'paid' ? 'success' : 'warning'
                ];
            }

            // 4. ดึงข้อมูลกิจกรรมอื่นๆ (customer_activities)
            $customerActivities = $this->db->fetchAll(
                "SELECT
                    ca.activity_id,
                    ca.activity_type,
                    ca.activity_description,
                    ca.created_at,
                    u.full_name as user_name
                FROM customer_activities ca
                LEFT JOIN users u ON ca.user_id = u.user_id
                WHERE ca.customer_id = :customer_id",
                ['customer_id' => $customerId]
            );

            foreach ($customerActivities as $activity) {
                $activities[] = [
                    'id' => 'activity_' . $activity['activity_id'],
                    'activity_type' => $activity['activity_type'],
                    'activity_description' => $activity['activity_description'],
                    'user_name' => $activity['user_name'],
                    'created_at' => $activity['created_at'],
                    'icon' => $this->getActivityIcon($activity['activity_type']),
                    'color' => $this->getActivityColor($activity['activity_type'])
                ];
            }

        } catch (Exception $e) {
            // ถ้ามีข้อผิดพลาด ให้ส่งกลับ array ว่าง
            error_log("Error fetching combined activities: " . $e->getMessage());
            return [];
        }

        // เรียงลำดับตามเวลา (ใหม่สุดก่อน)
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $activities;
    }

    /**
     * แปลงสถานะการโทรเป็นข้อความภาษาไทย
     */
    private function getCallStatusText($status) {
        switch ($status) {
            case 'answered': return 'รับสาย';
            case 'no_answer': return 'ไม่รับสาย';
            case 'busy': return 'สายไม่ว่าง';
            case 'invalid': return 'เบอร์ไม่ถูกต้อง';
            default: return 'ไม่ระบุ';
        }
    }

    /**
     * แปลงผลการโทรเป็นข้อความภาษาไทย
     */
    private function getCallResultText($result) {
        if (!$result) return '';
        switch ($result) {
            case 'interested': return 'สนใจ';
            case 'not_interested': return 'ไม่สนใจ';
            case 'callback': return 'โทรกลับ';
            case 'order': return 'สั่งซื้อ';
            case 'complaint': return 'ร้องเรียน';
            default: return $result;
        }
    }

    /**
     * แปลงสถานะการนัดหมายเป็นข้อความภาษาไทย
     */
    private function getAppointmentStatusText($status) {
        switch ($status) {
            case 'scheduled': return 'กำหนดการ';
            case 'confirmed': return 'ยืนยันแล้ว';
            case 'completed': return 'เสร็จสิ้น';
            case 'cancelled': return 'ยกเลิก';
            case 'no_show': return 'ไม่มาตามนัด';
            default: return 'ไม่ระบุ';
        }
    }

    /**
     * แปลงประเภทการนัดหมายเป็นข้อความภาษาไทย
     */
    private function getAppointmentTypeText($type) {
        switch ($type) {
            case 'call': return 'โทรศัพท์';
            case 'visit': return 'เยี่ยมชม';
            case 'meeting': return 'ประชุม';
            case 'demo': return 'สาธิต';
            default: return '';
        }
    }

    /**
     * แปลงสถานะการชำระเงินเป็นข้อความภาษาไทย
     */
    private function getPaymentStatusText($status) {
        switch ($status) {
            case 'paid': return 'ชำระแล้ว';
            case 'pending': return 'รอชำระ';
            case 'partial': return 'ชำระบางส่วน';
            case 'cancelled': return 'ยกเลิก';
            default: return 'ไม่ระบุ';
        }
    }

    /**
     * ดึงไอคอนตามประเภทกิจกรรม
     */
    private function getActivityIcon($activityType) {
        switch ($activityType) {
            case 'call': return 'fas fa-phone';
            case 'appointment': return 'fas fa-calendar';
            case 'order': return 'fas fa-shopping-cart';
            case 'status_change': return 'fas fa-exchange-alt';
            case 'assignment': return 'fas fa-user-plus';
            case 'note': return 'fas fa-sticky-note';
            case 'recall': return 'fas fa-undo';
            default: return 'fas fa-info-circle';
        }
    }

    /**
     * ดึงสีตามประเภทกิจกรรม
     */
    private function getActivityColor($activityType) {
        switch ($activityType) {
            case 'call': return 'primary';
            case 'appointment': return 'info';
            case 'order': return 'success';
            case 'status_change': return 'warning';
            case 'assignment': return 'secondary';
            case 'note': return 'light';
            case 'recall': return 'danger';
            default: return 'secondary';
        }
    }

    /**
     * คำนวณเวลาที่ผ่านมาเป็นภาษาไทย
     */
    private function getTimeAgo($datetime) {
        $time = time() - strtotime($datetime);

        if ($time < 60) {
            return 'เมื่อสักครู่';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' นาทีที่แล้ว';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' ชั่วโมงที่แล้ว';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' วันที่แล้ว';
        } elseif ($time < 31536000) {
            $months = floor($time / 2592000);
            return $months . ' เดือนที่แล้ว';
        } else {
            $years = floor($time / 31536000);
            return $years . ' ปีที่แล้ว';
        }
    }

    /**
     * แสดงหน้าข้อผิดพลาด
     */
    private function showError($title, $message) {
        include APP_VIEWS . 'errors/error.php';
    }

}
?> 