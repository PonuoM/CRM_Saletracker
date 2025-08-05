<?php
/**
 * CRM SalesTracker - Order Detail View
 * แสดงรายละเอียดคำสั่งซื้อ
 */

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order = $orderData ?? null;
$items = $orderItems ?? [];
$activities = $orderActivities ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;

if (!$order) {
    header('Location: orders.php');
    exit;
}

$paymentMethods = [
    'cash' => 'เงินสด',
    'transfer' => 'โอนเงิน',
    'cod' => 'เก็บเงินปลายทาง',
    'credit' => 'เครดิต',
    'other' => 'อื่นๆ'
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <!-- Include Header Component -->
    <?php include APP_VIEWS . 'components/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar Component -->
            <?php include APP_VIEWS . 'components/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">รายละเอียดคำสั่งซื้อ</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="orders.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>กลับ
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Order Information -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">ข้อมูลคำสั่งซื้อ</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>เลขที่คำสั่งซื้อ:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                                        <p><strong>ลูกค้า:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y', strtotime($order['order_date'])); ?></p>
                                        <p><strong>วิธีการชำระเงิน:</strong> <?php echo $paymentMethods[$order['payment_method']] ?? $order['payment_method']; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>สถานะ:</strong> 
                                            <span class="badge bg-<?php echo $order['delivery_status'] === 'delivered' ? 'success' : ($order['delivery_status'] === 'shipped' ? 'primary' : ($order['delivery_status'] === 'pending' ? 'warning' : 'secondary')); ?> text-dark">
                                                <?php echo htmlspecialchars($order['delivery_status']); ?>
                                            </span>
                                        </p>
                                        <p><strong>วิธีการจัดส่ง:</strong> <?php echo htmlspecialchars($order['delivery_method']); ?></p>
                                        <p><strong>ที่อยู่จัดส่ง:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? 'ไม่ระบุ'); ?></p>
                                        <p><strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($order['notes'] ?? 'ไม่มี'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">รายการสินค้า</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($items)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>สินค้า</th>
                                                    <th>รหัส</th>
                                                    <th class="text-center">จำนวน</th>
                                                    <th class="text-end">ราคาต่อหน่วย</th>
                                                    <th class="text-end">รวม</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                        <td class="text-end">฿<?php echo number_format($item['unit_price'], 2); ?></td>
                                                        <td class="text-end">฿<?php echo number_format($item['total_price'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">ไม่มีรายการสินค้า</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Order Activities -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">ประวัติกิจกรรม</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($activities)): ?>
                                    <div class="timeline">
                                        <?php foreach ($activities as $activity): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-primary"></div>
                                                <div class="timeline-content">
                                                    <h6><?php echo htmlspecialchars($activity['description']); ?></h6>
                                                    <small class="text-muted">
                                                        โดย <?php echo htmlspecialchars($activity['user_name']); ?> • 
                                                        <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">ไม่มีประวัติกิจกรรม</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">สรุปคำสั่งซื้อ</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>ยอดรวมสินค้า:</span>
                                        <span>฿<?php echo number_format($order['subtotal'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>ส่วนลด:</span>
                                        <span>฿<?php echo number_format($order['discount'] ?? 0, 2); ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong>ยอดรวมทั้งสิ้น:</strong>
                                        <strong class="text-success">฿<?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">การดำเนินการ</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-danger" onclick="cancelOrder()">
                                        <i class="fas fa-times me-1"></i>ยกเลิก
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="markAsCompleted()">
                                        <i class="fas fa-check me-1"></i>จัดส่งสำเร็จ
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" onclick="markAsProcessing()">
                                        <i class="fas fa-shipping-fast me-1"></i>จัดส่งแล้ว
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">อัปเดตสถานะคำสั่งซื้อ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="orderId" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">สถานะใหม่</label>
                            <select class="form-select" id="newStatus" name="status" required>
                                <option value="pending" <?php echo $order['delivery_status'] === 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                <option value="shipped" <?php echo $order['delivery_status'] === 'shipped' ? 'selected' : ''; ?>>จัดส่งแล้ว</option>
                                <option value="delivered" <?php echo $order['delivery_status'] === 'delivered' ? 'selected' : ''; ?>>จัดส่งสำเร็จ</option>
                                <option value="cancelled" <?php echo $order['delivery_status'] === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statusNotes" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="statusNotes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">อัปเดต</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/orders.js"></script>
    <script>
        // Quick action functions
        function cancelOrder() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคำสั่งซื้อนี้?')) {
                updateOrderStatus('cancelled');
            }
        }
        
        function markAsCompleted() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะเปลี่ยนสถานะเป็น "จัดส่งสำเร็จ"?')) {
                updateOrderStatus('delivered');
            }
        }
        
        function markAsProcessing() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะเปลี่ยนสถานะเป็น "จัดส่งแล้ว"?')) {
                updateOrderStatus('shipped');
            }
        }
        
        function updateOrderStatus(status) {
            const orderId = <?php echo $order['order_id']; ?>;
            
            fetch('orders.php?action=update_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    field: 'delivery_status',
                    value: status,
                    notes: 'อัปเดตสถานะโดยระบบ'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('อัปเดตสถานะสำเร็จ', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('เกิดข้อผิดพลาดในการอัปเดตสถานะ: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            });
        }
        
        function submitStatusUpdate() {
            const form = document.getElementById('statusForm');
            const formData = new FormData(form);
            
            fetch('orders.php?action=update_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: formData.get('order_id'),
                    field: 'delivery_status',
                    value: formData.get('status'),
                    notes: formData.get('notes')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('อัปเดตสถานะสำเร็จ', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('เกิดข้อผิดพลาดในการอัปเดตสถานะ: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            });
        }
    </script>
</body>
</html> 