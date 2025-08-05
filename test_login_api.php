<?php
/**
 * Test Login API
 * ทดสอบการทำงานของ API login
 */

echo "<h2>ทดสอบ API Login</h2>";

// Test 1: Check if API file exists
$apiPath = 'api/auth/login.php';
echo "<h3>1. ตรวจสอบไฟล์ API</h3>";
if (file_exists($apiPath)) {
    echo "✅ ไฟล์ $apiPath พบแล้ว<br>";
} else {
    echo "❌ ไฟล์ $apiPath ไม่พบ<br>";
}

// Test 2: Check required files
echo "<h3>2. ตรวจสอบไฟล์ที่จำเป็น</h3>";
$requiredFiles = [
    'config/config.php',
    'app/core/Database.php',
    'app/core/Auth.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file พบแล้ว<br>";
    } else {
        echo "❌ $file ไม่พบ<br>";
    }
}

// Test 3: Test API directly
echo "<h3>3. ทดสอบ API โดยตรง</h3>";
if (file_exists($apiPath)) {
    // Simulate POST request
    $_POST['username'] = 'test';
    $_POST['password'] = 'test';
    
    // Capture output
    ob_start();
    include $apiPath;
    $output = ob_get_clean();
    
    echo "API Response:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "❌ ไม่สามารถทดสอบ API ได้เพราะไฟล์ไม่พบ<br>";
}

// Test 4: Check database connection
echo "<h3>4. ทดสอบการเชื่อมต่อฐานข้อมูล</h3>";
if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    
    try {
        $db = new Database();
        echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        
        // Test query
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM users");
        echo "✅ จำนวนผู้ใช้ในระบบ: " . $result['count'] . " คน<br>";
        
    } catch (Exception $e) {
        echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ ไม่สามารถทดสอบฐานข้อมูลได้เพราะไฟล์ config ไม่พบ<br>";
}

echo "<hr>";
echo "<p><strong>สรุป:</strong> หากมี ❌ ปรากฏ ให้แก้ไขปัญหานั้นก่อน</p>";
?> 