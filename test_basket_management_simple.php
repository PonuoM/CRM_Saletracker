<?php
/**
 * Simple Basket Management Test
 * ทดสอบ basket management แบบง่ายๆ
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Simple Basket Management Test</h1>";

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>📊 สถิติก่อนทำงาน</h2>";
    showStats($pdo);
    
    if (isset($_GET['run']) && $_GET['run'] === 'yes') {
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🔄 กำลังรัน Basket Management...</h3>";
        echo "<pre>";
        
        $startTime = microtime(true);
        $results = [
            'new_customers_recalled' => 0,
            'existing_customers_recalled' => 0,
            'moved_to_distribution' => 0
        ];
        
        try {
            // 1. ดึงลูกค้าใหม่ที่หมดเวลาถือครอง (>30 วัน) กลับไป distribution
            echo "=== Step 1: ลูกค้าใหม่หมดเวลา ===\n";
            $sql1 = "
                UPDATE customers 
                SET basket_type = 'distribution',
                    assigned_to = NULL,
                    assigned_at = NULL,
                    recall_at = NOW(),
                    recall_reason = 'new_customer_timeout'
                WHERE basket_type = 'assigned'
                AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND customer_id NOT IN (
                    SELECT DISTINCT customer_id FROM orders 
                    WHERE order_date > assigned_at
                )
            ";
            
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute();
            $results['new_customers_recalled'] = $stmt1->rowCount();
            echo "✅ New customers recalled: {$results['new_customers_recalled']}\n\n";
            
            // 2. ดึงลูกค้าเก่าที่ไม่มีออเดอร์ใน 90 วัน ไปตะกร้ารอ (waiting)
            echo "=== Step 2: ลูกค้าเก่าไม่ซื้อนาน ===\n";
            $sql2 = "
                UPDATE customers 
                SET basket_type = 'waiting',
                    assigned_to = NULL,
                    assigned_at = NULL,
                    recall_at = NOW(),
                    recall_reason = 'existing_customer_timeout'
                WHERE basket_type = 'assigned'
                AND customer_id IN (
                    SELECT customer_id FROM (
                        SELECT customer_id 
                        FROM orders 
                        GROUP BY customer_id 
                        HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
                    ) as old_customers
                )
            ";
            
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute();
            $results['existing_customers_recalled'] = $stmt2->rowCount();
            echo "✅ Existing customers recalled: {$results['existing_customers_recalled']}\n\n";
            
            // 3. ย้ายลูกค้าจากตะกร้ารอ (waiting) ไปตะกร้าพร้อมแจก (distribution) หลัง 30 วัน
            echo "=== Step 3: ย้ายจาก waiting ไป distribution ===\n";
            $sql3 = "
                UPDATE customers 
                SET basket_type = 'distribution',
                    recall_at = NULL,
                    recall_reason = NULL
                WHERE basket_type = 'waiting'
                AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->execute();
            $results['moved_to_distribution'] = $stmt3->rowCount();
            echo "✅ Moved to distribution: {$results['moved_to_distribution']}\n\n";
            
            // บันทึก activity log
            $sql = "INSERT INTO activity_logs (activity_type, action, description, created_at) 
                    VALUES ('basket_management', 'auto_recall', ?, NOW())";
            $description = "Basket Management: New recalled: {$results['new_customers_recalled']}, Existing recalled: {$results['existing_customers_recalled']}, Moved to distribution: {$results['moved_to_distribution']}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$description]);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            echo "=== Summary ===\n";
            echo "✅ Basket Management Success!\n";
            echo "   - New customers recalled: {$results['new_customers_recalled']}\n";
            echo "   - Existing customers recalled: {$results['existing_customers_recalled']}\n";
            echo "   - Moved to distribution: {$results['moved_to_distribution']}\n";
            echo "   - Execution time: {$executionTime} seconds\n";
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
        
        echo "</pre>";
        echo "</div>";
        
        echo "<h2>📊 สถิติหลังทำงาน</h2>";
        showStats($pdo);
        
        // แสดงลูกค้าที่ถูกย้าย
        echo "<h3>🔄 ลูกค้าที่ถูกย้ายล่าสุด</h3>";
        $sql = "SELECT customer_id, CONCAT(first_name, ' ', last_name) as name, 
                       basket_type, recall_reason, recall_at
                FROM customers 
                WHERE recall_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY recall_at DESC
                LIMIT 10";
        $recentMoves = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($recentMoves)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>ชื่อ</th><th>Basket</th><th>เหตุผล</th><th>เวลา</th></tr>";
            foreach ($recentMoves as $move) {
                echo "<tr>";
                echo "<td>" . $move['customer_id'] . "</td>";
                echo "<td>" . $move['name'] . "</td>";
                echo "<td>" . $move['basket_type'] . "</td>";
                echo "<td>" . $move['recall_reason'] . "</td>";
                echo "<td>" . $move['recall_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>ไม่มีการย้ายลูกค้าในชั่วโมงที่ผ่านมา</p>";
        }
        
    } else {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ คำเตือน</h3>";
        echo "<p>การทดสอบนี้จะเปลี่ยนแปลงข้อมูลจริงในฐานข้อมูล</p>";
        echo "<p>ตรวจสอบข้อมูลข้างต้นแล้ว กดปุ่มด้านล่างเพื่อรัน</p>";
        echo "</div>";
        
        echo "<p><a href='?run=yes' style='background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🚀 รัน Basket Management</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

function showStats($pdo) {
    // สถิติตะกร้า
    $sql = "SELECT basket_type, COUNT(*) as count FROM customers GROUP BY basket_type";
    $stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Basket Type</th><th>Count</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr><td>" . ($stat['basket_type'] ?: 'NULL') . "</td><td>" . $stat['count'] . "</td></tr>";
    }
    echo "</table>";
    
    // ลูกค้าที่ควร recall
    echo "<h4>🔍 ลูกค้าที่ควร recall (ตามเงื่อนไข)</h4>";
    
    // 1. ลูกค้าใหม่หมดเวลา
    $sql = "SELECT COUNT(*) as count FROM customers 
            WHERE basket_type = 'assigned'
            AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND customer_id NOT IN (
                SELECT DISTINCT customer_id FROM orders 
                WHERE order_date > assigned_at
            )";
    $newTimeout = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 2. ลูกค้าเก่าไม่ซื้อนาน
    $sql = "SELECT COUNT(DISTINCT c.customer_id) as count
            FROM customers c
            WHERE c.basket_type = 'assigned'
            AND c.customer_id IN (
                SELECT customer_id FROM orders 
                GROUP BY customer_id 
                HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
            )";
    $existingTimeout = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 3. ลูกค้าใน waiting ครบเวลา
    $sql = "SELECT COUNT(*) as count FROM customers 
            WHERE basket_type = 'waiting'
            AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $waitingReady = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ประเภท</th><th>จำนวน</th></tr>";
    echo "<tr><td>ลูกค้าใหม่หมดเวลา (>30 วัน)</td><td style='color: " . ($newTimeout > 0 ? 'green' : 'gray') . ";'>$newTimeout</td></tr>";
    echo "<tr><td>ลูกค้าเก่าไม่ซื้อนาน (>90 วัน)</td><td style='color: " . ($existingTimeout > 0 ? 'green' : 'gray') . ";'>$existingTimeout</td></tr>";
    echo "<tr><td>ลูกค้าใน waiting ครบ 30 วัน</td><td style='color: " . ($waitingReady > 0 ? 'green' : 'gray') . ";'>$waitingReady</td></tr>";
    echo "</table>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='create_test_scenarios.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 สร้างสถานการณ์ใหม่</a>";
echo "<a href='?' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 รีเฟรช</a>";
echo "</div>";

?>
