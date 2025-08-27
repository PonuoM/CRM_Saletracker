<?php
/**
 * CRM SalesTracker - Customer Detail View Template
 * Template สำหรับแสดงรายละเอียดลูกค้า
 */

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($customer) || !$customer) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูลลูกค้า</div>';
    return;
}

$orders = $orderData ?? [];
$callLogs = $callLogs ?? [];
$activities = $activities ?? [];

// Pagination settings
$itemsPerPage = 5;
$currentPage = $_GET['page'] ?? 1;
$currentPage = max(1, intval($currentPage));

// Paginate call logs
$totalCallLogs = count($callLogs);
$totalCallLogPages = ceil($totalCallLogs / $itemsPerPage);
$callLogsOffset = ($currentPage - 1) * $itemsPerPage;
$paginatedCallLogs = array_slice($callLogs, $callLogsOffset, $itemsPerPage);

// Paginate orders
$totalOrders = count($orders);
$totalOrderPages = ceil($totalOrders / $itemsPerPage);
$ordersOffset = ($currentPage - 1) * $itemsPerPage;
$paginatedOrders = array_slice($orders, $ordersOffset, $itemsPerPage);
?>

<style>
/* CSS สำหรับแก้ปัญหา modal-backdrop */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal-backdrop.fade {
    opacity: 0.5 !important;
}

.modal-backdrop.show {
    opacity: 0.5 !important;
}

/* ตรวจสอบและลบ backdrop ที่ซ้อนกัน */
.modal-backdrop + .modal-backdrop {
    display: none !important;
}

/* ป้องกันการซ้อนทับของ backdrop */
body.modal-open {
    overflow: auto !important;
    padding-right: 0 !important;
}

/* ตรวจสอบ backdrop ที่เหลืออยู่ */
.modal-backdrop:not(:first-child) {
    display: none !important;
}

/* CSS สำหรับ tabs */
.nav-tabs .nav-link {
    cursor: pointer;
}

.nav-tabs .nav-link.active {
    background-color: #f8f9fa;
    border-color: #dee2e6 #dee2e6 #f8f9fa;
}

.tab-content {
    padding: 20px 0;
}

.tab-pane {
    min-height: 200px;
}
</style>

<!-- Customer Detail Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">รายละเอียดลูกค้า</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="customers.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i>กลับ
        </a>
        <button class="btn btn-success me-2 log-call-btn" id="logCallBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
            <i class="fas fa-phone me-1"></i>บันทึกการโทร
        </button>
        <button class="btn btn-info me-2" id="createAppointmentBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
            <i class="fas fa-calendar me-1"></i>นัดหมาย
        </button>
        <button class="btn btn-warning me-2" id="createOrderBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
            <i class="fas fa-shopping-cart me-1"></i>สร้างคำสั่งซื้อ
        </button>
        <a href="customers.php?action=edit_basic&id=<?php echo $customer['customer_id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i>แก้ไข
        </a>
    </div>
</div>

<!-- Customer Information -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">ข้อมูลลูกค้า</h5>
                <div>
                    <?php
                    $tempIcons = [
                        'hot' => '🔥',
                        'warm' => '🌤️',
                        'cold' => '❄️',
                        'frozen' => '🧊'
                    ];
                    $tempIcon = $tempIcons[$customer['temperature_status']] ?? '❓';
                    ?>
                    <span class="badge bg-<?php echo $customer['temperature_status'] === 'hot' ? 'danger' : ($customer['temperature_status'] === 'warm' ? 'warning' : ($customer['temperature_status'] === 'cold' ? 'info' : 'secondary')); ?> fs-6">
                        <?php echo $tempIcon; ?> <?php echo ucfirst($customer['temperature_status'] ?? 'Cold'); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>รหัสลูกค้า:</strong> <?php echo htmlspecialchars($customer['customer_code'] ?? 'ไม่ระบุ'); ?></p>
                        <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                        <p><strong>เบอร์โทร:</strong> 
                            <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($customer['phone']); ?>
                            </a>
                        </p>
                        <p><strong>อีเมล:</strong> 
                            <?php if ($customer['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">ไม่ระบุ</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($customer['address'] ?? 'ไม่ระบุ'); ?></p>
                        <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($customer['province'] ?? 'ไม่ระบุ'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>เกรดลูกค้า:</strong> 
                            <span class="badge bg-<?php echo $customer['customer_grade'] === 'A+' ? 'success' : ($customer['customer_grade'] === 'A' ? 'primary' : ($customer['customer_grade'] === 'B' ? 'info' : ($customer['customer_grade'] === 'C' ? 'warning' : 'secondary'))); ?> fs-6">
                                <?php echo htmlspecialchars($customer['customer_grade'] ?? 'D'); ?>
                            </span>
                        </p>
                        <p><strong>ยอดซื้อรวม:</strong> 
                            <strong class="text-success fs-5">
                                ฿<?php echo number_format($customer['total_purchase_amount'] ?? 0, 2); ?>
                            </strong>
                        </p>
                        <p><strong>จำนวนครั้งที่ซื้อ:</strong> <?php echo count($orders); ?> ครั้ง</p>
                        <p><strong>จำนวนครั้งที่ติดต่อ:</strong> <?php echo count($callLogs); ?> ครั้ง</p>
                        <p><strong>ผู้ดูแล:</strong> 
                            <?php echo htmlspecialchars($customer['assigned_to_name'] ?? 'ไม่ระบุ'); ?>
                            <?php if (in_array($_SESSION['role_name'] ?? '', ['admin', 'super_admin'])): ?>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="showChangeAssigneeModal(<?php echo $customer['customer_id']; ?>, '<?php echo htmlspecialchars($customer['assigned_to_name'] ?? 'ไม่ระบุ'); ?>')">
                                    <i class="fas fa-exchange-alt me-1"></i>เปลี่ยนผู้ดูแล
                                </button>
                            <?php endif; ?>
                        </p>
                        <p><strong>วันที่ลงทะเบียน:</strong> <?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></p>
                        <?php if ($customer['next_followup_at']): ?>
                        <p><strong>ติดตามถัดไป:</strong> 
                            <span class="badge bg-<?php echo strtotime($customer['next_followup_at']) < time() ? 'danger' : 'info'; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($customer['next_followup_at'])); ?>
                            </span>
                        </p>
                        <script>
                            // ส่งข้อมูลไปให้ JavaScript ใช้แสดงในหน้านัดหมาย
                            window.customerNextFollowup = '<?php echo $customer['next_followup_at']; ?>';
                        </script>
                        <?php endif; ?>
                        <?php if ($customer['recall_at']): ?>
                        <p><strong>นัดติดตาม (เก่า):</strong> 
                            <span class="badge bg-<?php echo strtotime($customer['recall_at']) < time() ? 'danger' : 'warning'; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($customer['recall_at'])); ?>
                            </span>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">สถิติสรุป</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h4 text-primary"><?php echo count($orders); ?></div>
                        <small class="text-muted">คำสั่งซื้อ</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-success">฿<?php echo number_format($customer['total_purchase_amount'] ?? 0, 0); ?></div>
                        <small class="text-muted">ยอดซื้อรวม</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-info"><?php echo count($callLogs); ?></div>
                        <small class="text-muted">การโทร</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-purple"><?php echo count($appointments ?? []); ?></div>
                        <small class="text-muted">นัดหมาย</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-warning"><?php echo count($activities); ?></div>
                        <small class="text-muted">กิจกรรม</small>
                    </div>
                    <div class="col-6 mb-3">
                        <?php 
                            // คำนวณเวลาคงเหลือจาก customer_time_expiry ถ้ามี ไม่งั้นดูจาก recall/next_followup
                            $expiry = $customer['customer_time_expiry'] ?? null;
                            $follow = $customer['next_followup_at'] ?? ($customer['recall_at'] ?? null);
                            $label = '';
                            $colorClass = 'text-muted';
                            if ($expiry) {
                                $expTs = strtotime($expiry);
                                $nowTs = time();
                                if ($expTs >= $nowTs) {
                                    $days = (int) ceil(($expTs - $nowTs) / 86400);
                                    if ($days < 0) { $days = 0; }
                                    $label = "{$days} วัน";
                                    $colorClass = ($days <= 3) ? 'text-warning' : 'text-info';
                                } else {
                                    $daysOver = (int) floor(($nowTs - $expTs) / 86400);
                                    $label = "เกินกำหนด {$daysOver} วัน";
                                    $colorClass = 'text-danger';
                                }
                            } elseif ($follow) {
                                $folTs = strtotime($follow);
                                $nowTs = time();
                                if ($folTs >= $nowTs) {
                                    $days = (int) ceil(($folTs - $nowTs) / 86400);
                                    if ($days < 0) { $days = 0; }
                                    $label = "ติดตามใน {$days} วัน";
                                    $colorClass = ($days <= 3) ? 'text-warning' : 'text-info';
                                } else {
                                    $daysOver = (int) floor(($nowTs - $folTs) / 86400);
                                    $label = "เกินกำหนด {$daysOver} วัน";
                                    $colorClass = 'text-danger';
                                }
                            } else {
                                $label = '-';
                            }
                        ?>
                        <div class="h4 <?php echo $colorClass; ?> mb-0"><?php echo htmlspecialchars($label); ?></div>
                        <small class="text-muted">เวลาคงเหลือ</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unified History Section -->
<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="historyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="call-history-tab" data-bs-toggle="tab" data-bs-target="#call-history" type="button" role="tab" aria-controls="call-history" aria-selected="true">
                            <i class="fas fa-phone me-1"></i>ประวัติการโทร
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab" aria-controls="appointments" aria-selected="false">
                            <i class="fas fa-calendar me-1"></i>รายการนัดหมาย
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                            <i class="fas fa-shopping-cart me-1"></i>ประวัติคำสั่งซื้อ
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="historyTabsContent">
                    <!-- Call History Tab -->
                    <div class="tab-pane fade show active" id="call-history" role="tabpanel" aria-labelledby="call-history-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">ประวัติการโทรล่าสุด</h6>
                            <button class="btn btn-sm btn-primary log-call-btn" data-customer-id="<?php echo $customer['customer_id']; ?>">
                                <i class="fas fa-phone me-1"></i>บันทึกการโทร
                            </button>
                        </div>
                        <?php if (!empty($paginatedCallLogs)): ?>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="font-size: 14px;">วันที่</th>
                                            <th style="font-size: 14px;">ผู้โทร</th>
                                            <th style="font-size: 14px;">สถานะ</th>
                                            <th style="font-size: 14px;">ผลการโทร</th>
                                            <th style="font-size: 14px;">หมายเหตุ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedCallLogs as $log): ?>
                                            <tr>
                                                <td style="font-size: 13px;"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td style="font-size: 13px;"><?php echo htmlspecialchars($log['user_name'] ?? 'ไม่ระบุ'); ?></td>
                                                <td style="font-size: 13px;">
                                                    <span class="badge bg-<?php 
                                                        echo $log['call_status'] === 'answered' ? 'success' : 
                                                            ($log['call_status'] === 'no_answer' ? 'danger' : 
                                                            ($log['call_status'] === 'hang_up' ? 'secondary' : 'warning')); 
                                                    ?>">
                                                        <?php 
                                                            echo $log['call_status'] === 'answered' ? 'รับสาย' : 
                                                                ($log['call_status'] === 'no_answer' ? 'ไม่รับสาย' : 
                                                                ($log['call_status'] === 'hang_up' ? 'ตัดสายทิ้ง' : 
                                                                ($log['call_status'] === 'invalid' ? 'เบอร์ผิด' : 'สายไม่ว่าง'))); 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td style="font-size: 13px;">
                                                    <?php 
                                                        $resultThMap = [
                                                            'order'=>'สั่งซื้อ','interested'=>'สนใจ','add_line'=>'Add Line แล้ว','buy_on_page'=>'ต้องการซื้อทางเพจ',
                                                            'flood'=>'น้ำท่วม','callback'=>'รอติดต่อใหม่','appointment'=>'นัดหมาย','invalid_number'=>'เบอร์ไม่ถูก',
                                                            'not_convenient'=>'ไม่สะดวกคุย','not_interested'=>'ไม่สนใจ','do_not_call'=>'อย่าโทรมาอีก',
                                                            'busy'=>'สายไม่ว่าง','unable_to_contact'=>'ติดต่อไม่ได้','hangup'=>'ตัดสายทิ้ง',
                                                            'ไม่รับสาย'=>'ไม่รับสาย','สายไม่ว่าง'=>'สายไม่ว่าง','เบอร์ผิด'=>'เบอร์ผิด','ตัดสายทิ้ง'=>'ตัดสายทิ้ง','ได้คุย'=>'ได้คุย','สนใจ'=>'สนใจ','ไม่สนใจ'=>'ไม่สนใจ','ลังเล'=>'ลังเล'
                                                        ];
                                                        $resultKey = $log['call_result'] ?? '';
                                                        echo htmlspecialchars($resultThMap[$resultKey] ?? $resultKey);
                                                    ?>
                                                </td>
                                                <td style="font-size: 13px;"><?php echo htmlspecialchars($log['notes'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($paginatedCallLogs) > 4): ?>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-arrow-down me-1"></i>
                                        เลื่อนลงเพื่อดูข้อมูลเพิ่มเติม (<?php echo count($paginatedCallLogs); ?> รายการ)
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Pagination for Call Logs -->
                            <?php if ($totalCallLogPages > 1): ?>
                                <nav aria-label="Call logs pagination">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <?php if ($currentPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage - 1; ?>&tab=call-history">ก่อนหน้า</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalCallLogPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $i; ?>&tab=call-history"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalCallLogPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage + 1; ?>&tab=call-history">ถัดไป</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">ไม่มีประวัติการโทร</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Appointments Tab -->
                    <div class="tab-pane fade" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">รายการนัดหมายล่าสุด</h6>
            <button class="btn btn-sm btn-info add-appointment-btn" data-customer-id="<?php echo $customer['customer_id']; ?>">
                                <i class="fas fa-plus me-1"></i>เพิ่มนัดหมาย
                            </button>
                        </div>
                        <div id="appointmentsList">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">กำลังโหลด...</span>
                                </div>
                                <span class="ms-2">กำลังโหลดรายการนัดหมาย...</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">ประวัติคำสั่งซื้อล่าสุด</h6>
            <button class="btn btn-sm btn-warning add-order-btn" data-customer-id="<?php echo $customer['customer_id']; ?>">
                                <i class="fas fa-plus me-1"></i>สร้างคำสั่งซื้อ
                            </button>
                        </div>
                        <?php if (!empty($paginatedOrders)): ?>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="font-size: 14px;">เลขที่</th>
                                            <th style="font-size: 14px;">วันที่</th>
                                            <th style="font-size: 14px;">รายการสินค้า</th>
                                            <th style="font-size: 14px;">ผู้ขาย</th>
                                            <th style="font-size: 14px;">ยอดรวม</th>
                                            <th style="font-size: 14px;">สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedOrders as $order): ?>
                                            <tr>
                                                <td style="font-size: 14px;"><?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['order_id']); ?></td>
                                                <td style="font-size: 14px;"><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                                <td style="font-size: 14px; max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <span class="text-muted">(ซ่อน)</span>
                                                </td>
                                                <td style="font-size: 14px;">
                                                    <?php if (!empty($order['salesperson_name'])): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($order['salesperson_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">ไม่ระบุ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-size: 14px;">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $statusText = '';
                                                    $statusClass = '';
                                                    $orderStatus = $order['payment_status'] ?? $order['status'] ?? $order['order_status'] ?? '';
                                                    switch($orderStatus) {
                                                        case 'paid':
                                                            $statusText = 'ชำระแล้ว';
                                                            $statusClass = 'success';
                                                            break;
                                                        case 'pending':
                                                            $statusText = 'รอชำระ';
                                                            $statusClass = 'warning';
                                                            break;
                                                        case 'partial':
                                                            $statusText = 'ชำระบางส่วน';
                                                            $statusClass = 'info';
                                                            break;
                                                        case 'cancelled':
                                                        case 'canceled':
                                                            $statusText = 'ยกเลิก';
                                                            $statusClass = 'danger';
                                                            break;
                                                        case 'completed':
                                                        case 'finished':
                                                            $statusText = 'เสร็จสิ้น';
                                                            $statusClass = 'success';
                                                            break;
                                                        case 'processing':
                                                        case 'in_progress':
                                                            $statusText = 'กำลังดำเนินการ';
                                                            $statusClass = 'primary';
                                                            break;
                                                        default:
                                                            $statusText = 'ไม่ระบุ';
                                                            $statusClass = 'secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>" style="font-size: 12px;">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                    <button class="btn btn-link btn-sm text-decoration-none ms-1" title="ดูรายการสินค้า" onclick="viewOrderItems(<?php echo (int)$order['order_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination for Orders -->
                            <?php if ($totalOrderPages > 1): ?>
                                <nav aria-label="Orders pagination">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <?php if ($currentPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage - 1; ?>&tab=orders">ก่อนหน้า</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalOrderPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $i; ?>&tab=orders"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalOrderPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage + 1; ?>&tab=orders">ถัดไป</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">ไม่มีประวัติคำสั่งซื้อ</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>กิจกรรมล่าสุด
            <?php if (count($activities) > 0): ?>
                <span class="badge bg-secondary ms-2"><?php echo count($activities); ?></span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($activities)): ?>
            <div class="activity-timeline-container" style="max-height: 300px; overflow-y: auto;">
                <div class="activity-timeline" id="activityTimeline">
                    <?php foreach ($activities as $index => $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="<?php echo $activity['icon'] ?? 'fas fa-info-circle'; ?> text-<?php echo $activity['color'] ?? 'secondary'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-header">
                                    <div class="activity-meta">
                                        <span class="activity-user">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($activity['user_name'] ?? 'ระบบ'); ?>
                                        </span>
                                        <span class="activity-time">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php
                                            // คำนวณเวลาที่ผ่านมา
                                            $time = time() - strtotime($activity['created_at']);
                                            if ($time < 60) {
                                                $timeAgo = 'เมื่อสักครู่';
                                            } elseif ($time < 3600) {
                                                $timeAgo = floor($time / 60) . ' นาทีที่แล้ว';
                                            } elseif ($time < 86400) {
                                                $timeAgo = floor($time / 3600) . ' ชั่วโมงที่แล้ว';
                                            } elseif ($time < 2592000) {
                                                $timeAgo = floor($time / 86400) . ' วันที่แล้ว';
                                            } elseif ($time < 31536000) {
                                                $timeAgo = floor($time / 2592000) . ' เดือนที่แล้ว';
                                            } else {
                                                $timeAgo = floor($time / 31536000) . ' ปีที่แล้ว';
                                            }
                                            echo $timeAgo . ' (' . date('d/m/Y H:i', strtotime($activity['created_at'])) . ')';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="activity-description">
                                    <?php echo htmlspecialchars($activity['activity_description']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($activities) > 10): ?>
                    <div class="activity-footer">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            แสดง <?php echo count($activities); ?> กิจกรรมทั้งหมด - เลื่อนเพื่อดูเพิ่มเติม
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="activity-empty">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">ยังไม่มีกิจกรรม</p>
                <small class="text-muted">กิจกรรมต่างๆ เช่น การโทร การนัดหมาย คำสั่งซื้อ จะแสดงที่นี่</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Log Call Modal -->
<div class="modal fade" id="logCallModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">บันทึกการโทร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="logCallForm">
                    <input type="hidden" id="callCustomerId" value="<?php echo $customer['customer_id']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="callStatus" class="form-label">สถานะการโทร</label>
                            <select class="form-select" id="callStatus" required>
                                <option value="">เลือกสถานะการโทร</option>
                                <option value="answered">รับสาย</option>
                                <option value="no_answer">ไม่รับสาย</option>
                                <option value="busy">สายไม่ว่าง</option>
                                <option value="invalid">เบอร์ผิด</option>
                                <option value="hang_up">ตัดสายทิ้ง</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="callResult" class="form-label">ผลการโทร</label>
                            <select class="form-select" id="callResult">
                                <option value="">เลือกผลการโทร</option>
                                <option value="สนใจ">สนใจ</option>
                                <option value="ไม่สนใจ">ไม่สนใจ</option>
                                <option value="ลังเล">ลังเล</option>
                                <option value="เบอร์ผิด">เบอร์ผิด</option>
                                <option value="ได้คุย">ได้คุย</option>
                                <option value="ตัดสายทิ้ง">ตัดสายทิ้ง</option>
                                <option value="ไม่รับสาย">ไม่รับสาย</option>
                                <option value="สายไม่ว่าง">สายไม่ว่าง</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="callDuration" class="form-label">ระยะเวลา (นาที)</label>
                            <input type="number" class="form-control" id="callDuration" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="nextFollowup" class="form-label">วันที่คาดว่าจะติดต่อครั้งถัดไป</label>
                            <input type="datetime-local" class="form-control" id="nextFollowup">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="callNotes" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="callNotes" rows="3"></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="callTags" class="form-label">เพิ่ม Tag</label>
                            <div class="d-flex gap-1 mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showAddTagModalFromCall()">
                                    <i class="fas fa-plus"></i> เพิ่ม Tag
                                </button>
                            </div>
                            <!-- Preview area สำหรับ Tags ที่เพิ่มแล้ว -->
                            <div id="callTagsPreview" class="border rounded p-2 bg-light min-height-40" style="min-height: 40px;">
                                <small class="text-muted">Tags ที่เพิ่มจะแสดงที่นี่</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="submitCallLogBtn">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Appointment Modal -->
<div class="modal fade" id="createAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">สร้างนัดหมาย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createAppointmentForm">
                    <input type="hidden" id="appointmentCustomerId" value="<?php echo $customer['customer_id']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="appointmentDate" class="form-label">วันที่นัดหมาย</label>
                            <input type="datetime-local" class="form-control" id="appointmentDate" required>
                        </div>
                        <div class="col-md-6">
                            <label for="appointmentType" class="form-label">ประเภทนัดหมาย</label>
                            <select class="form-select" id="appointmentType" required>
                                <option value="">เลือกประเภทนัดหมาย</option>
                                <option value="meeting">ประชุม</option>
                                <option value="call">โทรติดตาม</option>
                                <option value="visit">เยี่ยมลูกค้า</option>
                                <option value="other">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="appointmentDescription" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="appointmentDescription" rows="3"></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="appointmentTags" class="form-label">เพิ่ม Tag</label>
                            <div class="d-flex gap-1 mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showAddTagModalFromCall()">
                                    <i class="fas fa-plus"></i> เพิ่ม Tag
                                </button>
                            </div>
                            <!-- Preview area สำหรับ Tags ที่เพิ่มแล้ว -->
                            <div id="appointmentTagsPreview" class="border rounded p-2 bg-light min-height-40" style="min-height: 40px;">
                                <small class="text-muted">Tags ที่เพิ่มจะแสดงที่นี่</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="submitAppointmentBtn">สร้างนัดหมาย</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับเปลี่ยนผู้ดูแล -->
<?php if (in_array($_SESSION['role_name'] ?? '', ['admin', 'super_admin'])): ?>
<div class="modal fade" id="changeAssigneeModal" tabindex="-1" aria-labelledby="changeAssigneeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeAssigneeModalLabel">เปลี่ยนผู้ดูแลลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changeAssigneeForm">
                    <input type="hidden" id="customerId" name="customer_id">
                    <div class="mb-3">
                        <label for="currentAssignee" class="form-label">ผู้ดูแลปัจจุบัน</label>
                        <input type="text" class="form-control" id="currentAssignee" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="newAssignee" class="form-label">ผู้ดูแลใหม่</label>
                        <select class="form-select" id="newAssignee" name="new_assignee" required>
                            <option value="">เลือกผู้ดูแลใหม่</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="changeReason" class="form-label">เหตุผลในการเปลี่ยน</label>
                        <textarea class="form-control" id="changeReason" name="change_reason" rows="3" placeholder="ระบุเหตุผลในการเปลี่ยนผู้ดูแล"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="changeCustomerAssignee()">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Activity Timeline -->
<script>
// Pass customer data to JavaScript
window.customerNextFollowup = <?php echo json_encode($customer['next_followup_at']); ?>;
window.customerId = <?php echo json_encode($customer['customer_id']); ?>;

// Auto-fill ผลการโทรเมื่อเปลี่ยนสถานะการโทร
function setupCallStatusAutoFill() {
    const callStatus = document.getElementById('callStatus');
    const callResult = document.getElementById('callResult');
    
    if (callStatus && callResult) {
        callStatus.addEventListener('change', function() {
            // ถ้าเลือกสถานะที่ไม่ใช่ "รับสาย" ให้ auto-fill ผลการโทร
            if (this.value && this.value !== 'answered') {
                const statusValueMap = {
                    'no_answer': 'ไม่รับสาย',
                    'busy': 'สายไม่ว่าง',
                    'invalid': 'เบอร์ผิด',
                    'hang_up': 'ตัดสายทิ้ง'
                };
                const autoFillValue = statusValueMap[this.value];
                if (autoFillValue) {
                    // ตรวจสอบว่ามี option นี้อยู่ใน callResult หรือไม่
                    const option = Array.from(callResult.options).find(opt => opt.value === autoFillValue);
                    if (option) {
                        callResult.value = autoFillValue;
                    }
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม Auto-fill functionality
    setupCallStatusAutoFill();
    
    // ตั้งค่า Tabs
    setupTabs();
    
    // Activity Timeline Scroll Enhancement
    const timelineContainer = document.querySelector('.activity-timeline-container');

    if (timelineContainer) {
        // เพิ่ม smooth scrolling behavior
        timelineContainer.style.scrollBehavior = 'smooth';

        // เพิ่ม fade effect เมื่อ scroll ถึงด้านบนหรือล่าง
        timelineContainer.addEventListener('scroll', function() {
            const scrollTop = this.scrollTop;
            const scrollHeight = this.scrollHeight;
            const clientHeight = this.clientHeight;

            // เพิ่ม shadow เมื่อ scroll
            if (scrollTop > 0) {
                this.style.boxShadow = 'inset 0 7px 9px -7px rgba(0,0,0,0.1)';
            } else {
                this.style.boxShadow = 'none';
            }

            // เพิ่ม shadow ด้านล่างเมื่อยังไม่ scroll ถึงล่างสุด
            if (scrollTop + clientHeight < scrollHeight - 5) {
                this.style.borderBottom = '1px solid #e9ecef';
            } else {
                this.style.borderBottom = 'none';
            }
        });
    }
});

// ฟังก์ชันตั้งค่า Tabs
function setupTabs() {
    const tabLinks = document.querySelectorAll('#historyTabs .nav-link');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    console.log('Setting up tabs:', tabLinks.length, 'tabs found');
    console.log('Tab panes found:', tabPanes.length);
    
    // ตรวจสอบว่า tabs ถูกตั้งค่าถูกต้องหรือไม่
    if (tabLinks.length === 0) {
        console.error('No tab links found!');
        return;
    }
    
    if (tabPanes.length === 0) {
        console.error('No tab panes found!');
        return;
    }
    
    // ใช้ Bootstrap 5 Tab API
    tabLinks.forEach((link, index) => {
        const targetId = link.getAttribute('data-bs-target');
        const targetPane = document.querySelector(targetId);
        
        console.log(`Tab ${index + 1}:`, link.textContent.trim(), '->', targetId, 'Pane found:', !!targetPane);
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Tab clicked:', this.textContent.trim(), 'Target:', targetId);
            
            // ใช้ Bootstrap Tab API
            if (targetPane) {
                // ลบ active class จากทุก tab
                tabLinks.forEach(tab => tab.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('show', 'active'));
                
                // เพิ่ม active class ให้ tab ที่คลิก
                this.classList.add('active');
                targetPane.classList.add('show', 'active');
                
                // โหลดข้อมูลตาม tab ที่เลือก
                loadTabContent(targetId);
            } else {
                console.error('Target pane not found:', targetId);
            }
        });
    });
    
    // โหลดข้อมูลเริ่มต้นสำหรับ tab แรก (Call History)
    console.log('Loading initial tab content for call-history');
    loadTabContent('#call-history');
    
    // เพิ่ม Bootstrap 5 Tab initialization
    if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        console.log('Bootstrap 5 Tabs found, initializing...');
        tabLinks.forEach(link => {
            const tab = new bootstrap.Tab(link);
            link.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('data-bs-target');
                console.log('Bootstrap tab shown:', targetId);
                loadTabContent(targetId);
            });
        });
    } else {
        console.log('Bootstrap 5 Tabs not found, using custom implementation');
    }
}

// ฟังก์ชันโหลดข้อมูลตาม tab
function loadTabContent(tabId) {
    console.log('Loading tab content for:', tabId);
    
    switch(tabId) {
        case '#appointments':
            console.log('Loading appointments tab');
            loadAppointments();
            break;
        case '#orders':
            console.log('Loading orders tab');
            loadOrders();
            break;
        case '#call-history':
            // Call history ถูกโหลดแล้วจาก PHP
            console.log('Call history tab - data already loaded from PHP');
            break;
        default:
            console.log('Unknown tab:', tabId);
    }
}

// ฟังก์ชันโหลดข้อมูลนัดหมาย
function loadAppointments() {
    const appointmentsList = document.getElementById('appointmentsList');
    if (!appointmentsList) {
        console.error('Appointments list element not found');
        return;
    }
    
    console.log('Loading appointments for customer:', window.customerId);
    
    appointmentsList.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
            <span class="ms-2">กำลังโหลดรายการนัดหมาย...</span>
        </div>
    `;
    
    // เรียก API สำหรับดึงข้อมูลนัดหมาย
    const customerId = window.customerId || <?php echo $customer['customer_id']; ?>;
    const apiUrl = `api/appointments.php?action=get_by_customer&customer_id=${customerId}&limit=5`;
    
    console.log('Fetching from:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('API response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);
            if (data.success && data.data && data.data.length > 0) {
                const appointments = data.data;
                let html = '<div class="table-responsive">';
                html += '<table class="table table-hover table-sm">';
                html += '<thead class="table-light"><tr><th>วันที่</th><th>ประเภท</th><th>รายละเอียด</th><th>สถานะ</th></tr></thead>';
                html += '<tbody>';
                
                appointments.forEach(appointment => {
                    const date = new Date(appointment.appointment_date);
                    const formattedDate = date.toLocaleDateString('th-TH', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    html += `<tr>
                        <td>${formattedDate}</td>
                        <td>${getAppointmentTypeText(appointment.appointment_type)}</td>
                        <td>${appointment.description || 'ไม่มีรายละเอียด'}</td>
                        <td><span class="badge bg-success">นัดหมาย</span></td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                appointmentsList.innerHTML = html;
                console.log(`Loaded ${appointments.length} appointments`);
            } else {
                appointmentsList.innerHTML = '<p class="text-muted text-center mb-0">ไม่มีรายการนัดหมาย</p>';
                console.log('No appointments found');
            }
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            appointmentsList.innerHTML = `
                <div class="text-center">
                    <p class="text-danger mb-2">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                    <small class="text-muted d-block mb-2">${error.message}</small>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadAppointments()">ลองใหม่</button>
                </div>
            `;
        });
}

// ฟังก์ชันแปลงประเภทนัดหมายเป็นข้อความภาษาไทย
function getAppointmentTypeText(type) {
    const typeMap = {
        'meeting': 'ประชุม',
        'call': 'โทรติดตาม',
        'visit': 'เยี่ยมลูกค้า',
        'other': 'อื่นๆ'
    };
    return typeMap[type] || type || 'ไม่ระบุ';
}

// ฟังก์ชันโหลดข้อมูลคำสั่งซื้อ
function loadOrders() {
    const ordersTab = document.getElementById('orders');
    if (!ordersTab) return;
    
    console.log('Orders tab loaded - data already loaded from PHP');
    
    // ข้อมูลคำสั่งซื้อถูกโหลดแล้วจาก PHP ใน tab content
    // เพิ่มเติม: สามารถเพิ่มการ refresh ข้อมูลได้ที่นี่
    
    // ตรวจสอบว่ามีข้อมูลคำสั่งซื้อหรือไม่
    const orderRows = ordersTab.querySelectorAll('tbody tr');
    if (orderRows.length === 0) {
        console.log('No orders found in the table');
    } else {
        console.log(`Found ${orderRows.length} order rows`);
    }
}
</script>
<script>
function viewOrderItems(orderId) {
    fetch('orders.php?action=items&id=' + orderId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'ไม่สามารถโหลดรายการสินค้า'); return; }
            const items = data.items || [];
            const rows = items.map(it => `
                <tr>
                    <td>${escapeHtml(it.product_name || 'ไม่ทราบสินค้า')}</td>
                    <td>${escapeHtml(it.product_code || '')}</td>
                    <td class="text-center">${it.quantity}</td>
                    <td class="text-end">฿${Number(it.unit_price).toLocaleString('th-TH', {minimumFractionDigits:2})}</td>
                    <td class="text-end">฿${Number(it.total_price).toLocaleString('th-TH', {minimumFractionDigits:2})}</td>
                </tr>
            `).join('');
            const html = `
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>สินค้า</th>
                                <th>รหัส</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">ราคาต่อหน่วย</th>
                                <th class="text-end">รวม</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="5" class="text-center text-muted">ไม่มีรายการสินค้า</td></tr>'}</tbody>
                    </table>
                </div>`;
            showModal('รายละเอียดสินค้าในคำสั่งซื้อ #' + (data.order.order_number || orderId), html);
        })
        .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text || '');
    return div.innerHTML;
}

function showModal(title, bodyHtml) {
    // Clean up any existing backdrops first
    cleanupModalBackdrops();
    
    const id = 'orderItemsModal';
    let modal = document.getElementById(id);
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = id;
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body"></div>
                </div>
            </div>`;
        document.body.appendChild(modal);
        
        // Add event listeners for proper cleanup
        modal.addEventListener('hidden.bs.modal', function() {
            setTimeout(cleanupModalBackdrops, 100);
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            cleanupModalBackdrops();
        });
    }
    modal.querySelector('.modal-title').textContent = title;
    modal.querySelector('.modal-body').innerHTML = bodyHtml;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// ฟังก์ชันสำหรับแสดง Modal เปลี่ยนผู้ดูแล
function showChangeAssigneeModal(customerId, currentAssignee) {
    // Clean up any existing backdrops first
    cleanupModalBackdrops();
    
    document.getElementById('customerId').value = customerId;
    document.getElementById('currentAssignee').value = currentAssignee;
    
    // โหลดรายการ Telesales
    loadTelesalesList();
    
    // แสดง Modal
    const modal = new bootstrap.Modal(document.getElementById('changeAssigneeModal'));
    modal.show();
}

// โหลดรายการ Telesales
function loadTelesalesList() {
    fetch('api/customers.php?action=get_telesales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('newAssignee');
                select.innerHTML = '<option value="">เลือกผู้ดูแลใหม่</option>';
                
                data.data.forEach(telesales => {
                    const option = document.createElement('option');
                    option.value = telesales.user_id;
                    option.textContent = telesales.full_name + ' (' + telesales.company_name + ')';
                    select.appendChild(option);
                });
            } else {
                console.error('Error loading telesales:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// เปลี่ยนผู้ดูแลลูกค้า
function changeCustomerAssignee() {
    const customerId = document.getElementById('customerId').value;
    const newAssignee = document.getElementById('newAssignee').value;
    const changeReason = document.getElementById('changeReason').value;
    
    if (!newAssignee) {
        alert('กรุณาเลือกผู้ดูแลใหม่');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'change_assignee');
    formData.append('customer_id', customerId);
    formData.append('new_assignee', newAssignee);
    formData.append('change_reason', changeReason);
    
    fetch('api/customers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('เปลี่ยนผู้ดูแลสำเร็จ');
            // ปิด Modal และทำความสะอาด backdrop
            const modal = bootstrap.Modal.getInstance(document.getElementById('changeAssigneeModal'));
            if (modal) {
                modal.hide();
            }
            // รีโหลดหน้า
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถเปลี่ยนผู้ดูแลได้'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

// ฟังก์ชันทำความสะอาด modal backdrop
function cleanupModalBackdrops() {
    // ลบ backdrop ที่เหลืออยู่ทั้งหมด
    const backdrops = document.querySelectorAll('.modal-backdrop');
    let removedCount = 0;
    
    backdrops.forEach(backdrop => {
        backdrop.remove();
        removedCount++;
    });
    
    // ลบ body class ที่ Bootstrap เพิ่มเข้ามา
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // แสดง log ถ้ามีการลบ backdrop
    if (removedCount > 0) {
        console.log(`Cleaned up ${removedCount} modal backdrop(s)`);
    }
    
    return removedCount;
}

// ฟังก์ชันทำความสะอาดแบบ aggressive สำหรับกรณีที่ backdrop ยังคงอยู่
function forceCleanupBackdrops() {
    // ลบ backdrop ทั้งหมด
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // ลบ body classes และ styles ทั้งหมด
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // ลบ backdrop ที่อาจซ่อนอยู่
    const hiddenBackdrops = document.querySelectorAll('.modal-backdrop[style*="display: none"]');
    hiddenBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // ตรวจสอบและลบ backdrop ที่เหลืออยู่
    setTimeout(() => {
        const remainingBackdrops = document.querySelectorAll('.modal-backdrop');
        remainingBackdrops.forEach(backdrop => {
            backdrop.remove();
        });
    }, 100);
}

// เพิ่ม global function สำหรับเรียกใช้จาก console
window.cleanupBackdrops = cleanupModalBackdrops;
window.forceCleanupBackdrops = forceCleanupBackdrops;

// เพิ่ม event listener สำหรับ modal events
document.addEventListener('DOMContentLoaded', function() {
    // Clean up backdrops when modals are hidden
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            setTimeout(cleanupModalBackdrops, 100);
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            // Clean up immediately when hiding starts
            cleanupModalBackdrops();
        });
    });
    
    // Global backdrop cleanup on page load
    cleanupModalBackdrops();
    
    // Clean up backdrops every few seconds as a safety measure
    setInterval(cleanupModalBackdrops, 5000);
    
    // เพิ่ม keyboard shortcut สำหรับทำความสะอาด backdrop (Ctrl+Shift+B)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'B') {
            e.preventDefault();
            forceCleanupBackdrops();
            console.log('Backdrop cleanup triggered by keyboard shortcut');
        }
    });
    
    // เพิ่ม click handler สำหรับ backdrop ที่อาจเหลืออยู่
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.remove();
            cleanupModalBackdrops();
        }
    });
    
    // เพิ่ม error handler สำหรับ modal events
    window.addEventListener('error', function(e) {
        if (e.message.includes('modal') || e.message.includes('backdrop')) {
            setTimeout(forceCleanupBackdrops, 100);
        }
    });
});

// Override Bootstrap modal hide to ensure proper cleanup
if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const originalHide = bootstrap.Modal.prototype.hide;
    bootstrap.Modal.prototype.hide = function() {
        const result = originalHide.call(this);
        setTimeout(cleanupModalBackdrops, 100);
        return result;
    };
}
</script>