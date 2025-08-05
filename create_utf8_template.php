<?php
/**
 * สร้างไฟล์ Template CSV ที่มี UTF-8 BOM อย่างถูกต้อง
 */

// Set internal encoding
mb_internal_encoding('UTF-8');

// Create templates directory if it doesn't exist
$templateDir = __DIR__ . '/templates/';
if (!is_dir($templateDir)) {
    mkdir($templateDir, 0755, true);
}

// Customer template data
$customerTemplate = [
    ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'ตำบล/แขวง', 'อำเภอ/เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'แหล่งที่มา', 'หมายเหตุ'],
    ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถนนสุขุมวิท', 'คลองเตย', 'คลองเตย', 'กรุงเทพฯ', '10110', 'facebook', 'ลูกค้าใหม่สนใจสินค้า'],
    ['สมหญิง', 'รักดี', '0898765432', 'somying@example.com', '456 ถนนรัชดาภิเษก', 'ดินแดง', 'ดินแดง', 'กรุงเทพฯ', '10400', 'import', 'ลูกค้าเก่าต้องการติดตาม'],
    ['สมศักดิ์', 'มั่งคง', '0654321987', 'somsak@example.com', '789 ถนนลาดพร้าว', 'จันทรเกษม', 'จตุจักร', 'กรุงเทพฯ', '10900', 'manual', 'ลูกค้า VIP ต้องดูแลเป็นพิเศษ']
];

// Create customers template file with UTF-8 BOM
$filename = $templateDir . 'customers_template.csv';
$handle = fopen($filename, 'w');

// Write UTF-8 BOM
fwrite($handle, "\xEF\xBB\xBF");

// Write data
foreach ($customerTemplate as $row) {
    // Ensure each cell is properly UTF-8 encoded
    $encodedRow = array_map(function($cell) {
        return mb_convert_encoding($cell, 'UTF-8', 'UTF-8');
    }, $row);
    
    fputcsv($handle, $encodedRow);
}

fclose($handle);

echo "✅ สร้างไฟล์ templates/customers_template.csv สำเร็จ\n";
echo "ขนาดไฟล์: " . filesize($filename) . " bytes\n";
echo "Encoding: UTF-8 with BOM\n";

// Verify file content
echo "\n📄 เนื้อหาไฟล์:\n";
$content = file_get_contents($filename);
echo "BOM Check: " . (substr($content, 0, 3) === "\xEF\xBB\xBF" ? "✅ มี UTF-8 BOM" : "❌ ไม่มี UTF-8 BOM") . "\n";

// Read and display first few lines
$lines = file($filename);
echo "\nบรรทัดแรก (Header): " . trim($lines[0]) . "\n";
echo "บรรทัดที่ 2 (ตัวอย่าง): " . trim($lines[1]) . "\n";

// Test reading with PHP
echo "\n🧪 ทดสอบการอ่านด้วย PHP:\n";
$testHandle = fopen($filename, 'r');
$testHeader = fgetcsv($testHandle);

// Check for BOM in first cell
if (!empty($testHeader[0]) && substr($testHeader[0], 0, 3) === "\xEF\xBB\xBF") {
    $testHeader[0] = substr($testHeader[0], 3);
    echo "✅ ตรวจพบและลบ BOM แล้ว\n";
}

echo "Header ที่อ่านได้: " . implode(', ', $testHeader) . "\n";

$testData = fgetcsv($testHandle);
echo "ข้อมูลแถวแรก: " . implode(', ', $testData) . "\n";

fclose($testHandle);

echo "\n✅ การสร้างและทดสอบไฟล์ template เสร็จสิ้น\n";
?>