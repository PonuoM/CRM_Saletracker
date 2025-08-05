<?php
/**
 * Debug Appointment Creation
 * ทดสอบการสร้างนัดหมายเพื่อหาสาเหตุของ 500 error
 */

session_start();

// Simulate login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role_name'] = 'admin';
}

require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/AppointmentService.php';

echo "<h1>Debug Appointment Creation</h1>";

// Test 1: Check database connection
echo "<h2>1. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $db = new Database();
    echo "<p style='color: green;'>✓ การเชื่อมต่อฐานข้อมูลสำเร็จ</p>";
    
    // Check if appointments table exists
    $result = $db->query("SHOW TABLES LIKE 'appointments'");
    if ($result && count($result) > 0) {
        echo "<p style='color: green;'>✓ ตาราง appointments มีอยู่</p>";
    } else {
        echo "<p style='color: red;'>✗ ไม่พบตาราง appointments</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage() . "</p>";
}

// Test 2: Test AppointmentService directly
echo "<h2>2. ทดสอบ AppointmentService</h2>";
try {
    $appointmentService = new AppointmentService();
    echo "<p style='color: green;'>✓ AppointmentService สร้างสำเร็จ</p>";
    
    // Test data
    $testData = [
        'customer_id' => 1,
        'user_id' => 1,
        'appointment_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'appointment_type' => 'call',
        'notes' => 'Test appointment from debug script'
    ];
    
    echo "<p>ทดสอบข้อมูล:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    $result = $appointmentService->createAppointment($testData);
    
    echo "<p>ผลลัพธ์:</p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green;'>✓ การสร้างนัดหมายสำเร็จ</p>";
    } else {
        echo "<p style='color: red;'>✗ การสร้างนัดหมายล้มเหลว: " . $result['message'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดใน AppointmentService: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 3: Test API endpoint
echo "<h2>3. ทดสอบ API Endpoint</h2>";

// Prepare test data
$testData = [
    'customer_id' => 1,
    'appointment_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'appointment_type' => 'meeting',
    'notes' => 'Test appointment from API'
];

echo "<p>ข้อมูลที่จะส่งไป API:</p>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Test API call
$apiUrl = "http://localhost/CRM-CURSOR/api/appointments.php?action=create";

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($testData))
        ],
        'content' => json_encode($testData)
    ]
]);

try {
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>✗ ไม่สามารถเรียก API ได้</p>";
        
        // Check for HTTP errors
        $httpResponse = $http_response_header ?? [];
        echo "<p>HTTP Response Headers:</p>";
        echo "<pre>" . print_r($httpResponse, true) . "</pre>";
    } else {
        echo "<p style='color: green;'>✓ API ตอบกลับสำเร็จ</p>";
        echo "<h3>Response:</h3>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Parse JSON response
        $data = json_decode($response, true);
        if ($data) {
            if ($data['success']) {
                echo "<p style='color: green;'>✓ API สร้างนัดหมายสำเร็จ</p>";
            } else {
                echo "<p style='color: red;'>✗ API ส่งข้อผิดพลาด: " . $data['message'] . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ ไม่สามารถแปลง JSON ได้</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาดในการเรียก API: " . $e->getMessage() . "</p>";
}

// Test 4: Check PHP error logs
echo "<h2>4. ตรวจสอบ PHP Error Logs</h2>";
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    echo "<p>Error log path: $errorLogPath</p>";
    $recentErrors = file_get_contents($errorLogPath);
    if ($recentErrors) {
        echo "<p>ข้อผิดพลาดล่าสุด:</p>";
        echo "<pre>" . htmlspecialchars(substr($recentErrors, -1000)) . "</pre>";
    } else {
        echo "<p>ไม่มีข้อผิดพลาดใน log</p>";
    }
} else {
    echo "<p>ไม่พบ error log หรือไม่สามารถเข้าถึงได้</p>";
}

// Test 5: Check if appointments table has correct structure
echo "<h2>5. ตรวจสอบโครงสร้างตาราง appointments</h2>";
try {
    $result = $db->query("DESCRIBE appointments");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

echo "<h2>6. ข้อเสนอแนะ</h2>";
echo "<p>หากยังเกิดข้อผิดพลาด ให้ตรวจสอบ:</p>";
echo "<ul>";
echo "<li>PHP error logs</li>";
echo "<li>Database permissions</li>";
echo "<li>Session configuration</li>";
echo "<li>JSON encoding/decoding</li>";
echo "<li>Database table structure</li>";
echo "</ul>";
?> 