<?php
/**
 * ทดสอบ Navbar-less Layout
 * ตรวจสอบ Layout ที่ไม่มี navbar และมี logout ใน sidebar
 */

session_start();

// Set up test session (simulate login as admin)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบ Navbar-less Layout - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบ Navbar-less Layout</h1>
                <div class="text-muted">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    <small>(<?php echo htmlspecialchars($_SESSION['role_name']); ?>)</small>
                </div>
            </div>

            <!-- Test Results -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                การปรับปรุงที่ทำ
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    <strong>ลบ Navbar:</strong> ไม่มี navbar ด้านบนแล้ว
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-arrow-down text-success me-2"></i>
                                    <strong>Logout ใน Sidebar:</strong> ย้ายไปด้านล่าง sidebar
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-expand text-info me-2"></i>
                                    <strong>Full Height Sidebar:</strong> sidebar เต็มความสูง
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-arrows-alt text-warning me-2"></i>
                                    <strong>Content Full Width:</strong> เนื้อหาใช้พื้นที่เต็ม
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-mobile-alt text-primary me-2"></i>
                                    <strong>Mobile Friendly:</strong> ทำงานได้ดีบนมือถือ
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
                                ฟีเจอร์ใหม่ใน Sidebar
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-user-circle me-2"></i>User Info:</h6>
                                <ul class="mb-0">
                                    <li><strong>ชื่อผู้ใช้:</strong> แสดงชื่อเต็ม</li>
                                    <li><strong>บทบาท:</strong> แสดง role</li>
                                    <li><strong>ไอคอน:</strong> user-circle icon</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-sign-out-alt me-2"></i>Logout Button:</h6>
                                <ul class="mb-0">
                                    <li><strong>ตำแหน่ง:</strong> ด้านล่างสุดของ sidebar</li>
                                    <li><strong>สี:</strong> เปลี่ยนเป็นสีแดงเมื่อ hover</li>
                                    <li><strong>Tooltip:</strong> แสดงใน mini mode</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Comparison -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-balance-scale me-2"></i>
                                เปรียบเทียบ Layout
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0">❌ Layout เดิม (มี Navbar)</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li>🔸 Navbar ด้านบน (72px)</li>
                                                <li>🔸 Sidebar เริ่มจาก top: 72px</li>
                                                <li>🔸 Content margin-top: 72px</li>
                                                <li>🔸 Navbar ปรับตาม sidebar</li>
                                                <li>🔸 ซับซ้อน responsive</li>
                                                <li>🔸 User info ใน navbar</li>
                                                <li>🔸 Logout ใน navbar</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">✅ Layout ใหม่ (ไม่มี Navbar)</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li>🔹 ไม่มี navbar</li>
                                                <li>🔹 Sidebar เริ่มจาก top: 0</li>
                                                <li>🔹 Content ไม่มี margin-top</li>
                                                <li>🔹 Sidebar เต็มความสูง</li>
                                                <li>🔹 Responsive ง่ายขึ้น</li>
                                                <li>🔹 User info ใน sidebar</li>
                                                <li>🔹 Logout ใน sidebar</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Instructions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-check me-2"></i>
                                วิธีทดสอบ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="fas fa-mouse-pointer fa-2x text-primary mb-2"></i>
                                            <h6>1. ทดสอบ Pin/Unpin</h6>
                                            <p class="small text-muted">คลิกปุ่มปักหมุดใน sidebar</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fas fa-sign-out-alt fa-2x text-success mb-2"></i>
                                            <h6>2. ทดสอบ Logout</h6>
                                            <p class="small text-muted">ดู logout button ด้านล่าง sidebar</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="fas fa-mobile-alt fa-2x text-warning mb-2"></i>
                                            <h6>3. ทดสอบ Mobile</h6>
                                            <p class="small text-muted">ลองเปิดบนมือถือ</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Content -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>
                                เนื้อหาตัวอย่าง
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>เนื้อหาส่วนนี้แสดงให้เห็นว่า layout ใหม่ทำงานได้ดี:</p>
                            
                            <div class="row">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">การ์ดตัวอย่าง <?php echo $i; ?></h6>
                                                <p class="card-text">
                                                    นี่คือเนื้อหาตัวอย่างเพื่อแสดงให้เห็นว่า layout ใหม่
                                                    ทำงานได้ดีและใช้พื้นที่ได้เต็มที่
                                                </p>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">การ์ดที่ <?php echo $i; ?></small>
                                                    <small class="text-success">✓ ทำงานได้</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Navbar-less Layout Test ===');
    
    // Check if navbar exists (should not)
    const navbar = document.querySelector('.navbar');
    console.log('Navbar exists:', !!navbar);
    
    // Check sidebar
    const sidebar = document.getElementById('mainSidebar');
    if (sidebar) {
        console.log('✓ Sidebar found');
        console.log('Sidebar top position:', getComputedStyle(sidebar).top);
        console.log('Sidebar height:', getComputedStyle(sidebar).minHeight);
    }
    
    // Check main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        console.log('✓ Main content found');
        console.log('Main content margin-top:', getComputedStyle(mainContent).marginTop);
        console.log('Main content min-height:', getComputedStyle(mainContent).minHeight);
    }
    
    // Check logout button
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        console.log('✓ Logout button found in sidebar');
        
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Logout button clicked');
            alert('Logout button ทำงานได้! (ในการทดสอบจริงจะไปหน้า logout.php)');
        });
    }
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
