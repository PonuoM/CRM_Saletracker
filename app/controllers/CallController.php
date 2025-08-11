<?php
/**
 * Call Controller
 * จัดการการโทรติดตามลูกค้า
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../services/CallService.php';

class CallController {
    private $callService;
    private $auth;
    
    public function __construct() {
        $this->callService = new CallService();
        $this->auth = new Auth();
    }
    
    /**
     * หน้าหลักการจัดการการโทร
     */
    public function index() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $roleName = $_SESSION['role_name'];
        
        // ดึงข้อมูลลูกค้าที่ต้องติดตาม
        $followupCustomers = $this->callService->getCallFollowupCustomers($userId);
        
        // ดึงสถิติการโทร
        $callStats = $this->callService->getCallStats($userId, 'month');
        
        // ใช้ layout ที่ถูกต้อง
        $pageTitle = 'จัดการการโทรติดตาม - CRM SalesTracker';
        $bodyClass = 'call-page-body';
        
        // เริ่ม output buffering
        ob_start();
        
        // แสดงหน้า
        include APP_VIEWS . 'calls/index.php';
        
        $content = ob_get_clean();
        
        // ใช้ layout หลัก
        include APP_VIEWS . 'layouts/main.php';
    }
    
    /**
     * บันทึกการโทร (API)
     */
    public function logCall() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
            return;
        }
        
        // ตรวจสอบ method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Method ไม่ถูกต้อง']);
            return;
        }
        
        // รับข้อมูล
        $data = [
            'customer_id' => $_POST['customer_id'] ?? null,
            'user_id' => $_SESSION['user_id'],
            'call_type' => $_POST['call_type'] ?? 'outbound',
            'call_status' => $_POST['call_status'] ?? null,
            'call_result' => $_POST['call_result'] ?? null,
            'duration_minutes' => $_POST['duration_minutes'] ?? 0,
            'notes' => $_POST['notes'] ?? null,
            'next_action' => $_POST['next_action'] ?? null,
            'followup_notes' => $_POST['followup_notes'] ?? null
        ];
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (!$data['customer_id'] || !$data['call_status'] || !$data['call_result']) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            return;
        }
        
        // บันทึกการโทร
        $result = $this->callService->logCall($data);
        
        $this->sendJsonResponse($result);
    }
    
    /**
     * ดึงประวัติการโทรของลูกค้า (API)
     */
    public function getCallHistory() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
            return;
        }
        
        $customerId = $_GET['customer_id'] ?? null;
        $limit = $_GET['limit'] ?? 10;
        
        if (!$customerId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ไม่พบ ID ลูกค้า']);
            return;
        }
        
        $callHistory = $this->callService->getCallHistory($customerId, $limit);
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => $callHistory
        ]);
    }
    
    /**
     * ดึงลูกค้าที่ต้องติดตามการโทร (API)
     */
    public function getFollowupCustomers() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $roleName = $_SESSION['role_name'];

        // ทั้ง supervisor และ telesales ดูเฉพาะข้อมูลของตัวเอง
        // (supervisor จัดการทีมผ่านหน้าอื่น)
        
        $filters = [
            'urgency' => $_GET['urgency'] ?? null,
            'call_result' => $_GET['call_result'] ?? null,
            'priority' => $_GET['priority'] ?? null
        ];
        
        $customers = $this->callService->getCallFollowupCustomers($userId, $filters);
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => $customers
        ]);
    }
    
    /**
     * อัปเดตสถานะคิวการติดตาม (API)
     */
    public function updateQueueStatus() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
            return;
        }
        
        // ตรวจสอบ method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Method ไม่ถูกต้อง']);
            return;
        }
        
        $queueId = $_POST['queue_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        if (!$queueId || !$status) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            return;
        }
        
        $result = $this->callService->updateFollowupQueueStatus($queueId, $status, $notes);
        
        $this->sendJsonResponse($result);
    }
    
    /**
     * ดึงสถิติการโทร (API)
     */
    public function getCallStats() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $roleName = $_SESSION['role_name'];
        $period = $_GET['period'] ?? 'month';
        
        // สำหรับ supervisor ให้ดึงสถิติทั้งหมด
        if ($roleName === 'supervisor') {
            $userId = null;
        }
        
        $stats = $this->callService->getCallStats($userId, $period);
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * หน้าบันทึกการโทร
     */
    public function logCallForm() {
        // ตรวจสอบสิทธิ์
        if (!$this->auth->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        $customerId = $_GET['customer_id'] ?? null;
        
        if (!$customerId) {
            header('Location: customers.php');
            exit;
        }
        
        // ดึงข้อมูลลูกค้า
        require_once __DIR__ . '/../services/CustomerService.php';
        $customerService = new CustomerService();
        $customer = $customerService->getCustomerById($customerId);
        
        if (!$customer) {
            header('Location: customers.php');
            exit;
        }
        
        // ใช้ layout ที่ถูกต้อง
        $pageTitle = 'บันทึกการโทร - CRM SalesTracker';
        $bodyClass = 'call-log-page-body';
        
        // เริ่ม output buffering
        ob_start();
        
        // แสดงหน้า
        include APP_VIEWS . 'calls/log_call.php';
        
        $content = ob_get_clean();
        
        // ใช้ layout หลัก
        include APP_VIEWS . 'layouts/main.php';
    }
    
    /**
     * ส่ง JSON response
     */
    private function sendJsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
