<?php
/**
 * Test Import CSV Debug - ทดสอบการแก้ไขปัญหา 500 error
 * ตรวจสอบทุกขั้นตอนของการ import CSV
 */

echo "<h1>🔧 Test Import CSV Debug - ทดสอบการแก้ไขปัญหา 500 error</h1>";

// 1. ตรวจสอบการโหลดไฟล์ที่จำเป็น
echo "<h2>1. ตรวจสอบการโหลดไฟล์ที่จำเป็น</h2>";
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
    exit;
}

// 2. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>2. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $db = new Database();
    echo "✅ Database connection สำเร็จ<br>";
    
    // ทดสอบ query ง่ายๆ
    $result = $db->fetchOne("SELECT 1 as test");
    echo "✅ Database query test สำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ตรวจสอบการสร้าง Service และ Controller
echo "<h2>3. ตรวจสอบการสร้าง Service และ Controller</h2>";
try {
    $service = new ImportExportService();
    echo "✅ ImportExportService สร้างสำเร็จ<br>";
    
    $controller = new ImportExportController();
    echo "✅ ImportExportController สร้างสำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Service/Controller Error: " . $e->getMessage() . "<br>";
    exit;
}

// 4. ตรวจสอบโครงสร้างตาราง
echo "<h2>4. ตรวจสอบโครงสร้างตาราง</h2>";
try {
    $tables = ['customers', 'orders', 'customer_activities'];
    foreach ($tables as $table) {
        if ($db->tableExists($table)) {
            echo "✅ ตาราง {$table} มีอยู่<br>";
        } else {
            echo "❌ ตาราง {$table} ไม่มีอยู่<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Table Check Error: " . $e->getMessage() . "<br>";
}

// 5. สร้างไฟล์ CSV ทดสอบ
echo "<h2>5. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_import_debug.csv';
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

// 6. ทดสอบการ import ผ่าน Service โดยตรง
echo "<h2>6. ทดสอบการ import ผ่าน Service โดยตรง</h2>";
try {
    echo "เริ่มการ import ผ่าน Service...<br>";
    
    $results = $service->importSalesFromCSV($testFile);
    
    echo "✅ Import ผ่าน Service สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Service Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 7. ทดสอบการ import ผ่าน Controller
echo "<h2>7. ทดสอบการ import ผ่าน Controller</h2>";
try {
    echo "เริ่มการ import ผ่าน Controller...<br>";
    
    // สร้างไฟล์ upload จำลอง
    $uploadFile = $uploadDir . 'test_controller_upload.csv';
    copy($testFile, $uploadFile);
    
    // จำลอง $_FILES
    $_FILES['csv_file'] = [
        'name' => 'test_controller_upload.csv',
        'type' => 'text/csv',
        'tmp_name' => $uploadFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($uploadFile)
    ];
    
    $controllerResults = $controller->importSales();
    
    echo "✅ Import ผ่าน Controller สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($controllerResults, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Controller Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 8. ทดสอบการ import ผ่าน import-export.php
echo "<h2>8. ทดสอบการ import ผ่าน import-export.php</h2>";
try {
    echo "เริ่มการ import ผ่าน import-export.php...<br>";
    
    // จำลอง POST request
    $_POST['action'] = 'importSales';
    $_FILES['csv_file'] = [
        'name' => 'test_import_export.csv',
        'type' => 'text/csv',
        'tmp_name' => $testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($testFile)
    ];
    
    // เก็บ output buffer
    ob_start();
    
    // Include import-export.php
    include 'import-export.php';
    
    $output = ob_get_clean();
    
    echo "✅ Import ผ่าน import-export.php สำเร็จ<br>";
    echo "Output: <pre>" . htmlspecialchars($output) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ import-export.php Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 9. ตรวจสอบ HTTP headers
echo "<h2>9. ตรวจสอบ HTTP headers</h2>";
$headers = headers_list();
echo "Headers ที่ส่งออก:<br>";
foreach ($headers as $header) {
    echo "- " . htmlspecialchars($header) . "<br>";
}

// 10. ตรวจสอบ PHP settings
echo "<h2>10. ตรวจสอบ PHP settings</h2>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "error_reporting: " . ini_get('error_reporting') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// 11. ตรวจสอบ file permissions
echo "<h2>11. ตรวจสอบ file permissions</h2>";
echo "Upload directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";
echo "Test file readable: " . (is_readable($testFile) ? 'Yes' : 'No') . "<br>";

// 12. ทำความสะอาด
echo "<h2>12. ทำความสะอาด</h2>";
try {
    $filesToDelete = [$testFile, $uploadDir . 'test_controller_upload.csv'];
    foreach ($filesToDelete as $file) {
        if (file_exists($file)) {
            unlink($file);
            echo "✅ ลบไฟล์: " . basename($file) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Cleanup Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการทดสอบ</h2>";
echo "การทดสอบการแก้ไขปัญหา 500 error เสร็จสิ้นแล้ว<br>";
echo "หากไม่พบ error แสดงว่าการแก้ไขสำเร็จแล้ว! 🚀<br>";
?> 