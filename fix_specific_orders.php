<?php
/**
 * Script to fix specific orders with incorrect total_amount values
 * แก้ไขคำสั่งซื้อที่มี total_amount เป็นตัวเลขเล็กๆ ที่น่าจะเป็นจำนวน
 */

require_once 'app/core/Database.php';

$db = new Database();

echo "<h2>แก้ไขคำสั่งซื้อที่มี total_amount เป็นตัวเลขเล็กๆ</h2>";

// ตรวจสอบคำสั่งซื้อที่มี total_amount <= 50
$query = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.net_amount,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        COUNT(oi.order_id) as item_count,
        SUM(oi.total_price) as sum_item_total,
        o.created_at
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.total_amount <= 50
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$orders = $db->fetchAll($query);

echo "<h3>คำสั่งซื้อที่มี total_amount <= 50</h3>";

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Order ID</th>";
echo "<th>Order Number</th>";
echo "<th>Customer</th>";
echo "<th>Current Total Amount</th>";
echo "<th>Item Count</th>";
echo "<th>Sum Item Total</th>";
echo "<th>Suggested Fix</th>";
echo "<th>New Total Amount</th>";
echo "</tr>";

$ordersToFix = [];

foreach ($orders as $order) {
    $suggestedFix = '';
    $newTotalAmount = 0;
    
    // ตรวจสอบว่าควรแก้ไขอย่างไร
    if ($order['sum_item_total'] > 0) {
        // ถ้ามี sum_item_total ให้ใช้ค่านั้น
        $newTotalAmount = $order['sum_item_total'];
        $suggestedFix = "ใช้ sum_item_total";
    } elseif ($order['total_amount'] <= 5) {
        // ถ้า total_amount <= 5 ให้ตั้งเป็น 0 (ของแถม)
        $newTotalAmount = 0;
        $suggestedFix = "ตั้งเป็น 0 (ของแถม)";
    } elseif ($order['total_amount'] <= 20) {
        // ถ้า total_amount <= 20 ให้คูณด้วย 100 (สมมติว่าเป็นจำนวน)
        $newTotalAmount = $order['total_amount'] * 100;
        $suggestedFix = "คูณด้วย 100 (สมมติว่าเป็นจำนวน)";
    } else {
        // กรณีอื่นๆ ให้คูณด้วย 50
        $newTotalAmount = $order['total_amount'] * 50;
        $suggestedFix = "คูณด้วย 50 (สมมติว่าเป็นจำนวน)";
    }
    
    $ordersToFix[] = [
        'order_id' => $order['order_id'],
        'current_total' => $order['total_amount'],
        'new_total' => $newTotalAmount,
        'fix_reason' => $suggestedFix
    ];
    
    $rowStyle = $newTotalAmount != $order['total_amount'] ? 'background-color: #ffe6e6;' : '';
    
    echo "<tr style='{$rowStyle}'>";
    echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
    echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
    echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
    echo "<td>" . $order['item_count'] . "</td>";
    echo "<td>" . number_format($order['sum_item_total'] ?? 0, 2) . "</td>";
    echo "<td>" . htmlspecialchars($suggestedFix) . "</td>";
    echo "<td>" . number_format($newTotalAmount, 2) . "</td>";
    echo "</tr>";
}

echo "</table>";

// แสดงตัวเลือกการแก้ไข
if (!empty($ordersToFix)) {
    echo "<h3>ตัวเลือกการแก้ไข</h3>";
    
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='fix_specific'>";
    
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h4>เลือกวิธีการแก้ไข:</h4>";
    echo "<input type='radio' name='fix_method' value='auto' id='auto' checked>";
    echo "<label for='auto'>แก้ไขอัตโนมัติตามที่แนะนำข้างต้น</label><br>";
    echo "<input type='radio' name='fix_method' value='zero' id='zero'>";
    echo "<label for='zero'>ตั้งทั้งหมดเป็น 0 (ของแถม)</label><br>";
    echo "<input type='radio' name='fix_method' value='multiply_100' id='multiply_100'>";
    echo "<label for='multiply_100'>คูณทั้งหมดด้วย 100</label><br>";
    echo "<input type='radio' name='fix_method' value='multiply_50' id='multiply_50'>";
    echo "<label for='multiply_50'>คูณทั้งหมดด้วย 50</label><br>";
    echo "</div>";
    
    echo "<button type='submit' style='background-color: #ff4444; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "แก้ไขตามที่เลือก";
    echo "</button>";
    echo "</form>";
}

// จัดการการแก้ไข
if ($_POST['action'] ?? '' === 'fix_specific' && !empty($ordersToFix)) {
    $fixMethod = $_POST['fix_method'] ?? 'auto';
    
    echo "<h3>กำลังแก้ไขข้อมูล...</h3>";
    
    $fixedCount = 0;
    
    foreach ($ordersToFix as $order) {
        $newTotalAmount = 0;
        
        switch ($fixMethod) {
            case 'zero':
                $newTotalAmount = 0;
                break;
            case 'multiply_100':
                $newTotalAmount = $order['current_total'] * 100;
                break;
            case 'multiply_50':
                $newTotalAmount = $order['current_total'] * 50;
                break;
            default: // auto
                $newTotalAmount = $order['new_total'];
                break;
        }
        
        // อัปเดต total_amount และ net_amount
        $updateQuery = "UPDATE orders SET total_amount = ?, net_amount = ? WHERE order_id = ?";
        $result = $db->query($updateQuery, [$newTotalAmount, $newTotalAmount, $order['order_id']]);
        
        if ($result) {
            $fixedCount++;
            echo "<p>✅ แก้ไข Order ID {$order['order_id']}: {$order['current_total']} → " . number_format($newTotalAmount, 2) . "</p>";
        } else {
            echo "<p>❌ ไม่สามารถแก้ไข Order ID {$order['order_id']}</p>";
        }
    }
    
    echo "<h4>สรุปการแก้ไข</h4>";
    echo "<p>แก้ไขสำเร็จ: {$fixedCount} รายการ</p>";
    echo "<p>วิธีการแก้ไข: " . htmlspecialchars($fixMethod) . "</p>";
    
    // รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
    echo "<script>setTimeout(() => window.location.reload(), 3000);</script>";
}

echo "<hr>";
echo "<h3>หมายเหตุ</h3>";
echo "<ul>";
echo "<li>สคริปต์นี้จะแก้ไขคำสั่งซื้อที่มี total_amount <= 50</li>";
echo "<li>เลือกวิธีการแก้ไขที่เหมาะสมกับข้อมูลของคุณ</li>";
echo "<li>หากไม่แน่ใจ ให้เลือก 'แก้ไขอัตโนมัติ'</li>";
echo "<li>การแก้ไขจะอัปเดตทั้ง total_amount และ net_amount</li>";
echo "</ul>";

echo "<h3>คำแนะนำ</h3>";
echo "<ul>";
echo "<li><strong>ตั้งเป็น 0:</strong> หากเป็นของแถมหรือไม่มีค่า</li>";
echo "<li><strong>คูณด้วย 100:</strong> หาก total_amount เป็นจำนวนและราคาต่อชิ้นประมาณ 100 บาท</li>";
echo "<li><strong>คูณด้วย 50:</strong> หาก total_amount เป็นจำนวนและราคาต่อชิ้นประมาณ 50 บาท</li>";
echo "<li><strong>แก้ไขอัตโนมัติ:</strong> ระบบจะเลือกวิธีที่เหมาะสมที่สุด</li>";
echo "</ul>";
?>
