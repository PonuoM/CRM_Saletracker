<?php
/**
 * View Cron Logs
 * หน้าดู log การทำงานของ cron jobs
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📊 CRM SalesTracker - Cron Jobs Logs</h1>";

$logFile = 'logs/cron.log';
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 50;

echo "<div style='margin: 20px 0;'>";
echo "<a href='?lines=20' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>20 บรรทัด</a>";
echo "<a href='?lines=50' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>50 บรรทัด</a>";
echo "<a href='?lines=100' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>100 บรรทัด</a>";
echo "<a href='?lines=200' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>200 บรรทัด</a>";
echo "</div>";

if (file_exists($logFile)) {
    $fileSize = filesize($logFile);
    $lastModified = date('Y-m-d H:i:s', filemtime($logFile));
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📄 ข้อมูลไฟล์ Log</h3>";
    echo "<p><strong>ไฟล์:</strong> $logFile</p>";
    echo "<p><strong>ขนาด:</strong> " . number_format($fileSize) . " bytes</p>";
    echo "<p><strong>แก้ไขล่าสุด:</strong> $lastModified</p>";
    echo "</div>";
    
    // อ่านไฟล์ log
    $logContent = file_get_contents($logFile);
    
    if (!empty($logContent)) {
        // แยกบรรทัด
        $logLines = explode("\n", $logContent);
        $totalLines = count($logLines);
        
        // เอาบรรทัดล่าสุด
        $recentLines = array_slice($logLines, -$lines);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>📈 สถิติ Log</h3>";
        echo "<p><strong>จำนวนบรรทัดทั้งหมด:</strong> " . number_format($totalLines) . " บรรทัด</p>";
        echo "<p><strong>แสดง:</strong> " . count($recentLines) . " บรรทัดล่าสุด</p>";
        echo "</div>";
        
        echo "<h3>📋 Log ล่าสุด ($lines บรรทัด)</h3>";
        echo "<div style='background: #000; color: #00ff00; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 600px; overflow-y: auto;'>";
        
        foreach ($recentLines as $line) {
            if (trim($line) !== '') {
                // เพิ่มสีให้กับข้อความ
                $line = htmlspecialchars($line);
                
                // เพิ่มสีสำหรับ status
                if (strpos($line, '✅') !== false) {
                    $line = "<span style='color: #00ff00;'>$line</span>";
                } elseif (strpos($line, '❌') !== false) {
                    $line = "<span style='color: #ff0000;'>$line</span>";
                } elseif (strpos($line, '⚠️') !== false) {
                    $line = "<span style='color: #ffff00;'>$line</span>";
                } elseif (strpos($line, 'Starting') !== false || strpos($line, 'Completed') !== false) {
                    $line = "<span style='color: #00ffff;'>$line</span>";
                } elseif (preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
                    $line = "<span style='color: #ffff00;'>$line</span>";
                }
                
                echo $line . "<br>";
            }
        }
        echo "</div>";
        
        // ค้นหาข้อมูลสำคัญ
        echo "<h3>🔍 สรุปการทำงานล่าสุด</h3>";
        
        // หาบรรทัดที่มี basket_management
        $basketLines = array_filter($logLines, function($line) {
            return strpos($line, 'basket_management') !== false || 
                   strpos($line, 'New recalled') !== false ||
                   strpos($line, 'Customer basket management completed') !== false;
        });
        
        if (!empty($basketLines)) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🗂️ Basket Management</h4>";
            foreach (array_slice($basketLines, -5) as $line) {
                echo "<p style='margin: 5px 0; font-family: monospace;'>" . htmlspecialchars($line) . "</p>";
            }
            echo "</div>";
        }
        
        // หาบรรทัดที่มี grade_update
        $gradeLines = array_filter($logLines, function($line) {
            return strpos($line, 'grade_update') !== false || 
                   strpos($line, 'Customer grade update completed') !== false;
        });
        
        if (!empty($gradeLines)) {
            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>📊 Grade Update</h4>";
            foreach (array_slice($gradeLines, -3) as $line) {
                echo "<p style='margin: 5px 0; font-family: monospace;'>" . htmlspecialchars($line) . "</p>";
            }
            echo "</div>";
        }
        
        // หาข้อผิดพลาด
        $errorLines = array_filter($logLines, function($line) {
            return strpos($line, 'Error') !== false || 
                   strpos($line, 'Failed') !== false ||
                   strpos($line, '❌') !== false;
        });
        
        if (!empty($errorLines)) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>❌ ข้อผิดพลาด</h4>";
            foreach (array_slice($errorLines, -5) as $line) {
                echo "<p style='margin: 5px 0; font-family: monospace; color: #721c24;'>" . htmlspecialchars($line) . "</p>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>✅ ไม่พบข้อผิดพลาด</h4>";
            echo "<p>ระบบทำงานปกติ</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px;'>";
        echo "<h3>⚠️ ไฟล์ Log ว่าง</h3>";
        echo "<p>ยังไม่มีการบันทึก log หรือ cron jobs ยังไม่ได้รัน</p>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ ไม่พบไฟล์ Log</h3>";
    echo "<p>ไฟล์ <code>$logFile</code> ไม่มีอยู่</p>";
    echo "<p>อาจเป็นเพราะ:</p>";
    echo "<ul>";
    echo "<li>Cron jobs ยังไม่ได้รัน</li>";
    echo "<li>โฟลเดอร์ logs ไม่มีสิทธิ์เขียน</li>";
    echo "<li>Path ของ log file ไม่ถูกต้อง</li>";
    echo "</ul>";
    echo "</div>";
}

// ปุ่มรีเฟรช
echo "<div style='margin: 20px 0;'>";
echo "<a href='?' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 รีเฟรช</a>";
echo "<a href='test_cron_jobs.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 ทดสอบ Cron</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 หน้าหลัก</a>";
echo "</div>";

// แสดงคำแนะนำ
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>💡 คำแนะนำ</h3>";
echo "<ul>";
echo "<li><strong>ดู Log ประจำวัน:</strong> เข้ามาหน้านี้ทุกเช้าเพื่อดูผลการทำงาน</li>";
echo "<li><strong>ตรวจสอบข้อผิดพลาด:</strong> หากมีสีแดง (❌) ให้ตรวจสอบ</li>";
echo "<li><strong>Cron Jobs รันเวลา:</strong> 01:20 น. ทุกวัน</li>";
echo "<li><strong>หากไม่มี Log:</strong> ตรวจสอบการตั้งค่า cron jobs ใน cPanel</li>";
echo "</ul>";
echo "</div>";

?>
