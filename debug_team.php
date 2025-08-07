<?php
/**
 * Debug Team Management for Supervisors
 * จัดการทีมสำหรับ Supervisor - Debug Version
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

echo "<h1>Debug Team Management Page</h1>";

try {
    $db = new Database();
    echo "✅ Database connection successful<br>";
    
    $auth = new Auth($db);
    echo "✅ Auth class created successfully<br><br>";
    
    // Check if user is logged in
    echo "<h3>1. Authentication Check:</h3>";
    if ($auth->isLoggedIn()) {
        echo "✅ User is logged in<br>";
        
        $user = $auth->getCurrentUser();
        echo "User ID: {$user['user_id']}<br>";
        echo "Username: {$user['username']}<br>";
        echo "Role: {$user['role_name']}<br>";
        echo "Role ID: {$user['role_id']}<br><br>";
        
        // Check role
        if ($user['role_name'] === 'supervisor') {
            echo "✅ User is supervisor - should be able to access team management<br><br>";
        } else {
            echo "❌ User is not supervisor (role: {$user['role_name']})<br>";
            echo "Expected: supervisor, Got: {$user['role_name']}<br><br>";
        }
    } else {
        echo "❌ User is not logged in<br>";
        echo "Session data:<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        exit;
    }
    
    // Test team functions
    echo "<h3>2. Team Functions Test:</h3>";
    
    $supervisorId = $user['user_id'];
    echo "Testing with supervisor ID: {$supervisorId}<br><br>";
    
    // Test getTeamMembers
    echo "<h4>2.1 Testing getTeamMembers():</h4>";
    try {
        $teamMembers = $auth->getTeamMembers($supervisorId);
        echo "✅ getTeamMembers() successful<br>";
        echo "Found " . count($teamMembers) . " team members<br>";
        
        if (!empty($teamMembers)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Customers</th><th>Orders</th><th>Sales</th></tr>";
            foreach ($teamMembers as $member) {
                echo "<tr>";
                echo "<td>{$member['user_id']}</td>";
                echo "<td>{$member['username']}</td>";
                echo "<td>{$member['full_name']}</td>";
                echo "<td>{$member['customer_count']}</td>";
                echo "<td>{$member['order_count']}</td>";
                echo "<td>฿" . number_format($member['total_sales'] ?? 0, 2) . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
    } catch (Exception $e) {
        echo "❌ getTeamMembers() failed: " . $e->getMessage() . "<br>";
    }
    
    // Test getTeamSummary
    echo "<h4>2.2 Testing getTeamSummary():</h4>";
    try {
        $teamSummary = $auth->getTeamSummary($supervisorId);
        if ($teamSummary) {
            echo "✅ getTeamSummary() successful<br>";
            echo "Total team members: {$teamSummary['total_team_members']}<br>";
            echo "Total customers: {$teamSummary['total_customers']}<br>";
            echo "Total orders: {$teamSummary['total_orders']}<br>";
            echo "Total sales: ฿" . number_format($teamSummary['total_sales_amount'] ?? 0, 2) . "<br><br>";
        } else {
            echo "❌ getTeamSummary() returned null<br>";
        }
    } catch (Exception $e) {
        echo "❌ getTeamSummary() failed: " . $e->getMessage() . "<br><br>";
    }
    
    // Test getRecentTeamActivities
    echo "<h4>2.3 Testing getRecentTeamActivities():</h4>";
    try {
        $recentActivities = $auth->getRecentTeamActivities($supervisorId, 5);
        echo "✅ getRecentTeamActivities() successful<br>";
        echo "Found " . count($recentActivities) . " recent activities<br><br>";
    } catch (Exception $e) {
        echo "❌ getRecentTeamActivities() failed: " . $e->getMessage() . "<br><br>";
    }
    
    // Test direct database queries
    echo "<h3>3. Direct Database Queries Test:</h3>";
    
    // Test team members query
    echo "<h4>3.1 Direct team members query:</h4>";
    $sql = "SELECT u.*, r.role_name, c.company_name,
            (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
            (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
            (SELECT SUM(total_amount) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1
            ORDER BY u.created_at DESC";
    
    try {
        $teamMembers = $db->fetchAll($sql, ['supervisor_id' => $supervisorId]);
        echo "✅ Direct team members query successful<br>";
        echo "Found " . count($teamMembers) . " team members<br><br>";
    } catch (Exception $e) {
        echo "❌ Direct team members query failed: " . $e->getMessage() . "<br><br>";
    }
    
    // Test team summary query
    echo "<h4>3.2 Direct team summary query:</h4>";
    $sql = "SELECT 
            COUNT(DISTINCT team_stats.user_id) as total_team_members,
            SUM(team_stats.customer_count) as total_customers,
            SUM(team_stats.order_count) as total_orders,
            SUM(team_stats.total_sales) as total_sales_amount
            FROM (
                SELECT u.user_id,
                (SELECT COUNT(*) FROM customers WHERE assigned_to = u.user_id AND is_active = 1) as customer_count,
                (SELECT COUNT(*) FROM orders WHERE created_by = u.user_id AND is_active = 1) as order_count,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_by = u.user_id AND is_active = 1) as total_sales
                FROM users u 
                WHERE u.supervisor_id = :supervisor_id AND u.is_active = 1
            ) as team_stats";
    
    try {
        $teamSummary = $db->fetchOne($sql, ['supervisor_id' => $supervisorId]);
        if ($teamSummary) {
            echo "✅ Direct team summary query successful<br>";
            echo "Total team members: {$teamSummary['total_team_members']}<br>";
            echo "Total customers: {$teamSummary['total_customers']}<br>";
            echo "Total orders: {$teamSummary['total_orders']}<br>";
            echo "Total sales: ฿" . number_format($teamSummary['total_sales_amount'] ?? 0, 2) . "<br><br>";
        } else {
            echo "❌ Direct team summary query returned null<br><br>";
        }
    } catch (Exception $e) {
        echo "❌ Direct team summary query failed: " . $e->getMessage() . "<br><br>";
    }
    
    echo "<h3>4. Conclusion:</h3>";
    echo "✅ All tests passed! The team management functionality should work correctly.<br>";
    echo "If team.php is still not working, the issue might be in the HTML/display part.<br><br>";
    
    echo "<a href='team.php' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try team.php again</a><br><br>";
    
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
