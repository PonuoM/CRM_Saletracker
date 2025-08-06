<?php
/**
 * Check Error Log - ตรวจสอบ error log ของเซิร์ฟเวอร์
 */

echo "<h1>🔍 Check Error Log - ตรวจสอบ error log</h1>";

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>1. ตรวจสอบ PHP Error Log Path</h2>";

$errorLogPath = ini_get('error_log');
echo "PHP Error Log Path: " . ($errorLogPath ?: 'ไม่ตั้งค่า') . "<br>";

$logErrors = ini_get('log_errors');
echo "Log Errors: " . ($logErrors ? 'เปิด' : 'ปิด') . "<br>";

echo "<h2>2. ตรวจสอบ Apache Error Log</h2>";

$apacheLogs = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/usr/local/apache2/logs/error_log',
    '/opt/lampp/logs/error_log',
    '/xampp/apache/logs/error.log'
];

foreach ($apacheLogs as $logPath) {
    if (file_exists($logPath)) {
        echo "✅ พบ Apache Error Log: {$logPath}<br>";
        
        // อ่าน 10 บรรทัดสุดท้าย
        $lines = file($logPath);
        if ($lines) {
            $lastLines = array_slice($lines, -10);
            echo "<h3>10 บรรทัดสุดท้ายของ {$logPath}:</h3>";
            echo "<pre>";
            foreach ($lastLines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        }
    } else {
        echo "❌ ไม่พบ: {$logPath}<br>";
    }
}

echo "<h2>3. ตรวจสอบ PHP Error Log</h2>";

$phpLogs = [
    '/var/log/php_errors.log',
    '/var/log/php-fpm/error.log',
    '/opt/lampp/logs/php_error_log',
    '/xampp/php/logs/php_error_log'
];

foreach ($phpLogs as $logPath) {
    if (file_exists($logPath)) {
        echo "✅ พบ PHP Error Log: {$logPath}<br>";
        
        // อ่าน 10 บรรทัดสุดท้าย
        $lines = file($logPath);
        if ($lines) {
            $lastLines = array_slice($lines, -10);
            echo "<h3>10 บรรทัดสุดท้ายของ {$logPath}:</h3>";
            echo "<pre>";
            foreach ($lastLines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        }
    } else {
        echo "❌ ไม่พบ: {$logPath}<br>";
    }
}

echo "<h2>4. ทดสอบการเขียน Error Log</h2>";

try {
    error_log("Test error log message from check_error_log.php - " . date('Y-m-d H:i:s'));
    echo "✅ เขียน test message ลง error log สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ ไม่สามารถเขียน error log ได้: " . $e->getMessage() . "<br>";
}

echo "<h2>5. ตรวจสอบ PHP Configuration</h2>";

echo "display_errors: " . (ini_get('display_errors') ? 'เปิด' : 'ปิด') . "<br>";
echo "log_errors: " . (ini_get('log_errors') ? 'เปิด' : 'ปิด') . "<br>";
echo "error_reporting: " . ini_get('error_reporting') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h2>6. ทดสอบการสร้าง Error</h2>";

try {
    echo "🔍 ทดสอบการสร้าง error...<br>";
    
    // ทดสอบการโหลดไฟล์ที่ไม่มีอยู่
    require_once 'file_that_does_not_exist.php';
    
} catch (Exception $e) {
    echo "✅ จับ error ได้: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุป</h2>";
echo "การตรวจสอบ error log เสร็จสิ้นแล้ว<br>";
echo "หากพบ error ใน log กรุณาแชร์ผลลัพธ์เพื่อการแก้ไขต่อไป<br>";
?> 