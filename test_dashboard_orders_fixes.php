<?php
/**
 * ทดสอบการแก้ไข Dashboard และ Orders ตามที่ขอ
 */

session_start();

// Set up test session (simulate login as telesales)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['username'] = 'telesales';
$_SESSION['full_name'] = 'Telesales User';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบการแก้ไข Dashboard & Orders - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบการแก้ไข Dashboard & Orders</h1>
            </div>

            <!-- Dashboard Changes -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard - การแก้ไข
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>เลือกเดือน:</strong> ลดขนาด container เหลือแค่ตัวกรอง
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>KPI Cards:</strong> เปลี่ยน "วันนี้" เป็น "ประจำเดือน"
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>เพิ่ม 4 Cards:</strong> ปุ๋ยใหญ่, ปุ๋ยเล็ก, ชีวภัณฑ์, ของแถม
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>กราฟ 2 แท็บ:</strong> Stack Column + Mixed Chart
                                </li>
                            </ul>
                            
                            <div class="mt-3">
                                <a href="dashboard.php" class="btn btn-primary btn-sm" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>ทดสอบ Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Orders - การแก้ไข
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Status Fix:</strong> แก้ "undefined" ให้แสดงค่าที่ถูกต้อง
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>ลบไอคอนลบ:</strong> เอาปุ่มลบออกจากการจัดการ
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Payment Switch:</strong> ย้ายไปท้ายสุด
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Load More:</strong> โหลดเพิ่ม 10 รายการ
                                </li>
                            </ul>
                            
                            <div class="mt-3">
                                <a href="orders.php" class="btn btn-info btn-sm" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>ทดสอบ Orders
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Preview -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-eye me-2"></i>
                                ตัวอย่าง Dashboard ใหม่
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Compact Month Filter -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-end gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label small">เดือน/ปี</label>
                                            <input type="month" class="form-control form-control-sm" value="2025-08">
                                        </div>
                                        <button class="btn btn-primary btn-sm">
                                            <i class="fas fa-search me-1"></i>ดูข้อมูล
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 8 KPI Cards Preview -->
                            <div class="row mb-4">
                                <!-- Row 1: Original 4 cards -->
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ลูกค้าที่ได้รับมอบหมาย</h6>
                                                    <h4 class="mb-0 text-primary">150</h4>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-users fa-lg text-primary opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ลูกค้าที่ต้องติดตาม</h6>
                                                    <h4 class="mb-0 text-warning">25</h4>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-phone fa-lg text-warning opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">คำสั่งซื้อประจำเดือน</h6>
                                                    <h4 class="mb-0 text-success">45</h4>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-shopping-cart fa-lg text-success opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ยอดขายเดือนนี้</h6>
                                                    <h4 class="mb-0 text-info">฿125,000</h4>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-chart-line fa-lg text-info opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Row 2: Product category cards -->
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ปุ๋ยกระสอบใหญ่</h6>
                                                    <h4 class="mb-0 text-primary">฿45,000</h4>
                                                    <small class="text-muted">150 กระสอบ</small>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-seedling fa-lg text-primary opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ปุ๋ยกระสอบเล็ก</h6>
                                                    <h4 class="mb-0 text-success">฿35,000</h4>
                                                    <small class="text-muted">200 กระสอบ</small>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-leaf fa-lg text-success opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ชีวภัณฑ์</h6>
                                                    <h4 class="mb-0 text-warning">฿25,000</h4>
                                                    <small class="text-muted">80 ชิ้น</small>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-flask fa-lg text-warning opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card kpi-card">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted small">ของแถม</h6>
                                                    <h4 class="mb-0 text-danger">฿20,000</h4>
                                                    <small class="text-muted">120 ชิ้น</small>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-gift fa-lg text-danger opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Chart Tabs Preview -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        ผลงานรายวัน - August 2025
                                    </h5>
                                    <ul class="nav nav-tabs card-header-tabs mt-2">
                                        <li class="nav-item">
                                            <button class="nav-link active">
                                                <i class="fas fa-chart-column me-1"></i>ยอดขายตามประเภท
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link">
                                                <i class="fas fa-chart-mixed me-1"></i>คำสั่งซื้อ + ติดต่อ
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>แท็บ 1:</strong> กราฟแท่ง Stack Column แสดงยอดขายตามประเภทสินค้า<br>
                                        <strong>แท็บ 2:</strong> กราฟผสม - แท่ง (คำสั่งซื้อ) + เส้น (ผู้ติดต่อ)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Preview -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                ตัวอย่าง Orders ใหม่
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>หมายเลขคำสั่งซื้อ</th>
                                            <th>ลูกค้า</th>
                                            <th>วันที่สั่งซื้อ</th>
                                            <th>ยอดรวม</th>
                                            <th>สถานะ</th>
                                            <th>การชำระเงิน</th>
                                            <th>การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>ORD-001</td>
                                            <td>บริษัท ABC จำกัด</td>
                                            <td>10/08/2025</td>
                                            <td>฿15,000.00</td>
                                            <td><span class="badge bg-warning">รอดำเนินการ</span></td>
                                            <td><span class="badge bg-warning">รอชำระ</span></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check form-switch ms-2">
                                                        <input class="form-check-input" type="checkbox" title="ชำระแล้ว">
                                                        <label class="form-check-label small text-muted">ชำระแล้ว</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>ORD-002</td>
                                            <td>บริษัท XYZ จำกัด</td>
                                            <td>09/08/2025</td>
                                            <td>฿25,000.00</td>
                                            <td><span class="badge bg-success">ส่งมอบแล้ว</span></td>
                                            <td><span class="badge bg-success">ชำระแล้ว</span></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check form-switch ms-2">
                                                        <input class="form-check-input" type="checkbox" checked title="ชำระแล้ว">
                                                        <label class="form-check-label small text-muted">ชำระแล้ว</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center py-3">
                                <button class="btn btn-outline-primary" disabled>
                                    <i class="fas fa-plus me-1"></i>แสดงเพิ่มอีก 10 รายการ
                                </button>
                            </div>
                            
                            <div class="alert alert-success">
                                <strong>✓ การเปลี่ยนแปลง:</strong>
                                <ul class="mb-0">
                                    <li>ลบปุ่มลบออกแล้ว (ใช้ในหน้าแก้ไขแทน)</li>
                                    <li>ย้าย Payment Switch ไปท้ายสุด</li>
                                    <li>Status แสดงค่าที่ถูกต้อง (ไม่มี "undefined")</li>
                                </ul>
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
    console.log('=== Dashboard & Orders Fixes Test ===');
    
    // Test payment switches
    document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(switchEl => {
        switchEl.addEventListener('change', function() {
            console.log('Payment switch toggled:', this.checked);
            
            // Update badge color (demo)
            const row = this.closest('tr');
            const paymentBadge = row.querySelector('td:nth-child(6) .badge');
            
            if (this.checked) {
                paymentBadge.className = 'badge bg-success';
                paymentBadge.textContent = 'ชำระแล้ว';
            } else {
                paymentBadge.className = 'badge bg-warning';
                paymentBadge.textContent = 'รอชำระ';
            }
        });
    });
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
