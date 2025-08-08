<?php
/**
 * Script to check order_items table data
 * ตรวจสอบข้อมูลในตาราง order_items
 */

require_once 'app/core/Database.php';

$db = new Database();

echo "<h2>ตรวจสอบข้อมูลในตาราง order_items</h2>";

// ตรวจสอบข้อมูล order_items ที่เกี่ยวข้องกับ orders ที่มีปัญหา
$query = "
    SELECT 
        oi.order_id,
        oi.product_name,
        oi.quantity,
        oi.unit_price,
        oi.total_price,
        o.order_number,
        o.total_amount as order_total_amount,
        o.net_amount as order_net_amount,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.order_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE oi.order_id IN (
        SELECT order_id FROM orders 
        WHERE total_amount <= 50 
        ORDER BY created_at DESC 
        LIMIT 20
    )
    ORDER BY oi.order_id DESC, oi.created_at DESC
";

$orderItems = $db->fetchAll($query);

if (!empty($orderItems)) {
    echo "<h3>ข้อมูล order_items ที่เกี่ยวข้องกับ orders ที่มีปัญหา</h3>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Order ID</th>";
    echo "<th>Order Number</th>";
    echo "<th>Customer</th>";
    echo "<th>Product Name</th>";
    echo "<th>Quantity</th>";
    echo "<th>Unit Price</th>";
    echo "<th>Item Total Price</th>";
    echo "<th>Order Total Amount</th>";
    echo "<th>Order Net Amount</th>";
    echo "</tr>";
    
    foreach ($orderItems as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($item['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
        echo "<td>" . ($item['quantity'] ?? 'N/A') . "</td>";
        echo "<td>" . number_format($item['unit_price'] ?? 0, 2) . "</td>";
        echo "<td>" . number_format($item['total_price'] ?? 0, 2) . "</td>";
        echo "<td>" . number_format($item['order_total_amount'], 2) . "</td>";
        echo "<td>" . number_format($item['order_net_amount'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<h3>ไม่พบข้อมูล order_items</h3>";
    echo "<p>ตรวจสอบว่าตาราง order_items มีข้อมูลหรือไม่</p>";
}

// ตรวจสอบจำนวน order_items ต่อ order
echo "<h3>สถิติ order_items ต่อ order</h3>";

$statsQuery = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        COUNT(oi.order_id) as item_count,
        SUM(oi.total_price) as sum_item_total,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.total_amount <= 50
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
    LIMIT 20
";

$stats = $db->fetchAll($statsQuery);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Order ID</th>";
echo "<th>Order Number</th>";
echo "<th>Customer</th>";
echo "<th>Order Total Amount</th>";
echo "<th>Item Count</th>";
echo "<th>Sum Item Total</th>";
echo "<th>Status</th>";
echo "</tr>";

foreach ($stats as $stat) {
    $status = '';
    $rowStyle = '';
    
    if ($stat['item_count'] == 0) {
        $status = "ไม่มี order_items";
        $rowStyle = 'background-color: #ffe6e6;';
    } elseif ($stat['sum_item_total'] > 0 && abs($stat['total_amount'] - $stat['sum_item_total']) > 1) {
        $status = "total_amount ไม่ตรงกับ sum_item_total";
        $rowStyle = 'background-color: #fff3cd;';
    } else {
        $status = "ปกติ";
    }
    
    echo "<tr style='{$rowStyle}'>";
    echo "<td>" . htmlspecialchars($stat['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($stat['order_number']) . "</td>";
    echo "<td>" . htmlspecialchars($stat['customer_name']) . "</td>";
    echo "<td>" . number_format($stat['total_amount'], 2) . "</td>";
    echo "<td>" . $stat['item_count'] . "</td>";
    echo "<td>" . number_format($stat['sum_item_total'] ?? 0, 2) . "</td>";
    echo "<td>" . htmlspecialchars($status) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>สรุป</h3>";
echo "<ul>";
echo "<li>ตรวจสอบว่าตาราง order_items มีข้อมูลที่ถูกต้องหรือไม่</li>";
echo "<li>หากไม่มี order_items แสดงว่าข้อมูลถูกนำเข้าผิดวิธี</li>";
echo "<li>หากมี order_items แต่ total_amount ไม่ตรงกัน แสดงว่ามีการคำนวณผิดพลาด</li>";
echo "</ul>";
?>
