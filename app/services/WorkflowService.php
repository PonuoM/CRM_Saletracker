<?php
/**
 * WorkflowService Class
 * จัดการระบบ Workflow สำหรับการเรียกข้อมูลลูกค้าคืนและต่อเวลา
 */

require_once __DIR__ . '/../core/Database.php';

class WorkflowService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * ดึงสถิติ Workflow
     */
    public function getWorkflowStats() {
        try {
            // ลูกค้าที่ต้อง Recall
            $pendingRecall = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE (basket_type = 'assigned' AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
                 OR (basket_type = 'assigned' AND customer_id IN (
                     SELECT customer_id FROM orders 
                     GROUP BY customer_id 
                     HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
                 ))"
            );
            
            // ลูกค้าใหม่เกิน 30 วัน
            $newCustomerTimeout = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE basket_type = 'assigned' 
                 AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                 AND customer_id NOT IN (
                     SELECT DISTINCT customer_id FROM orders 
                     WHERE created_at > assigned_at
                 )
                 AND customer_id NOT IN (
                     SELECT DISTINCT customer_id FROM appointments 
                     WHERE created_at > assigned_at
                 )"
            );
            
            // ลูกค้าเก่าเกิน 90 วัน
            $existingCustomerTimeout = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE basket_type = 'assigned'
                 AND customer_id IN (
                     SELECT customer_id FROM orders 
                     GROUP BY customer_id 
                     HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
                 )"
            );
            
            // ลูกค้า Active วันนี้
            $activeToday = $this->db->fetchOne(
                "SELECT COUNT(DISTINCT customer_id) as count FROM (
                     SELECT customer_id FROM orders WHERE DATE(created_at) = CURDATE()
                     UNION
                     SELECT customer_id FROM appointments WHERE DATE(created_at) = CURDATE()
                     UNION
                     SELECT customer_id FROM call_logs WHERE DATE(created_at) = CURDATE()
                 ) as active_customers"
            );
            
            return [
                'pending_recall' => $pendingRecall['count'] ?? 0,
                'new_customer_timeout' => $newCustomerTimeout['count'] ?? 0,
                'existing_customer_timeout' => $existingCustomerTimeout['count'] ?? 0,
                'active_today' => $activeToday['count'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Error getting workflow stats: " . $e->getMessage());
            return [
                'pending_recall' => 0,
                'new_customer_timeout' => 0,
                'existing_customer_timeout' => 0,
                'active_today' => 0
            ];
        }
    }
    
    /**
     * ดึงรายการลูกค้าใหม่ที่เกิน 30 วัน
     */
    public function getNewCustomerTimeout($limit = 10) {
        try {
            $sql = "
                SELECT c.*, u.full_name as assigned_user_name,
                       DATEDIFF(NOW(), c.assigned_at) as days_overdue
                FROM customers c
                LEFT JOIN users u ON c.assigned_to = u.user_id
                WHERE c.basket_type = 'assigned' 
                AND c.assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND c.customer_id NOT IN (
                    SELECT DISTINCT customer_id FROM orders 
                    WHERE created_at > c.assigned_at
                )
                AND c.customer_id NOT IN (
                    SELECT DISTINCT customer_id FROM appointments 
                    WHERE created_at > c.assigned_at
                )
                ORDER BY c.assigned_at ASC
                LIMIT ?
            ";
            
            return $this->db->fetchAll($sql, [$limit]);
            
        } catch (Exception $e) {
            error_log("Error getting new customer timeout: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงรายการลูกค้าเก่าที่เกิน 90 วัน
     */
    public function getExistingCustomerTimeout($limit = 10) {
        try {
            $sql = "
                SELECT c.*, u.full_name as assigned_user_name,
                       DATEDIFF(NOW(), o.last_order_date) as days_overdue,
                       o.last_order_date
                FROM customers c
                LEFT JOIN users u ON c.assigned_to = u.user_id
                LEFT JOIN (
                    SELECT customer_id, MAX(order_date) as last_order_date
                    FROM orders 
                    GROUP BY customer_id
                ) o ON c.customer_id = o.customer_id
                WHERE c.basket_type = 'assigned'
                AND o.last_order_date < DATE_SUB(NOW(), INTERVAL 90 DAY)
                ORDER BY o.last_order_date ASC
                LIMIT ?
            ";
            
            return $this->db->fetchAll($sql, [$limit]);
            
        } catch (Exception $e) {
            error_log("Error getting existing customer timeout: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงกิจกรรมล่าสุด
     */
    public function getRecentActivities($limit = 20) {
        try {
            $sql = "
                SELECT 'order' as activity_type, 
                       o.order_id as id,
                       c.customer_name,
                       CONCAT('คำสั่งซื้อ #', o.order_id) as description,
                       o.created_at,
                       u.full_name as user_name
                FROM orders o
                JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN users u ON o.created_by = u.user_id
                
                UNION ALL
                
                SELECT 'appointment' as activity_type,
                       a.appointment_id as id,
                       c.customer_name,
                       CONCAT('นัดหมาย: ', a.title) as description,
                       a.created_at,
                       u.full_name as user_name
                FROM appointments a
                JOIN customers c ON a.customer_id = c.customer_id
                LEFT JOIN users u ON a.user_id = u.user_id
                
                UNION ALL
                
                SELECT 'recall' as activity_type,
                       ca.activity_id as id,
                       c.customer_name,
                       ca.description,
                       ca.created_at,
                       u.full_name as user_name
                FROM customer_activities ca
                JOIN customers c ON ca.customer_id = c.customer_id
                LEFT JOIN users u ON ca.user_id = u.user_id
                WHERE ca.activity_type = 'recall'
                
                ORDER BY created_at DESC
                LIMIT ?
            ";
            
            return $this->db->fetchAll($sql, [$limit]);
            
        } catch (Exception $e) {
            error_log("Error getting recent activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * รัน Manual Recall
     */
    public function runManualRecall() {
        try {
            $this->db->beginTransaction();
            
            $results = [
                'new_customers_recalled' => 0,
                'existing_customers_recalled' => 0,
                'moved_to_distribution' => 0
            ];
            
            // Recall ลูกค้าใหม่
            $sql1 = "
                UPDATE customers 
                SET basket_type = 'distribution',
                    assigned_to = NULL,
                    recall_at = NOW()
                WHERE basket_type = 'assigned'
                AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND customer_id NOT IN (
                    SELECT DISTINCT customer_id FROM orders 
                    WHERE created_at > assigned_at
                )
                AND customer_id NOT IN (
                    SELECT DISTINCT customer_id FROM appointments 
                    WHERE created_at > assigned_at
                )
            ";
            
            $results['new_customers_recalled'] = $this->db->execute($sql1);
            
            // Recall ลูกค้าเก่า
            $sql2 = "
                UPDATE customers 
                SET basket_type = 'waiting',
                    assigned_to = NULL,
                    recall_at = NOW()
                WHERE basket_type = 'assigned'
                AND customer_id IN (
                    SELECT customer_id FROM orders 
                    GROUP BY customer_id 
                    HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
                )
            ";
            
            $results['existing_customers_recalled'] = $this->db->execute($sql2);
            
            // ย้ายจาก waiting ไป distribution
            $sql3 = "
                UPDATE customers 
                SET basket_type = 'distribution'
                WHERE basket_type = 'waiting'
                AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            $results['moved_to_distribution'] = $this->db->execute($sql3);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'รัน Manual Recall สำเร็จ',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error running manual recall: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ต่อเวลาลูกค้า
     */
    public function extendCustomerTime($customerId, $extensionDays, $reason, $userId) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT * FROM customers WHERE customer_id = ?",
                [$customerId]
            );
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบลูกค้า'];
            }
            
            // คำนวณวันที่ใหม่
            $newDate = date('Y-m-d H:i:s', strtotime("+{$extensionDays} days"));
            
            // อัปเดตวันที่
            if ($customer['basket_type'] === 'assigned') {
                $this->db->update('customers', 
                    ['assigned_at' => $newDate], 
                    'customer_id = ?', 
                    [$customerId]
                );
            }
            
            // บันทึกกิจกรรม
            $this->logActivity($customerId, $userId, 'extend_time', 
                "ต่อเวลา {$extensionDays} วัน: {$reason}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "ต่อเวลาลูกค้าสำเร็จ {$extensionDays} วัน"
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error extending customer time: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ต่อเวลาอัตโนมัติเมื่อมีการ Active
     */
    public function autoExtendTimeOnActivity($customerId, $activityType, $userId) {
        try {
            // Don't use transactions for this function to avoid deadlocks
            // This function will be called from within OrderService transaction
            
            // ตรวจสอบลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT * FROM customers WHERE customer_id = ?",
                [$customerId]
            );
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบข้อมูลลูกค้า'];
            }
            
            // สำหรับการสร้างคำสั่งซื้อ ให้ต่อเวลาได้ทุกประเภท basket_type
            // สำหรับกิจกรรมอื่นๆ ต้องเป็น assigned เท่านั้น
            if ($activityType !== 'order' && $customer['basket_type'] !== 'assigned') {
                return ['success' => false, 'message' => 'ลูกค้าไม่พร้อมต่อเวลา (ต้องเป็น assigned สำหรับกิจกรรมนี้)'];
            }
            
            $extensionDays = 0;
            $reason = '';
            
            // กำหนดจำนวนวันที่ต่อตามประเภทกิจกรรม
            switch ($activityType) {
                case 'appointment':
                    $extensionDays = 30;
                    $reason = 'สร้างนัดหมายใหม่';
                    break;
                    
                case 'order':
                    $extensionDays = 90;
                    $reason = 'มีการขายใหม่';
                    break;
                    
                case 'call_positive':
                    $extensionDays = 30;
                    $reason = 'การโทรผลลัพธ์ดี';
                    break;
                    
                default:
                    $extensionDays = 30;
                    $reason = 'กิจกรรมใหม่';
                    break;
            }
            
            // คำนวณวันที่ใหม่
            $newDate = date('Y-m-d H:i:s', strtotime("+{$extensionDays} days"));
            
            // ดึงข้อมูลลูกค้าปัจจุบันเพื่อคำนวณ customer_time_expiry
            $currentCustomer = $this->db->fetchOne(
                "SELECT customer_time_expiry FROM customers WHERE customer_id = ?",
                [$customerId]
            );
            
            // คำนวณ customer_time_expiry ใหม่ - เริ่มใหม่จากวันนี้เสมอ (ไม่สแต็ก)
            $newExpiry = date('Y-m-d H:i:s', strtotime("+{$extensionDays} days"));
            
            // อัปเดตวันที่
            $this->db->update('customers', 
                [
                    'assigned_at' => $newDate,
                    'customer_time_expiry' => $newExpiry,
                    'customer_time_extension' => $extensionDays
                ], 
                'customer_id = ?', 
                [$customerId]
            );
            
            // บันทึกกิจกรรม
            $this->logActivity($customerId, $userId, 'auto_extend', 
                "ต่อเวลาอัตโนมัติ {$extensionDays} วัน: {$reason}");
            
            return [
                'success' => true,
                'message' => "ต่อเวลาอัตโนมัติสำเร็จ {$extensionDays} วัน",
                'extension_days' => $extensionDays
            ];
            
        } catch (Exception $e) {
            error_log("Error auto extending time: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * บันทึกกิจกรรม
     */
    private function logActivity($customerId, $userId, $activityType, $description) {
        try {
            $this->db->insert('customer_activities', [
                'customer_id' => $customerId,
                'user_id' => $userId,
                'activity_type' => $activityType,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    /**
     * ดึงรายการลูกค้าที่พร้อมต่อเวลา
     */
    public function getCustomersForExtension() {
        try {
            $sql = "
                SELECT c.customer_id, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.phone, c.email,
                       u.full_name as assigned_user_name,
                       c.assigned_at,
                       DATEDIFF(NOW(), c.assigned_at) as days_assigned
                FROM customers c
                LEFT JOIN users u ON c.assigned_to = u.user_id
                WHERE c.basket_type = 'assigned'
                AND c.assigned_to IS NOT NULL
                ORDER BY c.first_name ASC, c.last_name ASC
            ";
            
            return $this->db->fetchAll($sql);
            
        } catch (Exception $e) {
            error_log("Error getting customers for extension: " . $e->getMessage());
            return [];
        }
    }
} 