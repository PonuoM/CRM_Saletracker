<?php
/**
 * ทดสอบ Cron Jobs แบบง่าย
 */

// Load configuration
require_once 'config/config.php';
require_once 'app/services/CronJobService.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>ทดสอบ Cron Jobs แบบง่าย</title><meta charset='UTF-8'></head><body>";
echo "<h1>🧪 ทดสอบ Cron Jobs แบบง่าย</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $cronService = new CronJobService();
    
    echo "<hr>";
    
    // ทดสอบการอัปเดตเกรดลูกค้า
    echo "<h2>ทดสอบการอัปเดตเกรดลูกค้า</h2>";
    echo "<p>กำลังรัน...</p>";
    
    $result = $cronService->updateCustomerGrades();
    
    if ($result['success']) {
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> อัปเดตเกรดลูกค้า {$result['updated_count']} รายการ<br>";
        
        if (!empty($result['changes'])) {
            echo "<strong>การเปลี่ยนแปลง:</strong><br>";
            foreach ($result['changes'] as $change) {
                echo "- " . $change['customer_name'] . ": " . $change['old_grade'] . " → " . $change['new_grade'] . "<br>";
            }
        } else {
            echo "<em>ไม่มีการเปลี่ยนแปลง</em><br>";
        }
        echo "</div>";
    } else {
        echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . $result['error'] . "</div>";
    }
    
    echo "<hr>";
    
    // ทดสอบการอัปเดตอุณหภูมิลูกค้า
    echo "<h2>ทดสอบการอัปเดตอุณหภูมิลูกค้า</h2>";
    echo "<p>กำลังรัน...</p>";
    
    $result = $cronService->updateCustomerTemperatures();
    
    if ($result['success']) {
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> อัปเดตอุณหภูมิลูกค้า {$result['updated_count']} รายการ<br>";
        
        if (!empty($result['changes'])) {
            echo "<strong>การเปลี่ยนแปลง:</strong><br>";
            foreach ($result['changes'] as $change) {
                echo "- " . $change['customer_name'] . ": " . $change['old_temperature'] . " → " . $change['new_temperature'] . "<br>";
            }
        } else {
            echo "<em>ไม่มีการเปลี่ยนแปลง</em><br>";
        }
        echo "</div>";
    } else {
        echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . $result['error'] . "</div>";
    }
    
    echo "<hr>";
    
    // แสดงผลลัพธ์ปัจจุบัน
    echo "<h2>ผลลัพธ์ปัจจุบัน</h2>";
    
    $db = new Database();
    $sql = "SELECT 
                CONCAT(first_name, ' ', last_name) as customer_name,
                customer_grade,
                temperature_status,
                total_purchase_amount,
                DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) as days_since_contact
            FROM customers 
            WHERE first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')
            ORDER BY total_purchase_amount DESC";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ลูกค้า</th><th>เกรด</th><th>อุณหภูมิ</th><th>ยอดซื้อ</th><th>วันที่ไม่ติดต่อ</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
            echo "<td style='text-align: center; font-weight: bold;'>" . htmlspecialchars($row['customer_grade']) . "</td>";
            echo "<td style='text-align: center;'>" . htmlspecialchars($row['temperature_status']) . "</td>";
            echo "<td style='text-align: right;'>฿" . number_format($row['total_purchase_amount'], 2) . "</td>";
            echo "<td style='text-align: center;'>" . $row['days_since_contact'] . " วัน</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ ไม่พบข้อมูลลูกค้าทดลอง</p>";
    }
    
    echo "<hr>";
    
    // คำแนะนำ
    echo "<h2>📋 คำแนะนำ</h2>";
    echo "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3;'>";
    echo "<h3>ถ้าข้อมูลยังไม่ได้เปลี่ยนแปลง:</h3>";
    echo "<ol>";
    echo "<li><strong>ตรวจสอบข้อมูลทดลอง:</strong> รันไฟล์ create_sample_data.sql ใน phpMyAdmin</li>";
    echo "<li><strong>ตรวจสอบ error:</strong> ดูข้อผิดพลาดที่แสดงด้านบน</li>";
    echo "<li><strong>ตรวจสอบฐานข้อมูล:</strong> ดูว่าตาราง cron_tables_fixed.sql ถูกสร้างแล้วหรือไม่</li>";
    echo "<li><strong>ตรวจสอบสิทธิ์:</strong> ไฟล์ต้องมีสิทธิ์อ่านและเขียน</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการทดสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 