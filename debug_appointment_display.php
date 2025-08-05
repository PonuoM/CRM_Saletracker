<?php
/**
 * Debug Appointment Display
 * ทดสอบการแสดงผลนัดหมาย
 */

session_start();

// Simulate login if not logged in
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
    <title>Debug Appointment Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Debug Appointment Display</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h3>1. ตรวจสอบฐานข้อมูล</h3>
                <?php
                try {
                    $db = new Database();
                    
                    // Check appointments table
                    $result = $db->query("SHOW TABLES LIKE 'appointments'");
                    if ($result && count($result) > 0) {
                        echo "<p style='color: green;'>✓ ตาราง appointments มีอยู่</p>";
                        
                        // Count appointments
                        $count = $db->query("SELECT COUNT(*) as count FROM appointments");
                        $total = $count[0]['count'] ?? 0;
                        echo "<p>จำนวนนัดหมาย: <strong>{$total}</strong></p>";
                        
                        if ($total > 0) {
                            $appointments = $db->query("SELECT * FROM appointments ORDER BY created_at DESC LIMIT 5");
                            echo "<h4>ข้อมูลนัดหมาย:</h4>";
                            echo "<table class='table table-sm'>";
                            echo "<tr><th>ID</th><th>Customer ID</th><th>Date</th><th>Type</th><th>Status</th></tr>";
                            foreach ($appointments as $appointment) {
                                echo "<tr>";
                                echo "<td>{$appointment['appointment_id']}</td>";
                                echo "<td>{$appointment['customer_id']}</td>";
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
                ?>
            </div>
            
            <div class="col-md-6">
                <h3>2. ทดสอบ API</h3>
                <?php
                try {
                    $appointmentService = new AppointmentService();
                    $result = $appointmentService->getAppointmentsByCustomer(1, 5);
                    
                    echo "<p style='color: green;'>✓ AppointmentService ทำงานได้</p>";
                    echo "<p>ผลลัพธ์:</p>";
                    echo "<pre>" . print_r($result, true) . "</pre>";
                    
                } catch (Exception $e) {
                    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h3>3. ทดสอบการแสดงผล</h3>
                
                <!-- Simulate the customer detail page structure -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="historyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="calls-tab" data-bs-toggle="tab" data-bs-target="#calls" type="button" role="tab" aria-controls="calls" aria-selected="true">
                                    <i class="fas fa-phone me-1"></i>ประวัติการโทร
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab" aria-controls="appointments" aria-selected="false">
                                    <i class="fas fa-calendar me-1"></i>รายการนัดหมาย
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                                    <i class="fas fa-shopping-cart me-1"></i>ประวัติคำสั่งซื้อ
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="historyTabsContent">
                            <!-- Call History Tab -->
                            <div class="tab-pane fade show active" id="calls" role="tabpanel" aria-labelledby="calls-tab">
                                <p>ประวัติการโทร</p>
                            </div>
                            
                            <!-- Appointments Tab -->
                            <div class="tab-pane fade" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">รายการนัดหมายล่าสุด</h6>
                                    <button class="btn btn-sm btn-info" id="addAppointmentBtn" data-customer-id="1">
                                        <i class="fas fa-plus me-1"></i>เพิ่มนัดหมาย
                                    </button>
                                </div>
                                <div id="appointmentsList">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                        <span class="ms-2">กำลังโหลดรายการนัดหมาย...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Orders Tab -->
                            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                <p>ประวัติคำสั่งซื้อ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h3>4. ข้อมูล Debug</h3>
                <p><strong>Session User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'ไม่ระบุ'; ?></p>
                <p><strong>Session Role:</strong> <?php echo $_SESSION['role_name'] ?? 'ไม่ระบุ'; ?></p>
                <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/customer-detail.js"></script>
    
    <script>
        // Add some debugging
        console.log('Debug page loaded');
        console.log('Current URL:', window.location.href);
        console.log('appointmentsList element:', document.getElementById('appointmentsList'));
        console.log('appointments-tab element:', document.getElementById('appointments-tab'));
        
        // Test API call directly
        setTimeout(() => {
            console.log('Testing API call...');
            fetch('api/appointments.php?action=get_by_customer&customer_id=1&limit=5')
                .then(response => {
                    console.log('API Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API Response data:', data);
                })
                .catch(error => {
                    console.error('API Error:', error);
                });
        }, 2000);
    </script>
</body>
</html> 