<?php
/**
 * Check Customer Activities Schema - ตรวจสอบโครงสร้างตาราง customer_activities
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = new Database();
    echo "🔍 ตรวจสอบโครงสร้างตาราง customer_activities...\n\n";
    
    // 1. ตรวจสอบโครงสร้างตาราง
    echo "1. โครงสร้างตาราง customer_activities:\n";
    $columns = $db->fetchAll("DESCRIBE customer_activities");
    
    echo "📋 คอลัมน์ในตาราง customer_activities:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($column['Default'] ? " DEFAULT {$column['Default']}" : '') . "\n";
    }
    
    // 2. ตรวจสอบว่ามีคอลัมน์ description หรือ activity_description
    echo "\n2. ตรวจสอบคอลัมน์ description:\n";
    $descriptionColumn = $db->fetchOne("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'customer_activities' 
        AND COLUMN_NAME = 'description'
    ");
    
    $activityDescriptionColumn = $db->fetchOne("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'customer_activities' 
        AND COLUMN_NAME = 'activity_description'
    ");
    
    echo "คอลัมน์ 'description': " . ($descriptionColumn['count'] > 0 ? '✅ มีอยู่' : '❌ ไม่มี') . "\n";
    echo "คอลัมน์ 'activity_description': " . ($activityDescriptionColumn['count'] > 0 ? '✅ มีอยู่' : '❌ ไม่มี') . "\n";
    
    // 3. ตรวจสอบข้อมูลตัวอย่าง
    echo "\n3. ข้อมูลตัวอย่างในตาราง customer_activities:\n";
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM customer_activities");
    echo "📊 จำนวนรายการในตาราง: {$count['count']} รายการ\n";
    
    if ($count['count'] > 0) {
        // ลองดึงข้อมูลโดยไม่ระบุคอลัมน์ description ก่อน
        $sampleData = $db->fetchAll("
            SELECT activity_id, customer_id, activity_type, created_at
            FROM customer_activities 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        
        echo "📋 ข้อมูลตัวอย่าง (3 รายการล่าสุด):\n";
        foreach ($sampleData as $row) {
            echo "- ID: {$row['activity_id']}, Customer: {$row['customer_id']}, " .
                 "Type: {$row['activity_type']}, Created: {$row['created_at']}\n";
        }
    }
    
    echo "\n✅ การตรวจสอบเสร็จสิ้น\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    error_log("Schema check error: " . $e->getMessage());
}
?> 