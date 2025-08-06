<?php
/**
 * Test Service Import Focus - ทดสอบการ import ผ่าน Service แบบเฉพาะเจาะจง
 * เน้นการ debug step 6 ของ test_import_csv_debug.php
 */

echo "<h1>🔍 Test Service Import Focus - ทดสอบการ import ผ่าน Service แบบเฉพาะเจาะจง</h1>";

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

// 3. สร้างไฟล์ CSV ทดสอบ
echo "<h2>3. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_service_focus.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,ระบบ,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบระบบ\n";
    
    file_put_contents($testFile, $csvContent);
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
    echo "เนื้อหา: <pre>" . htmlspecialchars($csvContent) . "</pre><br>";
} catch (Exception $e) {
    echo "❌ CSV Creation Error: " . $e->getMessage() . "<br>";
    exit;
}

// 4. ทดสอบการอ่านไฟล์ CSV
echo "<h2>4. ทดสอบการอ่านไฟล์ CSV</h2>";
try {
    $fileContent = file_get_contents($testFile);
    if ($fileContent === false) {
        echo "❌ ไม่สามารถอ่านไฟล์ได้<br>";
        exit;
    }
    echo "✅ อ่านไฟล์สำเร็จ<br>";
    echo "ความยาวเนื้อหา: " . strlen($fileContent) . " bytes<br>";
    echo "เนื้อหา: <pre>" . htmlspecialchars($fileContent) . "</pre><br>";
} catch (Exception $e) {
    echo "❌ File Read Error: " . $e->getMessage() . "<br>";
    exit;
}

// 5. ทดสอบการตรวจสอบการมีอยู่ของตาราง
echo "<h2>5. ทดสอบการตรวจสอบการมีอยู่ของตาราง</h2>";
try {
    $tables = ['customers', 'orders', 'customer_activities'];
    foreach ($tables as $table) {
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?", [$table]);
        if ($result && $result['count'] > 0) {
            echo "✅ ตาราง {$table} มีอยู่<br>";
        } else {
            echo "❌ ตาราง {$table} ไม่มีอยู่<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Table Check Error: " . $e->getMessage() . "<br>";
    exit;
}

// 6. ทดสอบการ import ผ่าน Service โดยตรง
echo "<h2>6. ทดสอบการ import ผ่าน Service โดยตรง</h2>";
try {
    echo "เริ่มการ import...<br>";
    $results = $service->importSalesFromCSV($testFile);
    echo "✅ Import ผ่าน Service สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
} catch (Exception $e) {
    echo "❌ Service Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
    
    // ตรวจสอบ error log
    echo "<h3>ตรวจสอบ Error Log:</h3>";
    $errorLog = error_get_last();
    if ($errorLog) {
        echo "Last Error: <pre>" . print_r($errorLog, true) . "</pre><br>";
    }
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
echo "การทดสอบเสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบนเพื่อระบุปัญหาที่แท้จริง<br>";
?> 