<?php
/**
 * Test Supervisor Login
 * ทดสอบการเข้าสู่ระบบของ Supervisor
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

echo "<h1>ทดสอบการเข้าสู่ระบบของ Supervisor</h1>";

try {
    $db = new Database();
    $auth = new Auth($db);
    
    echo "✅ Database connection successful<br>";
    echo "✅ Auth class created successfully<br><br>";
    
    // Check if user is already logged in
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        echo "✅ User is already logged in<br>";
        echo "User ID: {$user['user_id']}<br>";
        echo "Username: {$user['username']}<br>";
        echo "Role: {$user['role_name']}<br><br>";
        
        if ($user['role_name'] === 'supervisor') {
            echo "✅ User is supervisor - can access team management<br>";
            echo "<a href='team.php' class='btn btn-primary'>Go to Team Management</a><br><br>";
        } else {
            echo "❌ User is not supervisor (role: {$user['role_name']})<br>";
            echo "<a href='logout.php' class='btn btn-warning'>Logout</a><br><br>";
        }
    } else {
        echo "❌ User is not logged in<br><br>";
        
        // Show login form
        echo "<h3>เข้าสู่ระบบด้วยบัญชี Supervisor:</h3>";
        echo "<form method='POST' action=''>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label>Username: </label>";
        echo "<input type='text' name='username' value='supervisor' style='padding: 5px; width: 200px;'><br>";
        echo "</div>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label>Password: </label>";
        echo "<input type='password' name='password' placeholder='Enter password' style='padding: 5px; width: 200px;'><br>";
        echo "</div>";
        echo "<input type='submit' value='Login' style='padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
        echo "</form><br>";
        
        // Handle login
        if ($_POST) {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (!empty($username) && !empty($password)) {
                $result = $auth->login($username, $password);
                
                if ($result['success']) {
                    echo "<div style='background-color: #d4edda; padding: 10px; border-radius: 4px; color: #155724;'>";
                    echo "✅ Login successful!<br>";
                    echo "Username: {$result['user']['username']}<br>";
                    echo "Role: {$result['user']['role_name']}<br>";
                    echo "</div><br>";
                    
                    echo "<a href='team.php' class='btn btn-primary'>Go to Team Management</a><br><br>";
                    
                    // Refresh page to show logged in status
                    echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
                    
                } else {
                    echo "<div style='background-color: #f8d7da; padding: 10px; border-radius: 4px; color: #721c24;'>";
                    echo "❌ Login failed: " . $result['message'] . "<br>";
                    echo "</div><br>";
                }
            } else {
                echo "<div style='background-color: #fff3cd; padding: 10px; border-radius: 4px; color: #856404;'>";
                echo "⚠️ Please enter both username and password<br>";
                echo "</div><br>";
            }
        }
    }
    
    // Show supervisor account info
    echo "<h3>ข้อมูลบัญชี Supervisor:</h3>";
    $sql = "SELECT u.user_id, u.username, u.full_name, r.role_name, u.is_active 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE r.role_name = 'supervisor' AND u.is_active = 1";
    
    $supervisors = $db->fetchAll($sql);
    
    if (!empty($supervisors)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($supervisors as $supervisor) {
            echo "<tr>";
            echo "<td>{$supervisor['user_id']}</td>";
            echo "<td><strong>{$supervisor['username']}</strong></td>";
            echo "<td>{$supervisor['full_name']}</td>";
            echo "<td>{$supervisor['role_name']}</td>";
            echo "<td>" . ($supervisor['is_active'] ? 'Active' : 'Inactive') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ ไม่พบบัญชี Supervisor</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 10px; border-radius: 4px; color: #721c24;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><hr><br>";
echo "<h2>Debug Information:</h2>";
echo "<p>Current session data:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>

<style>
.btn {
    display: inline-block;
    padding: 8px 16px;
    margin: 5px;
    text-decoration: none;
    border-radius: 4px;
    cursor: pointer;
}
.btn-primary {
    background-color: #007bff;
    color: white;
}
.btn-warning {
    background-color: #ffc107;
    color: #212529;
}
</style>
