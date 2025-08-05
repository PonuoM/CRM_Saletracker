<?php
/**
 * ตรวจสอบสถานะ Cron Jobs และข้อมูลปัจจุบัน
 */

// Load configuration
require_once 'config/config.php';
require_once 'app/core/Database.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>ตรวจสอบสถานะ Cron Jobs</title><meta charset='UTF-8'></head><body>";
echo "<h1>🔍 ตรวจสอบสถานะ Cron Jobs และข้อมูลปัจจุบัน</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $db = new Database();
    
    echo "<hr>";
    
    // 1. ตรวจสอบข้อมูลลูกค้าทดลอง
    echo "<h2>1. ข้อมูลลูกค้าทดลองปัจจุบัน</h2>";
    $sql = "SELECT 
                CONCAT(first_name, ' ', last_name) as customer_name,
                customer_grade,
                temperature_status,
                total_purchase_amount,
                DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) as days_since_contact,
                last_contact_at,
                created_at
            FROM customers 
            WHERE first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')
            ORDER BY total_purchase_amount DESC";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ลูกค้า</th><th>เกรด</th><th>อุณหภูมิ</th><th>ยอดซื้อ</th><th>วันที่ไม่ติดต่อ</th><th>ติดต่อล่าสุด</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $gradeColor = [
                'A+' => 'purple',
                'A' => 'red',
                'B' => 'orange',
                'C' => 'blue',
                'D' => 'gray'
            ];
            
            $tempColor = [
                'hot' => 'red',
                'warm' => 'orange',
                'cold' => 'blue',
                'frozen' => 'purple'
            ];
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
            echo "<td style='text-align: center; color: " . ($gradeColor[$row['customer_grade']] ?? 'black') . "; font-weight: bold;'>" . htmlspecialchars($row['customer_grade']) . "</td>";
            echo "<td style='text-align: center; color: " . ($tempColor[$row['temperature_status']] ?? 'black') . ";'>" . htmlspecialchars($row['temperature_status']) . "</td>";
            echo "<td style='text-align: right;'>฿" . number_format($row['total_purchase_amount'], 2) . "</td>";
            echo "<td style='text-align: center;'>" . $row['days_since_contact'] . " วัน</td>";
            echo "<td>" . ($row['last_contact_at'] ? date('Y-m-d', strtotime($row['last_contact_at'])) : 'ไม่เคย') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ ไม่พบข้อมูลลูกค้าทดลอง กรุณารันไฟล์ create_sample_data.sql ก่อน</p>";
    }
    
    echo "<hr>";
    
    // 2. ตรวจสอบ Cron Job Logs
    echo "<h2>2. Log การรัน Cron Jobs</h2>";
    $sql = "SELECT * FROM cron_job_logs ORDER BY created_at DESC LIMIT 10";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>งาน</th><th>สถานะ</th><th>เริ่มเวลา</th><th>จบเวลา</th><th>ใช้เวลา</th><th>ข้อผิดพลาด</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $statusColor = [
                'success' => 'green',
                'failed' => 'red',
                'running' => 'orange'
            ];
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['job_name']) . "</td>";
            echo "<td style='color: " . ($statusColor[$row['status']] ?? 'black') . "; font-weight: bold;'>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', strtotime($row['start_time'])) . "</td>";
            echo "<td>" . ($row['end_time'] ? date('Y-m-d H:i:s', strtotime($row['end_time'])) : '-') . "</td>";
            echo "<td>" . ($row['execution_time'] ? $row['execution_time'] . ' วินาที' : '-') . "</td>";
            echo "<td>" . ($row['error_message'] ? htmlspecialchars(substr($row['error_message'], 0, 50)) . '...' : '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่พบ log การรัน Cron Jobs แสดงว่าอาจจะยังไม่ได้รัน</p>";
    }
    
    echo "<hr>";
    
    // 3. ตรวจสอบ Activity Logs
    echo "<h2>3. Activity Logs (การเปลี่ยนแปลงข้อมูล)</h2>";
    $sql = "SELECT * FROM activity_logs WHERE activity_type IN ('grade_change', 'temperature_change') ORDER BY created_at DESC LIMIT 10";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ประเภท</th><th>ตาราง</th><th>การกระทำ</th><th>ค่าเดิม</th><th>ค่าใหม่</th><th>เวลา</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $oldValues = json_decode($row['old_values'], true);
            $newValues = json_decode($row['new_values'], true);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['activity_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['table_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['action']) . "</td>";
            echo "<td>" . htmlspecialchars(json_encode($oldValues, JSON_UNESCAPED_UNICODE)) . "</td>";
            echo "<td>" . htmlspecialchars(json_encode($newValues, JSON_UNESCAPED_UNICODE)) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่พบการเปลี่ยนแปลงข้อมูล แสดงว่า Cron Jobs ยังไม่ได้รันหรือมีปัญหา</p>";
    }
    
    echo "<hr>";
    
    // 4. ตรวจสอบการตั้งค่า Cron Jobs
    echo "<h2>4. การตั้งค่า Cron Jobs</h2>";
    $sql = "SELECT * FROM cron_job_settings";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>งาน</th><th>Cron Expression</th><th>สถานะ</th><th>รันล่าสุด</th><th>รันครั้งต่อไป</th><th>คำอธิบาย</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $statusText = $row['is_enabled'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            $statusColor = $row['is_enabled'] ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['job_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['cron_expression']) . "</td>";
            echo "<td style='color: " . $statusColor . ";'>" . $statusText . "</td>";
            echo "<td>" . ($row['last_run'] ? date('Y-m-d H:i:s', strtotime($row['last_run'])) : 'ไม่เคย') . "</td>";
            echo "<td>" . ($row['next_run'] ? date('Y-m-d H:i:s', strtotime($row['next_run'])) : '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ ไม่พบการตั้งค่า Cron Jobs</p>";
    }
    
    echo "<hr>";
    
    // 5. ตรวจสอบไฟล์ Cron
    echo "<h2>5. ตรวจสอบไฟล์ Cron</h2>";
    $cronFiles = [
        'cron/run_all_jobs.php',
        'cron/update_customer_grades.php',
        'cron/update_customer_temperatures.php',
        'cron/send_recall_notifications.php'
    ];
    
    echo "<ul>";
    foreach ($cronFiles as $file) {
        if (file_exists($file)) {
            echo "<li style='color: green;'>✅ " . $file . " - มีอยู่</li>";
        } else {
            echo "<li style='color: red;'>❌ " . $file . " - ไม่พบ</li>";
        }
    }
    echo "</ul>";
    
    echo "<hr>";
    
    // 6. คำแนะนำ
    echo "<h2>📋 คำแนะนำการแก้ไข</h2>";
    echo "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3;'>";
    echo "<h3>ถ้าข้อมูลไม่ได้เปลี่ยนแปลง:</h3>";
    echo "<ol>";
    echo "<li><strong>ตรวจสอบข้อมูลทดลอง:</strong> รันไฟล์ create_sample_data.sql ใน phpMyAdmin</li>";
    echo "<li><strong>ทดสอบระบบ:</strong> เข้าไปที่ manual_test_cron.php เพื่อทดสอบการทำงาน</li>";
    echo "<li><strong>ตั้งค่า Cron Jobs:</strong> ใช้คำสั่งใน CRON_SETUP_GUIDE.md</li>";
    echo "<li><strong>ตรวจสอบสิทธิ์:</strong> ไฟล์ cron/*.php ต้องมีสิทธิ์รันได้</li>";
    echo "<li><strong>ตรวจสอบ PHP Path:</strong> ใช้คำสั่ง which php ใน server</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการตรวจสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 