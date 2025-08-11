<?php
/**
 * ทดสอบการแก้ไขการเชื่อมต่อฐานข้อมูล
 * Dashboard: products.category -> order_items
 * Orders: delivery_status, payment_status
 */

session_start();

// Set up test session (simulate login as telesales)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['username'] = 'telesales';
$_SESSION['full_name'] = 'Telesales User';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบการแก้ไขฐานข้อมูล - CRM SalesTracker';
$currentPage = 'test';

// Test content
ob_start();
?>

<!-- Test Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ทดสอบการแก้ไขฐานข้อมูล</h1>
            </div>

            <!-- Database Schema Info -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-database me-2"></i>
                                Dashboard - Schema ที่ใช้
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>ตาราง products:</h6>
                            <ul class="list-unstyled">
                                <li><code>product_id</code> - รหัสสินค้า</li>
                                <li><code>category</code> - ประเภทสินค้า</li>
                            </ul>
                            
                            <h6>ตาราง order_items:</h6>
                            <ul class="list-unstyled">
                                <li><code>product_id</code> - เชื่อมกับ products</li>
                                <li><code>quantity</code> - จำนวนชิ้น</li>
                                <li><code>unit_price</code> - ราคาต่อหน่วย</li>
                                <li><code>total_price</code> - ราคาสุทธิ์</li>
                            </ul>
                            
                            <h6>Categories ที่ใช้:</h6>
                            <ul class="list-unstyled">
                                <li>🌱 ปุ๋ยกระสอบใหญ่</li>
                                <li>🍃 ปุ๋ยกระสอบเล็ก</li>
                                <li>🧪 ชีวภัณฑ์</li>
                                <li>🎁 ของแถม</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-table me-2"></i>
                                Orders - Schema ที่ใช้
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>ตาราง orders:</h6>
                            <ul class="list-unstyled">
                                <li><code>orders_number</code> - หมายเลขคำสั่งซื้อ</li>
                                <li><code>delivery_status</code> - สถานะการส่ง</li>
                                <li><code>payment_status</code> - สถานะการชำระ</li>
                            </ul>
                            
                            <h6>delivery_status values:</h6>
                            <ul class="list-unstyled">
                                <li>⏳ pending - รอดำเนินการ</li>
                                <li>ℹ️ confirmed - ยืนยันแล้ว</li>
                                <li>🚚 shipped - จัดส่งแล้ว</li>
                                <li>✅ delivered - ส่งมอบแล้ว</li>
                                <li>❌ cancelled - ยกเลิก</li>
                            </ul>
                            
                            <h6>payment_status values:</h6>
                            <ul class="list-unstyled">
                                <li>⏳ pending - รอชำระ</li>
                                <li>✅ paid - ชำระแล้ว</li>
                                <li>📊 partial - ชำระบางส่วน</li>
                                <li>❌ cancelled - ยกเลิก</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Changes Made -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                การแก้ไขที่ทำ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Dashboard Fixes:</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>Category Mapping:</strong> เพิ่ม "ของแถม" category
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>KPI Names:</strong> ปรับชื่อให้ตรงกับ dashboard
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>Monthly Orders:</strong> เพิ่มตัวแปร monthlyOrders
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Orders Fixes:</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>Status Field:</strong> ใช้ delivery_status แทน order_status
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>Payment Switch:</strong> ลบ label "ชำระแล้ว"
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>Database Connection:</strong> แก้ไข PDO connection
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>Error Logging:</strong> เพิ่ม error logging
                                        </li>
                                    </ul>
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
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                วิธีทดสอบ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Dashboard Testing:</h6>
                                    <ol>
                                        <li>เปิด <a href="dashboard.php" target="_blank">Dashboard</a></li>
                                        <li>ตรวจสอบ 8 KPI Cards แสดงข้อมูลถูกต้อง</li>
                                        <li>ตรวจสอบยอดขายตาม category</li>
                                        <li>ตรวจสอบจำนวนและหน่วย</li>
                                        <li>ตรวจสอบกราห 2 แท็บ</li>
                                    </ol>
                                </div>
                                <div class="col-md-6">
                                    <h6>Orders Testing:</h6>
                                    <ol>
                                        <li>เปิด <a href="orders.php" target="_blank">Orders</a></li>
                                        <li>ตรวจสอบคอลัมน์ "สถานะ" แสดงค่าที่ถูกต้อง</li>
                                        <li>ทดสอบ Payment Switch (ไม่มี label)</li>
                                        <li>ตรวจสอบ Console ไม่มี 500 Error</li>
                                        <li>ตรวจสอบ orders_number แสดงถูกต้อง</li>
                                    </ol>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <h6><i class="fas fa-info-circle me-2"></i>หมายเหตุ:</h6>
                                <ul class="mb-0">
                                    <li>หากยังมี Error 500 ให้ตรวจสอบ PHP Error Log</li>
                                    <li>ตรวจสอบว่าตาราง orders มีคอลัมน์ payment_status</li>
                                    <li>ตรวจสอบว่าตาราง products มีคอลัมน์ category</li>
                                    <li>ตรวจสอบว่าข้อมูลใน category ตรงกับที่กำหนด</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-bug me-2"></i>
                                Debug Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>JavaScript Console Commands:</h6>
                            <pre class="bg-light p-3"><code>// ตรวจสอบ Payment Switch
document.querySelectorAll('.payment-switch').forEach(el => {
    console.log('Switch found:', el.dataset.orderId);
});

// ตรวจสอบ AJAX Request
fetch('orders.php?action=update_payment', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({order_id: 1, payment_status: 'paid'})
}).then(r => r.json()).then(console.log);</code></pre>
                            
                            <h6>PHP Error Log Location:</h6>
                            <p class="text-muted">ตรวจสอบ PHP Error Log เพื่อดู error details:</p>
                            <ul>
                                <li>XAMPP: <code>C:\xampp\php\logs\php_error_log</code></li>
                                <li>Server: <code>/var/log/php/error.log</code></li>
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
    console.log('=== Database Fixes Test Page ===');
    
    // Test if we can access the pages
    console.log('Dashboard URL:', 'dashboard.php');
    console.log('Orders URL:', 'orders.php');
    
    // Check if payment switches exist
    const switches = document.querySelectorAll('.payment-switch');
    console.log('Payment switches found:', switches.length);
    
    // Test AJAX endpoint
    console.log('Testing update_payment endpoint...');
    fetch('orders.php?action=update_payment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: 999, // Test with non-existent ID
            payment_status: 'paid'
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
    })
    .catch(error => {
        console.error('AJAX Error:', error);
    });
    
    console.log('=== Test Complete ===');
});
</script>

<?php
$content = ob_get_clean();

// Use main layout
include APP_VIEWS . 'layouts/main.php';
?>
