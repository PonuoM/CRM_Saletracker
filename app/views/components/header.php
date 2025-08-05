<?php
/**
 * Header Component
 * แสดงชื่อ user และ navigation ในทุกหน้า
 */

// ตรวจสอบ session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role_name'] ?? 'user';
$username = $_SESSION['username'] ?? 'User';
$fullName = $_SESSION['full_name'] ?? $username;
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-chart-line me-2"></i>
            CRM SalesTracker
        </a>
        
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">
                <i class="fas fa-user-circle me-1"></i>
                <?php echo htmlspecialchars($fullName); ?>
                <small class="text-muted ms-1">(<?php echo htmlspecialchars($role); ?>)</small>
            </span>
            <a class="nav-link" href="logout.php" title="ออกจากระบบ">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">ออกจากระบบ</span>
            </a>
        </div>
    </div>
</nav> 