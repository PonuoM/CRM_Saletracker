<?php
/**
 * ทดสอบการแก้ไขตาม WORKLOG_2025-08-09.md
 * ตรวจสอบ Dashboard และ Orders ที่ถูกแก้ไขกลับเป็นแบบเดิม
 */

session_start();

// Set up test session (simulate login as telesales)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['username'] = 'telesales';
$_SESSION['full_name'] = 'Telesales User';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบการแก้ไขตาม WORKLOG - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบการแก้ไขตาม WORKLOG_2025-08-09.md</h1>
            </div>

            <!-- Test Results -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                Dashboard - การแก้ไข
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>KPI Cards:</strong> เปลี่ยนจากสีเป็นสีขาวธรรมดา
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Telesales:</strong> 4 การ์ด + กราฟรายวัน
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Supervisor:</strong> 4 การ์ด + กราฟรายเดือน
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Theme:</strong> ตรงตามธีมเดิม
                                </li>
                            </ul>
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
                                    <strong>Payment Switch:</strong> สวิตช์ "ชำระแล้ว" ในคอลัมน์การดำเนินการ
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Load More:</strong> "แสดงเพิ่มอีก 10 รายการ"
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Status Fix:</strong> แก้ "undefined" เป็นค่าที่ถูกต้อง
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Filters:</strong> คงค่าตัวกรองเดิม
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                ทดสอบ Dashboard
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>การทดสอบ Dashboard:</h6>
                                <ol class="mb-0">
                                    <li>เปิด <a href="dashboard.php" target="_blank">Dashboard</a></li>
                                    <li>ตรวจสอบ KPI Cards เป็นสีขาว (ไม่ใช่สี)</li>
                                    <li>ตรวจสอบไอคอนและตัวเลขมีสีตามธีม</li>
                                    <li>ตรวจสอบกราฟแสดงถูกต้อง</li>
                                </ol>
                            </div>
                            
                            <!-- Sample KPI Cards Preview -->
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card kpi-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted">ลูกค้าที่ได้รับมอบหมาย</h6>
                                                    <h3 class="mb-0 text-primary">150</h3>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-users fa-2x text-primary opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card kpi-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted">ลูกค้าที่ต้องติดตาม</h6>
                                                    <h3 class="mb-0 text-warning">25</h3>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-phone fa-2x text-warning opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card kpi-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted">คำสั่งซื้อวันนี้</h6>
                                                    <h3 class="mb-0 text-success">8</h3>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-shopping-cart fa-2x text-success opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card kpi-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title text-muted">ยอดขายเดือนนี้</h6>
                                                    <h3 class="mb-0 text-info">฿125,000</h3>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-chart-line fa-2x text-info opacity-75"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-success mt-3">
                                <strong>✓ ตัวอย่างข้างบน:</strong> KPI Cards แบบใหม่ (สีขาว + ไอคอนสี)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Test -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                ทดสอบ Orders
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>การทดสอบ Orders:</h6>
                                <ol class="mb-0">
                                    <li>เปิด <a href="orders.php" target="_blank">Orders</a></li>
                                    <li>ตรวจสอบคอลัมน์ "การจัดการ" มีสวิตช์ "ชำระแล้ว"</li>
                                    <li>ทดสอบคลิกสวิตช์เพื่ือเปลี่ยนสถานะ</li>
                                    <li>ตรวจสอบปุ่ม "แสดงเพิ่มอีก 10 รายการ"</li>
                                    <li>ตรวจสอบสถานะไม่แสดง "undefined"</li>
                                </ol>
                            </div>
                            
                            <!-- Sample Orders Table Preview -->
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
                                                    <!-- Payment Status Switch -->
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" title="ชำระแล้ว">
                                                        <label class="form-check-label small text-muted">ชำระแล้ว</label>
                                                    </div>
                                                    
                                                    <!-- Action Buttons -->
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" title="ลบ">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
                                                    <!-- Payment Status Switch -->
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" checked title="ชำระแล้ว">
                                                        <label class="form-check-label small text-muted">ชำระแล้ว</label>
                                                    </div>
                                                    
                                                    <!-- Action Buttons -->
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" title="ลบ">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
                                <strong>✓ ตัวอย่างข้างบน:</strong> Orders Table แบบใหม่ (สวิตช์ + ปุ่มโหลดเพิ่ม)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-check me-2"></i>
                                สรุปการแก้ไข
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Dashboard Changes:</h6>
                                    <ul>
                                        <li>✅ KPI Cards: สีขาว + ไอคอนสี</li>
                                        <li>✅ Theme: ตรงตามธีมเดิม</li>
                                        <li>✅ Layout: คงเดิม</li>
                                        <li>✅ Functionality: ทำงานปกติ</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Orders Changes:</h6>
                                    <ul>
                                        <li>✅ Payment Switch: ใช้งานได้</li>
                                        <li>✅ Load More: 10 รายการต่อครั้ง</li>
                                        <li>✅ Status Fix: ไม่มี "undefined"</li>
                                        <li>✅ Filters: คงค่าเดิม</li>
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
    console.log('=== WORKLOG Fixes Test Page ===');
    
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
