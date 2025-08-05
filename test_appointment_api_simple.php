<?php
/**
 * Simple Appointment API Test
 * ทดสอบ API appointments แบบง่าย
 */

// เริ่ม session
session_start();

// จำลองการ login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

echo "<h2>🔍 ทดสอบ API Appointments แบบง่าย</h2>";

// ทดสอบการเรียก API
$apiUrl = "http://localhost/CRM-CURSOR/api/appointments.php?action=get_by_customer&customer_id=1&limit=5";

echo "<h3>เรียก API: $apiUrl</h3>";

// ใช้ file_get_contents เพื่อเรียก API
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Cookie: PHPSESSID=' . session_id(),
            'Content-Type: application/json'
        ]
    ]
]);

$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "❌ ไม่สามารถเรียก API ได้<br>";
    echo "Error: " . error_get_last()['message'] ?? 'ไม่ระบุ';
} else {
    echo "✅ ได้รับข้อมูลจาก API<br>";
    echo "<h4>ข้อมูลดิบ:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "❌ ไม่สามารถแปลง JSON ได้<br>";
        echo "JSON Error: " . json_last_error_msg();
    } else {
        echo "<h4>ข้อมูลที่แปลงแล้ว:</h4>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        if (isset($data['success'])) {
            if ($data['success']) {
                echo "✅ API ทำงานถูกต้อง<br>";
                if (isset($data['data']) && is_array($data['data'])) {
                    echo "จำนวนรายการ: " . count($data['data']) . "<br>";
                    
                    if (count($data['data']) > 0) {
                        echo "<h4>รายการนัดหมาย:</h4>";
                        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                        echo "<tr><th>ID</th><th>วันที่</th><th>ประเภท</th><th>สถานะ</th><th>หมายเหตุ</th></tr>";
                        
                        foreach ($data['data'] as $appointment) {
                            echo "<tr>";
                            echo "<td>" . $appointment['appointment_id'] . "</td>";
                            echo "<td>" . $appointment['appointment_date'] . "</td>";
                            echo "<td>" . $appointment['appointment_type'] . "</td>";
                            echo "<td>" . $appointment['appointment_status'] . "</td>";
                            echo "<td>" . ($appointment['notes'] ?? '-') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "⚠️ ไม่พบข้อมูลการนัดหมาย<br>";
                    }
                }
            } else {
                echo "❌ API เกิดข้อผิดพลาด: " . ($data['message'] ?? 'ไม่ระบุ') . "<br>";
            }
        } else {
            echo "❌ ไม่พบ success field ในข้อมูล<br>";
        }
    }
}

// ทดสอบการเชื่อมต่อฐานข้อมูลโดยตรง
echo "<h3>ทดสอบการเชื่อมต่อฐานข้อมูลโดยตรง</h3>";

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    // ตรวจสอบข้อมูลในตาราง appointments
    $sql = "SELECT COUNT(*) as total FROM appointments WHERE customer_id = 1";
    $result = $db->query($sql);
    
    if ($result && count($result) > 0) {
        echo "จำนวนการนัดหมายในตาราง: " . $result[0]['total'] . "<br>";
        
        if ($result[0]['total'] > 0) {
            $sql = "SELECT * FROM appointments WHERE customer_id = 1 ORDER BY appointment_date DESC LIMIT 5";
            $appointments = $db->query($sql);
            
            echo "<h4>ข้อมูลในตาราง appointments:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Customer ID</th><th>User ID</th><th>วันที่</th><th>ประเภท</th><th>สถานะ</th></tr>";
            
            foreach ($appointments as $appointment) {
                echo "<tr>";
                echo "<td>" . $appointment['appointment_id'] . "</td>";
                echo "<td>" . $appointment['customer_id'] . "</td>";
                echo "<td>" . $appointment['user_id'] . "</td>";
                echo "<td>" . $appointment['appointment_date'] . "</td>";
                echo "<td>" . $appointment['appointment_type'] . "</td>";
                echo "<td>" . $appointment['appointment_status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style> 