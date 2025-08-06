<?php
/**
 * OrderService Class
 * จัดการการดำเนินการเกี่ยวกับคำสั่งซื้อ การสร้าง และการติดตาม
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/CustomerService.php';

class OrderService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * สร้างคำสั่งซื้อใหม่
     * @param array $orderData ข้อมูลคำสั่งซื้อ
     * @param array $orderItems รายการสินค้าในคำสั่งซื้อ
     * @param int $createdBy ID ของ Telesales ที่สร้างคำสั่งซื้อ
     * @return array ผลลัพธ์การสร้างคำสั่งซื้อ
     */
    public function createOrder($orderData, $orderItems, $createdBy) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT * FROM customers WHERE customer_id = :customer_id",
                ['customer_id' => $orderData['customer_id']]
            );
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบลูกค้า'];
            }
            
            // สร้างหมายเลขคำสั่งซื้อ
            $orderNumber = $this->generateOrderNumber();
            
            // คำนวณยอดรวม
            $totalAmount = 0;
            foreach ($orderItems as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            
            // คำนวณส่วนลด
            $discountAmount = 0;
            if (isset($orderData['discount_percentage']) && $orderData['discount_percentage'] > 0) {
                $discountAmount = ($totalAmount * $orderData['discount_percentage']) / 100;
            } elseif (isset($orderData['discount_amount'])) {
                $discountAmount = $orderData['discount_amount'];
            }
            
            $netAmount = $totalAmount - $discountAmount;
            
            // สร้างคำสั่งซื้อ
            $orderInsertData = [
                'order_number' => $orderNumber,
                'customer_id' => $orderData['customer_id'],
                'created_by' => $createdBy,
                'order_date' => date('Y-m-d'),
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'discount_percentage' => $orderData['discount_percentage'] ?? 0,
                'net_amount' => $netAmount,
                'payment_method' => $orderData['payment_method'] ?? 'cash',
                'payment_status' => $orderData['payment_status'] ?? 'pending',
                'delivery_date' => $orderData['delivery_date'] ?? null,
                'delivery_address' => $orderData['delivery_address'] ?? null,
                'delivery_status' => $orderData['delivery_status'] ?? 'pending',
                'notes' => $orderData['notes'] ?? null
            ];
            
            $orderId = $this->db->insert('orders', $orderInsertData);
            
            if (!$orderId) {
                throw new Exception('ไม่สามารถสร้างคำสั่งซื้อได้');
            }
            
            // เพิ่มรายการสินค้า
            foreach ($orderItems as $item) {
                $itemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ];
                
                $this->db->insert('order_items', $itemData);
            }
            
            // อัปเดตประวัติการซื้อของลูกค้า
            $this->updateCustomerPurchaseHistory($orderData['customer_id'], $netAmount);
            
            // รีเซ็ตตัวนับการต่อเวลาการนัดหมายเมื่อมีการขาย
            $this->resetAppointmentExtensionOnSale($orderData['customer_id'], $createdBy, $orderId);
            
            // บันทึกกิจกรรม
            $this->logOrderActivity($orderId, $createdBy, 'created', 
                "สร้างคำสั่งซื้อใหม่ หมายเลข: {$orderNumber}");
            
            $this->db->commit();
            
            // ต่อเวลาอัตโนมัติเมื่อมีการสร้างคำสั่งซื้อ (หลังจาก commit แล้ว)
            try {
                require_once __DIR__ . '/WorkflowService.php';
                $workflowService = new WorkflowService();
                $workflowResult = $workflowService->autoExtendTimeOnActivity($orderData['customer_id'], 'order', $createdBy);
                
                if ($workflowResult['success']) {
                    error_log("Auto time extension successful for customer {$orderData['customer_id']}: {$workflowResult['message']}");
                } else {
                    error_log("Auto time extension failed for customer {$orderData['customer_id']}: {$workflowResult['message']}");
                }
            } catch (Exception $e) {
                error_log("Error in auto time extension: " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'message' => 'สร้างคำสั่งซื้อสำเร็จ'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงรายการคำสั่งซื้อ
     * @param array $filters ตัวกรองข้อมูล
     * @param int $page หน้าปัจจุบัน
     * @param int $limit จำนวนรายการต่อหน้า
     * @return array รายการคำสั่งซื้อ
     */
    public function getOrders($filters = [], $page = 1, $limit = 20) {
        try {
            $whereConditions = ['1=1'];
            $params = [];
            
            // ตัวกรองตามลูกค้า
            if (!empty($filters['customer_id'])) {
                $whereConditions[] = 'o.customer_id = :customer_id';
                $params['customer_id'] = $filters['customer_id'];
            }
            
            // ตัวกรองตามผู้สร้าง
            if (!empty($filters['created_by'])) {
                $whereConditions[] = 'o.created_by = :created_by';
                $params['created_by'] = $filters['created_by'];
            }
            
            // ตัวกรองตามสถานะการชำระเงิน
            if (!empty($filters['payment_status'])) {
                $whereConditions[] = 'o.payment_status = :payment_status';
                $params['payment_status'] = $filters['payment_status'];
            }
            
            // ตัวกรองตามสถานะการจัดส่ง
            if (!empty($filters['delivery_status'])) {
                $whereConditions[] = 'o.delivery_status = :delivery_status';
                $params['delivery_status'] = $filters['delivery_status'];
            }
            
            // ตัวกรองตามช่วงวันที่
            if (!empty($filters['date_from'])) {
                $whereConditions[] = 'o.order_date >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = 'o.order_date <= :date_to';
                $params['date_to'] = $filters['date_to'];
            }
            
            // ตัวกรองตามหมายเลขคำสั่งซื้อ
            if (!empty($filters['order_number'])) {
                $whereConditions[] = 'o.order_number LIKE :order_number';
                $params['order_number'] = '%' . $filters['order_number'] . '%';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // นับจำนวนทั้งหมด
            $countQuery = "SELECT COUNT(*) as total FROM orders o WHERE {$whereClause}";
            $totalResult = $this->db->fetchOne($countQuery, $params);
            $total = $totalResult['total'];
            
            // คำนวณ offset
            $offset = ($page - 1) * $limit;
            
            // ดึงข้อมูล
            $query = "
                SELECT 
                    o.*,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    c.phone,
                    u.username as created_by_name,
                    COALESCE(item_counts.item_count, 0) as item_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN users u ON o.created_by = u.user_id
                LEFT JOIN (
                    SELECT order_id, COUNT(*) as item_count
                    FROM order_items
                    GROUP BY order_id
                ) as item_counts ON o.order_id = item_counts.order_id
                WHERE {$whereClause}
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            $orders = $this->db->fetchAll($query, $params);
            
            return [
                'success' => true,
                'orders' => $orders,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงรายละเอียดคำสั่งซื้อ
     * @param int $orderId ID ของคำสั่งซื้อ
     * @return array รายละเอียดคำสั่งซื้อ
     */
    public function getOrderDetail($orderId) {
        try {
            // ดึงข้อมูลคำสั่งซื้อ
            $orderQuery = "
                SELECT 
                    o.*,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    c.phone,
                    c.email,
                    c.address,
                    u.username as created_by_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN users u ON o.created_by = u.user_id
                WHERE o.order_id = :order_id
            ";
            
            $order = $this->db->fetchOne($orderQuery, ['order_id' => $orderId]);
            
            if (!$order) {
                return ['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ'];
            }
            
            // ดึงรายการสินค้า
            $itemsQuery = "
                SELECT 
                    oi.*,
                    p.product_name,
                    p.product_code,
                    p.unit
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id
            ";
            
            $items = $this->db->fetchAll($itemsQuery, ['order_id' => $orderId]);
            
            // คำนวณยอดรวมและส่วนลด
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            
            $discount = $order['discount_amount'] ?? 0;
            $netAmount = $order['net_amount'] ?? $subtotal;
            
            // เพิ่มข้อมูลที่คำนวณแล้ว
            $order['subtotal'] = $subtotal;
            $order['discount'] = $discount;
            $order['net_amount'] = $netAmount;
            
            return [
                'success' => true,
                'order' => $order,
                'items' => $items
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตคำสั่งซื้อ
     * @param array $orderData ข้อมูลคำสั่งซื้อที่ต้องการอัปเดต
     * @param array $orderItems รายการสินค้าใหม่
     * @return array ผลลัพธ์การอัปเดต
     */
    public function updateOrder($orderData, $orderItems) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบคำสั่งซื้อ
            $existingOrder = $this->db->fetchOne(
                "SELECT * FROM orders WHERE order_id = :order_id",
                ['order_id' => $orderData['order_id']]
            );
            
            if (!$existingOrder) {
                return ['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ'];
            }
            
            // คำนวณยอดรวมใหม่
            $totalAmount = 0;
            foreach ($orderItems as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            
            // คำนวณส่วนลด
            $discountAmount = 0;
            if (isset($orderData['discount_percentage']) && $orderData['discount_percentage'] > 0) {
                $discountAmount = ($totalAmount * $orderData['discount_percentage']) / 100;
            } elseif (isset($orderData['discount_amount'])) {
                $discountAmount = $orderData['discount_amount'];
            }
            
            $netAmount = $totalAmount - $discountAmount;
            
            // อัปเดตข้อมูลคำสั่งซื้อ
            $updateData = [
                'customer_id' => $orderData['customer_id'],
                'order_date' => $orderData['order_date'],
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'discount_percentage' => $orderData['discount_percentage'] ?? 0,
                'net_amount' => $netAmount,
                'payment_method' => $orderData['payment_method'],
                'delivery_method' => $orderData['delivery_method'],
                'delivery_address' => $orderData['delivery_address'],
                'notes' => $orderData['notes'],
                'updated_by' => $orderData['updated_by'],
                'updated_at' => $orderData['updated_at']
            ];
            
            $this->db->update('orders', $updateData, 'order_id = :order_id', ['order_id' => $orderData['order_id']]);
            
            // ลบรายการสินค้าเก่า
            $this->db->execute(
                "DELETE FROM order_items WHERE order_id = :order_id",
                ['order_id' => $orderData['order_id']]
            );
            
            // เพิ่มรายการสินค้าใหม่
            foreach ($orderItems as $item) {
                $itemData = [
                    'order_id' => $orderData['order_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ];
                
                $this->db->insert('order_items', $itemData);
            }
            
            // อัปเดตประวัติการซื้อของลูกค้า
            $this->updateCustomerPurchaseHistory($orderData['customer_id'], $netAmount);
            
            // บันทึกกิจกรรม
            $this->logOrderActivity($orderData['order_id'], $orderData['updated_by'], 'updated', 
                "อัปเดตคำสั่งซื้อ หมายเลข: {$existingOrder['order_number']}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'อัปเดตคำสั่งซื้อสำเร็จ',
                'order_id' => $orderData['order_id']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order Update Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตคำสั่งซื้อ: ' . $e->getMessage()];
        }
    }
    
    /**
     * อัปเดตสถานะคำสั่งซื้อ
     * @param int $orderId ID ของคำสั่งซื้อ
     * @param string $field ชื่อฟิลด์ที่ต้องการอัปเดต
     * @param string $value ค่าใหม่
     * @param int $updatedBy ID ของผู้ใช้อัปเดต
     * @return array ผลลัพธ์การอัปเดต
     */
    public function updateOrderStatus($orderId, $field, $value, $updatedBy) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบคำสั่งซื้อ
            $order = $this->db->fetchOne(
                "SELECT * FROM orders WHERE order_id = :order_id",
                ['order_id' => $orderId]
            );
            
            if (!$order) {
                return ['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ'];
            }
            
            // อัปเดตสถานะ
            $updateData = [
                $field => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('orders', $updateData, 'order_id = :order_id', ['order_id' => $orderId]);
            
            // บันทึกกิจกรรม
            $this->logOrderActivity($orderId, $updatedBy, 'status_update', 
                "อัปเดต {$field} เป็น: {$value}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'อัปเดตสถานะสำเร็จ'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดต: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * สร้างหมายเลขคำสั่งซื้อ
     * @return string หมายเลขคำสั่งซื้อ
     */
    private function generateOrderNumber() {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return "{$prefix}{$date}{$random}";
    }
    
    /**
     * อัปเดตประวัติการซื้อของลูกค้า
     * @param int $customerId ID ของลูกค้า
     * @param float $amount จำนวนเงิน
     * @param bool $updateGrade อัปเดตเกรดลูกค้าหรือไม่ (default: false เพื่อเพิ่มประสิทธิภาพ)
     */
    private function updateCustomerPurchaseHistory($customerId, $amount, $updateGrade = false) {
        // อัปเดตยอดซื้อรวม
        $this->db->query(
            "UPDATE customers SET total_purchase = total_purchase + :amount WHERE customer_id = :customer_id",
            ['amount' => $amount, 'customer_id' => $customerId]
        );
        
        // อัปเดตเกรดลูกค้า (เฉพาะเมื่อต้องการ)
        if ($updateGrade) {
            try {
                $customerService = new CustomerService();
                $customerService->updateCustomerGrade($customerId);
            } catch (Exception $e) {
                // Log error but don't fail the order creation
                error_log("Failed to update customer grade: " . $e->getMessage());
            }
        }
    }
    
    /**
     * บันทึกกิจกรรมคำสั่งซื้อ
     * @param int $orderId ID ของคำสั่งซื้อ
     * @param int $userId ID ของผู้ใช้
     * @param string $activityType ประเภทกิจกรรม
     * @param string $description รายละเอียด
     */
    private function logOrderActivity($orderId, $userId, $activityType, $description) {
        $activityData = [
            'order_id' => $orderId,
            'user_id' => $userId,
            'activity_type' => $activityType,
            'description' => $description
        ];
        
        $this->db->insert('order_activities', $activityData);
    }
    
    /**
     * รีเซ็ตตัวนับการต่อเวลาการนัดหมายเมื่อมีการขาย
     */
    private function resetAppointmentExtensionOnSale($customerId, $userId, $orderId) {
        try {
            // เรียกใช้ AppointmentExtensionService
            require_once __DIR__ . '/AppointmentExtensionService.php';
            $extensionService = new AppointmentExtensionService();
            
            $result = $extensionService->resetExtensionOnSale($customerId, $userId, $orderId);
            
            error_log("Reset appointment extension on sale result: " . print_r($result, true));
            
        } catch (Exception $e) {
            error_log('Error resetting appointment extension on sale: ' . $e->getMessage());
        }
    }
    
    /**
     * ดึงรายการสินค้า
     * @param array $filters ตัวกรอง
     * @return array รายการสินค้า
     */
    public function getProducts($filters = []) {
        try {
            $whereConditions = ['is_active = 1'];
            $params = [];
            
            if (!empty($filters['category'])) {
                $whereConditions[] = 'category = :category';
                $params['category'] = $filters['category'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = '(product_name LIKE :search OR product_code LIKE :search)';
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $query = "SELECT * FROM products WHERE {$whereClause} ORDER BY product_name";
            $products = $this->db->fetchAll($query, $params);
            
            return [
                'success' => true,
                'products' => $products
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ส่งออกข้อมูลคำสั่งซื้อ
     * @param array $filters ตัวกรอง
     * @return array ข้อมูลสำหรับส่งออก
     */
    public function exportOrders($filters = []) {
        try {
            $result = $this->getOrders($filters, 1, 10000); // ดึงทั้งหมด
            
            if (!$result['success']) {
                return $result;
            }
            
            $exportData = [];
            foreach ($result['orders'] as $order) {
                $exportData[] = [
                    'order_number' => $order['order_number'],
                    'order_date' => $order['order_date'],
                    'customer_name' => $order['customer_name'],
                    'phone' => $order['phone'],
                    'total_amount' => $order['total_amount'],
                    'discount_amount' => $order['discount_amount'],
                    'net_amount' => $order['net_amount'],
                    'payment_method' => $order['payment_method'],
                    'payment_status' => $order['payment_status'],
                    'delivery_status' => $order['delivery_status'],
                    'created_by' => $order['created_by_name']
                ];
            }
            
            return [
                'success' => true,
                'data' => $exportData,
                'total' => count($exportData)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการส่งออกข้อมูล: ' . $e->getMessage()
            ];
        }
    }
}
?> 