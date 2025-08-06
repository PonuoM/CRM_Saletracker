<?php
/**
 * Test Basic - ทดสอบพื้นฐาน
 * ตรวจสอบว่าเซิร์ฟเวอร์ทำงานได้หรือไม่
 */

echo "<h1>🧪 Test Basic - ทดสอบพื้นฐาน</h1>";

// 1. ทดสอบ PHP
echo "<h2>1. ทดสอบ PHP</h2>";
echo "✅ PHP ทำงานได้ปกติ<br>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. ทดสอบการแสดงผล
echo "<h2>2. ทดสอบการแสดงผล</h2>";
echo "✅ การแสดงผลทำงานได้ปกติ<br>";

// 3. ทดสอบการโหลดไฟล์
echo "<h2>3. ทดสอบการโหลดไฟล์</h2>";
$files = [
    'config/config.php',
    'app/core/Database.php',
    'app/services/ImportExportService.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} - มีอยู่<br>";
    } else {
        echo "❌ {$file} - ไม่มีอยู่<br>";
    }
}

// 4. ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>4. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    echo "✅ Database connection สำเร็จ<br>";
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM customers");
    echo "✅ Database query สำเร็จ: " . $result['count'] . " ลูกค้า<br>";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการทดสอบ</h2>";
echo "การทดสอบพื้นฐานเสร็จสิ้นแล้ว<br>";
?> 