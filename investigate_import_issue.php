<?php
/**
 * Script to investigate import issue and find root cause
 * ตรวจสอบปัญหาการนำเข้าข้อมูลและหาสาเหตุ
 */

require_once 'app/core/Database.php';

$db = new Database();

echo "<h2>ตรวจสอบปัญหาการนำเข้าข้อมูล CSV - การวิเคราะห์แบบละเอียด</h2>";

// 1. ตรวจสอบลูกค้าที่มีหลายคำสั่งซื้อและยอดเงินผิดปกติ
echo "<h3>1. ลูกค้าที่มีหลายคำสั่งซื้อและยอดเงินผิดปกติ</h3>";

$query = "
    SELECT 
        c.customer_id,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.phone,
        COUNT(o.order_id) as order_count,
        SUM(o.total_amount) as total_orders_amount,
        AVG(o.total_amount) as avg_order_amount,
        MIN(o.total_amount) as min_order_amount,
        MAX(o.total_amount) as max_order_amount,
        MIN(o.created_at) as first_order,
        MAX(o.created_at) as last_order
    FROM customers c
    LEFT JOIN orders o ON c.customer_id = o.customer_id
    WHERE o.order_number LIKE 'EXT-%'
    GROUP BY c.customer_id
    HAVING COUNT(o.order_id) > 1
    ORDER BY order_count DESC, total_orders_amount DESC
    LIMIT 20
";

$customersWithMultipleOrders = $db->fetchAll($query);

if (!empty($customersWithMultipleOrders)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Customer ID</th>";
    echo "<th>Customer Name</th>";
    echo "<th>Phone</th>";
    echo "<th>Order Count</th>";
    echo "<th>Total Orders Amount</th>";
    echo "<th>Avg Order Amount</th>";
    echo "<th>Min Order Amount</th>";
    echo "<th>Max Order Amount</th>";
    echo "<th>First Order</th>";
    echo "<th>Last Order</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    foreach ($customersWithMultipleOrders as $customer) {
        $status = '';
        $rowStyle = '';
        
        if ($customer['order_count'] > 3) {
            $status = "มีคำสั่งซื้อมากเกินไป (อาจซ้ำ)";
            $rowStyle = 'background-color: #ffe6e6;';
        } elseif ($customer['total_orders_amount'] > 10000) {
            $status = "ยอดรวมสูงมาก (อาจผิดพลาด)";
            $rowStyle = 'background-color: #fff3cd;';
        } elseif ($customer['max_order_amount'] - $customer['min_order_amount'] > 1000) {
            $status = "ยอดสั่งซื้อต่างกันมาก (อาจผิดพลาด)";
            $rowStyle = 'background-color: #d1ecf1;';
        } else {
            $status = "ปกติ";
        }
        
        echo "<tr style='{$rowStyle}'>";
        echo "<td>" . htmlspecialchars($customer['customer_id']) . "</td>";
        echo "<td>" . htmlspecialchars($customer['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($customer['phone']) . "</td>";
        echo "<td>" . $customer['order_count'] . "</td>";
        echo "<td>" . number_format($customer['total_orders_amount'], 2) . "</td>";
        echo "<td>" . number_format($customer['avg_order_amount'], 2) . "</td>";
        echo "<td>" . number_format($customer['min_order_amount'], 2) . "</td>";
        echo "<td>" . number_format($customer['max_order_amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($customer['first_order']) . "</td>";
        echo "<td>" . htmlspecialchars($customer['last_order']) . "</td>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>ไม่พบลูกค้าที่มีหลายคำสั่งซื้อ</p>";
}

// 2. ตรวจสอบคำสั่งซื้อที่สร้างในเวลาเดียวกัน (อาจซ้ำ)
echo "<h3>2. คำสั่งซื้อที่สร้างในเวลาเดียวกัน (อาจซ้ำ)</h3>";

$query = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.net_amount,
        o.created_at,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.phone
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_number LIKE 'EXT-%'
    AND o.created_at IN (
        SELECT created_at 
        FROM orders 
        WHERE order_number LIKE 'EXT-%'
        GROUP BY created_at 
        HAVING COUNT(*) > 1
    )
    ORDER BY o.created_at DESC, o.order_id DESC
    LIMIT 30
";

$duplicateTimeOrders = $db->fetchAll($query);

if (!empty($duplicateTimeOrders)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Order ID</th>";
    echo "<th>Order Number</th>";
    echo "<th>Customer</th>";
    echo "<th>Phone</th>";
    echo "<th>Total Amount</th>";
    echo "<th>Net Amount</th>";
    echo "<th>Created At</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    foreach ($duplicateTimeOrders as $order) {
        $status = '';
        $rowStyle = '';
        
        if ($order['total_amount'] != $order['net_amount']) {
            $status = "total_amount ไม่เท่ากับ net_amount";
            $rowStyle = 'background-color: #ffe6e6;';
        } else {
            $status = "ปกติ";
        }
        
        echo "<tr style='{$rowStyle}'>";
        echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['phone']) . "</td>";
        echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . number_format($order['net_amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>ไม่พบคำสั่งซื้อที่สร้างในเวลาเดียวกัน</p>";
}

// 3. ตรวจสอบข้อมูลในตาราง order_items อย่างละเอียด
echo "<h3>3. ข้อมูลในตาราง order_items อย่างละเอียด</h3>";

$query = "
    SELECT 
        oi.order_id,
        o.order_number,
        oi.product_name,
        oi.quantity,
        oi.unit_price,
        oi.total_price,
        o.total_amount as order_total_amount,
        o.net_amount as order_net_amount,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.phone
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.order_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_number LIKE 'EXT-%'
    ORDER BY oi.order_id DESC
    LIMIT 30
";

$orderItems = $db->fetchAll($query);

if (!empty($orderItems)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Order ID</th>";
    echo "<th>Order Number</th>";
    echo "<th>Customer</th>";
    echo "<th>Phone</th>";
    echo "<th>Product Name</th>";
    echo "<th>Quantity</th>";
    echo "<th>Unit Price</th>";
    echo "<th>Item Total Price</th>";
    echo "<th>Order Total Amount</th>";
    echo "<th>Order Net Amount</th>";
    echo "<th>Calculated Total</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    foreach ($orderItems as $item) {
        $calculatedTotal = $item['quantity'] * $item['unit_price'];
        $status = '';
        $rowStyle = '';
        
        if ($item['quantity'] == 0 || $item['unit_price'] == 0) {
            $status = "ไม่มีข้อมูล quantity/unit_price";
            $rowStyle = 'background-color: #ffe6e6;';
        } elseif (abs($item['total_price'] - $item['order_total_amount']) > 0.01) {
            $status = "item_total_price ไม่ตรงกับ order_total_amount";
            $rowStyle = 'background-color: #fff3cd;';
        } elseif (abs($calculatedTotal - $item['total_price']) > 0.01) {
            $status = "คำนวณไม่ตรง (quantity × unit_price ≠ total_price)";
            $rowStyle = 'background-color: #d1ecf1;';
        } elseif ($item['order_total_amount'] != $item['order_net_amount']) {
            $status = "total_amount ≠ net_amount";
            $rowStyle = 'background-color: #f8d7da;';
        } else {
            $status = "ปกติ";
        }
        
        echo "<tr style='{$rowStyle}'>";
        echo "<td>" . htmlspecialchars($item['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($item['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($item['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
        echo "<td>" . ($item['quantity'] ?? 'N/A') . "</td>";
        echo "<td>" . number_format($item['unit_price'] ?? 0, 2) . "</td>";
        echo "<td>" . number_format($item['total_price'] ?? 0, 2) . "</td>";
        echo "<td>" . number_format($item['order_total_amount'], 2) . "</td>";
        echo "<td>" . number_format($item['order_net_amount'], 2) . "</td>";
        echo "<td>" . number_format($calculatedTotal, 2) . "</td>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>ไม่พบข้อมูลในตาราง order_items</p>";
}

// 4. ตรวจสอบคำสั่งซื้อที่ไม่มีข้อมูลใน order_items
echo "<h3>4. คำสั่งซื้อที่ไม่มีข้อมูลใน order_items</h3>";

$query = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.net_amount,
        o.created_at,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.phone
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.order_number LIKE 'EXT-%'
    AND oi.order_id IS NULL
    ORDER BY o.created_at DESC
    LIMIT 20
";

$ordersWithoutItems = $db->fetchAll($query);

if (!empty($ordersWithoutItems)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Order ID</th>";
    echo "<th>Order Number</th>";
    echo "<th>Customer</th>";
    echo "<th>Phone</th>";
    echo "<th>Total Amount</th>";
    echo "<th>Net Amount</th>";
    echo "<th>Created At</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    foreach ($ordersWithoutItems as $order) {
        $status = '';
        $rowStyle = '';
        
        if ($order['total_amount'] != $order['net_amount']) {
            $status = "total_amount ≠ net_amount";
            $rowStyle = 'background-color: #ffe6e6;';
        } else {
            $status = "ไม่มีข้อมูล order_items";
            $rowStyle = 'background-color: #fff3cd;';
        }
        
        echo "<tr style='{$rowStyle}'>";
        echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['phone']) . "</td>";
        echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . number_format($order['net_amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>ไม่พบคำสั่งซื้อที่ไม่มีข้อมูลใน order_items</p>";
}

// 5. ตรวจสอบการคำนวณยอดรวมของลูกค้า
echo "<h3>5. ตรวจสอบการคำนวณยอดรวมของลูกค้า</h3>";

$query = "
    SELECT 
        c.customer_id,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.phone,
        c.total_purchase_amount as customer_total_purchase,
        SUM(o.total_amount) as calculated_total_from_orders,
        COUNT(o.order_id) as order_count
    FROM customers c
    LEFT JOIN orders o ON c.customer_id = o.customer_id AND o.order_number LIKE 'EXT-%'
    GROUP BY c.customer_id
    HAVING COUNT(o.order_id) > 0
    ORDER BY ABS(c.total_purchase_amount - SUM(o.total_amount)) DESC
    LIMIT 20
";

$customerTotalMismatch = $db->fetchAll($query);

if (!empty($customerTotalMismatch)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Customer ID</th>";
    echo "<th>Customer Name</th>";
    echo "<th>Phone</th>";
    echo "<th>Customer Total Purchase</th>";
    echo "<th>Calculated Total from Orders</th>";
    echo "<th>Difference</th>";
    echo "<th>Order Count</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    foreach ($customerTotalMismatch as $customer) {
        $difference = abs($customer['customer_total_purchase'] - $customer['calculated_total_from_orders']);
        $status = '';
        $rowStyle = '';
        
        if ($difference > 0.01) {
            $status = "ยอดรวมไม่ตรงกัน";
            $rowStyle = 'background-color: #ffe6e6;';
        } else {
            $status = "ปกติ";
        }
        
        echo "<tr style='{$rowStyle}'>";
        echo "<td>" . htmlspecialchars($customer['customer_id']) . "</td>";
        echo "<td>" . htmlspecialchars($customer['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($customer['phone']) . "</td>";
        echo "<td>" . number_format($customer['customer_total_purchase'], 2) . "</td>";
        echo "<td>" . number_format($customer['calculated_total_from_orders'], 2) . "</td>";
        echo "<td>" . number_format($difference, 2) . "</td>";
        echo "<td>" . $customer['order_count'] . "</td>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>ไม่พบปัญหาการคำนวณยอดรวมของลูกค้า</p>";
}

// 6. สรุปปัญหาและแนวทางแก้ไข
echo "<h3>6. สรุปปัญหาและแนวทางแก้ไข</h3>";

echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>ปัญหาที่พบ:</h4>";
echo "<ul>";
echo "<li><strong>ข้อมูลซ้ำ:</strong> ลูกค้าบางคนมีหลายคำสั่งซื้อในเวลาเดียวกัน</li>";
echo "<li><strong>การคำนวณผิด:</strong> total_amount และ net_amount ไม่ตรงกับข้อมูลจริง</li>";
echo "<li><strong>ข้อมูลไม่ครบ:</strong> ตาราง order_items ไม่มีข้อมูล quantity และ unit_price</li>";
echo "<li><strong>ยอดรวมไม่ตรง:</strong> ยอดรวมในตาราง customers ไม่ตรงกับยอดรวมจาก orders</li>";
echo "</ul>";

echo "<h4>สาเหตุหลัก:</h4>";
echo "<ul>";
echo "<li><strong>การนำเข้าข้อมูลซ้ำ:</strong> ระบบสร้างคำสั่งซื้อใหม่แทนการอัปเดตข้อมูลเก่า</li>";
echo "<li><strong>การคำนวณผิดพลาด:</strong> ใช้ข้อมูลจาก CSV โดยไม่ตรวจสอบความถูกต้อง</li>";
echo "<li><strong>การแมปคอลัมน์ผิด:</strong> คอลัมน์ใน CSV ไม่ตรงกับที่ระบบคาดหวัง</li>";
echo "</ul>";

echo "<h4>แนวทางแก้ไข:</h4>";
echo "<ul>";
echo "<li><strong>ลบข้อมูลซ้ำ:</strong> ลบคำสั่งซื้อที่ซ้ำกันและข้อมูลที่ไม่ถูกต้อง</li>";
echo "<li><strong>แก้ไขการคำนวณ:</strong> ปรับปรุงการคำนวณ total_amount และ net_amount</li>";
echo "<li><strong>ตรวจสอบข้อมูล:</strong> ตรวจสอบ CSV ก่อนนำเข้า</li>";
echo "<li><strong>อัปเดตยอดรวม:</strong> คำนวณยอดรวมลูกค้าใหม่จากข้อมูล orders</li>";
echo "</ul>";
echo "</div>";

// 7. ปุ่มแก้ไขปัญหา
echo "<h3>7. แก้ไขปัญหา</h3>";

echo "<form method='POST' style='margin-bottom: 20px;'>";
echo "<input type='hidden' name='action' value='fix_import_issues'>";
echo "<button type='submit' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;'>";
echo "แก้ไขปัญหาการนำเข้าข้อมูล";
echo "</button>";
echo "</form>";

// จัดการการแก้ไขปัญหา
if ($_POST['action'] ?? '' === 'fix_import_issues') {
    echo "<h3>กำลังแก้ไขปัญหา...</h3>";
    
    try {
        // 1. ลบคำสั่งซื้อที่ซ้ำกัน (เก็บอันใหม่สุด)
        $deleteQuery = "
            DELETE o1 FROM orders o1
            INNER JOIN orders o2
            WHERE o1.order_id < o2.order_id
            AND o1.customer_id = o2.customer_id
            AND o1.created_at = o2.created_at
            AND o1.order_number LIKE 'EXT-%'
            AND o2.order_number LIKE 'EXT-%'
        ";
        
        $result = $db->query($deleteQuery);
        echo "<p>✅ ลบคำสั่งซื้อที่ซ้ำกันสำเร็จ</p>";
        
        // 2. แก้ไข total_amount และ net_amount ให้ตรงกัน
        $updateQuery = "
            UPDATE orders 
            SET net_amount = total_amount 
            WHERE order_number LIKE 'EXT-%' 
            AND net_amount != total_amount
        ";
        
        $result = $db->query($updateQuery);
        echo "<p>✅ แก้ไข net_amount ให้ตรงกับ total_amount สำเร็จ</p>";
        
        // 3. อัปเดตยอดรวมลูกค้า
        $updateCustomerTotalQuery = "
            UPDATE customers c
            SET total_purchase_amount = (
                SELECT COALESCE(SUM(o.total_amount), 0)
                FROM orders o
                WHERE o.customer_id = c.customer_id
                AND o.order_number LIKE 'EXT-%'
            )
        ";
        
        $result = $db->query($updateCustomerTotalQuery);
        echo "<p>✅ อัปเดตยอดรวมลูกค้าสำเร็จ</p>";
        
        echo "<script>setTimeout(() => window.location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<p>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr>";
echo "<h3>หมายเหตุ</h3>";
echo "<ul>";
echo "<li>ปัญหาหลักคือการนำเข้าข้อมูลซ้ำและการคำนวณผิดพลาด</li>";
echo "<li>ไม่ควรแก้ไขโดยการคูณด้วยตัวเลข</li>";
echo "<li>ควรลบข้อมูลซ้ำและนำเข้าข้อมูลใหม่ด้วย Template ที่ถูกต้อง</li>";
echo "<li>ตรวจสอบข้อมูลใน CSV ก่อนนำเข้าเสมอ</li>";
echo "<li>ควรใช้คอลัมน์ 'ยอดรวม' ใน CSV แทนการคำนวณจาก จำนวน × ราคาต่อชิ้น</li>";
echo "</ul>";
?>
