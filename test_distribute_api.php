<?php
/**
 * Test Customer Distribution API
 * ทดสอบ API การแจกลูกค้าเพื่อหาสาเหตุของ HTTP 500 error
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/services/CustomerDistributionService.php';

echo "<h1>ทดสอบ API การแจกลูกค้า</h1>";

// Start session
session_start();

// Set test user session
$_SESSION['user_id'] = 1; // Admin user
$_SESSION['role_name'] = 'admin';

try {
    echo "<h2>1. ทดสอบการสร้าง CustomerDistributionService</h2>";
    $distributionService = new CustomerDistributionService();
    echo "✅ สร้าง CustomerDistributionService สำเร็จ<br>";
    
    echo "<h2>2. ทดสอบการดึงลูกค้าที่พร้อมแจก</h2>";
    $customers = $distributionService->getAvailableCustomers('hot_warm_cold', 5);
    echo "✅ ดึงลูกค้าได้ " . count($customers) . " คน<br>";
    
    echo "<h2>3. ทดสอบการแจกลูกค้า (5 คนให้ 2 Telesales)</h2>";
    $result = $distributionService->distributeCustomers(5, 'hot_warm_cold', [3, 4], 1);
    
    if ($result['success']) {
        echo "✅ แจกลูกค้าสำเร็จ: " . $result['message'] . "<br>";
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
    echo "<pre>";
    print_r($e->getTrace());
    echo "</pre>";
}

echo "<h2>4. ทดสอบการเรียกใช้ API โดยตรง</h2>";
echo "<p>ทดสอบ POST request ไปยัง API:</p>";

// Simulate POST request
$postData = [
    'quantity' => 5,
    'priority' => 'hot_warm_cold',
    'telesales_ids' => [3, 4]
];

echo "<p><strong>POST Data:</strong></p>";
echo "<pre>" . json_encode($postData, JSON_PRETTY_PRINT) . "</pre>";

// Test the actual API endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/CRM-CURSOR/api/customer-distribution.php?action=distribute');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Status Code:</strong> {$httpCode}</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($error) {
    echo "<p><strong>CURL Error:</strong> {$error}</p>";
}

echo "<h2>✅ การทดสอบเสร็จสิ้น</h2>";
?>
