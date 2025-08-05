<?php
/**
 * Test Import/Export System
 * ทดสอบระบบนำเข้าและส่งออกข้อมูล
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Load services
require_once 'app/services/ImportExportService.php';

// Initialize service
$importExportService = new ImportExportService();

echo "<h1>ทดสอบระบบ Import/Export</h1>";

// Test 1: Export customers
echo "<h2>1. ทดสอบการส่งออกข้อมูลลูกค้า</h2>";
try {
    $customers = $importExportService->exportCustomersToCSV();
    echo "<p>✅ ส่งออกข้อมูลลูกค้าสำเร็จ: " . count($customers) . " รายการ</p>";
    
    if (count($customers) > 0) {
        echo "<h3>ตัวอย่างข้อมูล:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อ</th><th>เบอร์โทร</th><th>สถานะ</th><th>อุณหภูมิ</th><th>เกรด</th></tr>";
        
        $sample = array_slice($customers, 0, 3);
        foreach ($sample as $customer) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($customer['id']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['name']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['status']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['temperature']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['grade']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

// Test 2: Export orders
echo "<h2>2. ทดสอบการส่งออกข้อมูลคำสั่งซื้อ</h2>";
try {
    $orders = $importExportService->exportOrdersToCSV();
    echo "<p>✅ ส่งออกข้อมูลคำสั่งซื้อสำเร็จ: " . count($orders) . " รายการ</p>";
    
    if (count($orders) > 0) {
        echo "<h3>ตัวอย่างข้อมูล:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th></tr>";
        
        $sample = array_slice($orders, 0, 3);
        foreach ($sample as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
            echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($order['delivery_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

// Test 3: Summary report
echo "<h2>3. ทดสอบการสร้างรายงานสรุป</h2>";
try {
    $reports = $importExportService->exportSummaryReport();
    echo "<p>✅ สร้างรายงานสรุปสำเร็จ</p>";
    
    echo "<h3>สถิติลูกค้า:</h3>";
    echo "<ul>";
    echo "<li>ลูกค้าทั้งหมด: " . $reports['customer_stats']['total_customers'] . "</li>";
    echo "<li>ลูกค้าที่ใช้งาน: " . $reports['customer_stats']['active_customers'] . "</li>";
    echo "<li>ลูกค้า Hot: " . $reports['customer_stats']['hot_customers'] . "</li>";
    echo "<li>ลูกค้า Warm: " . $reports['customer_stats']['warm_customers'] . "</li>";
    echo "<li>ลูกค้า Cold: " . $reports['customer_stats']['cold_customers'] . "</li>";
    echo "<li>ลูกค้า Frozen: " . $reports['customer_stats']['frozen_customers'] . "</li>";
    echo "</ul>";
    
    echo "<h3>สถิติคำสั่งซื้อ:</h3>";
    echo "<ul>";
    echo "<li>คำสั่งซื้อทั้งหมด: " . $reports['order_stats']['total_orders'] . "</li>";
    echo "<li>รอดำเนินการ: " . $reports['order_stats']['pending_orders'] . "</li>";
    echo "<li>กำลังดำเนินการ: " . $reports['order_stats']['processing_orders'] . "</li>";
    echo "<li>จัดส่งแล้ว: " . $reports['order_stats']['shipped_orders'] . "</li>";
    echo "<li>จัดส่งสำเร็จ: " . $reports['order_stats']['delivered_orders'] . "</li>";
    echo "<li>ยกเลิก: " . $reports['order_stats']['cancelled_orders'] . "</li>";
    echo "</ul>";
    
    echo "<h3>สถิติรายได้:</h3>";
    echo "<ul>";
    echo "<li>รายได้รวม: ฿" . number_format($reports['revenue_stats']['total_revenue'], 2) . "</li>";
    echo "<li>ยอดเฉลี่ยต่อคำสั่ง: ฿" . number_format($reports['revenue_stats']['average_order_value'], 2) . "</li>";
    echo "<li>จำนวนคำสั่งซื้อ: " . $reports['revenue_stats']['total_orders'] . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

// Test 4: Check template file
echo "<h2>4. ทดสอบไฟล์ Template</h2>";
$templateFile = 'templates/customers_template.csv';
if (file_exists($templateFile)) {
    echo "<p>✅ ไฟล์ template พบ: " . $templateFile . "</p>";
    echo "<p>ขนาดไฟล์: " . number_format(filesize($templateFile)) . " bytes</p>";
    
    // Check encoding
    $content = file_get_contents($templateFile);
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "<p>✅ ไฟล์มี UTF-8 BOM</p>";
    } else {
        echo "<p>⚠️ ไฟล์ไม่มี UTF-8 BOM</p>";
    }
    
    // Show first few lines
    $lines = explode("\n", $content);
    echo "<h3>เนื้อหาไฟล์ (3 บรรทัดแรก):</h3>";
    echo "<pre>";
    for ($i = 0; $i < min(3, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>❌ ไฟล์ template ไม่พบ: " . $templateFile . "</p>";
}

// Test 5: Check directories
echo "<h2>5. ทดสอบโฟลเดอร์ที่จำเป็น</h2>";
$directories = ['uploads', 'backups', 'templates'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "<p>✅ โฟลเดอร์ {$dir} พบ</p>";
    } else {
        echo "<p>❌ โฟลเดอร์ {$dir} ไม่พบ</p>";
    }
}

echo "<h2>สรุปการทดสอบ</h2>";
echo "<p>หากทุกข้อทดสอบผ่าน ✅ แสดงว่าระบบ Import/Export พร้อมใช้งาน</p>";
echo "<p><a href='import-export.php'>ไปยังหน้า Import/Export</a></p>";
?> 