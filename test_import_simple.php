<?php
/**
 * Test Import Simple - ทดสอบการ import ไฟล์ CSV แบบเรียบง่าย
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

echo "<h1>🧪 Test Import Simple - ทดสอบการ import ไฟล์ CSV แบบเรียบง่าย</h1>";

// 1. ตรวจสอบการโหลดไฟล์ที่จำเป็น
echo "<h2>1. ตรวจสอบการโหลดไฟล์ที่จำเป็น</h2>";

$requiredFiles = [
    'config/config.php',
    'app/core/Database.php',
    'app/services/ImportExportService.php',
    'app/controllers/ImportExportController.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} - มีอยู่<br>";
    } else {
        echo "❌ {$file} - ไม่มีอยู่<br>";
    }
}

// 2. ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>2. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    require_once 'app/core/Database.php';
    $db = new Database();
    echo "✅ Database connection สำเร็จ<br>";
    
    // ทดสอบ query ง่ายๆ
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM customers");
    echo "✅ Database query สำเร็จ: " . $result['count'] . " ลูกค้า<br>";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// 3. ทดสอบการสร้างไฟล์ CSV จำลอง
echo "<h2>3. ทดสอบการสร้างไฟล์ CSV จำลอง</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_simple.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,ระบบ,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบระบบ\n";
    
    file_put_contents($testFile, $csvContent);
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
} catch (Exception $e) {
    echo "❌ CSV Creation Error: " . $e->getMessage() . "<br>";
}

// 4. ทดสอบการ import ผ่าน Service โดยตรง
echo "<h2>4. ทดสอบการ import ผ่าน Service โดยตรง</h2>";
try {
    require_once 'app/services/ImportExportService.php';
    $service = new ImportExportService();
    
    $results = $service->importSalesFromCSV($testFile);
    echo "✅ Import ผ่าน Service สำเร็จ<br>";
    echo "ผลลัพธ์: " . json_encode($results, JSON_UNESCAPED_UNICODE) . "<br>";
} catch (Exception $e) {
    echo "❌ Service Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// 5. ทดสอบการ import ผ่าน Controller
echo "<h2>5. ทดสอบการ import ผ่าน Controller</h2>";
try {
    require_once 'app/controllers/ImportExportController.php';
    $controller = new ImportExportController();
    
    // จำลอง $_FILES
    $_FILES['csv_file'] = [
        'name' => 'test_simple.csv',
        'type' => 'text/csv',
        'tmp_name' => $testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($testFile)
    ];
    
    // จำลอง $_SERVER
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // เรียกใช้ importSales method
    ob_start();
    $controller->importSales();
    $output = ob_get_clean();
    
    echo "✅ Import ผ่าน Controller สำเร็จ<br>";
    echo "Output: " . $output . "<br>";
} catch (Exception $e) {
    echo "❌ Controller Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// 6. ทดสอบการเรียกใช้ import-export.php
echo "<h2>6. ทดสอบการเรียกใช้ import-export.php</h2>";
try {
    // จำลอง $_GET
    $_GET['action'] = 'importSales';
    
    // จำลอง $_FILES
    $_FILES['csv_file'] = [
        'name' => 'test_simple.csv',
        'type' => 'text/csv',
        'tmp_name' => $testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($testFile)
    ];
    
    // จำลอง $_SERVER
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // เรียกใช้ import-export.php
    ob_start();
    include 'import-export.php';
    $output = ob_get_clean();
    
    echo "✅ Import ผ่าน import-export.php สำเร็จ<br>";
    echo "Output: " . $output . "<br>";
} catch (Exception $e) {
    echo "❌ import-export.php Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// 7. ตรวจสอบ error log
echo "<h2>7. ตรวจสอบ error log</h2>";
$errorLog = ini_get('error_log');
if ($errorLog) {
    echo "Error log path: {$errorLog}<br>";
    if (file_exists($errorLog)) {
        echo "Error log file exists<br>";
        $logSize = filesize($errorLog);
        echo "Error log size: {$logSize} bytes<br>";
        
        if ($logSize > 0) {
            $recentLogs = file_get_contents($errorLog);
            if (strlen($recentLogs) > 1000) {
                $recentLogs = substr($recentLogs, -1000);
            }
            echo "Recent error logs:<br>";
            echo "<pre>" . htmlspecialchars($recentLogs) . "</pre>";
        }
    } else {
        echo "Error log file does not exist<br>";
    }
} else {
    echo "No error log configured<br>";
}

// 8. ทำความสะอาดไฟล์ทดสอบ
echo "<h2>8. ทำความสะอาดไฟล์ทดสอบ</h2>";
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
echo "หากพบ error ในขั้นตอนใด กรุณาแชร์ผลลัพธ์นั้นเพื่อการแก้ไขต่อไป<br>";
?> 