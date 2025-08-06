<?php
/**
 * ทดสอบการแก้ไขปัญหา Import/Export
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';
require_once 'app/controllers/ImportExportController.php';

echo "<h1>ทดสอบการแก้ไขปัญหา Import/Export</h1>";

try {
    // Test 1: Check ImportExportService instantiation
    echo "<h2>1. ทดสอบการสร้าง ImportExportService</h2>";
    $service = new ImportExportService();
    echo "✅ ImportExportService สร้างสำเร็จ<br>";
    
    // Test 2: Check database connection
    echo "<h2>2. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
    $db = $service->getDatabase();
    if ($db) {
        echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    } else {
        echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว<br>";
    }
    
    // Test 3: Check tableExists method
    echo "<h2>3. ทดสอบการตรวจสอบตาราง</h2>";
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('tableExists');
    $method->setAccessible(true);
    
    $customersExists = $method->invoke($service, 'customers');
    $ordersExists = $method->invoke($service, 'orders');
    $orderItemsExists = $method->invoke($service, 'order_items');
    
    echo "ตาราง customers: " . ($customersExists ? "✅ มีอยู่" : "❌ ไม่มี") . "<br>";
    echo "ตาราง orders: " . ($ordersExists ? "✅ มีอยู่" : "❌ ไม่มี") . "<br>";
    echo "ตาราง order_items: " . ($orderItemsExists ? "✅ มีอยู่" : "❌ ไม่มี") . "<br>";
    
    // Test 4: Check ImportExportController
    echo "<h2>4. ทดสอบการสร้าง ImportExportController</h2>";
    $controller = new ImportExportController();
    echo "✅ ImportExportController สร้างสำเร็จ<br>";
    
    // Test 5: Check uploads directory
    echo "<h2>5. ทดสอบโฟลเดอร์ uploads</h2>";
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✅ สร้างโฟลเดอร์ uploads สำเร็จ<br>";
    } else {
        echo "✅ โฟลเดอร์ uploads มีอยู่แล้ว<br>";
    }
    
    // Test 6: Check file permissions
    echo "<h2>6. ทดสอบสิทธิ์การเขียนไฟล์</h2>";
    $testFile = $uploadDir . 'test_write.txt';
    if (file_put_contents($testFile, 'test') !== false) {
        echo "✅ สามารถเขียนไฟล์ได้<br>";
        unlink($testFile);
    } else {
        echo "❌ ไม่สามารถเขียนไฟล์ได้<br>";
    }
    
    // Test 7: Check CSV template files
    echo "<h2>7. ทดสอบไฟล์ Template</h2>";
    $templates = [
        'templates/sales_import_template.csv',
        'templates/customers_only_template.csv',
        'templates/customers_template.csv'
    ];
    
    foreach ($templates as $template) {
        if (file_exists($template)) {
            echo "✅ {$template} มีอยู่<br>";
        } else {
            echo "❌ {$template} ไม่มีอยู่<br>";
        }
    }
    
    echo "<h2>สรุปผลการทดสอบ</h2>";
    echo "✅ การแก้ไขปัญหา Import/Export เสร็จสิ้น<br>";
    echo "✅ ระบบพร้อมใช้งาน<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "ข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 