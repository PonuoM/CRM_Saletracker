<?php
/**
 * Customer Transfer Service
 * จัดการการโอนย้ายลูกค้าระหว่างพนักงานขาย
 */

require_once __DIR__ . '/../core/Database.php';

class CustomerTransferService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * โอนย้ายลูกค้าระหว่างพนักงานขาย
     */
    public function transferCustomers($sourceTelesalesId, $targetTelesalesId, $customerIds, $reason, $transferredBy) {
        try {
            $this->db->beginTransaction();
            
            $transferredCount = 0;
            $errors = [];
            
            foreach ($customerIds as $customerId) {
                try {
                    // ตรวจสอบว่าลูกค้าอยู่กับ source telesales จริงหรือไม่
                    $customer = $this->db->fetchOne(
                        "SELECT customer_id, assigned_to, customer_status, first_name, last_name 
                         FROM customers 
                         WHERE customer_id = ? AND assigned_to = ?",
                        [$customerId, $sourceTelesalesId]
                    );
                    
                    if (!$customer) {
                        $errors[] = "ลูกค้า ID {$customerId} ไม่ได้อยู่กับพนักงานขายต้นทาง";
                        continue;
                    }
                    
                    // ตรวจสอบว่าพนักงานปลายทางเคยขายให้ลูกค้านี้หรือไม่
                    $hasRecentSales = $this->checkRecentSales($customerId, $targetTelesalesId);
                    
                    // กำหนดสถานะลูกค้าใหม่
                    $newStatus = $this->determineNewCustomerStatus($customerId, $targetTelesalesId, $hasRecentSales);
                    
                    // อัปเดตข้อมูลลูกค้า
                    $this->db->execute(
                        "UPDATE customers 
                         SET assigned_to = ?, 
                             assigned_at = NOW(), 
                             customer_status = ?,
                             updated_at = NOW()
                         WHERE customer_id = ?",
                        [$targetTelesalesId, $newStatus, $customerId]
                    );
                    
                    // บันทึกประวัติการโอนย้าย
                    $this->logTransfer($customerId, $sourceTelesalesId, $targetTelesalesId, $reason, $transferredBy, $newStatus);
                    
                    $transferredCount++;
                    
                } catch (Exception $e) {
                    $errors[] = "เกิดข้อผิดพลาดในการโอนลูกค้า ID {$customerId}: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'transferred_count' => $transferredCount,
                'errors' => $errors,
                'message' => "โอนย้ายลูกค้า {$transferredCount} คนสำเร็จ"
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโอนย้าย: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ตรวจสอบว่าพนักงานเคยขายให้ลูกค้าในช่วง 3 เดือนล่าสุดหรือไม่
     */
    private function checkRecentSales($customerId, $telesalesId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as sales_count
                 FROM orders 
                 WHERE customer_id = ? 
                   AND created_by = ? 
                   AND payment_status IN ('paid', 'partial')
                   AND order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
                [$customerId, $telesalesId]
            );
            
            return ($result['sales_count'] ?? 0) > 0;
            
        } catch (Exception $e) {
            error_log("Error checking recent sales: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * กำหนดสถานะลูกค้าใหม่ตามหลักการที่ถูกต้อง
     */
    private function determineNewCustomerStatus($customerId, $telesalesId, $hasRecentSales) {
        try {
            // ถ้าพนักงานเคยขายให้ลูกค้าในช่วง 3 เดือนล่าสุด = existing_3m
            if ($hasRecentSales) {
                return 'existing_3m';
            }
            
            // ถ้าไม่เคยขาย = ตรวจสอบว่าลูกค้ามีประวัติการขายหรือไม่
            $hasAnySales = $this->db->fetchOne(
                "SELECT COUNT(*) as sales_count
                 FROM orders 
                 WHERE customer_id = ? 
                   AND payment_status IN ('paid', 'partial')
                   AND order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
                [$customerId]
            );
            
            if (($hasAnySales['sales_count'] ?? 0) > 0) {
                // มีการขายในช่วง 3 เดือน แต่ไม่ใช่พนักงานคนนี้ = existing
                return 'existing';
            } else {
                // ไม่มีการขายในช่วง 3 เดือน = new
                return 'new';
            }
            
        } catch (Exception $e) {
            error_log("Error determining customer status: " . $e->getMessage());
            return 'new'; // default to new if error
        }
    }
    
    /**
     * บันทึกประวัติการโอนย้าย
     */
    private function logTransfer($customerId, $sourceTelesalesId, $targetTelesalesId, $reason, $transferredBy, $newStatus) {
        try {
            $this->db->execute(
                "INSERT INTO customer_transfers (
                    customer_id, 
                    source_telesales_id, 
                    target_telesales_id, 
                    reason, 
                    transferred_by, 
                    new_status,
                    transferred_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$customerId, $sourceTelesalesId, $targetTelesalesId, $reason, $transferredBy, $newStatus]
            );
        } catch (Exception $e) {
            error_log("Error logging transfer: " . $e->getMessage());
        }
    }
    
    /**
     * ดึงรายการพนักงานขาย
     */
    public function getTelesalesList($companyId = null) {
        try {
            $sql = "SELECT user_id, full_name, username, email, phone
                    FROM users 
                    WHERE role_id = 4 AND is_active = 1";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY full_name ASC";
            
            return $this->db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Error getting telesales list: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงสถิติพนักงานขาย
     */
    public function getTelesalesStats($telesalesId) {
        try {
            $stats = $this->db->fetchOne(
                "SELECT 
                    COUNT(*) as total_customers,
                    SUM(CASE WHEN customer_status = 'new' THEN 1 ELSE 0 END) as new_customers,
                    SUM(CASE WHEN customer_status = 'existing' THEN 1 ELSE 0 END) as existing_customers,
                    SUM(CASE WHEN customer_status = 'existing_3m' THEN 1 ELSE 0 END) as existing_3m_customers,
                    SUM(CASE WHEN customer_status = 'followup' THEN 1 ELSE 0 END) as followup_customers
                 FROM customers 
                 WHERE assigned_to = ? AND is_active = 1",
                [$telesalesId]
            );
            
            return $stats ?: [
                'total_customers' => 0,
                'new_customers' => 0,
                'existing_customers' => 0,
                'existing_3m_customers' => 0,
                'followup_customers' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Error getting telesales stats: " . $e->getMessage());
            return [
                'total_customers' => 0,
                'new_customers' => 0,
                'existing_customers' => 0,
                'existing_3m_customers' => 0,
                'followup_customers' => 0
            ];
        }
    }
    
    /**
     * ดึงรายการลูกค้าของพนักงานขาย
     */
    public function getCustomerList($telesalesId, $page = 1, $limit = 20, $search = '', $grade = '', $status = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT customer_id, first_name, last_name, phone, customer_status, customer_grade
                    FROM customers 
                    WHERE assigned_to = ? AND is_active = 1";
            
            $params = [$telesalesId];
            
            if (!empty($search)) {
                $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($grade)) {
                $sql .= " AND customer_grade = ?";
                $params[] = $grade;
            }
            
            if (!empty($status)) {
                $sql .= " AND customer_status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY first_name, last_name ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $customers = $this->db->fetchAll($sql, $params);
            
            // นับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) as total FROM customers WHERE assigned_to = ? AND is_active = 1";
            $countParams = [$telesalesId];
            
            if (!empty($search)) {
                $countSql .= " AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
                $searchTerm = "%{$search}%";
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
            }
            
            if (!empty($grade)) {
                $countSql .= " AND customer_grade = ?";
                $countParams[] = $grade;
            }
            
            if (!empty($status)) {
                $countSql .= " AND customer_status = ?";
                $countParams[] = $status;
            }
            
            $totalResult = $this->db->fetchOne($countSql, $countParams);
            $total = $totalResult['total'] ?? 0;
            
            return [
                'customers' => $customers,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error getting customer list: " . $e->getMessage());
            return [
                'customers' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }
    
    /**
     * ค้นหาลูกค้าสำหรับการโอนย้าย
     */
    public function searchCustomers($sourceTelesalesId, $searchTerm, $grade = '', $limit = 20) {
        try {
            $sql = "SELECT customer_id, first_name, last_name, phone, customer_status, customer_grade
                    FROM customers 
                    WHERE assigned_to = ? AND is_active = 1
                      AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
            
            $params = [$sourceTelesalesId];
            $searchPattern = "%{$searchTerm}%";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            
            if (!empty($grade)) {
                $sql .= " AND customer_grade = ?";
                $params[] = $grade;
            }
            
            $sql .= " ORDER BY first_name, last_name ASC LIMIT ?";
            $params[] = $limit;
            
            return $this->db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Error searching customers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงประวัติการโอนย้าย
     */
    public function getTransferHistory($limit = 50, $companyId = null) {
        try {
            $sql = "SELECT 
                        ct.*,
                        c.first_name, c.last_name, c.phone,
                        s.full_name as source_telesales_name,
                        t.full_name as target_telesales_name,
                        u.full_name as transferred_by_name
                    FROM customer_transfers ct
                    LEFT JOIN customers c ON ct.customer_id = c.customer_id
                    LEFT JOIN users s ON ct.source_telesales_id = s.user_id
                    LEFT JOIN users t ON ct.target_telesales_id = t.user_id
                    LEFT JOIN users u ON ct.transferred_by = u.user_id";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " WHERE c.company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY ct.transferred_at DESC LIMIT ?";
            $params[] = $limit;
            
            return $this->db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Error getting transfer history: " . $e->getMessage());
            return [];
        }
    }
}
?>
