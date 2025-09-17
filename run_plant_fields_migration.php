<?php
/**
 * Migration Script: เพิ่มฟิลด์พืชพันธุ์และขนาดสวนในตาราง call_logs
 * รันไฟล์นี้เพื่ออัปเดตโครงสร้างฐานข้อมูล
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = new Database();
    
    echo "<h2>Migration: เพิ่มฟิลด์พืชพันธุ์และขนาดสวนในตาราง call_logs</h2>\n";
    echo "<p>เริ่มต้นการ migration...</p>\n";
    
    // ตรวจสอบว่าฟิลด์มีอยู่แล้วหรือไม่
    $checkPlantVariety = $db->fetchOne("SHOW COLUMNS FROM call_logs LIKE 'plant_variety'");
    $checkGardenSize = $db->fetchOne("SHOW COLUMNS FROM call_logs LIKE 'garden_size'");
    
    if ($checkPlantVariety) {
        echo "<p style='color: orange;'>⚠️ ฟิลด์ plant_variety มีอยู่แล้วในตาราง call_logs</p>\n";
    } else {
        echo "<p>เพิ่มฟิลด์ plant_variety...</p>\n";
        $db->execute("ALTER TABLE `call_logs` ADD COLUMN `plant_variety` varchar(255) DEFAULT NULL COMMENT 'พืชพันธุ์ที่ลูกค้าปลูก' AFTER `followup_priority`");
        echo "<p style='color: green;'>✅ เพิ่มฟิลด์ plant_variety สำเร็จ</p>\n";
    }
    
    if ($checkGardenSize) {
        echo "<p style='color: orange;'>⚠️ ฟิลด์ garden_size มีอยู่แล้วในตาราง call_logs</p>\n";
    } else {
        echo "<p>เพิ่มฟิลด์ garden_size...</p>\n";
        $db->execute("ALTER TABLE `call_logs` ADD COLUMN `garden_size` varchar(100) DEFAULT NULL COMMENT 'ขนาดสวน (ไร่/ตารางวา/ตารางเมตร)' AFTER `plant_variety`");
        echo "<p style='color: green;'>✅ เพิ่มฟิลด์ garden_size สำเร็จ</p>\n";
    }
    
    // ตรวจสอบและเพิ่ม index
    $checkPlantIndex = $db->fetchOne("SHOW INDEX FROM call_logs WHERE Key_name = 'idx_plant_variety'");
    if (!$checkPlantIndex) {
        echo "<p>เพิ่ม index สำหรับ plant_variety...</p>\n";
        $db->execute("ALTER TABLE `call_logs` ADD KEY `idx_plant_variety` (`plant_variety`)");
        echo "<p style='color: green;'>✅ เพิ่ม index plant_variety สำเร็จ</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Index idx_plant_variety มีอยู่แล้ว</p>\n";
    }
    
    $checkGardenIndex = $db->fetchOne("SHOW INDEX FROM call_logs WHERE Key_name = 'idx_garden_size'");
    if (!$checkGardenIndex) {
        echo "<p>เพิ่ม index สำหรับ garden_size...</p>\n";
        $db->execute("ALTER TABLE `call_logs` ADD KEY `idx_garden_size` (`garden_size`)");
        echo "<p style='color: green;'>✅ เพิ่ม index garden_size สำเร็จ</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Index idx_garden_size มีอยู่แล้ว</p>\n";
    }
    
    // ตรวจสอบโครงสร้างตารางหลัง migration
    echo "<h3>โครงสร้างตาราง call_logs หลัง migration:</h3>\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM call_logs");
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3 style='color: green;'>🎉 Migration เสร็จสิ้น!</h3>\n";
    echo "<p>ตอนนี้คุณสามารถใช้ฟีเจอร์บันทึกการโทรพร้อมข้อมูลพืชพันธุ์และขนาดสวนได้แล้ว</p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ เกิดข้อผิดพลาด:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>กรุณาตรวจสอบการเชื่อมต่อฐานข้อมูลและลองใหม่อีกครั้ง</p>\n";
}
?>
