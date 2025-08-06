<?php
/**
 * Test API Simulation - ทดสอบการจำลองการเรียกใช้ API importSales
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load configuration
require_once 'config/config.php';

echo "<h1>🧪 Test API Simulation - ทดสอบการจำลอง API importSales</h1>";

try {
    echo "<h2>1. ตรวจสอบ Session</h2>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "❌ ไม่มี Session user_id - ต้องล็อกอินก่อน<br>";
        echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
        
        // Try to set a dummy session for testing
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'test_user';
        echo "✅ ตั้งค่า Session จำลองสำหรับการทดสอบ<br>";
    } else {
        echo "✅ มี Session user_id: " . $_SESSION['user_id'] . "<br>";
    }
    
    echo "<h2>2. ทดสอบการโหลด Controller</h2>";
    
    // Load controller
    require_once 'app/controllers/ImportExportController.php';
    $controller = new ImportExportController();
    echo "✅ ImportExportController สร้างสำเร็จ<br>";
    
    echo "<h2>3. ทดสอบการจำลอง POST Request</h2>";
    
    // Simulate POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Test 1: No file upload
    echo "<h3>3.1 ทดสอบไม่มีไฟล์อัปโหลด</h3>";
    ob_start();
    $controller->importSales();
    $output1 = ob_get_clean();
    
    echo "Output: " . htmlspecialchars($output1) . "<br>";
    
    // Test 2: Invalid file upload
    echo "<h3>3.2 ทดสอบไฟล์อัปโหลดไม่ถูกต้อง</h3>";
    $_FILES['csv_file'] = [
        'error' => UPLOAD_ERR_NO_FILE
    ];
    
    ob_start();
    $controller->importSales();
    $output2 = ob_get_clean();
    
    echo "Output: " . htmlspecialchars($output2) . "<br>";
    
    // Test 3: Create a test CSV file
    echo "<h3>3.3 ทดสอบสร้างไฟล์ CSV จำลอง</h3>";
    
    $testCsvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,สถานะ,อุณหภูมิ,เกรด,หมายเหตุ,ชื่อสินค้า,จำนวน,ราคา,วันที่ขาย\n";
    $testCsvContent .= "สมชาย,ใจดี,0812345678,somchai@example.com,123 ถ.สุขุมวิท,คลองเตย,กรุงเทพฯ,10110,new,cold,C,ลูกค้าใหม่,สินค้าทดสอบ,1,1000,2024-01-15\n";
    
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_sales.csv';
    file_put_contents($testFile, $testCsvContent);
    
    // Simulate file upload
    $_FILES['csv_file'] = [
        'name' => 'test_sales.csv',
        'type' => 'text/csv',
        'tmp_name' => $testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($testFile)
    ];
    
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
    
    echo "<h3>3.4 ทดสอบการ import ไฟล์ CSV</h3>";
    
    ob_start();
    $controller->importSales();
    $output3 = ob_get_clean();
    
    echo "Output: " . htmlspecialchars($output3) . "<br>";
    
    // Clean up test file
    if (file_exists($testFile)) {
        unlink($testFile);
        echo "✅ ลบไฟล์ทดสอบแล้ว<br>";
    }
    
    echo "<h2>4. ทดสอบการเรียกใช้ import-export.php โดยตรง</h2>";
    
    // Test 4: Direct call to import-export.php
    $_GET['action'] = 'importSales';
    
    ob_start();
    include 'import-export.php';
    $output4 = ob_get_clean();
    
    echo "Output: " . htmlspecialchars($output4) . "<br>";
    
    echo "<h2>5. ตรวจสอบการตั้งค่า HTTP Headers</h2>";
    
    // Test 5: Check HTTP headers
    echo "Content-Type: " . (headers_sent() ? 'Headers already sent' : 'Headers not sent yet') . "<br>";
    
    // Test setting headers
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        echo "✅ สามารถตั้งค่า HTTP Headers ได้<br>";
    } else {
        echo "❌ ไม่สามารถตั้งค่า HTTP Headers ได้ (headers already sent)<br>";
    }
    
    echo "<h2>6. ทดสอบการจำลอง JavaScript Fetch Request</h2>";
    
    // Test 6: Simulate JavaScript fetch request
    echo "<h3>6.1 สร้าง FormData จำลอง</h3>";
    
    // Create a simple test form
    echo '<form id="testForm" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" accept=".csv">';
    echo '<button type="button" onclick="testUpload()">ทดสอบอัปโหลด</button>';
    echo '</form>';
    
    echo '<div id="result"></div>';
    
    echo '<script>
    async function testUpload() {
        const form = document.getElementById("testForm");
        const formData = new FormData(form);
        
        try {
            const response = await fetch("import-export.php?action=importSales", {
                method: "POST",
                body: formData
            });
            
            const result = await response.text();
            document.getElementById("result").innerHTML = "<h4>Response:</h4><pre>" + result + "</pre>";
            
            if (!response.ok) {
                document.getElementById("result").innerHTML += "<h4>Error Status:</h4><p>HTTP " + response.status + " " + response.statusText + "</p>";
            }
        } catch (error) {
            document.getElementById("result").innerHTML = "<h4>Error:</h4><p>" + error.message + "</p>";
        }
    }
    </script>';
    
    echo "<h2>✅ สรุปผลการทดสอบ</h2>";
    echo "การทดสอบเสร็จสิ้นแล้ว หากยังมีปัญหา 500 error ให้ตรวจสอบ:<br>";
    echo "1. การตั้งค่า PHP ในเซิร์ฟเวอร์<br>";
    echo "2. สิทธิ์การเข้าถึงไฟล์และโฟลเดอร์<br>";
    echo "3. การเชื่อมต่อฐานข้อมูล<br>";
    echo "4. Error Log ของเซิร์ฟเวอร์<br>";
    echo "5. การตั้งค่า HTTP Headers<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาดในการทดสอบ</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 