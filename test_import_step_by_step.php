<?php
/**
 * Test Import Step by Step - ทดสอบการ import แบบทีละขั้นตอน
 */

echo "<h1>🧪 Test Import Step by Step - ทดสอบการ import แบบทีละขั้นตอน</h1>";

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>ขั้นตอนที่ 1: โหลดไฟล์ที่จำเป็น</h2>";

try {
    require_once 'config/config.php';
    echo "✅ config/config.php โหลดสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ config/config.php Error: " . $e->getMessage() . "<br>";
    exit;
}

try {
    require_once 'app/core/Database.php';
    echo "✅ Database.php โหลดสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Database.php Error: " . $e->getMessage() . "<br>";
    exit;
}

try {
    require_once 'app/services/ImportExportService.php';
    echo "✅ ImportExportService.php โหลดสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ ImportExportService.php Error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>ขั้นตอนที่ 2: สร้าง Service</h2>";

try {
    $service = new ImportExportService();
    echo "✅ ImportExportService สร้างสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ ImportExportService Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
    exit;
}

echo "<h2>ขั้นตอนที่ 3: สร้างไฟล์ CSV</h2>";

try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_step_by_step.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,ขั้นตอน,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบขั้นตอน\n";
    
    file_put_contents($testFile, $csvContent);
    echo "✅ สร้างไฟล์ CSV สำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
} catch (Exception $e) {
    echo "❌ CSV Creation Error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>ขั้นตอนที่ 4: อ่านไฟล์ CSV</h2>";

try {
    $content = file_get_contents($testFile);
    if ($content === false) {
        echo "❌ ไม่สามารถอ่านไฟล์ได้<br>";
        exit;
    }
    
    echo "✅ อ่านไฟล์สำเร็จ<br>";
    echo "ขนาดไฟล์: " . strlen($content) . " bytes<br>";
    echo "จำนวนบรรทัด: " . substr_count($content, "\n") . "<br>";
    
    // ทดสอบ encoding detection
    $encodings = ['UTF-8', 'ISO-8859-11', 'Windows-874'];
    $encoding = mb_detect_encoding($content, $encodings, true);
    echo "Encoding ที่ตรวจพบ: " . ($encoding ?: 'ไม่ทราบ') . "<br>";
    
    // ทดสอบการ parse CSV
    $lines = explode("\n", $content);
    $lines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    echo "จำนวนบรรทัดที่ไม่ว่าง: " . count($lines) . "<br>";
    
    if (!empty($lines)) {
        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine);
        echo "Headers: " . json_encode($headers) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ File Reading Error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>ขั้นตอนที่ 5: ทดสอบการ import (1)</h2>";

try {
    echo "🔍 เริ่มการ import ขั้นตอนที่ 1...<br>";
    
    // เพิ่ม memory limit และ execution time
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);
    
    echo "✅ ตั้งค่า memory และ execution time สำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Setting Error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>ขั้นตอนที่ 6: ทดสอบการ import (2)</h2>";

try {
    echo "🔍 เริ่มการ import ขั้นตอนที่ 2...<br>";
    
    // เรียกใช้ method โดยตรง
    $results = $service->importSalesFromCSV($testFile);
    
    echo "✅ Import สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Import Error: " . $e->getMessage() . "<br>";
    echo "Error Type: " . get_class($e) . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

echo "<h2>ขั้นตอนที่ 7: ทำความสะอาด</h2>";

try {
    if (file_exists($testFile)) {
        unlink($testFile);
        echo "✅ ลบไฟล์ทดสอบสำเร็จ<br>";
    }
} catch (Exception $e) {
    echo "❌ Cleanup Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุป</h2>";
echo "การทดสอบแบบทีละขั้นตอนเสร็จสิ้นแล้ว<br>";
echo "หากหยุดที่ขั้นตอนใด แสดงว่าปัญหาอยู่ที่ขั้นตอนนั้น<br>";
?> 