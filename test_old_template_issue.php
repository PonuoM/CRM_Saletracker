<?php
/**
 * Test Old Template Issue
 * ทดสอบปัญหาที่เกิดจากไฟล์ Template เก่า
 */

// Load configuration
require_once 'config/config.php';
require_once 'app/services/ImportExportService.php';
require_once 'app/core/Database.php';

echo "<h1>ทดสอบปัญหาที่เกิดจากไฟล์ Template เก่า</h1>\n";

// Test 1: Create old template file (simulating what user might have)
echo "<h2>1. สร้างไฟล์ Template เก่า (จำลองปัญหาของผู้ใช้)</h2>\n";
$oldTemplateFile = 'uploads/old_template_test.csv';

$output = fopen($oldTemplateFile, 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Add BOM
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'ตำบล', 'อำเภอ', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ', 'ช่องทางการขาย', 'หมายเหตุ']);
fputcsv($output, ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'คลองเตย', 'กรุงเทพฯ', '10110', 'สินค้า A', '2', '1500', '3000', '2025-01-20', 'TikTok', 'ลูกค้าใหม่']);
fclose($output);

if (file_exists($oldTemplateFile)) {
    echo "✅ สร้างไฟล์ Template เก่าสำเร็จ: {$oldTemplateFile}<br>\n";
    
    // Read and analyze the old template
    $content = file_get_contents($oldTemplateFile);
    $lines = explode("\n", $content);
    
    if (count($lines) > 0) {
        $headers = str_getcsv($lines[0]);
        
        // Check for BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $headers[0] = substr($headers[0], 3);
            echo "✅ พบ BOM และลบแล้ว<br>\n";
        }
        
        echo "Headers ในไฟล์เก่า: " . implode(', ', $headers) . "<br>\n";
        
        // Check for problematic columns
        $hasTambon = in_array('ตำบล', $headers);
        $hasAmphur = in_array('อำเภอ', $headers);
        
        if ($hasTambon) {
            echo "❌ พบคอลัมน์ 'ตำบล' (ไม่ควรมี)<br>\n";
        }
        if ($hasAmphur) {
            echo "❌ พบคอลัมน์ 'อำเภอ' (ไม่ควรมี)<br>\n";
        }
        
        // Test mapping with old headers
        $oldColumnMap = [
            'ชื่อ' => 'first_name',
            'นามสกุล' => 'last_name',
            'เบอร์โทรศัพท์' => 'phone',
            'อีเมล' => 'email',
            'ที่อยู่' => 'address',
            'ตำบล' => 'district',  // Old mapping
            'อำเภอ' => 'province',  // Old mapping
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
            if (isset($oldColumnMap[$header])) {
                $mappedHeaders[] = $oldColumnMap[$header];
            } else {
                $mappedHeaders[] = null;
            }
        }
        
        echo "Mapped headers (old): " . implode(', ', array_filter($mappedHeaders)) . "<br>\n";
        
        // Test data mapping
        if (count($lines) > 1) {
            $data = str_getcsv($lines[1]);
            $salesData = [];
            foreach ($mappedHeaders as $index => $column) {
                if ($column && isset($data[$index])) {
                    $value = trim($data[$index]);
                    $salesData[$column] = $value;
                }
            }
            
            echo "Mapped data (old):<br>\n";
            foreach ($salesData as $key => $value) {
                echo "  {$key}: {$value}<br>\n";
            }
            
            // Test validation
            echo "<h4>การตรวจสอบข้อมูล (ไฟล์เก่า):</h4>\n";
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
    }
}

// Test 2: Test with new template file
echo "<h2>2. ทดสอบกับไฟล์ Template ใหม่</h2>\n";
$newTemplateFile = 'uploads/new_template_test.csv';

$output = fopen($newTemplateFile, 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Add BOM
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ', 'ช่องทางการขาย', 'หมายเหตุ']);
fputcsv($output, ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', '10110', 'สินค้า A', '2', '1500', '3000', '2025-01-20', 'TikTok', 'ลูกค้าใหม่']);
fclose($output);

if (file_exists($newTemplateFile)) {
    echo "✅ สร้างไฟล์ Template ใหม่สำเร็จ: {$newTemplateFile}<br>\n";
    
    // Read and analyze the new template
    $content = file_get_contents($newTemplateFile);
    $lines = explode("\n", $content);
    
    if (count($lines) > 0) {
        $headers = str_getcsv($lines[0]);
        
        // Check for BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $headers[0] = substr($headers[0], 3);
            echo "✅ พบ BOM และลบแล้ว<br>\n";
        }
        
        echo "Headers ในไฟล์ใหม่: " . implode(', ', $headers) . "<br>\n";
        
        // Check for correct columns
        $hasKhet = in_array('เขต', $headers);
        $hasProvince = in_array('จังหวัด', $headers);
        
        if ($hasKhet) {
            echo "✅ พบคอลัมน์ 'เขต' (ถูกต้อง)<br>\n";
        }
        if ($hasProvince) {
            echo "✅ พบคอลัมน์ 'จังหวัด' (ถูกต้อง)<br>\n";
        }
        
        // Test mapping with new headers
        $newColumnMap = [
            'ชื่อ' => 'first_name',
            'นามสกุล' => 'last_name',
            'เบอร์โทรศัพท์' => 'phone',
            'อีเมล' => 'email',
            'ที่อยู่' => 'address',
            'เขต' => 'district',  // New mapping
            'จังหวัด' => 'province',  // New mapping
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
            if (isset($newColumnMap[$header])) {
                $mappedHeaders[] = $newColumnMap[$header];
            } else {
                $mappedHeaders[] = null;
            }
        }
        
        echo "Mapped headers (new): " . implode(', ', array_filter($mappedHeaders)) . "<br>\n";
        
        // Test data mapping
        if (count($lines) > 1) {
            $data = str_getcsv($lines[1]);
            $salesData = [];
            foreach ($mappedHeaders as $index => $column) {
                if ($column && isset($data[$index])) {
                    $value = trim($data[$index]);
                    $salesData[$column] = $value;
                }
            }
            
            echo "Mapped data (new):<br>\n";
            foreach ($salesData as $key => $value) {
                echo "  {$key}: {$value}<br>\n";
            }
            
            // Test validation
            echo "<h4>การตรวจสอบข้อมูล (ไฟล์ใหม่):</h4>\n";
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
    }
}

// Test 3: Test actual import with old template
echo "<h2>3. ทดสอบการ Import ด้วยไฟล์ Template เก่า</h2>\n";
try {
    $service = new ImportExportService();
    $results = $service->importSalesFromCSV($oldTemplateFile);
    
    echo "<h4>ผลลัพธ์การ Import (ไฟล์เก่า):</h4>\n";
    echo "รวมทั้งหมด: {$results['total']} รายการ<br>\n";
    echo "สำเร็จ: {$results['success']} รายการ<br>\n";
    echo "ลูกค้าใหม่: {$results['customers_created']} ราย<br>\n";
    echo "อัพเดทลูกค้า: {$results['customers_updated']} ราย<br>\n";
    echo "คำสั่งซื้อใหม่: {$results['orders_created']} ราย<br>\n";
    
    if (!empty($results['errors'])) {
        echo "<h4>ข้อผิดพลาด (ไฟล์เก่า):</h4>\n";
        foreach ($results['errors'] as $error) {
            echo "  - {$error}<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

// Test 4: Test actual import with new template
echo "<h2>4. ทดสอบการ Import ด้วยไฟล์ Template ใหม่</h2>\n";
try {
    $service = new ImportExportService();
    $results = $service->importSalesFromCSV($newTemplateFile);
    
    echo "<h4>ผลลัพธ์การ Import (ไฟล์ใหม่):</h4>\n";
    echo "รวมทั้งหมด: {$results['total']} รายการ<br>\n";
    echo "สำเร็จ: {$results['success']} รายการ<br>\n";
    echo "ลูกค้าใหม่: {$results['customers_created']} ราย<br>\n";
    echo "อัพเดทลูกค้า: {$results['customers_updated']} ราย<br>\n";
    echo "คำสั่งซื้อใหม่: {$results['orders_created']} ราย<br>\n";
    
    if (!empty($results['errors'])) {
        echo "<h4>ข้อผิดพลาด (ไฟล์ใหม่):</h4>\n";
        foreach ($results['errors'] as $error) {
            echo "  - {$error}<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

// Clean up
if (file_exists($oldTemplateFile)) {
    unlink($oldTemplateFile);
    echo "✅ ลบไฟล์ Template เก่าแล้ว<br>\n";
}
if (file_exists($newTemplateFile)) {
    unlink($newTemplateFile);
    echo "✅ ลบไฟล์ Template ใหม่แล้ว<br>\n";
}

echo "<h2>สรุปปัญหาและวิธีแก้ไข</h2>\n";
echo "<h3>ปัญหาที่พบ:</h3>\n";
echo "1. ผู้ใช้อาจดาวน์โหลดไฟล์ Template เก่าที่ยังมีคอลัมน์ 'ตำบล' และ 'อำเภอ'<br>\n";
echo "2. การ Map คอลัมน์ในโค้ดปัจจุบันรองรับเฉพาะ 'เขต' และ 'จังหวัด'<br>\n";
echo "3. ไฟล์ Template เก่าจะทำให้การ Import ล้มเหลว<br>\n";
echo "<br>\n";
echo "<h3>วิธีแก้ไข:</h3>\n";
echo "1. อัปโหลดไฟล์ Template ใหม่ไปยังเซิร์ฟเวอร์<br>\n";
echo "2. ล้าง Cache ของเบราว์เซอร์และเซิร์ฟเวอร์<br>\n";
echo "3. ตรวจสอบว่าไฟล์ Template ที่ดาวน์โหลดมีคอลัมน์ 'เขต' และ 'จังหวัด'<br>\n";
echo "4. หากยังมีปัญหา ให้แก้ไขโค้ดให้รองรับทั้งคอลัมน์เก่าและใหม่<br>\n";
?> 