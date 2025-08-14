<?php
/**
 * Manual Test Cron Jobs
 * ทดสอบ cron jobs แบบ manual เพื่อดูปัญหา
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Manual Test Cron Jobs</h1>";

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>1. ทดสอบ Basket Management</h2>";
    
    // ดูลูกค้าปัจจุบัน
    echo "<h3>📊 สถิติลูกค้าก่อนทำงาน</h3>";
    $sql = "SELECT basket_type, COUNT(*) as count FROM customers GROUP BY basket_type";
    $stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Basket Type</th><th>Count</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr><td>" . ($stat['basket_type'] ?: 'NULL') . "</td><td>" . $stat['count'] . "</td></tr>";
    }
    echo "</table>";
    
    // ทดสอบ SQL สำหรับ basket management
    echo "<h3>🔍 ทดสอบ SQL Queries</h3>";
    
    // 1. หาลูกค้าใหม่ที่หมดเวลา (>30 วัน)
    echo "<h4>1. ลูกค้าใหม่ที่หมดเวลา (>30 วัน)</h4>";
    $sql = "
        SELECT customer_id, CONCAT(first_name, ' ', last_name) as name, 
               assigned_at, DATEDIFF(NOW(), assigned_at) as days_assigned
        FROM customers 
        WHERE basket_type = 'assigned'
        AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND customer_id NOT IN (
            SELECT DISTINCT customer_id FROM orders 
            WHERE created_at > assigned_at
        )
        LIMIT 5
    ";
    
    try {
        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>ชื่อ</th><th>วันที่มอบหมาย</th><th>จำนวนวัน</th></tr>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $row['customer_id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['assigned_at'] . "</td>";
                echo "<td>" . $row['days_assigned'] . " วัน</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p style='color: green;'>✅ พบลูกค้าใหม่ที่ควร recall: " . count($result) . " รายการ</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ ไม่พบลูกค้าใหม่ที่ควร recall</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // 2. หาลูกค้าเก่าที่ไม่มีออเดอร์ใน 90 วัน
    echo "<h4>2. ลูกค้าเก่าที่ไม่มีออเดอร์ใน 90 วัน</h4>";
    $sql = "
        SELECT c.customer_id, CONCAT(c.first_name, ' ', c.last_name) as name,
               c.assigned_at, MAX(o.order_date) as last_order,
               DATEDIFF(NOW(), MAX(o.order_date)) as days_since_order
        FROM customers c
        LEFT JOIN orders o ON c.customer_id = o.customer_id
        WHERE c.basket_type = 'assigned'
        AND c.customer_id IN (
            SELECT customer_id FROM orders 
            GROUP BY customer_id 
            HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
        )
        GROUP BY c.customer_id
        LIMIT 5
    ";
    
    try {
        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>ชื่อ</th><th>ออเดอร์ล่าสุด</th><th>จำนวนวัน</th></tr>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $row['customer_id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['last_order'] . "</td>";
                echo "<td>" . $row['days_since_order'] . " วัน</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p style='color: green;'>✅ พบลูกค้าเก่าที่ควรไป waiting: " . count($result) . " รายการ</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ ไม่พบลูกค้าเก่าที่ควรไป waiting</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // 3. หาลูกค้าใน waiting ที่ครบ 30 วัน
    echo "<h4>3. ลูกค้าใน waiting ที่ครบ 30 วัน</h4>";
    $sql = "
        SELECT customer_id, CONCAT(first_name, ' ', last_name) as name,
               recall_at, DATEDIFF(NOW(), recall_at) as days_in_waiting
        FROM customers 
        WHERE basket_type = 'waiting'
        AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        LIMIT 5
    ";
    
    try {
        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>ชื่อ</th><th>วันที่เข้า waiting</th><th>จำนวนวัน</th></tr>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $row['customer_id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['recall_at'] . "</td>";
                echo "<td>" . $row['days_in_waiting'] . " วัน</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p style='color: green;'>✅ พบลูกค้าใน waiting ที่ควรกลับ distribution: " . count($result) . " รายการ</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ ไม่พบลูกค้าใน waiting ที่ควรกลับ distribution</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // ทดสอบรัน CronJobService
    echo "<h2>2. ทดสอบรัน CronJobService</h2>";
    
    if (isset($_GET['run_test']) && $_GET['run_test'] === 'yes') {
        if (file_exists('app/services/CronJobService.php')) {
            require_once 'app/services/CronJobService.php';
            
            echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
            echo "<h3>🔄 กำลังรัน CronJobService...</h3>";
            echo "<pre>";
            
            $startTime = microtime(true);
            
            try {
                $cronService = new CronJobService();
                
                // รัน basket management อย่างเดียวก่อน
                echo "=== Testing Basket Management ===\n";
                $result = $cronService->customerBasketManagement();
                
                if ($result['success']) {
                    echo "✅ Basket Management Success:\n";
                    echo "   - New customers recalled: " . $result['new_customers_recalled'] . "\n";
                    echo "   - Existing customers recalled: " . $result['existing_customers_recalled'] . "\n";
                    echo "   - Moved to distribution: " . $result['moved_to_distribution'] . "\n";
                } else {
                    echo "❌ Basket Management Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
                }
                
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 2);
                echo "\nExecution time: {$executionTime} seconds\n";
                
            } catch (Exception $e) {
                echo "❌ Exception: " . $e->getMessage() . "\n";
                echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            }
            
            echo "</pre>";
            echo "</div>";
            
            // แสดงสถิติหลังการทำงาน
            echo "<h3>📊 สถิติลูกค้าหลังทำงาน</h3>";
            $sql = "SELECT basket_type, COUNT(*) as count FROM customers GROUP BY basket_type";
            $stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Basket Type</th><th>Count</th></tr>";
            foreach ($stats as $stat) {
                echo "<tr><td>" . ($stat['basket_type'] ?: 'NULL') . "</td><td>" . $stat['count'] . "</td></tr>";
            }
            echo "</table>";
            
        } else {
            echo "<p style='color: red;'>❌ ไม่พบไฟล์ CronJobService.php</p>";
        }
    } else {
        echo "<p><a href='?run_test=yes' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 รันทดสอบ Basket Management</a></p>";
        echo "<p><small>⚠️ จะรัน basket management จริง อาจมีการเปลี่ยนแปลงข้อมูล</small></p>";
    }
    
    // ตรวจสอบ logs
    echo "<h2>3. ตรวจสอบ Logs</h2>";
    
    $logFile = 'logs/cron.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -10);
        
        echo "<h3>📄 Log ล่าสุด (10 บรรทัด)</h3>";
        echo "<pre style='background: #000; color: #00ff00; padding: 10px; border-radius: 5px;'>";
        foreach ($recentLines as $line) {
            if (trim($line) !== '') {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่พบไฟล์ log</p>";
    }
    
    // ตรวจสอบ activity logs
    echo "<h3>🗂️ Activity Logs ล่าสุด</h3>";
    $sql = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5";
    $activities = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($activities)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>เวลา</th><th>Activity Type</th><th>Action</th><th>Description</th></tr>";
        foreach ($activities as $activity) {
            echo "<tr>";
            echo "<td>" . $activity['created_at'] . "</td>";
            echo "<td>" . $activity['activity_type'] . "</td>";
            echo "<td>" . $activity['action'] . "</td>";
            echo "<td>" . substr($activity['description'], 0, 100) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: blue;'>ℹ️ ยังไม่มี activity logs</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='?' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 รีเฟรช</a>";
echo "<a href='test_cron_jobs.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 กลับหน้าทดสอบ</a>";
echo "<a href='view_cron_logs.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📄 ดู Logs</a>";
echo "</div>";

?>
