<?php
/**
 * Update Payment Method ENUM
 * อัปเดต ENUM ของ payment_method เพื่อเพิ่ม 'receive_before_payment' (รับสินค้าก่อนชำระ)
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Update Payment Method ENUM</h1>";

try {
    // Load configuration
    require_once __DIR__ . '/config/config.php';
    require_once APP_ROOT . '/app/core/Database.php';
    
    $db = new Database();
    
    echo "<h2>1. Check current payment_method ENUM</h2>";
    
    // Get current ENUM values
    $result = $db->fetchOne("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = :db_name 
        AND TABLE_NAME = 'orders' 
        AND COLUMN_NAME = 'payment_method'
    ", ['db_name' => DB_NAME]);
    
    if ($result) {
        echo "Current payment_method ENUM: " . $result['COLUMN_TYPE'] . "<br>";
        
        // Check if 'receive_before_payment' is already in the ENUM
        if (strpos($result['COLUMN_TYPE'], "'receive_before_payment'") !== false) {
            echo "✅ 'receive_before_payment' payment method already exists<br>";
        } else {
            echo "❌ 'receive_before_payment' payment method missing, updating...<br>";
            
            // Update the ENUM to include 'receive_before_payment'
            $db->query("
                ALTER TABLE orders 
                MODIFY COLUMN payment_method 
                ENUM('cash', 'transfer', 'cod', 'credit', 'receive_before_payment', 'other') DEFAULT 'cash'
            ");
            
            echo "✅ Payment method ENUM updated successfully<br>";
            
            // Verify the update
            $updatedResult = $db->fetchOne("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = :db_name 
                AND TABLE_NAME = 'orders' 
                AND COLUMN_NAME = 'payment_method'
            ", ['db_name' => DB_NAME]);
            
            echo "Updated payment_method ENUM: " . $updatedResult['COLUMN_TYPE'] . "<br>";
        }
    } else {
        echo "❌ Could not retrieve payment_method column information<br>";
    }
    
    echo "<h2>2. Test inserting order with 'receive_before_payment' payment method</h2>";
    $db->beginTransaction();
    
    try {
        $testData = [
            'order_number' => 'TEST-' . date('Ymd') . '-003',
            'customer_id' => 1,
            'created_by' => 1,
            'order_date' => date('Y-m-d'),
            'total_amount' => 200.00,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'net_amount' => 200.00,
            'payment_method' => 'receive_before_payment',
            'payment_status' => 'pending',
            'delivery_date' => null,
            'delivery_address' => 'Test Receive Before Payment Address',
            'delivery_status' => 'pending',
            'notes' => 'Test Receive Before Payment order'
        ];
        
        $orderId = $db->insert('orders', $testData);
        echo "✅ Test Receive Before Payment order inserted successfully, Order ID: {$orderId}<br>";
        
        // Verify the payment method was saved correctly
        $savedOrder = $db->fetchOne(
            "SELECT order_id, payment_method FROM orders WHERE order_id = :order_id",
            ['order_id' => $orderId]
        );
        
        echo "Saved payment method: " . $savedOrder['payment_method'] . "<br>";
        
    } catch (Exception $e) {
        echo "❌ Test Receive Before Payment insert failed: " . $e->getMessage() . "<br>";
    }
    
    $db->rollback();
    echo "✅ Test transaction rolled back<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<h2>Update Complete</h2>";
echo "<p>Payment method options now include:</p>";
echo "<ul>";
echo "<li>cash - เงินสด</li>";
echo "<li>transfer - โอนเงิน</li>";
echo "<li>cod - เก็บเงินปลายทาง</li>";
echo "<li>credit - เครดิต</li>";
echo "<li>receive_before_payment - รับสินค้าก่อนชำระ</li>";
echo "<li>other - อื่นๆ</li>";
echo "</ul>";
?> 