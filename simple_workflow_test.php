<?php
/**
 * Simple Workflow Test
 * ทดสอบ Workflow อย่างง่าย
 */

session_start();

// Set up session for testing (simulate logged in admin)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'admin';

echo "<h1>🔍 Simple Workflow Test</h1>";

echo "<h2>1. Session Check</h2>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'not set') . "</p>";
echo "<p>Role: " . ($_SESSION['role_name'] ?? 'not set') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h2>2. Direct API Test</h2>";

// Test the API endpoint directly
$apiUrl = "https://www.prima49.com/Customer/api/workflow.php?action=stats&debug=1";

echo "<p>Testing URL: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";

// Use file_get_contents with context
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Cookie: ' . session_name() . '=' . session_id(),
            'User-Agent: Mozilla/5.0 (compatible; PHP test)'
        ],
        'timeout' => 30
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = file_get_contents($apiUrl, false, $context);

if ($response !== false) {
    echo "<h3>API Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    
    $decoded = json_decode($response, true);
    if ($decoded !== null) {
        echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } else {
        echo htmlspecialchars($response);
    }
    echo "</pre>";
} else {
    echo "<p><strong>Failed to get response from API</strong></p>";
    echo "<p>Error: " . error_get_last()['message'] . "</p>";
}

echo "<h2>3. Direct WorkflowService Test</h2>";

try {
    require_once __DIR__ . '/app/services/WorkflowService.php';
    $workflowService = new WorkflowService();
    
    echo "<h3>Getting Workflow Stats:</h3>";
    $stats = $workflowService->getWorkflowStats();
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    print_r($stats);
    echo "</pre>";
    
    if (empty($stats) || array_sum($stats) == 0) {
        echo "<p><strong>⚠️ All stats are 0 - this might be normal if there's no data</strong></p>";
    } else {
        echo "<p><strong>✅ Stats loaded successfully</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>4. JavaScript Test</h2>";
?>

<script>
console.log('Testing JavaScript API call...');

// Test the API call that the frontend uses
fetch('api/workflow.php?action=stats&debug=1')
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed JSON:', data);
            
            // Display result on page
            const resultDiv = document.getElementById('jsResult');
            if (resultDiv) {
                resultDiv.innerHTML = '<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">' + 
                    JSON.stringify(data, null, 2) + '</pre>';
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            const resultDiv = document.getElementById('jsResult');
            if (resultDiv) {
                resultDiv.innerHTML = '<p style="color: red;">JSON Parse Error: ' + e.message + '</p>' +
                    '<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">' + text + '</pre>';
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        const resultDiv = document.getElementById('jsResult');
        if (resultDiv) {
            resultDiv.innerHTML = '<p style="color: red;">Fetch Error: ' + error.message + '</p>';
        }
    });
</script>

<div id="jsResult">
    <p>กำลังทดสอบ JavaScript API call...</p>
</div>

<p><em>หมายเหตุ: ไฟล์นี้ใช้สำหรับการทดสอบเท่านั้น ควรลบออกหลังจากแก้ไขปัญหาแล้ว</em></p>
