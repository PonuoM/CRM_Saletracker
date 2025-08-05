<?php
/**
 * ตรวจสอบการเชื่อมต่อฐานข้อมูลและแก้ไขปัญหา
 */

// Load configuration
require_once 'config/config.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>ตรวจสอบการเชื่อมต่อฐานข้อมูล</title><meta charset='UTF-8'></head><body>";
echo "<h1>🔍 ตรวจสอบการเชื่อมต่อฐานข้อมูล</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // 1. ตรวจสอบการตั้งค่าฐานข้อมูล
    echo "<hr>";
    echo "<h2>1. การตั้งค่าฐานข้อมูล</h2>";
    echo "<p><strong>DB_HOST:</strong> " . (defined('DB_HOST') ? DB_HOST : 'ไม่พบ') . "</p>";
    echo "<p><strong>DB_NAME:</strong> " . (defined('DB_NAME') ? DB_NAME : 'ไม่พบ') . "</p>";
    echo "<p><strong>DB_USER:</strong> " . (defined('DB_USER') ? DB_USER : 'ไม่พบ') . "</p>";
    echo "<p><strong>DB_PASS:</strong> " . (defined('DB_PASS') ? '***' : 'ไม่พบ') . "</p>";
    
    // 2. ทดสอบการเชื่อมต่อ
    echo "<hr>";
    echo "<h2>2. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
    
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ <strong>การเชื่อมต่อล้มเหลว:</strong> " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ <strong>การเชื่อมต่อสำเร็จ!</strong></p>";
        echo "<p><strong>Server Info:</strong> " . $mysqli->server_info . "</p>";
        echo "<p><strong>Database:</strong> " . $mysqli->database . "</p>";
        
        // 3. ตรวจสอบตารางด้วย mysqli
        echo "<hr>";
        echo "<h2>3. ตรวจสอบตารางด้วย mysqli</h2>";
        
        $requiredTables = [
            'notifications',
            'customer_recall_list', 
            'cron_job_logs',
            'activity_logs',
            'cron_job_settings'
        ];
        
        foreach ($requiredTables as $table) {
            $result = $mysqli->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✅ ตาราง $table - มีอยู่</p>";
            } else {
                echo "<p style='color: red;'>❌ ตาราง $table - ไม่พบ</p>";
            }
        }
        
        // 4. ตรวจสอบตารางทั้งหมด
        echo "<hr>";
        echo "<h2>4. ตารางทั้งหมดในฐานข้อมูล</h2>";
        
        $result = $mysqli->query("SHOW TABLES");
        if ($result) {
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                $tableName = $row[0];
                $isRequired = in_array($tableName, $requiredTables);
                $color = $isRequired ? 'green' : 'black';
                $mark = $isRequired ? '✅' : '📋';
                echo "<li style='color: $color;'>$mark $tableName</li>";
            }
            echo "</ul>";
        }
        
        // 5. ทดสอบการ query ตาราง cron_job_settings
        echo "<hr>";
        echo "<h2>5. ทดสอบการ query ตาราง cron_job_settings</h2>";
        
        $result = $mysqli->query("SELECT COUNT(*) as count FROM cron_job_settings");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ พบการตั้งค่า Cron Jobs {$row['count']} รายการ</p>";
            
            // แสดงรายละเอียด
            $result2 = $mysqli->query("SELECT * FROM cron_job_settings LIMIT 5");
            if ($result2 && $result2->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f0f0f0;'>";
                echo "<th>งาน</th><th>Cron Expression</th><th>สถานะ</th><th>คำอธิบาย</th>";
                echo "</tr>";
                
                while ($row = $result2->fetch_assoc()) {
                    $statusText = $row['is_enabled'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                    $statusColor = $row['is_enabled'] ? 'green' : 'red';
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['job_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['cron_expression']) . "</td>";
                    echo "<td style='color: $statusColor;'>$statusText</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถ query ตาราง cron_job_settings ได้</p>";
            echo "<p><strong>Error:</strong> " . $mysqli->error . "</p>";
        }
        
        // 6. ทดสอบการเขียน log
        echo "<hr>";
        echo "<h2>6. ทดสอบการเขียน log</h2>";
        
        $sql = "INSERT INTO cron_job_logs (job_name, status, start_time, output) VALUES ('debug_test', 'success', NOW(), 'ทดสอบการเขียน log สำเร็จ')";
        $result = $mysqli->query($sql);
        
        if ($result) {
            echo "<p style='color: green;'>✅ การเขียน log สำเร็จ</p>";
            
            // ลบ log ทดสอบ
            $mysqli->query("DELETE FROM cron_job_logs WHERE job_name = 'debug_test'");
        } else {
            echo "<p style='color: red;'>❌ การเขียน log ล้มเหลว</p>";
            echo "<p><strong>Error:</strong> " . $mysqli->error . "</p>";
        }
        
        $mysqli->close();
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h2>📋 สรุปและคำแนะนำ</h2>";
echo "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3;'>";
echo "<h3>ถ้าตารางมีอยู่แต่สคริปต์บอกว่าไม่พบ:</h3>";
echo "<ol>";
echo "<li><strong>ตรวจสอบการตั้งค่า:</strong> ดูว่าการตั้งค่าฐานข้อมูลถูกต้องหรือไม่</li>";
echo "<li><strong>ตรวจสอบสิทธิ์:</strong> ดูว่าผู้ใช้ฐานข้อมูลมีสิทธิ์เข้าถึงตารางหรือไม่</li>";
echo "<li><strong>ตรวจสอบ Database Class:</strong> อาจมีปัญหาใน Database.php</li>";
echo "<li><strong>ทดสอบด้วย mysqli:</strong> ใช้ mysqli โดยตรงแทน Database Class</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการตรวจสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 