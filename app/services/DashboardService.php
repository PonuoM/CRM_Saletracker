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
            "SELECT COUNT(*) as count FROM customers WHERE assigned_to = :user_id AND basket_type = 'follow_up'",
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
}
?> 