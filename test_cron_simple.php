<?php
/**
 * ทดสอบ Cron Jobs แบบง่าย
 */

// Load configuration
require_once 'config/config.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>ทดสอบ Cron Jobs แบบง่าย</title><meta charset='UTF-8'></head><body>";
echo "<h1>🧪 ทดสอบ Cron Jobs แบบง่าย</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // เชื่อมต่อฐานข้อมูล
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("การเชื่อมต่อล้มเหลว: " . $mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✅ <strong>การเชื่อมต่อสำเร็จ!</strong></p>";
    
    echo "<hr>";
    
    // 1. ตรวจสอบข้อมูลลูกค้าทดลอง
    echo "<h2>1. ตรวจสอบข้อมูลลูกค้าทดลอง</h2>";
    
    $sql = "SELECT COUNT(*) as count FROM customers WHERE first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')";
    $result = $mysqli->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo "<p style='color: green;'>✅ พบข้อมูลลูกค้าทดลอง {$row['count']} รายการ</p>";
        
        // แสดงข้อมูลปัจจุบัน
        $sql = "SELECT 
                    CONCAT(first_name, ' ', last_name) as customer_name,
                    customer_grade,
                    temperature_status,
                    total_purchase_amount,
                    DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) as days_since_contact
                FROM customers 
                WHERE first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')
                ORDER BY total_purchase_amount DESC";
        
        $result = $mysqli->query($sql);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ลูกค้า</th><th>เกรด</th><th>อุณหภูมิ</th><th>ยอดซื้อ</th><th>วันที่ไม่ติดต่อ</th>";
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
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ ไม่พบข้อมูลลูกค้าทดลอง</p>";
        echo "<p><strong>กรุณารันไฟล์ create_sample_data_simple.sql ใน phpMyAdmin ก่อน</strong></p>";
    }
    
    echo "<hr>";
    
    // 2. ทดสอบการอัปเดตเกรด (ถ้ามีข้อมูล)
    if ($row['count'] > 0) {
        echo "<h2>2. ทดสอบการอัปเดตเกรดลูกค้า</h2>";
        
        // หาลูกค้าที่ควรอัปเกรด
        $sql = "SELECT 
                    customer_id,
                    CONCAT(first_name, ' ', last_name) as customer_name,
                    customer_grade,
                    total_purchase_amount
                FROM customers 
                WHERE total_purchase_amount >= 100000 AND customer_grade != 'A+'
                AND first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')
                ORDER BY total_purchase_amount DESC";
        
        $result = $mysqli->query($sql);
        $updatedCount = 0;
        $changes = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $oldGrade = $row['customer_grade'];
                $newGrade = 'A+'; // อัปเกรดเป็น A+
                
                // อัปเดตเกรด
                $updateSql = "UPDATE customers SET customer_grade = ? WHERE customer_id = ?";
                $stmt = $mysqli->prepare($updateSql);
                $stmt->bind_param("si", $newGrade, $row['customer_id']);
                
                if ($stmt->execute()) {
                    $updatedCount++;
                    $changes[] = [
                        'customer_name' => $row['customer_name'],
                        'old_grade' => $oldGrade,
                        'new_grade' => $newGrade,
                        'total_purchase' => $row['total_purchase_amount']
                    ];
                    
                    // บันทึก log
                    $logSql = "INSERT INTO activity_logs (user_id, activity_type, table_name, record_id, action, old_values, new_values, created_at) 
                              VALUES (NULL, 'grade_change', 'customers', ?, 'update', ?, ?, NOW())";
                    $logStmt = $mysqli->prepare($logSql);
                    $oldValues = json_encode(['customer_grade' => $oldGrade]);
                    $newValues = json_encode(['customer_grade' => $newGrade]);
                    $logStmt->bind_param("iss", $row['customer_id'], $oldValues, $newValues);
                    $logStmt->execute();
                }
            }
        }
        
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> อัปเดตเกรดลูกค้า {$updatedCount} รายการ<br>";
        
        if (!empty($changes)) {
            echo "<strong>การเปลี่ยนแปลงเกรด:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ลูกค้า</th><th>เกรดเดิม</th><th>เกรดใหม่</th><th>ยอดซื้อ</th></tr>";
            
            foreach ($changes as $change) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($change['customer_name']) . "</td>";
                echo "<td style='text-align: center;'>" . htmlspecialchars($change['old_grade']) . "</td>";
                echo "<td style='text-align: center; color: blue; font-weight: bold;'>" . htmlspecialchars($change['new_grade']) . "</td>";
                echo "<td style='text-align: right;'>฿" . number_format($change['total_purchase'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<em>ไม่มีการเปลี่ยนแปลงเกรด (อาจจะอัปเกรดแล้ว)</em><br>";
        }
        echo "</div>";
        
        echo "<hr>";
        
        // 3. ทดสอบการอัปเดตอุณหภูมิ
        echo "<h2>3. ทดสอบการอัปเดตอุณหภูมิลูกค้า</h2>";
        
        // หาลูกค้าที่ควรเปลี่ยนอุณหภูมิ
        $sql = "SELECT 
                    customer_id,
                    CONCAT(first_name, ' ', last_name) as customer_name,
                    temperature_status,
                    DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) as days_since_contact
                FROM customers 
                WHERE DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) >= 90 
                AND temperature_status != 'frozen'
                AND first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')
                ORDER BY days_since_contact DESC";
        
        $result = $mysqli->query($sql);
        $updatedCount = 0;
        $changes = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $oldTemperature = $row['temperature_status'];
                $newTemperature = 'frozen'; // เปลี่ยนเป็น frozen
                
                // อัปเดตอุณหภูมิ
                $updateSql = "UPDATE customers SET temperature_status = ? WHERE customer_id = ?";
                $stmt = $mysqli->prepare($updateSql);
                $stmt->bind_param("si", $newTemperature, $row['customer_id']);
                
                if ($stmt->execute()) {
                    $updatedCount++;
                    $changes[] = [
                        'customer_name' => $row['customer_name'],
                        'old_temperature' => $oldTemperature,
                        'new_temperature' => $newTemperature,
                        'days_since_contact' => $row['days_since_contact']
                    ];
                    
                    // บันทึก log
                    $logSql = "INSERT INTO activity_logs (user_id, activity_type, table_name, record_id, action, old_values, new_values, created_at) 
                              VALUES (NULL, 'temperature_change', 'customers', ?, 'update', ?, ?, NOW())";
                    $logStmt = $mysqli->prepare($logSql);
                    $oldValues = json_encode(['temperature_status' => $oldTemperature]);
                    $newValues = json_encode(['temperature_status' => $newTemperature]);
                    $logStmt->bind_param("iss", $row['customer_id'], $oldValues, $newValues);
                    $logStmt->execute();
                }
            }
        }
        
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> อัปเดตอุณหภูมิลูกค้า {$updatedCount} รายการ<br>";
        
        if (!empty($changes)) {
            echo "<strong>การเปลี่ยนแปลงอุณหภูมิ:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ลูกค้า</th><th>อุณหภูมิเดิม</th><th>อุณหภูมิใหม่</th><th>วันที่ไม่ติดต่อ</th></tr>";
            
            foreach ($changes as $change) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($change['customer_name']) . "</td>";
                echo "<td style='text-align: center;'>" . htmlspecialchars($change['old_temperature']) . "</td>";
                echo "<td style='text-align: center; color: purple; font-weight: bold;'>" . htmlspecialchars($change['new_temperature']) . "</td>";
                echo "<td style='text-align: center;'>" . $change['days_since_contact'] . " วัน</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<em>ไม่มีการเปลี่ยนแปลงอุณหภูมิ (อาจจะอัปเดตแล้ว)</em><br>";
        }
        echo "</div>";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<h2>📋 คำแนะนำ</h2>";
echo "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3;'>";
echo "<h3>ขั้นตอนต่อไป:</h3>";
echo "<ol>";
echo "<li><strong>สร้างข้อมูลทดลอง:</strong> รันไฟล์ create_sample_data_simple.sql ใน phpMyAdmin</li>";
echo "<li><strong>ทดสอบอีกครั้ง:</strong> รีเฟรชหน้านี้</li>";
echo "<li><strong>ตั้งค่า Cron Jobs:</strong> ใน cPanel ตั้งค่าให้รันทุกวัน 1:00 น.</li>";
echo "<li><strong>ตรวจสอบผลลัพธ์:</strong> เช้าพรุ่งนี้ดูว่าข้อมูลเปลี่ยนแปลงหรือไม่</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการทดสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 