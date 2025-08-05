<?php
/**
 * ตรวจสอบโครงสร้างตาราง customers จริงในฐานข้อมูล
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

echo "<h1>ตรวจสอบโครงสร้างตาราง customers</h1>\n";
echo "<hr>\n";

try {
    $db = new Database();
    
    echo "<h2>1. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>\n";
    $testQuery = $db->query("SELECT 1 as test");
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n\n";
    
    echo "<h2>2. โครงสร้างตาราง customers</h2>\n";
    $columns = $db->query("DESCRIBE customers");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    $customerColumns = [];
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>\n";
        
        $customerColumns[] = $column['Field'];
    }
    echo "</table>\n\n";
    
    echo "<h2>3. คอลัมน์ที่สามารถใช้สำหรับ Import ได้</h2>\n";
    $importableColumns = [
        'first_name' => 'ชื่อ (จำเป็น)',
        'last_name' => 'นามสกุล (จำเป็น)', 
        'phone' => 'เบอร์โทรศัพท์ (จำเป็น)',
        'email' => 'อีเมล',
        'address' => 'ที่อยู่',
        'district' => 'เขต/อำเภอ',
        'province' => 'จังหวัด',
        'postal_code' => 'รหัสไปรษณีย์',
        'source' => 'แหล่งที่มา',
        'notes' => 'หมายเหตุ'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Database Column</th><th>Template Header (Thai)</th><th>Status</th></tr>\n";
    
    foreach ($importableColumns as $dbColumn => $thaiHeader) {
        $status = in_array($dbColumn, $customerColumns) ? "✅ มีในฐานข้อมูล" : "❌ ไม่มีในฐานข้อมูล";
        echo "<tr>";
        echo "<td>{$dbColumn}</td>";
        echo "<td>{$thaiHeader}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n\n";
    
    echo "<h2>4. Template CSV ที่ถูกต้อง</h2>\n";
    $templateHeaders = [];
    $templateData = [];
    
    foreach ($importableColumns as $dbColumn => $thaiHeader) {
        if (in_array($dbColumn, $customerColumns)) {
            $templateHeaders[] = $thaiHeader;
            
            // Sample data
            switch ($dbColumn) {
                case 'first_name':
                    $templateData[] = 'สมชาย';
                    break;
                case 'last_name':
                    $templateData[] = 'ใจดี';
                    break;
                case 'phone':
                    $templateData[] = '0812345678';
                    break;
                case 'email':
                    $templateData[] = 'somchai@example.com';
                    break;
                case 'address':
                    $templateData[] = '123 ถนนสุขุมวิท';
                    break;
                case 'district':
                    $templateData[] = 'คลองเตย';
                    break;
                case 'province':
                    $templateData[] = 'กรุงเทพฯ';
                    break;
                case 'postal_code':
                    $templateData[] = '10110';
                    break;
                case 'source':
                    $templateData[] = 'facebook';
                    break;
                case 'notes':
                    $templateData[] = 'ลูกค้าใหม่สนใจสินค้า';
                    break;
                default:
                    $templateData[] = '';
            }
        }
    }
    
    echo "<h3>Headers:</h3>\n";
    echo "<pre>" . implode(',', $templateHeaders) . "</pre>\n";
    
    echo "<h3>Sample Data:</h3>\n";
    echo "<pre>" . implode(',', $templateData) . "</pre>\n";
    
    echo "<h2>5. Column Mapping สำหรับ ImportExportService</h2>\n";
    echo "<pre>\n";
    echo "private function getCustomerColumnMap() {\n";
    echo "    return [\n";
    foreach ($importableColumns as $dbColumn => $thaiHeader) {
        if (in_array($dbColumn, $customerColumns)) {
            echo "        '{$thaiHeader}' => '{$dbColumn}',\n";
        }
    }
    echo "    ];\n";
    echo "}\n";
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}

echo "<hr>\n";
echo "<p><strong>ตรวจสอบเสร็จสิ้น</strong></p>\n";
?>