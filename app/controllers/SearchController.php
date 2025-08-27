<?php
/**
 * Search Controller
 * จัดการระบบค้นหาลูกค้าและยอดขาย
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/SearchService.php';

class SearchController {
    private $db;
    private $searchService;

    public function __construct() {
        $this->db = new Database();
        $this->searchService = new SearchService();
    }

    /**
     * แสดงหน้าค้นหา
     */
    public function index() {
        $pageTitle = 'ค้นหาลูกค้า';
        $bodyClass = 'search-page-body';
        
        // Get user role for permissions
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Get current user's company source
        $userSource = $this->searchService->getCurrentUserSource();
        
        // Capture the view content
        ob_start();
        include APP_ROOT . '/app/views/search/index.php';
        $content = ob_get_clean();
        
        // Include the main layout
        include APP_ROOT . '/app/views/layouts/main.php';
    }

    /**
     * ค้นหาลูกค้า (AJAX)
     */
    public function search() {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
                return;
            }

            // รับข้อมูลการค้นหา
            $searchTerm = $_GET['term'] ?? '';
            $searchTerm = trim($searchTerm);

            if (empty($searchTerm)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาใส่คำที่ต้องการค้นหา']);
                return;
            }

            // ตรวจสอบความยาวขั้นต่ำ
            if (mb_strlen($searchTerm) < 2) {
                echo json_encode(['success' => false, 'message' => 'กรุณาใส่คำค้นหาอย่างน้อย 2 ตัวอักษร']);
                return;
            }

            // ค้นหาลูกค้า
            $customers = $this->searchService->searchCustomers($searchTerm);

            if (empty($customers)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ไม่พบข้อมูลการขาย',
                    'customers' => []
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'customers' => $customers,
                'total' => count($customers)
            ]);

        } catch (Exception $e) {
            error_log("Search Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการค้นหา']);
        }
    }

    /**
     * ดึงรายละเอียดลูกค้า (AJAX)
     */
    public function getCustomerDetails() {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
                return;
            }

            $customerId = $_GET['customer_id'] ?? 0;

            if (!$customerId) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสลูกค้า']);
                return;
            }

            // ดึงข้อมูลลูกค้า
            $customer = $this->searchService->getCustomerById($customerId);

            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลลูกค้า']);
                return;
            }

            echo json_encode([
                'success' => true,
                'customer' => $customer
            ]);

        } catch (Exception $e) {
            error_log("Get Customer Details Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล']);
        }
    }

    /**
     * ดึงรายละเอียดคำสั่งซื้อ (AJAX)
     */
    public function getOrderDetails() {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
                return;
            }

            $orderId = $_GET['order_id'] ?? 0;

            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสคำสั่งซื้อ']);
                return;
            }

            // ดึงรายละเอียดคำสั่งซื้อ
            $orderDetails = $this->searchService->getOrderDetails($orderId);

            if (!$orderDetails) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลคำสั่งซื้อ']);
                return;
            }

            echo json_encode([
                'success' => true,
                'order' => $orderDetails
            ]);

        } catch (Exception $e) {
            error_log("Get Order Details Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล']);
        }
    }
}
