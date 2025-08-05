<?php
// ไฟล์ทดสอบโครงสร้างฐานข้อมูล
require_once 'config/config.php';

echo "<h1>ตรวจสอบโครงสร้างฐานข้อมูล CRM SalesTracker</h1>";
echo "<hr>";

try {
    $pdo = getDBConnection();
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br><br>";
    
    // ตรวจสอบตารางทั้งหมด
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>ตารางที่มีอยู่ในฐานข้อมูล:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // ตรวจสอบโครงสร้างตาราง order_details และ order_items
    echo "<h2>ตรวจสอบตาราง order_details:</h2>";
    if (in_array('order_details', $tables)) {
        $stmt = $pdo->query('DESCRIBE order_details');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ ไม่พบตาราง order_details<br>";
    }
    
    echo "<h2>ตรวจสอบตาราง order_items:</h2>";
    if (in_array('order_items', $tables)) {
        $stmt = $pdo->query('DESCRIBE order_items');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ ไม่พบตาราง order_items<br>";
    }
    
    // ตรวจสอบข้อมูลในตาราง orders
    echo "<h2>ข้อมูลในตาราง orders:</h2>";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM orders');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "จำนวนคำสั่งซื้อ: {$result['count']}<br>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT * FROM orders LIMIT 3');
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Order ID</th><th>Order Number</th><th>Customer ID</th><th>Total Amount</th><th>Created At</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['order_id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['customer_id']}</td>";
            echo "<td>{$order['total_amount']}</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // ตรวจสอบข้อมูลในตาราง order_details (ถ้ามี)
    if (in_array('order_details', $tables)) {
        echo "<h2>ข้อมูลในตาราง order_details:</h2>";
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM order_details');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "จำนวนรายการใน order_details: {$result['count']}<br>";
    }
    
    // ตรวจสอบข้อมูลในตาราง order_items (ถ้ามี)
    if (in_array('order_items', $tables)) {
        echo "<h2>ข้อมูลในตาราง order_items:</h2>";
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM order_items');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "จำนวนรายการใน order_items: {$result['count']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>สรุปการตรวจสอบ:</h2>";
echo "<p>ไฟล์นี้จะช่วยตรวจสอบโครงสร้างฐานข้อมูลและระบุปัญหาที่อาจเกิดขึ้นกับตาราง order_details และ order_items</p>";
?> 