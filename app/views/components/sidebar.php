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
        <div class="sidebar-brand d-flex align-items-center gap-2">
            <i class="fas fa-bars sidebar-brand-icon"></i>
            <span class="sidebar-brand-text">เมนู</span>
            <!-- Notification bell -->
            <div class="ms-auto d-flex align-items-center" id="sidebarNotifArea" style="margin-left:auto;">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary position-relative" id="notifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" title="การแจ้งเตือน">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="display:none;">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notifDropdownBtn" style="min-width: 320px; max-height: 380px; overflow:auto;">
                        <div class="list-group list-group-flush" id="notifList">
                            <div class="text-center text-muted py-3 small">ไม่มีการแจ้งเตือน</div>
                        </div>
                        <div class="border-top d-flex justify-content-between align-items-center p-2">
                            <button class="btn btn-sm btn-link" id="markAllReadBtn">ทำเครื่องหมายว่าอ่านแล้ว</button>
                            <button class="btn btn-sm btn-link" id="refreshNotifBtn">รีเฟรช</button>
                        </div>
                    </div>
                </div>
            </div>
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

            <?php if (in_array($roleName, ['supervisor', 'telesales', 'admin', 'super_admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'search') ? 'active' : ''; ?>" href="search.php" data-tooltip="ค้นหาลูกค้าและยอดขาย">
                    <i class="fas fa-search nav-icon"></i>
                    <span class="nav-text">ค้นหา</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($roleName === 'company_admin'): ?>
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
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'customer_distribution') ? 'active' : ''; ?>" href="admin.php?action=customer_distribution" data-tooltip="ระบบแจกลูกค้า">
                    <i class="fas fa-share-alt nav-icon"></i>
                    <span class="nav-text">ระบบแจกลูกค้า</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'import-export') ? 'active' : ''; ?>" href="import-export.php" data-tooltip="นำเข้า/ส่งออก">
                    <i class="fas fa-exchange-alt nav-icon"></i>
                    <span class="nav-text">นำเข้า/ส่งออก</span>
                </a>
            </li>
            <?php elseif (in_array($roleName, ['admin', 'super_admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin') ? 'active' : ''; ?>" href="admin.php" data-tooltip="Admin Dashboard">
                    <i class="fas fa-cogs nav-icon"></i>
                    <span class="nav-text">Admin Dashboard</span>
                </a>
            </li>
            <?php if ($roleName === 'super_admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'users') ? 'active' : ''; ?>" href="admin.php?action=users" data-tooltip="จัดการผู้ใช้">
                    <i class="fas fa-user-cog nav-icon"></i>
                    <span class="nav-text">จัดการผู้ใช้</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'products') ? 'active' : ''; ?>" href="admin.php?action=products" data-tooltip="จัดการสินค้า">
                    <i class="fas fa-box nav-icon"></i>
                    <span class="nav-text">จัดการสินค้า</span>
                </a>
            </li>
            <?php if ($roleName === 'super_admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'settings') ? 'active' : ''; ?>" href="admin.php?action=settings" data-tooltip="ตั้งค่าระบบ">
                    <i class="fas fa-cog nav-icon"></i>
                    <span class="nav-text">ตั้งค่าระบบ</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" href="reports.php" data-tooltip="รายงาน">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span class="nav-text">รายงาน</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'admin' && $currentAction === 'customer_distribution') ? 'active' : ''; ?>" href="admin.php?action=customer_distribution" data-tooltip="ระบบแจกลูกค้า">
                    <i class="fas fa-share-alt nav-icon"></i>
                    <span class="nav-text">ระบบแจกลูกค้า</span>
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
            
            <?php 
            // Telesales (role=4) should only see basic menus: Dashboard, Customers, Orders, Search
            // No admin menus for telesales
            ?>


                
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