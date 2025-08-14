<?php
/**
 * Create Test Scenarios
 * สร้างข้อมูลทดสอบสำหรับ basket management
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 สร้างสถานการณ์ทดสอบ Basket Management</h1>";

try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>📋 เลือกสถานการณ์ทดสอบ</h2>";
    
    if (isset($_GET['scenario'])) {
        $scenario = $_GET['scenario'];
        
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🔄 กำลังสร้างสถานการณ์: $scenario</h3>";
        
        switch ($scenario) {
            case 'new_customer_timeout':
                createNewCustomerTimeoutScenario($pdo);
                break;
            case 'existing_customer_timeout':
                createExistingCustomerTimeoutScenario($pdo);
                break;
            case 'waiting_to_distribution':
                createWaitingToDistributionScenario($pdo);
                break;
            case 'mixed_scenario':
                createMixedScenario($pdo);
                break;
            case 'reset_all':
                resetAllScenarios($pdo);
                break;
        }
        
        echo "</div>";
        
        // แสดงสถิติหลังการสร้าง
        showCurrentStats($pdo);
        
    } else {
        // แสดงตัวเลือก
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
        echo "<h3>🎯 เลือกสถานการณ์ที่ต้องการทดสอบ:</h3>";
        
        echo "<div style='margin: 10px 0;'>";
        echo "<a href='?scenario=new_customer_timeout' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>";
        echo "🆕 ลูกค้าใหม่หมดเวลา (>30 วัน)";
        echo "</a>";
        echo "</div>";
        
        echo "<div style='margin: 10px 0;'>";
        echo "<a href='?scenario=existing_customer_timeout' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>";
        echo "👥 ลูกค้าเก่าไม่ซื้อนาน (>90 วัน)";
        echo "</a>";
        echo "</div>";
        
        echo "<div style='margin: 10px 0;'>";
        echo "<a href='?scenario=waiting_to_distribution' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>";
        echo "⏳ ลูกค้าใน waiting ครบ 30 วัน";
        echo "</a>";
        echo "</div>";
        
        echo "<div style='margin: 10px 0;'>";
        echo "<a href='?scenario=mixed_scenario' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>";
        echo "🎭 สถานการณ์รวม (ทุกแบบ)";
        echo "</a>";
        echo "</div>";
        
        echo "<div style='margin: 10px 0;'>";
        echo "<a href='?scenario=reset_all' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>";
        echo "🔄 รีเซ็ตข้อมูลทดสอบ";
        echo "</a>";
        echo "</div>";
        
        echo "</div>";
        
        // แสดงสถิติปัจจุบัน
        showCurrentStats($pdo);
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

function createNewCustomerTimeoutScenario($pdo) {
    echo "<h4>🆕 สร้างสถานการณ์: ลูกค้าใหม่หมดเวลา</h4>";
    
    // หาลูกค้า 5 คนแรกที่ assigned
    $sql = "SELECT customer_id FROM customers WHERE basket_type = 'assigned' LIMIT 5";
    $customers = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($customers)) {
        echo "<p style='color: red;'>❌ ไม่พบลูกค้าใน assigned basket</p>";
        return;
    }
    
    $count = 0;
    foreach ($customers as $customerId) {
        // ตั้งวันที่ assigned ให้เป็น 35 วันที่แล้ว (เกิน 30 วัน)
        $sql = "UPDATE customers SET assigned_at = DATE_SUB(NOW(), INTERVAL 35 DAY) WHERE customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId]);
        $count++;
    }
    
    echo "<p style='color: green;'>✅ สร้างลูกค้าใหม่ที่หมดเวลา: $count รายการ</p>";
    echo "<p><small>ตั้งค่า assigned_at ให้เป็น 35 วันที่แล้ว</small></p>";
}

function createExistingCustomerTimeoutScenario($pdo) {
    echo "<h4>👥 สร้างสถานการณ์: ลูกค้าเก่าไม่ซื้อนาน</h4>";
    
    // หาลูกค้าที่มีออเดอร์
    $sql = "SELECT DISTINCT c.customer_id 
            FROM customers c 
            INNER JOIN orders o ON c.customer_id = o.customer_id 
            WHERE c.basket_type = 'assigned' 
            LIMIT 3";
    $customers = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($customers)) {
        echo "<p style='color: red;'>❌ ไม่พบลูกค้าที่มีออเดอร์</p>";
        return;
    }
    
    $count = 0;
    foreach ($customers as $customerId) {
        // ตั้งวันที่ออเดอร์ล่าสุดให้เป็น 100 วันที่แล้ว (เกิน 90 วัน)
        $sql = "UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 100 DAY) WHERE customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId]);
        $count++;
    }
    
    echo "<p style='color: green;'>✅ สร้างลูกค้าเก่าที่ไม่ซื้อนาน: $count รายการ</p>";
    echo "<p><small>ตั้งค่า order_date ให้เป็น 100 วันที่แล้ว</small></p>";
}

function createWaitingToDistributionScenario($pdo) {
    echo "<h4>⏳ สร้างสถานการณ์: ลูกค้าใน waiting ครบ 30 วัน</h4>";
    
    // หาลูกค้า 2 คนแรกที่ assigned
    $sql = "SELECT customer_id FROM customers WHERE basket_type = 'assigned' LIMIT 2";
    $customers = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($customers)) {
        echo "<p style='color: red;'>❌ ไม่พบลูกค้าใน assigned basket</p>";
        return;
    }
    
    $count = 0;
    foreach ($customers as $customerId) {
        // ย้ายไป waiting และตั้งวันที่ recall ให้เป็น 35 วันที่แล้ว
        $sql = "UPDATE customers SET 
                basket_type = 'waiting',
                recall_at = DATE_SUB(NOW(), INTERVAL 35 DAY),
                recall_reason = 'test_scenario'
                WHERE customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId]);
        $count++;
    }
    
    echo "<p style='color: green;'>✅ สร้างลูกค้าใน waiting ที่ครบ 30 วัน: $count รายการ</p>";
    echo "<p><small>ย้ายไป waiting และตั้งค่า recall_at ให้เป็น 35 วันที่แล้ว</small></p>";
}

function createMixedScenario($pdo) {
    echo "<h4>🎭 สร้างสถานการณ์รวม: ทุกแบบ</h4>";
    
    createNewCustomerTimeoutScenario($pdo);
    createExistingCustomerTimeoutScenario($pdo);
    createWaitingToDistributionScenario($pdo);
    
    echo "<p style='color: blue;'>ℹ️ สร้างสถานการณ์ทดสอบครบทุกแบบแล้ว</p>";
}

function resetAllScenarios($pdo) {
    echo "<h4>🔄 รีเซ็ตข้อมูลทดสอบ</h4>";
    
    try {
        // รีเซ็ตลูกค้าทั้งหมดกลับไป assigned
        $sql = "UPDATE customers SET 
                basket_type = 'assigned',
                recall_at = NULL,
                recall_reason = NULL
                WHERE recall_reason = 'test_scenario' OR basket_type = 'waiting'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $resetCount = $stmt->rowCount();
        
        // รีเซ็ต assigned_at ให้เป็นปัจจุบัน
        $sql = "UPDATE customers SET assigned_at = NOW() WHERE assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $assignedCount = $stmt->rowCount();
        
        // รีเซ็ต order_date ให้เป็นปัจจุบัน
        $sql = "UPDATE orders SET order_date = NOW() WHERE order_date < DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $orderCount = $stmt->rowCount();
        
        echo "<p style='color: green;'>✅ รีเซ็ตข้อมูลทดสอบเสร็จสิ้น</p>";
        echo "<ul>";
        echo "<li>รีเซ็ตลูกค้า: $resetCount รายการ</li>";
        echo "<li>รีเซ็ต assigned_at: $assignedCount รายการ</li>";
        echo "<li>รีเซ็ต order_date: $orderCount รายการ</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ เกิดข้อผิดพลาดในการรีเซ็ต: " . $e->getMessage() . "</p>";
    }
}

function showCurrentStats($pdo) {
    echo "<h3>📊 สถิติปัจจุบัน</h3>";
    
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
                WHERE created_at > assigned_at
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
echo "<a href='manual_test_cron.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 ทดสอบ Basket Management</a>";
echo "<a href='?' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 รีเฟรช</a>";
echo "</div>";

?>
