<?php
/**
 * แก้ไขปัญหา Import/Export System
 * ตรวจสอบและแก้ไขปัญหาที่เป็นไปได้
 */

session_start();

// Load configuration
require_once 'config/config.php';

echo "<h1>🔧 แก้ไขปัญหา Import/Export System</h1>";
echo "<hr>";

// ฟังก์ชันสำหรับแสดงผลลัพธ์
function showResult($success, $message) {
    $color = $success ? 'green' : 'red';
    $icon = $success ? '✅' : '❌';
    echo "<div style='color: {$color}; margin: 5px 0;'><strong>{$icon} {$message}</strong></div>";
}

// 1. ตรวจสอบและสร้างโฟลเดอร์ที่จำเป็น
echo "<h2>📁 ตรวจสอบและสร้างโฟลเดอร์</h2>";

$directories = [
    'uploads' => 0755,
    'uploads/customers' => 0755,
    'uploads/orders' => 0755,
    'uploads/imports' => 0755,
    'backups' => 0755,
    'logs' => 0755,
    'templates' => 0755
];

foreach ($directories as $dir => $permission) {
    if (!is_dir($dir)) {
        if (mkdir($dir, $permission, true)) {
            showResult(true, "สร้างโฟลเดอร์ {$dir} สำเร็จ");
        } else {
            showResult(false, "ไม่สามารถสร้างโฟลเดอร์ {$dir} ได้");
        }
    } else {
        // ตรวจสอบสิทธิ์
        if (is_writable($dir)) {
            showResult(true, "โฟลเดอร์ {$dir} พร้อมใช้งาน");
        } else {
            // พยายามเปลี่ยนสิทธิ์
            if (chmod($dir, $permission)) {
                showResult(true, "แก้ไขสิทธิ์โฟลเดอร์ {$dir} สำเร็จ");
            } else {
                showResult(false, "ไม่สามารถแก้ไขสิทธิ์โฟลเดอร์ {$dir} ได้");
            }
        }
    }
}

// 2. สร้างไฟล์ Template ที่จำเป็น
echo "<h2>📄 สร้างไฟล์ Template</h2>";

// Template สำหรับยอดขาย
$salesTemplate = [
    ['ชื่อ', 'นามสกุล', 'เบอร์โทร', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'รหัสสินค้า', 'ชื่อสินค้า', 'จำนวน', 'ราคา', 'ยอดรวม', 'วันที่สั่งซื้อ'],
    ['สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'กรุงเทพฯ', 'P001', 'เสื้อโปโล', '1', '250', '250', '2025-01-01'],
    ['สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดา', 'กรุงเทพฯ', 'P002', 'กางเกงยีนส์', '2', '450', '900', '2025-01-02']
];

// Template สำหรับลูกค้าเท่านั้น
$customersTemplate = [
    ['ชื่อ', 'นามสกุล', 'เบอร์โทร', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'หมายเหตุ'],
    ['สมปอง', 'มั่งมี', '081-333-3333', 'sompong@email.com', '789 ถนนลาดพร้าว', 'กรุงเทพฯ', 'ลูกค้าใหม่'],
    ['สมศักดิ์', 'ประหยัด', '081-444-4444', 'somsak@email.com', '321 ถนนเพชรบุรี', 'กรุงเทพฯ', 'ลูกค้าจากพี่']
];

// สร้างไฟล์ CSV
function createCSVTemplate($filename, $data) {
    $filepath = "templates/{$filename}";
    $file = fopen($filepath, 'w');
    
    // เพิ่ม BOM สำหรับ UTF-8
    fwrite($file, "\xEF\xBB\xBF");
    
    foreach ($data as $row) {
        fputcsv($file, $row);
    }
    fclose($file);
    
    return file_exists($filepath);
}

// สร้าง templates
$templates = [
    'sales_import_template.csv' => $salesTemplate,
    'customers_only_template.csv' => $customersTemplate
];

foreach ($templates as $filename => $data) {
    if (createCSVTemplate($filename, $data)) {
        showResult(true, "สร้างไฟล์ template {$filename} สำเร็จ");
    } else {
        showResult(false, "ไม่สามารถสร้างไฟล์ template {$filename} ได้");
    }
}

// 3. ตรวจสอบ Database Connection และ Tables
echo "<h2>🗄️ ตรวจสอบฐานข้อมูล</h2>";

try {
    require_once 'app/core/Database.php';
    $db = new Database();
    showResult(true, "เชื่อมต่อฐานข้อมูลสำเร็จ");
    
    // ตรวจสอบตารางที่จำเป็น
    $required_tables = ['customers', 'orders', 'order_items', 'products', 'users'];
    
    foreach ($required_tables as $table) {
        if ($db->tableExists($table)) {
            showResult(true, "ตาราง {$table} พร้อมใช้งาน");
        } else {
            showResult(false, "ไม่พบตาราง {$table}");
        }
    }
    
} catch (Exception $e) {
    showResult(false, "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}

// 4. ตรวจสอบ PHP Settings
echo "<h2>⚙️ ตรวจสอบการตั้งค่า PHP</h2>";

$php_settings = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

foreach ($php_settings as $setting => $value) {
    echo "<div style='margin: 5px 0;'><strong>{$setting}:</strong> {$value}</div>";
}

// แนะนำการตั้งค่าที่เหมาะสม
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<strong>📋 การตั้งค่าที่แนะนำ:</strong><br>";
echo "<code>upload_max_filesize = 10M</code><br>";
echo "<code>post_max_size = 10M</code><br>";
echo "<code>max_execution_time = 300</code><br>";
echo "<code>memory_limit = 256M</code><br>";
echo "</div>";

// 5. ทดสอบ Import Function
echo "<h2>🧪 ทดสอบ Import Function</h2>";

try {
    require_once 'app/controllers/ImportExportController.php';
    require_once 'app/services/ImportExportService.php';
    
    $controller = new ImportExportController();
    $service = new ImportExportService();
    
    showResult(true, "Import classes โหลดสำเร็จ");
    
    // ทดสอบ method ที่จำเป็น
    $required_methods = [
        'ImportExportController' => ['index', 'importCustomers', 'importSales', 'importCustomersOnly'],
        'ImportExportService' => ['importSalesData', 'importCustomersOnlyData']
    ];
    
    foreach ($required_methods as $class => $methods) {
        foreach ($methods as $method) {
            if (method_exists($class, $method)) {
                showResult(true, "Method {$class}::{$method}() พร้อมใช้งาน");
            } else {
                showResult(false, "ไม่พบ method {$class}::{$method}()");
            }
        }
    }
    
} catch (Exception $e) {
    showResult(false, "เกิดข้อผิดพลาดในการทดสอบ Import Function: " . $e->getMessage());
}

// 6. สร้างไฟล์ .htaccess สำหรับ uploads
echo "<h2>🔒 สร้างไฟล์ .htaccess สำหรับความปลอดภัย</h2>";

$htaccess_content = "# Prevent direct access to uploaded files
<Files *.csv>
    Order Allow,Deny
    Deny from all
</Files>

<Files *.sql>
    Order Allow,Deny
    Deny from all
</Files>

# Allow only specific file types
<FilesMatch \"\.(jpg|jpeg|png|gif|pdf|csv)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>
";

$htaccess_dirs = ['uploads', 'backups', 'logs'];
foreach ($htaccess_dirs as $dir) {
    $htaccess_file = "{$dir}/.htaccess";
    if (file_put_contents($htaccess_file, $htaccess_content)) {
        showResult(true, "สร้างไฟล์ {$htaccess_file} สำเร็จ");
    } else {
        showResult(false, "ไม่สามารถสร้างไฟล์ {$htaccess_file} ได้");
    }
}

// 7. สร้างไฟล์ index.php สำหรับป้องกัน directory browsing
echo "<h2>🛡️ สร้างไฟล์ป้องกัน Directory Browsing</h2>";

$index_content = "<?php\n// Directory access denied\nheader('HTTP/1.0 403 Forbidden');\nexit('Access denied');\n?>";

$protect_dirs = ['uploads', 'backups', 'logs', 'templates'];
foreach ($protect_dirs as $dir) {
    $index_file = "{$dir}/index.php";
    if (file_put_contents($index_file, $index_content)) {
        showResult(true, "สร้างไฟล์ป้องกัน {$index_file} สำเร็จ");
    } else {
        showResult(false, "ไม่สามารถสร้างไฟล์ป้องกัน {$index_file} ได้");
    }
}

// 8. สรุปและขั้นตอนถัดไป
echo "<h2>📋 สรุปและขั้นตอนถัดไป</h2>";
echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 15px 0;'>";

if (!isset($_SESSION['user_id'])) {
    echo "<h3>🎯 ขั้นตอนที่ 1: เข้าสู่ระบบ</h3>";
    echo "<ol>";
    echo "<li>ไปที่: <a href='login.php' target='_blank'>https://www.prima49.com/Customer/login.php</a></li>";
    echo "<li>ใส่ Username: <strong>admin</strong></li>";
    echo "<li>ใส่ Password: <strong>password</strong></li>";
    echo "<li>กดปุ่ม 'เข้าสู่ระบบ'</li>";
    echo "</ol>";
    
    echo "<h3>🎯 ขั้นตอนที่ 2: ทดสอบ Import</h3>";
    echo "<ol>";
    echo "<li>หลังเข้าสู่ระบบแล้ว ไปที่: <a href='import-export.php' target='_blank'>หน้า Import/Export</a></li>";
    echo "<li>เลือกแท็บ 'นำเข้าข้อมูล'</li>";
    echo "<li>ดาวน์โหลด template จากปุ่ม 'ดาวน์โหลด Template'</li>";
    echo "<li>กรอกข้อมูลในไฟล์ CSV</li>";
    echo "<li>อัปโหลดไฟล์และทดสอบ</li>";
    echo "</ol>";
} else {
    echo "<h3>✅ คุณเข้าสู่ระบบแล้ว</h3>";
    echo "<p>คุณสามารถไปที่ <a href='import-export.php' target='_blank'>หน้า Import/Export</a> ได้เลย</p>";
    
    echo "<h3>🧪 ลิงก์ทดสอบ:</h3>";
    echo "<ul>";
    echo "<li><a href='import-export.php' target='_blank'>📊 หน้า Import/Export</a></li>";
    echo "<li><a href='import-export.php?action=downloadTemplate&type=sales' target='_blank'>📄 ดาวน์โหลด Sales Template</a></li>";
    echo "<li><a href='import-export.php?action=downloadTemplate&type=customers_only' target='_blank'>📄 ดาวน์โหลด Customers Template</a></li>";
    echo "</ul>";
}

echo "</div>";

// 9. แสดงข้อมูล Template ที่สร้าง
echo "<h2>📁 ไฟล์ Template ที่สร้างแล้ว</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";

$template_files = glob('templates/*.csv');
if (!empty($template_files)) {
    echo "<ul>";
    foreach ($template_files as $file) {
        $size = filesize($file);
        $date = date('Y-m-d H:i:s', filemtime($file));
        echo "<li><strong>" . basename($file) . "</strong> - " . number_format($size) . " bytes - {$date}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>ไม่พบไฟล์ template</p>";
}

echo "</div>";

echo "<hr>";
echo "<p><small>🕒 สร้างเมื่อ: " . date('Y-m-d H:i:s') . "</small></p>";
?>

<style>
body { font-family: 'Sukhumvit Set', Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
