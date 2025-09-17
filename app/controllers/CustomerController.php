<?php
/**
 * CustomerController Class
 * จัดการการเรียกใช้ CustomerService และหน้า Customer Management
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../services/TagService.php';

class CustomerController {
    private $db;
    private $auth;
    private $customerService;
    private $tagService;
    
    public function __construct() {
        try {
            $this->db = new Database();
            $this->auth = new Auth($this->db);
            $this->customerService = new CustomerService();
            $this->tagService = new TagService();
        } catch (Exception $e) {
            error_log("CustomerController constructor error: " . $e->getMessage());
            throw $e;
        }
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
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ดึงข้อมูลตามบทบาท
        $customers = [];
        $followUpCustomers = [];
        
        switch ($roleName) {
            case 'super_admin':
                // Super admin เห็นลูกค้าทั้งหมด
                $customers = $this->customerService->getCustomersByBasket('distribution', ['current_user_id' => $userId]);
                break;

            case 'admin':
                // Admin company เห็นเฉพาะลูกค้าของบริษัทตัวเอง
                $customers = $this->customerService->getCustomersByBasket('distribution', [
                    'current_user_id' => $userId,
                    'company_id' => $companyId
                ]);
                break;

            case 'supervisor':
            case 'telesales':
                // Supervisor และ Telesales เห็นเฉพาะลูกค้าที่ได้รับมอบหมายให้ตัวเอง
                $customers = $this->customerService->getCustomersByBasket('assigned', [
                    'assigned_to' => $userId,
                    'current_user_id' => $userId,
                    'company_id' => $companyId
                ]);
                $followUpCustomers = $this->customerService->getFollowUpCustomers($userId);
                break;
        }
        
        // แก้ไขลำดับความสำคัญของ status: ติดตาม > existing_3m > existing > new
        $customers = $this->prioritizeCustomerStatus($customers);
        $followUpCustomers = $this->prioritizeCustomerStatus($followUpCustomers);
        
        // ดึงข้อมูล Telesales สำหรับ Supervisor
        $telesalesList = [];
        if ($roleName === 'super_admin') {
            $telesalesList = $this->db->fetchAll(
                "SELECT user_id, full_name FROM users WHERE role_id = 4 AND is_active = 1 ORDER BY full_name"
            );
        } elseif ($roleName === 'admin') {
            // Admin company เห็นเฉพาะ Telesales ของบริษัทตัวเอง
            $telesalesList = $this->db->fetchAll(
                "SELECT user_id, full_name FROM users WHERE role_id = 4 AND company_id = ? AND is_active = 1 ORDER BY full_name",
                [$companyId]
            );
        } elseif ($roleName === 'supervisor') {
            // Supervisor เห็นเฉพาะ Telesales ในทีมตัวเอง
            $telesalesList = $this->db->fetchAll(
                "SELECT user_id, full_name FROM users WHERE supervisor_id = :supervisor_id AND role_id = 4 AND is_active = 1 ORDER BY full_name",
                ['supervisor_id' => $userId]
            );
        }
        
        // ดึงข้อมูลจังหวัด
        if ($roleName === 'super_admin') {
            $provinces = $this->db->fetchAll(
                "SELECT DISTINCT province FROM customers WHERE province IS NOT NULL AND province != '' ORDER BY province"
            );
        } else {
            // กรองตาม company_id สำหรับ role อื่นๆ
            $provinces = $this->db->fetchAll(
                "SELECT DISTINCT province FROM customers WHERE province IS NOT NULL AND province != '' AND company_id = ? ORDER BY province",
                [$companyId]
            );
        }
        
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
     * แสดงหน้าแก้ไขข้อมูลพื้นฐานลูกค้า (ชื่อ, เบอร์, ที่อยู่)
     */
    public function editBasic($customerId) {
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];

        $customer = $this->db->fetchOne(
            "SELECT c.*, u.full_name as assigned_to_name FROM customers c LEFT JOIN users u ON c.assigned_to = u.user_id WHERE c.customer_id = :customer_id",
            ['customer_id' => $customerId]
        );
        if (!$customer) { $this->showError('ไม่พบลูกค้า','ลูกค้าไม่มีอยู่ในระบบ'); return; }
        if (($roleName === 'telesales' || $roleName === 'supervisor') && $customer['assigned_to'] != $userId) {
            $this->showError('ไม่มีสิทธิ์เข้าถึง', 'คุณไม่มีสิทธิ์แก้ไขลูกค้ารายนี้'); return; }

        $pageTitle = 'แก้ไขข้อมูลลูกค้า';
        ob_start();
        include APP_VIEWS . 'customers/edit_basic.php';
        $content = ob_get_clean();
        include APP_VIEWS . 'layouts/main.php';
    }

    /**
     * อัปเดตข้อมูลพื้นฐานลูกค้า (JSON)
     */
    public function updateBasic() {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'ไม่ได้รับอนุญาต']); return; }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed']); return; }
            $input = json_decode(file_get_contents('php://input'), true);
            $customerId = (int)($input['customer_id'] ?? 0);
            if ($customerId <= 0) { echo json_encode(['success'=>false,'message'=>'ไม่พบลูกค้า']); return; }

            $firstName = trim($input['first_name'] ?? '');
            $lastName = trim($input['last_name'] ?? '');
            $phoneRaw = preg_replace('/\D+/', '', (string)($input['phone'] ?? ''));
            $email = trim($input['email'] ?? '');
            $address = trim($input['address'] ?? '');
            $province = trim($input['province'] ?? '');
            $postal = trim($input['postal_code'] ?? '');

            if ($firstName === '' || !preg_match('/^\d{9,10}$/', $phoneRaw)) {
                echo json_encode(['success'=>false,'message'=>'กรุณากรอกชื่อ และเบอร์โทรให้ถูกต้อง']); return;
            }

            // ถ้าเป็น 10 หลักขึ้นต้น 0 ให้ตัด 0 ออก เหลือ 9 หลัก ให้สอดคล้องกฎเดิม
            if (preg_match('/^0\d{9}$/', $phoneRaw)) { $phoneRaw = substr($phoneRaw,1); }

            $this->db->update('customers', [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phoneRaw,
                'email' => $email,
                'address' => $address,
                'province' => $province,
                'postal_code' => $postal,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'customer_id = :id', ['id' => $customerId]);

            echo json_encode(['success'=>true,'message'=>'บันทึกข้อมูลเรียบร้อย']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>'เกิดข้อผิดพลาด: '.$e->getMessage()]);
        }
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
            "SELECT 
                 cl.*, 
                 -- override call_result to be the display label if available
                 COALESCE(cl.result_display, cl.call_result) AS call_result,
                 -- keep original call_status for coloring, but also include display for convenience
                 COALESCE(cl.status_display, NULL) AS status_display,
                 u.full_name as user_name 
             FROM call_logs cl 
             LEFT JOIN users u ON cl.user_id = u.user_id 
             WHERE cl.customer_id = :customer_id 
             ORDER BY cl.created_at DESC",
            ['customer_id' => $customerId]
        );
        
        // ดึงกิจกรรมลูกค้าแบบรวม (จากหลายแหล่ง)
        $activities = $this->getCombinedCustomerActivities($customerId);
        
        // ดึงคำสั่งซื้อ พร้อมจำนวนรวม (ชิ้น) และสรุปรายการสินค้าแบบย่อ
        $orders = $this->db->fetchAll(
            "SELECT 
                o.*, 
                COALESCE(q.qty_total, 0) AS total_quantity,
                u.full_name AS salesperson_name,
                s.item_summary
             FROM orders o 
             LEFT JOIN users u ON o.created_by = u.user_id 
             LEFT JOIN (
                SELECT order_id, SUM(quantity) AS qty_total
                FROM order_items
                GROUP BY order_id
             ) q ON o.order_id = q.order_id
             LEFT JOIN (
                SELECT oi.order_id,
                       GROUP_CONCAT(CONCAT(COALESCE(p.product_name, 'ไม่ทราบสินค้า'), ' × ', oi.quantity) SEPARATOR ', ') AS item_summary
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                GROUP BY oi.order_id
             ) s ON o.order_id = s.order_id
             WHERE o.customer_id = :customer_id 
             ORDER BY o.order_date DESC",
            ['customer_id' => $customerId]
        );
        
        // ส่งข้อมูลไปยังหน้า show.php
        $customerData = $customer;
        $orderData = $orders;
        // $callLogs และ $activities ถูกกำหนดไว้แล้วข้างบน
        
        // ใช้ layout ที่ถูกต้อง
        $pageTitle = 'รายละเอียดลูกค้า - CRM SalesTracker';
        $bodyClass = 'customer-detail-page'; // Set bodyClass for proper JavaScript loading
        
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
			// Map Thai labels to internal codes if needed
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
            if (isset($statusMap[$callStatus])) $callStatus = $statusMap[$callStatus];
            if (isset($resultMap[$callResult])) $callResult = $resultMap[$callResult];
            // Allow full result set; no forced normalization

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

			// เปลี่ยนสถานะลูกค้าอัตโนมัติ: เมื่อเป็นลูกค้าใหม่และไม่ได้ "สั่งซื้อ" ให้ย้ายไป followup
			try {
				$companyId = $_SESSION['company_id'] ?? null;
				$sql = "SELECT customer_status FROM customers WHERE customer_id = ?";
				$params = [$customerId];
				
				if ($companyId) {
					$sql .= " AND company_id = ?";
					$params[] = $companyId;
				}
				
				$customer = $this->db->fetchOne($sql, $params);
				if (($customer['customer_status'] ?? '') === 'new' && $callResult !== 'order') {
					$updateSql = "UPDATE customers SET customer_status = 'followup' WHERE customer_id = ?";
					$updateParams = [$customerId];
					
					if ($companyId) {
						$updateSql .= " AND company_id = ?";
						$updateParams[] = $companyId;
					}
					
					$this->db->execute($updateSql, $updateParams);
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
        $companyId = $_SESSION['company_id'] ?? null;
        if ($roleName === 'telesales' || $roleName === 'supervisor') {
            $filters['assigned_to'] = $_SESSION['user_id'];
            $filters['company_id'] = $companyId;
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
        $companyId = $_SESSION['company_id'] ?? null;

        // สำหรับ telesales ใช้ service โดยตรง (ตามผู้ใช้)
        if ($roleName === 'telesales') {
            // ส่ง filters ผ่านมาเพื่อเลี่ยงโหลดเกินและให้กรองฝั่งเซิร์ฟเวอร์
            $filters = [
                'temperature' => $_GET['temperature'] ?? null,
                'grade' => $_GET['grade'] ?? null,
                'province' => $_GET['province'] ?? null,
                'name' => $_GET['name'] ?? null,
                'phone' => $_GET['phone'] ?? null,
            ];
            $data = $this->customerService->getFollowUpCustomers($userId, $filters);
            echo json_encode(['success' => true, 'data' => $data]);
            return;
        }

        // สำหรับ supervisor/admin แสดงตามทีม/ทั้งหมด
        $params = [];
        $where = "c.is_active = 1 AND (c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR c.next_followup_at <= NOW())";

        if ($roleName === 'supervisor' || $roleName === 'telesales') {
            $where .= " AND c.assigned_to = ? AND c.company_id = ?";
            $params[] = $userId;
            $params[] = $companyId;
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
     * จัดลำดับความสำคัญของ customer status
     * ติดตาม > existing_3m > existing > new
     */
    private function prioritizeCustomerStatus($customers) {
        if (empty($customers)) {
            return $customers;
        }
        
        // กำหนดลำดับความสำคัญ
        $priorityOrder = [
            'followup' => 1,
            'call_followup' => 1,
            'existing_3m' => 2,
            'existing' => 3,
            'new' => 4,
            'daily_distribution' => 5
        ];
        
        // จัดเรียงตามลำดับความสำคัญ
        usort($customers, function($a, $b) use ($priorityOrder) {
            $statusA = $a['customer_status'] ?? 'new';
            $statusB = $b['customer_status'] ?? 'new';
            
            $priorityA = $priorityOrder[$statusA] ?? 999;
            $priorityB = $priorityOrder[$statusB] ?? 999;
            
            if ($priorityA == $priorityB) {
                // ถ้าลำดับเท่ากัน ให้เรียงตาม customer_id
                return ($a['customer_id'] ?? 0) - ($b['customer_id'] ?? 0);
            }
            
            return $priorityA - $priorityB;
        });
        
        return $customers;
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
            $companyId = $_SESSION['company_id'] ?? null;
            
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
                    "SELECT customer_id FROM customers WHERE customer_id = :customer_id AND assigned_to = :user_id AND company_id = :company_id",
                    ['customer_id' => $customerId, 'user_id' => $userId, 'company_id' => $companyId]
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
            case 'hangup': return 'ตัดสายทิ้ง';
            case 'unable_to_contact': return 'ติดต่อไม่ได้';
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
            case 'order': return 'สั่งซื้อ';
            case 'interested': return 'สนใจ';
            case 'add_line': return 'Add Line แล้ว';
            case 'buy_on_page': return 'ต้องการซื้อทางเพจ';
            case 'flood': return 'น้ำท่วม';
            case 'callback': return 'รอติดต่อใหม่';
            case 'appointment': return 'นัดหมาย';
            case 'invalid_number': return 'เบอร์ไม่ถูก';
            case 'not_convenient': return 'ไม่สะดวกคุย';
            case 'not_interested': return 'ไม่สนใจ';
            case 'do_not_call': return 'อย่าโทรมาอีก';
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

    // ========================= TAG MANAGEMENT =========================

    /**
     * API: เพิ่ม tag ให้ลูกค้า
     */
    public function addTag() {
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
        $tagName = trim($input['tag_name'] ?? '');
        $tagColor = $input['tag_color'] ?? '#007bff';
        $userId = $_SESSION['user_id'];

        if (!$customerId || !$tagName) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }

        $result = $this->tagService->addTagToCustomer($customerId, $userId, $tagName, $tagColor);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * API: ลบ tag ของลูกค้า
     */
    public function removeTag() {
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
        $tagName = trim($input['tag_name'] ?? '');
        $userId = $_SESSION['user_id'];

        if (!$customerId || !$tagName) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }

        $result = $this->tagService->removeTagFromCustomer($customerId, $userId, $tagName);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * API: ดึง tags ของลูกค้า
     */
    public function getCustomerTags() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $customerId = $_GET['customer_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$customerId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing customer_id']);
            return;
        }

        $tags = $this->tagService->getCustomerTags($customerId, $userId);
        echo json_encode(['tags' => $tags]);
    }

    /**
     * API: ดึง tags ทั้งหมดที่ user เคยใช้
     */
    public function getUserTags() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $tags = $this->tagService->getUserTags($userId);
        $predefinedTags = $this->tagService->getPredefinedTags($userId);
        
        echo json_encode([
            'user_tags' => $tags,
            'predefined_tags' => $predefinedTags
        ]);
    }

    /**
     * API: ค้นหาลูกค้าตาม tags
     */
    public function getCustomersByTags() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $tagNames = $_GET['tags'] ?? [];
        
        // แปลง string เป็น array ถ้าจำเป็น
        if (is_string($tagNames)) {
            $tagNames = explode(',', $tagNames);
        }

        // Additional filters
        $additionalFilters = [
            'temperature' => $_GET['temperature'] ?? '',
            'grade' => $_GET['grade'] ?? '',
            'province' => $_GET['province'] ?? '',
            'name' => $_GET['name'] ?? '',
            'phone' => $_GET['phone'] ?? ''
        ];

        // ลบค่าว่าง
        $additionalFilters = array_filter($additionalFilters);

        $customers = $this->tagService->getCustomersByTags($userId, $tagNames, $additionalFilters);
        echo json_encode(['customers' => $customers]);
    }

    /**
     * API: เพิ่ม tags หลายอันพร้อมกัน (Bulk operation)
     */
    public function bulkAddTags() {
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
        
        $customerIds = $input['customer_ids'] ?? [];
        $tagName = trim($input['tag_name'] ?? '');
        $tagColor = $input['tag_color'] ?? '#007bff';
        $userId = $_SESSION['user_id'];

        if (empty($customerIds) || !$tagName) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }

        $result = $this->tagService->bulkAddTags($customerIds, $userId, $tagName, $tagColor);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * API: ลบ tags หลายอันพร้อมกัน (Bulk operation)
     */
    public function bulkRemoveTags() {
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
        
        $customerIds = $input['customer_ids'] ?? [];
        $tagNames = $input['tag_names'] ?? [];
        $userId = $_SESSION['user_id'];

        if (empty($customerIds) || empty($tagNames)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }

        $result = $this->tagService->bulkRemoveTags($customerIds, $userId, $tagNames);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * API: ดึงข้อมูลลูกค้าพร้อม tags สำหรับอัพเดทตาราง
     */
    public function getCustomerWithTags($customerId) {
        try {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                return;
            }
            
            $companyId = $_SESSION['company_id'] ?? null;
            
            // ดึงข้อมูลลูกค้า
            $sql = "SELECT c.*, u.full_name as assigned_to_name 
                    FROM customers c 
                    LEFT JOIN users u ON c.assigned_to = u.user_id 
                    WHERE c.customer_id = ?";
            $params = [$customerId];
            
            if ($companyId) {
                $sql .= " AND c.company_id = ?";
                $params[] = $companyId;
            }
            
            $customer = $this->db->fetchOne($sql, $params);
            
            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'Customer not found']);
                return;
            }
            
            // ดึง tags ของลูกค้า
            $tags = $this->db->fetchAll(
                "SELECT ct.tag_name, ct.tag_color 
                 FROM customer_tags ct 
                 WHERE ct.customer_id = ? 
                 ORDER BY ct.created_at DESC",
                [$customerId]
            );
            
            $customer['tags'] = $tags;
            
            echo json_encode([
                'success' => true,
                'customer' => $customer
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getCustomerWithTags: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล'
            ]);
        }
    }

    /**
     * API: ดึงรายการ Telesales สำหรับการเปลี่ยนผู้ดูแล
     */
    public function getTelesalesList() {
        try {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                return;
            }

            // ตรวจสอบสิทธิ์ (Admin, Super Admin และ Company Admin)
            $roleName = $_SESSION['role_name'] ?? '';
            $roleId = $_SESSION['role_id'] ?? 0;
            $userCompanyId = $_SESSION['company_id'] ?? null;
            
            $canAccess = in_array($roleName, ['admin', 'super_admin', 'company_admin']) 
                      || $roleId == 6;
                      
            if (!$canAccess) {
                echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
                return;
            }

            // สร้าง SQL query ตามสิทธิ์
            $sql = "SELECT u.user_id, u.full_name, u.email, 
                           c.company_name, c.company_code
                    FROM users u
                    JOIN companies c ON u.company_id = c.company_id
                    WHERE u.role_id = 4 AND u.is_active = 1";
            
            $params = [];
            
            // ถ้าเป็น company_admin (role_id = 6) ให้เห็นเฉพาะคนในบริษัทตัวเอง
            if ($roleId == 6 || $roleName == 'company_admin') {
                if ($userCompanyId) {
                    $sql .= " AND u.company_id = ?";
                    $params[] = $userCompanyId;
                }
            }
            
            $sql .= " ORDER BY c.company_name, u.full_name";
            
            // ดึงข้อมูล
            $telesales = $this->db->fetchAll($sql, $params);

            echo json_encode([
                'success' => true,
                'data' => $telesales
            ]);

        } catch (Exception $e) {
            error_log("Error in getTelesalesList: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงรายการ Telesales'
            ]);
        }
    }

    /**
     * API: ดึงรายชื่อผู้รับที่อนุญาตตามกติกาเปลี่ยนผู้ดูแล
     * - รองรับทั้ง Supervisor และ Telesales
     * - กรองตามบริษัท และความเป็นเจ้าของลูกค้าตามกฎ
     */
    public function getAllowedAssignees() {
        try {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                return;
            }

            $actorUserId = (int)($_SESSION['user_id'] ?? 0);
            $roleName = $_SESSION['role_name'] ?? '';
            $roleId = (int)($_SESSION['role_id'] ?? 0);
            $companyId = $_SESSION['company_id'] ?? null;
            $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

            if ($customerId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Missing customer_id']);
                return;
            }

            // ตรวจสอบลูกค้าและเจ้าของปัจจุบัน
            $sql = "SELECT customer_id, assigned_to, company_id FROM customers WHERE customer_id = ?";
            $params = [$customerId];
            if ($companyId) { $sql .= " AND company_id = ?"; $params[] = $companyId; }
            $customer = $this->db->fetchOne($sql, $params);
            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'Customer not found']);
                return;
            }

            // สำหรับ supervisor/telesales ต้องเป็นผู้ถือจริงก่อน จึงจะเห็นรายชื่อโอนได้
            if ($roleId === 3 || $roleId === 4) {
                if ((int)$customer['assigned_to'] !== $actorUserId) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied: not owner']);
                    return;
                }
            }

            $allowedUsers = [];
            $groups = [];

            // ผู้ดูแลระดับสูง (admin/super_admin/company_admin/role_id 1,6) → เห็นผู้รับทุกคนในบริษัท (Supervisor/Telesales)
            if (in_array($roleName, ['admin', 'super_admin', 'company_admin']) || in_array($roleId, [1, 6])) {
                // แยกกลุ่ม Supervisor และ Telesales ภายในบริษัท
                $supervisors = $this->db->fetchAll(
                    "SELECT user_id, full_name, role_id FROM users WHERE is_active = 1 AND role_id = 3 AND company_id = ? ORDER BY full_name",
                    [$companyId]
                );
                $telesalesAll = $this->db->fetchAll(
                    "SELECT user_id, full_name, role_id FROM users WHERE is_active = 1 AND role_id = 4 AND company_id = ? ORDER BY full_name",
                    [$companyId]
                );
                $groups = [
                    [ 'key' => 'supervisors', 'label' => 'Supervisor (บริษัทเดียวกัน)', 'users' => $supervisors ],
                    [ 'key' => 'telesales', 'label' => 'Telesales (บริษัทเดียวกัน)', 'users' => $telesalesAll ],
                ];
            } elseif ($roleId === 3) {
                // Supervisor → สามารถโอนไป Supervisor บริษัทเดียวกัน หรือ Telesales ลูกทีมตัวเอง
                $supervisors = $this->db->fetchAll(
                    "SELECT user_id, full_name, role_id FROM users WHERE is_active = 1 AND role_id = 3 AND company_id = ? AND user_id <> ? ORDER BY full_name",
                    [$companyId, $actorUserId]
                );
                $teamTelesales = $this->db->fetchAll(
                    "SELECT user_id, full_name, role_id FROM users WHERE is_active = 1 AND role_id = 4 AND supervisor_id = ? AND company_id = ? ORDER BY full_name",
                    [$actorUserId, $companyId]
                );
                $groups = [
                    [ 'key' => 'team_telesales', 'label' => 'ลูกทีมของคุณ', 'users' => $teamTelesales ],
                    [ 'key' => 'company_supervisors', 'label' => 'Supervisor (บริษัทเดียวกัน)', 'users' => $supervisors ],
                ];
                $allowedUsers = array_merge($teamTelesales, $supervisors);
            } elseif ($roleId === 4) {
                // Telesales → โอนได้เฉพาะกลับหัวหน้าของตนเอง
                $supervisor = $this->db->fetchOne(
                    "SELECT s.user_id, s.full_name, s.role_id FROM users u LEFT JOIN users s ON u.supervisor_id = s.user_id WHERE u.user_id = ? AND s.is_active = 1",
                    [$actorUserId]
                );
                if ($supervisor) { 
                    $allowedUsers = [$supervisor]; 
                    $groups = [ [ 'key' => 'supervisor', 'label' => 'หัวหน้าของคุณ', 'users' => [$supervisor] ] ];
                }
            }

            // เพิ่ม label บทบาท
            foreach ($allowedUsers as &$u) {
                $u['role_label'] = ((int)$u['role_id'] === 3) ? 'Supervisor' : (((int)$u['role_id'] === 4) ? 'Telesales' : '');
            }
            unset($u);

            echo json_encode(['success' => true, 'data' => $allowedUsers, 'groups' => $groups]);
        } catch (Exception $e) {
            error_log("Error in getAllowedAssignees: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงรายชื่อผู้รับ']);
        }
    }

    /**
     * API: เปลี่ยนผู้ดูแลลูกค้า
     */
    public function changeCustomerAssignee() {
        try {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                return;
            }

            // ตรวจสอบสิทธิ์ตามบทบาท
            $roleName = $_SESSION['role_name'] ?? '';
            $roleId = (int)($_SESSION['role_id'] ?? 0);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                return;
            }

            $customerId = $_POST['customer_id'] ?? null;
            $newAssignee = $_POST['new_assignee'] ?? null;
            $changeReason = $_POST['change_reason'] ?? '';

            if (!$customerId || !$newAssignee) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            $companyId = $_SESSION['company_id'] ?? null;
            
            // ตรวจสอบว่าลูกค้ามีอยู่จริง
            $sql = "SELECT customer_id, assigned_to, basket_type, first_name, last_name FROM customers WHERE customer_id = ?";
            $params = [$customerId];
            
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $customer = $this->db->fetchOne($sql, $params);

            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'Customer not found']);
                return;
            }

            // ข้อมูลผู้รับมอบหมายใหม่ (รองรับทั้ง Supervisor และ Telesales)
            $newUser = $this->db->fetchOne(
                "SELECT user_id, full_name, role_id, company_id, supervisor_id 
                 FROM users WHERE user_id = ? AND is_active = 1",
                [$newAssignee]
            );

            if (!$newUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }
            if ($companyId && (int)$newUser['company_id'] !== (int)$companyId) {
                echo json_encode(['success' => false, 'message' => 'Different company']);
                return;
            }

            $oldAssignee = $customer['assigned_to'];
            $oldAssigneeName = '';
            if ($oldAssignee) {
                $oldUser = $this->db->fetchOne(
                    "SELECT full_name FROM users WHERE user_id = ?",
                    [$oldAssignee]
                );
                $oldAssigneeName = $oldUser['full_name'] ?? 'ไม่ระบุ';
            }

            // ตรวจสอบกติกาการย้ายตามบทบาท
            $actorUserId = (int)$_SESSION['user_id'];

            // 1) Supervisor ↔ Supervisor (role 3 -> role 3) ในบริษัทเดียวกัน และต้องเป็นผู้ถืออยู่ (B โอนจากตัวเองให้ A)
            $allowByRule = false;

            if (in_array($roleId, [1, 6]) || in_array($roleName, ['admin', 'super_admin', 'company_admin'])) {
                // ผู้ดูแลระดับสูง ใช้เงื่อนไขบริษัทเดียวกันเป็นหลัก
                $allowByRule = ((int)$newUser['company_id'] === (int)$companyId);
            } elseif ($roleId === 3) { // supervisor กระทำเอง
                // ต้องเป็นเจ้าของลูกค้ารายนี้ก่อน
                if ((int)$oldAssignee === $actorUserId) {
                    // โอนไป Supervisor ได้ (กฎ 1)
                    if ((int)$newUser['role_id'] === 3 && (int)$newUser['company_id'] === (int)$companyId) {
                        $allowByRule = true;
                    }
                    // หรือโอนไป Telesales ที่เป็นลูกทีมตนเอง (กฎ 2)
                    if ((int)$newUser['role_id'] === 4 && (int)$newUser['supervisor_id'] === $actorUserId && (int)$newUser['company_id'] === (int)$companyId) {
                        $allowByRule = true;
                    }
                }
            } elseif ($roleId === 4) { // telesales กระทำเอง
                // ต้องเป็นเจ้าของลูกค้าก่อน และโอนกลับให้หัวหน้าของตนเท่านั้น (กฎ 3)
                $selfRow = $this->db->fetchOne("SELECT supervisor_id, company_id FROM users WHERE user_id = ?", [$actorUserId]);
                if ((int)$oldAssignee === $actorUserId && $selfRow) {
                    if ((int)$newUser['role_id'] === 3 && (int)$newUser['user_id'] === (int)$selfRow['supervisor_id'] && (int)$newUser['company_id'] === (int)$selfRow['company_id']) {
                        $allowByRule = true;
                    }
                }
            }

            if (!$allowByRule) {
                echo json_encode(['success' => false, 'message' => 'Permission denied by transfer rule']);
                return;
            }

            // เริ่ม transaction
            $this->db->beginTransaction();

            try {
                // อัปเดตผู้ดูแลลูกค้า
                // กรณีลูกค้าอยู่ในตะกร้า "รอ" หรือ "แจก" (หรือยังไม่มีผู้ดูแล)
                // ให้ย้ายไปตะกร้า assigned และรีเซ็ตเวลาเริ่มต้น/หมดอายุ
                $isNewAssignment = empty($customer['assigned_to']) || (($customer['basket_type'] ?? '') !== 'assigned');

                $updateData = [
                    'assigned_to' => $newAssignee,
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($isNewAssignment) {
                    $updateData['basket_type'] = 'assigned';
                    $updateData['customer_time_base'] = date('Y-m-d H:i:s');
                    $updateData['customer_time_expiry'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                }

                $this->db->update('customers', $updateData, 'customer_id = ?', [$customerId]);

                // บันทึกประวัติการเปลี่ยนแปลง
                $this->db->insert('customer_activities', [
                    'customer_id' => $customerId,
                    'user_id' => $_SESSION['user_id'],
                    'activity_type' => 'assignee_changed',
                    'activity_description' => "เปลี่ยนผู้ดูแลจาก {$oldAssigneeName} เป็น {$newUser['full_name']}" . 
                                            ($changeReason ? " - เหตุผล: {$changeReason}" : ''),
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $this->db->commit();

                echo json_encode([
                    'success' => true,
                    'message' => "เปลี่ยนผู้ดูแลลูกค้า {$customer['first_name']} {$customer['last_name']} สำเร็จ",
                    'data' => [
                        'customer_id' => $customerId,
                        'old_assignee' => $oldAssignee,
                        'new_assignee' => $newAssignee,
                        'change_reason' => $changeReason
                    ]
                ]);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error in changeCustomerAssignee: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนผู้ดูแล: ' . $e->getMessage()
            ]);
        }
    }

}
?> 
