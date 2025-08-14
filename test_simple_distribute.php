<?php
/**
 * Simple Test Customer Distribution
 * ทดสอบการแจกลูกค้าแบบง่ายๆ
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/services/CustomerDistributionService.php';

echo "<h1>ทดสอบการแจกลูกค้าแบบง่ายๆ</h1>";

// Start session
session_start();

// Set test user session
$_SESSION['user_id'] = 1; // Admin user
$_SESSION['role_name'] = 'admin';

try {
    echo "<h2>1. ทดสอบการสร้าง CustomerDistributionService</h2>";
    $distributionService = new CustomerDistributionService();
    echo "✅ สร้าง CustomerDistributionService สำเร็จ<br>";
    
    echo "<h2>2. ทดสอบการดึงลูกค้าที่พร้อมแจก (5 คน)</h2>";
    $customers = $distributionService->getAvailableCustomers('hot_warm_cold', 5);
    echo "✅ ดึงลูกค้าได้ " . count($customers) . " คน<br>";
    
    if (count($customers) > 0) {
        echo "<h3>รายการลูกค้า:</h3>";
        echo "<ul>";
        foreach ($customers as $customer) {
            echo "<li>{$customer['first_name']} {$customer['last_name']} - {$customer['temperature_status']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>3. ทดสอบการแจกลูกค้า (2 คนให้ 2 Telesales)</h2>";
    
    // ใช้ Telesales ID ที่มีอยู่จริง
    $telesalesIds = [3, 4]; // พนักงานขาย 1 และ 2
    
    $result = $distributionService->distributeCustomers(2, 'hot_warm_cold', $telesalesIds, 1);
    
    if ($result['success']) {
        echo "✅ แจกลูกค้าสำเร็จ: " . $result['message'] . "<br>";
        echo "<h3>ผลการแจก:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "❌ แจกลูกค้าไม่สำเร็จ: " . $result['message'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>";
    print_r($e->getTrace());
    echo "</pre>";
}

echo "<h2>✅ การทดสอบเสร็จสิ้น</h2>";
?>
