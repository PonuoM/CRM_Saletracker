<?php
/**
 * Update Payment Method ENUM
 * อัปเดต ENUM ของ payment_method เพื่อเพิ่ม 'cod'
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
        
        // Check if 'cod' is already in the ENUM
        if (strpos($result['COLUMN_TYPE'], "'cod'") !== false) {
            echo "✅ 'cod' payment method already exists<br>";
        } else {
            echo "❌ 'cod' payment method missing, updating...<br>";
            
            // Update the ENUM to include 'cod'
            $db->query("
                ALTER TABLE orders 
                MODIFY COLUMN payment_method 
                ENUM('cash', 'transfer', 'cod', 'credit', 'other') DEFAULT 'cash'
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
    
    echo "<h2>2. Test inserting order with 'cod' payment method</h2>";
    $db->beginTransaction();
    
    try {
        $testData = [
            'order_number' => 'TEST-' . date('Ymd') . '-002',
            'customer_id' => 1,
            'created_by' => 1,
            'order_date' => date('Y-m-d'),
            'total_amount' => 150.00,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'net_amount' => 150.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'delivery_date' => null,
            'delivery_address' => 'Test COD Address',
            'delivery_status' => 'pending',
            'notes' => 'Test COD order'
        ];
        
        $orderId = $db->insert('orders', $testData);
        echo "✅ Test COD order inserted successfully, Order ID: {$orderId}<br>";
        
        // Verify the payment method was saved correctly
        $savedOrder = $db->fetchOne(
            "SELECT order_id, payment_method FROM orders WHERE order_id = :order_id",
            ['order_id' => $orderId]
        );
        
        echo "Saved payment method: " . $savedOrder['payment_method'] . "<br>";
        
    } catch (Exception $e) {
        echo "❌ Test COD insert failed: " . $e->getMessage() . "<br>";
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
?> 