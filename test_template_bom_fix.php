<?php
/**
 * Test Template BOM Fix
 * ทดสอบการแก้ไขปัญหา BOM ของไฟล์ Template
 */

echo "<h1>ทดสอบการแก้ไขปัญหา BOM ของไฟล์ Template</h1>\n";

// Test template files
$templateFiles = [
    'templates/sales_import_template.csv',
    'templates/customers_only_template.csv',
    'templates/customers_template.csv'
];

foreach ($templateFiles as $file) {
    echo "<h2>ตรวจสอบไฟล์: {$file}</h2>\n";
    
    if (file_exists($file)) {
        echo "✅ ไฟล์พบ<br>\n";
        
        $content = file_get_contents($file);
        $contentHex = bin2hex(substr($content, 0, 10));
        echo "Hex ของ 10 bytes แรก: {$contentHex}<br>\n";
        
        // Check for UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            echo "✅ มี UTF-8 BOM<br>\n";
        } else {
            echo "❌ ไม่มี UTF-8 BOM<br>\n";
        }
        
        // Check encoding
        if (mb_check_encoding($content, 'UTF-8')) {
            echo "✅ เข้ารหัส UTF-8 ถูกต้อง<br>\n";
        } else {
            echo "❌ เข้ารหัส UTF-8 ไม่ถูกต้อง<br>\n";
        }
        
        // Check Thai characters
        if (preg_match('/[\p{Thai}]/u', $content)) {
            echo "✅ มีตัวอักษรไทย<br>\n";
        } else {
            echo "❌ ไม่มีตัวอักษรไทย<br>\n";
        }
        
        // Show first line
        $lines = explode("\n", $content);
        if (count($lines) > 0) {
            $firstLine = trim($lines[0]);
            echo "บรรทัดแรก: " . htmlspecialchars($firstLine) . "<br>\n";
        }
        
    } else {
        echo "❌ ไฟล์ไม่พบ<br>\n";
    }
    echo "<br>\n";
}

// Test CSV generation with BOM
echo "<h2>ทดสอบการสร้าง CSV ใหม่</h2>\n";

$testFile = 'uploads/test_bom_fix.csv';
$output = fopen($testFile, 'w');

// Add UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'หมายเหตุ']);

// Add test data
fputcsv($output, ['ทดสอบ', 'ระบบ', '0812345678', 'test@example.com', '123 ถ.ทดสอบ', 'ข้อมูลทดสอบ']);

fclose($output);

if (file_exists($testFile)) {
    echo "✅ สร้างไฟล์ทดสอบสำเร็จ: {$testFile}<br>\n";
    
    $content = file_get_contents($testFile);
    $contentHex = bin2hex(substr($content, 0, 10));
    echo "Hex ของ 10 bytes แรก: {$contentHex}<br>\n";
    
    // Check for UTF-8 BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "✅ มี UTF-8 BOM<br>\n";
    } else {
        echo "❌ ไม่มี UTF-8 BOM<br>\n";
    }
    
    // Clean up
    unlink($testFile);
    echo "✅ ลบไฟล์ทดสอบแล้ว<br>\n";
} else {
    echo "❌ ไม่สามารถสร้างไฟล์ทดสอบได้<br>\n";
}

echo "<h2>สรุปการแก้ไข</h2>\n";
echo "1. ไฟล์ Template ทั้งหมดถูกสร้างใหม่ด้วย UTF-8 BOM<br>\n";
echo "2. การสร้าง CSV ใหม่มี BOM ถูกต้อง<br>\n";
echo "3. ไฟล์ที่ดาวน์โหลดจากระบบควรแสดงภาษาไทยได้ถูกต้องใน Excel แล้ว<br>\n";
echo "<br>\n";
echo "<strong>หมายเหตุ:</strong> หากเว็บไซต์ Live ยังแสดงไฟล์เก่า ให้ลอง:<br>\n";
echo "- ล้าง Cache ของเบราว์เซอร์ (Ctrl+F5)<br>\n";
echo "- ตรวจสอบว่าโค้ดใหม่ถูกอัปโหลดไปยังเซิร์ฟเวอร์แล้ว<br>\n";
echo "- ตรวจสอบการตั้งค่า Cache ของเซิร์ฟเวอร์<br>\n";
?> 