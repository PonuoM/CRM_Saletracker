<?php
/**
 * Test Import Debug
 * ทดสอบการแก้ไขปัญหา Import ที่ไม่สำเร็จ
 */

// Load configuration
require_once 'config/config.php';
require_once 'app/services/ImportExportService.php';
require_once 'app/core/Database.php';

echo "<h1>ทดสอบการแก้ไขปัญหา Import</h1>\n";

// Test 1: Check template files
echo "<h2>1. ตรวจสอบไฟล์ Template</h2>\n";
$templateFiles = [
    'templates/sales_import_template.csv',
    'templates/customers_only_template.csv'
];

foreach ($templateFiles as $file) {
    echo "<h3>ไฟล์: {$file}</h3>\n";
    
    if (file_exists($file)) {
        echo "✅ ไฟล์พบ<br>\n";
        
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        if (count($lines) > 0) {
            $headers = str_getcsv($lines[0]);
            echo "Headers: " . implode(', ', $headers) . "<br>\n";
            
            // Check for BOM
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                echo "✅ มี UTF-8 BOM<br>\n";
                // Remove BOM from first line
                $headers[0] = substr($headers[0], 3);
                echo "Headers (after BOM removal): " . implode(', ', $headers) . "<br>\n";
            } else {
                echo "❌ ไม่มี UTF-8 BOM<br>\n";
            }
            
            // Show sample data
            if (count($lines) > 1) {
                $sampleData = str_getcsv($lines[1]);
                echo "Sample data: " . implode(', ', $sampleData) . "<br>\n";
            }
        }
    } else {
        echo "❌ ไฟล์ไม่พบ<br>\n";
    }
    echo "<br>\n";
}

// Test 2: Test column mapping
echo "<h2>2. ทดสอบ Column Mapping</h2>\n";
echo "<h3>Sales Column Mapping (Expected):</h3>\n";
$expectedSalesMap = [
    'ชื่อ' => 'first_name',
    'นามสกุล' => 'last_name',
    'เบอร์โทรศัพท์' => 'phone',
    'อีเมล' => 'email',
    'ที่อยู่' => 'address',
    'เขต' => 'district',
    'จังหวัด' => 'province',
    'รหัสไปรษณีย์' => 'postal_code',
    'ชื่อสินค้า' => 'product_name',
    'จำนวน' => 'quantity',
    'ราคาต่อชิ้น' => 'unit_price',
    'ยอดรวม' => 'total_amount',
    'วันที่สั่งซื้อ' => 'order_date',
    'ช่องทางการขาย' => 'sales_channel',
    'หมายเหตุ' => 'notes'
];

foreach ($expectedSalesMap as $header => $column) {
    echo "  '{$header}' => '{$column}'<br>\n";
}

echo "<h3>Customers Only Column Mapping (Expected):</h3>\n";
$expectedCustomersOnlyMap = [
    'ชื่อ' => 'first_name',
    'นามสกุล' => 'last_name',
    'เบอร์โทรศัพท์' => 'phone',
    'อีเมล' => 'email',
    'ที่อยู่' => 'address',
    'เขต' => 'district',
    'จังหวัด' => 'province',
    'รหัสไปรษณีย์' => 'postal_code',
    'หมายเหตุ' => 'notes'
];

foreach ($expectedCustomersOnlyMap as $header => $column) {
    echo "  '{$header}' => '{$column}'<br>\n";
}

// Test 3: Test CSV parsing
echo "<h2>3. ทดสอบการ Parse CSV</h2>\n";
$testFile = 'templates/sales_import_template.csv';
if (file_exists($testFile)) {
    echo "<h3>ทดสอบ Parse ไฟล์: {$testFile}</h3>\n";
    
    $handle = fopen($testFile, 'r');
    if ($handle) {
        // Read headers
        $headers = fgetcsv($handle);
        
        // Check for BOM
        if ($headers && !empty($headers[0]) && substr($headers[0], 0, 3) === "\xEF\xBB\xBF") {
            $headers[0] = substr($headers[0], 3);
            echo "✅ พบ BOM และลบแล้ว<br>\n";
        }
        
        echo "Headers: " . implode(', ', $headers) . "<br>\n";
        
        // Read first data row
        $data = fgetcsv($handle);
        if ($data) {
            echo "Data: " . implode(', ', $data) . "<br>\n";
            
            // Test mapping
            $columnMap = [
                'ชื่อ' => 'first_name',
                'นามสกุล' => 'last_name',
                'เบอร์โทรศัพท์' => 'phone',
                'อีเมล' => 'email',
                'ที่อยู่' => 'address',
                'เขต' => 'district',
                'จังหวัด' => 'province',
                'รหัสไปรษณีย์' => 'postal_code',
                'ชื่อสินค้า' => 'product_name',
                'จำนวน' => 'quantity',
                'ราคาต่อชิ้น' => 'unit_price',
                'ยอดรวม' => 'total_amount',
                'วันที่สั่งซื้อ' => 'order_date',
                'ช่องทางการขาย' => 'sales_channel',
                'หมายเหตุ' => 'notes'
            ];
            
            $mappedHeaders = [];
            foreach ($headers as $header) {
                $header = trim($header);
                if (isset($columnMap[$header])) {
                    $mappedHeaders[] = $columnMap[$header];
                } else {
                    $mappedHeaders[] = null;
                }
            }
            
            echo "Mapped headers: " . implode(', ', array_filter($mappedHeaders)) . "<br>\n";
            
            // Test data mapping
            $salesData = [];
            foreach ($mappedHeaders as $index => $column) {
                if ($column && isset($data[$index])) {
                    $value = trim($data[$index]);
                    $salesData[$column] = $value;
                }
            }
            
            echo "Mapped data:<br>\n";
            foreach ($salesData as $key => $value) {
                echo "  {$key}: {$value}<br>\n";
            }
            
            // Test validation
            echo "<h4>การตรวจสอบข้อมูล:</h4>\n";
            if (empty($salesData['first_name'])) {
                echo "❌ first_name ว่าง<br>\n";
            } else {
                echo "✅ first_name: '{$salesData['first_name']}'<br>\n";
            }
            
            if (empty($salesData['phone'])) {
                echo "❌ phone ว่าง<br>\n";
            } else {
                echo "✅ phone: '{$salesData['phone']}'<br>\n";
            }
            
            if (empty($salesData['first_name']) || empty($salesData['phone'])) {
                echo "❌ การตรวจสอบล้มเหลว: ชื่อและเบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น<br>\n";
            } else {
                echo "✅ การตรวจสอบผ่าน<br>\n";
            }
        }
        
        fclose($handle);
    }
}

// Test 4: Test database connection
echo "<h2>4. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>\n";
try {
    $db = new Database();
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>\n";
    
    // Check customers table structure
    $columns = $db->fetchAll("DESCRIBE customers");
    echo "คอลัมน์ในตาราง customers:<br>\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ การเชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage() . "<br>\n";
}

// Test 5: Test import simulation
echo "<h2>5. ทดสอบการจำลอง Import</h2>\n";
$testImportFile = 'uploads/test_import_debug.csv';

// Create test file
$output = fopen($testImportFile, 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Add BOM
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ', 'ช่องทางการขาย', 'หมายเหตุ']);
fputcsv($output, ['ทดสอบ', 'ระบบ', '0812345678', 'test@example.com', '123 ถ.ทดสอบ', 'คลองเตย', 'กรุงเทพฯ', '10110', 'สินค้าทดสอบ', '1', '1000', '1000', '2025-01-20', 'Test', 'ข้อมูลทดสอบ']);
fclose($output);

if (file_exists($testImportFile)) {
    echo "✅ สร้างไฟล์ทดสอบสำเร็จ: {$testImportFile}<br>\n";
    
    try {
        $service = new ImportExportService();
        $results = $service->importSalesFromCSV($testImportFile);
        
        echo "<h4>ผลลัพธ์การ Import:</h4>\n";
        echo "รวมทั้งหมด: {$results['total']} รายการ<br>\n";
        echo "สำเร็จ: {$results['success']} รายการ<br>\n";
        echo "ลูกค้าใหม่: {$results['customers_created']} ราย<br>\n";
        echo "อัพเดทลูกค้า: {$results['customers_updated']} ราย<br>\n";
        echo "คำสั่งซื้อใหม่: {$results['orders_created']} ราย<br>\n";
        
        if (!empty($results['errors'])) {
            echo "<h4>ข้อผิดพลาด:</h4>\n";
            foreach ($results['errors'] as $error) {
                echo "  - {$error}<br>\n";
            }
        }
        
        // Clean up
        unlink($testImportFile);
        echo "✅ ลบไฟล์ทดสอบแล้ว<br>\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>\n";
    }
}

echo "<h2>สรุปการแก้ไขปัญหา</h2>\n";
echo "ปัญหาที่พบ:<br>\n";
echo "1. ไฟล์ Template ที่ดาวน์โหลดจากเว็บไซต์ Live อาจเป็นไฟล์เก่าที่ยังมีคอลัมน์ 'ตำบล' และ 'อำเภอ'<br>\n";
echo "2. การ Map คอลัมน์อาจไม่ตรงกับ Header ที่จริงในไฟล์ CSV<br>\n";
echo "3. การตรวจสอบ BOM อาจไม่ถูกต้อง<br>\n";
echo "<br>\n";
echo "วิธีแก้ไข:<br>\n";
echo "1. ตรวจสอบว่าไฟล์ Template ใหม่ถูกอัปโหลดไปยังเซิร์ฟเวอร์แล้ว<br>\n";
echo "2. ล้าง Cache ของเบราว์เซอร์และเซิร์ฟเวอร์<br>\n";
echo "3. ตรวจสอบการ Map คอลัมน์ให้ตรงกับ Header จริง<br>\n";
?> 