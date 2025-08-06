<?php
/**
 * Test Import CSV - ทดสอบการ import ไฟล์ CSV
 */

// Start session
session_start();

// Set a dummy session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['role'] = 'super_admin';

// Load configuration
require_once 'config/config.php';

echo "<h1>🧪 Test Import CSV - ทดสอบการ import ไฟล์ CSV</h1>";

try {
    echo "<h2>1. ตรวจสอบการโหลด Controller</h2>";
    
    // Load controller
    require_once 'app/controllers/ImportExportController.php';
    $controller = new ImportExportController();
    echo "✅ ImportExportController สร้างสำเร็จ<br>";
    
    echo "<h2>2. ตรวจสอบการโหลด Service</h2>";
    
    // Load service
    require_once 'app/services/ImportExportService.php';
    $service = new ImportExportService();
    echo "✅ ImportExportService สร้างสำเร็จ<br>";
    
    echo "<h2>3. ทดสอบการสร้างไฟล์ CSV จำลอง</h2>";
    
    // Create test CSV content
    $testCsvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,สถานะ,อุณหภูมิ,เกรด,หมายเหตุ,ชื่อสินค้า,จำนวน,ราคา,วันที่ขาย\n";
    $testCsvContent .= "สมชาย,ใจดี,0812345678,somchai@example.com,123 ถ.สุขุมวิท,คลองเตย,กรุงเทพฯ,10110,new,cold,C,ลูกค้าใหม่,สินค้าทดสอบ,1,1000,2024-01-15\n";
    $testCsvContent .= "สมหญิง,รักดี,0898765432,somying@example.com,456 ถ.รัชดาภิเษก,ดินแดง,กรุงเทพฯ,10400,new,warm,B,ลูกค้าใหม่,สินค้าทดสอบ2,2,2000,2024-01-16\n";
    
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_sales_import.csv';
    file_put_contents($testFile, $testCsvContent);
    
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
    echo "เนื้อหา: <pre>" . htmlspecialchars($testCsvContent) . "</pre>";
    
    echo "<h2>4. ทดสอบการ import ไฟล์ CSV โดยตรง</h2>";
    
    // Test direct import
    try {
        $results = $service->importSalesFromCSV($testFile);
        echo "✅ การ import ไฟล์ CSV สำเร็จ<br>";
        echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre>";
    } catch (Exception $e) {
        echo "❌ การ import ไฟล์ CSV ล้มเหลว<br>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>5. ทดสอบการจำลอง POST Request</h2>";
    
    // Simulate POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_FILES['csv_file'] = [
        'name' => 'test_sales_import.csv',
        'type' => 'text/csv',
        'tmp_name' => $testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($testFile)
    ];
    
    echo "✅ จำลอง POST request สำเร็จ<br>";
    
    echo "<h2>6. ทดสอบการเรียกใช้ importSales method</h2>";
    
    // Test importSales method
    ob_start();
    $controller->importSales();
    $output = ob_get_clean();
    
    echo "✅ importSales method ทำงานได้<br>";
    echo "Output: " . htmlspecialchars($output) . "<br>";
    
    // Try to decode JSON
    $jsonData = json_decode($output, true);
    if ($jsonData) {
        echo "✅ Output เป็น JSON ที่ถูกต้อง<br>";
        echo "JSON Data: <pre>" . print_r($jsonData, true) . "</pre>";
    } else {
        echo "❌ Output ไม่ใช่ JSON ที่ถูกต้อง<br>";
        echo "JSON Error: " . json_last_error_msg() . "<br>";
    }
    
    echo "<h2>7. ทดสอบการเรียกใช้ import-export.php</h2>";
    
    // Test import-export.php
    $_GET['action'] = 'importSales';
    
    ob_start();
    include 'import-export.php';
    $pageOutput = ob_get_clean();
    
    echo "✅ import-export.php ทำงานได้<br>";
    echo "Output: " . htmlspecialchars($pageOutput) . "<br>";
    
    // Try to decode JSON
    $jsonData2 = json_decode($pageOutput, true);
    if ($jsonData2) {
        echo "✅ Page Output เป็น JSON ที่ถูกต้อง<br>";
        echo "JSON Data: <pre>" . print_r($jsonData2, true) . "</pre>";
    } else {
        echo "❌ Page Output ไม่ใช่ JSON ที่ถูกต้อง<br>";
        echo "JSON Error: " . json_last_error_msg() . "<br>";
    }
    
    echo "<h2>8. ตรวจสอบการตั้งค่า HTTP Headers</h2>";
    
    // Check headers
    if (headers_sent()) {
        echo "❌ Headers already sent<br>";
    } else {
        echo "✅ Headers not sent yet<br>";
    }
    
    // Clean up test file
    if (file_exists($testFile)) {
        unlink($testFile);
        echo "✅ ลบไฟล์ทดสอบแล้ว<br>";
    }
    
    echo "<h2>✅ สรุปผลการทดสอบ</h2>";
    echo "การทดสอบเสร็จสิ้นแล้ว หากยังมีปัญหา 500 error ให้ตรวจสอบ:<br>";
    echo "1. การตั้งค่า HTTP Headers<br>";
    echo "2. การจัดการไฟล์ CSV<br>";
    echo "3. การเชื่อมต่อฐานข้อมูล<br>";
    echo "4. Error Log ของเซิร์ฟเวอร์<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาดในการทดสอบ</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 