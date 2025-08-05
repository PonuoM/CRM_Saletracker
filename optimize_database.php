<?php
/**
 * Optimize Database
 * เพิ่ม indexes เพื่อปรับปรุงประสิทธิภาพ
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Optimization</h1>";

try {
    // Load configuration
    require_once __DIR__ . '/config/config.php';
    require_once APP_ROOT . '/app/core/Database.php';
    
    $db = new Database();
    
    echo "<h2>1. Adding Database Indexes</h2>";
    
    // Indexes for orders table
    $indexes = [
        // Orders table indexes
        "CREATE INDEX IF NOT EXISTS idx_orders_customer_id ON orders(customer_id)",
        "CREATE INDEX IF NOT EXISTS idx_orders_created_by ON orders(created_by)",
        "CREATE INDEX IF NOT EXISTS idx_orders_order_date ON orders(order_date)",
        "CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status)",
        "CREATE INDEX IF NOT EXISTS idx_orders_delivery_status ON orders(delivery_status)",
        "CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at)",
        
        // Order items table indexes
        "CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id)",
        "CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id)",
        
        // Order activities table indexes
        "CREATE INDEX IF NOT EXISTS idx_order_activities_order_id ON order_activities(order_id)",
        "CREATE INDEX IF NOT EXISTS idx_order_activities_user_id ON order_activities(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_order_activities_created_at ON order_activities(created_at)",
        
        // Customers table indexes
        "CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone)",
        "CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)",
        "CREATE INDEX IF NOT EXISTS idx_customers_total_purchase ON customers(total_purchase)",
        
        // Products table indexes
        "CREATE INDEX IF NOT EXISTS idx_products_product_code ON products(product_code)",
        "CREATE INDEX IF NOT EXISTS idx_products_is_active ON products(is_active)",
        
        // Users table indexes
        "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
        "CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $db->query($index);
            echo "✅ " . substr($index, 0, 50) . "...<br>";
        } catch (Exception $e) {
            echo "⚠️ " . substr($index, 0, 50) . "... (may already exist)<br>";
        }
    }
    
    echo "<h2>2. Analyze Table Performance</h2>";
    
    // Analyze tables
    $tables = ['orders', 'order_items', 'order_activities', 'customers', 'products', 'users'];
    foreach ($tables as $table) {
        try {
            $db->query("ANALYZE TABLE {$table}");
            echo "✅ Analyzed table: {$table}<br>";
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze table {$table}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>3. Performance Recommendations</h2>";
    echo "✅ Database indexes added<br>";
    echo "✅ Tables analyzed<br>";
    echo "<br>";
    echo "คำแนะนำเพิ่มเติม:<br>";
    echo "1. ตรวจสอบ MySQL configuration (my.cnf)<br>";
    echo "2. เพิ่ม query_cache_size หากใช้ MySQL 5.7 หรือเก่ากว่า<br>";
    echo "3. ใช้ InnoDB engine สำหรับ tables ที่มีการ update บ่อย<br>";
    echo "4. ตั้งค่า innodb_buffer_pool_size ให้เหมาะสม<br>";
    echo "5. ใช้ connection pooling หากมีผู้ใช้หลายคน<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<h2>Optimization Complete</h2>";
?> 