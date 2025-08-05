<?php
/**
 * Simple Appointment Creation Test
 * ทดสอบการสร้างนัดหมายแบบง่าย
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

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Appointment Creation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>ทดสอบการสร้างนัดหมาย</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h3>1. ทดสอบฐานข้อมูล</h3>
                <?php
                try {
                    $db = new Database();
                    echo "<p style='color: green;'>✓ การเชื่อมต่อฐานข้อมูลสำเร็จ</p>";
                    
                    // Check appointments table
                    $result = $db->query("SHOW TABLES LIKE 'appointments'");
                    if ($result && count($result) > 0) {
                        echo "<p style='color: green;'>✓ ตาราง appointments มีอยู่</p>";
                        
                        // Count appointments
                        $count = $db->query("SELECT COUNT(*) as count FROM appointments");
                        $total = $count[0]['count'] ?? 0;
                        echo "<p>จำนวนนัดหมายในตาราง: <strong>{$total}</strong></p>";
                    } else {
                        echo "<p style='color: red;'>✗ ไม่พบตาราง appointments</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
            
            <div class="col-md-6">
                <h3>2. ทดสอบ AppointmentService</h3>
                <?php
                try {
                    $appointmentService = new AppointmentService();
                    echo "<p style='color: green;'>✓ AppointmentService สร้างสำเร็จ</p>";
                    
                    // Test data
                    $testData = [
                        'customer_id' => 1,
                        'user_id' => 1,
                        'appointment_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                        'appointment_type' => 'call',
                        'notes' => 'Test appointment from simple test'
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
                    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h3>3. ทดสอบ API ผ่าน JavaScript</h3>
                <button class="btn btn-primary" onclick="testApi()">ทดสอบ API</button>
                <div id="apiResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h3>4. ฟอร์มทดสอบ</h3>
                <form id="testForm">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="customerId" class="form-label">Customer ID</label>
                            <input type="number" class="form-control" id="customerId" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="appointmentDate" class="form-label">วันที่นัดหมาย</label>
                            <input type="datetime-local" class="form-control" id="appointmentDate" required>
                        </div>
                        <div class="col-md-3">
                            <label for="appointmentType" class="form-label">ประเภท</label>
                            <select class="form-select" id="appointmentType" required>
                                <option value="call">โทรศัพท์</option>
                                <option value="meeting">ประชุม</option>
                                <option value="presentation">นำเสนอ</option>
                                <option value="followup">ติดตาม</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="notes" class="form-label">หมายเหตุ</label>
                            <input type="text" class="form-control" id="notes" value="Test from form">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mt-3">สร้างนัดหมาย</button>
                </form>
                <div id="formResult" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script>
        // Set default date to tomorrow
        document.getElementById('appointmentDate').value = new Date(Date.now() + 24*60*60*1000).toISOString().slice(0, 16);
        
        function testApi() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>กำลังทดสอบ...</p>';
            
            const testData = {
                customer_id: 1,
                appointment_date: new Date(Date.now() + 24*60*60*1000).toISOString().slice(0, 19).replace('T', ' '),
                appointment_type: 'call',
                notes: 'Test from JavaScript API'
            };
            
            console.log('Sending data:', testData);
            
            fetch('api/appointments.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(testData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                resultDiv.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'danger'}">
                        <h5>ผลลัพธ์ API:</h5>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>เกิดข้อผิดพลาด:</h5>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        }
        
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('formResult');
            resultDiv.innerHTML = '<p>กำลังส่งข้อมูล...</p>';
            
            const formData = {
                customer_id: parseInt(document.getElementById('customerId').value),
                appointment_date: document.getElementById('appointmentDate').value.replace('T', ' '),
                appointment_type: document.getElementById('appointmentType').value,
                notes: document.getElementById('notes').value
            };
            
            console.log('Form data:', formData);
            
            fetch('api/appointments.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                resultDiv.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'danger'}">
                        <h5>ผลลัพธ์ฟอร์ม:</h5>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>เกิดข้อผิดพลาด:</h5>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        });
    </script>
</body>
</html> 