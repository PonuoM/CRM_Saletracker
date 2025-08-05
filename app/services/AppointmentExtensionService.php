<?php
/**
 * Appointment Extension Service
 * จัดการระบบการต่อเวลาจากการนัดหมาย
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../core/Database.php';

class AppointmentExtensionService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * ดึงข้อมูลการต่อเวลาของลูกค้า
     */
    public function getCustomerExtensionInfo($customerId) {
        try {
            $sql = "SELECT 
                        c.customer_id,
                        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                        c.customer_grade,
                        c.temperature_status,
                        c.appointment_count,
                        c.appointment_extension_count,
                        c.max_appointment_extensions,
                        c.appointment_extension_expiry,
                        c.appointment_extension_days,
                        c.last_appointment_date,
                        CASE 
                            WHEN c.appointment_extension_expiry IS NULL THEN 'ไม่มีวันหมดอายุ'
                            WHEN c.appointment_extension_expiry < NOW() THEN 'หมดอายุแล้ว'
                            ELSE 'ยังไม่หมดอายุ'
                        END as expiry_status,
                        CASE 
                            WHEN c.appointment_extension_count >= c.max_appointment_extensions THEN 'ไม่สามารถต่อเวลาได้แล้ว'
                            ELSE 'สามารถต่อเวลาได้'
                        END as extension_status
                    FROM customers c
                    WHERE c.customer_id = ? AND c.is_active = TRUE";
            
            $result = $this->db->fetchOne($sql, [$customerId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลลูกค้า'
                ];
            }
            
        } catch (Exception $e) {
            error_log('Error getting customer extension info: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ตรวจสอบว่าลูกค้าสามารถต่อเวลาได้หรือไม่
     */
    public function canExtendTime($customerId) {
        try {
            $sql = "SELECT 
                        appointment_extension_count,
                        max_appointment_extensions,
                        appointment_extension_expiry
                    FROM customers 
                    WHERE customer_id = ? AND is_active = TRUE";
            
            $result = $this->db->fetchOne($sql, [$customerId]);
            
            if (!$result) {
                return false;
            }
            
            // ตรวจสอบว่าต่อครบจำนวนครั้งแล้วหรือไม่
            if ($result['appointment_extension_count'] >= $result['max_appointment_extensions']) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error checking if can extend time: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ต่อเวลาจากการนัดหมาย
     */
    public function extendTimeFromAppointment($customerId, $appointmentId, $userId, $extensionDays = null) {
        try {
            // ถ้าไม่ระบุจำนวนวัน ให้ใช้ค่าเริ่มต้น
            if ($extensionDays === null) {
                $sql = "SELECT appointment_extension_days FROM customers WHERE customer_id = ?";
                $result = $this->db->fetchOne($sql, [$customerId]);
                $extensionDays = $result ? $result['appointment_extension_days'] : 30;
            }
            
            // เรียกใช้ stored procedure
            $sql = "CALL ExtendCustomerTimeFromAppointment(?, ?, ?, ?)";
            $result = $this->db->query($sql, [$customerId, $appointmentId, $userId, $extensionDays]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ต่อเวลาสำเร็จ',
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถต่อเวลาได้'
                ];
            }
            
        } catch (Exception $e) {
            error_log('Error extending time from appointment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการต่อเวลา: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * รีเซ็ตตัวนับการต่อเวลาเมื่อมีการขาย
     */
    public function resetExtensionOnSale($customerId, $userId, $orderId) {
        try {
            // เรียกใช้ stored procedure
            $sql = "CALL ResetAppointmentExtensionOnSale(?, ?, ?)";
            $result = $this->db->query($sql, [$customerId, $userId, $orderId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'รีเซ็ตตัวนับการต่อเวลาสำเร็จ',
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถรีเซ็ตตัวนับได้'
                ];
            }
            
        } catch (Exception $e) {
            error_log('Error resetting extension on sale: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรีเซ็ต: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ต่อเวลาด้วยตนเอง
     */
    public function extendTimeManually($customerId, $userId, $extensionDays, $reason) {
        try {
            // ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่
            if (!$this->canExtendTime($customerId)) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถต่อเวลาได้ (อาจจะต่อครบจำนวนครั้งแล้ว)'
                ];
            }
            
            // ดึงข้อมูลปัจจุบัน
            $sql = "SELECT 
                        appointment_extension_count,
                        appointment_extension_expiry
                    FROM customers 
                    WHERE customer_id = ?";
            $customer = $this->db->fetchOne($sql, [$customerId]);
            
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลลูกค้า'
                ];
            }
            
            // คำนวณวันหมดอายุใหม่
            $newExpiry = null;
            if ($customer['appointment_extension_expiry'] === null || $customer['appointment_extension_expiry'] < date('Y-m-d H:i:s')) {
                $newExpiry = date('Y-m-d H:i:s', strtotime("+{$extensionDays} days"));
            } else {
                $newExpiry = date('Y-m-d H:i:s', strtotime($customer['appointment_extension_expiry'] . " +{$extensionDays} days"));
            }
            
            // อัปเดตข้อมูลลูกค้า
            $updateSql = "UPDATE customers 
                         SET appointment_extension_count = appointment_extension_count + 1,
                             appointment_extension_expiry = ?,
                             updated_at = NOW()
                         WHERE customer_id = ?";
            $this->db->query($updateSql, [$newExpiry, $customerId]);
            
            // บันทึกประวัติ
            $insertSql = "INSERT INTO appointment_extensions (
                            customer_id, user_id, appointment_id, extension_type, 
                            extension_days, extension_reason, previous_expiry, 
                            new_expiry, extension_count_before, extension_count_after
                          ) VALUES (?, ?, NULL, 'manual', ?, ?, ?, ?, ?, ?)";
            $this->db->query($insertSql, [
                $customerId, $userId, $extensionDays, $reason,
                $customer['appointment_extension_expiry'], $newExpiry,
                $customer['appointment_extension_count'], $customer['appointment_extension_count'] + 1
            ]);
            
            return [
                'success' => true,
                'message' => 'ต่อเวลาด้วยตนเองสำเร็จ',
                'data' => [
                    'new_expiry' => $newExpiry,
                    'extension_count' => $customer['appointment_extension_count'] + 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error extending time manually: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการต่อเวลา: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงประวัติการต่อเวลา
     */
    public function getExtensionHistory($customerId, $limit = 10) {
        try {
            $sql = "SELECT 
                        extension_id,
                        extension_type,
                        extension_days,
                        extension_reason,
                        previous_expiry,
                        new_expiry,
                        extension_count_before,
                        extension_count_after,
                        created_at
                    FROM appointment_extensions 
                    WHERE customer_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?";
            
            $result = $this->db->query($sql, [$customerId, $limit]);
            
            return [
                'success' => true,
                'data' => $result ?: []
            ];
            
        } catch (Exception $e) {
            error_log('Error getting extension history: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงประวัติ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงลูกค้าที่ใกล้หมดอายุ
     */
    public function getCustomersNearExpiry($days = 7) {
        try {
            $sql = "SELECT 
                        customer_id,
                        CONCAT(first_name, ' ', last_name) as customer_name,
                        appointment_extension_expiry,
                        appointment_extension_count,
                        max_appointment_extensions
                    FROM customers 
                    WHERE is_active = TRUE 
                    AND appointment_extension_expiry IS NOT NULL
                    AND appointment_extension_expiry BETWEEN NOW() 
                    AND DATE_ADD(NOW(), INTERVAL ? DAY)
                    ORDER BY appointment_extension_expiry ASC";
            
            $result = $this->db->query($sql, [$days]);
            
            return [
                'success' => true,
                'data' => $result ?: []
            ];
            
        } catch (Exception $e) {
            error_log('Error getting customers near expiry: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงลูกค้าที่หมดอายุแล้ว
     */
    public function getExpiredCustomers() {
        try {
            $sql = "SELECT 
                        customer_id,
                        CONCAT(first_name, ' ', last_name) as customer_name,
                        appointment_extension_expiry,
                        appointment_extension_count,
                        max_appointment_extensions
                    FROM customers 
                    WHERE is_active = TRUE 
                    AND appointment_extension_expiry IS NOT NULL
                    AND appointment_extension_expiry < NOW()
                    ORDER BY appointment_extension_expiry DESC";
            
            $result = $this->db->query($sql);
            
            return [
                'success' => true,
                'data' => $result ?: []
            ];
            
        } catch (Exception $e) {
            error_log('Error getting expired customers: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงสถิติการต่อเวลา
     */
    public function getExtensionStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_customers,
                        SUM(CASE WHEN appointment_extension_count < max_appointment_extensions THEN 1 ELSE 0 END) as can_extend,
                        SUM(CASE WHEN appointment_extension_count >= max_appointment_extensions THEN 1 ELSE 0 END) as cannot_extend,
                        SUM(CASE WHEN appointment_extension_expiry IS NOT NULL 
                                 AND appointment_extension_expiry BETWEEN NOW() 
                                 AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as near_expiry,
                        SUM(CASE WHEN appointment_extension_expiry IS NOT NULL 
                                 AND appointment_extension_expiry < NOW() THEN 1 ELSE 0 END) as expired
                    FROM customers 
                    WHERE is_active = TRUE";
            
            $result = $this->db->fetchOne($sql);
            
            return [
                'success' => true,
                'data' => $result ?: [
                    'total_customers' => 0,
                    'can_extend' => 0,
                    'cannot_extend' => 0,
                    'near_expiry' => 0,
                    'expired' => 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting extension stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงสถิติ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตกฎการต่อเวลา
     */
    public function updateExtensionRule($ruleId, $data) {
        try {
            $sql = "UPDATE appointment_extension_rules 
                    SET rule_name = ?, extension_days = ?, max_extensions = ?, 
                        reset_on_sale = ?, required_appointment_status = ?, 
                        min_customer_grade = ?, temperature_status_filter = ?,
                        updated_at = NOW()
                    WHERE rule_id = ?";
            
            $result = $this->db->query($sql, [
                $data['rule_name'],
                $data['extension_days'],
                $data['max_extensions'],
                $data['reset_on_sale'],
                $data['required_appointment_status'],
                $data['min_customer_grade'],
                $data['temperature_status_filter'],
                $ruleId
            ]);
            
            return [
                'success' => true,
                'message' => 'อัปเดตกฎการต่อเวลาสำเร็จ'
            ];
            
        } catch (Exception $e) {
            error_log('Error updating extension rule: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดต: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงกฎการต่อเวลา
     */
    public function getExtensionRules() {
        try {
            $sql = "SELECT * FROM appointment_extension_rules WHERE is_active = TRUE ORDER BY rule_id";
            $result = $this->db->query($sql);
            
            return [
                'success' => true,
                'data' => $result ?: []
            ];
            
        } catch (Exception $e) {
            error_log('Error getting extension rules: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงกฎ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * แปลงสถานะการต่อเวลาเป็นข้อความ
     */
    private function getExtensionStatusText($extensionCount, $maxExtensions) {
        if ($extensionCount >= $maxExtensions) {
            return 'ไม่สามารถต่อเวลาได้แล้ว';
        } else {
            $remaining = $maxExtensions - $extensionCount;
            return "สามารถต่อเวลาได้อีก {$remaining} ครั้ง";
        }
    }
    
    /**
     * แปลงสถานะวันหมดอายุเป็นข้อความ
     */
    private function getExpiryStatusText($expiryDate) {
        if ($expiryDate === null) {
            return 'ไม่มีวันหมดอายุ';
        }
        
        $expiry = strtotime($expiryDate);
        $now = time();
        
        if ($expiry < $now) {
            return 'หมดอายุแล้ว';
        } else {
            $diff = $expiry - $now;
            $days = floor($diff / (60 * 60 * 24));
            return "ยังไม่หมดอายุ (เหลือ {$days} วัน)";
        }
    }
}
?> 