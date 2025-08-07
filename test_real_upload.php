<?php
/**
 * ทดสอบ Real HTTP Upload
 * จำลอง HTTP POST แบบจริง
 */

session_start();

// จำลอง session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role_name'] = 'super_admin';

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 ทดสอบ Real HTTP Upload</h1>";
echo "<hr>";

// ตรวจสอบว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    echo "<h2>📤 ข้อมูล HTTP Upload ที่ได้รับ</h2>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>📋 \$_FILES Information:</strong><br>";
    echo "<pre>" . print_r($_FILES['csv_file'], true) . "</pre>";
    echo "</div>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>📋 \$_SERVER Information:</strong><br>";
    echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "<br>";
    echo "CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'Not set') . "<br>";
    echo "</div>";
    
    $file = $_FILES['csv_file'];
    
    // ตรวจสอบ upload errors
    echo "<h3>🔍 ตรวจสอบ Upload Status</h3>";
    
    $upload_errors = [
        UPLOAD_ERR_OK => 'No error',
        UPLOAD_ERR_INI_SIZE => 'File too large (php.ini)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form)',
        UPLOAD_ERR_PARTIAL => 'Partial upload',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
        UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
        UPLOAD_ERR_EXTENSION => 'Extension stopped upload'
    ];
    
    $error_code = $file['error'];
    $error_message = $upload_errors[$error_code] ?? 'Unknown error';
    
    if ($error_code === UPLOAD_ERR_OK) {
        echo "<div style='color: green;'>✅ Upload Status: {$error_message}</div>";
    } else {
        echo "<div style='color: red;'>❌ Upload Error: {$error_message} (Code: {$error_code})</div>";
    }
    
    // ตรวจสอบไฟล์
    echo "<h3>📄 ตรวจสอบไฟล์</h3>";
    
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>File Details:</strong><br>";
    echo "Name: " . htmlspecialchars($file['name']) . "<br>";
    echo "Type: " . htmlspecialchars($file['type']) . "<br>";
    echo "Size: " . number_format($file['size']) . " bytes<br>";
    echo "Tmp Name: " . htmlspecialchars($file['tmp_name']) . "<br>";
    echo "</div>";
    
    // ตรวจสอบไฟล์ tmp
    if (file_exists($file['tmp_name'])) {
        echo "<div style='color: green;'>✅ Temp file exists</div>";
        echo "<div>Temp file size: " . filesize($file['tmp_name']) . " bytes</div>";
        echo "<div>Temp file readable: " . (is_readable($file['tmp_name']) ? 'Yes' : 'No') . "</div>";
    } else {
        echo "<div style='color: red;'>❌ Temp file does not exist</div>";
    }
    
    // ตรวจสอบ is_uploaded_file
    if (is_uploaded_file($file['tmp_name'])) {
        echo "<div style='color: green;'>✅ is_uploaded_file() confirms this is a real upload</div>";
    } else {
        echo "<div style='color: red;'>❌ is_uploaded_file() says this is NOT a real upload</div>";
    }
    
    // ลองเรียก ImportExportController
    if ($error_code === UPLOAD_ERR_OK && file_exists($file['tmp_name'])) {
        
        echo "<h3>🎮 ทดสอบ ImportExportController</h3>";
        
        try {
            require_once 'config/config.php';
            require_once 'app/controllers/ImportExportController.php';
            
            $controller = new ImportExportController();
            
            echo "<div style='color: green;'>✅ Controller loaded</div>";
            
            // Capture output
            ob_start();
            $controller->importSales();
            $output = ob_get_clean();
            
            echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>📊 Controller Output:</strong><br>";
            
            // ตรวจสอบว่าเป็น JSON หรือไม่
            $json_result = json_decode($output, true);
            if ($json_result !== null) {
                echo "<pre>" . json_encode($json_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                
                if (isset($json_result['success']) && $json_result['success'] > 0) {
                    echo "<div style='color: green; margin-top: 10px;'>🎉 Import สำเร็จ!</div>";
                } else {
                    echo "<div style='color: orange; margin-top: 10px;'>⚠️ Import ไม่สำเร็จ</div>";
                    if (!empty($json_result['errors'])) {
                        echo "<div style='color: red;'>Errors:</div>";
                        foreach ($json_result['errors'] as $error) {
                            echo "<div style='color: red;'>• " . htmlspecialchars($error) . "</div>";
                        }
                    }
                }
            } else {
                echo "<div style='color: orange;'>⚠️ Output is not JSON:</div>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Controller Error: " . $e->getMessage() . "</div>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    
    echo "<hr>";
    echo "<h3>📋 สรุปการทดสอบ</h3>";
    
    if ($error_code === UPLOAD_ERR_OK && file_exists($file['tmp_name'])) {
        echo "<div style='color: green;'>✅ File upload ทำงานได้ปกติ</div>";
        echo "<div>การทดสอบนี้แสดงว่าปัญหาไม่ได้อยู่ที่ file upload mechanism</div>";
    } else {
        echo "<div style='color: red;'>❌ File upload มีปัญหา</div>";
        echo "<div>ต้องแก้ไข server configuration หรือ PHP settings</div>";
    }
    
} else {
    // แสดงฟอร์มสำหรับทดสอบ
    echo "<h2>📋 แบบฟอร์มทดสอบ Real HTTP Upload</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 15px 0;'>";
    echo "<p><strong>📤 ใช้ฟอร์มนี้เพื่อทดสอบ HTTP upload จริง:</strong></p>";
    
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label><strong>เลือกไฟล์ CSV:</strong></label><br>";
    echo "<input type='file' name='csv_file' accept='.csv' required style='margin: 5px 0;'>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🧪 ทดสอบ Upload";
    echo "</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<p><small>💡 ใช้ไฟล์ CSV ใดก็ได้ หรือสร้างไฟล์ทดสอบด้วยข้อมูล:</small></p>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace;'>";
    echo "ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล<br>";
    echo "ทดสอบ,Real,081-111-1111,test@real.com";
    echo "</div>";
    echo "</div>";
    
    echo "<h2>🔗 ลิงก์ทดสอบอื่นๆ</h2>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<ul>";
    echo "<li><a href='import-export.php?action=downloadTemplate&type=sales' target='_blank'>📄 Download Sales Template</a></li>";
    echo "<li><a href='import-export.php?action=downloadTemplate&type=customers_only' target='_blank'>📄 Download Customers Template</a></li>";
    echo "<li><a href='import-export.php' target='_blank'>📊 หน้า Import/Export หลัก</a></li>";
    echo "<li><a href='test_upload_fixed.php' target='_blank'>🧪 Test Upload Fixed</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>📋 ขั้นตอนการทดสอบ</h2>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>🎯 วิธีการทดสอบที่แนะนำ:</h3>";
    echo "<ol>";
    echo "<li><strong>ใช้ฟอร์มด้านบน</strong> - อัปโหลดไฟล์ CSV จริง</li>";
    echo "<li><strong>ตรวจสอบผลลัพธ์</strong> - ดูว่า move_uploaded_file() ทำงานหรือไม่</li>";
    echo "<li><strong>ทดสอบหน้าจริง</strong> - ไปที่ import-export.php</li>";
    echo "<li><strong>เปรียบเทียบผลลัพธ์</strong> - ดูความแตกต่าง</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>🕒 ทดสอบเมื่อ: " . date('Y-m-d H:i:s') . "</small></p>";
?>

<style>
body { 
    font-family: 'Sukhumvit Set', Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6; 
    background: #f8f9fa;
}
h1, h2, h3 { color: #333; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
