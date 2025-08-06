<?php
/**
 * Test Error Log Check - ตรวจสอบ error log และ debug importSalesFromCSV
 */

echo "<h1>🔍 Test Error Log Check - ตรวจสอบ error log และ debug importSalesFromCSV</h1>";

// 1. ตรวจสอบ error log path
echo "<h2>1. ตรวจสอบ error log path</h2>";
$errorLogPath = ini_get('error_log');
echo "Error log path: " . ($errorLogPath ?: 'Not set') . "<br>";

// 2. ทดสอบการเขียน error log
echo "<h2>2. ทดสอบการเขียน error log</h2>";
error_log("Test error log entry from test_error_log_check.php");
echo "✅ เขียน error log สำเร็จ<br>";

// 3. โหลดไฟล์ที่จำเป็น
echo "<h2>3. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/ImportExportService.php';
    echo "✅ โหลดไฟล์ที่จำเป็นสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 4. สร้าง Service และ Database
echo "<h2>4. สร้าง Service และ Database</h2>";
try {
    $service = new ImportExportService();
    $db = new Database();
    echo "✅ สร้าง Service และ Database สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error creating objects: " . $e->getMessage() . "<br>";
    exit;
}

// 5. สร้างไฟล์ CSV ทดสอบ
echo "<h2>5. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_error_log.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,ระบบ,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบระบบ\n";
    
    file_put_contents($testFile, $csvContent);
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
} catch (Exception $e) {
    echo "❌ CSV Creation Error: " . $e->getMessage() . "<br>";
    exit;
}

// 6. ทดสอบการ import พร้อม error handling
echo "<h2>6. ทดสอบการ import พร้อม error handling</h2>";

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "เริ่มการ import...<br>";
    
    // เรียกใช้ importSalesFromCSV method
    $results = $service->importSalesFromCSV($testFile);
    
    echo "✅ Import สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
    
    // ตรวจสอบ error log
    echo "<h3>ตรวจสอบ Error Log:</h3>";
    $errorLog = error_get_last();
    if ($errorLog) {
        echo "Last Error: <pre>" . print_r($errorLog, true) . "</pre><br>";
    }
}

// 7. ตรวจสอบ error log หลังจาก import
echo "<h2>7. ตรวจสอบ error log หลังจาก import</h2>";
if ($errorLogPath && file_exists($errorLogPath)) {
    echo "Error log file exists<br>";
    $logSize = filesize($errorLogPath);
    echo "Error log size: {$logSize} bytes<br>";
    
    if ($logSize > 0) {
        $recentLogs = file_get_contents($errorLogPath);
        if (strlen($recentLogs) > 2000) {
            $recentLogs = substr($recentLogs, -2000);
        }
        echo "Recent error logs:<br>";
        echo "<pre>" . htmlspecialchars($recentLogs) . "</pre>";
    }
} else {
    echo "Error log file does not exist or not configured<br>";
}

// 8. ทดสอบการเรียกใช้ method อื่นๆ
echo "<h2>8. ทดสอบการเรียกใช้ method อื่นๆ</h2>";

try {
    // ทดสอบ getSalesColumnMap method
    $reflection = new ReflectionClass($service);
    $getSalesColumnMapMethod = $reflection->getMethod('getSalesColumnMap');
    $getSalesColumnMapMethod->setAccessible(true);
    $columnMap = $getSalesColumnMapMethod->invoke($service);
    
    echo "✅ getSalesColumnMap method ทำงานได้<br>";
    echo "Column map: " . json_encode($columnMap) . "<br>";
    
} catch (Exception $e) {
    echo "❌ getSalesColumnMap Error: " . $e->getMessage() . "<br>";
}

// 9. ทำความสะอาด
echo "<h2>9. ทำความสะอาด</h2>";
try {
    if (file_exists($testFile)) {
        unlink($testFile);
        echo "✅ ลบไฟล์ทดสอบสำเร็จ<br>";
    }
} catch (Exception $e) {
    echo "❌ Cleanup Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการทดสอบ</h2>";
echo "การทดสอบเสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบนและ error log เพื่อระบุปัญหาที่แท้จริง<br>";
?> 