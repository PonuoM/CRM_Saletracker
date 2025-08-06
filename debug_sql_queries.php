<?php
/**
 * Debug SQL Queries - ตรวจสอบ SQL queries และ database operations
 * เน้นการ debug ปัญหาที่อาจเกิดจาก database operations
 */

echo "<h1>🔍 Debug SQL Queries - ตรวจสอบ SQL queries และ database operations</h1>";

// เปิด error reporting แบบเต็ม
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// 1. โหลดไฟล์ที่จำเป็น
echo "<h2>1. โหลดไฟล์ที่จำเป็น</h2>";
try {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/services/ImportExportService.php';
    echo "✅ โหลดไฟล์สำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ Error loading files: " . $e->getMessage() . "<br>";
    exit;
}

// 2. ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>2. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $db = new Database();
    echo "✅ Database connection สำเร็จ<br>";
    
    // ทดสอบ query ง่ายๆ
    $result = $db->fetchOne("SELECT 1 as test");
    echo "✅ Basic query สำเร็จ<br>";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    exit;
}

// 3. ทดสอบ tableExists method
echo "<h2>3. ทดสอบ tableExists method</h2>";
try {
    $tables = ['customers', 'orders', 'customer_activities'];
    foreach ($tables as $table) {
        $exists = $db->tableExists($table);
        echo ($exists ? "✅" : "❌") . " ตาราง {$table}: " . ($exists ? "มีอยู่" : "ไม่มีอยู่") . "<br>";
    }
} catch (Exception $e) {
    echo "❌ TableExists Error: " . $e->getMessage() . "<br>";
}

// 4. ทดสอบการ query ตาราง customers
echo "<h2>4. ทดสอบการ query ตาราง customers</h2>";
try {
    // ทดสอบ SELECT query
    $customers = $db->fetchAll("SELECT COUNT(*) as count FROM customers");
    echo "✅ SELECT customers สำเร็จ: " . $customers[0]['count'] . " รายการ<br>";
    
    // ทดสอบ SELECT ด้วย WHERE clause
    $testCustomer = $db->fetchOne("SELECT customer_id, first_name, phone FROM customers WHERE phone = ?", ['0812345678']);
    if ($testCustomer) {
        echo "✅ SELECT customer by phone สำเร็จ<br>";
    } else {
        echo "✅ SELECT customer by phone สำเร็จ (ไม่พบข้อมูล)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Customers Query Error: " . $e->getMessage() . "<br>";
}

// 5. ทดสอบการ INSERT ข้อมูลทดสอบ
echo "<h2>5. ทดสอบการ INSERT ข้อมูลทดสอบ</h2>";
try {
    // ตรวจสอบว่ามีข้อมูลทดสอบอยู่แล้วหรือไม่
    $existingCustomer = $db->fetchOne("SELECT customer_id FROM customers WHERE phone = ?", ['0812345678']);
    
    if (!$existingCustomer) {
        // INSERT ข้อมูลทดสอบ
        $customerData = [
            'first_name' => 'ทดสอบ',
            'last_name' => 'ระบบ',
            'phone' => '0812345678',
            'email' => 'test@example.com',
            'address' => '123 ถ.ทดสอบ',
            'district' => 'เขตทดสอบ',
            'province' => 'จังหวัดทดสอบ',
            'postal_code' => '10000',
            'customer_status' => 'new',
            'temperature_status' => 'cold',
            'customer_grade' => 'C',
            'basket_type' => 'distribution',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $customerId = $db->insert('customers', $customerData);
        echo "✅ INSERT customer สำเร็จ: ID = {$customerId}<br>";
    } else {
        echo "✅ ข้อมูลทดสอบมีอยู่แล้ว: ID = " . $existingCustomer['customer_id'] . "<br>";
        $customerId = $existingCustomer['customer_id'];
    }
    
} catch (Exception $e) {
    echo "❌ INSERT Error: " . $e->getMessage() . "<br>";
    $customerId = null;
}

// 6. ทดสอบการ UPDATE ข้อมูล
echo "<h2>6. ทดสอบการ UPDATE ข้อมูล</h2>";
if ($customerId) {
    try {
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        $affectedRows = $db->update('customers', $updateData, 'customer_id = ?', [$customerId]);
        echo "✅ UPDATE customer สำเร็จ: {$affectedRows} แถวที่อัปเดต<br>";
    } catch (Exception $e) {
        echo "❌ UPDATE Error: " . $e->getMessage() . "<br>";
    }
}

// 7. ทดสอบการ query ตาราง orders
echo "<h2>7. ทดสอบการ query ตาราง orders</h2>";
try {
    $orders = $db->fetchAll("SELECT COUNT(*) as count FROM orders");
    echo "✅ SELECT orders สำเร็จ: " . $orders[0]['count'] . " รายการ<br>";
} catch (Exception $e) {
    echo "❌ Orders Query Error: " . $e->getMessage() . "<br>";
}

// 8. ทดสอบการ INSERT order
echo "<h2>8. ทดสอบการ INSERT order</h2>";
if ($customerId) {
    try {
        $orderData = [
            'customer_id' => $customerId,
            'order_number' => 'TEST-' . date('YmdHis'),
            'total_amount' => 1000.00,
            'discount_amount' => 0.00,
            'net_amount' => 1000.00,
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $orderId = $db->insert('orders', $orderData);
        echo "✅ INSERT order สำเร็จ: ID = {$orderId}<br>";
        
    } catch (Exception $e) {
        echo "❌ INSERT Order Error: " . $e->getMessage() . "<br>";
    }
}

// 9. ทดสอบการ query ตาราง customer_activities
echo "<h2>9. ทดสอบการ query ตาราง customer_activities</h2>";
try {
    $activities = $db->fetchAll("SELECT COUNT(*) as count FROM customer_activities");
    echo "✅ SELECT customer_activities สำเร็จ: " . $activities[0]['count'] . " รายการ<br>";
} catch (Exception $e) {
    echo "❌ Customer Activities Query Error: " . $e->getMessage() . "<br>";
}

// 10. ทดสอบการ INSERT customer_activity
echo "<h2>10. ทดสอบการ INSERT customer_activity</h2>";
if ($customerId) {
    try {
        $activityData = [
            'customer_id' => $customerId,
            'activity_type' => 'purchase',
            'activity_date' => date('Y-m-d'),
            'description' => 'ทดสอบการซื้อสินค้า',
            'amount' => 1000.00,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $activityId = $db->insert('customer_activities', $activityData);
        echo "✅ INSERT customer_activity สำเร็จ: ID = {$activityId}<br>";
        
    } catch (Exception $e) {
        echo "❌ INSERT Activity Error: " . $e->getMessage() . "<br>";
    }
}

// 11. ทดสอบ complex queries ที่ใช้ใน ImportExportService
echo "<h2>11. ทดสอบ complex queries ที่ใช้ใน ImportExportService</h2>";

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
    
} catch (Exception $e) {
    echo "❌ Complex Query Error: " . $e->getMessage() . "<br>";
}

// 12. ทดสอบ transaction handling
echo "<h2>12. ทดสอบ transaction handling</h2>";
try {
    $db->beginTransaction();
    echo "✅ Begin transaction สำเร็จ<br>";
    
    // ทำ query ง่ายๆ
    $result = $db->fetchOne("SELECT 1 as test");
    echo "✅ Query in transaction สำเร็จ<br>";
    
    $db->commit();
    echo "✅ Commit transaction สำเร็จ<br>";
    
} catch (Exception $e) {
    $db->rollback();
    echo "❌ Transaction Error: " . $e->getMessage() . "<br>";
}

// 13. ตรวจสอบ database connection status
echo "<h2>13. ตรวจสอบ database connection status</h2>";
try {
    $pdo = $db->getPdo();
    $attributes = [
        PDO::ATTR_ERRMODE,
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::ATTR_EMULATE_PREPARES
    ];
    
    foreach ($attributes as $attr) {
        $value = $pdo->getAttribute($attr);
        echo "✅ PDO Attribute " . $attr . ": " . $value . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ PDO Attribute Error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 สรุปการ Debug SQL</h2>";
echo "การ debug SQL queries เสร็จสิ้นแล้ว<br>";
echo "หากไม่พบ error แสดงว่า database operations ทำงานได้ปกติ<br>";
echo "หากพบ error กรุณาแชร์ผลลัพธ์เพื่อการแก้ไขต่อไป<br>";
?> 