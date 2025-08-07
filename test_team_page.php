<?php
/**
 * Test Team Page Access
 * ทดสอบการเข้าถึงหน้า team.php
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

echo "<h1>ทดสอบการเข้าถึงหน้า team.php</h1>";

try {
    $db = new Database();
    echo "✅ Database connection successful<br>";
    
    $auth = new Auth($db);
    echo "✅ Auth class created successfully<br>";
    
    // Check if user is logged in
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        echo "✅ User is logged in<br>";
        echo "User ID: {$user['user_id']}<br>";
        echo "Username: {$user['username']}<br>";
        echo "Role: {$user['role_name']}<br>";
        
        if ($user['role_name'] === 'supervisor') {
            echo "✅ User is supervisor - should be able to access team.php<br>";
            
            // Test team functions
            $teamMembers = $auth->getTeamMembers($user['user_id']);
            echo "✅ Team members count: " . count($teamMembers) . "<br>";
            
            $teamSummary = $auth->getTeamSummary($user['user_id']);
            if ($teamSummary) {
                echo "✅ Team summary retrieved successfully<br>";
                echo "- Total team members: {$teamSummary['total_team_members']}<br>";
                echo "- Total customers: {$teamSummary['total_customers']}<br>";
                echo "- Total orders: {$teamSummary['total_orders']}<br>";
                echo "- Total sales: ฿" . number_format($teamSummary['total_sales_amount'] ?? 0, 2) . "<br>";
            } else {
                echo "❌ Failed to get team summary<br>";
            }
            
            echo "<br><strong>✅ User should be able to access team.php successfully!</strong><br>";
            echo "<a href='team.php' target='_blank'>Click here to test team.php</a>";
            
        } else {
            echo "❌ User is not supervisor (role: {$user['role_name']}) - will be redirected to dashboard<br>";
            echo "Expected role: supervisor<br>";
            echo "Current role: {$user['role_name']}<br>";
        }
        
    } else {
        echo "❌ User is not logged in - will be redirected to login.php<br>";
        echo "Please log in first with a supervisor account<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<br><hr><br>";
echo "<h2>Debug Information:</h2>";
echo "<p>Current session data:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
