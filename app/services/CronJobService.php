<?php
/**
 * Cron Job Service
 * จัดการงานอัตโนมัติของระบบ
 */

require_once __DIR__ . '/../core/Database.php';

class CronJobService {
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/../../logs/cron.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * อัปเดตเกรดลูกค้าอัตโนมัติตามยอดซื้อ
     */
    public function updateCustomerGrades() {
        $this->log("Starting customer grade update...");
        
        try {
            // คำนวณยอดซื้อของลูกค้าในช่วง 6 เดือนที่ผ่านมา (ใช้ total_purchase_amount จากตาราง customers)
            $sql = "SELECT 
                        c.customer_id as id,
                        CONCAT(c.first_name, ' ', c.last_name) as name,
                        c.customer_grade as current_grade,
                        COALESCE(c.total_purchase_amount, 0) as total_purchase_6months
                    FROM customers c
                    WHERE c.is_active = 1";
            
            $stmt = $this->db->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updatedCount = 0;
            $gradeChanges = [];
            
            foreach ($customers as $customer) {
                $newGrade = $this->calculateGrade($customer['total_purchase_6months']);
                
                if ($newGrade !== $customer['current_grade']) {
                    // อัปเดตเกรดในฐานข้อมูล
                    $updateSql = "UPDATE customers SET customer_grade = ?, updated_at = NOW() WHERE customer_id = ?";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([$newGrade, $customer['id']]);
                    
                    // บันทึกการเปลี่ยนแปลงเกรด
                    $this->logGradeChange($customer['id'], $customer['current_grade'], $newGrade, $customer['total_purchase_6months']);
                    
                    $gradeChanges[] = [
                        'customer_id' => $customer['id'],
                        'customer_name' => $customer['name'],
                        'old_grade' => $customer['current_grade'],
                        'new_grade' => $newGrade,
                        'total_purchase' => $customer['total_purchase_6months']
                    ];
                    
                    $updatedCount++;
                }
            }
            
            $this->log("Customer grade update completed. Updated: {$updatedCount} customers");
            
            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'changes' => $gradeChanges
            ];
            
        } catch (Exception $e) {
            $this->log("Error updating customer grades: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตสถานะลูกค้าอัตโนมัติ (Hot/Warm/Cold/Frozen)
     */
    public function updateCustomerTemperatures() {
        $this->log("Starting customer temperature update...");
        
        try {
            // คำนวณอุณหภูมิลูกค้าตามการติดต่อล่าสุด
            $sql = "SELECT 
                        c.customer_id as id,
                        CONCAT(c.first_name, ' ', c.last_name) as name,
                        c.temperature_status as current_temperature,
                        COALESCE(c.last_contact_at, c.created_at) as last_contact,
                        DATEDIFF(NOW(), COALESCE(c.last_contact_at, c.created_at)) as days_since_contact
                    FROM customers c
                    WHERE c.is_active = 1";
            
            $stmt = $this->db->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updatedCount = 0;
            $temperatureChanges = [];
            
            foreach ($customers as $customer) {
                $newTemperature = $this->calculateTemperature($customer['days_since_contact']);
                
                if ($newTemperature !== $customer['current_temperature']) {
                    // อัปเดตอุณหภูมิในฐานข้อมูล
                    $updateSql = "UPDATE customers SET temperature_status = ?, updated_at = NOW() WHERE customer_id = ?";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([$newTemperature, $customer['id']]);
                    
                    // บันทึกการเปลี่ยนแปลงอุณหภูมิ
                    $this->logTemperatureChange($customer['id'], $customer['current_temperature'], $newTemperature, $customer['days_since_contact']);
                    
                    $temperatureChanges[] = [
                        'customer_id' => $customer['id'],
                        'customer_name' => $customer['name'],
                        'old_temperature' => $customer['current_temperature'],
                        'new_temperature' => $newTemperature,
                        'days_since_contact' => $customer['days_since_contact'],
                        'last_contact' => $customer['last_contact']
                    ];
                    
                    $updatedCount++;
                }
            }
            
            $this->log("Customer temperature update completed. Updated: {$updatedCount} customers");
            
            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'changes' => $temperatureChanges
            ];
            
        } catch (Exception $e) {
            $this->log("Error updating customer temperatures: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * สร้างรายการลูกค้าที่ต้องติดตามผล (Customer Recall)
     */
    public function createCustomerRecallList() {
        $this->log("Creating customer recall list...");
        
        try {
            // หาลูกค้าที่ไม่ได้ติดต่อนานเกิน 30 วัน
            $sql = "SELECT 
                        c.customer_id as id,
                        CONCAT(c.first_name, ' ', c.last_name) as name,
                        c.phone,
                        c.email,
                        c.temperature_status as temperature,
                        c.customer_grade as grade,
                        COALESCE(c.last_contact_at, c.created_at) as last_contact,
                        DATEDIFF(NOW(), COALESCE(c.last_contact_at, c.created_at)) as days_since_contact,
                        COALESCE(c.total_purchase_amount, 0) as total_spent
                    FROM customers c
                    WHERE c.is_active = 1
                    HAVING days_since_contact >= 30
                    ORDER BY 
                        CASE c.customer_grade 
                            WHEN 'A+' THEN 1 
                            WHEN 'A' THEN 2 
                            WHEN 'B' THEN 3 
                            WHEN 'C' THEN 4 
                            WHEN 'D' THEN 5 
                        END,
                        days_since_contact DESC";
            
            $stmt = $this->db->query($sql);
            $recallCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // บันทึกรายการลูกค้าที่ต้องติดตาม
            if (!empty($recallCustomers)) {
                $this->saveRecallList($recallCustomers);
            }
            
            $this->log("Customer recall list created. Found: " . count($recallCustomers) . " customers");
            
            return [
                'success' => true,
                'recall_count' => count($recallCustomers),
                'customers' => $recallCustomers
            ];
            
        } catch (Exception $e) {
            $this->log("Error creating customer recall list: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * ส่งการแจ้งเตือนสำหรับลูกค้าที่ต้องติดตาม
     */
    public function sendCustomerRecallNotifications() {
        $this->log("Sending customer recall notifications...");
        
        try {
            // หาผู้ใช้ที่เป็น telesales และ supervisor
            $sql = "SELECT 
                        u.user_id as id,
                        u.full_name as name,
                        u.email,
                        r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE r.role_name IN ('telesales', 'supervisor')
                    AND u.is_active = 1";
            
            $stmt = $this->db->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // สร้างข้อมูลการแจ้งเตือน
            $notifications = [];
            
            // หาลูกค้าที่ต้องติดตาม (จากฟังก์ชันก่อนหน้า)
            $recallData = $this->createCustomerRecallList();
            
            if ($recallData['success'] && $recallData['recall_count'] > 0) {
                foreach ($users as $user) {
                    $notification = [
                        'user_id' => $user['id'],
                        'type' => 'customer_recall',
                        'title' => 'มีลูกค้าที่ต้องติดตาม',
                        'message' => "พบลูกค้า {$recallData['recall_count']} รายที่ไม่ได้ติดต่อมานานเกิน 30 วัน กรุณาติดตามผล",
                        'created_at' => date('Y-m-d H:i:s'),
                        'is_read' => 0
                    ];
                    
                    $notifications[] = $notification;
                    
                    // บันทึกการแจ้งเตือนในฐานข้อมูล
                    $this->saveNotification($notification);
                }
            }
            
            $this->log("Customer recall notifications sent to " . count($users) . " users");
            
            return [
                'success' => true,
                'notification_count' => count($notifications),
                'recipient_count' => count($users)
            ];
            
        } catch (Exception $e) {
            $this->log("Error sending customer recall notifications: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตการติดตามการโทร
     */
    public function updateCallFollowups() {
        $this->log("Starting call follow-up update...");
        
        try {
            // ดึงลูกค้าที่ต้องติดตาม
            $sql = "SELECT 
                        cl.customer_id,
                        cl.log_id,
                        c.assigned_to,
                        cl.next_followup_at,
                        cl.followup_priority
                    FROM call_logs cl
                    JOIN customers c ON cl.customer_id = c.customer_id
                    WHERE cl.next_followup_at IS NOT NULL
                        AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                        AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
                        AND NOT EXISTS (
                            SELECT 1 FROM call_followup_queue cfq 
                            WHERE cfq.customer_id = cl.customer_id 
                            AND cfq.status = 'pending'
                        )";
            
            $customers = $this->db->fetchAll($sql);
            
            $newQueues = 0;
            $overdueCount = 0;
            $urgentCount = 0;
            
            foreach ($customers as $customer) {
                // สร้างคิวการติดตาม
                $queueData = [
                    'customer_id' => $customer['customer_id'],
                    'call_log_id' => $customer['log_id'],
                    'user_id' => $customer['assigned_to'],
                    'followup_date' => date('Y-m-d', strtotime($customer['next_followup_at'])),
                    'priority' => $customer['followup_priority'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert('call_followup_queue', $queueData);
                $newQueues++;
                
                // นับจำนวนเกินกำหนดและเร่งด่วน
                $followupDate = strtotime($customer['next_followup_at']);
                $today = time();
                
                if ($followupDate <= $today) {
                    $overdueCount++;
                } elseif ($followupDate <= strtotime('+3 days')) {
                    $urgentCount++;
                }
            }
            
            $this->log("Call follow-up update completed. New queues: {$newQueues}, Overdue: {$overdueCount}, Urgent: {$urgentCount}");
            
            return [
                'success' => true,
                'new_queues' => $newQueues,
                'overdue_count' => $overdueCount,
                'urgent_count' => $urgentCount
            ];
            
        } catch (Exception $e) {
            $this->log("Error updating call follow-ups: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * ทำความสะอาดข้อมูลเก่า (Data Cleanup)
     */
    public function cleanupOldData() {
        $this->log("Starting data cleanup...");
        
        try {
            $cleanupResults = [];
            
            // ลบ log เก่าที่เก็บไว้นานเกิน 90 วัน
            $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
            $stmt = $this->db->query($sql);
            $cleanupResults['deleted_logs'] = $stmt->rowCount();
            
            // ลบการแจ้งเตือนเก่าที่อ่านแล้วเกิน 30 วัน
            $sql = "DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->query($sql);
            $cleanupResults['deleted_notifications'] = $stmt->rowCount();
            
            // ลบไฟล์ backup เก่าที่เก็บไว้นานเกิน 30 วัน
            $backupDir = __DIR__ . '/../../backups/';
            $deletedBackups = 0;
            
            if (is_dir($backupDir)) {
                $files = glob($backupDir . '*.sql');
                foreach ($files as $file) {
                    if (filemtime($file) < strtotime('-30 days')) {
                        if (unlink($file)) {
                            $deletedBackups++;
                        }
                    }
                }
            }
            $cleanupResults['deleted_backups'] = $deletedBackups;
            
            $this->log("Data cleanup completed. Results: " . json_encode($cleanupResults));
            
            return [
                'success' => true,
                'cleanup_results' => $cleanupResults
            ];
            
        } catch (Exception $e) {
            $this->log("Error during data cleanup: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ระบบจัดการตะกร้าลูกค้าอัตโนมัติ (Customer Basket Management)
     */
    public function customerBasketManagement() {
        $this->log("Starting customer basket management...");

        try {
            $results = [
                'new_customers_recalled' => 0,
                'existing_customers_recalled' => 0,
                'moved_to_distribution' => 0
            ];

            // 1. ดึงลูกค้าใหม่ที่หมดเวลาถือครอง (>30 วัน) กลับไป distribution
            $sql1 = "
                UPDATE customers
                SET basket_type = 'distribution',
                    assigned_to = NULL,
                    assigned_at = NULL,
                    recall_at = NOW(),
                    recall_reason = 'new_customer_timeout'
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

            $stmt1 = $this->db->prepare($sql1);
            $stmt1->execute();
            $results['new_customers_recalled'] = $stmt1->rowCount();

            // 2. ดึงลูกค้าเก่าที่ไม่มีออเดอร์ใน 90 วัน ไปตะกร้ารอ (waiting)
            $sql2 = "
                UPDATE customers
                SET basket_type = 'waiting',
                    assigned_to = NULL,
                    assigned_at = NULL,
                    recall_at = NOW(),
                    recall_reason = 'existing_customer_timeout'
                WHERE basket_type = 'assigned'
                AND customer_id IN (
                    SELECT customer_id FROM (
                        SELECT customer_id
                        FROM orders
                        GROUP BY customer_id
                        HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
                    ) as old_customers
                )
            ";

            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute();
            $results['existing_customers_recalled'] = $stmt2->rowCount();

            // 3. ย้ายลูกค้าจากตะกร้ารอ (waiting) ไปตะกร้าพร้อมแจก (distribution) หลัง 30 วัน
            $sql3 = "
                UPDATE customers
                SET basket_type = 'distribution',
                    recall_at = NULL,
                    recall_reason = NULL
                WHERE basket_type = 'waiting'
                AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";

            $stmt3 = $this->db->prepare($sql3);
            $stmt3->execute();
            $results['moved_to_distribution'] = $stmt3->rowCount();

            // บันทึก activity logs
            if ($results['new_customers_recalled'] > 0) {
                $this->logBasketActivity('new_customer_recall', $results['new_customers_recalled']);
            }

            if ($results['existing_customers_recalled'] > 0) {
                $this->logBasketActivity('existing_customer_recall', $results['existing_customers_recalled']);
            }

            if ($results['moved_to_distribution'] > 0) {
                $this->logBasketActivity('waiting_to_distribution', $results['moved_to_distribution']);
            }

            $this->log("Customer basket management completed. New recalled: {$results['new_customers_recalled']}, Existing recalled: {$results['existing_customers_recalled']}, Moved to distribution: {$results['moved_to_distribution']}");

            return [
                'success' => true,
                'new_customers_recalled' => $results['new_customers_recalled'],
                'existing_customers_recalled' => $results['existing_customers_recalled'],
                'moved_to_distribution' => $results['moved_to_distribution']
            ];

        } catch (Exception $e) {
            $this->log("Error in customer basket management: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * เรียกใช้งาน Cron Jobs ทั้งหมด
     */
    public function runAllJobs() {
        $this->log("=== Starting all cron jobs ===");
        $startTime = microtime(true);
        
        $results = [];
        
        // 1. จัดการตะกร้าลูกค้าอัตโนมัติ (ย้ายลูกค้าระหว่างตะกร้า)
        $results['basket_management'] = $this->customerBasketManagement();

        // 2. อัปเดตเกรดลูกค้า
        $results['grade_update'] = $this->updateCustomerGrades();

        // 3. อัปเดตอุณหภูมิลูกค้า
        $results['temperature_update'] = $this->updateCustomerTemperatures();

        // 4. สร้างรายการลูกค้าที่ต้องติดตาม
        $results['recall_list'] = $this->createCustomerRecallList();

        // 5. ส่งการแจ้งเตือน
        $results['notifications'] = $this->sendCustomerRecallNotifications();

        // 6. อัปเดตการติดตามการโทร
        $results['call_followup'] = $this->updateCallFollowups();
        
        // 7. ทำความสะอาดข้อมูล (รันทุกวันอาทิตย์)
        if (date('w') == 0) { // Sunday
            $results['cleanup'] = $this->cleanupOldData();
        }
        
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        $this->log("=== All cron jobs completed in {$executionTime} seconds ===");
        
        return [
            'success' => true,
            'execution_time' => $executionTime,
            'results' => $results
        ];
    }
    
    /**
     * คำนวณเกรดลูกค้าตามยอดซื้อ
     */
    private function calculateGrade($totalPurchase) {
        if ($totalPurchase >= 100000) {
            return 'A+';
        } elseif ($totalPurchase >= 50000) {
            return 'A';
        } elseif ($totalPurchase >= 20000) {
            return 'B';
        } elseif ($totalPurchase >= 5000) {
            return 'C';
        } else {
            return 'D';
        }
    }
    
    /**
     * คำนวณอุณหภูมิลูกค้าตามวันที่ติดต่อล่าสุด
     */
    private function calculateTemperature($daysSinceContact) {
        if ($daysSinceContact <= 7) {
            return 'hot';
        } elseif ($daysSinceContact <= 30) {
            return 'warm';
        } elseif ($daysSinceContact <= 90) {
            return 'cold';
        } else {
            return 'frozen';
        }
    }
    
    /**
     * บันทึกการเปลี่ยนแปลงเกรด
     */
    private function logGradeChange($customerId, $oldGrade, $newGrade, $totalPurchase) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, activity_type, table_name, record_id, action, old_values, new_values, created_at) 
                    VALUES (NULL, 'grade_change', 'customers', ?, 'update', ?, ?, NOW())";
            
            $oldValues = json_encode(['customer_grade' => $oldGrade]);
            $newValues = json_encode([
                'customer_grade' => $newGrade,
                'total_purchase_6months' => $totalPurchase,
                'automated' => true
            ]);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$customerId, $oldValues, $newValues]);
        } catch (Exception $e) {
            $this->log("Error logging grade change: " . $e->getMessage());
        }
    }
    
    /**
     * บันทึกการเปลี่ยนแปลงอุณหภูมิ
     */
    private function logTemperatureChange($customerId, $oldTemperature, $newTemperature, $daysSinceContact) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, activity_type, table_name, record_id, action, old_values, new_values, created_at) 
                    VALUES (NULL, 'temperature_change', 'customers', ?, 'update', ?, ?, NOW())";
            
            $oldValues = json_encode(['temperature_status' => $oldTemperature]);
            $newValues = json_encode([
                'temperature_status' => $newTemperature,
                'days_since_contact' => $daysSinceContact,
                'automated' => true
            ]);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$customerId, $oldValues, $newValues]);
        } catch (Exception $e) {
            $this->log("Error logging temperature change: " . $e->getMessage());
        }
    }
    
    /**
     * บันทึกรายการลูกค้าที่ต้องติดตาม
     */
    private function saveRecallList($customers) {
        try {
            // ลบรายการเก่า
            $sql = "DELETE FROM customer_recall_list WHERE created_date = CURDATE()";
            $this->db->query($sql);
            
            // เพิ่มรายการใหม่
            $sql = "INSERT INTO customer_recall_list (customer_id, priority, days_since_contact, created_date) VALUES (?, ?, ?, CURDATE())";
            $stmt = $this->db->prepare($sql);
            
            foreach ($customers as $customer) {
                $priority = $this->calculateRecallPriority($customer['grade'], $customer['days_since_contact']);
                $stmt->execute([$customer['id'], $priority, $customer['days_since_contact']]);
            }
        } catch (Exception $e) {
            $this->log("Error saving recall list: " . $e->getMessage());
        }
    }
    
    /**
     * บันทึกการแจ้งเตือน
     */
    private function saveNotification($notification) {
        try {
            $sql = "INSERT INTO notifications (user_id, type, title, message, created_at, is_read) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $notification['user_id'],
                $notification['type'],
                $notification['title'],
                $notification['message'],
                $notification['created_at'],
                $notification['is_read']
            ]);
        } catch (Exception $e) {
            $this->log("Error saving notification: " . $e->getMessage());
        }
    }
    
    /**
     * คำนวณความสำคัญของการติดตาม
     */
    private function calculateRecallPriority($grade, $daysSinceContact) {
        $baseScore = 0;
        
        // คะแนนตามเกรด
        switch ($grade) {
            case 'A+': $baseScore += 100; break;
            case 'A': $baseScore += 80; break;
            case 'B': $baseScore += 60; break;
            case 'C': $baseScore += 40; break;
            case 'D': $baseScore += 20; break;
        }
        
        // คะแนนตามวันที่ไม่ได้ติดต่อ
        if ($daysSinceContact >= 90) {
            $baseScore += 50;
        } elseif ($daysSinceContact >= 60) {
            $baseScore += 30;
        } elseif ($daysSinceContact >= 30) {
            $baseScore += 10;
        }
        
        return min($baseScore, 150); // จำกัดคะแนนสูงสุดที่ 150
    }
    
    /**
     * บันทึกกิจกรรมการจัดการตะกร้า
     */
    private function logBasketActivity($activityType, $count) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, activity_type, table_name, action, description, created_at)
                    VALUES (NULL, 'basket_management', 'customers', ?, ?, NOW())";

            $descriptions = [
                'new_customer_recall' => "ดึงลูกค้าใหม่ {$count} รายกลับไปตะกร้าพร้อมแจก (หมดเวลาถือครอง 30 วัน)",
                'existing_customer_recall' => "ดึงลูกค้าเก่า {$count} รายไปตะกร้ารอ (ไม่มีออเดอร์ 90 วัน)",
                'waiting_to_distribution' => "ย้ายลูกค้า {$count} รายจากตะกร้ารอไปตะกร้าพร้อมแจก (รอครบ 30 วัน)"
            ];

            $description = $descriptions[$activityType] ?? "จัดการตะกร้าลูกค้า: {$activityType} ({$count} รายการ)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$activityType, $description]);

        } catch (Exception $e) {
            $this->log("Error logging basket activity: " . $e->getMessage());
        }
    }

    /**
     * บันทึก log
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // แสดงผลใน console ด้วย (ถ้ารันจาก command line)
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}