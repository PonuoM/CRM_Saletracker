<?php
/**
 * OrderController Class
 * จัดการการเรียกใช้ OrderService และหน้า Order Management
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/CustomerService.php';

class OrderController {
    private $db;
    private $auth;
    private $orderService;
    private $customerService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth($this->db);
        $this->orderService = new OrderService();
        $this->customerService = new CustomerService();
    }
    
    /**
     * แสดงหน้ารายการคำสั่งซื้อ
     */
    public function index() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ตั้งค่าตัวกรอง
        $filters = [];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // ตัวกรองตามบทบาท
        if ($roleName === 'telesales') {
            $filters['created_by'] = $userId;
        }
        
        // ตัวกรองจาก URL parameters
        if (!empty($_GET['customer_id'])) {
            $filters['customer_id'] = $_GET['customer_id'];
        }
        
        if (!empty($_GET['payment_status'])) {
            $filters['payment_status'] = $_GET['payment_status'];
        }
        
        if (!empty($_GET['delivery_status'])) {
            $filters['delivery_status'] = $_GET['delivery_status'];
        }
        
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if (!empty($_GET['order_number'])) {
            $filters['order_number'] = $_GET['order_number'];
        }
        
        // ดึงข้อมูลคำสั่งซื้อ
        $result = $this->orderService->getOrders($filters, $page, 20);
        
        if (!$result['success']) {
            $this->showError('เกิดข้อผิดพลาด', $result['message']);
            return;
        }
        
        $orderList = $result['orders'];
        $total = $result['total'];
        $totalPages = $result['total_pages'];
        
        // ดึงข้อมูลลูกค้าสำหรับตัวกรอง
        $customers = [];
        if (in_array($roleName, ['supervisor', 'admin', 'super_admin'])) {
            $customers = $this->db->fetchAll(
                "SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name FROM customers ORDER BY first_name, last_name"
            );
        } else {
            // Telesales เห็นเฉพาะลูกค้าที่ได้รับมอบหมาย
            $customers = $this->db->fetchAll(
                "SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name FROM customers WHERE assigned_to = :user_id ORDER BY first_name, last_name",
                ['user_id' => $userId]
            );
        }
        
        include APP_VIEWS . 'orders/index.php';
    }
    
    /**
     * แสดงหน้ารายละเอียดคำสั่งซื้อ
     */
    public function show($orderId) {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ดึงข้อมูลคำสั่งซื้อ
        $result = $this->orderService->getOrderDetail($orderId);
        
        if (!$result['success']) {
            $this->showError('ไม่พบคำสั่งซื้อ', $result['message']);
            return;
        }
        
        $orderData = $result['order'];
        $orderItems = $result['items'];
        
        // ดึงข้อมูลกิจกรรม (ถ้ามี)
        $orderActivities = [];
        try {
            $activitiesQuery = "
                SELECT 
                    oa.*,
                    u.username as user_name
                FROM order_activities oa
                LEFT JOIN users u ON oa.user_id = u.user_id
                WHERE oa.order_id = :order_id
                ORDER BY oa.created_at DESC
            ";
            $orderActivities = $this->db->fetchAll($activitiesQuery, ['order_id' => $orderId]);
        } catch (Exception $e) {
            // ถ้าไม่มีตาราง order_activities ก็ไม่เป็นไร
            $orderActivities = [];
        }
        
        // ตรวจสอบสิทธิ์การเข้าถึง
        if ($roleName === 'telesales' && $orderData['created_by'] != $userId) {
            $this->showError('ไม่มีสิทธิ์เข้าถึง', 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลคำสั่งซื้อนี้');
            return;
        }
        
        include APP_VIEWS . 'orders/show.php';
    }
    
    /**
     * แสดงหน้าสร้างคำสั่งซื้อใหม่
     */
    public function create() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // รับ customer_id จาก URL parameter
        $selectedCustomerId = $_GET['customer_id'] ?? null;
        
        // ดึงข้อมูลลูกค้าสำหรับเลือก
        $customerList = [];
        if ($roleName === 'telesales') {
            $customerList = $this->db->fetchAll(
                "SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, phone FROM customers WHERE assigned_to = :user_id ORDER BY first_name, last_name",
                ['user_id' => $userId]
            );
        } else {
            $customerList = $this->db->fetchAll(
                "SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, phone FROM customers ORDER BY first_name, last_name"
            );
        }
        
        // ดึงข้อมูลสินค้า
        $productsResult = $this->orderService->getProducts();
        $productList = $productsResult['success'] ? $productsResult['products'] : [];
        
        include APP_VIEWS . 'orders/create.php';
    }
    
    /**
     * แสดงหน้าแก้ไขคำสั่งซื้อ
     */
    public function edit($orderId) {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ดึงข้อมูลคำสั่งซื้อ
        $result = $this->orderService->getOrderDetail($orderId);
        
        if (!$result['success']) {
            $this->showError('ไม่พบคำสั่งซื้อ', $result['message']);
            return;
        }
        
        $order = $result['order'];
        $items = $result['items'];
        
        // ตรวจสอบสิทธิ์การเข้าถึง
        if ($roleName === 'telesales' && $order['created_by'] != $userId) {
            $this->showError('ไม่มีสิทธิ์เข้าถึง', 'คุณไม่มีสิทธิ์แก้ไขคำสั่งซื้อนี้');
            return;
        }
        
        // ดึงข้อมูลลูกค้าสำหรับเลือก
        $customerList = [];
        if ($roleName === 'telesales') {
            $customerList = $this->db->fetchAll(
                "SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, phone FROM customers WHERE assigned_to = :user_id ORDER BY first_name, last_name",
                ['user_id' => $userId]
            );
        } else {
            $customerList = $this->db->fetchAll(
                "SELECT customer_id, CONCAT(first_name, ' ', last_name) as customer_name, phone FROM customers ORDER BY first_name, last_name"
            );
        }
        
        // ดึงข้อมูลสินค้า
        $productsResult = $this->orderService->getProducts();
        $productList = $productsResult['success'] ? $productsResult['products'] : [];
        
        include APP_VIEWS . 'orders/edit.php';
    }
    
    /**
     * อัปเดตคำสั่งซื้อ
     */
    public function update() {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
                return;
            }
            
            $roleName = $_SESSION['role_name'] ?? '';
            $userId = $_SESSION['user_id'];
            
            // ตรวจสอบสิทธิ์
            if (!in_array($roleName, ['telesales', 'supervisor', 'admin', 'super_admin'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไขคำสั่งซื้อ']);
                return;
            }
            
            // ตรวจสอบข้อมูลที่ส่งมา
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Method ไม่ถูกต้อง']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
                return;
            }
            
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($input['order_id']) || empty($input['customer_id']) || empty($input['order_items'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                return;
            }
            
            // ตรวจสอบสิทธิ์การแก้ไข
            $orderResult = $this->orderService->getOrderDetail($input['order_id']);
            if (!$orderResult['success']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ']);
                return;
            }
            
            $order = $orderResult['order'];
            if ($roleName === 'telesales' && $order['created_by'] != $userId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไขคำสั่งซื้อนี้']);
                return;
            }
            
            // อัปเดตข้อมูลคำสั่งซื้อ
            $orderData = [
                'order_id' => $input['order_id'],
                'customer_id' => $input['customer_id'],
                'order_date' => $input['order_date'] ?? date('Y-m-d'),
                'payment_method' => $input['payment_method'] ?? 'pending',
                'delivery_method' => $input['delivery_method'] ?? 'delivery',
                'delivery_address' => $input['delivery_address'] ?? '',
                'notes' => $input['notes'] ?? '',
                'total_amount' => $input['total_amount'] ?? 0,
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->orderService->updateOrder($orderData, $input['order_items']);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Order Update Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตคำสั่งซื้อ']);
        }
    }
    
    /**
     * บันทึกคำสั่งซื้อใหม่
     */
    public function store() {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
                return;
            }
            
            $roleName = $_SESSION['role_name'] ?? '';
            $userId = $_SESSION['user_id'];
            
            // ตรวจสอบสิทธิ์
            if (!in_array($roleName, ['telesales', 'supervisor', 'admin', 'super_admin'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์สร้างคำสั่งซื้อ']);
                return;
            }
            
            // ตรวจสอบข้อมูลที่ส่งมา
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Method ไม่ถูกต้อง']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
                return;
            }
            
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($input['customer_id']) || empty($input['items']) || !is_array($input['items'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                return;
            }
            
            // ตรวจสอบสิทธิ์การเข้าถึงลูกค้า
            if ($roleName === 'telesales') {
                $customer = $this->db->fetchOne(
                    "SELECT customer_id FROM customers WHERE customer_id = :customer_id AND assigned_to = :user_id",
                    ['customer_id' => $input['customer_id'], 'user_id' => $userId]
                );
                
                if (!$customer) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์สร้างคำสั่งซื้อให้ลูกค้ารายนี้']);
                    return;
                }
            }
            
            // จัดการที่อยู่จัดส่ง
            $deliveryAddress = $input['delivery_address'] ?? null;
            if (isset($input['use_customer_address']) && $input['use_customer_address']) {
                // ดึงที่อยู่จากข้อมูลลูกค้า
                $customer = $this->db->fetchOne(
                    "SELECT address FROM customers WHERE customer_id = :customer_id",
                    ['customer_id' => $input['customer_id']]
                );
                if ($customer && $customer['address']) {
                    $deliveryAddress = $customer['address'];
                }
            }
            
            // สร้างคำสั่งซื้อ
            $orderData = [
                'customer_id' => $input['customer_id'],
                'payment_method' => $input['payment_method'] ?? 'cash',
                'payment_status' => $input['payment_status'] ?? 'pending',
                'delivery_date' => $input['delivery_date'] ?? null,
                'delivery_address' => $deliveryAddress,
                'delivery_status' => $input['delivery_status'] ?? 'pending',
                'discount_percentage' => $input['discount_percentage'] ?? 0,
                'discount_amount' => $input['discount_amount'] ?? 0,
                'notes' => $input['notes'] ?? null
            ];
            
            $result = $this->orderService->createOrder($orderData, $input['items'], $userId);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            
        } catch (Exception $e) {
            // Log the error
            error_log("Order creation error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * อัปเดตสถานะคำสั่งซื้อ
     */
    public function updateStatus() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
            return;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ตรวจสอบสิทธิ์
        if (!in_array($roleName, ['telesales', 'supervisor', 'admin', 'super_admin'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์อัปเดตคำสั่งซื้อ']);
            return;
        }
        
        // ตรวจสอบข้อมูลที่ส่งมา
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Method ไม่ถูกต้อง']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['order_id']) || empty($input['field']) || !isset($input['value'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            return;
        }
        
        // ตรวจสอบสิทธิ์การเข้าถึงคำสั่งซื้อ
        if ($roleName === 'telesales') {
            $order = $this->db->fetchOne(
                "SELECT order_id FROM orders WHERE order_id = :order_id AND created_by = :user_id",
                ['order_id' => $input['order_id'], 'user_id' => $userId]
            );
            
            if (!$order) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์อัปเดตคำสั่งซื้อนี้']);
                return;
            }
        }
        
        // อัปเดตสถานะ
        $result = $this->orderService->updateOrderStatus(
            $input['order_id'],
            $input['field'],
            $input['value'],
            $userId
        );
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * ส่งออกข้อมูลคำสั่งซื้อ
     */
    public function export() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
            return;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // ตั้งค่าตัวกรอง
        $filters = [];
        
        // ตัวกรองตามบทบาท
        if ($roleName === 'telesales') {
            $filters['created_by'] = $userId;
        }
        
        // ตัวกรองจาก URL parameters
        if (!empty($_GET['customer_id'])) {
            $filters['customer_id'] = $_GET['customer_id'];
        }
        
        if (!empty($_GET['payment_status'])) {
            $filters['payment_status'] = $_GET['payment_status'];
        }
        
        if (!empty($_GET['delivery_status'])) {
            $filters['delivery_status'] = $_GET['delivery_status'];
        }
        
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        // ส่งออกข้อมูล
        $result = $this->orderService->exportOrders($filters);
        
        if (!$result['success']) {
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }
        
        // สร้างไฟล์ CSV
        $filename = 'orders_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // สร้าง header
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
        
        fputcsv($output, [
            'หมายเลขคำสั่งซื้อ',
            'วันที่สั่งซื้อ',
            'ชื่อลูกค้า',
            'เบอร์โทร',
            'ยอดรวม',
            'ส่วนลด',
            'ยอดสุทธิ',
            'วิธีการชำระเงิน',
            'สถานะการชำระเงิน',
            'สถานะการจัดส่ง',
            'ผู้สร้าง'
        ]);
        
        // เพิ่มข้อมูล
        foreach ($result['data'] as $row) {
            fputcsv($output, [
                $row['order_number'],
                $row['order_date'],
                $row['customer_name'],
                $row['phone'],
                number_format($row['total_amount'], 2),
                number_format($row['discount_amount'], 2),
                number_format($row['net_amount'], 2),
                $row['payment_method'],
                $row['payment_status'],
                $row['delivery_status'],
                $row['created_by']
            ]);
        }
        
        fclose($output);
    }
    
    /**
     * API สำหรับดึงข้อมูลสินค้า
     */
    public function getProducts() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่มีการยืนยันตัวตน']);
            return;
        }
        
        $filters = [];
        
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        if (!empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        
        $result = $this->orderService->getProducts($filters);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * ลบคำสั่งซื้อ
     */
    public function delete($orderId) {
        try {
            // ตรวจสอบการยืนยันตัวตน
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('ไม่ได้รับอนุญาต');
            }
            
            $roleName = $_SESSION['role_name'] ?? '';
            $userId = $_SESSION['user_id'];
            
            // ตรวจสอบสิทธิ์
            if (!in_array($roleName, ['supervisor', 'admin', 'super_admin'])) {
                // ตรวจสอบว่าเป็นผู้สร้างคำสั่งซื้อหรือไม่
                $order = $this->db->fetchOne(
                    "SELECT created_by FROM orders WHERE order_id = :order_id",
                    ['order_id' => $orderId]
                );
                
                if (!$order || $order['created_by'] != $userId) {
                    throw new Exception('ไม่มีสิทธิ์ลบคำสั่งซื้อนี้');
                }
            }
            
            // เริ่ม transaction
            $this->db->beginTransaction();
            
            // ลบ order activities
            $this->db->delete('order_activities', 'order_id = :order_id', ['order_id' => $orderId]);
            
            // ลบ order items
            $this->db->delete('order_items', 'order_id = :order_id', ['order_id' => $orderId]);
            
            // ลบ order
            $this->db->delete('orders', 'order_id = :order_id', ['order_id' => $orderId]);
            
            // commit transaction
            $this->db->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'ลบคำสั่งซื้อสำเร็จ'
            ]);
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบคำสั่งซื้อ: ' . $e->getMessage()
            ]);
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