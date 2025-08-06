<?php
/**
 * Test Schema Fix
 * ทดสอบการแก้ไขปัญหา Schema และ BOM ของไฟล์ Template
 */

echo "<h1>ทดสอบการแก้ไขปัญหา Schema และ BOM ของไฟล์ Template</h1>\n";

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
        
        // Show first line and check columns
        $lines = explode("\n", $content);
        if (count($lines) > 0) {
            $firstLine = trim($lines[0]);
            echo "บรรทัดแรก: " . htmlspecialchars($firstLine) . "<br>\n";
            
            // Check for correct column names
            $columns = str_getcsv($firstLine);
            echo "คอลัมน์ที่พบ: " . implode(', ', $columns) . "<br>\n";
            
            // Check for problematic columns
            $hasTambon = in_array('ตำบล', $columns);
            $hasAmphur = in_array('อำเภอ', $columns);
            $hasKhet = in_array('เขต', $columns);
            $hasProvince = in_array('จังหวัด', $columns);
            
            if ($hasTambon) {
                echo "❌ พบคอลัมน์ 'ตำบล' (ไม่ควรมี)<br>\n";
            }
            if ($hasAmphur) {
                echo "❌ พบคอลัมน์ 'อำเภอ' (ไม่ควรมี)<br>\n";
            }
            if ($hasKhet) {
                echo "✅ พบคอลัมน์ 'เขต' (ถูกต้อง)<br>\n";
            }
            if ($hasProvince) {
                echo "✅ พบคอลัมน์ 'จังหวัด' (ถูกต้อง)<br>\n";
            }
        }
        
    } else {
        echo "❌ ไฟล์ไม่พบ<br>\n";
    }
    echo "<br>\n";
}

// Test database schema
echo "<h2>ตรวจสอบ Database Schema</h2>\n";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>\n";
    
    // Check customers table structure
    $sql = "DESCRIBE customers";
    $columns = $db->fetchAll($sql);
    
    echo "<h3>โครงสร้างตาราง customers:</h3>\n";
    $foundColumns = [];
    foreach ($columns as $column) {
        $foundColumns[] = $column['Field'];
        echo "  - {$column['Field']} ({$column['Type']})<br>\n";
    }
    
    // Check for required columns
    $requiredColumns = ['customer_id', 'first_name', 'last_name', 'phone', 'email', 'address', 'district', 'province', 'postal_code'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $required) {
        if (!in_array($required, $foundColumns)) {
            $missingColumns[] = $required;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✅ คอลัมน์ที่จำเป็นครบถ้วน<br>\n";
    } else {
        echo "❌ คอลัมน์ที่ขาดหาย: " . implode(', ', $missingColumns) . "<br>\n";
    }
    
    // Check orders table structure
    $sql = "DESCRIBE orders";
    $columns = $db->fetchAll($sql);
    
    echo "<h3>โครงสร้างตาราง orders:</h3>\n";
    $foundOrderColumns = [];
    foreach ($columns as $column) {
        $foundOrderColumns[] = $column['Field'];
        echo "  - {$column['Field']} ({$column['Type']})<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage() . "<br>\n";
}

// Test CSV generation with correct schema
echo "<h2>ทดสอบการสร้าง CSV ด้วย Schema ที่ถูกต้อง</h2>\n";

$testFile = 'uploads/test_schema_fix.csv';
$output = fopen($testFile, 'w');

// Add UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers with correct column names
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'หมายเหตุ']);

// Add test data
fputcsv($output, ['ทดสอบ', 'ระบบ', '0812345678', 'test@example.com', '123 ถ.ทดสอบ', 'คลองเตย', 'กรุงเทพฯ', '10110', 'ข้อมูลทดสอบ']);

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
echo "2. คอลัมน์ถูกแก้ไขให้ตรงกับ Schema จริง:<br>\n";
echo "   - เปลี่ยนจาก 'ตำบล' เป็น 'เขต' (district)<br>\n";
echo "   - เปลี่ยนจาก 'อำเภอ' เป็น 'จังหวัด' (province)<br>\n";
echo "3. การสร้าง CSV ใหม่มี BOM และ Schema ถูกต้อง<br>\n";
echo "4. ไฟล์ที่ดาวน์โหลดจากระบบควรแสดงภาษาไทยได้ถูกต้องใน Excel แล้ว<br>\n";
echo "<br>\n";
echo "<strong>หมายเหตุ:</strong> หากเว็บไซต์ Live ยังแสดงไฟล์เก่า ให้ลอง:<br>\n";
echo "- ล้าง Cache ของเบราว์เซอร์ (Ctrl+F5)<br>\n";
echo "- ตรวจสอบว่าโค้ดใหม่ถูกอัปโหลดไปยังเซิร์ฟเวอร์แล้ว<br>\n";
echo "- ตรวจสอบการตั้งค่า Cache ของเซิร์ฟเวอร์<br>\n";
?> 