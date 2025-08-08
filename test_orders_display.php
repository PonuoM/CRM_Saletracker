<?php
/**
 * Test script to check orders data and total_amount values
 */

require_once 'app/core/Database.php';

$db = new Database();

echo "<h2>ตรวจสอบข้อมูลในตาราง orders</h2>";

// ดึงข้อมูลคำสั่งซื้อล่าสุด 10 รายการ
$query = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.net_amount,
        o.quantity,
        oi.quantity as item_quantity,
        oi.unit_price,
        oi.total_price as item_total_price,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    ORDER BY o.created_at DESC
    LIMIT 10
";

$orders = $db->fetchAll($query);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Order ID</th>";
echo "<th>Order Number</th>";
echo "<th>Customer</th>";
echo "<th>total_amount (orders)</th>";
echo "<th>net_amount (orders)</th>";
echo "<th>quantity (orders)</th>";
echo "<th>item_quantity (order_items)</th>";
echo "<th>unit_price (order_items)</th>";
echo "<th>item_total_price (order_items)</th>";
echo "</tr>";

foreach ($orders as $order) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
    echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
    echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
    echo "<td>" . number_format($order['net_amount'], 2) . "</td>";
    echo "<td>" . ($order['quantity'] ?? 'N/A') . "</td>";
    echo "<td>" . ($order['item_quantity'] ?? 'N/A') . "</td>";
    echo "<td>" . number_format($order['unit_price'] ?? 0, 2) . "</td>";
    echo "<td>" . number_format($order['item_total_price'] ?? 0, 2) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>สรุป:</h3>";
echo "<p>ตรวจสอบว่าคอลัมน์ total_amount ในตาราง orders มีค่าที่ถูกต้องหรือไม่</p>";
echo "<p>หาก total_amount แสดงค่าเดียวกับ quantity แสดงว่ามีปัญหาในการคำนวณ</p>";
?>
