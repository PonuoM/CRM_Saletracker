<?php
/**
 * Debug API Error
 * ตรวจสอบปัญหา API
 */

session_start();

// Simulate session
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['full_name'] = 'Test User';

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/controllers/CustomerController.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug API Error</h1>";

try {
    echo "<h2>1. ตรวจสอบ Database Connection</h2>";
    $db = new Database();
    echo "✅ Database connection successful<br>";
    
    echo "<h2>2. ตรวจสอบ Session</h2>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h2>3. ตรวจสอบ CustomerController</h2>";
    $controller = new CustomerController();
    echo "✅ CustomerController created successfully<br>";
    
    echo "<h2>4. ตรวจสอบตาราง call_logs</h2>";
    $result = $db->query("SHOW TABLES LIKE 'call_logs'");
    if ($result && $result->rowCount() > 0) {
        echo "✅ Table call_logs exists<br>";
    } else {
        echo "❌ Table call_logs does not exist<br>";
    }
    
    echo "<h2>5. ตรวจสอบตาราง customer_activities</h2>";
    $result = $db->query("SHOW TABLES LIKE 'customer_activities'");
    if ($result && $result->rowCount() > 0) {
        echo "✅ Table customer_activities exists<br>";
    } else {
        echo "❌ Table customer_activities does not exist<br>";
    }
    
    echo "<h2>6. ตรวจสอบลูกค้า ID 1</h2>";
    $customer = $db->fetchOne("SELECT customer_id FROM customers WHERE customer_id = 1");
    if ($customer) {
        echo "✅ Customer ID 1 exists<br>";
    } else {
        echo "❌ Customer ID 1 does not exist<br>";
    }
    
    echo "<h2>7. ทดสอบ API logCall โดยตรง</h2>";
    
    // Simulate POST data
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $input = [
        'customer_id' => 1,
        'call_type' => 'outbound',
        'call_status' => 'answered',
        'call_result' => 'interested',
        'duration' => 5,
        'notes' => 'Test call from debug',
        'next_action' => 'Follow up',
        'next_followup' => null
    ];
    
    // Mock the input
    file_put_contents('php://temp', json_encode($input));
    rewind(fopen('php://temp', 'r'));
    
    echo "Input data: <pre>" . print_r($input, true) . "</pre>";
    
    // Test the logCall method
    ob_start();
    try {
        $controller->logCall();
        $output = ob_get_clean();
        echo "✅ API logCall executed successfully<br>";
        echo "Output: <pre>" . htmlspecialchars($output) . "</pre>";
    } catch (Exception $e) {
        $output = ob_get_clean();
        echo "❌ API logCall error: " . $e->getMessage() . "<br>";
        echo "Output: <pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>8. ตรวจสอบ Error Log</h2>";
$errorLog = error_get_last();
if ($errorLog) {
    echo "Last error: <pre>" . print_r($errorLog, true) . "</pre>";
} else {
    echo "✅ No errors in error log<br>";
}
?> 