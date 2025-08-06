<?php
/**
 * Test Import Debug Step by Step - ทดสอบการ import แบบ debug ทีละขั้นตอน
 * เพื่อระบุปัญหาที่แท้จริงใน importSalesFromCSV method
 */

echo "<h1>🔍 Test Import Debug Step by Step - ทดสอบการ import แบบ debug ทีละขั้นตอน</h1>";

// 1. โหลดไฟล์ที่จำเป็น
echo "<h2>1. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/ImportExportService.php';
    echo "✅ โหลดไฟล์ที่จำเป็นสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. สร้าง Service และ Database
echo "<h2>2. สร้าง Service และ Database</h2>";
try {
    $service = new ImportExportService();
    $db = new Database();
    echo "✅ สร้าง Service และ Database สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error creating objects: " . $e->getMessage() . "<br>";
    exit;
}

// 3. สร้างไฟล์ CSV ทดสอบ
echo "<h2>3. สร้างไฟล์ CSV ทดสอบ</h2>";
try {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testFile = $uploadDir . 'test_debug_step.csv';
    $csvContent = "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,วันที่สั่งซื้อ,หมายเหตุ\n";
    $csvContent .= "ทดสอบ,ระบบ,0812345678,test@example.com,123 ถ.ทดสอบ,เขตทดสอบ,จังหวัดทดสอบ,10000,สินค้าทดสอบ,1,1000,2024-01-15,ทดสอบระบบ\n";
    
    file_put_contents($testFile, $csvContent);
    echo "✅ สร้างไฟล์ CSV จำลองสำเร็จ<br>";
    echo "ไฟล์: {$testFile}<br>";
    echo "ขนาด: " . filesize($testFile) . " bytes<br>";
} catch (Exception $e) {
    echo "❌ CSV Creation Error: " . $e->getMessage() . "<br>";
    exit;
}

// 4. ทดสอบการอ่านไฟล์และประมวลผลทีละขั้นตอน
echo "<h2>4. ทดสอบการอ่านไฟล์และประมวลผลทีละขั้นตอน</h2>";

try {
    // 4.1 อ่านไฟล์
    echo "<h3>4.1 อ่านไฟล์</h3>";
    $content = file_get_contents($testFile);
    if ($content === false) {
        echo "❌ ไม่สามารถอ่านไฟล์ได้<br>";
        exit;
    }
    echo "✅ อ่านไฟล์สำเร็จ<br>";
    echo "ความยาวเนื้อหา: " . strlen($content) . " bytes<br>";
    
    // 4.2 ตรวจสอบ encoding
    echo "<h3>4.2 ตรวจสอบ encoding</h3>";
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-11', 'Windows-874'], true);
    if (!$encoding) {
        $encoding = 'UTF-8';
    }
    echo "✅ Encoding ที่ตรวจพบ: " . $encoding . "<br>";
    
    // 4.3 แปลง encoding ถ้าจำเป็น
    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        echo "✅ แปลง encoding เป็น UTF-8 สำเร็จ<br>";
    }
    
    // 4.4 ลบ BOM
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    echo "✅ ลบ BOM สำเร็จ<br>";
    
    // 4.5 แยกเป็นบรรทัด
    echo "<h3>4.3 แยกเป็นบรรทัด</h3>";
    $lines = explode("\n", $content);
    echo "✅ แยกเป็นบรรทัดสำเร็จ: " . count($lines) . " บรรทัด<br>";
    
    // 4.6 ลบบรรทัดว่าง
    $lines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    echo "✅ ลบบรรทัดว่างสำเร็จ: " . count($lines) . " บรรทัดที่ไม่ว่าง<br>";
    
    if (empty($lines)) {
        echo "❌ ไม่มีข้อมูลในไฟล์ CSV<br>";
        exit;
    }
    
    // 4.7 ประมวลผล header
    echo "<h3>4.4 ประมวลผล header</h3>";
    $headerLine = array_shift($lines);
    $headers = str_getcsv($headerLine);
    echo "✅ แยก header สำเร็จ: " . json_encode($headers) . "<br>";
    
    // 4.8 ทำความสะอาด header
    $headers = array_map(function($header) {
        return trim($header);
    }, $headers);
    echo "✅ ทำความสะอาด header สำเร็จ: " . json_encode($headers) . "<br>";
    
    // 4.9 แมป header กับคอลัมน์ฐานข้อมูล
    echo "<h3>4.5 แมป header กับคอลัมน์ฐานข้อมูล</h3>";
    
    // ใช้ reflection เพื่อเข้าถึง private method
    $reflection = new ReflectionClass($service);
    $getSalesColumnMapMethod = $reflection->getMethod('getSalesColumnMap');
    $getSalesColumnMapMethod->setAccessible(true);
    $columnMap = $getSalesColumnMapMethod->invoke($service);
    
    echo "✅ Column map: " . json_encode($columnMap) . "<br>";
    
    $mappedHeaders = [];
    foreach ($headers as $header) {
        if (isset($columnMap[$header])) {
            $mappedHeaders[] = $columnMap[$header];
        } else {
            $mappedHeaders[] = null;
        }
    }
    echo "✅ Mapped headers: " . json_encode($mappedHeaders) . "<br>";
    
    // 4.10 ประมวลผลข้อมูล
    echo "<h3>4.6 ประมวลผลข้อมูล</h3>";
    $rowNumber = 1; // Header row
    foreach ($lines as $line) {
        $rowNumber++;
        echo "ประมวลผลแถวที่ {$rowNumber}<br>";
        
        $data = str_getcsv($line);
        echo "ข้อมูลแถว: " . json_encode($data) . "<br>";
        
        $salesData = [];
        foreach ($mappedHeaders as $index => $column) {
            if ($column && isset($data[$index])) {
                $value = trim($data[$index]);
                $salesData[$column] = $value;
            }
        }
        echo "ข้อมูลที่แมปแล้ว: " . json_encode($salesData) . "<br>";
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($salesData['first_name'])) {
            echo "❌ แถวที่ {$rowNumber}: ชื่อเป็นข้อมูลที่จำเป็น<br>";
            continue;
        }
        
        if (empty($salesData['phone'])) {
            echo "❌ แถวที่ {$rowNumber}: เบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น<br>";
            continue;
        }
        
        echo "✅ ข้อมูลแถวที่ {$rowNumber} ผ่านการตรวจสอบ<br>";
        
        // ตรวจสอบว่าลูกค้ามีอยู่หรือไม่
        $existingCustomer = $db->fetchOne(
            "SELECT customer_id, first_name, last_name FROM customers WHERE phone = ?",
            [$salesData['phone']]
        );
        
        if ($existingCustomer) {
            echo "✅ พบลูกค้าที่มีอยู่: " . $existingCustomer['customer_id'] . "<br>";
        } else {
            echo "✅ จะสร้างลูกค้าใหม่สำหรับเบอร์: " . $salesData['phone'] . "<br>";
        }
        
        break; // ประมวลผลแค่แถวแรก
    }
    
    echo "✅ การประมวลผลข้อมูลสำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Error in step-by-step processing: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 5. ทดสอบการ import จริง
echo "<h2>5. ทดสอบการ import จริง</h2>";
try {
    echo "เริ่มการ import...<br>";
    $results = $service->importSalesFromCSV($testFile);
    echo "✅ Import สำเร็จ<br>";
    echo "ผลลัพธ์: <pre>" . print_r($results, true) . "</pre><br>";
} catch (Exception $e) {
    echo "❌ Import Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

// 6. ทำความสะอาด
echo "<h2>6. ทำความสะอาด</h2>";
try {
    if (file_exists($testFile)) {
        unlink($testFile);
        echo "✅ ลบไฟล์ทดสอบสำเร็จ<br>";
    }
} catch (Exception $e) {
    echo "❌ Cleanup Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการทดสอบ</h2>";
echo "การทดสอบเสร็จสิ้นแล้ว กรุณาตรวจสอบผลลัพธ์ด้านบนเพื่อระบุปัญหาที่แท้จริง<br>";
?> 