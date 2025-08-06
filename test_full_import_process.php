<?php
/**
 * Test Full Import Process - ทดสอบกระบวนการ import แบบครบถ้วน
 */

echo "<h1>🚀 Test Full Import Process - ทดสอบกระบวนการ import แบบครบถ้วน</h1>";

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. โหลดไฟล์ที่จำเป็น
echo "<h2>1. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/ImportExportService.php';
    require_once 'app/controllers/ImportExportController.php';
    echo "✅ โหลดไฟล์ที่จำเป็นสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. สร้าง Service, Database และ Controller
echo "<h2>2. สร้าง Service, Database และ Controller</h2>";
try {
    $service = new ImportExportService();
    $db = new Database();
    $controller = new ImportExportController();
    echo "✅ สร้าง Service, Database และ Controller สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error creating objects: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>3. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $customers = $db->fetchAll("SELECT COUNT(*) as count FROM customers");
    echo "✅ Database connection สำเร็จ<br>";
    echo "✅ จำนวนลูกค้าในระบบ: " . $customers[0]['count'] . " รายการ<br>";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    exit;
}

// 4. ทดสอบการตรวจสอบตาราง
echo "<h2>4. ทดสอบการตรวจสอบตาราง</h2>";
try {
    $tables = ['customers', 'orders', 'customer_activities'];
    foreach ($tables as $table) {
        // ใช้ Database class แทน service เพราะ tableExists เป็น private
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
    
    $testFile = $uploadDir . 'test_full_import.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,ระบบ,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบระบบ\n";
    $csvContent .= "ทดสอบ2,ระบบ2,0812345679,test2@example.com,456 ถ.ทดสอบ2,เขตทดสอบ2,จังหวัดทดสอบ2,10001,สินค้าทดสอบ2,2,1500,2024-01-16,ทดสอบระบบ2\n";
    
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

// 9. ตรวจสอบข้อมูลที่ import เข้าไป
echo "<h2>9. ตรวจสอบข้อมูลที่ import เข้าไป</h2>";
try {
    // ตรวจสอบลูกค้าที่เพิ่มใหม่
    $newCustomers = $db->fetchAll("SELECT * FROM customers WHERE phone IN ('0812345678', '0812345679') ORDER BY created_at DESC LIMIT 5");
    echo "✅ ตรวจสอบลูกค้าที่เพิ่มใหม่: " . count($newCustomers) . " รายการ<br>";
    
    // ตรวจสอบออเดอร์ที่เพิ่มใหม่
    $newOrders = $db->fetchAll("SELECT * FROM orders WHERE customer_id IN (SELECT customer_id FROM customers WHERE phone IN ('0812345678', '0812345679')) ORDER BY created_at DESC LIMIT 5");
    echo "✅ ตรวจสอบออเดอร์ที่เพิ่มใหม่: " . count($newOrders) . " รายการ<br>";
    
} catch (Exception $e) {
    echo "❌ Data Check Error: " . $e->getMessage() . "<br>";
}

// 10. ทำความสะอาด
echo "<h2>10. ทำความสะอาด</h2>";
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
echo "การทดสอบกระบวนการ import แบบครบถ้วนเสร็จสิ้นแล้ว<br>";
echo "หากไม่พบ error แสดงว่าระบบ import ทำงานได้ปกติแล้ว! 🚀<br>";
?> 