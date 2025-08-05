<?php
/**
 * Check Orders Table Structure
 * ตรวจสอบโครงสร้างตาราง orders
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Orders Table Structure Check</h1>";

try {
    // Load configuration
    require_once __DIR__ . '/config/config.php';
    require_once APP_ROOT . '/app/core/Database.php';
    
    $db = new Database();
    
    echo "<h2>1. Check if orders table exists</h2>";
    if ($db->tableExists('orders')) {
        echo "✅ orders table exists<br>";
    } else {
        echo "❌ orders table does not exist<br>";
        exit;
    }
    
    echo "<h2>2. Get orders table structure</h2>";
    $structure = $db->getTableStructure('orders');
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>3. Check required columns for order creation</h2>";
    $requiredColumns = [
        'order_id',
        'order_number', 
        'customer_id',
        'created_by',
        'order_date',
        'total_amount',
        'discount_amount',
        'discount_percentage',
        'net_amount',
        'payment_method',
        'payment_status',
        'delivery_date',
        'delivery_address',
        'delivery_status',
        'notes',
        'created_at'
    ];
    
    $existingColumns = array_column($structure, 'Field');
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $existingColumns)) {
            echo "✅ Column {$column} exists<br>";
        } else {
            echo "❌ Column {$column} missing<br>";
        }
    }
    
    echo "<h2>4. Check order_items table</h2>";
    if ($db->tableExists('order_items')) {
        echo "✅ order_items table exists<br>";
        $itemsStructure = $db->getTableStructure('order_items');
        echo "Columns: " . implode(', ', array_column($itemsStructure, 'Field')) . "<br>";
    } else {
        echo "❌ order_items table missing<br>";
    }
    
    echo "<h2>5. Check order_activities table</h2>";
    if ($db->tableExists('order_activities')) {
        echo "✅ order_activities table exists<br>";
        $activitiesStructure = $db->getTableStructure('order_activities');
        echo "Columns: " . implode(', ', array_column($activitiesStructure, 'Field')) . "<br>";
    } else {
        echo "❌ order_activities table missing<br>";
    }
    
    echo "<h2>6. Test sample insert (will be rolled back)</h2>";
    $db->beginTransaction();
    
    try {
        $testData = [
            'order_number' => 'TEST-' . date('Ymd') . '-001',
            'customer_id' => 1,
            'created_by' => 1,
            'order_date' => date('Y-m-d'),
            'total_amount' => 100.00,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'net_amount' => 100.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'delivery_date' => null,
            'delivery_address' => 'Test Address',
            'delivery_status' => 'pending',
            'notes' => 'Test insert'
        ];
        
        $orderId = $db->insert('orders', $testData);
        echo "✅ Test insert successful, Order ID: {$orderId}<br>";
        
        // Test order_items insert
        $itemData = [
            'order_id' => $orderId,
            'product_id' => 1,
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00
        ];
        
        $itemId = $db->insert('order_items', $itemData);
        echo "✅ Test order_items insert successful, Item ID: {$itemId}<br>";
        
        // Test order_activities insert
        $activityData = [
            'order_id' => $orderId,
            'user_id' => 1,
            'activity_type' => 'created',
            'description' => 'Test activity'
        ];
        
        $activityId = $db->insert('order_activities', $activityData);
        echo "✅ Test order_activities insert successful, Activity ID: {$activityId}<br>";
        
    } catch (Exception $e) {
        echo "❌ Test insert failed: " . $e->getMessage() . "<br>";
    }
    
    $db->rollback();
    echo "✅ Test transaction rolled back<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<h2>Check Complete</h2>";
?> 