<?php
/**
 * CustomerDistributionService Class
 * จัดการระบบการแจกลูกค้าตามคำขอ
 */

require_once __DIR__ . '/../core/Database.php';

class CustomerDistributionService {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * ดึงสถิติการแจกลูกค้า
     */
    public function getDistributionStats() {
        try {
            // ลูกค้าใน Distribution
            $distributionCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE basket_type = 'distribution' 
                 AND temperature_status != 'frozen' 
                 AND is_active = 1"
            );

            // Telesales ที่พร้อมรับงาน
            $availableTelesalesCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM users 
                 WHERE role_id = 4 AND is_active = 1"
            );

            // ลูกค้า Hot
            $hotCustomersCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE basket_type = 'distribution' 
                 AND temperature_status = 'hot' 
                 AND is_active = 1"
            );

            // ลูกค้า Warm
            $warmCustomersCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE basket_type = 'distribution' 
                 AND temperature_status = 'warm' 
                 AND is_active = 1"
            );

            return [
                'distribution_count' => $distributionCount['count'] ?? 0,
                'available_telesales_count' => $availableTelesalesCount['count'] ?? 0,
                'hot_customers_count' => $hotCustomersCount['count'] ?? 0,
                'warm_customers_count' => $warmCustomersCount['count'] ?? 0
            ];

        } catch (Exception $e) {
            error_log("Error getting distribution stats: " . $e->getMessage());
            return [
                'distribution_count' => 0,
                'available_telesales_count' => 0,
                'hot_customers_count' => 0,
                'warm_customers_count' => 0
            ];
        }
    }

    /**
     * ดึงรายการ Telesales ที่พร้อมรับงาน
     */
    public function getAvailableTelesales() {
        try {
            $sql = "
                SELECT u.user_id, u.full_name, u.email,
                       COUNT(c.customer_id) as current_customers_count
                FROM users u
                LEFT JOIN customers c ON u.user_id = c.assigned_to 
                    AND c.basket_type = 'assigned' 
                    AND c.is_active = 1
                WHERE u.role_id = 4 
                AND u.is_active = 1
                GROUP BY u.user_id, u.full_name, u.email
                ORDER BY current_customers_count ASC, u.full_name ASC
            ";

            return $this->db->fetchAll($sql);

        } catch (Exception $e) {
            error_log("Error getting available telesales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงรายการลูกค้าที่พร้อมแจก
     */
    public function getAvailableCustomers($priority = 'hot_warm_cold', $limit = 10) {
        try {
            $sql = "SELECT c.* FROM customers c 
                    WHERE c.basket_type = 'distribution' 
                    AND c.temperature_status != 'frozen' 
                    AND c.is_active = 1";

            // เพิ่มเงื่อนไขตามลำดับความสำคัญ
            switch ($priority) {
                case 'hot_only':
                    $sql .= " AND c.temperature_status = 'hot'";
                    break;
                case 'warm_only':
                    $sql .= " AND c.temperature_status = 'warm'";
                    break;
                case 'cold_only':
                    $sql .= " AND c.temperature_status = 'cold'";
                    break;
                case 'hot_warm_cold':
                default:
                    // เรียงลำดับตามความสำคัญ: Hot -> Warm -> Cold
                    $sql .= " ORDER BY 
                        CASE c.temperature_status 
                            WHEN 'hot' THEN 1 
                            WHEN 'warm' THEN 2 
                            WHEN 'cold' THEN 3 
                            ELSE 4 
                        END";
                    break;
            }

            if ($priority !== 'hot_warm_cold') {
                $sql .= " ORDER BY c.created_at ASC";
            }

            $sql .= " LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]);

        } catch (Exception $e) {
            error_log("Error getting available customers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * แจกลูกค้าตามคำขอ
     */
    public function distributeCustomers($quantity, $priority, $telesalesIds, $supervisorId) {
        try {
            $this->db->beginTransaction();

            // ดึงลูกค้าที่พร้อมแจก
            $availableCustomers = $this->getAvailableCustomers($priority, $quantity * 2); // ดึงมากกว่าเพื่อให้มีตัวเลือก

            if (empty($availableCustomers)) {
                return [
                    'success' => false,
                    'message' => 'ไม่มีลูกค้าที่พร้อมแจก'
                ];
            }

            // สุ่มเลือกลูกค้าตามจำนวนที่ต้องการ
            $customersToDistribute = array_slice($availableCustomers, 0, $quantity);

            // แบ่งลูกค้าให้ Telesales อย่างเท่าเทียม
            $telesalesCount = count($telesalesIds);
            $customersPerTelesales = ceil($quantity / $telesalesCount);

            $distributionResults = [
                'total_distributed' => 0,
                'telesales_count' => $telesalesCount,
                'distribution_details' => [],
                'customer_details' => []
            ];

            $telesalesIndex = 0;
            $distributedCount = 0;

            foreach ($customersToDistribute as $customer) {
                $telesalesId = $telesalesIds[$telesalesIndex % $telesalesCount];

                // อัปเดตสถานะลูกค้า
                $updateData = [
                    'assigned_to' => $telesalesId,
                    'basket_type' => 'assigned',
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'recall_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
                ];

                $this->db->update('customers', $updateData, 'customer_id = ?', [$customer['customer_id']]);

                // บันทึกประวัติการมอบหมาย
                $this->logAssignmentHistory($customer['customer_id'], $telesalesId, $supervisorId);

                // บันทึกกิจกรรม
                $this->logCustomerActivity($customer['customer_id'], $supervisorId, 'distribution', 
                    "ลูกค้าถูกแจกให้ Telesales ID: {$telesalesId}");

                $distributedCount++;
                $telesalesIndex++;

                // เพิ่มข้อมูลลูกค้าในผลลัพธ์
                $distributionResults['customer_details'][] = [
                    'customer_id' => $customer['customer_id'],
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'phone' => $customer['phone'],
                    'temperature_status' => $customer['temperature_status'],
                    'assigned_to_name' => $this->getTelesalesName($telesalesId)
                ];
            }

            // สร้างรายละเอียดการแจกสำหรับแต่ละ Telesales
            foreach ($telesalesIds as $telesalesId) {
                $assignedCustomers = array_filter($distributionResults['customer_details'], 
                    function($customer) use ($telesalesId) {
                        return $customer['assigned_to_name'] === $this->getTelesalesName($telesalesId);
                    }
                );

                $hotCount = count(array_filter($assignedCustomers, function($c) { return $c['temperature_status'] === 'hot'; }));
                $warmCount = count(array_filter($assignedCustomers, function($c) { return $c['temperature_status'] === 'warm'; }));
                $coldCount = count(array_filter($assignedCustomers, function($c) { return $c['temperature_status'] === 'cold'; }));

                $distributionResults['distribution_details'][] = [
                    'telesales_id' => $telesalesId,
                    'telesales_name' => $this->getTelesalesName($telesalesId),
                    'customer_count' => count($assignedCustomers),
                    'hot_count' => $hotCount,
                    'warm_count' => $warmCount,
                    'cold_count' => $coldCount
                ];
            }

            $distributionResults['total_distributed'] = $distributedCount;

            $this->db->commit();

            return [
                'success' => true,
                'message' => "แจกลูกค้า {$distributedCount} รายการสำเร็จ",
                'results' => $distributionResults
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error distributing customers: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแจกลูกค้า: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ดึงชื่อ Telesales
     */
    private function getTelesalesName($telesalesId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT full_name FROM users WHERE user_id = ?",
                [$telesalesId]
            );
            return $result['full_name'] ?? 'ไม่ระบุ';
        } catch (Exception $e) {
            return 'ไม่ระบุ';
        }
    }

    /**
     * บันทึกประวัติการมอบหมาย
     */
    private function logAssignmentHistory($customerId, $telesalesId, $supervisorId) {
        try {
            $data = [
                'customer_id' => $customerId,
                'user_id' => $telesalesId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $supervisorId,
                'is_current' => 1
            ];

            $this->db->insert('sales_history', $data);
        } catch (Exception $e) {
            error_log("Error logging assignment history: " . $e->getMessage());
        }
    }

    /**
     * บันทึกกิจกรรมลูกค้า
     */
    private function logCustomerActivity($customerId, $userId, $activityType, $description) {
        try {
            $data = [
                'customer_id' => $customerId,
                'user_id' => $userId,
                'activity_type' => $activityType,
                'activity_description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('customer_activities', $data);
        } catch (Exception $e) {
            error_log("Error logging customer activity: " . $e->getMessage());
        }
    }
} 