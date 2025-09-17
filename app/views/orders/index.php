<?php
/**
 * CRM SalesTracker - Orders List View
 * แสดงรายการคำสั่งซื้อ
 */

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$orders = $orderList ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - CRM SalesTracker</title>
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
            <main class="col-md-9 col-lg-10 main-content page-transition">
                <div id="orders-alert-container"></div>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">จัดการคำสั่งซื้อ</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="orders.php?action=create" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-1"></i>สร้างคำสั่งซื้อใหม่
                        </a>
                        <button id="loadMoreBtn" class="btn btn-outline-primary">
                            <i class="fas fa-chevron-down me-1"></i>แสดงเพิ่มอีก 10
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="orders.php" class="row g-3">
                            <input type="hidden" name="action" value="index">
                            <div class="col-md-3">
                                <label for="search" class="form-label">ค้นหา</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                       placeholder="เลขที่คำสั่งซื้อ, ชื่อลูกค้า, เบอร์โทร, ชื่อเล่น">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">สถานะ</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">ทั้งหมด</option>
                                    <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                    <option value="processing" <?php echo ($_GET['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                                    <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                                    <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">วันที่เริ่มต้น</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>ค้นหา
                                </button>
                                <a href="orders.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>ล้าง
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">รายการคำสั่งซื้อ</h5>
                        <div>
                            <a href="orders.php?action=create&guest=1" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-plus me-1"></i>สร้างคำสั่งซื้อ (ลูกค้าใหม่)
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>เลขที่คำสั่งซื้อ</th>
                                            <th>ลูกค้า</th>
                                            <th>เบอร์โทร</th>
                                            <th>วันที่</th>
                                            <th>จำนวนรายการ</th>
                                            <th>จำนวน (ชิ้น)</th>
                                            <th>ยอดรวม</th>
                                            <th>สถานะ</th>
                                            <th>การชำระเงิน</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['phone'] ?? ''); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                                <td><?php echo $order['item_count'] ?? 0; ?> รายการ</td>
                                                <td><?php echo (int)($order['total_quantity'] ?? 0); ?></td>
                                                <td>
                                                    <strong class="text-success">
                                                        ฿<?php echo number_format($order['total_amount'], 2); ?>
                                                    </strong>
                                                    <?php if (isset($order['item_count']) && $order['item_count'] > 0): ?>
                                                        <br><small class="text-muted"><?php echo $order['item_count']; ?> รายการ</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (in_array($roleName, ['supervisor', 'admin', 'super_admin']) || ($order['created_by'] ?? null) == $userId): ?>
                                                        <?php
                                                            $delMap = ['pending'=>'รอดำเนินการ','confirmed'=>'ยืนยันแล้ว','shipped'=>'จัดส่งแล้ว','delivered'=>'ส่งมอบแล้ว','cancelled'=>'ยกเลิก'];
                                                            $delColor = [
                                                                'pending' => 'warning',
                                                                'confirmed' => 'info',
                                                                'shipped' => 'primary',
                                                                'delivered' => 'success',
                                                                'cancelled' => 'danger'
                                                            ][$order['delivery_status'] ?? 'pending'];
                                                            $currDel = $order['delivery_status'] ?? 'pending';
                                                        ?>
                                                        <div class="dropdown dropup d-inline-block">
                                                            <button id="status-badge-<?php echo (int)$order['order_id']; ?>" class="badge bg-<?php echo $delColor; ?> text-dark dropdown-toggle border-0" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-container="body" aria-expanded="false">
                                                                <?php echo htmlspecialchars($delMap[$currDel]); ?>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <?php foreach (['pending'=>'รอดำเนินการ','confirmed'=>'ยืนยันแล้ว','shipped'=>'จัดส่งแล้ว','delivered'=>'ส่งมอบแล้ว','cancelled'=>'ยกเลิก'] as $val=>$label): ?>
                                                                    <li><a class="dropdown-item" href="#" onclick="updateDeliveryStatusInline(<?php echo (int)$order['order_id']; ?>, '<?php echo $val; ?>'); return false;"><?php echo $label; ?></a></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-<?php echo $order['delivery_status'] === 'shipped' ? 'primary' : ($order['delivery_status'] === 'pending' ? 'warning' : ($order['delivery_status'] === 'confirmed' ? 'info' : ($order['delivery_status'] === 'delivered' ? 'success' : 'secondary'))); ?> text-dark">
                                                            <?php echo htmlspecialchars($order['delivery_status']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $paymentMethodLabels = [
                                                        'cash' => 'เงินสด',
                                                        'transfer' => 'โอนเงิน',
                                                        'cod' => 'เก็บเงินปลายทาง',
                                                        'receive_before_payment' => 'รับสินค้าก่อนชำระ',
                                                        'credit' => 'เครดิต',
                                                        'other' => 'อื่นๆ'
                                                    ];
                                                    $paymentMethodLabel = $paymentMethodLabels[$order['payment_method']] ?? $order['payment_method'];
                                                    $badgeClass = in_array($order['payment_method'], ['cod', 'receive_before_payment']) ? 'warning' : 'info';
                                                    ?>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-<?php echo $badgeClass; ?> text-dark">
                                                            <?php echo htmlspecialchars($paymentMethodLabel); ?>
                                                        </span>
                                                        <?php if (in_array($roleName, ['supervisor', 'admin', 'super_admin']) || ($order['created_by'] ?? null) == $userId): ?>
                                                            <?php
                                                                $payMap = ['pending'=>'รอชำระ','paid'=>'ชำระแล้ว','partial'=>'ชำระบางส่วน','cancelled'=>'ยกเลิก','returned'=>'ตีกลับ'];
                                                                $payColor = [
                                                                    'pending' => 'warning',
                                                                    'paid' => 'success',
                                                                    'partial' => 'info',
                                                                    'cancelled' => 'danger',
                                                                    'returned' => 'secondary'
                                                                ][$order['payment_status'] ?? 'pending'];
                                                                $currPay = $order['payment_status'] ?? 'pending';
                                                            ?>
                                                            <div class="dropdown dropup d-inline-block">
                                                                <button id="payment-badge-<?php echo (int)$order['order_id']; ?>" class="badge bg-<?php echo $payColor; ?> text-dark dropdown-toggle border-0" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-container="body" aria-expanded="false">
                                                                    <?php echo htmlspecialchars($payMap[$currPay]); ?>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php foreach (['pending'=>'รอชำระ','paid'=>'ชำระแล้ว','partial'=>'ชำระบางส่วน','cancelled'=>'ยกเลิก','returned'=>'ตีกลับ'] as $val=>$label): ?>
                                                                        <li><a class="dropdown-item" href="#" onclick="updatePaymentStatusInline(<?php echo (int)$order['order_id']; ?>, '<?php echo $val; ?>'); return false;"><?php echo $label; ?></a></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <a href="orders.php?action=show&id=<?php echo $order['order_id']; ?>"
                                                           class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (in_array($roleName, ['supervisor', 'admin', 'super_admin']) || $order['created_by'] == $userId): ?>
                                                        <a href="orders.php?action=edit&id=<?php echo $order['order_id']; ?>"
                                                           class="btn btn-sm btn-outline-warning" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-center mt-3">
                                    <button id="loadMoreBottomBtn" class="btn btn-outline-secondary btn-sm">
                                        แสดงเพิ่มอีก 10
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5>ไม่มีคำสั่งซื้อ</h5>
                                <p class="text-muted">ยังไม่มีการสร้างคำสั่งซื้อ</p>
                                <a href="orders.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>สร้างคำสั่งซื้อใหม่
                                </a>
                            </div>
                        <?php endif; ?>
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
                        <input type="hidden" id="orderId" name="order_id">
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">สถานะใหม่</label>
                            <select class="form-select" id="newStatus" name="status" required>
                                <option value="pending">รอดำเนินการ</option>
                                <option value="processing">กำลังดำเนินการ</option>
                                <option value="completed">เสร็จสิ้น</option>
                                <option value="cancelled">ยกเลิก</option>
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
                    <button type="button" class="btn btn-primary" onclick="updateStatus()">อัปเดต</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script>
      // expose session info for JS render function
      window.sessionRoleName = <?php echo json_encode($roleName); ?>;
      window.sessionUserId = <?php echo json_encode($userId); ?>;
    </script>
    <script src="assets/js/orders.js"></script>
</body>
</html> 