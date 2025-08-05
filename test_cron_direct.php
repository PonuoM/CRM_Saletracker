<?php
/**
 * ทดสอบ Cron Jobs โดยใช้ mysqli โดยตรง
 */

// Load configuration
require_once 'config/config.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>ทดสอบ Cron Jobs โดยตรง</title><meta charset='UTF-8'></head><body>";
echo "<h1>🧪 ทดสอบ Cron Jobs โดยใช้ mysqli โดยตรง</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // เชื่อมต่อฐานข้อมูลด้วย mysqli
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("การเชื่อมต่อล้มเหลว: " . $mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✅ <strong>การเชื่อมต่อสำเร็จ!</strong></p>";
    
    echo "<hr>";
    
    // 1. ทดสอบการอัปเดตเกรดลูกค้า
    echo "<h2>1. ทดสอบการอัปเดตเกรดลูกค้า</h2>";
    
    // หาลูกค้าที่ควรอัปเกรด
    $sql = "SELECT 
                customer_id,
                CONCAT(first_name, ' ', last_name) as customer_name,
                customer_grade,
                total_purchase_amount
            FROM customers 
            WHERE total_purchase_amount >= 100000 AND customer_grade != 'A+'
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
        echo "<em>ไม่มีการเปลี่ยนแปลงเกรด</em><br>";
    }
    echo "</div>";
    
    echo "<hr>";
    
    // 2. ทดสอบการอัปเดตอุณหภูมิลูกค้า
    echo "<h2>2. ทดสอบการอัปเดตอุณหภูมิลูกค้า</h2>";
    
    // หาลูกค้าที่ควรเปลี่ยนอุณหภูมิ
    $sql = "SELECT 
                customer_id,
                CONCAT(first_name, ' ', last_name) as customer_name,
                temperature_status,
                DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) as days_since_contact,
                last_contact_at
            FROM customers 
            WHERE DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) >= 90 
            AND temperature_status != 'frozen'
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
                    'days_since_contact' => $row['days_since_contact'],
                    'last_contact' => $row['last_contact_at']
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
        echo "<em>ไม่มีการเปลี่ยนแปลงอุณหภูมิ</em><br>";
    }
    echo "</div>";
    
    echo "<hr>";
    
    // 3. แสดงผลลัพธ์ปัจจุบัน
    echo "<h2>3. ผลลัพธ์ปัจจุบัน</h2>";
    
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
    
    if ($result && $result->num_rows > 0) {
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
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการทดสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 