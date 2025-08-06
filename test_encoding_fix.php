<?php
/**
 * Test Encoding Fix - ทดสอบการแก้ไขปัญหา encoding
 */

echo "<h1>🔧 Test Encoding Fix - ทดสอบการแก้ไขปัญหา encoding</h1>";

// 1. โหลดไฟล์ที่จำเป็น
echo "<h2>1. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/ImportExportService.php';
    echo "✅ โหลดไฟล์ที่จำเป็นสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. สร้าง Service และ Database
echo "<h2>2. สร้าง Service และ Database</h2>";
try {
    $service = new ImportExportService();
    $db = new Database();
    echo "✅ สร้าง Service และ Database สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error creating objects: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ทดสอบ encoding detection
echo "<h2>3. ทดสอบ encoding detection</h2>";
try {
    $testContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์\nทดสอบ,ระบบ,0812345678";
    
    // ทดสอบ encoding detection ที่แก้ไขแล้ว
    $encodings = ['UTF-8', 'ISO-8859-11', 'Windows-874'];
    $detectedEncoding = mb_detect_encoding($testContent, $encodings, true);
    
    if (!$detectedEncoding) {
        $detectedEncoding = 'UTF-8';
    }
    
    echo "✅ Encoding detection สำเร็จ: " . $detectedEncoding . "<br>";
    echo "✅ ใช้ encodings: " . json_encode($encodings) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Encoding detection Error: " . $e->getMessage() . "<br>";
}

// 4. สร้างไฟล์ CSV ทดสอบ
echo "<h2>4. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_encoding_fix.csv';
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

// 5. ทดสอบการ import
echo "<h2>5. ทดสอบการ import</h2>";
try {
    echo "เริ่มการ import...<br>";
    
    // เปิด error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $results = $service->importSalesFromCSV($testFile);
    
    echo "✅ Import สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 6. ทดสอบการ import customers only
echo "<h2>6. ทดสอบการ import customers only</h2>";
try {
    echo "เริ่มการ import customers only...<br>";
    
    $results = $service->importCustomersOnlyFromCSV($testFile);
    
    echo "✅ Import customers only สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Import customers only Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 7. ทำความสะอาด
echo "<h2>7. ทำความสะอาด</h2>";
try {
    if (file_exists($testFile)) {
        unlink($testFile);
        echo "✅ ลบไฟล์ทดสอบสำเร็จ<br>";
    }
} catch (Exception $e) {
    echo "❌ Cleanup Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการทดสอบ</h2>";
echo "การทดสอบการแก้ไขปัญหา encoding เสร็จสิ้นแล้ว<br>";
echo "หากไม่พบ error แสดงว่าการแก้ไขสำเร็จแล้ว! 🚀<br>";
?> 