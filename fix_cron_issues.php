<?php
/**
 * ตรวจสอบและแก้ไขปัญหาของ Cron Jobs
 */

// Load configuration
require_once 'config/config.php';
require_once 'app/core/Database.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><title>แก้ไขปัญหา Cron Jobs</title><meta charset='UTF-8'></head><body>";
echo "<h1>🔧 ตรวจสอบและแก้ไขปัญหา Cron Jobs</h1>";
echo "<p><strong>เวลาปัจจุบัน:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $db = new Database();
    
    echo "<hr>";
    
    // 1. ตรวจสอบตารางที่จำเป็น
    echo "<h2>1. ตรวจสอบตารางที่จำเป็น</h2>";
    $requiredTables = [
        'notifications',
        'customer_recall_list', 
        'cron_job_logs',
        'activity_logs',
        'cron_job_settings'
    ];
    
    $missingTables = [];
    foreach ($requiredTables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✅ ตาราง $table - มีอยู่</p>";
        } else {
            echo "<p style='color: red;'>❌ ตาราง $table - ไม่พบ</p>";
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
        echo "<h3>⚠️ ตารางที่ขาดหายไป:</h3>";
        echo "<p>กรุณารันไฟล์ <strong>database/cron_tables_fixed.sql</strong> ใน phpMyAdmin</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    
    // 2. ตรวจสอบข้อมูลลูกค้าทดลอง
    echo "<h2>2. ตรวจสอบข้อมูลลูกค้าทดลอง</h2>";
    $result = $db->query("SELECT COUNT(*) as count FROM customers WHERE first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            echo "<p style='color: green;'>✅ พบข้อมูลลูกค้าทดลอง {$row['count']} รายการ</p>";
        } else {
            echo "<p style='color: red;'>❌ ไม่พบข้อมูลลูกค้าทดลอง</p>";
            echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
            echo "<h3>⚠️ ข้อมูลทดลองขาดหายไป:</h3>";
            echo "<p>กรุณารันไฟล์ <strong>create_sample_data.sql</strong> ใน phpMyAdmin</p>";
            echo "</div>";
        }
    }
    
    echo "<hr>";
    
    // 3. ตรวจสอบการตั้งค่า Cron Jobs
    echo "<h2>3. ตรวจสอบการตั้งค่า Cron Jobs</h2>";
    $result = $db->query("SELECT COUNT(*) as count FROM cron_job_settings");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            echo "<p style='color: green;'>✅ พบการตั้งค่า Cron Jobs {$row['count']} รายการ</p>";
        } else {
            echo "<p style='color: red;'>❌ ไม่พบการตั้งค่า Cron Jobs</p>";
            echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
            echo "<h3>⚠️ การตั้งค่า Cron Jobs ขาดหายไป:</h3>";
            echo "<p>กรุณารันไฟล์ <strong>database/cron_tables_fixed.sql</strong> อีกครั้ง (ส่วน INSERT)</p>";
            echo "</div>";
        }
    }
    
    echo "<hr>";
    
    // 4. ทดสอบการเชื่อมต่อและสิทธิ์
    echo "<h2>4. ทดสอบการเชื่อมต่อและสิทธิ์</h2>";
    
    // ทดสอบการเขียน log
    try {
        $sql = "INSERT INTO cron_job_logs (job_name, status, start_time, output) VALUES ('test_connection', 'success', NOW(), 'ทดสอบการเชื่อมต่อสำเร็จ')";
        $result = $db->query($sql);
        
        if ($result) {
            echo "<p style='color: green;'>✅ การเขียน log สำเร็จ</p>";
            
            // ลบ log ทดสอบ
            $db->query("DELETE FROM cron_job_logs WHERE job_name = 'test_connection'");
        } else {
            echo "<p style='color: red;'>❌ การเขียน log ล้มเหลว</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ เกิดข้อผิดพลาดในการเขียน log: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 5. แสดงคำสั่งสำหรับตั้งค่า Cron Jobs
    echo "<h2>5. คำสั่งสำหรับตั้งค่า Cron Jobs</h2>";
    echo "<div style='background: #e3f2fd; padding: 15px; border: 1px solid #2196F3; margin: 10px 0;'>";
    echo "<h3>📋 คำสั่งสำหรับ cPanel:</h3>";
    echo "<p><strong>Minute:</strong> 0</p>";
    echo "<p><strong>Hour:</strong> 1</p>";
    echo "<p><strong>Day:</strong> *</p>";
    echo "<p><strong>Month:</strong> *</p>";
    echo "<p><strong>Weekday:</strong> *</p>";
    echo "<p><strong>Command:</strong></p>";
    echo "<code style='background: #f5f5f5; padding: 5px; display: block;'>/usr/bin/php /home/primacom/domains/prima49.com/public_html/Customer/cron/run_all_jobs.php</code>";
    echo "</div>";
    
    echo "<hr>";
    
    // 6. ลิงก์สำหรับทดสอบ
    echo "<h2>6. ลิงก์สำหรับทดสอบ</h2>";
    echo "<ul>";
    echo "<li><a href='simple_test_cron.php' target='_blank'>🧪 ทดสอบ Cron Jobs แบบง่าย</a></li>";
    echo "<li><a href='manual_test_cron.php' target='_blank'>🤖 ทดสอบ Cron Jobs แบบ Manual</a></li>";
    echo "<li><a href='check_cron_status.php' target='_blank'>🔍 ตรวจสอบสถานะ Cron Jobs</a></li>";
    echo "</ul>";
    
    echo "<hr>";
    
    // 7. สรุปปัญหาและวิธีแก้ไข
    echo "<h2>7. สรุปปัญหาและวิธีแก้ไข</h2>";
    echo "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3;'>";
    echo "<h3>🔍 สาเหตุที่ข้อมูลไม่ได้เปลี่ยนแปลง:</h3>";
    echo "<ol>";
    echo "<li><strong>ตารางขาดหายไป:</strong> รัน database/cron_tables_fixed.sql</li>";
    echo "<li><strong>ข้อมูลทดลองขาดหายไป:</strong> รัน create_sample_data.sql</li>";
    echo "<li><strong>Cron Jobs ยังไม่ได้ตั้งค่า:</strong> ตั้งค่าใน cPanel</li>";
    echo "<li><strong>สิทธิ์ไฟล์ไม่ถูกต้อง:</strong> ตรวจสอบสิทธิ์ไฟล์ cron/*.php</li>";
    echo "<li><strong>PHP Path ไม่ถูกต้อง:</strong> ตรวจสอบ path ของ PHP ใน server</li>";
    echo "</ol>";
    
    echo "<h3>✅ ขั้นตอนการแก้ไข:</h3>";
    echo "<ol>";
    echo "<li>รันไฟล์ database/cron_tables_fixed.sql ใน phpMyAdmin</li>";
    echo "<li>รันไฟล์ create_sample_data.sql ใน phpMyAdmin</li>";
    echo "<li>ทดสอบด้วย simple_test_cron.php</li>";
    echo "<li>ตั้งค่า Cron Jobs ใน cPanel</li>";
    echo "<li>ตรวจสอบผลลัพธ์พรุ่งนี้เช้า</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><strong>เสร็จสิ้นการตรวจสอบเมื่อ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 