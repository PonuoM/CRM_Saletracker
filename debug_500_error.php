<?php
/**
 * Debug 500 Error - ทดสอบปัญหา 500 error ในสภาพแวดล้อมจริง
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load configuration
require_once 'config/config.php';

echo "<h1>🔍 Debug 500 Error - ตรวจสอบปัญหาในสภาพแวดล้อมจริง</h1>";

try {
    echo "<h2>1. ตรวจสอบการโหลดไฟล์</h2>";
    
    // Test 1: Check if files exist
    $files = [
        'config/config.php',
        'app/controllers/ImportExportController.php',
        'app/services/ImportExportService.php',
        'app/core/Database.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "✅ {$file} - มีอยู่<br>";
        } else {
            echo "❌ {$file} - ไม่มีอยู่<br>";
        }
    }
    
    echo "<h2>2. ตรวจสอบการสร้าง ImportExportService</h2>";
    
    // Test 2: Create ImportExportService
    try {
        require_once 'app/services/ImportExportService.php';
        $service = new ImportExportService();
        echo "✅ ImportExportService สร้างสำเร็จ<br>";
        
        // Test database connection
        $db = $service->getDatabase();
        if ($db) {
            echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        } else {
            echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ ImportExportService Error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>3. ตรวจสอบการสร้าง ImportExportController</h2>";
    
    // Test 3: Create ImportExportController
    try {
        require_once 'app/controllers/ImportExportController.php';
        $controller = new ImportExportController();
        echo "✅ ImportExportController สร้างสำเร็จ<br>";
    } catch (Exception $e) {
        echo "❌ ImportExportController Error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>4. ตรวจสอบสิทธิ์ไฟล์และโฟลเดอร์</h2>";
    
    // Test 4: Check file permissions
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "✅ สร้างโฟลเดอร์ uploads สำเร็จ<br>";
        } else {
            echo "❌ ไม่สามารถสร้างโฟลเดอร์ uploads ได้<br>";
        }
    } else {
        echo "✅ โฟลเดอร์ uploads มีอยู่แล้ว<br>";
    }
    
    // Test write permissions
    $testFile = $uploadDir . 'debug_test.txt';
    if (file_put_contents($testFile, 'test') !== false) {
        echo "✅ สามารถเขียนไฟล์ได้<br>";
        unlink($testFile);
    } else {
        echo "❌ ไม่สามารถเขียนไฟล์ได้<br>";
    }
    
    echo "<h2>5. ตรวจสอบการตั้งค่า PHP</h2>";
    
    // Test 5: Check PHP settings
    echo "PHP Version: " . phpversion() . "<br>";
    echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
    echo "Max Upload Size: " . ini_get('upload_max_filesize') . "<br>";
    echo "Max Post Size: " . ini_get('post_max_size') . "<br>";
    echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
    echo "Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "<br>";
    echo "Log Errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "<br>";
    
    echo "<h2>6. ตรวจสอบ Session</h2>";
    
    // Test 6: Check session
    if (isset($_SESSION['user_id'])) {
        echo "✅ Session user_id: " . $_SESSION['user_id'] . "<br>";
    } else {
        echo "❌ ไม่มี Session user_id<br>";
        echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    }
    
    echo "<h2>7. ตรวจสอบการเรียกใช้ importSales method</h2>";
    
    // Test 7: Test importSales method (without file upload)
    try {
        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Test without file
        ob_start();
        $controller->importSales();
        $output = ob_get_clean();
        
        echo "✅ importSales method ทำงานได้ (ไม่มีไฟล์)<br>";
        echo "Output: " . htmlspecialchars($output) . "<br>";
        
    } catch (Exception $e) {
        echo "❌ importSales Error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>8. ตรวจสอบ Error Log</h2>";
    
    // Test 8: Check error log
    $errorLog = ini_get('error_log');
    if ($errorLog) {
        echo "Error Log Path: {$errorLog}<br>";
        if (file_exists($errorLog)) {
            echo "✅ Error Log ไฟล์มีอยู่<br>";
            $logSize = filesize($errorLog);
            echo "Error Log Size: " . number_format($logSize) . " bytes<br>";
            
            if ($logSize > 0) {
                echo "Last 10 lines of error log:<br>";
                $lines = file($errorLog);
                $lastLines = array_slice($lines, -10);
                echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
            }
        } else {
            echo "❌ Error Log ไฟล์ไม่มีอยู่<br>";
        }
    } else {
        echo "❌ ไม่มีการตั้งค่า Error Log<br>";
    }
    
    echo "<h2>✅ สรุปผลการทดสอบ</h2>";
    echo "การทดสอบเสร็จสิ้นแล้ว หากยังมีปัญหา 500 error ให้ตรวจสอบ:<br>";
    echo "1. Error Log ของเซิร์ฟเวอร์<br>";
    echo "2. การตั้งค่า PHP ในเซิร์ฟเวอร์<br>";
    echo "3. สิทธิ์การเข้าถึงไฟล์และโฟลเดอร์<br>";
    echo "4. การเชื่อมต่อฐานข้อมูล<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาดในการทดสอบ</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 