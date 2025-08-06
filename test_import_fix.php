<?php
/**
 * Test Import Fix - ทดสอบการแก้ไขปัญหา JSON response
 */

echo "<h1>🔧 Test Import Fix - ทดสอบการแก้ไขปัญหา JSON response</h1>";

// 1. ทดสอบการเรียก import-export.php โดยตรง
echo "<h2>1. ทดสอบการเรียก import-export.php โดยตรง</h2>";

// จำลอง POST request
$_POST['action'] = 'importSales';
$_FILES['csv_file'] = [
    'name' => 'test.csv',
    'type' => 'text/csv',
    'tmp_name' => __DIR__ . '/uploads/test_full_import.csv',
    'error' => UPLOAD_ERR_OK,
    'size' => 100
];

// เก็บ output buffer
ob_start();

// Include import-export.php
include 'import-export.php';

$output = ob_get_clean();

echo "✅ Output จาก import-export.php:<br>";
echo "<pre>" . htmlspecialchars($output) . "</pre><br>";

// 2. ตรวจสอบว่า output เป็น JSON หรือไม่
echo "<h2>2. ตรวจสอบว่า output เป็น JSON หรือไม่</h2>";

if (empty($output)) {
    echo "❌ ไม่มี output<br>";
} else {
    // ตรวจสอบว่าเริ่มต้นด้วย { หรือ [
    if (preg_match('/^[\s]*[{\[]/', $output)) {
        echo "✅ Output ดูเหมือนจะเป็น JSON<br>";
        
        // พยายาม decode JSON
        $json = json_decode($output, true);
        if ($json !== null) {
            echo "✅ JSON decode สำเร็จ<br>";
            echo "ผลลัพธ์: <pre>" . print_r($json, true) . "</pre><br>";
        } else {
            echo "❌ JSON decode ไม่สำเร็จ<br>";
            echo "JSON Error: " . json_last_error_msg() . "<br>";
        }
    } else {
        echo "❌ Output ไม่ใช่ JSON (อาจเป็น HTML)<br>";
        echo "Output เริ่มต้นด้วย: " . htmlspecialchars(substr($output, 0, 50)) . "<br>";
    }
}

// 3. ตรวจสอบ HTTP headers
echo "<h2>3. ตรวจสอบ HTTP headers</h2>";

$headers = headers_list();
echo "Headers ที่ส่งออก:<br>";
foreach ($headers as $header) {
    echo "- " . htmlspecialchars($header) . "<br>";
}

echo "<h2>🎯 สรุปการทดสอบ</h2>";
echo "หาก output เป็น JSON และไม่มี HTML แสดงว่าการแก้ไขสำเร็จแล้ว! 🚀<br>";
?> 