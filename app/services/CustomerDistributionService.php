<?php
/**
 * CustomerDistributionService Class
 * จัดการระบบการแจกลูกค้าตามคำขอ
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/CompanyContext.php';

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
            // Determine company source from current user
            $companyId = CompanyContext::getCompanyId($this->db);
            
            // Debug logging
            error_log("CustomerDistributionService::getDistributionStats - Company ID: " . ($companyId ?? 'NULL'));
            error_log("Session data: " . json_encode([
                'user_id' => $_SESSION['user_id'] ?? 'null',
                'company_id' => $_SESSION['company_id'] ?? 'null',
                'role_name' => $_SESSION['role_name'] ?? 'null',
                'override_company_id' => $_SESSION['override_company_id'] ?? 'null'
            ]));

            // Build base WHERE clause for company filtering
            $companyWhere = "";
            $params = [];
            if ($companyId) {
                $companyWhere = " AND company_id = ?";
                $params[] = $companyId;
            }

            $distributionCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE basket_type = 'distribution'
                 AND temperature_status != 'frozen'
                 AND is_active = 1" . $companyWhere,
                 $params
            );
            error_log("Distribution count: " . ($distributionCount['count'] ?? 0));

            // Telesales ที่พร้อมรับงาน
            $telesalesParams = [];
            if ($companyId) {
                $telesalesParams[] = $companyId;
            }
            $availableTelesalesCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM users
                 WHERE role_id IN (3,4) AND is_active = 1" . $companyWhere,
                $telesalesParams
            );
            error_log("Available telesales count: " . ($availableTelesalesCount['count'] ?? 0));

            // ลูกค้า Hot
            $hotCustomersCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE basket_type = 'distribution'
                 AND temperature_status = 'hot'
                 AND is_active = 1" . $companyWhere,
                 $params
            );
            error_log("Hot customers count: " . ($hotCustomersCount['count'] ?? 0));

            // ลูกค้า Warm
            $warmCustomersCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE basket_type = 'distribution'
                 AND temperature_status = 'warm'
                 AND is_active = 1" . $companyWhere,
                 $params
            );
            error_log("Warm customers count: " . ($warmCustomersCount['count'] ?? 0));

            // ลูกค้าถูกดึงกลับ (รอเวลา) - รวม waiting, frozen หรือมี recall_at ในอนาคต
            $waitingCustomersCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE is_active = 1
                 AND (
                    basket_type = 'waiting'
                    OR temperature_status = 'frozen'
                    OR (recall_at IS NOT NULL AND recall_at > NOW())
                 )" . $companyWhere,
                 $params
            );
            error_log("Waiting customers count: " . ($waitingCustomersCount['count'] ?? 0));

            // ลูกค้าเกรด A+ และ A
            $gradeAPlusCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE customer_grade = 'A+' 
                 AND basket_type = 'distribution' 
                 AND is_active = 1" . $companyWhere,
                $params
            );

            $gradeACount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE customer_grade = 'A' 
                 AND basket_type = 'distribution' 
                 AND is_active = 1" . $companyWhere,
                $params
            );

            $result = [
                'distribution_count' => $distributionCount['count'] ?? 0,
                'available_telesales_count' => $availableTelesalesCount['count'] ?? 0,
                'hot_customers_count' => $hotCustomersCount['count'] ?? 0,
                'warm_customers_count' => $warmCustomersCount['count'] ?? 0,
                'waiting_customers_count' => $waitingCustomersCount['count'] ?? 0,
                'grade_a_plus_count' => $gradeAPlusCount['count'] ?? 0,
                'grade_a_count' => $gradeACount['count'] ?? 0
            ];

            error_log("Final stats result: " . json_encode($result));
            return $result;

        } catch (Exception $e) {
            error_log("Error getting distribution stats: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'distribution_count' => 0,
                'available_telesales_count' => 0,
                'hot_customers_count' => 0,
                'warm_customers_count' => 0,
                'waiting_customers_count' => 0,
                'grade_a_plus_count' => 0,
                'grade_a_count' => 0
            ];
        }
    }

    /**
     * ดึงรายการ Telesales ที่พร้อมรับงาน
     */
    public function getAvailableTelesales() {
        try {
            $companyId = CompanyContext::getCompanyId($this->db);
            error_log("getAvailableTelesales: company_id: " . ($companyId ?? 'NULL'));
            
            $params = [];
            
            // Build SQL with proper parameter handling
            if ($companyId) {
                $sql = "
                    SELECT u.user_id, u.full_name, u.email,
                           COUNT(c.customer_id) as current_customers_count,
                           SUM(CASE WHEN c.customer_grade = 'A+' THEN 1 ELSE 0 END) AS grade_a_plus,
                           SUM(CASE WHEN c.customer_grade = 'A' THEN 1 ELSE 0 END) AS grade_a,
                           SUM(CASE WHEN c.customer_grade = 'B' THEN 1 ELSE 0 END) AS grade_b,
                           SUM(CASE WHEN c.customer_grade = 'C' THEN 1 ELSE 0 END) AS grade_c,
                           SUM(CASE WHEN c.customer_grade = 'D' THEN 1 ELSE 0 END) AS grade_d,
                           comp.company_name,
                           comp.company_code
                    FROM users u
                    LEFT JOIN companies comp ON u.company_id = comp.company_id
                    LEFT JOIN customers c ON u.user_id = c.assigned_to
                        AND c.basket_type = 'assigned'
                        AND c.is_active = 1
                        AND c.company_id = ?
                    WHERE u.role_id IN (3,4) AND u.is_active = 1 AND u.company_id = ?
                    GROUP BY u.user_id, u.full_name, u.email, comp.company_name, comp.company_code
                    ORDER BY current_customers_count ASC, u.full_name ASC
                ";
                $params = [$companyId, $companyId]; // for c.company_id and u.company_id
            } else {
                // Super admin sees all telesales and supervisors
                $sql = "
                    SELECT u.user_id, u.full_name, u.email,
                           COUNT(c.customer_id) as current_customers_count,
                           SUM(CASE WHEN c.customer_grade = 'A+' THEN 1 ELSE 0 END) AS grade_a_plus,
                           SUM(CASE WHEN c.customer_grade = 'A' THEN 1 ELSE 0 END) AS grade_a,
                           SUM(CASE WHEN c.customer_grade = 'B' THEN 1 ELSE 0 END) AS grade_b,
                           SUM(CASE WHEN c.customer_grade = 'C' THEN 1 ELSE 0 END) AS grade_c,
                           SUM(CASE WHEN c.customer_grade = 'D' THEN 1 ELSE 0 END) AS grade_d,
                           comp.company_name,
                           comp.company_code
                    FROM users u
                    LEFT JOIN companies comp ON u.company_id = comp.company_id
                    LEFT JOIN customers c ON u.user_id = c.assigned_to
                        AND c.basket_type = 'assigned'
                        AND c.is_active = 1
                    WHERE u.role_id IN (3,4) AND u.is_active = 1
                    GROUP BY u.user_id, u.full_name, u.email, comp.company_name, comp.company_code
                    ORDER BY current_customers_count ASC, u.full_name ASC
                ";
                $params = [];
            }

            $rows = $this->db->fetchAll($sql, $params);
            // enrich rows with grade_distribution for UI
            $result = array_map(function($row) {
                $row['grade_distribution'] = [
                    'A_plus' => (int)($row['grade_a_plus'] ?? 0),
                    'A' => (int)($row['grade_a'] ?? 0),
                    'B' => (int)($row['grade_b'] ?? 0),
                    'C' => (int)($row['grade_c'] ?? 0),
                    'D' => (int)($row['grade_d'] ?? 0),
                ];
                return $row;
            }, $rows);
            error_log("getAvailableTelesales: Found " . count($result) . " telesales");
            
            return $result;

        } catch (Exception $e) {
            error_log("Error getting available telesales: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * ดึงสถิติของบริษัทเฉพาะ
     */
    public function getCompanyStats($company = null) {
        try {
            $companyId = CompanyContext::getCompanyId($this->db);
            error_log("getCompanyStats: company_id: {$companyId}");

            // ลูกค้าพร้อมแจก (ยกเว้นเกรด A และ A+ สำหรับการแจกเฉลี่ย)
            $distributionCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE basket_type = 'distribution'
                 AND temperature_status != 'frozen'
                 AND is_active = 1
                 AND company_id = ?
                 AND customer_grade NOT IN ('A', 'A+')",
                [$companyId]
            );

            // Telesales ของบริษัท
            $telesalesCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM users u
                 WHERE u.role_id = 4 AND u.is_active = 1" . ($companyId ? " AND u.company_id = ?" : ""),
                $companyId ? [$companyId] : []
            );

            // ลูกค้า Hot (ยกเว้นเกรด A และ A+)
            $hotCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE basket_type = 'distribution'
                 AND temperature_status = 'hot'
                 AND is_active = 1
                 AND company_id = ?
                 AND customer_grade NOT IN ('A', 'A+')",
                [$companyId]
            );

            // ลูกค้า Warm (ยกเว้นเกรด A และ A+)
            $warmCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE basket_type = 'distribution'
                 AND temperature_status = 'warm'
                 AND is_active = 1
                 AND company_id = ?
                 AND customer_grade NOT IN ('A', 'A+')",
                [$companyId]
            );

            $result = [
                'distribution_count' => $distributionCount['count'] ?? 0,
                'telesales_count' => $telesalesCount['count'] ?? 0,
                'hot_count' => $hotCount['count'] ?? 0,
                'warm_count' => $warmCount['count'] ?? 0
            ];

            error_log("getCompanyStats: Result: " . json_encode($result));
            return $result;

        } catch (Exception $e) {
            error_log("Error getting company stats: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'distribution_count' => 0,
                'telesales_count' => 0,
                'hot_count' => 0,
                'warm_count' => 0
            ];
        }
    }

    /**
     * ดึงรายการ Telesales ตามบริษัท
     */
    public function getTelesalesByCompany($company) {
        try {
            // Use current company_id context and ignore free-text company source
            $companyId = CompanyContext::getCompanyId($this->db);
            $params = [];
            $sql = "
                SELECT u.user_id, u.full_name, u.email, comp.company_name, comp.company_code,
                       COUNT(c.customer_id) as current_customers_count,
                       SUM(CASE WHEN c.customer_grade = 'A+' THEN 1 ELSE 0 END) AS grade_a_plus,
                       SUM(CASE WHEN c.customer_grade = 'A' THEN 1 ELSE 0 END) AS grade_a,
                       SUM(CASE WHEN c.customer_grade = 'B' THEN 1 ELSE 0 END) AS grade_b,
                       SUM(CASE WHEN c.customer_grade = 'C' THEN 1 ELSE 0 END) AS grade_c,
                       SUM(CASE WHEN c.customer_grade = 'D' THEN 1 ELSE 0 END) AS grade_d
                FROM users u
                JOIN companies comp ON u.company_id = comp.company_id
                LEFT JOIN customers c ON u.user_id = c.assigned_to
                    AND c.basket_type = 'assigned'
                    AND c.is_active = 1" . ($companyId ? " AND c.company_id = ?" : "") . "
                WHERE u.role_id IN (3,4) AND u.is_active = 1" . ($companyId ? " AND u.company_id = ?" : "") . "
                GROUP BY u.user_id, u.full_name, u.email, comp.company_name, comp.company_code
                ORDER BY current_customers_count ASC, u.full_name ASC
            ";
            if ($companyId) { $params = [$companyId, $companyId]; }
            $rows = $this->db->fetchAll($sql, $params);
            // enrich with grade_distribution
            return array_map(function($row) {
                $row['grade_distribution'] = [
                    'A_plus' => (int)($row['grade_a_plus'] ?? 0),
                    'A' => (int)($row['grade_a'] ?? 0),
                    'B' => (int)($row['grade_b'] ?? 0),
                    'C' => (int)($row['grade_c'] ?? 0),
                    'D' => (int)($row['grade_d'] ?? 0),
                ];
                return $row;
            }, $rows);
        } catch (Exception $e) {
            error_log("getTelesalesByCompany error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงรายการ Telesales ตามบริษัท (ใช้ชื่อบริษัท)
     */
    public function getTelesalesByCompanyName($company) {
        try {
            if (!$company) {
                error_log("getTelesalesByCompany: Company parameter is empty");
                return [];
            }

            // แปลงชื่อบริษัทให้ตรงกับข้อมูลในฐานข้อมูล
            $companyMappings = [
                'prima' => ['PRIMA49', 'พรีม่า', 'prima'],
                'prionic' => ['A02', 'พรีออนิค', 'prionic']
            ];

            $searchTerms = $companyMappings[strtolower($company)] ?? [$company];
            error_log("getTelesalesByCompany: Company: {$company}, Search terms: " . json_encode($searchTerms));

            // สร้าง WHERE clause สำหรับค้นหาหลายเงื่อนไข
            $whereConditions = [];
            $params = [];

            foreach ($searchTerms as $term) {
                $whereConditions[] = "comp.company_name LIKE ? OR comp.company_code LIKE ?";
                $params[] = "%{$term}%";
                $params[] = "%{$term}%";
            }

            $whereClause = "(" . implode(" OR ", $whereConditions) . ")";

            $sql = "
                SELECT u.user_id, u.full_name, u.email, comp.company_name, comp.company_code,
                       COUNT(c.customer_id) as current_customers_count
                FROM users u
                JOIN companies comp ON u.company_id = comp.company_id
                LEFT JOIN customers c ON u.user_id = c.assigned_to
                    AND c.basket_type = 'assigned'
                    AND c.is_active = 1
                WHERE u.role_id = 4 AND u.is_active = 1
                AND {$whereClause}
                GROUP BY u.user_id, u.full_name, u.email, comp.company_name, comp.company_code
                ORDER BY current_customers_count ASC, u.full_name ASC
            ";

            $result = $this->db->fetchAll($sql, $params);

            // Log สำหรับ debug
            error_log("getTelesalesByCompany - Company: {$company}, Found: " . count($result) . " users");

            return $result;

        } catch (Exception $e) {
            error_log("Error getting telesales by company for {$company}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * ดึงจำนวนลูกค้าพร้อมแจกตามวันที่และบริษัท
     */
    public function getAvailableCustomersByDate($dateFrom, $dateTo) {
        try {
            if (!$dateFrom || !$dateTo) {
                error_log("getAvailableCustomersByDate: Missing parameters - dateFrom: {$dateFrom}, dateTo: {$dateTo}");
                return ['count' => 0];
            }

            $companyId = CompanyContext::getCompanyId($this->db);
            error_log("getAvailableCustomersByDate: company_id: {$companyId}, dateFrom: {$dateFrom}, dateTo: {$dateTo}");

            // นับจำนวนลูกค้าที่อยู่ในช่วงวันที่ โดยพิจารณา order_date ถ้ามี ไม่งั้นใช้ created_at
            $params = [];
            $join = "LEFT JOIN orders o ON o.customer_id = c.customer_id AND o.is_active = 1";
            if ($companyId) { $join .= " AND o.company_id = ?"; $params[] = $companyId; }

            $sql = "SELECT COUNT(DISTINCT c.customer_id) as count
                    FROM customers c
                    $join
                    WHERE c.basket_type = 'distribution'
                      AND c.temperature_status != 'frozen'
                      AND c.is_active = 1
                      AND c.customer_grade NOT IN ('A', 'A+')";

            if ($companyId) { $sql .= " AND c.company_id = ?"; $params[] = $companyId; }

            // เงื่อนไขช่วงวันที่ (ใช้ order_date ถ้ามี, ถ้าไม่มีใช้ created_at)
            $sql .= " AND ( (o.order_date IS NOT NULL AND DATE(o.order_date) BETWEEN ? AND ?) 
                              OR (o.order_date IS NULL AND DATE(c.created_at) BETWEEN ? AND ?) )";
            array_push($params, $dateFrom, $dateTo, $dateFrom, $dateTo);

            $result = $this->db->fetchOne($sql, $params);

            $count = $result['count'] ?? 0;
            error_log("getAvailableCustomersByDate: Found {$count} customers for company {$companyId} between {$dateFrom} and {$dateTo}");

            return [
                'count' => $count,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'company_id' => $companyId
            ];

        } catch (Exception $e) {
            error_log("Error getting available customers by date: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return ['count' => 0];
        }
    }

    /**
     * ตรวจสอบโควต้าของ Telesales
     */
    public function checkTelesalesQuota($telesalesId) {
        try {
            $companyId = CompanyContext::getCompanyId($this->db);

            // ตรวจสอบการใช้โควต้าในสัปดาห์นี้ (7 วันล่าสุด)
            $sql = "SELECT COUNT(*) as weekly_used FROM customers
                    WHERE assigned_to = ?
                    AND company_id = ?
                    AND assigned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

            $result = $this->db->fetchOne($sql, [$telesalesId, $companyId]);
            $weeklyUsed = $result['weekly_used'] ?? 0;

            // ตรวจสอบการใช้โควต้าในวันนี้
            $sql = "SELECT COUNT(*) as daily_used FROM customers
                    WHERE assigned_to = ?
                    AND company_id = ?
                    AND DATE(assigned_at) = CURDATE()";

            $result = $this->db->fetchOne($sql, [$telesalesId, $companyId]);
            $dailyUsed = $result['daily_used'] ?? 0;

            // คำนวณโควต้าคงเหลือ
            $weeklyQuota = 300;
            $dailyMaxQuota = 150;

            $weeklyRemaining = max(0, $weeklyQuota - $weeklyUsed);
            $dailyRemaining = max(0, $dailyMaxQuota - $dailyUsed);

            return [
                'weekly_used' => $weeklyUsed,
                'weekly_quota' => $weeklyQuota,
                'weekly_remaining' => $weeklyRemaining,
                'daily_used' => $dailyUsed,
                'daily_quota' => $dailyMaxQuota,
                'daily_remaining' => $dailyRemaining,
                'can_request' => ($weeklyRemaining > 0 && $dailyRemaining > 0)
            ];

        } catch (Exception $e) {
            error_log("Error checking telesales quota: " . $e->getMessage());
            return [
                'weekly_used' => 0,
                'weekly_quota' => 300,
                'weekly_remaining' => 300,
                'daily_used' => 0,
                'daily_quota' => 150,
                'daily_remaining' => 150,
                'can_request' => true
            ];
        }
    }

    /**
     * แจกลูกค้าแบบเฉลี่ย
     */
    public function distributeAverage($customerCount, $dateFrom, $dateTo, $telesalesIds, $adminUserId, $postStatus = null) {
        try {
            $this->db->beginTransaction();

            $companyId = CompanyContext::getCompanyId($this->db);

            // ดึงลูกค้าที่พร้อมแจก (ยกเว้นเกรด A และ A+) โดยใช้เกณฑ์วันที่เดียวกับส่วนแสดงจำนวน
            $params = [];
            $join = "LEFT JOIN orders o ON o.customer_id = c.customer_id AND o.is_active = 1";
            if ($companyId) { $join .= " AND o.company_id = ?"; $params[] = $companyId; }

            $whereClause = "c.basket_type = 'distribution' AND c.temperature_status != 'frozen' AND c.is_active = 1 AND c.customer_grade NOT IN ('A', 'A+')";
            if ($companyId) { $whereClause .= " AND c.company_id = ?"; $params[] = $companyId; }

            if ($dateFrom && $dateTo) {
                $whereClause .= " AND ((o.order_date IS NOT NULL AND DATE(o.order_date) BETWEEN ? AND ?) OR (o.order_date IS NULL AND DATE(c.created_at) BETWEEN ? AND ?))";
                array_push($params, $dateFrom, $dateTo, $dateFrom, $dateTo);
            }

            // NOTE: MySQL does not allow binding LIMIT with native prepares; inject sanitized int
            $limitInt = max(0, (int)$customerCount);
            $sql = "SELECT DISTINCT c.customer_id, c.first_name, c.last_name FROM customers c $join WHERE {$whereClause} ORDER BY c.created_at ASC LIMIT {$limitInt}";
            $customers = $this->db->fetchAll($sql, $params);

            if (count($customers) < $customerCount) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => "มีลูกค้าพร้อมแจกเพียง " . count($customers) . " คน (ต้องการ {$customerCount} คน)"
                ];
            }

            // คำนวณการแจก
            $telesalesCount = count($telesalesIds);
            if ($telesalesCount <= 0) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'กรุณาเลือก Telesales อย่างน้อย 1 คน'
                ];
            }
            $averagePerPerson = floor($customerCount / $telesalesCount);
            $remainder = $customerCount % $telesalesCount;

            // แจกเศษให้คนที่เลือกเท่านั้น (เรียงตาม user_id เพื่อความเป็นธรรม)
            $remainderRecipients = [];
            if ($remainder > 0) {
                // เรียง telesalesIds เพื่อให้การแจกเศษเป็นไปอย่างเป็นธรรม
                sort($telesalesIds);
                for ($i = 0; $i < $remainder && $i < count($telesalesIds); $i++) {
                    $remainderRecipients[] = $telesalesIds[$i];
                }
            }

            // แจกลูกค้า
            $distributedCount = 0;
            $customerIndex = 0;
            $distributionResults = []; // เก็บผลการแจก

            foreach ($telesalesIds as $telesalesId) {
                $assignCount = $averagePerPerson;

                // เพิ่มเศษให้คนที่อยู่ในรายการผู้รับเศษ (คนที่เลือกเท่านั้น)
                if (in_array($telesalesId, $remainderRecipients)) {
                    $assignCount += 1; // เพิ่มทีละ 1 คนตาม remainder
                }

                $assignedCustomers = []; // เก็บลูกค้าที่แจกให้คนนี้

                for ($i = 0; $i < $assignCount && $customerIndex < count($customers); $i++) {
                    $customer = $customers[$customerIndex];

                    // อัปเดตลูกค้า
                    // อัปเดตสถานะลูกค้า พร้อมตั้ง customer_status ตามที่เลือก (ถ้าระบุ)
                    $updateFields = [
                        'basket_type' => 'assigned',
                        'assigned_to' => $telesalesId,
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'customer_time_base' => date('Y-m-d H:i:s'),
                        'customer_time_expiry' => date('Y-m-d H:i:s', strtotime('+30 days')),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    if (!empty($postStatus)) {
                        $updateFields['customer_status'] = $postStatus;
                    }
                    $this->db->update('customers', $updateFields, 'customer_id = ?', [$customer['customer_id']]);

                    // ดึงข้อมูลลูกค้าเพิ่มเติม
                    $customerInfo = $this->db->fetchOne(
                        "SELECT customer_id, customer_code, first_name, last_name, phone, customer_grade, temperature_status 
                         FROM customers WHERE customer_id = ?",
                        [$customer['customer_id']]
                    );

                    $assignedCustomers[] = [
                        'customer_id' => $customerInfo['customer_id'],
                        'customer_code' => $customerInfo['customer_code'],
                        'full_name' => $customerInfo['first_name'] . ' ' . $customerInfo['last_name'],
                        'name' => $customerInfo['first_name'] . ' ' . $customerInfo['last_name'],
                        'phone' => $customerInfo['phone'],
                        'customer_grade' => $customerInfo['customer_grade'],
                        'grade' => $customerInfo['customer_grade'],
                        'temperature_status' => $customerInfo['temperature_status'],
                        'temperature' => $customerInfo['temperature_status'],
                        'customer_time_expiry' => date('Y-m-d H:i:s', strtotime('+30 days'))
                    ];

                    $distributedCount++;
                    $customerIndex++;
                }

                // ดึงชื่อ Telesales
                $telesalesInfo = $this->db->fetchOne(
                    "SELECT full_name FROM users WHERE user_id = ?",
                    [$telesalesId]
                );

                $distributionResults[] = [
                    'telesales_id' => $telesalesId,
                    'telesales_name' => $telesalesInfo['full_name'] ?? 'Unknown',
                    'company_id' => $companyId,
                    'count' => count($assignedCustomers),
                    'customers' => $assignedCustomers
                ];
            }

            // บันทึกประวัติการแจก
            // TODO: Create distribution_logs table or use alternative logging method
            // $this->db->execute(
            //     "INSERT INTO distribution_logs (admin_user_id, distribution_type, company, customer_count, telesales_count, created_at)
            //      VALUES (?, 'average', ?, ?, ?, NOW())",
            //     [$adminUserId, $companySource, $distributedCount, $telesalesCount]
            // );

            $this->db->commit();

            return [
                'success' => true,
                'message' => "แจกลูกค้าแบบเฉลี่ยสำเร็จ {$distributedCount} คนให้ {$telesalesCount} คน",
                'data' => [
                    'distributions' => $distributionResults,
                    'total_distributed' => $distributedCount,
                    'company_id' => $companyId,
                    'type' => 'average',
                    'excluded_grades' => ['A', 'A+'] // แสดงเกรดที่ยกเว้น
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error in distributeAverage: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแจกลูกค้า: ' . $e->getMessage()
            ];
        }
    }

    /**
     * แจกลูกค้าตามคำขอ
     */
    public function distributeRequest($quantity, $priority, $telesalesId, $adminUserId) {
        try {
            $this->db->beginTransaction();

            $companyId = CompanyContext::getCompanyId($this->db);

            // ตรวจสอบโควต้า (300 รายชื่อ/สัปดาห์, ไม่เกิน 150 ต่อครั้ง)
            if ($quantity > 150) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถขอเกิน 150 รายชื่อต่อครั้ง'
                ];
            }

            // ตรวจสอบโควต้าสัปดาห์
            $weeklyUsed = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers
                 WHERE assigned_to = ? AND company_id = ?
                 AND assigned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                [$telesalesId, $companyId]
            );

            if (($weeklyUsed['count'] + $quantity) > 300) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => "เกินโควต้าสัปดาห์ (ใช้แล้ว {$weeklyUsed['count']}/300)"
                ];
            }

            // สร้าง WHERE clause ตาม priority (ยกเว้นเกรด A และ A+)
            $whereClause = "basket_type = 'distribution' AND is_active = 1 AND company_id = ? AND customer_grade NOT IN ('A', 'A+')";
            $params = [$companyId];

            switch ($priority) {
                case 'hot_only':
                    $whereClause .= " AND temperature_status = 'hot'";
                    break;
                case 'warm_only':
                    $whereClause .= " AND temperature_status = 'warm'";
                    break;
                case 'cold_only':
                    $whereClause .= " AND temperature_status = 'cold'";
                    break;
                case 'stock_only':
                    $whereClause .= " AND created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                default: // hot_warm_cold
                    $whereClause .= " AND temperature_status != 'frozen'";
                    break;
            }

            // เรียงลำดับตาม priority
            $orderClause = "ORDER BY ";
            if ($priority === 'hot_warm_cold') {
                $orderClause .= "CASE temperature_status WHEN 'hot' THEN 1 WHEN 'warm' THEN 2 WHEN 'cold' THEN 3 ELSE 4 END, created_at ASC";
            } else {
                $orderClause .= "created_at ASC";
            }

            // ดึงลูกค้า
            // Avoid binding LIMIT with native prepares
            $limitReq = max(0, (int)$quantity);
            $sqlReq = "SELECT customer_id, first_name, last_name, phone, temperature_status FROM customers
                 WHERE {$whereClause} {$orderClause} LIMIT {$limitReq}";
            $customers = $this->db->fetchAll($sqlReq, $params);

            if (count($customers) < $quantity) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => "มีลูกค้าพร้อมแจกเพียง " . count($customers) . " คน (ต้องการ {$quantity} คน)"
                ];
            }

            // แจกลูกค้า
            foreach ($customers as $customer) {
                $this->db->execute(
                    "UPDATE customers SET
                     basket_type = 'assigned',
                     assigned_to = ?,
                     assigned_at = NOW(),
                     customer_time_base = NOW(),
                     customer_time_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY),
                     updated_at = NOW()
                     WHERE customer_id = ?",
                    [$telesalesId, $customer['customer_id']]
                );
            }

            // ดึงชื่อ Telesales
            $telesalesInfo = $this->db->fetchOne(
                "SELECT full_name FROM users WHERE user_id = ?",
                [$telesalesId]
            );

            // บันทึกประวัติการแจก
            // TODO: Create distribution_logs table or use alternative logging method
            // $this->db->execute(
            //     "INSERT INTO distribution_logs (admin_user_id, distribution_type, company, customer_count, telesales_count, created_at)
            //      VALUES (?, 'request', ?, ?, 1, NOW())",
            //     [$adminUserId, $companySource, count($customers)]
            // );

            $this->db->commit();

            return [
                'success' => true,
                'message' => "แจกลูกค้าตามคำขอสำเร็จ " . count($customers) . " คน",
                'data' => [
                    'distributed_count' => count($customers),
                    'telesales_name' => $telesalesInfo['full_name'] ?? 'ไม่ทราบชื่อ',
                    'customers' => $customers
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error in distributeRequest: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแจกลูกค้า: ' . $e->getMessage()
            ];
        }
    }

    /**
     * อ่านบริษัทของผู้ใช้งานปัจจุบันจาก session และคืนค่าเป็น source เช่น 'Prima' หรือ 'Prionic'
     */
    private function getCurrentCompanySource() {
        try {
            if (!isset($_SESSION)) { @session_start(); }
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) return null;
            $row = $this->db->fetchOne("SELECT c.company_name, c.company_code FROM users u LEFT JOIN companies c ON u.company_id = c.company_id WHERE u.user_id = ?", [$userId]);
            $name = trim($row['company_code'] ?? $row['company_name'] ?? '');
            if ($name === '') return null;
            if (stripos($name, 'prionic') !== false) return 'PRIONIC';
            if (stripos($name, 'prima') !== false) return 'PRIMA';
            return strtoupper($name);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * ดึงรายการลูกค้าที่พร้อมแจก
     */
    public function getAvailableCustomers($priority = 'hot_warm_cold', $limit = 10) {
        try {
            $companyId = CompanyContext::getCompanyId($this->db);
            $params = [];
            $sql = "SELECT c.* FROM customers c
                    WHERE c.basket_type = 'distribution'
                    AND c.temperature_status != 'frozen'
                    AND c.is_active = 1
                    AND c.customer_grade NOT IN ('A', 'A+')" . ($companyId ? " AND c.company_id = ?" : "");
            if ($companyId) { $params[] = $companyId; }

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
            $params[] = $limit;

            return $this->db->fetchAll($sql, $params);

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
                    'recall_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'customer_time_base' => date('Y-m-d H:i:s'),
                    'customer_time_expiry' => date('Y-m-d H:i:s', strtotime('+30 days'))
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
                    'customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
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
            error_log("Stack trace: " . $e->getTraceAsString());
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
            error_log("Error getting telesales name: " . $e->getMessage());
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
                'is_current' => 1
            ];

            $this->db->insert('sales_history', $data);
        } catch (Exception $e) {
            error_log("Error logging assignment history: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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
                'activity_description' => $description
            ];

            $this->db->insert('customer_activities', $data);
        } catch (Exception $e) {
            error_log("Error logging customer activity: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * ดึงสถิติลูกค้าเกรด A
     */
    public function getGradeAStats($company = null) {
        try {
            $companyId = CompanyContext::getCompanyId($this->db);
            error_log("getGradeAStats: company_id: " . ($companyId ?? 'null'));
            
            // ลูกค้าเกรด A+
            $gradeAPlusCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE customer_grade = 'A+' 
                 AND basket_type = 'distribution' 
                 AND is_active = 1" . 
                 ($companyId ? " AND company_id = ?" : ""),
                $companyId ? [$companyId] : []
            );

            // ลูกค้าเกรด A
            $gradeACount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM customers 
                 WHERE customer_grade = 'A' 
                 AND basket_type = 'distribution' 
                 AND is_active = 1" . 
                 ($companyId ? " AND company_id = ?" : ""),
                $companyId ? [$companyId] : []
            );

            $gradeAPlusTotal = $gradeAPlusCount['count'] ?? 0;
            $gradeATotal = $gradeACount['count'] ?? 0;
            $totalGradeA = $gradeAPlusTotal + $gradeATotal;

            $result = [
                'success' => true,
                'data' => [
                    'grade_a_plus_count' => $gradeAPlusTotal,
                    'grade_a_count' => $gradeATotal,
                    'total_grade_a' => $totalGradeA
                ]
            ];

            error_log("getGradeAStats: Result: " . json_encode($result));
            return $result;

        } catch (Exception $e) {
            error_log("Error getting Grade A stats: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดสถิติเกรด A: ' . $e->getMessage(),
                'data' => [
                    'grade_a_plus_count' => 0,
                    'grade_a_count' => 0,
                    'total_grade_a' => 0
                ]
            ];
        }
    }

    /**
     * แจกลูกค้าเกรด A แบบ Dynamic Allocation
     */
    public function distributeGradeA($company = null, $allocations = [], $selectedGrades = ['A']) {
        try {
            // ใช้ company_id จากบริบทปัจจุบัน ไม่พึ่งพา string ชื่อบริษัท
            $companyId = CompanyContext::getCompanyId($this->db);
            if (empty($allocations)) {
                throw new Exception('ไม่มีการจัดสรรลูกค้า');
            }

            // ป้องกัน selectedGrades ว่างเปล่า
            if (empty($selectedGrades)) {
                $selectedGrades = ['A'];
            }

            // สร้าง placeholders สำหรับ selectedGrades
            $gradePlaceholders = str_repeat('?,', count($selectedGrades) - 1) . '?';

            // ตรวจสอบจำนวนลูกค้าตามเกรดที่เลือกที่มีอยู่
            $gradeParams = array_merge($selectedGrades, [$companyId]);
            $availableCustomers = $this->db->fetchAll(
                "SELECT customer_id FROM customers 
                 WHERE customer_grade IN ($gradePlaceholders) 
                 AND basket_type = 'distribution' 
                 AND is_active = 1 
                 AND company_id = ?
                 ORDER BY 
                    CASE WHEN customer_grade = 'A+' THEN 1 ELSE 2 END,
                    CASE WHEN temperature_status = 'hot' THEN 1 
                         WHEN temperature_status = 'warm' THEN 2 
                         ELSE 3 END,
                    created_at ASC",
                $gradeParams
            );

            $totalAvailable = count($availableCustomers);
            $totalRequested = array_sum(array_column($allocations, 'count'));

            if ($totalRequested > $totalAvailable) {
                throw new Exception("จำนวนที่จัดสรรเกินจำนวนที่มี ({$totalRequested}/{$totalAvailable})");
            }

            // เริ่มการแจก
            $this->db->beginTransaction();
            
            $distributionResults = [];
            $customerIndex = 0;

            foreach ($allocations as $allocation) {
                $telesalesId = $allocation['telesales_id'] ?? 0;
                $count = $allocation['count'] ?? 0;

                if ($count <= 0) continue;

                // ตรวจสอบ Telesales
                $telesales = $this->db->fetchOne(
                    "SELECT user_id, full_name FROM users WHERE user_id = ? AND role_id = 4 AND is_active = 1",
                    [$telesalesId]
                );

                if (!$telesales) {
                    throw new Exception("ไม่พบ Telesales ID: {$telesalesId}");
                }

                // จัดสรรลูกค้าให้ Telesales คนนี้
                $assignedCount = 0;
                $assignedCustomers = []; // เก็บรายชื่อลูกค้าที่แจกให้คนนี้
                
                for ($i = 0; $i < $count && $customerIndex < $totalAvailable; $i++, $customerIndex++) {
                    $customerId = $availableCustomers[$customerIndex]['customer_id'];
                    
                    // ดึงข้อมูลลูกค้าก่อนอัปเดต
                    $customerInfo = $this->db->fetchOne(
                        "SELECT customer_id, customer_code, first_name, last_name, phone, customer_grade, temperature_status 
                         FROM customers WHERE customer_id = ?",
                        [$customerId]
                    );
                    
                    // อัปเดตลูกค้า (รีเซ็ต customer_time_base และ customer_time_expiry เป็น 30 วันใหม่)
                    $this->db->update('customers', 
                        [
                            'basket_type' => 'assigned',
                            'assigned_to' => $telesalesId,
                            'assigned_at' => date('Y-m-d H:i:s'),
                            'customer_time_base' => date('Y-m-d H:i:s'),
                            'customer_time_expiry' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'recall_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'customer_status' => 'existing', // อัปเดตสถานะเป็น existing เมื่อได้รับมอบหมาย
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'customer_id = ?',
                        [$customerId]
                    );

                    // บันทึกประวัติการมอบหมาย
                    $this->logAssignmentHistory($customerId, $telesalesId, $_SESSION['user_id'] ?? 1);

                    // บันทึกกิจกรรม
                    $this->logCustomerActivity($customerId, $telesalesId, 'grade_a_assigned', 
                        "แจกลูกค้าเกรด A ให้ {$telesales['full_name']}");

                    // เพิ่มข้อมูลลูกค้าในรายการ
                    $assignedCustomers[] = [
                        'customer_id' => $customerInfo['customer_id'],
                        'customer_code' => $customerInfo['customer_code'],
                        'name' => $customerInfo['first_name'] . ' ' . $customerInfo['last_name'],
                        'phone' => $customerInfo['phone'],
                        'grade' => $customerInfo['customer_grade'],
                        'temperature' => $customerInfo['temperature_status'],
                        'time_base' => date('Y-m-d H:i:s'), // วันที่เริ่มนับใหม่
                        'time_expiry' => date('Y-m-d H:i:s', strtotime('+30 days')), // วันที่หมดอายุ (30 วันใหม่)
                        'days_remaining' => 30 // จำนวนวันที่เหลือ (30 วันเต็ม)
                    ];

                    $assignedCount++;
                }

                $distributionResults[] = [
                    'telesales_id' => $telesalesId,
                    'telesales_name' => $telesales['full_name'],
                    'company' => $companyId,
                    'count' => $assignedCount,
                    'grade' => 'A',
                    'customers' => $assignedCustomers // เพิ่มรายชื่อลูกค้า
                ];
            }

            $this->db->commit();

            $totalDistributed = array_sum(array_column($distributionResults, 'count'));

            return [
                'success' => true,
                'message' => "แจกลูกค้าเกรด A สำเร็จ {$totalDistributed} คน",
                'data' => [
                    'distributions' => $distributionResults,
                    'total_distributed' => $totalDistributed,
                    'company_id' => $companyId,
                    'selected_grades' => $selectedGrades
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error distributing Grade A customers: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแจกลูกค้าเกรด A: ' . $e->getMessage()
            ];
        }
    }
}
