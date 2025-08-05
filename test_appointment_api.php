<?php
/**
 * Test Appointment API
 * ทดสอบ API สำหรับนัดหมาย
 */

echo "<h1>ทดสอบ Appointment API</h1>";

// Test 1: Check if appointments table exists
echo "<h2>1. ตรวจสอบตาราง appointments</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    $result = $db->query("SHOW TABLES LIKE 'appointments'");
    
    if ($result && count($result) > 0) {
        echo "<p style='color: green;'>✓ ตาราง appointments มีอยู่</p>";
        
        // Check data count
        $count = $db->query("SELECT COUNT(*) as count FROM appointments");
        $total = $count[0]['count'] ?? 0;
        echo "<p>จำนวนนัดหมายในตาราง: <strong>{$total}</strong></p>";
        
        if ($total > 0) {
            $appointments = $db->query("SELECT * FROM appointments ORDER BY created_at DESC LIMIT 3");
            echo "<h3>ข้อมูลตัวอย่าง:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Customer ID</th><th>User ID</th><th>Date</th><th>Type</th><th>Status</th></tr>";
            foreach ($appointments as $appointment) {
                echo "<tr>";
                echo "<td>{$appointment['appointment_id']}</td>";
                echo "<td>{$appointment['customer_id']}</td>";
                echo "<td>{$appointment['user_id']}</td>";
                echo "<td>{$appointment['appointment_date']}</td>";
                echo "<td>{$appointment['appointment_type']}</td>";
                echo "<td>{$appointment['appointment_status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>✗ ไม่พบตาราง appointments</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

// Test 2: Test API endpoint
echo "<h2>2. ทดสอบ API Endpoint</h2>";

// Start session for API test
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: orange;'>⚠ ไม่ได้ login - จำลอง session</p>";
    $_SESSION['user_id'] = 1; // Simulate login
}

// Test API call
$apiUrl = "http://localhost/CRM-CURSOR/api/appointments.php?action=get_by_customer&customer_id=1&limit=5";

echo "<p>ทดสอบ API URL: <code>{$apiUrl}</code></p>";

// Use file_get_contents to test API
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

try {
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>✗ ไม่สามารถเรียก API ได้</p>";
    } else {
        echo "<p style='color: green;'>✓ API ตอบกลับสำเร็จ</p>";
        echo "<h3>Response:</h3>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Parse JSON response
        $data = json_decode($response, true);
        if ($data) {
            if ($data['success']) {
                echo "<p style='color: green;'>✓ API ส่งข้อมูลสำเร็จ</p>";
                echo "<p>จำนวนนัดหมายที่ได้รับ: <strong>" . count($data['data']) . "</strong></p>";
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

// Test 3: Check AppointmentService directly
echo "<h2>3. ทดสอบ AppointmentService โดยตรง</h2>";

try {
    require_once 'app/services/AppointmentService.php';
    
    $appointmentService = new AppointmentService();
    $result = $appointmentService->getAppointmentsByCustomer(1, 5);
    
    echo "<p style='color: green;'>✓ AppointmentService ทำงานได้</p>";
    echo "<h3>ผลลัพธ์:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาดใน AppointmentService: " . $e->getMessage() . "</p>";
}

echo "<h2>4. ข้อเสนอแนะ</h2>";
echo "<p>หาก API ทำงานได้แต่หน้าเว็บยังไม่แสดงข้อมูล ให้ตรวจสอบ:</p>";
echo "<ul>";
echo "<li>JavaScript console errors</li>";
echo "<li>Network tab ใน Developer Tools</li>";
echo "<li>Session authentication</li>";
echo "<li>DOM element IDs</li>";
echo "</ul>";
?> 