<?php
/**
 * DashboardService Class
 * จัดการข้อมูลสำหรับหน้า Dashboard
 */

require_once __DIR__ . '/../core/Database.php';

class DashboardService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * ดึงข้อมูล KPI สำหรับ Dashboard
     */
    public function getDashboardData($userId = null, $role = null) {
        try {
            // สำหรับ supervisor ใช้ข้อมูลเฉพาะทีม
            if ($role === 'supervisor') {
                $data = [
                    'total_customers' => $this->getTeamTotalCustomers($userId),
                    'hot_customers' => $this->getTeamHotCustomers($userId),
                    'total_orders' => $this->getTeamTotalOrders($userId),
                    'total_sales' => $this->getTeamTotalSales($userId),
                    'monthly_sales' => $this->getTeamMonthlySales($userId),
                    'recent_activities' => $this->getTeamRecentActivities($userId),
                    'customer_grades' => $this->getTeamCustomerGrades($userId),
                    'order_status' => $this->getTeamOrderStatus($userId)
                ];
            } else {
                // สำหรับ admin และ super_admin ใช้ข้อมูลทั้งหมด
                $data = [
                    'total_customers' => $this->getTotalCustomers(),
                    'hot_customers' => $this->getHotCustomers(),
                    'total_orders' => $this->getTotalOrders(),
                    'total_sales' => $this->getTotalSales(),
                    'monthly_sales' => $this->getMonthlySales(),
                    'recent_activities' => $this->getRecentActivities($userId),
                    'customer_grades' => $this->getCustomerGrades(),
                    'order_status' => $this->getOrderStatus()
                ];
            }
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * นับจำนวนลูกค้าทั้งหมด
     */
    private function getTotalCustomers() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM customers");
        return $result['count'] ?? 0;
    }
    
    /**
     * นับจำนวนลูกค้า Hot
     */
    private function getHotCustomers() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM customers WHERE temperature_status = 'hot'");
        return $result['count'] ?? 0;
    }
    
    /**
     * นับจำนวนคำสั่งซื้อทั้งหมด
     */
    private function getTotalOrders() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM orders");
        return $result['count'] ?? 0;
    }
    
    /**
     * คำนวณยอดขายรวม
     */
    private function getTotalSales() {
        $result = $this->db->fetchOne("SELECT SUM(net_amount) as total FROM orders WHERE payment_status = 'paid'");
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงข้อมูลยอดขายรายเดือน
     */
    private function getMonthlySales() {
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    SUM(net_amount) as total_sales,
                    COUNT(*) as order_count
                FROM orders 
                WHERE payment_status = 'paid' 
                AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงกิจกรรมล่าสุด
     */
    private function getRecentActivities($userId = null) {
        $sql = "SELECT 
                    'order' as type,
                    o.order_number as title,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    o.created_at as date,
                    'สร้างคำสั่งซื้อใหม่' as description
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                SELECT 
                    'customer' as type,
                    CONCAT(c.first_name, ' ', c.last_name) as title,
                    c.phone as customer_name,
                    c.created_at as date,
                    'เพิ่มลูกค้าใหม่' as description
                FROM customers c
                WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                ORDER BY date DESC
                LIMIT 10";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงข้อมูลเกรดลูกค้า
     */
    private function getCustomerGrades() {
        $sql = "SELECT 
                    customer_grade,
                    COUNT(*) as count
                FROM customers 
                GROUP BY customer_grade
                ORDER BY 
                    CASE customer_grade
                        WHEN 'A' THEN 1
                        WHEN 'B' THEN 2
                        WHEN 'C' THEN 3
                        WHEN 'D' THEN 4
                        ELSE 5
                    END";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงข้อมูลสถานะคำสั่งซื้อ
     */
    private function getOrderStatus() {
        $sql = "SELECT 
                    payment_status,
                    COUNT(*) as count
                FROM orders 
                GROUP BY payment_status";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงข้อมูลสำหรับ Telesales Dashboard
     */
    public function getTelesalesDashboard($userId) {
        try {
            $data = [
                'assigned_customers' => $this->getAssignedCustomers($userId),
                'follow_up_customers' => $this->getFollowUpCustomers($userId),
                'today_orders' => $this->getTodayOrders($userId),
                'monthly_performance' => $this->getMonthlyPerformance($userId)
            ];
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * นับลูกค้าที่มอบหมายให้
     */
    private function getAssignedCustomers($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM customers WHERE assigned_to = :user_id",
            ['user_id' => $userId]
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * นับลูกค้าที่ต้องติดตาม
     */
    private function getFollowUpCustomers($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM customers 
             WHERE assigned_to = :user_id 
             AND basket_type = 'assigned'
             AND is_active = 1
             AND (
                 customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR
                 next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
             )",
            ['user_id' => $userId]
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * นับคำสั่งซื้อวันนี้
     */
    private function getTodayOrders($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders WHERE created_by = :user_id AND DATE(created_at) = CURDATE()",
            ['user_id' => $userId]
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * ดึงข้อมูลประสิทธิภาพรายเดือน
     */
    private function getMonthlyPerformance($userId) {
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    COUNT(*) as orders,
                    SUM(net_amount) as sales
                FROM orders 
                WHERE created_by = :user_id 
                AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC";
        
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    /**
     * ดึงข้อมูลประสิทธิภาพรายวันสำหรับเดือนที่เลือก (สำหรับ Telesales)
     * - แกน X: วันที่ 1 ถึงสิ้นเดือน
     * - แกน Y (ซ้าย): ยอดขายต่อวัน (net_amount)
     * - แกน Y (ขวา): จำนวนรายชื่อลูกค้าที่ติดต่อ (distinct customer_id) ต่อวัน
     */
    public function getDailyPerformanceForMonth(int $userId, string $yearMonth): array {
        // Validate input month format YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $yearMonth = date('Y-m');
        }

        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Initialize arrays for every day of the month
        $labels = [];
        $salesByDay = [];
        $contactsByDay = [];
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );

        foreach ($period as $date) {
            $dayLabel = $date->format('j'); // 1..31
            $labels[] = $dayLabel;
            $salesByDay[$date->format('Y-m-d')] = 0.0;
            $contactsByDay[$date->format('Y-m-d')] = 0;
        }

        // Fetch daily sales (use order_date) for the month
        $salesRows = $this->db->fetchAll(
            "SELECT DATE(order_date) as d, SUM(net_amount) as total_sales
             FROM orders
             WHERE created_by = :user_id
               AND payment_status = 'paid'
               AND order_date BETWEEN :start_date AND :end_date
             GROUP BY DATE(order_date)",
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        foreach ($salesRows as $row) {
            $dayKey = $row['d'];
            $salesByDay[$dayKey] = (float) ($row['total_sales'] ?? 0);
        }

        // Fetch daily orders count
        $ordersRows = $this->db->fetchAll(
            "SELECT DATE(order_date) as d, COUNT(*) as order_count
             FROM orders
             WHERE created_by = :user_id
               AND order_date BETWEEN :start_date AND :end_date
             GROUP BY DATE(order_date)",
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        $ordersByDay = [];
        foreach ($period as $date) {
            $ordersByDay[$date->format('Y-m-d')] = 0;
        }
        foreach ($ordersRows as $row) {
            $dayKey = $row['d'];
            $ordersByDay[$dayKey] = (int) ($row['order_count'] ?? 0);
        }

        // Fetch daily contacts from call_logs (distinct customers contacted per day)
        $contactRows = $this->db->fetchAll(
            "SELECT DATE(created_at) as d, COUNT(DISTINCT customer_id) as contact_count
             FROM call_logs
             WHERE user_id = :user_id
               AND created_at BETWEEN :start_date_time AND :end_date_time
             GROUP BY DATE(created_at)",
            [
                'user_id' => $userId,
                'start_date_time' => $startDate . ' 00:00:00',
                'end_date_time' => $endDate . ' 23:59:59',
            ]
        );

        foreach ($contactRows as $row) {
            $dayKey = $row['d'];
            $contactsByDay[$dayKey] = (int) ($row['contact_count'] ?? 0);
        }

        // Build ordered arrays aligned with labels
        $salesSeries = [];
        $contactsSeries = [];
        $ordersSeries = [];
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $salesSeries[] = $salesByDay[$key];
            $contactsSeries[] = $contactsByDay[$key];
            $ordersSeries[] = $ordersByDay[$key];
        }

        // Fetch daily sales by category
        $categorySalesRows = $this->db->fetchAll(
            "SELECT DATE(o.order_date) as d, p.category, SUM(oi.total_price) as category_sales
             FROM orders o
             JOIN order_items oi ON o.order_id = oi.order_id
             JOIN products p ON oi.product_id = p.product_id
             WHERE o.created_by = :user_id
               AND o.payment_status = 'paid'
               AND o.order_date BETWEEN :start_date AND :end_date
             GROUP BY DATE(o.order_date), p.category",
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        // Initialize category arrays
        $categories = ['ปุ๋ยกระสอบใหญ่', 'ปุ๋ยกระสอบเล็ก', 'ชีวภัณฑ์', 'ของแถม'];
        $categorySalesByDay = [];

        foreach ($categories as $category) {
            $categorySalesByDay[$category] = [];
            foreach ($period as $date) {
                $categorySalesByDay[$category][$date->format('Y-m-d')] = 0.0;
            }
        }

        // Fill category sales data
        foreach ($categorySalesRows as $row) {
            $dayKey = $row['d'];
            $category = $row['category'];
            if (in_array($category, $categories)) {
                $categorySalesByDay[$category][$dayKey] = (float) ($row['category_sales'] ?? 0);
            }
        }

        // Build category series arrays
        $categorySeries = [];
        foreach ($categories as $category) {
            $series = [];
            foreach ($period as $date) {
                $key = $date->format('Y-m-d');
                $series[] = $categorySalesByDay[$category][$key];
            }
            $categorySeries[$category] = $series;
        }

        return [
            'labels' => $labels,
            'sales' => $salesSeries,
            'orders' => $ordersSeries,
            'contacts' => $contactsSeries,
            // Category sales for stack column chart
            'fertilizer_large' => $categorySeries['ปุ๋ยกระสอบใหญ่'],
            'fertilizer_small' => $categorySeries['ปุ๋ยกระสอบเล็ก'],
            'bio_products' => $categorySeries['ชีวภัณฑ์'],
            'freebies' => $categorySeries['ของแถม'],
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * ดึง KPI รายเดือนสำหรับ Telesales ตามเดือนที่เลือก
     * - ยอดขายประจำเดือน (sum net_amount ของคำสั่งซื้อที่ผู้ใช้สร้าง)
     * - คำสั่งซื้อประจำเดือน (count คำสั่งซื้อ)
     * - ยอดขายตามหมวดสินค้า (ปุ๋ยกระสอบใหญ่, ปุ๋ยกระสอบเล็ก, ชีวภัณฑ์)
     */
    public function getMonthlyKpisForTelesales(int $userId, string $yearMonth): array {
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $yearMonth = date('Y-m');
        }

        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Total monthly sales
        $rowSales = $this->db->fetchOne(
            "SELECT COALESCE(SUM(net_amount), 0) AS total_sales
             FROM orders
             WHERE created_by = :user_id
               AND payment_status = 'paid'
               AND order_date BETWEEN :start_date AND :end_date",
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        // Total monthly orders
        $rowOrders = $this->db->fetchOne(
            "SELECT COUNT(*) AS total_orders
             FROM orders
             WHERE created_by = :user_id
               AND order_date BETWEEN :start_date AND :end_date",
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        // Helper: aggregate category totals (amount, quantity) and most common unit
        $categoryAgg = function(string $category) use ($userId, $startDate, $endDate) {
            $row = $this->db->fetchOne(
                "SELECT 
                        COALESCE(SUM(oi.total_price), 0) AS total_amount,
                        COALESCE(SUM(oi.quantity), 0) AS total_quantity
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.order_id
                 JOIN products p ON oi.product_id = p.product_id
                 WHERE o.created_by = :user_id
                   AND o.payment_status = 'paid'
                   AND o.order_date BETWEEN :start_date AND :end_date
                   AND p.category = :category",
                [
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'category' => $category,
                ]
            );

            // Find most common unit in this category for the month
            $unitRow = $this->db->fetchOne(
                "SELECT p.unit, COUNT(*) as cnt
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.order_id
                 JOIN products p ON oi.product_id = p.product_id
                 WHERE o.created_by = :user_id
                   AND o.payment_status = 'paid'
                   AND o.order_date BETWEEN :start_date AND :end_date
                   AND p.category = :category
                 GROUP BY p.unit
                 ORDER BY cnt DESC
                 LIMIT 1",
                [
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'category' => $category,
                ]
            );

            return [
                'amount' => (float) ($row['total_amount'] ?? 0),
                'quantity' => (int) ($row['total_quantity'] ?? 0),
                'unit' => $unitRow['unit'] ?? 'หน่วย',
            ];
        };

        $big = $categoryAgg('ปุ๋ยกระสอบใหญ่');
        $small = $categoryAgg('ปุ๋ยกระสอบเล็ก');
        $bio = $categoryAgg('ชีวภัณฑ์');
        $freebies = $categoryAgg('ของแถม');

        return [
            'month' => $yearMonth,
            'total_sales' => (float) ($rowSales['total_sales'] ?? 0),
            'total_orders' => (int) ($rowOrders['total_orders'] ?? 0),
            // ปุ๋ยกระสอบใหญ่
            'fertilizer_large_sales' => $big['amount'],
            'fertilizer_large_qty' => $big['quantity'],
            'fertilizer_large_unit' => $big['unit'],
            // ปุ๋ยกระสอบเล็ก
            'fertilizer_small_sales' => $small['amount'],
            'fertilizer_small_qty' => $small['quantity'],
            'fertilizer_small_unit' => $small['unit'],
            // ชีวภัณฑ์
            'bio_products_sales' => $bio['amount'],
            'bio_products_qty' => $bio['quantity'],
            'bio_products_unit' => $bio['unit'],
            // ของแถม
            'freebies_sales' => $freebies['amount'],
            'freebies_qty' => $freebies['quantity'],
            'freebies_unit' => $freebies['unit'],
        ];
    }
    
    // ========== Supervisor Team Methods ==========
    
    /**
     * ดึง user_id ของสมาชิกทีมทั้งหมด
     */
    private function getTeamMemberIds($supervisorId) {
        $teamMembers = $this->db->fetchAll(
            "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
            ['supervisor_id' => $supervisorId]
        );
        
        $teamMemberIds = [];
        foreach ($teamMembers as $member) {
            $teamMemberIds[] = $member['user_id'];
        }
        
        return $teamMemberIds;
    }
    
    /**
     * นับจำนวนลูกค้าทั้งหมดของทีม
     */
    private function getTeamTotalCustomers($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM customers WHERE assigned_to IN ($placeholders)",
            $teamMemberIds
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * นับจำนวนลูกค้า Hot ของทีม
     */
    private function getTeamHotCustomers($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM customers WHERE assigned_to IN ($placeholders) AND temperature_status = 'hot'",
            $teamMemberIds
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * นับจำนวนคำสั่งซื้อทั้งหมดของทีม
     */
    private function getTeamTotalOrders($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders WHERE created_by IN ($placeholders)",
            $teamMemberIds
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * คำนวณยอดขายรวมของทีม
     */
    private function getTeamTotalSales($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $result = $this->db->fetchOne(
            "SELECT SUM(net_amount) as total FROM orders WHERE created_by IN ($placeholders) AND payment_status = 'paid'",
            $teamMemberIds
        );
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงข้อมูลยอดขายรายเดือนของทีม
     */
    private function getTeamMonthlySales($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    SUM(net_amount) as total_sales,
                    COUNT(*) as order_count
                FROM orders 
                WHERE created_by IN ($placeholders)
                AND payment_status = 'paid' 
                AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC";
        
        return $this->db->fetchAll($sql, $teamMemberIds);
    }
    
    /**
     * ดึงกิจกรรมล่าสุดของทีม
     */
    private function getTeamRecentActivities($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $sql = "SELECT 
                    'order' as type,
                    o.order_number as title,
                    o.created_at as date,
                    u.full_name as user_name,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    o.net_amount as amount
                FROM orders o
                JOIN users u ON o.created_by = u.user_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.created_by IN ($placeholders)
                
                UNION ALL
                
                SELECT 
                    'customer' as type,
                    CONCAT('ลูกค้าใหม่: ', c.first_name, ' ', c.last_name) as title,
                    c.assigned_at as date,
                    u.full_name as user_name,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    0 as amount
                FROM customers c
                JOIN users u ON c.assigned_to = u.user_id
                WHERE c.assigned_to IN ($placeholders) AND c.assigned_at IS NOT NULL
                
                ORDER BY date DESC
                LIMIT 10";
        
        return $this->db->fetchAll($sql, array_merge($teamMemberIds, $teamMemberIds));
    }
    
    /**
     * ดึงข้อมูลเกรดลูกค้าของทีม
     */
    private function getTeamCustomerGrades($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $sql = "SELECT 
                    customer_grade,
                    COUNT(*) as count
                FROM customers 
                WHERE assigned_to IN ($placeholders)
                GROUP BY customer_grade
                ORDER BY 
                    CASE customer_grade
                        WHEN 'A' THEN 1
                        WHEN 'B' THEN 2
                        WHEN 'C' THEN 3
                        WHEN 'D' THEN 4
                        ELSE 5
                    END";
        
        return $this->db->fetchAll($sql, $teamMemberIds);
    }
    
    /**
     * ดึงข้อมูลสถานะคำสั่งซื้อของทีม
     */
    private function getTeamOrderStatus($supervisorId) {
        $teamMemberIds = $this->getTeamMemberIds($supervisorId);
        if (empty($teamMemberIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $sql = "SELECT 
                    payment_status,
                    COUNT(*) as count
                FROM orders 
                WHERE created_by IN ($placeholders)
                GROUP BY payment_status";
        
        return $this->db->fetchAll($sql, $teamMemberIds);
    }
}
?> 