<?php
/**
 * ทดสอบระบบ Import หลังแก้ไข
 */

session_start();

// จำลอง session admin
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role_name'] = 'super_admin';

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 ทดสอบระบบ Import หลังแก้ไข</h1>";
echo "<hr>";

// 1. ทดสอบการโหลด ImportExportController
echo "<h2>📦 ทดสอบการโหลด Controller</h2>";

try {
    require_once 'config/config.php';
    require_once 'app/controllers/ImportExportController.php';
    
    $controller = new ImportExportController();
    echo "<div style='color: green;'>✅ ImportExportController โหลดสำเร็จ</div>";
    
    // ตรวจสอบ methods ที่จำเป็น
    $required_methods = [
        'index', 'importSales', 'importCustomersOnly', 'downloadTemplate',
        'importCustomers', 'exportCustomers', 'exportOrders', 'createBackup'
    ];
    
    echo "<h3>🔍 ตรวจสอบ Methods:</h3>";
    foreach ($required_methods as $method) {
        if (method_exists($controller, $method)) {
            echo "<div style='color: green;'>✅ {$method}() พร้อมใช้งาน</div>";
        } else {
            echo "<div style='color: red;'>❌ {$method}() ไม่พบ</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 2. ทดสอบการเข้าถึงหน้า import-export.php
echo "<h2>🌐 ทดสอบการเข้าถึงหน้า Import/Export</h2>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>📋 ลิงก์ทดสอบ:</h3>";
echo "<ul>";
echo "<li><a href='import-export.php' target='_blank'>📊 หน้า Import/Export หลัก</a></li>";
echo "<li><a href='import-export.php?action=downloadTemplate&type=sales' target='_blank'>📄 Download Sales Template</a></li>";
echo "<li><a href='import-export.php?action=downloadTemplate&type=customers_only' target='_blank'>📄 Download Customers Template</a></li>";
echo "</ul>";
echo "</div>";

// 3. ตรวจสอบไฟล์ที่เกี่ยวข้อง
echo "<h2>📁 ตรวจสอบไฟล์ที่เกี่ยวข้อง</h2>";

$important_files = [
    'import-export.php' => 'ไฟล์หลัก Import/Export',
    'app/controllers/ImportExportController.php' => 'Controller หลัก',
    'app/services/ImportExportService.php' => 'Service Layer',
    'app/views/import-export/index.php' => 'View Template',
    'assets/js/import-export.js' => 'JavaScript Functions'
];

foreach ($important_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<div style='color: green;'>✅ {$description}: {$file} (" . number_format($size) . " bytes)</div>";
    } else {
        echo "<div style='color: red;'>❌ {$description}: {$file} (ไม่พบ)</div>";
    }
}

// 4. สร้าง Quick Test Form
echo "<h2>🚀 Quick Test Form</h2>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>ทดสอบ Download Template:</h3>";
echo "<p><button onclick=\"window.open('import-export.php?action=downloadTemplate&type=sales', '_blank')\">📄 ดาวน์โหลด Sales Template</button></p>";
echo "<p><button onclick=\"window.open('import-export.php?action=downloadTemplate&type=customers_only', '_blank')\">📄 ดาวน์โหลด Customers Template</button></p>";
echo "</div>";

// 5. ข้อแนะนำการทดสอบ
echo "<h2>📋 ขั้นตอนการทดสอบแนะนำ</h2>";

echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>🎯 ขั้นตอนการทดสอบ:</h3>";
echo "<ol>";
echo "<li><strong>คลิกลิงก์ด้านบน</strong> เพื่อเข้าไปที่หน้า Import/Export</li>";
echo "<li><strong>ตรวจสอบว่าหน้าแสดงปกติ</strong> (ไม่ใช่หน้าขาวหรือ 500 error)</li>";
echo "<li><strong>ทดสอบ Download Template</strong> ในแต่ละแท็บ</li>";
echo "<li><strong>ลองอัปโหลดไฟล์ CSV</strong> (ใช้ template ที่ดาวน์โหลด)</li>";
echo "<li><strong>ตรวจสอบผลลัพธ์</strong> ว่าไม่มี error และมีข้อมูลถูกต้อง</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>⚠️ หากยังมีปัญหา:</h3>";
echo "<ul>";
echo "<li>ตรวจสอบ PHP error log ของเซิร์ฟเวอร์</li>";
echo "<li>ตรวจสอบ permissions ของโฟลเดอร์ uploads/</li>";
echo "<li>รัน fix_import_issues.php อีกครั้ง</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>🕒 ทดสอบเมื่อ: " . date('Y-m-d H:i:s') . "</small></p>";
?>

<script>
// Test AJAX function
function testAjaxConnection() {
    fetch('import-export.php?action=downloadTemplate&type=sales')
        .then(response => {
            if (response.ok) {
                alert('✅ การเชื่อมต่อ AJAX ทำงานปกติ!');
            } else {
                alert('❌ การเชื่อมต่อ AJAX มีปัญหา: ' + response.status);
            }
        })
        .catch(error => {
            alert('❌ เกิดข้อผิดพลาด: ' + error.message);
        });
}
</script>

<style>
body { 
    font-family: 'Sukhumvit Set', Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6; 
    background: #f8f9fa;
}
h1, h2, h3 { color: #333; }
button { 
    background: #007bff; 
    color: white; 
    border: none; 
    padding: 10px 15px; 
    border-radius: 5px; 
    cursor: pointer; 
    margin: 5px;
}
button:hover { background: #0056b3; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
