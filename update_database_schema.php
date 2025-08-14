<?php
/**
 * Update Database Schema
 * อัปเดตโครงสร้างฐานข้อมูลสำหรับระบบ basket management
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'app/core/Database.php';

echo "<h1>🔧 CRM SalesTracker - Database Schema Update</h1>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>📋 ตรวจสอบและอัปเดตโครงสร้างฐานข้อมูล</h2>";
    
    $updates = [];
    $errors = [];
    
    // 1. เพิ่มคอลัมน์ recall_at และ recall_reason ในตาราง customers
    echo "<h3>1. อัปเดตตาราง customers</h3>";
    
    // ตรวจสอบว่ามีคอลัมน์ recall_at หรือไม่
    $sql = "SHOW COLUMNS FROM customers LIKE 'recall_at'";
    $result = $pdo->query($sql);
    
    if ($result->rowCount() == 0) {
        try {
            $sql = "ALTER TABLE customers ADD COLUMN recall_at DATETIME NULL AFTER assigned_at";
            $pdo->exec($sql);
            $updates[] = "✅ เพิ่มคอลัมน์ recall_at ในตาราง customers";
            echo "<p style='color: green;'>✅ เพิ่มคอลัมน์ recall_at สำเร็จ</p>";
        } catch (Exception $e) {
            $errors[] = "❌ ไม่สามารถเพิ่มคอลัมน์ recall_at: " . $e->getMessage();
            echo "<p style='color: red;'>❌ ไม่สามารถเพิ่มคอลัมน์ recall_at: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ คอลัมน์ recall_at มีอยู่แล้ว</p>";
    }
    
    // ตรวจสอบว่ามีคอลัมน์ recall_reason หรือไม่
    $sql = "SHOW COLUMNS FROM customers LIKE 'recall_reason'";
    $result = $pdo->query($sql);
    
    if ($result->rowCount() == 0) {
        try {
            $sql = "ALTER TABLE customers ADD COLUMN recall_reason VARCHAR(100) NULL AFTER recall_at";
            $pdo->exec($sql);
            $updates[] = "✅ เพิ่มคอลัมน์ recall_reason ในตาราง customers";
            echo "<p style='color: green;'>✅ เพิ่มคอลัมน์ recall_reason สำเร็จ</p>";
        } catch (Exception $e) {
            $errors[] = "❌ ไม่สามารถเพิ่มคอลัมน์ recall_reason: " . $e->getMessage();
            echo "<p style='color: red;'>❌ ไม่สามารถเพิ่มคอลัมน์ recall_reason: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ คอลัมน์ recall_reason มีอยู่แล้ว</p>";
    }
    
    // 1.1 อัปเดต ENUM ของตาราง orders: payment_status และ delivery_status ให้รองรับค่าล่าสุด
    echo "<h3>1.1 อัปเดตตาราง orders (ENUM)</h3>";
    try {
        // ตรวจสอบ payment_status
        $col = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_status'")->fetch(PDO::FETCH_ASSOC);
        if ($col) {
            $type = strtolower($col['Type'] ?? '');
            // ต้องมี pending,paid,partial,cancelled,returned
            if (strpos($type, "'returned'") === false) {
                $sql = "ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','paid','partial','cancelled','returned') DEFAULT 'pending'";
                $pdo->exec($sql);
                $updates[] = "✅ อัปเดต ENUM payment_status เพิ่ม 'returned'";
                echo "<p style='color: green;'>✅ อัปเดต payment_status เพิ่ม 'returned'</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ payment_status มี 'returned' แล้ว</p>";
            }
        }

        // ตรวจสอบ delivery_status
        $col2 = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_status'")->fetch(PDO::FETCH_ASSOC);
        if ($col2) {
            $type2 = strtolower($col2['Type'] ?? '');
            // ต้องมี pending,confirmed,shipped,delivered,cancelled (เพิ่ม confirmed)
            $needsAlter = (strpos($type2, "'confirmed'") === false) || (strpos($type2, "'delivered'") === false) || (strpos($type2, "'shipped'") === false) || (strpos($type2, "'pending'") === false) || (strpos($type2, "'cancelled'") === false);
            if ($needsAlter) {
                $sql = "ALTER TABLE orders MODIFY COLUMN delivery_status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending'";
                $pdo->exec($sql);
                $updates[] = "✅ อัปเดต ENUM delivery_status เพิ่ม 'confirmed'";
                echo "<p style='color: green;'>✅ อัปเดต delivery_status เพิ่ม 'confirmed'</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ delivery_status รองรับค่าล่าสุดแล้ว</p>";
            }
        }
    } catch (Exception $e) {
        $errors[] = "❌ ไม่สามารถอัปเดต ENUM orders: " . $e->getMessage();
        echo "<p style='color: red;'>❌ ไม่สามารถอัปเดต ENUM orders: " . $e->getMessage() . "</p>";
    }

    // 2. ตรวจสอบและสร้างตาราง cron_job_logs
    echo "<h3>2. ตรวจสอบตาราง cron_job_logs</h3>";
    
    $sql = "SHOW TABLES LIKE 'cron_job_logs'";
    $result = $pdo->query($sql);
    
    if ($result->rowCount() == 0) {
        try {
            $sql = "CREATE TABLE cron_job_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                job_name VARCHAR(100) NOT NULL,
                status ENUM('running', 'success', 'failed') NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NULL,
                execution_time DECIMAL(10,2) NULL,
                output TEXT NULL,
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            $updates[] = "✅ สร้างตาราง cron_job_logs";
            echo "<p style='color: green;'>✅ สร้างตาราง cron_job_logs สำเร็จ</p>";
        } catch (Exception $e) {
            $errors[] = "❌ ไม่สามารถสร้างตาราง cron_job_logs: " . $e->getMessage();
            echo "<p style='color: red;'>❌ ไม่สามารถสร้างตาราง cron_job_logs: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ ตาราง cron_job_logs มีอยู่แล้ว</p>";
    }
    
    // 3. ตรวจสอบและสร้างตาราง cron_job_settings
    echo "<h3>3. ตรวจสอบตาราง cron_job_settings</h3>";
    
    $sql = "SHOW TABLES LIKE 'cron_job_settings'";
    $result = $pdo->query($sql);
    
    if ($result->rowCount() == 0) {
        try {
            $sql = "CREATE TABLE cron_job_settings (
                setting_id INT AUTO_INCREMENT PRIMARY KEY,
                job_name VARCHAR(100) NOT NULL UNIQUE,
                is_enabled BOOLEAN DEFAULT TRUE,
                last_run DATETIME NULL,
                next_run DATETIME NULL,
                schedule_expression VARCHAR(50) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            $updates[] = "✅ สร้างตาราง cron_job_settings";
            echo "<p style='color: green;'>✅ สร้างตาราง cron_job_settings สำเร็จ</p>";
            
            // เพิ่มข้อมูลเริ่มต้น
            $jobs = [
                'run_all_jobs',
                'basket_management',
                'grade_update',
                'temperature_update',
                'recall_list',
                'notifications',
                'call_followup'
            ];
            
            foreach ($jobs as $job) {
                $sql = "INSERT IGNORE INTO cron_job_settings (job_name, schedule_expression) VALUES (?, '20 1 * * *')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$job]);
            }
            
            echo "<p style='color: green;'>✅ เพิ่มข้อมูลเริ่มต้นใน cron_job_settings</p>";
            
        } catch (Exception $e) {
            $errors[] = "❌ ไม่สามารถสร้างตาราง cron_job_settings: " . $e->getMessage();
            echo "<p style='color: red;'>❌ ไม่สามารถสร้างตาราง cron_job_settings: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ ตาราง cron_job_settings มีอยู่แล้ว</p>";
    }
    
    // 4. ตรวจสอบตาราง activity_logs
    echo "<h3>4. ตรวจสอบตาราง activity_logs</h3>";
    
    $sql = "SHOW TABLES LIKE 'activity_logs'";
    $result = $pdo->query($sql);
    
    if ($result->rowCount() == 0) {
        try {
            $sql = "CREATE TABLE activity_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                activity_type VARCHAR(50) NOT NULL,
                table_name VARCHAR(50) NULL,
                record_id INT NULL,
                action VARCHAR(50) NOT NULL,
                description TEXT NULL,
                old_values JSON NULL,
                new_values JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_activity_type (activity_type),
                INDEX idx_created_at (created_at)
            )";
            $pdo->exec($sql);
            $updates[] = "✅ สร้างตาราง activity_logs";
            echo "<p style='color: green;'>✅ สร้างตาราง activity_logs สำเร็จ</p>";
        } catch (Exception $e) {
            $errors[] = "❌ ไม่สามารถสร้างตาราง activity_logs: " . $e->getMessage();
            echo "<p style='color: red;'>❌ ไม่สามารถสร้างตาราง activity_logs: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ ตาราง activity_logs มีอยู่แล้ว</p>";
    }
    
    // 5. อัปเดตข้อมูลลูกค้าเริ่มต้น
    echo "<h3>5. อัปเดตข้อมูลลูกค้าเริ่มต้น</h3>";
    
    // ตั้งค่า basket_type เริ่มต้นสำหรับลูกค้าที่ไม่มี
    $sql = "UPDATE customers SET basket_type = 'assigned' WHERE basket_type IS NULL OR basket_type = ''";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $updatedCustomers = $stmt->rowCount();
    
    if ($updatedCustomers > 0) {
        echo "<p style='color: green;'>✅ อัปเดต basket_type สำหรับลูกค้า $updatedCustomers รายการ</p>";
        $updates[] = "✅ อัปเดต basket_type สำหรับลูกค้า $updatedCustomers รายการ";
    } else {
        echo "<p style='color: blue;'>ℹ️ ข้อมูล basket_type ครบถ้วนแล้ว</p>";
    }
    
    // สรุปผลการอัปเดต
    echo "<h2>📊 สรุปผลการอัปเดต</h2>";
    
    if (!empty($updates)) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ การอัปเดตที่สำเร็จ</h3>";
        echo "<ul>";
        foreach ($updates as $update) {
            echo "<li>$update</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>❌ ข้อผิดพลาด</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (empty($errors)) {
        echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🎉 อัปเดตฐานข้อมูลเสร็จสิ้น</h3>";
        echo "<p>ตอนนี้สามารถรัน cron jobs และดูผลลัพธ์ได้แล้ว</p>";
        echo "<p><strong>ขั้นตอนต่อไป:</strong></p>";
        echo "<ol>";
        echo "<li>ทดสอบรัน cron jobs</li>";
        echo "<li>ตรวจสอบ log files</li>";
        echo "<li>ดูข้อมูลในฐานข้อมูล</li>";
        echo "</ol>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาดร้าย</h3>";
    echo "<p>ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// ปุ่มนำทาง
echo "<div style='margin: 20px 0;'>";
echo "<a href='view_cron_database.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🗄️ ดูฐานข้อมูล</a>";
echo "<a href='test_cron_jobs.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 ทดสอบ Cron</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 หน้าหลัก</a>";
echo "</div>";

?>
