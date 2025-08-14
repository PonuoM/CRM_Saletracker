<?php
/**
 * CRM SalesTracker - Project Cleanup Script
 * สคริปต์สำหรับจัดระเบียบและลบไฟล์ที่ไม่จำเป็น
 * 
 * คำเตือน: สคริปต์นี้จะลบไฟล์จำนวนมาก กรุณาสำรองข้อมูลก่อนใช้งาน
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧹 CRM SalesTracker - Project Cleanup</h1>";
echo "<p><strong>คำเตือน:</strong> สคริปต์นี้จะลบไฟล์จำนวนมาก กรุณาสำรองข้อมูลก่อนใช้งาน</p>";

// ตรวจสอบการยืนยัน
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo '<p><a href="?confirm=yes" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">⚠️ ยืนยันการลบไฟล์</a></p>';
    echo '<p><a href="?" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">❌ ยกเลิก</a></p>';
    exit;
}

// สร้างโฟลเดอร์สำรอง
$backupDir = 'archive_' . date('Y-m-d_H-i-s');
$docsBackupDir = $backupDir . '/documentation';

if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "<p>✅ สร้างโฟลเดอร์สำรอง: $backupDir</p>";
}

if (!file_exists($docsBackupDir)) {
    mkdir($docsBackupDir, 0755, true);
    echo "<p>✅ สร้างโฟลเดอร์สำรองเอกสาร: $docsBackupDir</p>";
}

// รายการไฟล์ที่ต้องลบ
$filesToDelete = [
    // Debug files
    'debug-import-export.php',
    'debug-import.php',
    'debug-service.php',
    'debug_500_error.php',
    'check-syntax.php',
    'investigate_import_issue.php',
    'investigate_total_purchase_issue.php',
    'simple_team_test.php',
    'simple_test_cron.php',
    'simple_workflow_test.php',
    'quick_test_call_followup.php',
    'test_admin_page_role.php',
    'manual_test_cron.php',
    'ssl_diagnostic.php',
    
    // Fix files
    'fix_admin_views.php',
    'fix_call_followup_data.php',
    'fix_cron_issues.php',
    'fix_customer_activities_schema.php',
    'fix_customer_grade_and_total_purchase.php',
    'fix_customers_500_error.php',
    'fix_customerservice_parameter_issue.php',
    'fix_database_schema.php',
    'fix_import_calculation_issue.php',
    'fix_import_issues.php',
    'fix_orders_total_amount.php',
    'fix_schema_and_performance.php',
    'fix_specific_orders.php',
    'fix_supervisor_customers.php',
    'fix_team_data.php',
    'fix_team_page.php',
    'fix_total_purchase_amount_issue.php',
    'fix_total_purchase_simple.php',
    'production_fix.php',
    'quick_fix_call_followup.php',
    
    // Setup files
    'add_admin_page_role.php',
    'apply_call_system_schema.php',
    'apply_workflow_schema.php',
    'create_supervisor_customers.php',
    'create_utf8_template.php',
    'optimize_database.php',
    
    // Production files (used already)
    'production_deployment.php',
    'production_show.php',
    'documentation_training.php',
    
    // SQL files
    'add_activity_date_column.sql',
    'add_activity_date_column_minimal.sql',
    'add_activity_date_column_simple.sql',
    'add_admin_page_role.sql',
    'add_call_followup_system.sql',
    'add_supervisor_team_management.sql',
    'add_time_columns.sql',
    'add_workflow_columns.sql',
    'create_products_table.sql',
    'create_sample_data.sql',
    'create_sample_data_simple.sql',
    'fix_customer_time_columns.sql',
    'fix_order_activities_schema.sql'
];

// รายการไฟล์ .md ที่ต้องย้ายไปสำรอง (ยกเว้นไฟล์หลัก)
$keepMdFiles = [
    'README.md',
    'CRM_PROJECT_COMPLETE_DOCUMENTATION.md',
    'CRM_DEVELOPMENT_HISTORY.md',
    'CRM_TROUBLESHOOTING_GUIDE.md',
    'FILES_TO_DELETE_ANALYSIS.md',
    'ขั้นตอนการทดสอบระบบ_Import_Export.md'
];

// ฟังก์ชันสำหรับย้ายไฟล์ไปสำรอง
function moveToBackup($file, $backupDir) {
    if (file_exists($file)) {
        $backupFile = $backupDir . '/' . basename($file);
        if (rename($file, $backupFile)) {
            echo "<p>📦 ย้ายไฟล์ไปสำรอง: $file → $backupFile</p>";
            return true;
        } else {
            echo "<p>❌ ไม่สามารถย้ายไฟล์: $file</p>";
            return false;
        }
    }
    return false;
}

// ฟังก์ชันสำหรับลบไฟล์
function deleteFile($file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p>🗑️ ลบไฟล์: $file</p>";
            return true;
        } else {
            echo "<p>❌ ไม่สามารถลบไฟล์: $file</p>";
            return false;
        }
    }
    return false;
}

echo "<h2>📦 ขั้นตอนที่ 1: ย้ายไฟล์ไปสำรอง</h2>";

// ย้ายไฟล์ที่ต้องลบไปสำรองก่อน
$movedCount = 0;
foreach ($filesToDelete as $file) {
    if (moveToBackup($file, $backupDir)) {
        $movedCount++;
    }
}

echo "<h2>📄 ขั้นตอนที่ 2: จัดการไฟล์ .md</h2>";

// ย้ายไฟล์ .md ที่ไม่ต้องการไปสำรอง
$mdFiles = glob('*.md');
$mdMovedCount = 0;

foreach ($mdFiles as $mdFile) {
    $filename = basename($mdFile);
    if (!in_array($filename, $keepMdFiles)) {
        if (moveToBackup($mdFile, $docsBackupDir)) {
            $mdMovedCount++;
        }
    }
}

echo "<h2>📁 ขั้นตอนที่ 3: สร้างโฟลเดอร์เอกสาร</h2>";

// สร้างโฟลเดอร์ docs และย้ายไฟล์เอกสารหลัก
$docsDir = 'docs';
if (!file_exists($docsDir)) {
    mkdir($docsDir, 0755, true);
    echo "<p>✅ สร้างโฟลเดอร์: $docsDir</p>";
}

// ย้ายไฟล์เอกสารหลักไป docs/
$mainDocs = [
    'CRM_PROJECT_COMPLETE_DOCUMENTATION.md',
    'CRM_DEVELOPMENT_HISTORY.md',
    'CRM_TROUBLESHOOTING_GUIDE.md',
    'FILES_TO_DELETE_ANALYSIS.md'
];

foreach ($mainDocs as $doc) {
    if (file_exists($doc)) {
        $newPath = $docsDir . '/' . $doc;
        if (rename($doc, $newPath)) {
            echo "<p>📋 ย้ายเอกสารไป docs/: $doc → $newPath</p>";
        }
    }
}

echo "<h2>📊 สรุปผลการดำเนินการ</h2>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📈 สถิติการจัดระเบียบ</h3>";
echo "<ul>";
echo "<li><strong>ไฟล์ที่ย้ายไปสำรอง:</strong> $movedCount ไฟล์</li>";
echo "<li><strong>ไฟล์ .md ที่ย้ายไปสำรอง:</strong> $mdMovedCount ไฟล์</li>";
echo "<li><strong>โฟลเดอร์สำรอง:</strong> $backupDir</li>";
echo "<li><strong>โฟลเดอร์เอกสาร:</strong> $docsDir</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ ไฟล์หลักที่เหลืออยู่</h3>";
echo "<ul>";

// แสดงไฟล์หลักที่เหลือ
$coreFiles = [
    'index.php',
    'admin.php', 
    'customers.php',
    'orders.php',
    'dashboard.php',
    'dashboard_supervisor.php',
    'reports.php',
    'import-export.php',
    'login.php',
    'logout.php',
    'README.md'
];

foreach ($coreFiles as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file</li>";
    } else {
        echo "<li>❌ $file (ไม่พบ)</li>";
    }
}
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📁 โครงสร้างโฟลเดอร์ที่เหลือ</h3>";
echo "<ul>";

$directories = ['app', 'api', 'assets', 'config', 'cron', 'database', 'templates', 'docs', $backupDir];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $fileCount = count(glob($dir . '/*'));
        echo "<li>📁 $dir/ ($fileCount ไฟล์)</li>";
    }
}
echo "</ul>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>⚠️ หมายเหตุสำคัญ</h3>";
echo "<ul>";
echo "<li>ไฟล์ทั้งหมดที่ลบได้ถูกย้ายไปโฟลเดอร์ <strong>$backupDir</strong></li>";
echo "<li>หากต้องการไฟล์เหล่านั้นกลับมา สามารถคัดลอกจากโฟลเดอร์สำรองได้</li>";
echo "<li>เอกสารหลักถูกย้ายไปโฟลเดอร์ <strong>docs/</strong></li>";
echo "<li>ควรทดสอบระบบให้แน่ใจว่าทำงานปกติหลังจากการจัดระเบียบ</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎉 การจัดระเบียบเสร็จสิ้น</h2>";
echo "<p>โครงการได้รับการจัดระเบียบเรียบร้อยแล้ว ระบบควรทำงานได้ปกติ</p>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🏠 กลับหน้าหลัก</a>";
echo "<a href='docs/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📚 ดูเอกสาร</a>";
echo "</div>";

// สร้างไฟล์ log การจัดระเบียบ
$logContent = "CRM SalesTracker - Project Cleanup Log\n";
$logContent .= "วันที่: " . date('Y-m-d H:i:s') . "\n";
$logContent .= "ไฟล์ที่ย้ายไปสำรอง: $movedCount ไฟล์\n";
$logContent .= "ไฟล์ .md ที่ย้ายไปสำรอง: $mdMovedCount ไฟล์\n";
$logContent .= "โฟลเดอร์สำรอง: $backupDir\n";
$logContent .= "\nรายการไฟล์ที่ย้าย:\n";
foreach ($filesToDelete as $file) {
    if (file_exists($backupDir . '/' . basename($file))) {
        $logContent .= "- $file\n";
    }
}

file_put_contents($backupDir . '/cleanup_log.txt', $logContent);
echo "<p>📝 สร้างไฟล์ log: {$backupDir}/cleanup_log.txt</p>";

?>
