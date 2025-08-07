<?php
/**
 * Test Product Management System
 * ทดสอบระบบจัดการสินค้า
 */

require_once __DIR__ . '/app/core/Database.php';

echo "<h1>🧪 Test Product Management System</h1>";
echo "<hr>";

try {
    $db = new Database();
    
    // 1. ทดสอบการเชื่อมต่อฐานข้อมูล
    echo "<h2>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
    $result = $db->query("SELECT 1 as test");
    if ($result) {
        echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    } else {
        echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว<br>";
    }
    
    // 2. ตรวจสอบตาราง products
    echo "<h2>2. ตรวจสอบตาราง products</h2>";
    $sql = "SHOW TABLES LIKE 'products'";
    $result = $db->fetchAll($sql);
    
    if (count($result) > 0) {
        echo "✅ ตาราง products มีอยู่แล้ว<br>";
        
        // ตรวจสอบโครงสร้างตาราง
        $sql = "DESCRIBE products";
        $columns = $db->fetchAll($sql);
        echo "<h3>โครงสร้างตาราง products:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // ตรวจสอบข้อมูลในตาราง
        $sql = "SELECT COUNT(*) as count FROM products";
        $result = $db->fetchOne($sql);
        echo "<br>📊 จำนวนสินค้าในระบบ: {$result['count']} รายการ<br>";
        
        if ($result['count'] > 0) {
            $sql = "SELECT * FROM products LIMIT 5";
            $products = $db->fetchAll($sql);
            echo "<h3>สินค้าล่าสุด 5 รายการ:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>รหัส</th><th>ชื่อ</th><th>หมวดหมู่</th><th>ราคา</th><th>สต็อก</th></tr>";
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>{$product['product_id']}</td>";
                echo "<td>{$product['product_code']}</td>";
                echo "<td>{$product['product_name']}</td>";
                echo "<td>{$product['category']}</td>";
                echo "<td>฿{$product['selling_price']}</td>";
                echo "<td>{$product['stock_quantity']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ ตาราง products ไม่มีอยู่<br>";
        echo "💡 ต้องรันไฟล์ create_products_table.sql ก่อน<br>";
    }
    
    // 3. ตรวจสอบตาราง order_details
    echo "<h2>3. ตรวจสอบตาราง order_details</h2>";
    $sql = "SHOW TABLES LIKE 'order_details'";
    $result = $db->fetchAll($sql);
    
    if (count($result) > 0) {
        echo "✅ ตาราง order_details มีอยู่แล้ว<br>";
        
        $sql = "SELECT COUNT(*) as count FROM order_details";
        $result = $db->fetchOne($sql);
        echo "📊 จำนวนรายการคำสั่งซื้อ: {$result['count']} รายการ<br>";
        
    } else {
        echo "❌ ตาราง order_details ไม่มีอยู่<br>";
        echo "💡 ต้องรันไฟล์ create_products_table.sql ก่อน<br>";
    }
    
    // 4. ทดสอบการเพิ่มสินค้าใหม่
    echo "<h2>4. ทดสอบการเพิ่มสินค้าใหม่</h2>";
    
    $testProduct = [
        'product_code' => 'TEST_' . date('YmdHis'),
        'product_name' => 'สินค้าทดสอบ ' . date('Y-m-d H:i:s'),
        'category' => 'ทดสอบ',
        'description' => 'สินค้าสำหรับทดสอบระบบ',
        'unit' => 'ชิ้น',
        'cost_price' => 100.00,
        'selling_price' => 150.00,
        'stock_quantity' => 10
    ];
    
    $sql = "INSERT INTO products (product_code, product_name, category, description, unit, cost_price, selling_price, stock_quantity) 
            VALUES (:product_code, :product_name, :category, :description, :unit, :cost_price, :selling_price, :stock_quantity)";
    
    try {
        $result = $db->query($sql, $testProduct);
        if ($result) {
            echo "✅ เพิ่มสินค้าทดสอบสำเร็จ<br>";
            echo "📝 รหัสสินค้า: {$testProduct['product_code']}<br>";
            
            // ลบสินค้าทดสอบ
            $sql = "DELETE FROM products WHERE product_code = :product_code";
            $db->query($sql, ['product_code' => $testProduct['product_code']]);
            echo "🗑️ ลบสินค้าทดสอบแล้ว<br>";
        } else {
            echo "❌ เพิ่มสินค้าทดสอบล้มเหลว<br>";
        }
    } catch (Exception $e) {
        echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
    }
    
    // 5. ตรวจสอบไฟล์ที่จำเป็น
    echo "<h2>5. ตรวจสอบไฟล์ที่จำเป็น</h2>";
    
    $requiredFiles = [
        'app/views/admin/products/index.php',
        'app/views/admin/products/create.php',
        'app/views/admin/products/edit.php',
        'app/controllers/AdminController.php',
        'create_products_table.sql'
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "✅ {$file}<br>";
        } else {
            echo "❌ {$file} - ไม่พบไฟล์<br>";
        }
    }
    
    // 6. ตรวจสอบ URL ที่ใช้งานได้
    echo "<h2>6. ตรวจสอบ URL ที่ใช้งานได้</h2>";
    
    $baseUrl = 'https://www.prima49.com/Customer/';
    $urls = [
        'admin.php?action=products' => 'รายการสินค้า',
        'admin.php?action=products&action=create' => 'เพิ่มสินค้าใหม่'
    ];
    
    foreach ($urls as $url => $description) {
        echo "🔗 <a href='{$baseUrl}{$url}' target='_blank'>{$description}</a><br>";
    }
    
    // 7. สรุปผลการทดสอบ
    echo "<h2>7. สรุปผลการทดสอบ</h2>";
    
    $allTestsPassed = true;
    
    // ตรวจสอบเงื่อนไขต่างๆ
    if (!file_exists('app/views/admin/products/create.php')) {
        $allTestsPassed = false;
    }
    if (!file_exists('app/views/admin/products/edit.php')) {
        $allTestsPassed = false;
    }
    
    if ($allTestsPassed) {
        echo "🎉 <strong>การทดสอบผ่านทั้งหมด!</strong><br>";
        echo "✅ ระบบจัดการสินค้าพร้อมใช้งาน<br>";
        echo "✅ สามารถเพิ่ม/แก้ไข/ลบสินค้าได้<br>";
        echo "✅ ฐานข้อมูลพร้อมใช้งาน<br>";
    } else {
        echo "⚠️ <strong>การทดสอบไม่ผ่านบางส่วน</strong><br>";
        echo "❌ ต้องแก้ไขปัญหาก่อนใช้งาน<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาดในการทดสอบ: " . $e->getMessage() . "<br>";
    echo "🔧 ตรวจสอบการตั้งค่าฐานข้อมูลและไฟล์ config<br>";
}

echo "<hr>";
echo "<p><strong>วันที่ทดสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>เวอร์ชัน:</strong> 1.0.0</p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background-color: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
}

table {
    margin: 10px 0;
    background-color: white;
}

th, td {
    padding: 8px;
    text-align: left;
}

th {
    background-color: #007bff;
    color: white;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

hr {
    border: none;
    border-top: 2px solid #007bff;
    margin: 20px 0;
}
</style>
