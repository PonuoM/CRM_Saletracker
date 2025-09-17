<?php
/**
 * Call Service
 * จัดการการโทรติดตามลูกค้า
 */

require_once __DIR__ . '/../core/Database.php';

class CallService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * บันทึกการโทร
     * @param array $data ข้อมูลการโทร
     * @return array ผลลัพธ์การบันทึก
     */
    public function logCall($data) {
        try {
            $this->db->beginTransaction();
            
            // ตรวจสอบลูกค้า
            $customer = $this->db->fetchOne(
                "SELECT * FROM customers WHERE customer_id = :customer_id",
                ['customer_id' => $data['customer_id']]
            );
            
            if (!$customer) {
                return ['success' => false, 'message' => 'ไม่พบลูกค้า'];
            }
            
            // คำนวณวันติดตามตามกฎ
            $followupDate = $this->calculateFollowupDate($data['call_result']);
            
            // บันทึกการโทร
            // Build call log payload (with display labels)
            $statusLabelMap = [
                'answered' => 'รับสาย',
                'got_talk' => 'ได้คุย',
                'no_answer' => 'ไม่รับสาย',
                'busy' => 'สายไม่ว่าง',
                'hang_up' => 'ตัดสายทิ้ง',
                'no_signal' => 'ไม่มีสัญญาณ',
                'invalid' => 'เบอร์ไม่ถูกต้อง',
                'invaild' => 'เบอร์ไม่ถูกต้อง',
            ];
            $resultLabelMap = [
                'interested' => 'สนใจ',
                'not_interested' => 'ไม่สนใจ',
                'callback' => 'ติดต่อนัด/โทรกลับ',
                'order' => 'สั่งซื้อ',
                'complaint' => 'ร้องเรียน',
            ];
            $statusDisplay = $statusLabelMap[$data['call_status']] ?? (preg_match('/[\x{0E00}-\x{0E7F}]/u', (string)$data['call_status']) ? $data['call_status'] : null);
            $resultDisplay = $resultLabelMap[$data['call_result']] ?? (preg_match('/[\x{0E00}-\x{0E7F}]/u', (string)$data['call_result']) ? $data['call_result'] : null);

            $callData = [
                'customer_id' => $data['customer_id'],
                'user_id' => $data['user_id'],
                'call_type' => $data['call_type'] ?? 'outbound',
                'call_status' => $data['call_status'],
                'call_result' => $data['call_result'],
                'duration_minutes' => $data['duration_minutes'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'next_action' => $data['next_action'] ?? null,
                'next_followup_at' => $followupDate,
                'followup_notes' => $data['followup_notes'] ?? null,
                'followup_days' => $this->getFollowupDays($data['call_result']),
                'followup_priority' => $this->getFollowupPriority($data['call_result']),
                'plant_variety' => $data['plant_variety'] ?? null,
                'garden_size' => $data['garden_size'] ?? null,
                'status_display' => $statusDisplay,
                'result_display' => $resultDisplay,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $callLogId = $this->db->insert('call_logs', $callData);
            
            // อัปเดตข้อมูลลูกค้า
            $this->updateCustomerAfterCall($data['customer_id'], $data['call_result'], $followupDate);
            
            // บันทึกกิจกรรม
            $this->logCallActivity($data['customer_id'], $data['user_id'], $callData);
            
            // สร้างคิวการติดตามถ้าจำเป็น
            if ($followupDate) {
                $this->createFollowupQueue($data['customer_id'], $callLogId, $data['user_id'], $followupDate);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'call_log_id' => $callLogId,
                'followup_date' => $followupDate,
                'message' => 'บันทึกการโทรสำเร็จ'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * คำนวณวันติดตามตามผลการโทร
     * @param string $callResult ผลการโทร
     * @return string|null วันติดตาม
     */
    private function calculateFollowupDate($callResult) {
        $rule = $this->db->fetchOne(
            "SELECT followup_days FROM call_followup_rules WHERE call_result = ? AND is_active = 1",
            [$callResult]
        );
        
        if ($rule && $rule['followup_days'] > 0) {
            return date('Y-m-d H:i:s', strtotime("+{$rule['followup_days']} days"));
        }
        
        return null;
    }
    
    /**
     * ดึงจำนวนวันติดตาม
     * @param string $callResult ผลการโทร
     * @return int จำนวนวัน
     */
    private function getFollowupDays($callResult) {
        $rule = $this->db->fetchOne(
            "SELECT followup_days FROM call_followup_rules WHERE call_result = ? AND is_active = 1",
            [$callResult]
        );
        
        return $rule ? $rule['followup_days'] : 0;
    }
    
    /**
     * ดึงความสำคัญของการติดตาม
     * @param string $callResult ผลการโทร
     * @return string ความสำคัญ
     */
    private function getFollowupPriority($callResult) {
        $rule = $this->db->fetchOne(
            "SELECT priority FROM call_followup_rules WHERE call_result = ? AND is_active = 1",
            [$callResult]
        );
        
        return $rule ? $rule['priority'] : 'medium';
    }
    
    /**
     * อัปเดตข้อมูลลูกค้าหลังการโทร
     * @param int $customerId ID ลูกค้า
     * @param string $callResult ผลการโทร
     * @param string|null $followupDate วันติดตาม
     */
    private function updateCustomerAfterCall($customerId, $callResult, $followupDate) {
        $updateData = [
            'last_contact_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // อัปเดตสถานะลูกค้า
        if (in_array($callResult, ['not_interested', 'callback', 'interested', 'complaint'])) {
            $updateData['customer_status'] = 'call_followup';
        }
        
        // อัปเดต next_followup_at ถ้ามีวันติดตาม
        if ($followupDate) {
            $updateData['next_followup_at'] = $followupDate;
        }
        
        $this->db->update('customers', $updateData, 'customer_id = :customer_id', ['customer_id' => $customerId]);
    }
    
    /**
     * บันทึกกิจกรรมการโทร
     * @param int $customerId ID ลูกค้า
     * @param int $userId ID ผู้ใช้
     * @param array $callData ข้อมูลการโทร
     */
    private function logCallActivity($customerId, $userId, $callData) {
        $activityData = [
            'customer_id' => $customerId,
            'user_id' => $userId,
            'activity_type' => 'call',
            'activity_date' => date('Y-m-d'),
            'activity_description' => $this->generateCallDescription($callData),
            'old_value' => null,
            'new_value' => $callData['call_result'],
            'metadata' => json_encode([
                'call_status' => $callData['call_status'],
                'duration' => $callData['duration_minutes'],
                'followup_date' => $callData['next_followup_at']
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('customer_activities', $activityData);
    }
    
    /**
     * สร้างคำอธิบายการโทร
     * @param array $callData ข้อมูลการโทร
     * @return string คำอธิบาย
     */
    private function generateCallDescription($callData) {
        $statusText = [
            'answered' => 'รับสาย',
            'no_answer' => 'ไม่รับสาย',
            'busy' => 'สายไม่ว่าง',
            'invalid' => 'เบอร์ไม่ถูกต้อง'
        ];
        
        $resultText = [
            'product_not_out' => 'สินค้ายังไม่หมด',
            'no_result' => 'ใช้แล้วไม่เห็นผล',
            'not_tried' => 'ยังไม่ได้ลองใช้',
            'not_ready_to_use' => 'ยังไม่ถึงรอบใช้งาน',
            'ordered_elsewhere' => 'สั่งช่องทางอื่นแล้ว',
            'not_convenient' => 'ไม่สะดวกคุย',
            'hang_up' => 'ตัดสายทิ้ง',
            'order_for_others' => 'ฝากสั่งไม่ได้ใช้เอง',
            'someone_else_answered' => 'คนอื่นรับสายแทน',
            'stopped_gardening' => 'เลิกทำสวน',
            'not_interested' => 'ไม่สนใจ',
            'do_not_call' => 'ห้ามติดต่อ',
            'got_talk' => 'ได้คุย',
            'sold' => 'ขายได้',
            // Keep old mappings for backward compatibility
            'interested' => 'สนใจ',
            'callback' => 'ขอโทรกลับ',
            'order' => 'สั่งซื้อ',
            'complaint' => 'ร้องเรียน'
        ];
        
        $status = $statusText[$callData['call_status']] ?? $callData['call_status'];
        $result = $resultText[$callData['call_result']] ?? $callData['call_result'];
        
        $description = "โทรติดต่อ: {$status}, ผล: {$result}";
        
        if ($callData['duration_minutes'] > 0) {
            $description .= " (ใช้เวลา {$callData['duration_minutes']} นาที)";
        }
        
        if ($callData['next_followup_at']) {
            $description .= " - ติดตามวันที่ " . date('d/m/Y', strtotime($callData['next_followup_at']));
        }
        
        return $description;
    }
    
    /**
     * สร้างคิวการติดตาม
     * @param int $customerId ID ลูกค้า
     * @param int $callLogId ID การโทร
     * @param int $userId ID ผู้ใช้
     * @param string $followupDate วันติดตาม
     */
    private function createFollowupQueue($customerId, $callLogId, $userId, $followupDate) {
        // ตรวจสอบว่ามีคิวอยู่แล้วหรือไม่
        $existingQueue = $this->db->fetchOne(
            "SELECT queue_id FROM call_followup_queue WHERE customer_id = ? AND status = 'pending'",
            [$customerId]
        );
        
        if (!$existingQueue) {
            $queueData = [
                'customer_id' => $customerId,
                'call_log_id' => $callLogId,
                'user_id' => $userId,
                'followup_date' => date('Y-m-d', strtotime($followupDate)),
                'priority' => $this->getFollowupPriority($callLogId),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('call_followup_queue', $queueData);
        }
    }
    
    /**
     * ดึงประวัติการโทรของลูกค้า
     * @param int $customerId ID ลูกค้า
     * @param int $limit จำนวนรายการ
     * @return array ประวัติการโทร
     */
    public function getCallHistory($customerId, $limit = 10) {
        $sql = "SELECT cl.*, u.full_name as user_name
                FROM call_logs cl
                LEFT JOIN users u ON cl.user_id = u.user_id
                WHERE cl.customer_id = :customer_id
                ORDER BY cl.created_at DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['customer_id' => $customerId, 'limit' => $limit]);
    }
    
    /**
     * ดึงลูกค้าที่ต้องติดตามการโทร
     * @param int $userId ID ผู้ใช้ (ถ้าเป็น null จะดึงทั้งหมด)
     * @param array $filters ตัวกรอง
     * @return array รายการลูกค้า
     */
    public function getCallFollowupCustomers($userId = null, $filters = []) {
        $sql = "SELECT * FROM customer_call_followup_list WHERE 1=1";
        $params = [];
        
        if ($userId) {
            $sql .= " AND assigned_to = :user_id";
            $params['user_id'] = $userId;
        }
        
        // ตัวกรองตามสถานะความเร่งด่วน
        if (!empty($filters['urgency'])) {
            switch ($filters['urgency']) {
                case 'overdue':
                    $sql .= " AND urgency_status = 'overdue'";
                    break;
                case 'urgent':
                    $sql .= " AND urgency_status = 'urgent'";
                    break;
                case 'soon':
                    $sql .= " AND urgency_status = 'soon'";
                    break;
            }
        }
        
        // ตัวกรองตามผลการโทร
        if (!empty($filters['call_result'])) {
            $sql .= " AND call_result = :call_result";
            $params['call_result'] = $filters['call_result'];
        }
        
        // ตัวกรองตามความสำคัญ
        if (!empty($filters['priority'])) {
            $sql .= " AND followup_priority = :priority";
            $params['priority'] = $filters['priority'];
        }
        
        $sql .= " ORDER BY next_followup_at ASC, followup_priority DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * อัปเดตสถานะคิวการติดตาม
     * @param int $queueId ID คิว
     * @param string $status สถานะใหม่
     * @param string $notes หมายเหตุ
     * @return array ผลลัพธ์
     */
    public function updateFollowupQueueStatus($queueId, $status, $notes = null) {
        try {
            $updateData = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($notes) {
                $updateData['notes'] = $notes;
            }
            
            $this->db->update('call_followup_queue', $updateData, 'queue_id = :queue_id', ['queue_id' => $queueId]);
            
            return ['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ดึงสถิติการโทร
     * @param int $userId ID ผู้ใช้ (ถ้าเป็น null จะดึงทั้งหมด)
     * @param string $period ช่วงเวลา (today, week, month)
     * @return array สถิติ
     */
    public function getCallStats($userId = null, $period = 'month') {
        $dateCondition = $this->getDateCondition($period);
        
        $sql = "SELECT 
                    call_result,
                    call_status,
                    COUNT(*) as total_calls,
                    AVG(duration_minutes) as avg_duration,
                    COUNT(CASE WHEN next_followup_at IS NOT NULL THEN 1 END) as need_followup
                FROM call_logs 
                WHERE created_at >= :start_date";
        
        $params = ['start_date' => $dateCondition];
        
        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " GROUP BY call_result, call_status";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * สร้างเงื่อนไขวันที่
     * @param string $period ช่วงเวลา
     * @return string วันที่เริ่มต้น
     */
    private function getDateCondition($period) {
        switch ($period) {
            case 'today':
                return date('Y-m-d 00:00:00');
            case 'week':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'month':
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
            default:
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
    }
}
?>
