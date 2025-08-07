<?php
/**
 * Test Team Page Redirect
 * ทดสอบการเข้าถึงหน้า team.php และตรวจสอบการ redirect
 */

echo "<h1>ทดสอบการเข้าถึงหน้า team.php</h1>";

// Start output buffering to capture any redirects
ob_start();

// Simulate accessing team.php
echo "<h3>1. Testing team.php access:</h3>";

// Check if we can include the team.php file
try {
    // Capture any output or errors
    $output = '';
    $error = '';
    
    // Start output buffering
    ob_start();
    
    // Try to include team.php
    include 'team.php';
    
    // Get the output
    $output = ob_get_contents();
    
    // Clean the buffer
    ob_end_clean();
    
    if (!empty($output)) {
        echo "✅ team.php executed successfully<br>";
        echo "Output length: " . strlen($output) . " characters<br>";
        echo "First 500 characters of output:<br>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
    } else {
        echo "❌ team.php produced no output (might have redirected)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error including team.php: " . $e->getMessage() . "<br>";
}

echo "<br><h3>2. Testing direct access:</h3>";
echo "<p>Try accessing team.php directly:</p>";
echo "<a href='team.php' target='_blank' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Open team.php in new tab</a><br><br>";

echo "<h3>3. Testing with JavaScript:</h3>";
echo "<button onclick='testTeamPage()' style='background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test team.php with JavaScript</button><br><br>";

echo "<div id='result'></div>";

echo "<h3>4. Current Session Status:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>5. Manual Test:</h3>";
echo "<p>If the above tests don't work, try these steps:</p>";
echo "<ol>";
echo "<li>Make sure you're logged in as supervisor</li>";
echo "<li>Open team.php in a new browser tab</li>";
echo "<li>Check the browser's developer tools for any errors</li>";
echo "<li>Check if there are any redirects in the Network tab</li>";
echo "</ol>";
?>

<script>
function testTeamPage() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<p>Testing team.php...</p>';
    
    fetch('team.php')
        .then(response => {
            resultDiv.innerHTML += '<p>Response status: ' + response.status + '</p>';
            resultDiv.innerHTML += '<p>Response URL: ' + response.url + '</p>';
            
            if (response.redirected) {
                resultDiv.innerHTML += '<p style="color: red;">⚠️ Page was redirected to: ' + response.url + '</p>';
            }
            
            return response.text();
        })
        .then(data => {
            resultDiv.innerHTML += '<p>Response length: ' + data.length + ' characters</p>';
            resultDiv.innerHTML += '<p>First 200 characters:</p>';
            resultDiv.innerHTML += '<pre>' + data.substring(0, 200) + '</pre>';
        })
        .catch(error => {
            resultDiv.innerHTML += '<p style="color: red;">Error: ' + error.message + '</p>';
        });
}
</script>
