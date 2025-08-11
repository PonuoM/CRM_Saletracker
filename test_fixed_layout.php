<?php
/**
 * ทดสอบ Fixed Layout System
 * ตรวจสอบ Navbar และ Sidebar ที่ปรับตัวได้
 */

session_start();

// Set up test session (simulate login as admin)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบ Fixed Layout System - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบ Fixed Layout System</h1>
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
                                    <strong>Fixed Navbar:</strong> ปรับตาม sidebar อัตโนมัติ
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Fixed Sidebar:</strong> ไม่หายเมื่อ scroll
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Responsive Navbar:</strong> ปรับขนาดตาม sidebar
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Content Margin:</strong> เว้นพื้นที่สำหรับ navbar
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Mobile Support:</strong> ทำงานได้ดีบนมือถือ
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
                                <h6><i class="fas fa-info-circle me-2"></i>Fixed Layout:</h6>
                                <ul class="mb-0">
                                    <li><strong>Navbar:</strong> Fixed top, ปรับ left ตาม sidebar</li>
                                    <li><strong>Sidebar:</strong> Fixed left, ไม่หายเมื่อ scroll</li>
                                    <li><strong>Content:</strong> margin-top: 72px</li>
                                    <li><strong>Responsive:</strong> ปรับตัวบนมือถือ</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-mobile-alt me-2"></i>การทดสอบ:</h6>
                                <ol class="mb-0">
                                    <li>ลอง scroll หน้านี้ลง - sidebar ยังคงเห็น</li>
                                    <li>คลิกปุ่มปักหมุด - navbar จะปรับขนาด</li>
                                    <li>ลองบนมือถือ - navbar เต็มความกว้าง</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Long Content for Scroll Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-scroll me-2"></i>
                                ทดสอบการ Scroll
                            </h5>
                        </div>
                        <div class="card-body">
                            <p><strong>คำแนะนำ:</strong> เลื่อนหน้านี้ลงเพื่อทดสอบว่า Sidebar และ Navbar ยังคงอยู่ที่เดิม</p>
                            
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">การ์ดทดสอบที่ <?php echo $i; ?></h6>
                                        <p class="card-text">
                                            นี่คือเนื้อหาสำหรับทดสอบการ scroll ของหน้าเว็บ 
                                            เมื่อคุณเลื่อนลงมาถึงตรงนี้ Sidebar และ Navbar ควรจะยังคงปรากฏอยู่
                                            และสามารถใช้งานได้ปกติ
                                        </p>
                                        
                                        <?php if ($i % 5 == 0): ?>
                                            <div class="alert alert-primary">
                                                <strong>Checkpoint <?php echo $i/5; ?>:</strong> 
                                                ตรวจสอบว่า Sidebar ยังคงเห็นอยู่ด้านซ้าย และ Navbar อยู่ด้านบน
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body text-center">
                                                        <i class="fas fa-eye fa-2x text-primary mb-2"></i>
                                                        <h6>Sidebar Visible</h6>
                                                        <p class="small text-muted">ควรเห็น sidebar ด้านซ้าย</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body text-center">
                                                        <i class="fas fa-window-maximize fa-2x text-success mb-2"></i>
                                                        <h6>Navbar Fixed</h6>
                                                        <p class="small text-muted">navbar ติดด้านบน</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body text-center">
                                                        <i class="fas fa-arrows-alt fa-2x text-warning mb-2"></i>
                                                        <h6>Content Flows</h6>
                                                        <p class="small text-muted">เนื้อหา scroll ได้</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                            
                            <div class="alert alert-success text-center">
                                <h4><i class="fas fa-trophy me-2"></i>ยินดีด้วย!</h4>
                                <p class="mb-0">
                                    หากคุณเห็นข้อความนี้และ Sidebar + Navbar ยังคงอยู่ 
                                    แสดงว่าระบบ Fixed Layout ทำงานได้ถูกต้อง
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Information -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                ข้อมูล Layout
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>CSS Properties:</h6>
                                    <ul>
                                        <li><code>.navbar</code>: position: fixed, top: 0, left: 250px</li>
                                        <li><code>.sidebar</code>: position: fixed, left: 0, top: 72px</li>
                                        <li><code>.main-content</code>: margin-left: 250px, margin-top: 72px</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Responsive Behavior:</h6>
                                    <ul>
                                        <li><strong>Desktop:</strong> Navbar ปรับตาม sidebar</li>
                                        <li><strong>Mobile:</strong> Navbar เต็มความกว้าง</li>
                                        <li><strong>Transitions:</strong> 0.3s cubic-bezier</li>
                                    </ul>
                                </div>
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
    console.log('=== Fixed Layout Test Page ===');
    
    // Test elements
    const navbar = document.querySelector('.navbar');
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.querySelector('.main-content');
    
    console.log('Navbar position:', navbar ? getComputedStyle(navbar).position : 'not found');
    console.log('Navbar left:', navbar ? getComputedStyle(navbar).left : 'not found');
    console.log('Sidebar position:', sidebar ? getComputedStyle(sidebar).position : 'not found');
    console.log('Main content margin-top:', mainContent ? getComputedStyle(mainContent).marginTop : 'not found');
    
    // Monitor scroll position
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop + 100 || scrollTop < lastScrollTop - 100) {
            console.log('Scroll position:', scrollTop, 'px');
            console.log('Navbar still visible:', navbar ? navbar.getBoundingClientRect().top === 0 : 'not found');
            console.log('Sidebar still visible:', sidebar ? sidebar.getBoundingClientRect().left >= 0 : 'not found');
            lastScrollTop = scrollTop;
        }
    });
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
