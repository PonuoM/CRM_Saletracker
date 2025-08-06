<?php
/**
 * Test Page Display - ทดสอบการแสดงผลหน้าเว็บ
 */

// Start session
session_start();

// Set a dummy session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['role'] = 'super_admin';

// Load configuration
require_once 'config/config.php';

echo "<h1>🧪 Test Page Display - ทดสอบการแสดงผลหน้าเว็บ</h1>";

try {
    echo "<h2>1. ตรวจสอบการโหลด Controller</h2>";
    
    // Load controller
    require_once 'app/controllers/ImportExportController.php';
    $controller = new ImportExportController();
    echo "✅ ImportExportController สร้างสำเร็จ<br>";
    
    echo "<h2>2. ทดสอบการเรียกใช้ index method</h2>";
    
    // Test index method
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    echo "✅ index method ทำงานได้<br>";
    echo "Output length: " . strlen($output) . " characters<br>";
    
    if (strlen($output) > 1000) {
        echo "✅ Output มีขนาดใหญ่ (น่าจะเป็น HTML)<br>";
    } else {
        echo "❌ Output มีขนาดเล็ก (อาจมีปัญหา)<br>";
        echo "Output preview: " . htmlspecialchars(substr($output, 0, 200)) . "...<br>";
    }
    
    echo "<h2>3. ตรวจสอบไฟล์ Components</h2>";
    
    // Check component files
    $components = [
        'app/views/components/sidebar.php',
        'app/views/components/header.php',
        'app/views/import-export/index.php'
    ];
    
    foreach ($components as $component) {
        if (file_exists($component)) {
            echo "✅ {$component} - มีอยู่<br>";
        } else {
            echo "❌ {$component} - ไม่มีอยู่<br>";
        }
    }
    
    echo "<h2>4. ทดสอบการแสดงผลหน้าเว็บ</h2>";
    
    // Test direct access to import-export.php
    $_GET['action'] = 'index';
    
    ob_start();
    include 'import-export.php';
    $pageOutput = ob_get_clean();
    
    echo "✅ import-export.php ทำงานได้<br>";
    echo "Page output length: " . strlen($pageOutput) . " characters<br>";
    
    if (strlen($pageOutput) > 1000) {
        echo "✅ Page output มีขนาดใหญ่ (น่าจะเป็น HTML)<br>";
        echo "✅ หน้าเว็บควรแสดงผลปกติ<br>";
    } else {
        echo "❌ Page output มีขนาดเล็ก (อาจมีปัญหา)<br>";
        echo "Output preview: " . htmlspecialchars(substr($pageOutput, 0, 200)) . "...<br>";
    }
    
    echo "<h2>5. ตรวจสอบ Content-Type</h2>";
    
    // Check if headers were sent
    if (headers_sent()) {
        echo "❌ Headers already sent<br>";
    } else {
        echo "✅ Headers not sent yet (สามารถตั้งค่าได้)<br>";
    }
    
    echo "<h2>✅ สรุปผลการทดสอบ</h2>";
    echo "การทดสอบเสร็จสิ้นแล้ว หากหน้าเว็บยังแสดงเป็นตัวหนังสือ ให้ตรวจสอบ:<br>";
    echo "1. การตั้งค่า Content-Type<br>";
    echo "2. การ include ไฟล์ components<br>";
    echo "3. การตั้งค่า session<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาดในการทดสอบ</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 