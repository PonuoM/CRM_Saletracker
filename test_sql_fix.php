<?php
/**
 * Test SQL Fix - ทดสอบการแก้ไขปัญหา SQL Syntax
 * ตรวจสอบว่า tableExists method ทำงานได้ถูกต้อง
 */

echo "<h1>🧪 Test SQL Fix - ทดสอบการแก้ไขปัญหา SQL Syntax</h1>";

// 1. ทดสอบการโหลดไฟล์ที่จำเป็น
echo "<h2>1. ตรวจสอบการโหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/ImportExportService.php';
    echo "✅ โหลดไฟล์ที่จำเป็นสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. ทดสอบ Database tableExists method
echo "<h2>2. ทดสอบ Database tableExists method</h2>";
try {
    $db = new Database();
    $tables = ['customers', 'orders', 'customer_activities', 'non_existent_table'];
    
    foreach ($tables as $table) {
        $exists = $db->tableExists($table);
        if ($exists) {
            echo "✅ ตาราง {$table} มีอยู่<br>";
        } else {
            echo "❌ ตาราง {$table} ไม่มีอยู่<br>";
        }
    }
    echo "✅ Database tableExists method ทำงานได้ปกติ<br>";
} catch (Exception $e) {
    echo "❌ Database tableExists Error: " . $e->getMessage() . "<br>";
}

// 3. ทดสอบ ImportExportService tableExists method
echo "<h2>3. ทดสอบ ImportExportService tableExists method</h2>";
try {
    $service = new ImportExportService();
    
    // ใช้ reflection เพื่อเข้าถึง private method
    $reflection = new ReflectionClass($service);
    $tableExistsMethod = $reflection->getMethod('tableExists');
    $tableExistsMethod->setAccessible(true);
    
    $tables = ['customers', 'orders', 'customer_activities', 'non_existent_table'];
    
    foreach ($tables as $table) {
        $exists = $tableExistsMethod->invoke($service, $table);
        if ($exists) {
            echo "✅ ตาราง {$table} มีอยู่<br>";
        } else {
            echo "❌ ตาราง {$table} ไม่มีอยู่<br>";
        }
    }
    echo "✅ ImportExportService tableExists method ทำงานได้ปกติ<br>";
} catch (Exception $e) {
    echo "❌ ImportExportService tableExists Error: " . $e->getMessage() . "<br>";
}

// 4. ทดสอบการ query ข้อมูลจากตาราง
echo "<h2>4. ทดสอบการ query ข้อมูลจากตาราง</h2>";
try {
    $db = new Database();
    
    // ทดสอบ query ข้อมูลลูกค้า
    $customers = $db->fetchAll("SELECT COUNT(*) as count FROM customers");
    echo "✅ Query customers: " . $customers[0]['count'] . " รายการ<br>";
    
    // ทดสอบ query ข้อมูลออเดอร์
    $orders = $db->fetchAll("SELECT COUNT(*) as count FROM orders");
    echo "✅ Query orders: " . $orders[0]['count'] . " รายการ<br>";
    
    echo "✅ การ query ข้อมูลทำงานได้ปกติ<br>";
} catch (Exception $e) {
    echo "❌ Query Error: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ การทดสอบเสร็จสิ้น</h2>";
echo "<p>หากไม่พบ error แสดงว่าการแก้ไข SQL syntax สำเร็จแล้ว!</p>";
?> 