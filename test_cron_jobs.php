<?php
/**
 * Test Cron Jobs System
 * ทดสอบระบบงานอัตโนมัติ
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Load services
require_once 'app/services/CronJobService.php';

echo "<h1>ทดสอบระบบ Cron Jobs</h1>";

// Initialize service
$cronService = new CronJobService();

// Test 1: Customer Grade Update
echo "<h2>1. ทดสอบการอัปเดตเกรดลูกค้า</h2>";
try {
    $result = $cronService->updateCustomerGrades();
    
    if ($result['success']) {
        echo "<p>✅ อัปเดตเกรดลูกค้าสำเร็จ: {$result['updated_count']} รายการ</p>";
        
        if (!empty($result['changes'])) {
            echo "<h3>การเปลี่ยนแปลงเกรด:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ลูกค้า</th><th>เกรดเดิม</th><th>เกรดใหม่</th><th>ยอดซื้อ 6 เดือน</th></tr>";
            
            foreach ($result['changes'] as $change) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($change['customer_name']) . "</td>";
                echo "<td>" . htmlspecialchars($change['old_grade']) . "</td>";
                echo "<td>" . htmlspecialchars($change['new_grade']) . "</td>";
                echo "<td>฿" . number_format($change['total_purchase'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>ไม่มีการเปลี่ยนแปลงเกรด</p>";
        }
    } else {
        echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($result['error']) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Customer Temperature Update
echo "<h2>2. ทดสอบการอัปเดตอุณหภูมิลูกค้า</h2>";
try {
    $result = $cronService->updateCustomerTemperatures();
    
    if ($result['success']) {
        echo "<p>✅ อัปเดตอุณหภูมิลูกค้าสำเร็จ: {$result['updated_count']} รายการ</p>";
        
        if (!empty($result['changes'])) {
            echo "<h3>การเปลี่ยนแปลงอุณหภูมิ:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ลูกค้า</th><th>อุณหภูมิเดิม</th><th>อุณหภูมิใหม่</th><th>วันที่ไม่ได้ติดต่อ</th><th>ติดต่อล่าสุด</th></tr>";
            
            foreach ($result['changes'] as $change) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($change['customer_name']) . "</td>";
                echo "<td>" . htmlspecialchars($change['old_temperature']) . "</td>";
                echo "<td>" . htmlspecialchars($change['new_temperature']) . "</td>";
                echo "<td>" . $change['days_since_contact'] . " วัน</td>";
                echo "<td>" . htmlspecialchars($change['last_contact']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>ไม่มีการเปลี่ยนแปลงอุณหภูมิ</p>";
        }
    } else {
        echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($result['error']) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Customer Recall List
echo "<h2>3. ทดสอบการสร้างรายการลูกค้าที่ต้องติดตาม</h2>";
try {
    $result = $cronService->createCustomerRecallList();
    
    if ($result['success']) {
        echo "<p>✅ สร้างรายการลูกค้าที่ต้องติดตามสำเร็จ: {$result['recall_count']} รายการ</p>";
        
        if ($result['recall_count'] > 0) {
            echo "<h3>ลูกค้าที่ต้องติดตาม (10 รายการแรก):</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ลูกค้า</th><th>เบอร์โทร</th><th>เกรด</th><th>อุณหภูมิ</th><th>วันที่ไม่ได้ติดต่อ</th><th>ยอดซื้อรวม</th></tr>";
            
            $customers = array_slice($result['customers'], 0, 10);
            foreach ($customers as $customer) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($customer['name']) . "</td>";
                echo "<td>" . htmlspecialchars($customer['phone']) . "</td>";
                echo "<td>" . htmlspecialchars($customer['grade']) . "</td>";
                echo "<td>" . htmlspecialchars($customer['temperature']) . "</td>";
                echo "<td>" . $customer['days_since_contact'] . " วัน</td>";
                echo "<td>฿" . number_format($customer['total_spent'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($result['recall_count'] > 10) {
                echo "<p>และอีก " . ($result['recall_count'] - 10) . " รายการ</p>";
            }
        }
    } else {
        echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($result['error']) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Send Notifications
echo "<h2>4. ทดสอบการส่งการแจ้งเตือน</h2>";
try {
    $result = $cronService->sendCustomerRecallNotifications();
    
    if ($result['success']) {
        echo "<p>✅ ส่งการแจ้งเตือนสำเร็จ: {$result['notification_count']} รายการ ถึง {$result['recipient_count']} ผู้ใช้</p>";
    } else {
        echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($result['error']) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Data Cleanup
echo "<h2>5. ทดสอบการทำความสะอาดข้อมูล</h2>";
try {
    $result = $cronService->cleanupOldData();
    
    if ($result['success']) {
        $cleanup = $result['cleanup_results'];
        echo "<p>✅ ทำความสะอาดข้อมูลสำเร็จ:</p>";
        echo "<ul>";
        echo "<li>ลบ activity logs: {$cleanup['deleted_logs']} รายการ</li>";
        echo "<li>ลบการแจ้งเตือนเก่า: {$cleanup['deleted_notifications']} รายการ</li>";
        echo "<li>ลบไฟล์ backup เก่า: {$cleanup['deleted_backups']} ไฟล์</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($result['error']) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Check Cron Job Files
echo "<h2>6. ทดสอบไฟล์ Cron Jobs</h2>";
$cronFiles = [
    'cron/run_all_jobs.php',
    'cron/update_customer_grades.php',
    'cron/update_customer_temperatures.php',
    'cron/send_recall_notifications.php'
];

foreach ($cronFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ ไฟล์ {$file} พบ (ขนาด: " . number_format(filesize($file)) . " bytes)</p>";
        
        // Check if executable
        if (is_executable($file)) {
            echo "<p>✅ ไฟล์สามารถรันได้</p>";
        } else {
            echo "<p>⚠️ ไฟล์ไม่สามารถรันได้ (ต้องตั้งค่า chmod +x)</p>";
        }
    } else {
        echo "<p>❌ ไฟล์ {$file} ไม่พบ</p>";
    }
}

// Test 7: Check Database Tables
echo "<h2>7. ทดสอบตารางฐานข้อมูล</h2>";
$requiredTables = [
    'notifications',
    'customer_recall_list',
    'cron_job_logs',
    'activity_logs',
    'cron_job_settings'
];

try {
    $db = new Database();
    
    foreach ($requiredTables as $table) {
        $sql = "SHOW TABLES LIKE '{$table}'";
        $stmt = $db->query($sql);
        
        if ($stmt->rowCount() > 0) {
            // Count records
            $countSql = "SELECT COUNT(*) as count FROM {$table}";
            $countStmt = $db->query($countSql);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>✅ ตาราง {$table} พบ ({$count} รายการ)</p>";
        } else {
            echo "<p>❌ ตาราง {$table} ไม่พบ</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาดในการตรวจสอบตาราง: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>สรุปการทดสอบ</h2>";
echo "<p>หากทุกข้อทดสอบผ่าน ✅ แสดงว่าระบบ Cron Jobs พร้อมใช้งาน</p>";

echo "<h3>วิธีการตั้งค่า Cron Jobs ใน Server:</h3>";
echo "<pre>";
echo "# เพิ่มใน crontab (-e เพื่อแก้ไข)\n";
echo "# อัปเดตเกรดลูกค้าทุกวัน 2:00 น.\n";
echo "0 2 * * * /usr/bin/php " . realpath('cron/update_customer_grades.php') . "\n\n";
echo "# อัปเดตอุณหภูมิลูกค้าทุกวัน 2:30 น.\n";
echo "30 2 * * * /usr/bin/php " . realpath('cron/update_customer_temperatures.php') . "\n\n";
echo "# ส่งการแจ้งเตือนทุกวัน 3:00 น.\n";
echo "0 3 * * * /usr/bin/php " . realpath('cron/send_recall_notifications.php') . "\n\n";
echo "# รันงานทั้งหมดทุกวัน 1:00 น. (ทางเลือก)\n";
echo "0 1 * * * /usr/bin/php " . realpath('cron/run_all_jobs.php') . "\n";
echo "</pre>";

echo "<h3>การทดสอบ Manual:</h3>";
echo "<pre>";
echo "# ทดสอบรันไฟล์ cron jobs จาก command line:\n";
echo "php cron/run_all_jobs.php\n";
echo "php cron/update_customer_grades.php\n";
echo "php cron/update_customer_temperatures.php\n";
echo "php cron/send_recall_notifications.php\n";
echo "</pre>";
?>