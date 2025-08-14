<?php
/**
 * CustomerService Class
 * จัดการการดำเนินการเกี่ยวกับลูกค้า การมอบหมาย และระบบตะกร้า
 */

require_once __DIR__ . '/../core/Database.php';

class CustomerService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * มอบหมายลูกค้าให้กับ Telesales
     * @param int $supervisorId ID ของ Supervisor ที่ทำการมอบหมาย
     * @param int $telesalesId ID ของ Telesales ที่จะได้รับมอบหมาย
     * @param array $customerIds รายการ ID ของลูกค้าที่จะมอบหมาย
     * @return array ผลลัพธ์การมอบหมาย
     */
    public function assignCustomers($supervisorId, $telesalesId, $customerIds) {
        try {
            $this->db->beginTransaction();
            
            $assignedCount = 0;
            $errors = [];
            
            foreach ($customerIds as $customerId) {
                // ตรวจสอบว่าลูกค้าอยู่ในตะกร้าแจกหรือไม่
                $customer = $this->db->fetchOne(
                    "SELECT * FROM customers WHERE customer_id = :customer_id AND basket_type = 'distribution'",
                    ['customer_id' => $customerId]
                );
                
                if (!$customer) {
                    $errors[] = "ลูกค้า ID {$customerId} ไม่สามารถมอบหมายได้ (ไม่อยู่ในตะกร้าแจก)";
                    continue;
                }
                
                // อัปเดตสถานะลูกค้า
                $updateData = [
                    'assigned_to' => $telesalesId,
                    'basket_type' => 'assigned',
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'recall_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
                ];
                
                $this->db->update('customers', $updateData, 'customer_id = :customer_id', ['customer_id' => $customerId]);
                
                // บันทึกประวัติการมอบหมาย
                $this->logAssignmentHistory($customerId, $telesalesId, $supervisorId);
                
                // บันทึกกิจกรรม
                $this->logCustomerActivity($customerId, $supervisorId, 'assignment', 
                    "ลูกค้าถูกมอบหมายให้ Telesales ID: {$telesalesId}");
                
                $assignedCount++;
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'assigned_count' => $assignedCount,
                'errors' => $errors,
                'message' => "มอบหมายลูกค้า {$assignedCount} รายการสำเร็จ"
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการมอบหมาย: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงลูกค้ากลับจาก Telesales
     * @param int $customerId ID ของลูกค้า
     * @param string $reason เหตุผลการดึงกลับ
     * @param int $userId ID ของผู้ใช้ที่ทำการดึงกลับ
     * @return array ผลลัพธ์การดึงกลับ
     */
    public function recallCustomer($customerId, $reason, $userId) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT * FROM customers WHERE customer_id = :customer_id",
                ['customer_id' => $customerId]
            );
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบลูกค้า'];
            }
            
            $oldAssignedTo = $customer['assigned_to'];
            
            // อัปเดตสถานะลูกค้า
            $updateData = [
                'assigned_to' => null,
                'basket_type' => 'waiting',
                'recall_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('customers', $updateData, 'customer_id = :customer_id', ['customer_id' => $customerId]);
            
            // บันทึกประวัติการมอบหมาย (ปิดการมอบหมายปัจจุบัน)
            if ($oldAssignedTo) {
                $this->closeAssignmentHistory($customerId, $oldAssignedTo, $reason);
            }
            
            // บันทึกกิจกรรม
            $this->logCustomerActivity($customerId, $userId, 'recall', "ลูกค้าถูกดึงกลับ: {$reason}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'ดึงลูกค้ากลับสำเร็จ'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงกลับ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตสถานะลูกค้า
     * @param int $customerId ID ของลูกค้า
     * @param string $status สถานะใหม่
     * @param string $notes หมายเหตุ
     * @param int $userId ID ของผู้ใช้ที่ทำการอัปเดต
     * @return array ผลลัพธ์การอัปเดต
     */
    public function updateCustomerStatus($customerId, $status, $notes, $userId) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT * FROM customers WHERE customer_id = :customer_id",
                ['customer_id' => $customerId]
            );
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบลูกค้า'];
            }
            
            $oldStatus = $customer['temperature_status'];
            
            // อัปเดตสถานะ
            $updateData = [
                'temperature_status' => $status,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('customers', $updateData, 'customer_id = :customer_id', ['customer_id' => $customerId]);
            
            // บันทึกกิจกรรม
            $this->logCustomerActivity($customerId, $userId, 'status_change', 
                "เปลี่ยนสถานะจาก {$oldStatus} เป็น {$status}: {$notes}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'อัปเดตสถานะลูกค้าสำเร็จ'
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
     * ดึงลูกค้าตามตะกร้า
     * @param string $basketType ประเภทตะกร้า (distribution, waiting, assigned)
     * @param array $filters ตัวกรองเพิ่มเติม
     * @return array รายการลูกค้า
     */
    public function getCustomersByBasket($basketType, $filters = []) {
        $sql = "SELECT c.*, u.full_name as assigned_to_name 
                FROM customers c 
                LEFT JOIN users u ON c.assigned_to = u.user_id 
                WHERE c.is_active = 1";
        
        $params = [];
        
        // เงื่อนไข basket_type: ถ้าเป็น 'all' จะไม่กรองตาม basket
        if (!empty($basketType) && $basketType !== 'all') {
            $sql .= " AND c.basket_type = ?";
            $params[] = $basketType;
        }
        
        // เพิ่มตัวกรอง
        if (!empty($filters['temperature'])) {
            $sql .= " AND c.temperature_status = ?";
            $params[] = $filters['temperature'];
        }
        
        if (!empty($filters['grade'])) {
            $sql .= " AND c.customer_grade = ?";
            $params[] = $filters['grade'];
        }
        
        if (!empty($filters['province'])) {
            $sql .= " AND c.province = ?";
            $params[] = $filters['province'];
        }
        
        if (!empty($filters['name'])) {
            $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
            $searchTerm = '%' . $filters['name'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['phone'])) {
            $sql .= " AND c.phone LIKE ?";
            $params[] = '%' . $filters['phone'] . '%';
        }
        
        if (!empty($filters['customer_status'])) {
            $sql .= " AND c.customer_status = ?";
            $params[] = $filters['customer_status'];
        }
        
        if (!empty($filters['assigned_to'])) {
            if (is_array($filters['assigned_to'])) {
                // สำหรับ supervisor ที่ส่ง array ของ user_id
                $placeholders = str_repeat('?,', count($filters['assigned_to']) - 1) . '?';
                $sql .= " AND c.assigned_to IN ($placeholders)";
                $params = array_merge($params, $filters['assigned_to']);
            } else {
                // สำหรับ telesales ที่ส่ง user_id เดียว
                $sql .= " AND c.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * ดึงลูกค้าที่ต้องติดตาม (Do section สำหรับ Telesales และ Supervisor)
     * @param int $userId ID ของ Telesales หรือ Supervisor
     * @return array รายการลูกค้าที่ต้องติดตาม
     */
    public function getFollowUpCustomers($userId, $filters = []) {
        $sql = "SELECT c.*, u.full_name as assigned_to_name,
                       DATEDIFF(c.customer_time_expiry, NOW()) as days_remaining,
                       DATEDIFF(c.next_followup_at, NOW()) as followup_days,
                       CASE
                           WHEN c.next_followup_at IS NOT NULL THEN 'appointment'
                           WHEN c.customer_status = 'new' THEN 'new'
                           WHEN c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiry'
                           ELSE 'other'
                       END as reason_type
                FROM customers c
                LEFT JOIN users u ON c.assigned_to = u.user_id
                WHERE c.assigned_to = :user_id
                AND c.basket_type = 'assigned'
                AND c.is_active = 1
                AND (
                    c.next_followup_at IS NOT NULL OR
                    c.customer_status = 'new' OR
                    c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                )
                ";

        $params = ['user_id' => $userId];
        if (!empty($filters['temperature'])) {
            $sql .= " AND c.temperature_status = :temp"; $params['temp'] = $filters['temperature'];
        }
        if (!empty($filters['grade'])) {
            $sql .= " AND c.customer_grade = :grade"; $params['grade'] = $filters['grade'];
        }
        if (!empty($filters['province'])) {
            $sql .= " AND c.province = :province"; $params['province'] = $filters['province'];
        }
        if (!empty($filters['name'])) {
            $sql .= " AND (c.first_name LIKE :name OR c.last_name LIKE :name OR CONCAT(c.first_name, ' ', c.last_name) LIKE :name)"; $params['name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['phone'])) {
            $sql .= " AND c.phone LIKE :phone"; $params['phone'] = '%' . $filters['phone'] . '%';
        }

        $sql .= " ORDER BY 
                    (c.next_followup_at IS NULL) ASC,
                    c.next_followup_at ASC,
                    (c.customer_status <> 'new') ASC,
                    c.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * บันทึกประวัติการมอบหมาย
     * @param int $customerId ID ของลูกค้า
     * @param int $telesalesId ID ของ Telesales
     * @param int $supervisorId ID ของ Supervisor
     */
    private function logAssignmentHistory($customerId, $telesalesId, $supervisorId) {
        $data = [
            'customer_id' => $customerId,
            'user_id' => $telesalesId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'assigned_by' => $supervisorId,
            'is_current' => 1
        ];
        
        $this->db->insert('sales_history', $data);
    }
    
    /**
     * ปิดประวัติการมอบหมาย
     * @param int $customerId ID ของลูกค้า
     * @param int $telesalesId ID ของ Telesales
     * @param string $reason เหตุผลการปิด
     */
    private function closeAssignmentHistory($customerId, $telesalesId, $reason) {
        // ปิดการมอบหมายปัจจุบัน
        $this->db->update('sales_history', 
            [
                'unassigned_at' => date('Y-m-d H:i:s'),
                'reason' => $reason,
                'is_current' => 0
            ],
            'customer_id = :customer_id AND user_id = :user_id AND is_current = 1',
            ['customer_id' => $customerId, 'user_id' => $telesalesId]
        );
    }
    
    /**
     * บันทึกกิจกรรมลูกค้า
     * @param int $customerId ID ของลูกค้า
     * @param int $userId ID ของผู้ใช้
     * @param string $activityType ประเภทกิจกรรม
     * @param string $description รายละเอียด
     */
    private function logCustomerActivity($customerId, $userId, $activityType, $description) {
        $data = [
            'customer_id' => $customerId,
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('customer_activities', $data);
    }
    
    /**
     * คำนวณเกรดลูกค้าตามยอดซื้อ
     * @param int $customerId ID ของลูกค้า
     * @return string เกรดลูกค้า
     */
    public function calculateCustomerGrade($customerId) {
        // ดึงเกณฑ์เกรดจาก system_settings
        $gradeSettings = $this->getGradeSettings();
        
        // ดึงยอดซื้อรวมของลูกค้า
        $sql = "SELECT COALESCE(SUM(o.net_amount), 0) as total_amount 
                FROM orders o 
                WHERE o.customer_id = :customer_id AND o.payment_status = 'paid'";
        
        $result = $this->db->fetchOne($sql, ['customer_id' => $customerId]);
        $totalAmount = $result['total_amount'];
        
        // คำนวณเกรด
        if ($totalAmount >= $gradeSettings['a_plus']) {
            return 'A+';
        } elseif ($totalAmount >= $gradeSettings['a']) {
            return 'A';
        } elseif ($totalAmount >= $gradeSettings['b']) {
            return 'B';
        } elseif ($totalAmount >= $gradeSettings['c']) {
            return 'C';
        } else {
            return 'D';
        }
    }
    
    /**
     * อัปเดตเกรดลูกค้า
     * @param int $customerId ID ของลูกค้า
     * @return array ผลลัพธ์การอัปเดต
     */
    public function updateCustomerGrade($customerId) {
        try {
            $newGrade = $this->calculateCustomerGrade($customerId);
            
            $this->db->update('customers', 
                ['customer_grade' => $newGrade], 
                'customer_id = :customer_id', 
                ['customer_id' => $customerId]
            );
            
            return [
                'success' => true,
                'grade' => $newGrade,
                'message' => "อัปเดตเกรดลูกค้าเป็น {$newGrade}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตเกรด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงเกณฑ์เกรดจาก system_settings
     * @return array เกณฑ์เกรด
     */
    private function getGradeSettings() {
        $sql = "SELECT setting_key, setting_value FROM system_settings 
                WHERE setting_key LIKE 'customer_grade_%'";
        
        $settings = $this->db->fetchAll($sql);
        $gradeSettings = [];
        
        foreach ($settings as $setting) {
            $key = str_replace('customer_grade_', '', $setting['setting_key']);
            $gradeSettings[$key] = (float) $setting['setting_value'];
        }
        
        return $gradeSettings;
    }
    
    /**
     * อัปเดตสถานะอุณหภูมิลูกค้า
     * @param int $customerId ID ของลูกค้า
     * @return array ผลลัพธ์การอัปเดต
     */
    public function updateCustomerTemperature($customerId) {
        try {
            // ดึงข้อมูลลูกค้าและประวัติการซื้อ
            $sql = "SELECT c.*, 
                           MAX(o.order_date) as last_order_date,
                           COUNT(o.order_id) as total_orders,
                           SUM(o.net_amount) as total_amount
                    FROM customers c
                    LEFT JOIN orders o ON c.customer_id = o.customer_id AND o.payment_status = 'paid'
                    WHERE c.customer_id = :customer_id
                    GROUP BY c.customer_id";
            
            $customer = $this->db->fetchOne($sql, ['customer_id' => $customerId]);
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบลูกค้า'];
            }
            
            $newTemperature = $this->calculateTemperatureStatus($customer);
            
            $this->db->update('customers', 
                ['temperature_status' => $newTemperature], 
                'customer_id = :customer_id', 
                ['customer_id' => $customerId]
            );
            
            return [
                'success' => true,
                'temperature' => $newTemperature,
                'message' => "อัปเดตสถานะอุณหภูมิเป็น {$newTemperature}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * คำนวณสถานะอุณหภูมิ
     * @param array $customer ข้อมูลลูกค้า
     * @return string สถานะอุณหภูมิ
     */
    private function calculateTemperatureStatus($customer) {
        $createdDays = (time() - strtotime($customer['created_at'])) / (60 * 60 * 24);
        $lastOrderDays = $customer['last_order_date'] ? 
            (time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24) : null;
        
        // Hot: ลูกค้าใหม่ (30 วัน) หรือลูกค้าเกรด A+ ที่ซื้อใน 60 วัน
        if ($createdDays <= 30 || 
            ($customer['customer_grade'] == 'A+' && $lastOrderDays && $lastOrderDays <= 60)) {
            return 'hot';
        }
        
        // Warm: ลูกค้าที่ซื้อใน 180 วัน
        if ($lastOrderDays && $lastOrderDays <= 180) {
            return 'warm';
        }
        
        // Cold: ลูกค้าเก่าที่มีประวัติการซื้อ
        if ($customer['total_orders'] > 0) {
            return 'cold';
        }
        
        // Frozen: ลูกค้าที่ไม่มีกิจกรรม
        return 'frozen';
    }
}
?> 