<?php
/**
 * Simple Supervisor System Test
 * ทดสอบระบบ Supervisor แบบง่าย
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

echo "<h1>ทดสอบระบบ Supervisor แบบง่าย</h1>";

try {
    $db = new Database();
    echo "✅ Database connection successful<br>";
    
    // Check if supervisor_id column exists
    $sql = "SHOW COLUMNS FROM users LIKE 'supervisor_id'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "✅ supervisor_id column exists<br>";
    } else {
        echo "❌ supervisor_id column does not exist<br>";
        echo "Running SQL script...<br>";
        
        // Add the column
        $sql = "ALTER TABLE `users` ADD COLUMN `supervisor_id` INT NULL AFTER `company_id`";
        $db->execute($sql);
        echo "✅ Added supervisor_id column<br>";
        
        // Add index
        $sql = "ALTER TABLE `users` ADD INDEX `idx_supervisor_id` (`supervisor_id`)";
        $db->execute($sql);
        echo "✅ Added index<br>";
        
        // Update existing telesales users
        $sql = "UPDATE `users` SET `supervisor_id` = 2 WHERE `role_id` = 4 AND `user_id` IN (3, 4)";
        $db->execute($sql);
        echo "✅ Updated existing telesales users<br>";
    }
    
    // Test Auth class
    $auth = new Auth($db);
    echo "✅ Auth class created successfully<br>";
    
    // Show current users
    $sql = "SELECT u.user_id, u.username, u.full_name, r.role_name, u.supervisor_id 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            ORDER BY u.user_id";
    
    $users = $db->fetchAll($sql);
    
    echo "<h3>Current Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Supervisor ID</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role_name']}</td>";
        echo "<td>" . ($user['supervisor_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Test completed successfully!</h3>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
