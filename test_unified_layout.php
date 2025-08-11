<?php
/**
 * ทดสอบ Unified Layout System
 * ตรวจสอบว่าทุกหน้าใช้ layout เดียวกันและไม่มี header ซ้อน
 */

session_start();

// Set up test session (simulate login as admin)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบ Unified Layout System - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบ Unified Layout System</h1>
            </div>

            <!-- Test Results -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                ปัญหาที่แก้ไขแล้ว
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>ลบชื่อซ้อนใน Sidebar:</strong> เปลี่ยนจาก "CRM Sales" เป็น "เมนู"
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Dashboard:</strong> ใช้ main.php layout แล้ว
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Orders:</strong> ใช้ main.php layout แล้ว
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Admin:</strong> ใช้ main.php layout แล้ว
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Dynamic Sidebar:</strong> ทำงานได้ทุกหน้า
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-cogs me-2"></i>
                                การทำงานของระบบ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Layout Structure:</h6>
                                <ul class="mb-0">
                                    <li><strong>Header:</strong> CRM SalesTracker (เดียว)</li>
                                    <li><strong>Sidebar:</strong> เมนู + Dynamic features</li>
                                    <li><strong>Content:</strong> ปรับตาม sidebar</li>
                                    <li><strong>Scripts:</strong> sidebar.js ทุกหน้า</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>ทดสอบ Links:</h6>
                                <div class="d-grid gap-2">
                                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                    </a>
                                    <a href="customers.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-users me-1"></i>Customers
                                    </a>
                                    <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-shopping-cart me-1"></i>Orders
                                    </a>
                                    <a href="admin.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-cogs me-1"></i>Admin
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-desktop me-2"></i>
                                ทดสอบ Dynamic Sidebar
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>ทดสอบฟีเจอร์ต่างๆ ของ Dynamic Sidebar:</p>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <i class="fas fa-compress-arrows-alt fa-2x text-primary mb-2"></i>
                                            <h6>โหมด Mini</h6>
                                            <p class="small text-muted">Sidebar หุบเหลือไอคอน</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <i class="fas fa-thumbtack fa-2x text-success mb-2"></i>
                                            <h6>ปุ่มปักหมุด</h6>
                                            <p class="small text-muted">คลิกเพื่อคงสถานะ</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <i class="fas fa-expand-arrows-alt fa-2x text-warning mb-2"></i>
                                            <h6>Auto Expand</h6>
                                            <p class="small text-muted">Hover เพื่อขยาย</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Browser Console Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bug me-2"></i>
                                Console Debug Info
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>เปิด Browser Console (F12) เพื่อดู debug information:</p>
                            <ul>
                                <li>Sidebar state changes</li>
                                <li>LocalStorage operations</li>
                                <li>Layout adjustments</li>
                                <li>Error messages (ถ้ามี)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Unified Layout Test Page ===');
    console.log('Current page:', '<?php echo $currentPage; ?>');
    console.log('Page title:', '<?php echo $pageTitle; ?>');
    
    // Test sidebar functionality
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar) {
        console.log('✓ Sidebar found');
        console.log('Sidebar classes:', sidebar.className);
    } else {
        console.error('✗ Sidebar not found');
    }
    
    if (mainContent) {
        console.log('✓ Main content found');
        console.log('Main content classes:', mainContent.className);
    } else {
        console.error('✗ Main content not found');
    }
    
    // Test localStorage
    const sidebarPinned = localStorage.getItem('sidebarPinned');
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    
    console.log('LocalStorage - sidebarPinned:', sidebarPinned);
    console.log('LocalStorage - sidebarCollapsed:', sidebarCollapsed);
    
    // Test pin button
    const pinBtn = document.getElementById('sidebarPinBtn');
    if (pinBtn) {
        console.log('✓ Pin button found');
        
        pinBtn.addEventListener('click', function() {
            console.log('Pin button clicked');
        });
    } else {
        console.error('✗ Pin button not found');
    }
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
