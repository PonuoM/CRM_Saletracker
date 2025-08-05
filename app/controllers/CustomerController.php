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
            case 'supervisor':
            case 'admin':
            case 'super_admin':
                // Supervisor/Admin เห็นลูกค้าทั้งหมด
                $customers = $this->customerService->getCustomersByBasket('distribution');
                break;
                
            case 'telesales':
                // Telesales เห็นเฉพาะลูกค้าที่ได้รับมอบหมาย
                $customers = $this->customerService->getCustomersByBasket('assigned', ['assigned_to' => $userId]);
                $followUpCustomers = $this->customerService->getFollowUpCustomers($userId);
                break;
        }
        
        // ดึงข้อมูล Telesales สำหรับ Supervisor
        $telesalesList = [];
        if (in_array($roleName, ['supervisor', 'admin', 'super_admin'])) {
            $telesalesList = $this->db->fetchAll(
                "SELECT user_id, full_name FROM users WHERE role_id = 4 AND is_active = 1 ORDER BY full_name"
            );
        }
        
        // ดึงข้อมูลจังหวัด
        $provinces = $this->db->fetchAll(
            "SELECT DISTINCT province FROM customers WHERE province IS NOT NULL AND province != '' ORDER BY province"
        );
        
        include APP_VIEWS . 'customers/index.php';
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
        if ($roleName === 'telesales' && $customer['assigned_to'] != $userId) {
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
        
        // ดึงกิจกรรมลูกค้า
        $activities = $this->db->fetchAll(
            "SELECT ca.*, u.full_name as user_name 
             FROM customer_activities ca 
             LEFT JOIN users u ON ca.user_id = u.user_id 
             WHERE ca.customer_id = :customer_id 
             ORDER BY ca.created_at DESC",
            ['customer_id' => $customerId]
        );
        
        // ดึงคำสั่งซื้อ
        $orders = $this->db->fetchAll(
            "SELECT o.*, COUNT(oi.item_id) as item_count 
             FROM orders o 
             LEFT JOIN order_items oi ON o.order_id = oi.order_id 
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
        
        // สำหรับ Telesales เห็นเฉพาะลูกค้าที่ได้รับมอบหมาย
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'telesales') {
            $filters['assigned_to'] = $_SESSION['user_id'];
        }
        
        $customers = $this->customerService->getCustomersByBasket($basketType, $filters);
        
        echo json_encode([
            'success' => true,
            'data' => $customers
        ]);
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
     * แสดงหน้าข้อผิดพลาด
     */
    private function showError($title, $message) {
        include APP_VIEWS . 'errors/error.php';
    }
}
?> 