<?php
/**
 * Debug Appointments System
 * ตรวจสอบระบบนัดหมาย
 */

require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/AppointmentService.php';

echo "<h1>Debug Appointments System</h1>";

try {
    $db = new Database();
    $appointmentService = new AppointmentService();
    
    echo "<h2>1. ตรวจสอบตาราง appointments</h2>";
    
    // Check if appointments table exists
    $result = $db->query("SHOW TABLES LIKE 'appointments'");
    if ($result && count($result) > 0) {
        echo "<p style='color: green;'>✓ ตาราง appointments มีอยู่</p>";
        
        // Check table structure
        $structure = $db->query("DESCRIBE appointments");
        echo "<h3>โครงสร้างตาราง:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($structure as $field) {
            echo "<tr>";
            echo "<td>{$field['Field']}</td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "<td>{$field['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if there's data
        $count = $db->query("SELECT COUNT(*) as count FROM appointments");
        $totalAppointments = $count[0]['count'] ?? 0;
        echo "<p>จำนวนนัดหมายทั้งหมด: <strong>{$totalAppointments}</strong></p>";
        
        if ($totalAppointments > 0) {
            $appointments = $db->query("SELECT * FROM appointments ORDER BY created_at DESC LIMIT 5");
            echo "<h3>นัดหมายล่าสุด 5 รายการ:</h3>";
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
        echo "<p style='color: red;'>✗ ตาราง appointments ไม่มีอยู่</p>";
    }
    
    echo "<h2>2. ทดสอบ AppointmentService</h2>";
    
    // Test getAppointmentsByCustomer for customer ID 1
    $result = $appointmentService->getAppointmentsByCustomer(1, 5);
    echo "<h3>ผลการทดสอบ getAppointmentsByCustomer(1, 5):</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    echo "<h2>3. ทดสอบ API Endpoint</h2>";
    
    // Simulate API call
    $_GET['action'] = 'get_by_customer';
    $_GET['customer_id'] = '1';
    $_GET['limit'] = '5';
    
    // Start output buffering to capture API response
    ob_start();
    
    // Include the API file
    include 'api/appointments.php';
    
    $apiResponse = ob_get_clean();
    echo "<h3>API Response:</h3>";
    echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
    
    // Try to decode JSON
    $decoded = json_decode($apiResponse, true);
    if ($decoded) {
        echo "<h3>Decoded Response:</h3>";
        echo "<pre>" . print_r($decoded, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 