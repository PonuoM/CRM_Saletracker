<?php
/**
 * Script to check and fix total_amount values in orders table
 * ตรวจสอบและแก้ไขค่า total_amount ในตาราง orders
 */

require_once 'app/core/Database.php';

$db = new Database();

echo "<h2>ตรวจสอบและแก้ไข total_amount ในตาราง orders</h2>";

// 1. ตรวจสอบข้อมูลปัจจุบัน
echo "<h3>1. ข้อมูลปัจจุบันในตาราง orders</h3>";

$query = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.net_amount,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        oi.quantity as item_quantity,
        oi.unit_price,
        oi.total_price as item_total_price,
        o.created_at
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    ORDER BY o.created_at DESC
    LIMIT 50
";

$orders = $db->fetchAll($query);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Order ID</th>";
echo "<th>Order Number</th>";
echo "<th>Customer</th>";
echo "<th>total_amount (orders)</th>";
echo "<th>net_amount (orders)</th>";
echo "<th>item_quantity</th>";
echo "<th>unit_price</th>";
echo "<th>item_total_price</th>";
echo "<th>Created At</th>";
echo "</tr>";

$suspiciousOrders = [];

foreach ($orders as $order) {
    $isSuspicious = false;
    $suspiciousReason = '';
    
    // ตรวจสอบว่าค่า total_amount น่าจะเป็น quantity หรือไม่
    if ($order['total_amount'] > 0) {
        // กรณี 1: ถ้า total_amount เท่ากับ quantity หรือใกล้เคียง
        if ($order['item_quantity'] > 0 && abs($order['total_amount'] - $order['item_quantity']) < 1) {
            $isSuspicious = true;
            $suspiciousReason = "total_amount เท่ากับ quantity";
        }
        
        // กรณี 2: ถ้า total_amount น้อยกว่า unit_price (ไม่สมเหตุสมผล)
        if ($order['unit_price'] > 0 && $order['total_amount'] < $order['unit_price']) {
            $isSuspicious = true;
            $suspiciousReason = "total_amount น้อยกว่า unit_price";
        }
        
        // กรณี 3: ถ้า total_amount เป็นตัวเลขเล็กๆ (1, 2, 10) และไม่มี item_quantity หรือ unit_price
        if ($order['total_amount'] <= 50 && ($order['item_quantity'] == null || $order['unit_price'] == 0)) {
            $isSuspicious = true;
            $suspiciousReason = "total_amount เป็นตัวเลขเล็กๆ และไม่มีข้อมูล item";
        }
        
        // กรณี 4: ถ้า total_amount เป็นจำนวนเต็มเล็กๆ ที่น่าจะเป็น quantity
        if ($order['total_amount'] <= 20 && $order['total_amount'] == intval($order['total_amount'])) {
            $isSuspicious = true;
            $suspiciousReason = "total_amount เป็นจำนวนเต็มเล็กๆ (น่าจะเป็น quantity)";
        }
    }
    
    if ($isSuspicious) {
        $suspiciousOrders[] = $order;
        echo "<tr style='background-color: #ffe6e6;'>"; // แดงอ่อน
    } else {
        echo "<tr>";
    }
    
    echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
    echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
    echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
    echo "<td>" . number_format($order['net_amount'], 2) . "</td>";
    echo "<td>" . ($order['item_quantity'] ?? 'N/A') . "</td>";
    echo "<td>" . number_format($order['unit_price'] ?? 0, 2) . "</td>";
    echo "<td>" . number_format($order['item_total_price'] ?? 0, 2) . "</td>";
    echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// 2. แสดงคำสั่งซื้อที่น่าสงสัย
if (!empty($suspiciousOrders)) {
    echo "<h3>2. คำสั่งซื้อที่น่าสงสัย (อาจมีปัญหา)</h3>";
    echo "<p style='color: red;'>พบคำสั่งซื้อที่ total_amount อาจไม่ถูกต้อง:</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #ffcccc;'>";
    echo "<th>Order ID</th>";
    echo "<th>Order Number</th>";
    echo "<th>Customer</th>";
    echo "<th>total_amount (ปัจจุบัน)</th>";
    echo "<th>item_quantity</th>";
    echo "<th>unit_price</th>";
    echo "<th>total_amount (ที่ควรเป็น)</th>";
    echo "<th>การแก้ไข</th>";
    echo "<th>หมายเหตุ</th>";
    echo "</tr>";
    
    foreach ($suspiciousOrders as $order) {
        $correctTotal = 0;
        $fixReason = '';
        $notes = '';
        
        // ตรวจสอบว่าควรแก้ไขอย่างไร
        if ($order['unit_price'] > 0 && $order['item_quantity'] > 0) {
            $correctTotal = $order['unit_price'] * $order['item_quantity'];
            $fixReason = "คำนวณจาก unit_price × quantity";
        } elseif ($order['item_total_price'] > 0) {
            $correctTotal = $order['item_total_price'];
            $fixReason = "ใช้ค่า item_total_price";
        } elseif ($order['total_amount'] <= 50) {
            // ถ้า total_amount เป็นตัวเลขเล็กๆ และไม่มีข้อมูลอื่น ให้ตั้งเป็น 0 หรือค่าที่เหมาะสม
            if ($order['total_amount'] <= 5) {
                $correctTotal = 0; // ของแถมหรือไม่มีค่า
                $fixReason = "ตั้งเป็น 0 (ของแถม)";
                $notes = "total_amount เป็นตัวเลขเล็กๆ อาจเป็นของแถม";
            } else {
                $correctTotal = $order['total_amount'] * 100; // สมมติว่าเป็นจำนวน × 100
                $fixReason = "คูณด้วย 100 (สมมติว่าเป็นจำนวน)";
                $notes = "total_amount เป็นตัวเลขเล็กๆ อาจเป็นจำนวน × 100";
            }
        } else {
            $correctTotal = $order['total_amount']; // ไม่แก้ไข
            $fixReason = "ไม่สามารถคำนวณได้";
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
        echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . ($order['item_quantity'] ?? 'N/A') . "</td>";
        echo "<td>" . number_format($order['unit_price'] ?? 0, 2) . "</td>";
        echo "<td>" . number_format($correctTotal, 2) . "</td>";
        echo "<td>" . htmlspecialchars($fixReason) . "</td>";
        echo "<td>" . htmlspecialchars($notes) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 3. ปุ่มแก้ไข
    echo "<h3>3. แก้ไขข้อมูล</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='fix'>";
    echo "<button type='submit' style='background-color: #ff4444; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "แก้ไข total_amount ที่ไม่ถูกต้อง";
    echo "</button>";
    echo "</form>";
    
} else {
    echo "<h3>2. ผลการตรวจสอบ</h3>";
    echo "<p style='color: green;'>✅ ไม่พบคำสั่งซื้อที่มีปัญหา total_amount ดูปกติ</p>";
}

// 4. จัดการการแก้ไข
if ($_POST['action'] ?? '' === 'fix' && !empty($suspiciousOrders)) {
    echo "<h3>4. กำลังแก้ไขข้อมูล...</h3>";
    
    $fixedCount = 0;
    
    foreach ($suspiciousOrders as $order) {
        $correctTotal = 0;
        
        // ใช้ตรรกะเดียวกับข้างต้น
        if ($order['unit_price'] > 0 && $order['item_quantity'] > 0) {
            $correctTotal = $order['unit_price'] * $order['item_quantity'];
        } elseif ($order['item_total_price'] > 0) {
            $correctTotal = $order['item_total_price'];
        } elseif ($order['total_amount'] <= 50) {
            if ($order['total_amount'] <= 5) {
                $correctTotal = 0; // ของแถม
            } else {
                $correctTotal = $order['total_amount'] * 100; // สมมติว่าเป็นจำนวน × 100
            }
        } else {
            continue; // ข้ามถ้าไม่สามารถคำนวณได้
        }
        
        // อัปเดต total_amount และ net_amount
        $updateQuery = "UPDATE orders SET total_amount = ?, net_amount = ? WHERE order_id = ?";
        $result = $db->query($updateQuery, [$correctTotal, $correctTotal, $order['order_id']]);
        
        if ($result) {
            $fixedCount++;
            echo "<p>✅ แก้ไข Order ID {$order['order_id']}: {$order['total_amount']} → " . number_format($correctTotal, 2) . "</p>";
        } else {
            echo "<p>❌ ไม่สามารถแก้ไข Order ID {$order['order_id']}</p>";
        }
    }
    
    echo "<h4>สรุปการแก้ไข</h4>";
    echo "<p>แก้ไขสำเร็จ: {$fixedCount} รายการ</p>";
    
    // รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
    echo "<script>setTimeout(() => window.location.reload(), 3000);</script>";
}

echo "<hr>";
echo "<h3>หมายเหตุ</h3>";
echo "<ul>";
echo "<li>สคริปต์นี้จะตรวจสอบคำสั่งซื้อที่ total_amount อาจไม่ถูกต้อง</li>";
echo "<li>หากพบปัญหา จะคำนวณค่า total_amount ที่ถูกต้องจาก unit_price × quantity</li>";
echo "<li>หรือใช้ค่า item_total_price จากตาราง order_items</li>";
echo "<li>สำหรับตัวเลขเล็กๆ (≤5) จะตั้งเป็น 0 (ของแถม)</li>";
echo "<li>สำหรับตัวเลขเล็กๆ (6-50) จะคูณด้วย 100 (สมมติว่าเป็นจำนวน)</li>";
echo "<li>การแก้ไขจะอัปเดตทั้ง total_amount และ net_amount</li>";
echo "</ul>";
?>
