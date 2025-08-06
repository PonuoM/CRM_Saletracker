<?php
/**
 * Test Import After Fix - ทดสอบการ import หลังจากแก้ไขฐานข้อมูล
 * 
 * ใช้สำหรับทดสอบว่าระบบ import ทำงานได้ปกติหลังจากแก้ไขคอลัมน์ activity_date
 */

echo "<h1>🧪 Test Import After Fix - ทดสอบการ import หลังจากแก้ไขฐานข้อมูล</h1>";

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
    echo "✅ โหลดไฟล์สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. สร้าง Service และ Controller
echo "<h2>2. สร้าง Service และ Controller</h2>";
try {
    $service = new ImportExportService();
    $controller = new ImportExportController();
    echo "✅ สร้าง Service และ Controller สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error creating objects: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ตรวจสอบโครงสร้างตาราง customer_activities
echo "<h2>3. ตรวจสอบโครงสร้างตาราง customer_activities</h2>";
try {
    $db = new Database();
    $columns = $db->fetchAll("DESCRIBE customer_activities");
    
    echo "✅ โครงสร้างตาราง customer_activities:<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // ตรวจสอบว่ามีคอลัมน์ activity_date หรือไม่
    $hasActivityDate = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'activity_date') {
            $hasActivityDate = true;
            break;
        }
    }
    
    if ($hasActivityDate) {
        echo "✅ คอลัมน์ activity_date มีอยู่แล้ว - โครงสร้างฐานข้อมูลถูกต้อง<br>";
    } else {
        echo "❌ คอลัมน์ activity_date ยังไม่มีอยู่<br>";
        exit;
    }
    
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
    exit;
}

// 4. สร้างไฟล์ CSV ทดสอบ
echo "<h2>4. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_import_after_fix.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,หลังแก้ไข,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบหลังแก้ไข\n";
    
    file_put_contents($testFile, $csvContent);
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
} catch (Exception $e) {
    echo "❌ CSV Creation Error: " . $e->getMessage() . "<br>";
    exit;
}

// 5. ทดสอบการ import ผ่าน Service
echo "<h2>5. ทดสอบการ import ผ่าน Service</h2>";
try {
    echo "เริ่มการ import ผ่าน Service...<br>";
    
    $results = $service->importSalesFromCSV($testFile);
    
    echo "✅ Import ผ่าน Service สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Service Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 6. ทดสอบการ import ผ่าน Controller
echo "<h2>6. ทดสอบการ import ผ่าน Controller</h2>";
try {
    echo "เริ่มการ import ผ่าน Controller...<br>";
    
    // สร้างไฟล์ upload จำลอง
    $uploadFile = $uploadDir . 'test_controller_after_fix.csv';
    copy($testFile, $uploadFile);
    
    // จำลอง $_FILES
    $_FILES['csv_file'] = [
        'name' => 'test_controller_after_fix.csv',
        'type' => 'text/csv',
        'tmp_name' => $uploadFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($uploadFile)
    ];
    
    // จำลอง $_SERVER
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    $controllerResults = $controller->importSales();
    
    echo "✅ Import ผ่าน Controller สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($controllerResults, true) . "</pre><br>";
    
} catch (Exception $e) {
    echo "❌ Controller Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 7. ตรวจสอบข้อมูลที่ import เข้าไป
echo "<h2>7. ตรวจสอบข้อมูลที่ import เข้าไป</h2>";
try {
    // ตรวจสอบลูกค้าที่เพิ่มใหม่
    $newCustomers = $db->fetchAll("SELECT * FROM customers WHERE phone = '0812345678' ORDER BY created_at DESC LIMIT 5");
    echo "✅ ตรวจสอบลูกค้าที่เพิ่มใหม่: " . count($newCustomers) . " รายการ<br>";
    
    if (!empty($newCustomers)) {
        echo "ข้อมูลลูกค้าล่าสุด: <pre>" . print_r($newCustomers[0], true) . "</pre><br>";
    }
    
    // ตรวจสอบออเดอร์ที่เพิ่มใหม่
    $newOrders = $db->fetchAll("SELECT * FROM orders WHERE customer_id IN (SELECT customer_id FROM customers WHERE phone = '0812345678') ORDER BY created_at DESC LIMIT 5");
    echo "✅ ตรวจสอบออเดอร์ที่เพิ่มใหม่: " . count($newOrders) . " รายการ<br>";
    
    if (!empty($newOrders)) {
        echo "ข้อมูลออเดอร์ล่าสุด: <pre>" . print_r($newOrders[0], true) . "</pre><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Data Check Error: " . $e->getMessage() . "<br>";
}

// 8. ทำความสะอาด
echo "<h2>8. ทำความสะอาด</h2>";
try {
    $filesToDelete = [$testFile, $uploadDir . 'test_controller_after_fix.csv'];
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
echo "การทดสอบการ import หลังจากแก้ไขฐานข้อมูลเสร็จสิ้นแล้ว<br>";
echo "หากไม่พบ error แสดงว่าระบบ import ทำงานได้ปกติแล้ว! 🚀<br>";
echo "ตอนนี้คุณสามารถใช้ระบบ Import CSV ได้ตามปกติ<br>";
?> 