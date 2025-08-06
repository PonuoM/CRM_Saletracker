<?php
/**
 * ทดสอบการต่อเวลาอัตโนมัติแบบง่าย
 */

// ตรวจสอบว่ามี config.php หรือไม่
if (!file_exists('config/config.php')) {
    die("❌ ไม่พบไฟล์ config/config.php");
}

require_once 'config/config.php';

// ตรวจสอบว่ามี Database.php หรือไม่
if (!file_exists('app/core/Database.php')) {
    die("❌ ไม่พบไฟล์ app/core/Database.php");
}

require_once 'app/core/Database.php';

echo "<h2>🧪 ทดสอบการต่อเวลาอัตโนมัติแบบง่าย</h2>\n";

try {
    $db = new Database();
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n";
    
    // ตรวจสอบโครงสร้างตาราง customers
    echo "<h3>1. ตรวจสอบโครงสร้างตาราง customers</h3>\n";
    
    $columns = $db->fetchAll("DESCRIBE customers");
    $columnNames = [];
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
    }
    
    echo "📋 คอลัมน์ในตาราง customers:\n";
    foreach ($columnNames as $name) {
        echo "   - $name\n";
    }
    
    // ตรวจสอบคอลัมน์ที่จำเป็น
    $requiredColumns = ['customer_id', 'assigned_at', 'customer_time_expiry', 'customer_time_extension'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $required) {
        if (!in_array($required, $columnNames)) {
            $missingColumns[] = $required;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✅ คอลัมน์ที่จำเป็นครบถ้วน\n";
    } else {
        echo "❌ คอลัมน์ที่ขาดหายไป: " . implode(', ', $missingColumns) . "\n";
        echo "💡 กรุณารันไฟล์ fix_customer_time_columns.sql\n";
    }
    
    // ตรวจสอบข้อมูลลูกค้า
    echo "<h3>2. ตรวจสอบข้อมูลลูกค้า</h3>\n";
    
    $customers = $db->fetchAll(
        "SELECT customer_id, first_name, last_name, assigned_at, customer_time_expiry, customer_time_extension 
         FROM customers 
         WHERE assigned_at IS NOT NULL 
         LIMIT 5"
    );
    
    if ($customers) {
        echo "✅ พบลูกค้าที่มี assigned_at: " . count($customers) . " ราย\n";
        foreach ($customers as $customer) {
            echo "   - {$customer['first_name']} {$customer['last_name']} (ID: {$customer['customer_id']})\n";
            echo "     assigned_at: {$customer['assigned_at']}\n";
            echo "     customer_time_expiry: {$customer['customer_time_expiry']}\n";
            echo "     customer_time_extension: {$customer['customer_time_extension']}\n";
        }
    } else {
        echo "❌ ไม่พบลูกค้าที่มี assigned_at\n";
    }
    
    // ทดสอบการอัปเดตเวลา
    echo "<h3>3. ทดสอบการอัปเดตเวลา</h3>\n";
    
    if ($customers) {
        $testCustomer = $customers[0];
        $customerId = $testCustomer['customer_id'];
        
        echo "🧪 ทดสอบกับลูกค้า: {$testCustomer['first_name']} {$testCustomer['last_name']} (ID: {$customerId})\n";
        
        // บันทึกข้อมูลก่อนการทดสอบ
        $beforeData = $db->fetchOne(
            "SELECT assigned_at, customer_time_expiry, customer_time_extension 
             FROM customers 
             WHERE customer_id = ?",
            [$customerId]
        );
        
        echo "📅 ข้อมูลก่อนการทดสอบ:\n";
        echo "   - assigned_at: {$beforeData['assigned_at']}\n";
        echo "   - customer_time_expiry: {$beforeData['customer_time_expiry']}\n";
        echo "   - customer_time_extension: {$beforeData['customer_time_extension']}\n";
        
        // ทดสอบการอัปเดตเวลา
        $extensionDays = 90;
        $newDate = date('Y-m-d H:i:s', strtotime("+{$extensionDays} days"));
        
        // คำนวณ customer_time_expiry ใหม่
        if ($beforeData['customer_time_expiry'] === null || $beforeData['customer_time_expiry'] <= date('Y-m-d H:i:s')) {
            $newExpiry = date('Y-m-d H:i:s', strtotime("+{$extensionDays} days"));
        } else {
            $newExpiry = date('Y-m-d H:i:s', strtotime($beforeData['customer_time_expiry'] . " +{$extensionDays} days"));
        }
        
        // อัปเดตข้อมูล
        $updateResult = $db->update('customers', 
            [
                'assigned_at' => $newDate,
                'customer_time_expiry' => $newExpiry,
                'customer_time_extension' => $extensionDays
            ], 
            'customer_id = ?', 
            [$customerId]
        );
        
        if ($updateResult) {
            echo "✅ อัปเดตข้อมูลสำเร็จ\n";
        } else {
            echo "❌ อัปเดตข้อมูลล้มเหลว\n";
        }
        
        // ตรวจสอบข้อมูลหลังการทดสอบ
        $afterData = $db->fetchOne(
            "SELECT assigned_at, customer_time_expiry, customer_time_extension 
             FROM customers 
             WHERE customer_id = ?",
            [$customerId]
        );
        
        echo "📅 ข้อมูลหลังการทดสอบ:\n";
        echo "   - assigned_at: {$afterData['assigned_at']}\n";
        echo "   - customer_time_expiry: {$afterData['customer_time_expiry']}\n";
        echo "   - customer_time_extension: {$afterData['customer_time_extension']}\n";
        
        // ตรวจสอบการเปลี่ยนแปลง
        if ($beforeData['assigned_at'] !== $afterData['assigned_at']) {
            echo "✅ assigned_at เปลี่ยนแล้ว\n";
        } else {
            echo "❌ assigned_at ไม่เปลี่ยน\n";
        }
        
        if ($beforeData['customer_time_expiry'] !== $afterData['customer_time_expiry']) {
            echo "✅ customer_time_expiry เปลี่ยนแล้ว\n";
        } else {
            echo "❌ customer_time_expiry ไม่เปลี่ยน\n";
        }
        
        if ($beforeData['customer_time_extension'] !== $afterData['customer_time_extension']) {
            echo "✅ customer_time_extension เปลี่ยนแล้ว\n";
        } else {
            echo "❌ customer_time_extension ไม่เปลี่ยน\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 