<?php
/**
 * Sidebar Component
 * Centralized sidebar for consistent navigation across all pages
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentAction = $_GET['action'] ?? '';

// Get user role
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar p-0">
    <div class="p-3">
        <h6 class="text-muted mb-3">เมนูหลัก</h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    แดชบอร์ด
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'customers') ? 'active' : ''; ?>" href="customers.php">
                    <i class="fas fa-users me-2"></i>
                    จัดการลูกค้า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'orders') ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>
                    จัดการคำสั่งซื้อ
                </a>
            </li>
            <?php if (in_array($roleName, ['admin', 'super_admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin') ? 'active' : ''; ?>" href="admin.php">
                    <i class="fas fa-cogs me-2"></i>
                    Admin Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'users') ? 'active' : ''; ?>" href="admin.php?action=users">
                    <i class="fas fa-user-cog me-2"></i>
                    จัดการผู้ใช้
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'products') ? 'active' : ''; ?>" href="admin.php?action=products">
                    <i class="fas fa-box me-2"></i>
                    จัดการสินค้า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'settings') ? 'active' : ''; ?>" href="admin.php?action=settings">
                    <i class="fas fa-cog me-2"></i>
                    ตั้งค่าระบบ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    รายงาน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'import-export') ? 'active' : ''; ?>" href="import-export.php">
                    <i class="fas fa-exchange-alt me-2"></i>
                    นำเข้า/ส่งออก
                </a>
            </li>
            <?php elseif ($roleName === 'supervisor'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    รายงาน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'team') ? 'active' : ''; ?>" href="team.php">
                    <i class="fas fa-users-cog me-2"></i>
                    จัดการทีม
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'import-export') ? 'active' : ''; ?>" href="import-export.php">
                    <i class="fas fa-exchange-alt me-2"></i>
                    นำเข้า/ส่งออก
                </a>
            </li>
            <?php endif; ?>
            <!-- Note: telesales role only sees Dashboard, Customer Management, and Order Management -->

                <!-- Admin/Supervisor Menu -->
                <?php if (in_array($roleName, ['admin', 'supervisor', 'super_admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cogs me-2"></i>
                            <span>จัดการระบบ</span>
                        </a>
                    </li>
                                    <li class="nav-item">
                    <a class="nav-link" href="admin.php?action=workflow">
                        <i class="fas fa-project-diagram me-2"></i>
                        <span>Workflow Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php?action=customer_distribution">
                        <i class="fas fa-users me-2"></i>
                        <span>ระบบแจกลูกค้า</span>
                    </a>
                </li>
                <?php endif; ?>
        </ul>
    </div>
</div> 