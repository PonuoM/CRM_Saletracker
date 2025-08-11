<?php
/**
 * Simple Team Test
 * ทดสอบข้อมูลทีมอย่างง่าย
 */

session_start();

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

$db = new Database();
$auth = new Auth($db);

// Check if user is supervisor
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role_name'] !== 'supervisor') {
    header('Location: dashboard.php');
    exit;
}

$user = $auth->getCurrentUser();
$supervisorId = $user['user_id'];

$pageTitle = 'Simple Team Test - CRM SalesTracker';
$currentPage = 'test';

// Start content capture
ob_start();
?>

<!-- Simple Team Test -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Simple Team Test</h1>
                <div>
                    <a href="team.php" class="btn btn-primary">Go to Team Page</a>
                </div>
            </div>

            <!-- Raw Database Query -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Raw Database Query</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            echo "<p><strong>Supervisor ID:</strong> {$supervisorId}</p>";
                            
                            try {
                                // Simple query to get team members
                                $sql = "SELECT user_id, username, full_name, is_active FROM users WHERE supervisor_id = ? ORDER BY user_id";
                                $stmt = $db->getConnection()->prepare($sql);
                                $stmt->execute([$supervisorId]);
                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                echo "<h6>SQL Query:</h6>";
                                echo "<pre class='bg-dark text-light p-2 small'>{$sql}</pre>";
                                
                                echo "<h6>Results:</h6>";
                                echo "<p><strong>Count:</strong> " . count($results) . "</p>";
                                
                                if (!empty($results)) {
                                    echo "<table class='table table-striped'>";
                                    echo "<thead><tr><th>User ID</th><th>Username</th><th>Full Name</th><th>Active</th></tr></thead>";
                                    echo "<tbody>";
                                    foreach ($results as $row) {
                                        echo "<tr>";
                                        echo "<td>" . $row['user_id'] . "</td>";
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                        echo "<td>" . ($row['is_active'] ? '✅ Active' : '❌ Inactive') . "</td>";
                                        echo "</tr>";
                                    }
                                    echo "</tbody></table>";
                                    
                                    // Check for duplicates
                                    $usernames = array_column($results, 'username');
                                    $duplicates = array_count_values($usernames);
                                    $hasDuplicates = false;
                                    
                                    echo "<h6>Duplicate Check:</h6>";
                                    foreach ($duplicates as $username => $count) {
                                        if ($count > 1) {
                                            echo "<div class='alert alert-danger'>❌ Duplicate: {$username} appears {$count} times</div>";
                                            $hasDuplicates = true;
                                        }
                                    }
                                    
                                    if (!$hasDuplicates) {
                                        echo "<div class='alert alert-success'>✅ No duplicates found in database</div>";
                                    }
                                    
                                } else {
                                    echo "<div class='alert alert-warning'>No team members found</div>";
                                }
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test team.php Logic -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Test team.php Logic</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                // Simulate the exact logic from team.php
                                $userIds = $db->fetchAll("SELECT DISTINCT user_id FROM users WHERE supervisor_id = ?", [$supervisorId]);
                                
                                echo "<h6>Step 1: Get unique user IDs</h6>";
                                echo "<p><strong>Count:</strong> " . count($userIds) . "</p>";
                                echo "<pre class='bg-light p-2 small'>" . json_encode($userIds, JSON_PRETTY_PRINT) . "</pre>";
                                
                                $teamMembers = [];
                                
                                foreach ($userIds as $userIdRow) {
                                    $userId = $userIdRow['user_id'];
                                    
                                    $userDetails = $db->fetchOne("
                                        SELECT u.user_id, u.username, u.full_name, u.email, u.created_at, u.is_active,
                                               r.role_name, c.company_name
                                        FROM users u 
                                        LEFT JOIN roles r ON u.role_id = r.role_id 
                                        LEFT JOIN companies c ON u.company_id = c.company_id 
                                        WHERE u.user_id = ?
                                    ", [$userId]);
                                    
                                    if ($userDetails) {
                                        // Add stats
                                        $customerCount = $db->fetchOne("SELECT COUNT(*) as count FROM customers WHERE assigned_to = ? AND is_active = 1", [$userId]);
                                        $userDetails['customer_count'] = $customerCount['count'] ?? 0;
                                        
                                        $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE created_by = ? AND is_active = 1", [$userId]);
                                        $userDetails['order_count'] = $orderCount['count'] ?? 0;
                                        
                                        $totalSales = $db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE created_by = ? AND is_active = 1", [$userId]);
                                        $userDetails['total_sales'] = $totalSales['total'] ?? 0;
                                        
                                        $teamMembers[] = $userDetails;
                                    }
                                }
                                
                                echo "<h6>Step 2: Final team members with stats</h6>";
                                echo "<p><strong>Count:</strong> " . count($teamMembers) . "</p>";
                                
                                if (!empty($teamMembers)) {
                                    echo "<table class='table table-striped'>";
                                    echo "<thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Customers</th><th>Orders</th><th>Sales</th><th>Active</th></tr></thead>";
                                    echo "<tbody>";
                                    foreach ($teamMembers as $member) {
                                        echo "<tr>";
                                        echo "<td>" . $member['user_id'] . "</td>";
                                        echo "<td>" . htmlspecialchars($member['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($member['role_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . $member['customer_count'] . "</td>";
                                        echo "<td>" . $member['order_count'] . "</td>";
                                        echo "<td>฿" . number_format($member['total_sales'], 2) . "</td>";
                                        echo "<td>" . ($member['is_active'] ? '✅' : '❌') . "</td>";
                                        echo "</tr>";
                                    }
                                    echo "</tbody></table>";
                                    
                                    // Check for duplicates in final result
                                    $finalUsernames = array_column($teamMembers, 'username');
                                    $finalDuplicates = array_count_values($finalUsernames);
                                    $hasFinalDuplicates = false;
                                    
                                    echo "<h6>Final Result Duplicate Check:</h6>";
                                    foreach ($finalDuplicates as $username => $count) {
                                        if ($count > 1) {
                                            echo "<div class='alert alert-danger'>❌ Final result has duplicate: {$username} appears {$count} times</div>";
                                            $hasFinalDuplicates = true;
                                        }
                                    }
                                    
                                    if (!$hasFinalDuplicates) {
                                        echo "<div class='alert alert-success'>✅ No duplicates in final result</div>";
                                    }
                                }
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6>If you see this page without errors:</h6>
                                <ul>
                                    <li>✅ Database connection is working</li>
                                    <li>✅ Team member queries are working</li>
                                    <li>✅ No duplicate data in database</li>
                                    <li>✅ team.php logic should work correctly</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6>If team.php still shows duplicates:</h6>
                                <ul>
                                    <li>The issue might be in the HTML rendering</li>
                                    <li>Check browser cache (Ctrl+F5)</li>
                                    <li>Check DataTable JavaScript initialization</li>
                                </ul>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="team.php" class="btn btn-primary">Test team.php</a>
                                <a href="fix_team_data.php" class="btn btn-warning">Fix Team Data</a>
                                <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
