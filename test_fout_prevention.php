<?php
/**
 * ทดสอบ FOUT Prevention
 * ตรวจสอบการป้องกัน Flash of Unstyled Text ของฟอนต์ Sukhumvit Set
 */

session_start();

// Set up test session (simulate login as telesales)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['username'] = 'telesales';
$_SESSION['full_name'] = 'Telesales User';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบ FOUT Prevention - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบ FOUT Prevention</h1>
                <div class="text-muted">
                    <small>Flash of Unstyled Text Prevention Test</small>
                </div>
            </div>

            <!-- FOUT Explanation -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                FOUT คืออะไร?
                            </h5>
                        </div>
                        <div class="card-body">
                            <p><strong>FOUT = Flash of Unstyled Text</strong></p>
                            <p>ปัญหาที่เกิดขึ้นเมื่อ:</p>
                            <ol>
                                <li>เว็บไซต์โหลดข้อความด้วยฟอนต์ default ก่อน</li>
                                <li>จากนั้นเปลี่ยนเป็นฟอนต์ที่กำหนด (Sukhumvit Set)</li>
                                <li>ทำให้เกิดการ "กะพริบ" หรือ "เปลี่ยนฟอนต์กะทันหัน"</li>
                            </ol>
                            
                            <div class="alert alert-warning">
                                <strong>ปัญหาใน Sidebar:</strong><br>
                                ข้อความในเมนูและ user info จะ "กะพริบ" เปลี่ยนฟอนต์เมื่อโหลดหน้าเว็บ
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shield-alt me-2"></i>
                                วิธีแก้ FOUT
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>1. Font Preloading:</h6>
                            <pre class="bg-light p-2 small"><code>&lt;link rel="preload" href="fonts/sukhumvit.woff2" as="font"&gt;</code></pre>
                            
                            <h6>2. Font Display Strategy:</h6>
                            <pre class="bg-light p-2 small"><code>font-display: swap;</code></pre>
                            
                            <h6>3. Font Loading Detection:</h6>
                            <pre class="bg-light p-2 small"><code>document.fonts.ready.then(() => {
  // ฟอนต์โหลดเสร็จแล้ว
});</code></pre>
                            
                            <h6>4. Loading States:</h6>
                            <ul class="small">
                                <li><code>.fonts-loading</code> - กำลังโหลดฟอนต์</li>
                                <li><code>.fonts-loaded</code> - ฟอนต์โหลดเสร็จ</li>
                                <li><code>.fonts-fallback</code> - ใช้ฟอนต์ fallback</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Font Loading Status -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-font me-2"></i>
                                สถานะการโหลดฟอนต์
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Body Class</h6>
                                            <span id="bodyClass" class="badge bg-secondary">กำลังตรวจสอบ...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Font Loading API</h6>
                                            <span id="fontApiSupport" class="badge bg-secondary">กำลังตรวจสอบ...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Sukhumvit Set</h6>
                                            <span id="sukhumvitStatus" class="badge bg-secondary">กำลังตรวจสอบ...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Font Test Samples -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-eye me-2"></i>
                                ตัวอย่างข้อความทดสอบ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>ข้อความภาษาไทย (Sukhumvit Set):</h6>
                                    <div class="p-3 border rounded">
                                        <p class="mb-2" style="font-family: 'Sukhumvit Set', sans-serif; font-size: 16px;">
                                            ระบบ CRM SalesTracker สำหรับจัดการลูกค้าและคำสั่งซื้อ
                                        </p>
                                        <p class="mb-2" style="font-family: 'Sukhumvit Set', sans-serif; font-size: 14px; font-weight: 500;">
                                            เมนูหลัก: แดชบอร์ด ลูกค้า คำสั่งซื้อ รายงาน
                                        </p>
                                        <p class="mb-0" style="font-family: 'Sukhumvit Set', sans-serif; font-size: 12px; color: #6c757d;">
                                            ผู้ใช้งาน: Telesales User (บทบาท: เทเลเซลส์)
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>ข้อความภาษาอังกฤษ (System Font):</h6>
                                    <div class="p-3 border rounded">
                                        <p class="mb-2" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px;">
                                            CRM SalesTracker System for Customer Management
                                        </p>
                                        <p class="mb-2" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; font-weight: 500;">
                                            Main Menu: Dashboard Customers Orders Reports
                                        </p>
                                        <p class="mb-0" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; color: #6c757d;">
                                            User: Telesales User (Role: Telesales)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bars me-2"></i>
                                ทดสอบ Sidebar FOUT Prevention
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>วิธีทดสอบ:</h6>
                                <ol>
                                    <li><strong>รีเฟรชหน้าเว็บ</strong> (F5 หรือ Ctrl+R)</li>
                                    <li><strong>สังเกตข้อความใน Sidebar</strong> ขณะโหลด</li>
                                    <li><strong>ดู Console</strong> (F12) เพื่อดู font loading status</li>
                                    <li><strong>ทดสอบหลายครั้ง</strong> เพื่อดูความสม่ำเสมอ</li>
                                </ol>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>ก่อนแก้ไข FOUT:</h6>
                                    <ul class="text-danger">
                                        <li>ข้อความใน sidebar แสดงด้วยฟอนต์ default ก่อน</li>
                                        <li>จากนั้นเปลี่ยนเป็น Sukhumvit Set ทันที</li>
                                        <li>เกิดการ "กะพริบ" ที่เห็นได้ชัด</li>
                                        <li>UX ไม่ smooth</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>หลังแก้ไข FOUT:</h6>
                                    <ul class="text-success">
                                        <li>ฟอนต์ preload ก่อนแสดงเนื้อหา</li>
                                        <li>ใช้ font-display: swap</li>
                                        <li>มี loading states ที่เหมาะสม</li>
                                        <li>การเปลี่ยนฟอนต์นุ่มนวลขึ้น</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-primary" onclick="location.reload()">
                                    <i class="fas fa-redo me-1"></i>รีเฟรชเพื่อทดสอบ
                                </button>
                                <button class="btn btn-secondary" onclick="simulateSlowConnection()">
                                    <i class="fas fa-turtle me-1"></i>จำลองอินเทอร์เน็ตช้า
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Performance Metrics
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>Font Load Time</h6>
                                        <span id="fontLoadTime" class="badge bg-info">กำลังวัด...</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>Page Load Time</h6>
                                        <span id="pageLoadTime" class="badge bg-success">กำลังวัด...</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>FOUT Duration</h6>
                                        <span id="foutDuration" class="badge bg-warning">กำลังวัด...</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>Font Status</h6>
                                        <span id="fontStatus" class="badge bg-secondary">กำลังตรวจสอบ...</span>
                                    </div>
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
    console.log('=== FOUT Prevention Test ===');
    
    const startTime = performance.now();
    let fontLoadStartTime = startTime;
    
    // Check body class
    function updateBodyClass() {
        const bodyClass = document.body.className;
        const badge = document.getElementById('bodyClass');
        
        if (bodyClass.includes('fonts-loading')) {
            badge.textContent = 'fonts-loading';
            badge.className = 'badge bg-warning';
        } else if (bodyClass.includes('fonts-loaded')) {
            badge.textContent = 'fonts-loaded';
            badge.className = 'badge bg-success';
        } else if (bodyClass.includes('fonts-fallback')) {
            badge.textContent = 'fonts-fallback';
            badge.className = 'badge bg-info';
        } else {
            badge.textContent = 'unknown';
            badge.className = 'badge bg-secondary';
        }
    }
    
    // Check Font Loading API support
    const fontApiSupport = document.getElementById('fontApiSupport');
    if ('fonts' in document) {
        fontApiSupport.textContent = 'รองรับ';
        fontApiSupport.className = 'badge bg-success';
    } else {
        fontApiSupport.textContent = 'ไม่รองรับ';
        fontApiSupport.className = 'badge bg-danger';
    }
    
    // Monitor font loading
    if ('fonts' in document) {
        document.fonts.ready.then(function() {
            const fontLoadTime = performance.now() - fontLoadStartTime;
            document.getElementById('fontLoadTime').textContent = Math.round(fontLoadTime) + 'ms';
            document.getElementById('fontLoadTime').className = 'badge bg-success';
            
            document.getElementById('sukhumvitStatus').textContent = 'โหลดเสร็จ';
            document.getElementById('sukhumvitStatus').className = 'badge bg-success';
            
            console.log('✓ All fonts loaded in', Math.round(fontLoadTime), 'ms');
        });
        
        // Check specific font
        document.fonts.load('400 16px "Sukhumvit Set"').then(function() {
            console.log('✓ Sukhumvit Set loaded');
        }).catch(function() {
            console.log('⚠ Sukhumvit Set failed to load');
            document.getElementById('sukhumvitStatus').textContent = 'โหลดไม่สำเร็จ';
            document.getElementById('sukhumvitStatus').className = 'badge bg-danger';
        });
    }
    
    // Update body class periodically
    const classInterval = setInterval(updateBodyClass, 100);
    
    // Page load time
    window.addEventListener('load', function() {
        const pageLoadTime = performance.now() - startTime;
        document.getElementById('pageLoadTime').textContent = Math.round(pageLoadTime) + 'ms';
        document.getElementById('pageLoadTime').className = 'badge bg-success';
        
        clearInterval(classInterval);
        updateBodyClass();
    });
    
    // Simulate slow connection
    window.simulateSlowConnection = function() {
        alert('เปิด Developer Tools (F12) → Network → เลือก "Slow 3G" แล้วรีเฟรชหน้าเว็บ');
    };
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
