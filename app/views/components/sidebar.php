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
<div class="col-md-3 col-lg-2 sidebar p-0" id="mainSidebar">
    <!-- Sidebar Header with Pin Button -->
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-bars sidebar-brand-icon"></i>
            <span class="sidebar-brand-text">เมนู</span>
        </div>
        <button class="sidebar-pin-btn" id="sidebarPinBtn" title="ปักหมุด Sidebar">
            <i class="fas fa-thumbtack"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <h6 class="sidebar-section-title">เมนูหลัก</h6>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="<?php echo ($_SESSION['role_name'] === 'supervisor') ? 'dashboard_supervisor.php' : 'dashboard.php'; ?>" data-tooltip="แดชบอร์ด">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span class="nav-text">แดชบอร์ด</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'customers') ? 'active' : ''; ?>" href="customers.php" data-tooltip="จัดการลูกค้า">
                    <i class="fas fa-users nav-icon"></i>
                    <span class="nav-text">จัดการลูกค้า</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'orders') ? 'active' : ''; ?>" href="orders.php" data-tooltip="จัดการคำสั่งซื้อ">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                    <span class="nav-text">จัดการคำสั่งซื้อ</span>
                </a>
            </li>

            <?php if (in_array($roleName, ['admin', 'super_admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin') ? 'active' : ''; ?>" href="admin.php" data-tooltip="Admin Dashboard">
                    <i class="fas fa-cogs nav-icon"></i>
                    <span class="nav-text">Admin Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'users') ? 'active' : ''; ?>" href="admin.php?action=users" data-tooltip="จัดการผู้ใช้">
                    <i class="fas fa-user-cog nav-icon"></i>
                    <span class="nav-text">จัดการผู้ใช้</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'products') ? 'active' : ''; ?>" href="admin.php?action=products" data-tooltip="จัดการสินค้า">
                    <i class="fas fa-box nav-icon"></i>
                    <span class="nav-text">จัดการสินค้า</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'settings') ? 'active' : ''; ?>" href="admin.php?action=settings" data-tooltip="ตั้งค่าระบบ">
                    <i class="fas fa-cog nav-icon"></i>
                    <span class="nav-text">ตั้งค่าระบบ</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" href="reports.php" data-tooltip="รายงาน">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span class="nav-text">รายงาน</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'import-export') ? 'active' : ''; ?>" href="import-export.php" data-tooltip="นำเข้า/ส่งออก">
                    <i class="fas fa-exchange-alt nav-icon"></i>
                    <span class="nav-text">นำเข้า/ส่งออก</span>
                </a>
            </li>
            <?php elseif ($roleName === 'supervisor'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'team') ? 'active' : ''; ?>" href="team.php" data-tooltip="จัดการทีม">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <span class="nav-text">จัดการทีม</span>
                </a>
            </li>
            <?php endif; ?>
            <!-- Note: telesales role only sees Dashboard, Customer Management, and Order Management -->

                <!-- Admin Menu -->
                <?php if (in_array($roleName, ['admin', 'super_admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'workflow') ? 'active' : ''; ?>" href="admin.php?action=workflow" data-tooltip="Workflow Management">
                        <i class="fas fa-project-diagram nav-icon"></i>
                        <span class="nav-text">Workflow Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'customer_distribution') ? 'active' : ''; ?>" href="admin.php?action=customer_distribution" data-tooltip="ระบบแจกลูกค้า">
                        <i class="fas fa-users nav-icon"></i>
                        <span class="nav-text">ระบบแจกลูกค้า</span>
                    </a>
                </li>
                <?php endif; ?>
        </ul>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <!-- User Info -->
        <div class="sidebar-user-info">
            <i class="fas fa-user-circle nav-icon"></i>
            <div class="nav-text">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'User'); ?></div>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="sidebar-logout">
            <a href="logout.php" class="logout-btn" data-tooltip="ออกจากระบบ" title="ออกจากระบบ">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="nav-text">ออกจากระบบ</span>
            </a>
        </div>
    </div>
</div>