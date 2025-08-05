<?php
/**
 * Database Check Script
 * ตรวจสอบว่าตารางที่จำเป็นสำหรับระบบจัดการคำสั่งซื้อมีอยู่ครบหรือไม่
 */

require_once __DIR__ . '/config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Check - Order Management System</h1>";
echo "<p><strong>Environment:</strong> " . ENVIRONMENT . "</p>";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Host:</strong> " . DB_HOST . ":" . DB_PORT . "</p>";
echo "<hr>";

try {
    // Connect to database
    require_once APP_ROOT . '/app/core/Database.php';
    $db = new Database();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Required tables for Order Management System
    $requiredTables = [
        'orders' => [
            'description' => 'ตารางคำสั่งซื้อ',
            'required_columns' => ['order_id', 'order_number', 'customer_id', 'created_by', 'order_date', 'total_amount', 'net_amount']
        ],
        'order_items' => [
            'description' => 'ตารางรายการสินค้าในคำสั่งซื้อ',
            'required_columns' => ['item_id', 'order_id', 'product_id', 'quantity', 'unit_price', 'total_price']
        ],
        'order_activities' => [
            'description' => 'ตารางกิจกรรมของคำสั่งซื้อ',
            'required_columns' => ['activity_id', 'order_id', 'user_id', 'activity_type', 'description', 'created_at']
        ],
        'products' => [
            'description' => 'ตารางสินค้า',
            'required_columns' => ['product_id', 'product_code', 'product_name', 'selling_price']
        ],
        'customers' => [
            'description' => 'ตารางลูกค้า',
            'required_columns' => ['customer_id', 'first_name', 'last_name', 'phone']
        ],
        'users' => [
            'description' => 'ตารางผู้ใช้',
            'required_columns' => ['user_id', 'username', 'full_name', 'role_id']
        ],
        'roles' => [
            'description' => 'ตารางบทบาท',
            'required_columns' => ['role_id', 'role_name']
        ]
    ];
    
    echo "<h2>Table Check Results</h2>";
    $allTablesExist = true;
    
    foreach ($requiredTables as $tableName => $tableInfo) {
        echo "<h3>{$tableName} - {$tableInfo['description']}</h3>";
        
        // Check if table exists
        $result = $db->fetchOne("SHOW TABLES LIKE '{$tableName}'");
        if ($result) {
            echo "<p style='color: green;'>✅ Table '{$tableName}' exists</p>";
            
            // Check required columns
            $columns = $db->fetchAll("SHOW COLUMNS FROM {$tableName}");
            $columnNames = array_column($columns, 'Field');
            
            $missingColumns = [];
            foreach ($tableInfo['required_columns'] as $requiredColumn) {
                if (!in_array($requiredColumn, $columnNames)) {
                    $missingColumns[] = $requiredColumn;
                }
            }
            
            if (empty($missingColumns)) {
                echo "<p style='color: green;'>✅ All required columns exist</p>";
            } else {
                echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
                $allTablesExist = false;
            }
            
            // Show table structure
            echo "<details>";
            echo "<summary>Table Structure</summary>";
            echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
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
            echo "</details>";
            
        } else {
            echo "<p style='color: red;'>❌ Table '{$tableName}' does not exist</p>";
            $allTablesExist = false;
        }
        
        echo "<hr>";
    }
    
    // Summary
    echo "<h2>Summary</h2>";
    if ($allTablesExist) {
        echo "<p style='color: green; font-weight: bold;'>✅ All required tables and columns exist. Order Management System should work correctly.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Some required tables or columns are missing. Please run the database setup script.</p>";
        echo "<p><a href='database_setup.sql' target='_blank'>View Database Setup Script</a></p>";
    }
    
    // Test data check
    echo "<h2>Test Data Check</h2>";
    
    // Check if there are any orders
    $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM orders");
    echo "<p>Orders: " . ($orderCount['count'] ?? 0) . " records</p>";
    
    // Check if there are any products
    $productCount = $db->fetchOne("SELECT COUNT(*) as count FROM products");
    echo "<p>Products: " . ($productCount['count'] ?? 0) . " records</p>";
    
    // Check if there are any customers
    $customerCount = $db->fetchOne("SELECT COUNT(*) as count FROM customers");
    echo "<p>Customers: " . ($customerCount['count'] ?? 0) . " records</p>";
    
    // Check if there are any users
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users");
    echo "<p>Users: " . ($userCount['count'] ?? 0) . " records</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 20px;
    background-color: #f8f9fa;
}
h1, h2, h3 {
    color: #333;
}
table {
    font-size: 12px;
}
details {
    margin: 10px 0;
}
summary {
    cursor: pointer;
    font-weight: bold;
    color: #2563eb;
}
</style> 