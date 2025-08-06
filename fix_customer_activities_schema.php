<?php
/**
 * Fix Customer Activities Schema - เพิ่มคอลัมน์ที่ขาดหายไปในตาราง customer_activities
 * 
 * ปัญหาที่พบ: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'activity_date' in 'field list'
 * 
 * วิธีแก้ไข: เพิ่มคอลัมน์ activity_date ในตาราง customer_activities
 */

echo "<h1>🔧 Fix Customer Activities Schema - แก้ไขโครงสร้างตาราง customer_activities</h1>";

// 1. โหลดไฟล์ที่จำเป็น
echo "<h2>1. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    echo "✅ โหลดไฟล์สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. สร้าง Database connection
echo "<h2>2. สร้าง Database connection</h2>";
try {
    $db = new Database();
    echo "✅ Database connection สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ตรวจสอบโครงสร้างตารางปัจจุบัน
echo "<h2>3. ตรวจสอบโครงสร้างตาราง customer_activities ปัจจุบัน</h2>";
try {
    $columns = $db->fetchAll("DESCRIBE customer_activities");
    echo "✅ โครงสร้างตารางปัจจุบัน:<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // ตรวจสอบว่ามีคอลัมน์ activity_date หรือไม่
    $hasActivityDate = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'activity_date') {
            $hasActivityDate = true;
            break;
        }
    }
    
    if ($hasActivityDate) {
        echo "✅ คอลัมน์ activity_date มีอยู่แล้ว<br>";
    } else {
        echo "❌ คอลัมน์ activity_date ไม่มีอยู่ - ต้องเพิ่ม<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
    exit;
}

// 4. เพิ่มคอลัมน์ activity_date (ถ้ายังไม่มี)
echo "<h2>4. เพิ่มคอลัมน์ activity_date</h2>";
if (!$hasActivityDate) {
    try {
        // เพิ่มคอลัมน์ activity_date
        $sql = "ALTER TABLE customer_activities ADD COLUMN activity_date DATE NULL AFTER activity_type";
        $db->getPdo()->exec($sql);
        echo "✅ เพิ่มคอลัมน์ activity_date สำเร็จ<br>";
        
        // ตรวจสอบโครงสร้างตารางใหม่
        $columns = $db->fetchAll("DESCRIBE customer_activities");
        echo "✅ โครงสร้างตารางใหม่:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "❌ Error adding column: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "✅ คอลัมน์ activity_date มีอยู่แล้ว ไม่ต้องเพิ่ม<br>";
}

// 5. ทดสอบการ INSERT ข้อมูล
echo "<h2>5. ทดสอบการ INSERT ข้อมูล</h2>";
try {
    // หา customer_id ที่มีอยู่
    $customer = $db->fetchOne("SELECT customer_id FROM customers LIMIT 1");
    if ($customer) {
        $customerId = $customer['customer_id'];
        
        // ทดสอบ INSERT ข้อมูล
        $activityData = [
            'customer_id' => $customerId,
            'activity_type' => 'purchase',
            'activity_date' => date('Y-m-d'),
            'description' => 'ทดสอบการซื้อสินค้าหลังจากแก้ไขโครงสร้าง',
            'amount' => 1000.00,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $activityId = $db->insert('customer_activities', $activityData);
        echo "✅ ทดสอบ INSERT สำเร็จ: ID = {$activityId}<br>";
        
        // ตรวจสอบข้อมูลที่เพิ่มเข้าไป
        $newActivity = $db->fetchOne("SELECT * FROM customer_activities WHERE id = ?", [$activityId]);
        echo "✅ ข้อมูลที่เพิ่มเข้าไป:<br>";
        echo "<pre>" . print_r($newActivity, true) . "</pre><br>";
        
    } else {
        echo "❌ ไม่พบข้อมูลลูกค้าในระบบ<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing INSERT: " . $e->getMessage() . "<br>";
}

// 6. ทดสอบ complex query ที่ใช้ใน ImportExportService
echo "<h2>6. ทดสอบ complex query ที่ใช้ใน ImportExportService</h2>";
try {
    // ทดสอบ query ที่ใช้ใน updateCustomerPurchaseHistory
    $purchaseQuery = "INSERT INTO customer_activities (customer_id, activity_type, activity_date, description, amount, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->getPdo()->prepare($purchaseQuery);
    echo "✅ Prepare purchase query สำเร็จ<br>";
    
    // ทดสอบ query ที่ใช้ใน updateCustomerTotalPurchase
    $totalQuery = "UPDATE customers SET total_purchase_amount = (
                      SELECT COALESCE(SUM(amount), 0) 
                      FROM customer_activities 
                      WHERE customer_id = ? AND activity_type = 'purchase'
                   ) WHERE customer_id = ?";
    $stmt = $db->getPdo()->prepare($totalQuery);
    echo "✅ Prepare total purchase query สำเร็จ<br>";
    
    echo "✅ Complex queries ทำงานได้ปกติ<br>";
    
} catch (Exception $e) {
    echo "❌ Complex Query Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการแก้ไข</h2>";
echo "การแก้ไขโครงสร้างตาราง customer_activities เสร็จสิ้นแล้ว<br>";
echo "หากไม่พบ error แสดงว่าการแก้ไขสำเร็จแล้ว! 🚀<br>";
echo "ตอนนี้ระบบ Import Sales ควรจะทำงานได้ปกติแล้ว<br>";
?> 