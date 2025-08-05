<?php
/**
 * Manual Test Cron Jobs
 * ทดสอบ cron jobs แบบ manual ผ่าน web browser
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';
require_once 'app/services/CronJobService.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>Manual Cron Jobs Test</title><meta charset='UTF-8'></head><body>";
echo "<h1>🤖 ทดสอบ Cron Jobs แบบ Manual</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Initialize service
$cronService = new CronJobService();

echo "<hr>";

// Test 1: Customer Grade Update
echo "<h2>1. ทดสอบการอัปเดตเกรดลูกค้า</h2>";
echo "<p><em>กำลังตรวจสอบและอัปเดตเกรดลูกค้าตามยอดซื้อ...</em></p>";

try {
    $startTime = microtime(true);
    $result = $cronService->updateCustomerGrades();
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> อัปเดตเกรดลูกค้า {$result['updated_count']} รายการ ใช้เวลา {$executionTime} วินาที<br>";
        
        if (!empty($result['changes'])) {
            echo "<strong>การเปลี่ยนแปลงเกรด:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ลูกค้า</th><th>เกรดเดิม</th><th>เกรดใหม่</th><th>ยอดซื้อ</th></tr>";
            
            foreach ($result['changes'] as $change) {
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
    } else {
        echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($result['error']) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";

// Test 2: Customer Temperature Update
echo "<h2>2. ทดสอบการอัปเดตอุณหภูมิลูกค้า</h2>";
echo "<p><em>กำลังตรวจสอบและอัปเดตอุณหภูมิลูกค้าตามการติดต่อล่าสุด...</em></p>";

try {
    $startTime = microtime(true);
    $result = $cronService->updateCustomerTemperatures();
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> อัปเดตอุณหภูมิลูกค้า {$result['updated_count']} รายการ ใช้เวลา {$executionTime} วินาที<br>";
        
        if (!empty($result['changes'])) {
            echo "<strong>การเปลี่ยนแปลงอุณหภูมิ:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ลูกค้า</th><th>อุณหภูมิเดิม</th><th>อุณหภูมิใหม่</th><th>วันที่ไม่ติดต่อ</th><th>ติดต่อล่าสุด</th></tr>";
            
            foreach ($result['changes'] as $change) {
                $tempColor = [
                    'hot' => 'red',
                    'warm' => 'orange', 
                    'cold' => 'blue',
                    'frozen' => 'purple'
                ];
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($change['customer_name']) . "</td>";
                echo "<td style='text-align: center;'>" . htmlspecialchars($change['old_temperature']) . "</td>";
                echo "<td style='text-align: center; color: " . ($tempColor[$change['new_temperature']] ?? 'black') . "; font-weight: bold;'>" . htmlspecialchars($change['new_temperature']) . "</td>";
                echo "<td style='text-align: center;'>" . $change['days_since_contact'] . " วัน</td>";
                echo "<td>" . htmlspecialchars($change['last_contact']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<em>ไม่มีการเปลี่ยนแปลงอุณหภูมิ</em><br>";
        }
        echo "</div>";
    } else {
        echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($result['error']) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";

// Test 3: Customer Recall List
echo "<h2>3. ทดสอบการสร้างรายการลูกค้าที่ต้องติดตาม</h2>";
echo "<p><em>กำลังหาลูกค้าที่ไม่ได้ติดต่อนานเกิน 30 วัน...</em></p>";

try {
    $startTime = microtime(true);
    $result = $cronService->createCustomerRecallList();
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> พบลูกค้าที่ต้องติดตาม {$result['recall_count']} รายการ ใช้เวลา {$executionTime} วินาที<br>";
        
        if ($result['recall_count'] > 0) {
            echo "<strong>รายการลูกค้าที่ต้องติดตาม:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ลูกค้า</th><th>เบอร์โทร</th><th>เกรด</th><th>อุณหภูมิ</th><th>วันที่ไม่ติดต่อ</th><th>ยอดซื้อรวม</th></tr>";
            
            $customers = array_slice($result['customers'], 0, 10); // แสดง 10 รายการแรก
            foreach ($customers as $customer) {
                $gradeColor = [
                    'A+' => 'purple',
                    'A' => 'red',
                    'B' => 'orange',
                    'C' => 'blue',
                    'D' => 'gray'
                ];
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($customer['name']) . "</td>";
                echo "<td>" . htmlspecialchars($customer['phone']) . "</td>";
                echo "<td style='text-align: center; color: " . ($gradeColor[$customer['grade']] ?? 'black') . "; font-weight: bold;'>" . htmlspecialchars($customer['grade']) . "</td>";
                echo "<td style='text-align: center;'>" . htmlspecialchars($customer['temperature']) . "</td>";
                echo "<td style='text-align: center;'>" . $customer['days_since_contact'] . " วัน</td>";
                echo "<td style='text-align: right;'>฿" . number_format($customer['total_spent'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($result['recall_count'] > 10) {
                echo "<p><em>และอีก " . ($result['recall_count'] - 10) . " รายการ</em></p>";
            }
        }
        echo "</div>";
    } else {
        echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($result['error']) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";

// Test 4: Send Notifications
echo "<h2>4. ทดสอบการส่งการแจ้งเตือน</h2>";
echo "<p><em>กำลังส่งการแจ้งเตือนไปยัง telesales และ supervisor...</em></p>";

try {
    $startTime = microtime(true);
    $result = $cronService->sendCustomerRecallNotifications();
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "<div style='color: green;'>";
        echo "✅ <strong>สำเร็จ!</strong> ส่งการแจ้งเตือน {$result['notification_count']} รายการ ถึง {$result['recipient_count']} ผู้ใช้ ใช้เวลา {$executionTime} วินาที";
        echo "</div>";
    } else {
        echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($result['error']) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";

// Summary
echo "<h2>📊 สรุปการทดสอบ</h2>";
echo "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3;'>";
echo "<h3>✅ สิ่งที่ควรเห็นหลังจากรัน Cron Jobs คืนนี้:</h3>";
echo "<ul>";
echo "<li>🏷️ <strong>เกรดลูกค้า:</strong> ลูกค้าที่มียอดซื้อสูงจะได้เกรดที่ดีขึ้น</li>";
echo "<li>🌡️ <strong>อุณหภูมิลูกค้า:</strong> ลูกค้าที่ไม่ได้ติดต่อนานจะเป็น cold/frozen</li>";
echo "<li>📋 <strong>รายการติดตาม:</strong> ระบบจะสร้างรายการลูกค้าที่ต้องติดตาม</li>";
echo "<li>🔔 <strong>การแจ้งเตือน:</strong> telesales และ supervisor จะได้รับแจ้งเตือน</li>";
echo "</ul>";

echo "<h3>🕐 กำหนดการ Cron Jobs (ถ้าตั้งค่าแล้ว):</h3>";
echo "<ul>";
echo "<li><strong>02:00 น.</strong> - อัปเดตเกรดลูกค้า</li>";
echo "<li><strong>02:30 น.</strong> - อัปเดตอุณหภูมิลูกค้า</li>";
echo "<li><strong>03:00 น.</strong> - สร้างรายการลูกค้าที่ต้องติดตาม</li>";
echo "<li><strong>03:30 น.</strong> - ส่งการแจ้งเตือน</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการทดสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>