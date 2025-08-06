<?php
/**
 * Test Import/Export System
 * ทดสอบระบบนำเข้าและส่งออกข้อมูล
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Set user session for testing
$_SESSION['user_id'] = 1;

// Load service
require_once 'app/services/ImportExportService.php';

echo "<h1>ทดสอบระบบ Import/Export</h1>";

try {
    $service = new ImportExportService();
    
    echo "<h2>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
    
    // Test database connection
    $db = $service->getDatabase();
    $testQuery = $db->fetchOne("SELECT COUNT(*) as count FROM customers");
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    echo "จำนวนลูกค้าในระบบ: " . $testQuery['count'] . " ราย<br><br>";
    
    echo "<h2>2. ทดสอบการสร้างไฟล์ CSV สำหรับ Sales Import</h2>";
    
    // Create test CSV file for sales import with proper encoding
    $testSalesData = [
        ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ', 'ช่องทางการขาย', 'หมายเหตุ'],
        ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', '10110', 'สินค้า A', '2', '1500', '3000', '2025-08-06', 'TikTok', 'ลูกค้าใหม่'],
        ['สมหญิง', 'รักดี', '0898765432', 'somying@example.com', '456 ถ.รัชดา', 'ดินแดง', 'กรุงเทพฯ', '10400', 'สินค้า B', '1', '2500', '2500', '2025-08-06', 'Adminpage', 'ลูกค้าเก่า']
    ];
    
    $testSalesFile = 'test_sales_import.csv';
    $handle = fopen($testSalesFile, 'w');
    
    // Add UTF-8 BOM
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($testSalesData as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    
    echo "✅ สร้างไฟล์ทดสอบ Sales Import: {$testSalesFile}<br>";
    
    // Test reading the file content
    $content = file_get_contents($testSalesFile);
    echo "✅ ไฟล์มีขนาด: " . strlen($content) . " ไบต์<br>";
    echo "✅ Encoding: " . mb_detect_encoding($content, ['UTF-8', 'TIS-620', 'ISO-8859-11', 'Windows-874'], true) . "<br><br>";
    
    echo "<h2>3. ทดสอบการสร้างไฟล์ CSV สำหรับ Customers Only Import</h2>";
    
    // Create test CSV file for customers only import
    $testCustomersData = [
        ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'หมายเหตุ'],
        ['สมศักดิ์', 'มั่งมี', '0876543210', 'somsak@example.com', '789 ถ.ลาดพร้าว', 'วังทองหลาง', 'กรุงเทพฯ', '10310', 'ลูกค้าจาก Facebook'],
        ['สมใจ', 'ประหยัด', '0865432109', 'somjai@example.com', '101 ถ.พระราม 4', 'ห้วยขวาง', 'กรุงเทพฯ', '10310', 'ลูกค้าจาก Instagram']
    ];
    
    $testCustomersFile = 'test_customers_only_import.csv';
    $handle = fopen($testCustomersFile, 'w');
    
    // Add UTF-8 BOM
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($testCustomersData as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    
    echo "✅ สร้างไฟล์ทดสอบ Customers Only Import: {$testCustomersFile}<br>";
    
    // Test reading the file content
    $content = file_get_contents($testCustomersFile);
    echo "✅ ไฟล์มีขนาด: " . strlen($content) . " ไบต์<br>";
    echo "✅ Encoding: " . mb_detect_encoding($content, ['UTF-8', 'TIS-620', 'ISO-8859-11', 'Windows-874'], true) . "<br><br>";
    
    echo "<h2>4. ทดสอบการอ่านไฟล์ Template ที่มีอยู่</h2>";
    
    // Test reading existing template files
    $templateFiles = [
        'templates/sales_import_template.csv',
        'templates/customers_only_template.csv',
        'templates/customers_template.csv'
    ];
    
    foreach ($templateFiles as $templateFile) {
        if (file_exists($templateFile)) {
            $content = file_get_contents($templateFile);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'TIS-620', 'ISO-8859-11', 'Windows-874'], true);
            echo "✅ {$templateFile}: " . strlen($content) . " ไบต์, Encoding: " . ($encoding ?: 'Unknown') . "<br>";
            
            // Show first few characters
            $preview = mb_substr($content, 0, 100);
            echo "   Preview: " . htmlspecialchars($preview) . "<br>";
        } else {
            echo "❌ {$templateFile}: ไม่พบไฟล์<br>";
        }
    }
    echo "<br>";
    
    echo "<h2>5. ทดสอบการนำเข้ายอดขาย</h2>";
    
    // Test sales import
    $salesResults = $service->importSalesFromCSV($testSalesFile);
    
    echo "ผลการนำเข้ายอดขาย:<br>";
    echo "- รวมทั้งหมด: " . $salesResults['total'] . " รายการ<br>";
    echo "- สำเร็จ: " . $salesResults['success'] . " รายการ<br>";
    echo "- ลูกค้าใหม่: " . $salesResults['customers_created'] . " ราย<br>";
    echo "- อัพเดทลูกค้า: " . $salesResults['customers_updated'] . " ราย<br>";
    echo "- สร้างคำสั่งซื้อ: " . $salesResults['orders_created'] . " รายการ<br>";
    
    if (!empty($salesResults['errors'])) {
        echo "- ข้อผิดพลาด:<br>";
        foreach ($salesResults['errors'] as $error) {
            echo "  • " . $error . "<br>";
        }
    }
    echo "<br>";
    
    echo "<h2>6. ทดสอบการนำเข้าเฉพาะรายชื่อ</h2>";
    
    // Test customers only import
    $customersResults = $service->importCustomersOnlyFromCSV($testCustomersFile);
    
    echo "ผลการนำเข้าเฉพาะรายชื่อ:<br>";
    echo "- รวมทั้งหมด: " . $customersResults['total'] . " รายการ<br>";
    echo "- สำเร็จ: " . $customersResults['success'] . " รายการ<br>";
    echo "- ลูกค้าใหม่: " . $customersResults['customers_created'] . " ราย<br>";
    echo "- ข้าม: " . $customersResults['customers_skipped'] . " ราย<br>";
    
    if (!empty($customersResults['errors'])) {
        echo "- ข้อผิดพลาด:<br>";
        foreach ($customersResults['errors'] as $error) {
            echo "  • " . $error . "<br>";
        }
    }
    echo "<br>";
    
    echo "<h2>7. ทดสอบการส่งออกข้อมูล</h2>";
    
    // Test export
    $customers = $service->exportCustomersToCSV();
    echo "✅ ส่งออกข้อมูลลูกค้า: " . count($customers) . " รายการ<br>";
    
    $orders = $service->exportOrdersToCSV();
    echo "✅ ส่งออกข้อมูลคำสั่งซื้อ: " . count($orders) . " รายการ<br><br>";
    
    echo "<h2>8. ทดสอบการสร้างรายงานสรุป</h2>";
    
    // Test summary report
    $reports = $service->exportSummaryReport();
    echo "✅ สร้างรายงานสรุปสำเร็จ<br>";
    echo "- สถิติลูกค้า: " . $reports['customer_stats']['total_customers'] . " ราย<br>";
    echo "- สถิติคำสั่งซื้อ: " . $reports['order_stats']['total_orders'] . " รายการ<br>";
    echo "- รายได้รวม: " . number_format($reports['revenue_stats']['total_revenue'], 2) . " บาท<br><br>";
    
    // Clean up test files
    if (file_exists($testSalesFile)) {
        unlink($testSalesFile);
        echo "🗑️ ลบไฟล์ทดสอบ Sales Import<br>";
    }
    
    if (file_exists($testCustomersFile)) {
        unlink($testCustomersFile);
        echo "🗑️ ลบไฟล์ทดสอบ Customers Only Import<br>";
    }
    
    echo "<br><h2>✅ การทดสอบเสร็จสิ้น</h2>";
    echo "ระบบ Import/Export ทำงานได้ปกติ<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "ข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "ไฟล์: " . $e->getFile() . "<br>";
    echo "บรรทัด: " . $e->getLine() . "<br>";
}
?> 