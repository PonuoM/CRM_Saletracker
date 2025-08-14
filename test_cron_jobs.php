<?php
/**
 * Test Cron Jobs Script
 * สคริปต์สำหรับทดสอบ cron jobs ทั้งหมด
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🕐 CRM SalesTracker - Cron Jobs Testing</h1>";

// ตรวจสอบไฟล์ cron ที่มีอยู่
$cronDir = 'cron';
$cronFiles = [
    'run_all_jobs.php',
    'customer_recall_workflow.php',
    'update_customer_grades.php',
    'update_customer_temperatures.php',
    'update_call_followups.php',
    'send_recall_notifications.php'
];

echo "<h2>📁 ตรวจสอบไฟล์ Cron</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ไฟล์</th><th>สถานะ</th><th>ขนาด</th><th>แก้ไขล่าสุด</th></tr>";

foreach ($cronFiles as $file) {
    $filePath = $cronDir . '/' . $file;
    echo "<tr>";
    echo "<td>$file</td>";
    
    if (file_exists($filePath)) {
        echo "<td style='color: green;'>✅ มีอยู่</td>";
        echo "<td>" . number_format(filesize($filePath)) . " bytes</td>";
        echo "<td>" . date('Y-m-d H:i:s', filemtime($filePath)) . "</td>";
    } else {
        echo "<td style='color: red;'>❌ ไม่พบ</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
    }
    echo "</tr>";
}
echo "</table>";

// ตรวจสอบไฟล์ที่ไม่ควรมีใน cron jobs
echo "<h2>⚠️ ตรวจสอบไฟล์ที่ไม่ควรรันเป็น Cron</h2>";
$problematicFiles = [
    'fix_import_calculation_issue.php',
    'fix_orders_total_amount.php',
    'check_order_items_data.php'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ไฟล์</th><th>สถานะ</th><th>คำแนะนำ</th></tr>";

foreach ($problematicFiles as $file) {
    echo "<tr>";
    echo "<td>$file</td>";
    
    if (file_exists($file)) {
        echo "<td style='color: orange;'>⚠️ พบไฟล์</td>";
        echo "<td>ควรลบออกจาก cron jobs (เป็น one-time fix script)</td>";
    } else {
        echo "<td style='color: green;'>✅ ไม่พบ</td>";
        echo "<td>ดี - ไฟล์ถูกลบแล้วหรือไม่มีอยู่</td>";
    }
    echo "</tr>";
}
echo "</table>";

// ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>🔌 ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p style='color: green;'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
    
    // ตรวจสอบตารางที่จำเป็น
    $requiredTables = [
        'customers',
        'orders',
        'customer_activities',
        'cron_job_logs',
        'cron_job_settings',
        'activity_logs'
    ];
    
    echo "<h3>📊 ตรวจสอบตารางฐานข้อมูล</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ตาราง</th><th>สถานะ</th><th>จำนวนแถว</th></tr>";
    
    foreach ($requiredTables as $table) {
        echo "<tr>";
        echo "<td>$table</td>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<td style='color: green;'>✅ มีอยู่</td>";
            echo "<td>" . number_format($result['count']) . " แถว</td>";
        } catch (Exception $e) {
            echo "<td style='color: red;'>❌ ไม่พบ</td>";
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage() . "</p>";
}

// ทดสอบรัน cron job หลัก
echo "<h2>🚀 ทดสอบรัน Cron Jobs</h2>";

if (isset($_GET['test_cron']) && $_GET['test_cron'] === 'yes') {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🔄 กำลังทดสอบ Cron Jobs (Web Version)</h3>";
    
    try {
        // Load required files
        require_once 'config/config.php';
        require_once 'app/core/Database.php';
        
        // Check if CronJobService exists
        if (file_exists('app/services/CronJobService.php')) {
            require_once 'app/services/CronJobService.php';
            
            echo "<pre>";
            $startTime = microtime(true);
            
            // Initialize cron job service
            $cronService = new CronJobService();
            
            echo "=== CRM SalesTracker Cron Jobs (Web Test) ===\n";
            echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Run all jobs
            $results = $cronService->runAllJobs();
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            if ($results['success']) {
                echo "✅ All cron jobs completed successfully in {$executionTime} seconds\n\n";
                
                // แสดงผลสรุป
                if (isset($results['results'])) {
                    foreach ($results['results'] as $jobName => $result) {
                        if (isset($result['success'])) {
                            $status = $result['success'] ? '✅' : '❌';
                            echo "{$status} {$jobName}: ";
                            
                            if ($result['success']) {
                                if (isset($result['updated_count'])) {
                                    echo "Updated {$result['updated_count']} records";
                                } elseif (isset($result['recall_count'])) {
                                    echo "Found {$result['recall_count']} customers";
                                } elseif (isset($result['notification_count'])) {
                                    echo "Sent {$result['notification_count']} notifications";
                                } elseif (isset($result['new_customers_recalled'])) {
                                    echo "New recalled: {$result['new_customers_recalled']}, Existing recalled: {$result['existing_customers_recalled']}, Moved to distribution: {$result['moved_to_distribution']}";
                                } elseif (isset($result['cleanup_results'])) {
                                    $cleanup = $result['cleanup_results'];
                                    echo "Cleaned up: {$cleanup['deleted_logs']} logs, {$cleanup['deleted_notifications']} notifications, {$cleanup['deleted_backups']} backups";
                                } else {
                                    echo "Success";
                                }
                            } else {
                                echo "Error: " . ($result['error'] ?? 'Unknown error');
                            }
                            echo "\n";
                        }
                    }
                }
            } else {
                echo "❌ Cron jobs failed: " . ($results['error'] ?? 'Unknown error') . "\n";
            }
            
            echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
            echo "=== End of Cron Jobs ===\n";
            echo "</pre>";
            
        } else {
            echo "<p style='color: red;'>❌ ไม่พบไฟล์ CronJobService.php</p>";
            echo "<p>ทดสอบ individual cron jobs แทน:</p>";
            
            // Test individual cron files
            $individualCronFiles = [
                'update_customer_grades.php' => 'อัปเดตเกรดลูกค้า',
                'update_customer_temperatures.php' => 'อัปเดตอุณหภูมิลูกค้า',
                'customer_recall_workflow.php' => 'ระบบดึงลูกค้ากลับ'
            ];
            
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🔧 ทดสอบ Individual Cron Jobs</h4>";
            
            foreach ($individualCronFiles as $file => $description) {
                $filePath = 'cron/' . $file;
                echo "<p><strong>$description ($file):</strong> ";
                
                if (file_exists($filePath)) {
                    echo "<span style='color: green;'>✅ ไฟล์พร้อมใช้งาน</span>";
                } else {
                    echo "<span style='color: red;'>❌ ไม่พบไฟล์</span>";
                }
                echo "</p>";
            }
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<pre>";
        echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    }
    
    echo "</div>";
} else {
    echo "<p><a href='?test_cron=yes' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 ทดสอบรัน Cron Jobs</a></p>";
    echo "<p><small>⚠️ การทดสอบนี้จะรัน cron jobs จริง อาจใช้เวลาสักครู่</small></p>";
}

// แสดงคำแนะนำการตั้งค่า cron
echo "<h2>⚙️ คำแนะนำการตั้งค่า Cron Jobs</h2>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Cron Job ที่แนะนำ (Master Job)</h3>";
echo "<pre>";
echo "# รันทุกวันเวลา 01:20 น. พร้อม logging\n";
echo "20 1 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/Customer/cron/run_all_jobs.php >> /home/primacom/domains/prima49.com/public_html/Customer/logs/cron.log 2>&1";
echo "</pre>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>❌ Cron Jobs ที่ควรลบ</h3>";
echo "<pre>";
echo "# ลบ cron jobs เหล่านี้ออก:\n\n";
echo "# 1. Path ไม่ถูกต้อง\n";
echo "0 * * * * php /path/to/cron/customer_recall_workflow.php\n";
echo "* 3 * * * php /path/to/cron/customer_recall_workflow.php\n\n";
echo "# 2. ไฟล์ fix ที่ไม่ควรรันซ้ำ\n";
echo "20 2 * * 0 .../fix_import_calculation_issue.php\n";
echo "30 2 * * 0 .../fix_orders_total_amount.php\n\n";
echo "# 3. ไฟล์ที่ไม่มีอยู่\n";
echo "10 2 * * * .../check_order_items_data.php\n\n";
echo "# 4. Cron job ซ้ำ\n";
echo "0 1 * * * .../run_all_jobs.php (ลบอันนี้ เก็บอันที่มี logging)";
echo "</pre>";
echo "</div>";

// ตรวจสอบ logs directory
echo "<h2>📁 ตรวจสอบโฟลเดอร์ Logs</h2>";
$logsDir = 'logs';

if (!is_dir($logsDir)) {
    echo "<p style='color: orange;'>⚠️ ไม่พบโฟลเดอร์ logs</p>";
    echo "<p><a href='?create_logs=yes' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📁 สร้างโฟลเดอร์ logs</a></p>";
} else {
    echo "<p style='color: green;'>✅ พบโฟลเดอร์ logs</p>";
    
    // แสดงไฟล์ log ที่มีอยู่
    $logFiles = glob($logsDir . '/*.log');
    if (!empty($logFiles)) {
        echo "<h3>📄 ไฟล์ Log ที่มีอยู่</h3>";
        echo "<ul>";
        foreach ($logFiles as $logFile) {
            $size = filesize($logFile);
            $modified = date('Y-m-d H:i:s', filemtime($logFile));
            echo "<li>" . basename($logFile) . " (" . number_format($size) . " bytes, แก้ไข: $modified)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>ไม่พบไฟล์ log</p>";
    }
}

// สร้างโฟลเดอร์ logs หากไม่มี
if (isset($_GET['create_logs']) && $_GET['create_logs'] === 'yes') {
    if (!is_dir($logsDir)) {
        if (mkdir($logsDir, 0755, true)) {
            echo "<p style='color: green;'>✅ สร้างโฟลเดอร์ logs สำเร็จ</p>";
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถสร้างโฟลเดอร์ logs</p>";
        }
    }
}

echo "<h2>📋 สรุปและขั้นตอนต่อไป</h2>";
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📝 สิ่งที่ต้องทำ</h3>";
echo "<ol>";
echo "<li><strong>อัปเดตฐานข้อมูล</strong> - รัน update_database_schema.php ก่อน</li>";
echo "<li><strong>ทดสอบ Cron Jobs</strong> - กดปุ่มทดสอบข้างต้น</li>";
echo "<li><strong>ลบ Cron Jobs ที่ไม่ถูกต้อง</strong> - เข้าไป cPanel → Cron Jobs</li>";
echo "<li><strong>ตั้งค่า Master Cron Job</strong> - เพิ่ม cron job ใหม่ตามที่แนะนำ</li>";
echo "<li><strong>ตรวจสอบผลลัพธ์</strong> - ดู log หลังจาก cron job รัน</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='update_database_schema.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔧 อัปเดต DB ก่อน</a>";
echo "<a href='view_cron_logs.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📄 ดู Log Files</a>";
echo "<a href='view_cron_database.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🗄️ ดูฐานข้อมูล</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 กลับหน้าหลัก</a>";
echo "</div>";

?>
