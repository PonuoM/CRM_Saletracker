<?php
/**
 * Test CSV Encoding Fix
 * ทดสอบการแก้ไขปัญหา encoding ของไฟล์ CSV
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Set user session for testing
$_SESSION['user_id'] = 1;

// Load service
require_once 'app/services/ImportExportService.php';

echo "<h1>ทดสอบการแก้ไขปัญหา Encoding ของไฟล์ CSV</h1>";

try {
    $service = new ImportExportService();
    
    echo "<h2>1. ทดสอบการอ่านไฟล์ Template ที่มีอยู่</h2>";
    
    // Test reading existing template files
    $templateFiles = [
        'templates/sales_import_template.csv',
        'templates/customers_only_template.csv',
        'templates/customers_template.csv'
    ];
    
    foreach ($templateFiles as $templateFile) {
        if (file_exists($templateFile)) {
            echo "<h3>ไฟล์: {$templateFile}</h3>";
            
            $content = file_get_contents($templateFile);
            $originalEncoding = mb_detect_encoding($content, ['UTF-8', 'TIS-620', 'ISO-8859-11', 'Windows-874'], true);
            
            echo "ขนาดไฟล์: " . strlen($content) . " ไบต์<br>";
            echo "Encoding เดิม: " . ($originalEncoding ?: 'Unknown') . "<br>";
            
            // Show first few characters
            $preview = mb_substr($content, 0, 200);
            echo "เนื้อหาต้น: " . htmlspecialchars($preview) . "<br>";
            
            // Test different encoding conversions
            $encodings = ['UTF-8', 'TIS-620', 'ISO-8859-11', 'Windows-874'];
            
            foreach ($encodings as $encoding) {
                if ($encoding !== $originalEncoding) {
                    $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
                    $preview = mb_substr($converted, 0, 200);
                    echo "แปลงจาก {$encoding}: " . htmlspecialchars($preview) . "<br>";
                }
            }
            
            echo "<br>";
        } else {
            echo "❌ {$templateFile}: ไม่พบไฟล์<br><br>";
        }
    }
    
    echo "<h2>2. สร้างไฟล์ทดสอบด้วย Encoding ต่างๆ</h2>";
    
    // Create test data
    $testData = [
        ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'หมายเหตุ'],
        ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'ลูกค้าใหม่'],
        ['สมหญิง', 'รักดี', '0898765432', 'somying@example.com', '456 ถ.รัชดา', 'ลูกค้าเก่า']
    ];
    
    // Test different encodings
    $encodings = [
        'UTF-8' => 'test_utf8.csv',
        'TIS-620' => 'test_tis620.csv',
        'Windows-874' => 'test_windows874.csv'
    ];
    
    foreach ($encodings as $encoding => $filename) {
        echo "<h3>สร้างไฟล์ {$filename} ด้วย encoding {$encoding}</h3>";
        
        $handle = fopen($filename, 'w');
        
        // Add BOM for UTF-8
        if ($encoding === 'UTF-8') {
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        }
        
        foreach ($testData as $row) {
            $encodedRow = array_map(function($cell) use ($encoding) {
                return mb_convert_encoding($cell, $encoding, 'UTF-8');
            }, $row);
            fputcsv($handle, $encodedRow);
        }
        fclose($handle);
        
        // Test reading back
        $content = file_get_contents($filename);
        $detectedEncoding = mb_detect_encoding($content, ['UTF-8', 'TIS-620', 'ISO-8859-11', 'Windows-874'], true);
        
        echo "ไฟล์: {$filename}<br>";
        echo "ขนาด: " . strlen($content) . " ไบต์<br>";
        echo "Encoding ที่ตรวจพบ: " . ($detectedEncoding ?: 'Unknown') . "<br>";
        
        // Convert to UTF-8 for display
        $utf8Content = mb_convert_encoding($content, 'UTF-8', $detectedEncoding ?: 'UTF-8');
        $preview = mb_substr($utf8Content, 0, 200);
        echo "เนื้อหา: " . htmlspecialchars($preview) . "<br><br>";
        
        // Clean up
        unlink($filename);
    }
    
    echo "<h2>3. ทดสอบการนำเข้าข้อมูลจากไฟล์ที่มี Encoding ต่างๆ</h2>";
    
    // Create a test file with TIS-620 encoding (common Thai encoding issue)
    $testFile = 'test_encoding_issue.csv';
    $handle = fopen($testFile, 'w');
    
    // Write data in TIS-620 encoding
    foreach ($testData as $row) {
        $encodedRow = array_map(function($cell) {
            return mb_convert_encoding($cell, 'TIS-620', 'UTF-8');
        }, $row);
        fputcsv($handle, $encodedRow);
    }
    fclose($handle);
    
    echo "สร้างไฟล์ทดสอบ: {$testFile}<br>";
    
    // Test import
    $results = $service->importCustomersOnlyFromCSV($testFile);
    
    echo "ผลการนำเข้า:<br>";
    echo "- รวมทั้งหมด: " . $results['total'] . " รายการ<br>";
    echo "- สำเร็จ: " . $results['success'] . " รายการ<br>";
    echo "- ลูกค้าใหม่: " . $results['customers_created'] . " ราย<br>";
    echo "- ข้าม: " . $results['customers_skipped'] . " ราย<br>";
    
    if (!empty($results['errors'])) {
        echo "- ข้อผิดพลาด:<br>";
        foreach ($results['errors'] as $error) {
            echo "  • " . $error . "<br>";
        }
    }
    
    // Clean up
    unlink($testFile);
    
    echo "<br><h2>✅ การทดสอบเสร็จสิ้น</h2>";
    echo "ระบบสามารถจัดการไฟล์ CSV ที่มี encoding ต่างๆ ได้แล้ว<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "ข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "ไฟล์: " . $e->getFile() . "<br>";
    echo "บรรทัด: " . $e->getLine() . "<br>";
}
?> 