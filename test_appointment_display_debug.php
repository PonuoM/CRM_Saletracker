<?php
/**
 * Test Appointment Display Debug
 * ทดสอบการแสดงผลข้อมูลการนัดหมาย
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/AppointmentService.php';

// เริ่ม session สำหรับทดสอบ
session_start();

// จำลองการ login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

echo "<h2>🔍 ทดสอบการแสดงผลข้อมูลการนัดหมาย</h2>";

// 1. ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h3>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h3>";
try {
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
    exit;
}

// 2. ทดสอบ AppointmentService
echo "<h3>2. ทดสอบ AppointmentService</h3>";
try {
    $appointmentService = new AppointmentService();
    echo "✅ AppointmentService สร้างสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ AppointmentService สร้างล้มเหลว: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ทดสอบการดึงข้อมูลการนัดหมายของลูกค้า ID 1
echo "<h3>3. ทดสอบการดึงข้อมูลการนัดหมายของลูกค้า ID 1</h3>";
try {
    $result = $appointmentService->getAppointmentsByCustomer(1, 5);
    echo "API Response: <pre>" . print_r($result, true) . "</pre>";
    
    if ($result['success']) {
        echo "✅ ดึงข้อมูลสำเร็จ<br>";
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
        echo "❌ ดึงข้อมูลล้มเหลว: " . $result['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
}

// 4. ทดสอบการเรียก API โดยตรง
echo "<h3>4. ทดสอบการเรียก API โดยตรง</h3>";
try {
    // จำลองการเรียก API
    $apiUrl = "api/appointments.php?action=get_by_customer&customer_id=1&limit=5";
    
    // ใช้ cURL เพื่อเรียก API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code: " . $httpCode . "<br>";
    echo "Raw Response: <pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($response) {
        $data = json_decode($response, true);
        echo "Decoded Response: <pre>" . print_r($data, true) . "</pre>";
        
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "✅ API ทำงานถูกต้อง<br>";
            } else {
                echo "❌ API เกิดข้อผิดพลาด: " . ($data['message'] ?? 'ไม่ระบุ') . "<br>";
            }
        } else {
            echo "❌ ไม่สามารถแปลง JSON ได้<br>";
        }
    } else {
        echo "❌ ไม่ได้รับข้อมูลจาก API<br>";
    }
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาดในการเรียก API: " . $e->getMessage() . "<br>";
}

// 5. ตรวจสอบข้อมูลในตารางโดยตรง
echo "<h3>5. ตรวจสอบข้อมูลในตารางโดยตรง</h3>";
try {
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
    echo "❌ เกิดข้อผิดพลาดในการตรวจสอบตาราง: " . $e->getMessage() . "<br>";
}

// 6. ทดสอบ JavaScript
echo "<h3>6. ทดสอบ JavaScript</h3>";
echo "<div id='testAppointmentsList'>กำลังโหลดรายการนัดหมาย...</div>";
echo "<button onclick='testLoadAppointments()'>ทดสอบโหลดข้อมูล</button>";

?>

<script>
function testLoadAppointments() {
    console.log('Testing loadAppointments function...');
    
    const appointmentsList = document.getElementById('testAppointmentsList');
    console.log('appointmentsList element:', appointmentsList);
    
    if (!appointmentsList) {
        console.error('appointmentsList element not found');
        return;
    }
    
    const customerId = 1; // ใช้ customer ID 1 สำหรับทดสอบ
    console.log('Customer ID:', customerId);
    
    const apiUrl = `api/appointments.php?action=get_by_customer&customer_id=${customerId}&limit=5`;
    console.log('Calling API:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('API response status:', response.status);
            console.log('API response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);
            if (data.success && data.data.length > 0) {
                console.log('Displaying appointments:', data.data);
                displayTestAppointments(data.data);
            } else {
                console.log('No appointments found or API error');
                appointmentsList.innerHTML = '<p class="text-muted text-center mb-0">ไม่มีรายการนัดหมาย</p>';
            }
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            appointmentsList.innerHTML = '<p class="text-danger text-center mb-0">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
        });
}

function displayTestAppointments(appointments) {
    console.log('displayTestAppointments function called with:', appointments);
    
    const appointmentsList = document.getElementById('testAppointmentsList');
    console.log('appointmentsList element in displayTestAppointments:', appointmentsList);
    
    if (!appointmentsList) {
        console.error('appointmentsList element not found in displayTestAppointments');
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr>';
    html += '<th>วันที่</th>';
    html += '<th>ประเภท</th>';
    html += '<th>สถานะ</th>';
    html += '<th>หมายเหตุ</th>';
    html += '</tr></thead><tbody>';
    
    appointments.forEach(appointment => {
        const appointmentDate = new Date(appointment.appointment_date);
        const formattedDate = appointmentDate.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const typeText = {
            'call': 'โทรศัพท์',
            'meeting': 'ประชุม',
            'presentation': 'นำเสนอ',
            'followup': 'ติดตาม',
            'other': 'อื่นๆ'
        }[appointment.appointment_type] || appointment.appointment_type;
        
        const statusText = {
            'scheduled': 'นัดแล้ว',
            'confirmed': 'ยืนยันแล้ว',
            'completed': 'เสร็จสิ้น',
            'cancelled': 'ยกเลิก',
            'no_show': 'ไม่มา'
        }[appointment.appointment_status] || appointment.appointment_status;
        
        html += '<tr>';
        html += `<td>${formattedDate}</td>`;
        html += `<td>${typeText}</td>`;
        html += `<td>${statusText}</td>`;
        html += `<td>${appointment.notes || '-'}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    appointmentsList.innerHTML = html;
    console.log('Appointments displayed successfully');
}

// ทดสอบโหลดข้อมูลทันทีเมื่อหน้าโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, testing appointments...');
    setTimeout(testLoadAppointments, 1000);
});
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background-color: #0056b3; }
</style> 