<?php
/**
 * Test Appointment Fix Verification
 * ตรวจสอบว่าการแก้ไข PDOStatement Object ทำงานหรือไม่
 */

// เริ่ม session
session_start();

// จำลองการ login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

echo "<h2>🔍 ตรวจสอบการแก้ไข PDOStatement Object</h2>";

// Load required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/AppointmentService.php';

try {
    echo "<h3>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h3>";
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    echo "<h3>2. ทดสอบ AppointmentService</h3>";
    $appointmentService = new AppointmentService();
    echo "✅ AppointmentService สร้างสำเร็จ<br>";
    
    echo "<h3>3. ทดสอบการดึงข้อมูลการนัดหมายของลูกค้า ID 1</h3>";
    $result = $appointmentService->getAppointmentsByCustomer(1, 5);
    
    echo "<h4>ผลลัพธ์:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['success']) {
        echo "✅ การดึงข้อมูลสำเร็จ<br>";
        
        if (is_array($result['data'])) {
            echo "✅ ข้อมูลเป็นอาร์เรย์ (ถูกต้อง)<br>";
            echo "จำนวนรายการ: " . count($result['data']) . "<br>";
            
            if (count($result['data']) > 0) {
                echo "<h4>รายการนัดหมาย:</h4>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>วันที่</th><th>ประเภท</th><th>สถานะ</th><th>หมายเหตุ</th></tr>";
                
                foreach ($result['data'] as $appointment) {
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
        } else {
            echo "❌ ข้อมูลไม่ใช่อาร์เรย์: " . gettype($result['data']) . "<br>";
            if (is_object($result['data'])) {
                echo "ประเภทออบเจ็กต์: " . get_class($result['data']) . "<br>";
            }
        }
    } else {
        echo "❌ การดึงข้อมูลล้มเหลว: " . $result['message'] . "<br>";
    }
    
    echo "<h3>4. ทดสอบ API Endpoint</h3>";
    $apiUrl = "http://localhost/CRM-CURSOR/api/appointments.php?action=get_by_customer&customer_id=1&limit=5";
    
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
        
        $data = json_decode($response, true);
        
        if ($data === null) {
            echo "❌ ไม่สามารถแปลง JSON ได้<br>";
            echo "JSON Error: " . json_last_error_msg();
        } else {
            if (isset($data['success']) && $data['success']) {
                echo "✅ API ทำงานถูกต้อง<br>";
                if (isset($data['data']) && is_array($data['data'])) {
                    echo "จำนวนรายการจาก API: " . count($data['data']) . "<br>";
                }
            } else {
                echo "❌ API เกิดข้อผิดพลาด: " . ($data['message'] ?? 'ไม่ระบุ') . "<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>สรุป</h3>";
echo "การแก้ไขนี้ควรแก้ปัญหาการแสดงผลข้อมูลการนัดหมายในหน้าเว็บ<br>";
echo "หากผลลัพธ์แสดงข้อมูลเป็นอาร์เรย์ แสดงว่าการแก้ไขสำเร็จ<br>";
?> 