<?php
/**
 * Create Appointments Table
 * สร้างตาราง appointments และข้อมูลตัวอย่าง
 */

require_once 'config/config.php';
require_once 'app/core/Database.php';

echo "<h1>สร้างตาราง Appointments</h1>";

try {
    $db = new Database();
    
    // Read the SQL file
    $sqlFile = 'database/appointments_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("ไม่พบไฟล์ SQL: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h2>กำลังสร้างตารางและข้อมูล...</h2>";
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $result = $db->query($statement);
            if ($result !== false) {
                echo "<p style='color: green;'>✓ สำเร็จ: " . substr($statement, 0, 50) . "...</p>";
            } else {
                echo "<p style='color: orange;'>⚠ ไม่มีผลลัพธ์: " . substr($statement, 0, 50) . "...</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
            echo "<p>SQL: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
        }
    }
    
    // Verify the table was created
    echo "<h2>ตรวจสอบผลลัพธ์</h2>";
    
    $result = $db->query("SHOW TABLES LIKE 'appointments'");
    if ($result && count($result) > 0) {
        echo "<p style='color: green;'>✓ ตาราง appointments สร้างสำเร็จ</p>";
        
        // Check data count
        $count = $db->query("SELECT COUNT(*) as count FROM appointments");
        $totalAppointments = $count[0]['count'] ?? 0;
        echo "<p>จำนวนนัดหมายในตาราง: <strong>{$totalAppointments}</strong></p>";
        
        if ($totalAppointments > 0) {
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
        
        echo "<h3>ทดสอบ API</h3>";
        echo "<p><a href='api/appointments.php?action=get_by_customer&customer_id=1&limit=5' target='_blank'>ทดสอบ API สำหรับลูกค้า ID 1</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ ไม่สามารถสร้างตาราง appointments ได้</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 