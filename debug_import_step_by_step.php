<?php
/**
 * Debug Import Step by Step - ตรวจสอบจุดที่เกิดปัญหาในการ import
 * เน้นการ debug เฉพาะจุดที่หยุดที่ "เริ่มการ import..."
 */

echo "<h1>🔍 Debug Import Step by Step - ตรวจสอบจุดที่เกิดปัญหา</h1>";

// เปิด error reporting แบบเต็ม
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// 1. โหลดไฟล์ที่จำเป็น
echo "<h2>1. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    echo "✅ config/config.php โหลดสำเร็จ<br>";
    
    require_once 'app/core/Database.php';
    echo "✅ app/core/Database.php โหลดสำเร็จ<br>";
    
    require_once 'app/services/ImportExportService.php';
    echo "✅ app/services/ImportExportService.php โหลดสำเร็จ<br>";
    
    require_once 'app/controllers/ImportExportController.php';
    echo "✅ app/controllers/ImportExportController.php โหลดสำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
    exit;
}

// 2. สร้าง Service และ Controller
echo "<h2>2. สร้าง Service และ Controller</h2>";
try {
    $service = new ImportExportService();
    echo "✅ ImportExportService สร้างสำเร็จ<br>";
    
    $controller = new ImportExportController();
    echo "✅ ImportExportController สร้างสำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Service/Controller Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
    exit;
}

// 3. สร้างไฟล์ CSV ทดสอบ
echo "<h2>3. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'debug_import_test.csv';
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

// 4. ทดสอบการ import ผ่าน Service แบบ step by step
echo "<h2>4. ทดสอบการ import ผ่าน Service แบบ step by step</h2>";

try {
    echo "🔍 เริ่มการ debug importSalesFromCSV...<br>";
    
    // เรียกใช้ method โดยตรงและดู error ที่เกิดขึ้น
    $results = $service->importSalesFromCSV($testFile);
    
    echo "✅ Import ผ่าน Service สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Service Import Error: " . $e->getMessage() . "<br>";
    echo "Error Type: " . get_class($e) . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 5. ทดสอบการ import ผ่าน Controller แบบ step by step
echo "<h2>5. ทดสอบการ import ผ่าน Controller แบบ step by step</h2>";

try {
    echo "🔍 เริ่มการ debug importSales method...<br>";
    
    // สร้างไฟล์ upload จำลอง
    $uploadFile = $uploadDir . 'debug_controller_test.csv';
    copy($testFile, $uploadFile);
    
    // จำลอง $_FILES
    $_FILES['csv_file'] = [
        'name' => 'debug_controller_test.csv',
        'type' => 'text/csv',
        'tmp_name' => $uploadFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($uploadFile)
    ];
    
    // จำลอง $_SERVER
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // เรียกใช้ method โดยตรง
    $controllerResults = $controller->importSales();
    
    echo "✅ Import ผ่าน Controller สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($controllerResults, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Controller Import Error: " . $e->getMessage() . "<br>";
    echo "Error Type: " . get_class($e) . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 6. ตรวจสอบ memory usage
echo "<h2>6. ตรวจสอบ memory usage</h2>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Memory usage: " . memory_get_usage(true) . " bytes<br>";
echo "Peak memory usage: " . memory_get_peak_usage(true) . " bytes<br>";

// 7. ตรวจสอบ PHP settings
echo "<h2>7. ตรวจสอบ PHP settings</h2>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_input_vars: " . ini_get('max_input_vars') . "<br>";

// 8. ตรวจสอบ file permissions
echo "<h2>8. ตรวจสอบ file permissions</h2>";
echo "Upload directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";
echo "Test file readable: " . (is_readable($testFile) ? 'Yes' : 'No') . "<br>";
echo "Test file writable: " . (is_writable($testFile) ? 'Yes' : 'No') . "<br>";

// 9. ตรวจสอบ database connection
echo "<h2>9. ตรวจสอบ database connection</h2>";
try {
    $db = new Database();
    echo "✅ Database connection สำเร็จ<br>";
    
    // ทดสอบ query ง่ายๆ
    $result = $db->fetchOne("SELECT 1 as test");
    echo "✅ Database query test สำเร็จ<br>";
    
    // ตรวจสอบตารางที่จำเป็น
    $tables = ['customers', 'orders', 'customer_activities'];
    foreach ($tables as $table) {
        if ($db->tableExists($table)) {
            echo "✅ ตาราง {$table} มีอยู่<br>";
        } else {
            echo "❌ ตาราง {$table} ไม่มีอยู่<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 10. ทดสอบการอ่านไฟล์ CSV
echo "<h2>10. ทดสอบการอ่านไฟล์ CSV</h2>";
try {
    $content = file_get_contents($testFile);
    if ($content === false) {
        echo "❌ ไม่สามารถอ่านไฟล์ได้<br>";
    } else {
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
    }
} catch (Exception $e) {
    echo "❌ File Reading Error: " . $e->getMessage() . "<br>";
}

// 11. ทำความสะอาด
echo "<h2>11. ทำความสะอาด</h2>";
try {
    $filesToDelete = [$testFile, $uploadDir . 'debug_controller_test.csv'];
    foreach ($filesToDelete as $file) {
        if (file_exists($file)) {
            unlink($file);
            echo "✅ ลบไฟล์: " . basename($file) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Cleanup Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการ Debug</h2>";
echo "การ debug เสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบนเพื่อระบุจุดที่เกิดปัญหา<br>";
echo "หากพบ error ในขั้นตอนใด กรุณาแชร์ผลลัพธ์นั้นเพื่อการแก้ไขต่อไป<br>";
?> 