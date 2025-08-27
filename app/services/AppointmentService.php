<?php
/**
 * Appointment Service
 * จัดการข้อมูลนัดหมายและกิจกรรมที่เกี่ยวข้อง
 */

require_once __DIR__ . '/../core/Database.php';

class AppointmentService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * สร้างนัดหมายใหม่
     */
    public function createAppointment($data) {
        try {
            // Log input data for debugging
            error_log("AppointmentService - Input data: " . print_r($data, true));
            
            $sql = "INSERT INTO appointments (
                customer_id, user_id, appointment_date, appointment_type, 
                appointment_status, location, contact_person, contact_phone,
                title, description, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['customer_id'],
                $data['user_id'],
                $data['appointment_date'],
                $data['appointment_type'],
                $data['appointment_status'] ?? 'scheduled',
                $data['location'] ?? null,
                $data['contact_person'] ?? null,
                $data['contact_phone'] ?? null,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['notes'] ?? null
            ];
            
            // Log SQL and params for debugging
            error_log("AppointmentService - SQL: " . $sql);
            error_log("AppointmentService - Params: " . print_r($params, true));
            
            $result = $this->db->executeInsert($sql, $params);
            
            error_log("AppointmentService - Insert result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                $appointmentId = $this->db->lastInsertId();
                error_log("AppointmentService - Inserted ID: " . $appointmentId);
                
                // บันทึกกิจกรรม
                $this->logActivity($appointmentId, $data['user_id'], 'created', 'สร้างนัดหมายใหม่');

                // 🔄 SYNC: อัปเดต customers.next_followup_at เพื่อให้ sync กับ appointment
                try {
                    $this->db->query(
                        "UPDATE customers 
                         SET next_followup_at = ?, 
                             customer_status = CASE WHEN customer_status = 'new' THEN 'followup' ELSE customer_status END,
                             customer_time_expiry = LEAST(DATE_ADD(customer_time_expiry, INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 90 DAY)),
                             updated_at = NOW()
                         WHERE customer_id = ?",
                        [$data['appointment_date'], $data['customer_id']]
                    );
                } catch (Exception $e) {
                    error_log('Failed to update customer followup info: ' . $e->getMessage());
                }
                
                return [
                    'success' => true,
                    'appointment_id' => $appointmentId,
                    'message' => 'สร้างนัดหมายสำเร็จ'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างนัดหมาย'
            ];
            
        } catch (Exception $e) {
            error_log("AppointmentService - Exception: " . $e->getMessage());
            error_log("AppointmentService - Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตนัดหมาย
     */
    public function updateAppointment($appointmentId, $data) {
        try {
            // ดึงข้อมูลนัดหมายปัจจุบันเพื่อเปรียบเทียบ
            $currentAppointment = $this->getAppointmentById($appointmentId);
            if (!$currentAppointment['success']) {
                return $currentAppointment;
            }
            
            $sql = "UPDATE appointments SET 
                appointment_date = ?, appointment_type = ?, appointment_status = ?,
                location = ?, contact_person = ?, contact_phone = ?,
                title = ?, description = ?, notes = ?, updated_at = NOW()
                WHERE appointment_id = ?";
            
            $params = [
                $data['appointment_date'],
                $data['appointment_type'],
                $data['appointment_status'],
                $data['location'] ?? null,
                $data['contact_person'] ?? null,
                $data['contact_phone'] ?? null,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['notes'] ?? null,
                $appointmentId
            ];
            
            $result = $this->db->query($sql, $params);
            
            if ($result) {
                // บันทึกกิจกรรม
                $this->logActivity($appointmentId, $data['user_id'], 'updated', 'อัปเดตนัดหมาย');
                
                // 🔄 SYNC: อัปเดต customers.next_followup_at ถ้าวันที่นัดหมายเปลี่ยน
                $customerId = $currentAppointment['data']['customer_id'];
                $oldDate = $currentAppointment['data']['appointment_date'];
                $newDate = $data['appointment_date'];
                
                if ($oldDate !== $newDate && $data['appointment_status'] !== 'completed' && $data['appointment_status'] !== 'cancelled') {
                    try {
                        $this->db->query(
                            "UPDATE customers 
                             SET next_followup_at = ?,
                                 customer_time_expiry = LEAST(DATE_ADD(customer_time_expiry, INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 90 DAY)),
                                 updated_at = NOW()
                             WHERE customer_id = ?",
                            [$newDate, $customerId]
                        );
                    } catch (Exception $e) {
                        error_log('Failed to sync customer followup date: ' . $e->getMessage());
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'อัปเดตนัดหมายสำเร็จ'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตนัดหมาย'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ลบนัดหมาย
     */
    public function deleteAppointment($appointmentId, $userId) {
        try {
            $sql = "DELETE FROM appointments WHERE appointment_id = ?";
            $result = $this->db->query($sql, [$appointmentId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ลบนัดหมายสำเร็จ'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบนัดหมาย'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงข้อมูลนัดหมายตาม ID
     */
    public function getAppointmentById($appointmentId) {
        try {
            $sql = "SELECT a.*, c.first_name, c.last_name, c.phone as customer_phone,
                           u.full_name as user_name
                    FROM appointments a
                    LEFT JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN users u ON a.user_id = u.user_id
                    WHERE a.appointment_id = ?";
            
            $result = $this->db->fetchOne($sql, [$appointmentId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            }
            
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลนัดหมาย'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงรายการนัดหมายของลูกค้า
     */
    public function getAppointmentsByCustomer($customerId, $limit = 10) {
        try {
            $sql = "SELECT a.*, u.full_name as user_name
                    FROM appointments a
                    LEFT JOIN users u ON a.user_id = u.user_id
                    WHERE a.customer_id = ?
                    ORDER BY a.appointment_date DESC
                    LIMIT ?";
            
            $result = $this->db->fetchAll($sql, [$customerId, $limit]);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงรายการนัดหมายของผู้ใช้
     */
    public function getAppointmentsByUser($userId, $status = null, $limit = 20) {
        try {
            $sql = "SELECT a.*, c.first_name, c.last_name, c.phone as customer_phone
                    FROM appointments a
                    LEFT JOIN customers c ON a.customer_id = c.customer_id
                    WHERE a.user_id = ?";
            
            $params = [$userId];
            
            if ($status) {
                $sql .= " AND a.appointment_status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY a.appointment_date ASC LIMIT ?";
            $params[] = $limit;
            
            $result = $this->db->fetchAll($sql, $params);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงรายการนัดหมายที่ใกล้ถึงกำหนด
     */
    public function getUpcomingAppointments($userId = null, $days = 7) {
        try {
            $sql = "SELECT a.*, c.first_name, c.last_name, c.phone as customer_phone,
                           u.full_name as user_name
                    FROM appointments a
                    LEFT JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN users u ON a.user_id = u.user_id
                    WHERE a.appointment_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                    AND a.appointment_status IN ('scheduled', 'confirmed')";
            
            $params = [$days];
            
            if ($userId) {
                $sql .= " AND a.user_id = ?";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY a.appointment_date ASC";
            
            $result = $this->db->fetchAll($sql, $params);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตสถานะนัดหมาย
     */
    public function updateAppointmentStatus($appointmentId, $status, $userId) {
        try {
            $sql = "UPDATE appointments SET appointment_status = ?, updated_at = NOW() WHERE appointment_id = ?";
            $result = $this->db->query($sql, [$status, $appointmentId]);
            
            if ($result) {
                // บันทึกกิจกรรม
                $activityType = $status === 'completed' ? 'completed' : 'updated';
                $this->logActivity($appointmentId, $userId, $activityType, "อัปเดตสถานะเป็น: $status");
                
                // ตรวจสอบว่าต้องต่อเวลาหรือไม่
                if ($status === 'completed') {
                    $this->handleAppointmentCompletion($appointmentId, $userId);
                }
                
                return [
                    'success' => true,
                    'message' => 'อัปเดตสถานะสำเร็จ'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * จัดการการเสร็จสิ้นการนัดหมาย - ต่อเวลาอัตโนมัติ
     */
    private function handleAppointmentCompletion($appointmentId, $userId) {
        try {
            // ดึงข้อมูลการนัดหมาย
            $appointment = $this->getAppointmentById($appointmentId);
            if (!$appointment || !$appointment['success']) {
                error_log("Appointment not found or error: {$appointmentId}");
                return;
            }
            
            $customerId = $appointment['data']['customer_id'];
            
            // ล้าง next_followup_at เพื่อให้ลูกค้าออกจาก Do tab หลังจากการนัดหมายเสร็จสิ้น
            $this->clearCustomerFollowUp($customerId);
            
            // ตรวจสอบว่าต้องต่อเวลาหรือไม่
            if ($this->shouldExtendTimeForAppointment($appointment['data'])) {
                // เรียกใช้ AppointmentExtensionService
                require_once __DIR__ . '/AppointmentExtensionService.php';
                $extensionService = new AppointmentExtensionService();
                
                try {
                    $result = $extensionService->extendTimeFromAppointment(
                        $customerId,
                        $appointmentId,
                        $userId
                    );
                    
                    error_log("Auto extension result: " . print_r($result, true));
                    
                } catch (Exception $e) {
                    error_log("Auto extension failed: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            error_log('Error handling appointment completion: ' . $e->getMessage());
        }
    }
    
    /**
     * ล้าง next_followup_at เพื่อให้ลูกค้าออกจาก Do tab
     * @param int $customerId ID ของลูกค้า
     */
    private function clearCustomerFollowUp($customerId) {
        try {
            // ล้าง next_followup_at และเพิ่มเวลา 30 วัน แต่ไม่เกิน 90 วัน (สำหรับนัดหมายที่เสร็จสิ้น)
            $this->db->execute(
                "UPDATE customers SET 
                    next_followup_at = NULL,
                    customer_time_expiry = LEAST(DATE_ADD(customer_time_expiry, INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 90 DAY))
                WHERE customer_id = ?",
                [$customerId]
            );
            
            // ล้าง next_followup_at ใน call_logs ที่ยังค้างอยู่
            $this->db->execute(
                "UPDATE call_logs SET next_followup_at = NULL 
                 WHERE customer_id = ? AND next_followup_at IS NOT NULL",
                [$customerId]
            );
            
        } catch (Exception $e) {
            error_log("Error clearing customer follow-up in AppointmentService: " . $e->getMessage());
        }
    }

    /**
     * ตรวจสอบว่าควรต่อเวลาหรือไม่
     */
    private function shouldExtendTimeForAppointment($appointment) {
        try {
            // ดึงกฎการต่อเวลา
            $sql = "SELECT * FROM appointment_extension_rules WHERE is_active = TRUE LIMIT 1";
            $rule = $this->db->fetchOne($sql);
            
            if (!$rule) {
                return false; // ไม่มีกฎที่ใช้งาน
            }
            
            // ตรวจสอบสถานะการนัดหมาย
            if ($appointment['appointment_status'] !== $rule['required_appointment_status']) {
                return false;
            }
            
            // ตรวจสอบเกรดลูกค้า
            $customerSql = "SELECT customer_grade, temperature_status FROM customers WHERE customer_id = ?";
            $customer = $this->db->fetchOne($customerSql, [$appointment['customer_id']]);
            
            if (!$customer) {
                return false;
            }
            
            // ตรวจสอบเกรดลูกค้า
            $gradeOrder = ['D', 'C', 'B', 'A', 'A+'];
            $customerGradeIndex = array_search($customer['customer_grade'], $gradeOrder);
            $minGradeIndex = array_search($rule['min_customer_grade'], $gradeOrder);
            
            if ($customerGradeIndex < $minGradeIndex) {
                return false; // เกรดลูกค้าต่ำเกินไป
            }
            
            // ตรวจสอบสถานะอุณหภูมิ
            $temperatureFilter = json_decode($rule['temperature_status_filter'], true);
            if (!in_array($customer['temperature_status'], $temperatureFilter)) {
                return false; // สถานะอุณหภูมิไม่ตรงตามเงื่อนไข
            }
            
            // ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่
            $canExtendSql = "SELECT appointment_extension_count, max_appointment_extensions 
                           FROM customers WHERE customer_id = ?";
            $extensionInfo = $this->db->fetchOne($canExtendSql, [$appointment['customer_id']]);
            
            if (!$extensionInfo) {
                return false;
            }
            
            return $extensionInfo['appointment_extension_count'] < $extensionInfo['max_appointment_extensions'];
            
        } catch (Exception $e) {
            error_log('Error checking if should extend time: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * บันทึกกิจกรรมนัดหมาย
     */
    private function logActivity($appointmentId, $userId, $activityType, $description) {
        try {
            $sql = "INSERT INTO appointment_activities (appointment_id, user_id, activity_type, activity_description) 
                    VALUES (?, ?, ?, ?)";
            
            $this->db->query($sql, [$appointmentId, $userId, $activityType, $description]);
            
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Error logging appointment activity: " . $e->getMessage());
        }
    }
    
    /**
     * ดึงประวัติกิจกรรมนัดหมาย
     */
    public function getAppointmentActivities($appointmentId) {
        try {
            $sql = "SELECT aa.*, u.full_name as user_name
                    FROM appointment_activities aa
                    LEFT JOIN users u ON aa.user_id = u.user_id
                    WHERE aa.appointment_id = ?
                    ORDER BY aa.created_at DESC";
            
            $result = $this->db->fetchAll($sql, [$appointmentId]);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ส่งการแจ้งเตือนนัดหมาย
     */
    public function sendAppointmentReminder($appointmentId) {
        try {
            // อัปเดตสถานะการส่งการแจ้งเตือน
            $sql = "UPDATE appointments SET reminder_sent = TRUE, reminder_sent_at = NOW() 
                    WHERE appointment_id = ?";
            $this->db->query($sql, [$appointmentId]);
            
            // บันทึกกิจกรรม
            $appointment = $this->getAppointmentById($appointmentId);
            if ($appointment['success']) {
                $this->logActivity($appointmentId, $appointment['data']['user_id'], 'reminder_sent', 'ส่งการแจ้งเตือนนัดหมาย');
            }
            
            return [
                'success' => true,
                'message' => 'ส่งการแจ้งเตือนสำเร็จ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * สถิตินัดหมาย
     */
    public function getAppointmentStats($userId = null, $period = 'month') {
        try {
            $dateFilter = '';
            switch ($period) {
                case 'week':
                    $dateFilter = "AND appointment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $dateFilter = "AND appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $dateFilter = "AND appointment_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                    break;
            }
            
            $userFilter = $userId ? "AND user_id = $userId" : "";
            
            $sql = "SELECT 
                        appointment_status,
                        appointment_type,
                        COUNT(*) as count
                    FROM appointments 
                    WHERE 1=1 $dateFilter $userFilter
                    GROUP BY appointment_status, appointment_type";
            
            $result = $this->db->fetchAll($sql);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
}
?> 