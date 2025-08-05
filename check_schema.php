<?php
/**
 * Check_Schema.php - ตรวจสอบโครงสร้างฐานข้อมูลระบบการต่อเวลาจากการนัดหมาย
 * 
 * ไฟล์นี้จะตรวจสอบ:
 * 1. คอลัมน์ใหม่ในตาราง customers
 * 2. ตาราง appointment_extensions
 * 3. ตาราง appointment_extension_rules
 * 4. VIEW customer_appointment_extensions
 * 5. Stored Procedures
 * 6. Triggers
 */

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 ตรวจสอบโครงสร้างฐานข้อมูลระบบการต่อเวลาจากการนัดหมาย</h1>\n";
echo "<hr>\n";

try {
    // เชื่อมต่อฐานข้อมูล
    require_once 'config/config.php';
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ การเชื่อมต่อฐานข้อมูลสำเร็จ</h2>\n";
    
    // 1. ตรวจสอบคอลัมน์ใหม่ในตาราง customers
    echo "<h3>📋 1. ตรวจสอบคอลัมน์ใหม่ในตาราง customers</h3>\n";
    
    $sql = "DESCRIBE customers";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = [
        'appointment_count',
        'appointment_extension_count', 
        'last_appointment_date',
        'appointment_extension_expiry',
        'max_appointment_extensions',
        'appointment_extension_days'
    ];
    
    $existing_columns = array_column($columns, 'Field');
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>คอลัมน์ที่ต้องการ</th><th>สถานะ</th><th>ประเภทข้อมูล</th></tr>\n";
    
    foreach ($required_columns as $column) {
        if (in_array($column, $existing_columns)) {
            $column_info = array_filter($columns, function($col) use ($column) {
                return $col['Field'] === $column;
            });
            $column_info = reset($column_info);
            echo "<tr style='background-color: #d4edda;'>";
            echo "<td>{$column}</td>";
            echo "<td>✅ มีอยู่</td>";
            echo "<td>{$column_info['Type']}</td>";
            echo "</tr>\n";
        } else {
            echo "<tr style='background-color: #f8d7da;'>";
            echo "<td>{$column}</td>";
            echo "<td>❌ ไม่มี</td>";
            echo "<td>-</td>";
            echo "</tr>\n";
        }
    }
    echo "</table>\n";
    
    // 2. ตรวจสอบตาราง appointment_extensions
    echo "<h3>📋 2. ตรวจสอบตาราง appointment_extensions</h3>\n";
    
    $sql = "SHOW TABLES LIKE 'appointment_extensions'";
    $stmt = $pdo->query($sql);
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ ตาราง appointment_extensions มีอยู่</p>\n";
        
        // แสดงโครงสร้างตาราง
        $sql = "DESCRIBE appointment_extensions";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>NULL</th><th>Key</th><th>Default</th></tr>\n";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // นับจำนวนข้อมูล
        $sql = "SELECT COUNT(*) as count FROM appointment_extensions";
        $stmt = $pdo->query($sql);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>จำนวนข้อมูล: {$count} รายการ</p>\n";
        
    } else {
        echo "<p style='color: red;'>❌ ตาราง appointment_extensions ไม่มีอยู่</p>\n";
    }
    
    // 3. ตรวจสอบตาราง appointment_extension_rules
    echo "<h3>📋 3. ตรวจสอบตาราง appointment_extension_rules</h3>\n";
    
    $sql = "SHOW TABLES LIKE 'appointment_extension_rules'";
    $stmt = $pdo->query($sql);
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ ตาราง appointment_extension_rules มีอยู่</p>\n";
        
        // แสดงโครงสร้างตาราง
        $sql = "DESCRIBE appointment_extension_rules";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>NULL</th><th>Key</th><th>Default</th></tr>\n";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // แสดงข้อมูลในตาราง
        $sql = "SELECT * FROM appointment_extension_rules";
        $stmt = $pdo->query($sql);
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rules) > 0) {
            echo "<h4>ข้อมูลกฎการต่อเวลา:</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>ชื่อกฎ</th><th>จำนวนวัน</th><th>จำนวนครั้งสูงสุด</th><th>สถานะ</th></tr>\n";
            
            foreach ($rules as $rule) {
                echo "<tr>";
                echo "<td>{$rule['id']}</td>";
                echo "<td>{$rule['rule_name']}</td>";
                echo "<td>{$rule['extension_days']}</td>";
                echo "<td>{$rule['max_extensions']}</td>";
                echo "<td>" . ($rule['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ ไม่มีข้อมูลกฎการต่อเวลา</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>❌ ตาราง appointment_extension_rules ไม่มีอยู่</p>\n";
    }
    
    // 4. ตรวจสอบ VIEW customer_appointment_extensions
    echo "<h3>📋 4. ตรวจสอบ VIEW customer_appointment_extensions</h3>\n";
    
    $sql = "SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_" . $dbname . " = 'customer_appointment_extensions'";
    $stmt = $pdo->query($sql);
    $view_exists = $stmt->rowCount() > 0;
    
    if ($view_exists) {
        echo "<p style='color: green;'>✅ VIEW customer_appointment_extensions มีอยู่</p>\n";
        
        // แสดงโครงสร้าง VIEW
        $sql = "DESCRIBE customer_appointment_extensions";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>NULL</th><th>Key</th><th>Default</th></tr>\n";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // ทดสอบการดึงข้อมูลจาก VIEW
        try {
            $sql = "SELECT COUNT(*) as count FROM customer_appointment_extensions";
            $stmt = $pdo->query($sql);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>จำนวนข้อมูลใน VIEW: {$count} รายการ</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ ไม่สามารถดึงข้อมูลจาก VIEW ได้: " . $e->getMessage() . "</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>❌ VIEW customer_appointment_extensions ไม่มีอยู่</p>\n";
    }
    
    // 5. ตรวจสอบ Stored Procedures
    echo "<h3>📋 5. ตรวจสอบ Stored Procedures</h3>\n";
    
    $sql = "SHOW PROCEDURE STATUS WHERE Db = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dbname]);
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_procedures = [
        'ExtendCustomerTimeFromAppointment',
        'ResetAppointmentExtensionOnSale'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Stored Procedure</th><th>สถานะ</th><th>วันที่สร้าง</th></tr>\n";
    
    foreach ($required_procedures as $procedure) {
        $found = false;
        $created = '';
        
        foreach ($procedures as $proc) {
            if ($proc['Name'] === $procedure) {
                $found = true;
                $created = $proc['Created'];
                break;
            }
        }
        
        if ($found) {
            echo "<tr style='background-color: #d4edda;'>";
            echo "<td>{$procedure}</td>";
            echo "<td>✅ มีอยู่</td>";
            echo "<td>{$created}</td>";
            echo "</tr>\n";
        } else {
            echo "<tr style='background-color: #f8d7da;'>";
            echo "<td>{$procedure}</td>";
            echo "<td>❌ ไม่มี</td>";
            echo "<td>-</td>";
            echo "</tr>\n";
        }
    }
    echo "</table>\n";
    
    // 6. ตรวจสอบ Triggers
    echo "<h3>📋 6. ตรวจสอบ Triggers</h3>\n";
    
    $sql = "SHOW TRIGGERS";
    $stmt = $pdo->query($sql);
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_triggers = [
        'after_appointment_insert',
        'after_appointment_delete'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Trigger</th><th>สถานะ</th><th>ตาราง</th><th>Event</th><th>Timing</th></tr>\n";
    
    foreach ($required_triggers as $trigger) {
        $found = false;
        $table = '';
        $event = '';
        $timing = '';
        
        foreach ($triggers as $trig) {
            if ($trig['Trigger'] === $trigger) {
                $found = true;
                $table = $trig['Table'];
                $event = $trig['Event'];
                $timing = $trig['Timing'];
                break;
            }
        }
        
        if ($found) {
            echo "<tr style='background-color: #d4edda;'>";
            echo "<td>{$trigger}</td>";
            echo "<td>✅ มีอยู่</td>";
            echo "<td>{$table}</td>";
            echo "<td>{$event}</td>";
            echo "<td>{$timing}</td>";
            echo "</tr>\n";
        } else {
            echo "<tr style='background-color: #f8d7da;'>";
            echo "<td>{$trigger}</td>";
            echo "<td>❌ ไม่มี</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "</tr>\n";
        }
    }
    echo "</table>\n";
    
    // 7. ตรวจสอบข้อมูลตัวอย่าง
    echo "<h3>📋 7. ตรวจสอบข้อมูลตัวอย่าง</h3>\n";
    
    // ตรวจสอบลูกค้าที่มีการต่อเวลา
    $sql = "SELECT 
                c.id,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.appointment_count,
                c.appointment_extension_count,
                c.last_appointment_date,
                c.appointment_extension_expiry,
                c.max_appointment_extensions,
                c.appointment_extension_days
            FROM customers c 
            WHERE c.appointment_count > 0 
            OR c.appointment_extension_count > 0
            LIMIT 5";
    
    try {
        $stmt = $pdo->query($sql);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($customers) > 0) {
            echo "<h4>ลูกค้าที่มีการต่อเวลา:</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>ชื่อลูกค้า</th><th>จำนวนนัดหมาย</th><th>จำนวนต่อเวลา</th><th>วันที่นัดหมายล่าสุด</th><th>วันหมดอายุต่อเวลา</th></tr>\n";
            
            foreach ($customers as $customer) {
                echo "<tr>";
                echo "<td>{$customer['id']}</td>";
                echo "<td>{$customer['customer_name']}</td>";
                echo "<td>{$customer['appointment_count']}</td>";
                echo "<td>{$customer['appointment_extension_count']}</td>";
                echo "<td>" . ($customer['last_appointment_date'] ? $customer['last_appointment_date'] : '-') . "</td>";
                echo "<td>" . ($customer['appointment_extension_expiry'] ? $customer['appointment_extension_expiry'] : '-') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ ไม่มีลูกค้าที่มีการต่อเวลา</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ไม่สามารถดึงข้อมูลลูกค้าได้: " . $e->getMessage() . "</p>\n";
    }
    
    // 8. สรุปสถานะ
    echo "<h3>📊 สรุปสถานะระบบ</h3>\n";
    
    $status = [];
    
    // ตรวจสอบคอลัมน์ใน customers
    $missing_columns = array_diff($required_columns, $existing_columns);
    if (empty($missing_columns)) {
        $status[] = "✅ คอลัมน์ในตาราง customers: เสร็จสิ้น";
    } else {
        $status[] = "❌ คอลัมน์ในตาราง customers: ขาดหาย " . implode(', ', $missing_columns);
    }
    
    // ตรวจสอบตาราง
    if ($table_exists) {
        $status[] = "✅ ตาราง appointment_extensions: เสร็จสิ้น";
    } else {
        $status[] = "❌ ตาราง appointment_extensions: ขาดหาย";
    }
    
    if ($view_exists) {
        $status[] = "✅ VIEW customer_appointment_extensions: เสร็จสิ้น";
    } else {
        $status[] = "❌ VIEW customer_appointment_extensions: ขาดหาย";
    }
    
    echo "<ul>\n";
    foreach ($status as $item) {
        echo "<li>{$item}</li>\n";
    }
    echo "</ul>\n";
    
    // 9. คำแนะนำ
    echo "<h3>💡 คำแนะนำ</h3>\n";
    
    if (!empty($missing_columns) || !$table_exists || !$view_exists) {
        echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'>\n";
        echo "<h4>⚠️ ต้องดำเนินการ:</h4>\n";
        echo "<ol>\n";
        
        if (!empty($missing_columns)) {
            echo "<li>เพิ่มคอลัมน์ที่ขาดหายในตาราง customers</li>\n";
        }
        
        if (!$table_exists) {
            echo "<li>สร้างตาราง appointment_extensions</li>\n";
        }
        
        if (!$view_exists) {
            echo "<li>สร้าง VIEW customer_appointment_extensions</li>\n";
        }
        
        echo "<li>รันไฟล์ database/appointment_extension_system_fixed.sql</li>\n";
        echo "</ol>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px;'>\n";
        echo "<h4>✅ ระบบพร้อมใช้งาน</h4>\n";
        echo "<p>โครงสร้างฐานข้อมูลครบถ้วน สามารถทดสอบระบบได้</p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>\n";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>\n";
    echo "<h3>การแก้ไขปัญหา:</h3>\n";
    echo "<ol>\n";
    echo "<li>ตรวจสอบการเชื่อมต่อฐานข้อมูลใน config/config.php</li>\n";
    echo "<li>ตรวจสอบสิทธิ์การเข้าถึงฐานข้อมูล</li>\n";
    echo "<li>ตรวจสอบว่าฐานข้อมูลมีอยู่จริง</li>\n";
    echo "</ol>\n";
}

echo "<hr>\n";
echo "<p><strong>วันที่ตรวจสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><strong>เวอร์ชัน:</strong> 1.0</p>\n";
?> 