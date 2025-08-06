<?php
/**
 * Fix Database Schema - เพิ่มคอลัมน์ description ในตาราง order_activities
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = new Database();
    echo "🔧 กำลังแก้ไขโครงสร้างฐานข้อมูล...\n\n";
    
    // 1. ตรวจสอบว่าคอลัมน์ description มีอยู่หรือไม่
    echo "1. ตรวจสอบคอลัมน์ description ในตาราง order_activities...\n";
    $columnExists = $db->fetchOne("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'order_activities' 
        AND COLUMN_NAME = 'description'
    ");
    
    if ($columnExists['count'] > 0) {
        echo "✅ คอลัมน์ description มีอยู่แล้วในตาราง order_activities\n";
    } else {
        echo "❌ ไม่พบคอลัมน์ description ในตาราง order_activities\n";
        echo "🔧 กำลังเพิ่มคอลัมน์ description...\n";
        
        // เพิ่มคอลัมน์ description
        $result = $db->execute("
            ALTER TABLE order_activities 
            ADD COLUMN description TEXT NOT NULL AFTER activity_type
        ");
        
        if ($result) {
            echo "✅ เพิ่มคอลัมน์ description สำเร็จ\n";
        } else {
            echo "❌ ไม่สามารถเพิ่มคอลัมน์ description ได้\n";
        }
    }
    
    // 2. ตรวจสอบโครงสร้างตารางหลังการแก้ไข
    echo "\n2. ตรวจสอบโครงสร้างตาราง order_activities:\n";
    $columns = $db->fetchAll("DESCRIBE order_activities");
    
    echo "📋 คอลัมน์ในตาราง order_activities:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($column['Default'] ? " DEFAULT {$column['Default']}" : '') . "\n";
    }
    
    // 3. ตรวจสอบข้อมูลในตาราง
    echo "\n3. ตรวจสอบข้อมูลในตาราง order_activities:\n";
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM order_activities");
    echo "📊 จำนวนรายการในตาราง: {$count['count']} รายการ\n";
    
    if ($count['count'] > 0) {
        $sampleData = $db->fetchAll("
            SELECT activity_id, order_id, activity_type, 
                   CASE WHEN description IS NOT NULL THEN 'มีข้อมูล' ELSE 'ไม่มีข้อมูล' END as description_status,
                   created_at
            FROM order_activities 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        
        echo "📋 ข้อมูลตัวอย่าง (5 รายการล่าสุด):\n";
        foreach ($sampleData as $row) {
            echo "- ID: {$row['activity_id']}, Order: {$row['order_id']}, " .
                 "Type: {$row['activity_type']}, Description: {$row['description_status']}, " .
                 "Created: {$row['created_at']}\n";
        }
    }
    
    echo "\n✅ การแก้ไขโครงสร้างฐานข้อมูลเสร็จสิ้น\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    error_log("Database schema fix error: " . $e->getMessage());
}
?> 