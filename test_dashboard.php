<?php
/**
 * Test Dashboard for Admin
 * ทดสอบ dashboard สำหรับ admin
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

echo "<h1>🧪 ทดสอบ Dashboard สำหรับ Admin</h1>";

$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

echo "<p><strong>Role:</strong> $roleName</p>";
echo "<p><strong>User ID:</strong> $userId</p>";

// Test database connection
try {
    require_once __DIR__ . '/app/core/Database.php';
    $db = new Database();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test basic queries
    echo "<h2>📊 ทดสอบ Queries</h2>";
    
    // Test customers count
    try {
        $sql = "SELECT COUNT(*) as count FROM customers";
        $result = $db->fetchOne($sql);
        echo "<p>✅ ลูกค้าทั้งหมด: " . number_format($result['count']) . " คน</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error counting customers: " . $e->getMessage() . "</p>";
    }
    
    // Test orders count and total
    try {
        $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders";
        $result = $db->fetchOne($sql);
        echo "<p>✅ ออเดอร์ทั้งหมด: " . number_format($result['count']) . " ออเดอร์</p>";
        echo "<p>✅ รายได้รวม: ฿" . number_format($result['total'] ?? 0, 2) . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error counting orders: " . $e->getMessage() . "</p>";
    }
    
    // Test monthly orders
    try {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
                FROM orders 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month 
                ORDER BY month DESC";
        $monthlyOrders = $db->fetchAll($sql);
        echo "<p>✅ ข้อมูลรายเดือน: " . count($monthlyOrders) . " เดือน</p>";
        
        if (!empty($monthlyOrders)) {
            echo "<ul>";
            foreach (array_slice($monthlyOrders, 0, 3) as $month) {
                echo "<li>" . $month['month'] . ": " . $month['count'] . " ออเดอร์, ฿" . number_format($month['total'], 2) . "</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error getting monthly orders: " . $e->getMessage() . "</p>";
    }
    
    // Test customer grades
    try {
        $sql = "SELECT customer_grade AS grade, COUNT(*) as count FROM customers GROUP BY customer_grade ORDER BY customer_grade";
        $customerGrades = $db->fetchAll($sql);
        echo "<p>✅ เกรดลูกค้า: " . count($customerGrades) . " เกรด</p>";
        
        if (!empty($customerGrades)) {
            echo "<ul>";
            foreach ($customerGrades as $grade) {
                echo "<li>" . ($grade['grade'] ?: 'ไม่ระบุ') . ": " . $grade['count'] . " คน</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error getting customer grades: " . $e->getMessage() . "</p>";
    }
    
    // Test order statuses
    try {
        $sql = "SELECT delivery_status, COUNT(*) as count FROM orders GROUP BY delivery_status";
        $orderStatuses = $db->fetchAll($sql);
        echo "<p>✅ สถานะการจัดส่ง: " . count($orderStatuses) . " สถานะ</p>";
        
        if (!empty($orderStatuses)) {
            echo "<ul>";
            foreach ($orderStatuses as $status) {
                echo "<li>" . ($status['delivery_status'] ?: 'ไม่ระบุ') . ": " . $status['count'] . " ออเดอร์</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error getting order statuses: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test if we can include the reports view
echo "<h2>📄 ทดสอบ Reports View</h2>";

try {
    // Prepare data like in dashboard.php
    $stats = [
        'total_customers' => 426,
        'total_orders' => 702,
        'total_revenue' => 1500000,
        'monthly_orders' => [
            ['month' => '2025-08', 'count' => 50, 'total' => 150000],
            ['month' => '2025-07', 'count' => 45, 'total' => 135000]
        ],
        'customer_grades' => [
            ['grade' => 'A+', 'count' => 100],
            ['grade' => 'A', 'count' => 150],
            ['grade' => 'B', 'count' => 176]
        ],
        'order_statuses' => [
            ['delivery_status' => 'delivered', 'count' => 500],
            ['delivery_status' => 'shipped', 'count' => 150],
            ['delivery_status' => 'pending', 'count' => 52]
        ]
    ];
    
    $currentPage = 'dashboard';
    $error = null;
    
    echo "<p>✅ ข้อมูลทดสอบพร้อม</p>";
    
    // Try to include the view
    ob_start();
    include __DIR__ . '/app/views/reports/index.php';
    $content = ob_get_clean();
    
    echo "<p style='color: green;'>✅ Reports view loaded successfully!</p>";
    echo "<p>Content length: " . strlen($content) . " characters</p>";
    
    // Show a preview
    echo "<h3>🔍 Preview (first 500 characters):</h3>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading reports view: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🏠 ทดสอบ Dashboard จริง</a>";
echo "<a href='reports.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 ดู Reports</a>";
echo "</div>";

?>
