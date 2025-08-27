<?php
/**
 * Search Service
 * จัดการค้นหาลูกค้าและยอดขายภายในบริษัทเดียวกัน
 */

require_once __DIR__ . '/../core/Database.php';

class SearchService {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * ดึง source ของผู้ใช้ปัจจุบัน
     */
    public function getCurrentUserSource() {
        try {
            if (!isset($_SESSION)) { @session_start(); }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) return null;
            
            $row = $this->db->fetchOne(
                "SELECT c.company_name, c.company_code 
                 FROM users u 
                 LEFT JOIN companies c ON u.company_id = c.company_id 
                 WHERE u.user_id = ?", 
                [$userId]
            );
            
            $name = trim($row['company_code'] ?? $row['company_name'] ?? '');
            if ($name === '') return null;
            
            if (stripos($name, 'prionic') !== false) return 'PRIONIC';
            if (stripos($name, 'prima') !== false) return 'PRIMA';
            
            return strtoupper($name);
            
        } catch (Exception $e) {
            error_log("Error getting user source: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ค้นหาลูกค้าตามชื่อหรือเบอร์โทร (แค่ข้อมูลพื้นฐาน)
     */
    public function searchCustomers($searchTerm) {
        try {
            $userSource = $this->getCurrentUserSource();
            
            $sql = "SELECT 
                        c.customer_id,
                        c.customer_code,
                        CONCAT(c.first_name, ' ', c.last_name) as full_name,
                        c.phone,
                        c.email,
                        c.customer_grade,
                        c.total_purchase_amount,
                        c.source,
                        COUNT(DISTINCT o.order_id) as total_orders,
                        0 as total_calls,
                        NULL as last_contact_date
                    FROM customers c
                    LEFT JOIN orders o ON c.customer_id = o.customer_id
                    WHERE c.is_active = 1";
            
            $params = [];
            
            // จำกัดเฉพาะ source ของบริษัทตัวเอง
            if ($userSource) {
                $sql .= " AND c.source = ?";
                $params[] = $userSource;
            }
            
            // เงื่อนไขการค้นหา (ชื่อ หรือ เบอร์โทร)
            $sql .= " AND (
                        CONCAT(c.first_name, ' ', c.last_name) LIKE ? 
                        OR c.phone LIKE ?
                        OR c.first_name LIKE ?
                        OR c.last_name LIKE ?
                    )";
            
            $searchPattern = '%' . $searchTerm . '%';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            
            $sql .= " GROUP BY c.customer_id
                      ORDER BY c.total_purchase_amount DESC, c.updated_at DESC
                      LIMIT 20";
            
            return $this->db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Error searching customers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงข้อมูลลูกค้าแบบละเอียด
     */
    public function getCustomerById($customerId) {
        try {
            $userSource = $this->getCurrentUserSource();
            
            $sql = "SELECT 
                        c.*,
                        CONCAT(c.first_name, ' ', c.last_name) as full_name,
                        COUNT(DISTINCT o.order_id) as total_orders,
                        COALESCE(SUM(o.net_amount), 0) as total_purchase_amount_calculated,
                        COUNT(DISTINCT cl.log_id) as total_calls,
                        MAX(cl.created_at) as last_contact_date,
                        u.full_name as assigned_to_name
                    FROM customers c
                    LEFT JOIN orders o ON c.customer_id = o.customer_id
                    LEFT JOIN call_logs cl ON c.customer_id = cl.customer_id
                    LEFT JOIN users u ON c.assigned_to = u.user_id
                    WHERE c.customer_id = ?";
            
            $params = [$customerId];
            
            // จำกัดเฉพาะ source ของบริษัทตัวเอง
            if ($userSource) {
                $sql .= " AND c.source = ?";
                $params[] = $userSource;
            }
            
            $sql .= " GROUP BY c.customer_id";
            
            $customer = $this->db->fetchOne($sql, $params);
            
            if (!$customer) {
                return null;
            }
            
            // ดึงประวัติคำสั่งซื้อ
            $customer['orders'] = $this->getCustomerOrders($customerId);
            
            return $customer;
            
        } catch (Exception $e) {
            error_log("Error getting customer by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงประวัติคำสั่งซื้อของลูกค้า
     */
    public function getCustomerOrders($customerId) {
        try {
            $userSource = $this->getCurrentUserSource();
            
            $sql = "SELECT 
                        o.order_id,
                        o.order_number,
                        o.order_date,
                        o.total_amount,
                        o.net_amount,
                        o.payment_status,
                        o.delivery_status,
                        COALESCE(u.full_name, CONCAT('User ', o.created_by)) as created_by_name,
                        0 as item_count
                    FROM orders o
                    LEFT JOIN users u ON o.created_by = u.user_id
                    LEFT JOIN customers c ON o.customer_id = c.customer_id
                    WHERE o.customer_id = ? AND o.is_active = 1";
            
            $params = [$customerId];
            
            // จำกัดเฉพาะ source ของบริษัทตัวเอง
            if ($userSource) {
                $sql .= " AND c.source = ?";
                $params[] = $userSource;
            }
            
            $sql .= " GROUP BY o.order_id
                      ORDER BY o.order_date DESC";
            
            return $this->db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Error getting customer orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงรายละเอียดคำสั่งซื้อพร้อมสินค้า
     */
    public function getOrderDetails($orderId) {
        try {
            $userSource = $this->getCurrentUserSource();
            
            // ดึงข้อมูลคำสั่งซื้อ
            $sql = "SELECT 
                        o.*,
                        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                        c.phone as customer_phone,
                        u.full_name as created_by_name
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.customer_id
                    LEFT JOIN users u ON o.created_by = u.user_id
                    WHERE o.order_id = ?";
            
            $params = [$orderId];
            
            // จำกัดเฉพาะ source ของบริษัทตัวเอง
            if ($userSource) {
                $sql .= " AND c.source = ?";
                $params[] = $userSource;
            }
            
            $order = $this->db->fetchOne($sql, $params);
            
            if (!$order) {
                return null;
            }
            
            // ดึงรายการสินค้าในคำสั่งซื้อ
            $order['items'] = $this->getOrderItems($orderId);
            
            return $order;
            
        } catch (Exception $e) {
            error_log("Error getting order details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงรายการสินค้าในคำสั่งซื้อ
     */
    private function getOrderItems($orderId) {
        try {
            $sql = "SELECT 
                        oi.*,
                        p.product_name,
                        p.product_code
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.product_id
                    WHERE oi.order_id = ?
                    ORDER BY oi.order_item_id";
            
            return $this->db->fetchAll($sql, [$orderId]);
            
        } catch (Exception $e) {
            error_log("Error getting order items: " . $e->getMessage());
            return [];
        }
    }
}
