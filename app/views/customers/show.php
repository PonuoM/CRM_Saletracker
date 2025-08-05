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

<!-- Customer Detail Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">รายละเอียดลูกค้า</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="customers.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i>กลับ
        </a>
        <button class="btn btn-success me-2" id="logCallBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
            <i class="fas fa-phone me-1"></i>บันทึกการโทร
        </button>
        <button class="btn btn-info me-2" id="createAppointmentBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
            <i class="fas fa-calendar me-1"></i>นัดหมาย
        </button>
        <button class="btn btn-warning me-2" id="createOrderBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
            <i class="fas fa-shopping-cart me-1"></i>สร้างคำสั่งซื้อ
        </button>
        <a href="customers.php?action=edit&id=<?php echo $customer['customer_id']; ?>" class="btn btn-primary">
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
                                ฿<?php echo number_format($customer['total_purchase'] ?? 0, 2); ?>
                            </strong>
                        </p>
                        <p><strong>จำนวนครั้งที่ซื้อ:</strong> <?php echo count($orders); ?> ครั้ง</p>
                        <p><strong>จำนวนครั้งที่ติดต่อ:</strong> <?php echo count($callLogs); ?> ครั้ง</p>
                        <p><strong>ผู้ดูแล:</strong> <?php echo htmlspecialchars($customer['assigned_to_name'] ?? 'ไม่ระบุ'); ?></p>
                        <p><strong>วันที่ลงทะเบียน:</strong> <?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></p>
                        <?php if ($customer['recall_at']): ?>
                        <p><strong>นัดติดตาม:</strong> 
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
                        <div class="h4 text-success">฿<?php echo number_format($customer['total_purchase'] ?? 0, 0); ?></div>
                        <small class="text-muted">ยอดซื้อรวม</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-info"><?php echo count($callLogs); ?></div>
                        <small class="text-muted">การโทร</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-warning"><?php echo count($activities); ?></div>
                        <small class="text-muted">กิจกรรม</small>
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
                        <button class="nav-link active" id="calls-tab" data-bs-toggle="tab" data-bs-target="#calls" type="button" role="tab" aria-controls="calls" aria-selected="true">
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
                    <div class="tab-pane fade show active" id="calls" role="tabpanel" aria-labelledby="calls-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">ประวัติการโทรล่าสุด</h6>
                            <button class="btn btn-sm btn-success" id="logCallBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
                                <i class="fas fa-plus me-1"></i>บันทึกการโทร
                            </button>
                        </div>
                        <?php if (!empty($paginatedCallLogs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="font-size: 14px;">วันที่</th>
                                            <th style="font-size: 14px;">ผู้โทร</th>
                                            <th style="font-size: 14px;">สถานะ</th>
                                            <th style="font-size: 14px;">ผลการโทร</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedCallLogs as $log): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($log['user_name'] ?? 'ไม่ระบุ'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $log['call_status'] === 'answered' ? 'success' : ($log['call_status'] === 'no_answer' ? 'danger' : 'warning'); ?>">
                                                        <?php echo $log['call_status'] === 'answered' ? 'รับสาย' : ($log['call_status'] === 'no_answer' ? 'ไม่รับสาย' : 'สายไม่ว่าง'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['call_result'] ?? 'ไม่ระบุ'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination for Call Logs -->
                            <?php if ($totalCallLogPages > 1): ?>
                                <nav aria-label="Call logs pagination">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <?php if ($currentPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage - 1; ?>">ก่อนหน้า</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalCallLogPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalCallLogPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage + 1; ?>">ถัดไป</a>
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
                            <button class="btn btn-sm btn-info" id="addAppointmentBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
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
                            <button class="btn btn-sm btn-warning" id="addOrderBtn" data-customer-id="<?php echo $customer['customer_id']; ?>">
                                <i class="fas fa-plus me-1"></i>สร้างคำสั่งซื้อ
                            </button>
                        </div>
                        <?php if (!empty($paginatedOrders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="font-size: 14px;">เลขที่</th>
                                            <th style="font-size: 14px;">วันที่</th>
                                            <th style="font-size: 14px;">ยอดรวม</th>
                                            <th style="font-size: 14px;">สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedOrders as $order): ?>
                                            <tr>
                                                <td style="font-size: 14px;"><?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['order_id']); ?></td>
                                                <td style="font-size: 14px;"><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                                <td style="font-size: 14px;">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $statusText = '';
                                                    $statusClass = '';
                                                    switch($order['status']) {
                                                        case 'completed':
                                                            $statusText = 'เสร็จสิ้น';
                                                            $statusClass = 'success';
                                                            break;
                                                        case 'processing':
                                                            $statusText = 'กำลังดำเนินการ';
                                                            $statusClass = 'primary';
                                                            break;
                                                        case 'pending':
                                                            $statusText = 'รอดำเนินการ';
                                                            $statusClass = 'warning';
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
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage - 1; ?>">ก่อนหน้า</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalOrderPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalOrderPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=show&id=<?php echo $customer['customer_id']; ?>&page=<?php echo $currentPage + 1; ?>">ถัดไป</a>
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
        <h5 class="mb-0">กิจกรรมล่าสุด</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($activities)): ?>
            <div class="timeline">
                <?php foreach (array_slice($activities, 0, 10) as $activity): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($activity['user_name'] ?? 'ไม่ระบุ'); ?></strong>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                            <p class="mb-0"><?php echo htmlspecialchars($activity['activity_description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted text-center mb-0">ไม่มีกิจกรรม</p>
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
                            <label for="callType" class="form-label">ประเภทการโทร</label>
                            <select class="form-select" id="callType" required>
                                <option value="outbound">โทรออก</option>
                                <option value="inbound">โทรเข้า</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="callStatus" class="form-label">สถานะการโทร</label>
                            <select class="form-select" id="callStatus" required>
                                <option value="answered">รับสาย</option>
                                <option value="no_answer">ไม่รับสาย</option>
                                <option value="busy">สายไม่ว่าง</option>
                                <option value="invalid">เบอร์ไม่ถูกต้อง</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="callResult" class="form-label">ผลการโทร</label>
                            <select class="form-select" id="callResult">
                                <option value="">เลือกผลการโทร</option>
                                <option value="interested">สนใจ</option>
                                <option value="not_interested">ไม่สนใจ</option>
                                <option value="callback">โทรกลับ</option>
                                <option value="order">สั่งซื้อ</option>
                                <option value="complaint">ร้องเรียน</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="callDuration" class="form-label">ระยะเวลา (นาที)</label>
                            <input type="number" class="form-control" id="callDuration" min="0" value="0">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="callNotes" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="callNotes" rows="3"></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="nextAction" class="form-label">การดำเนินการต่อไป</label>
                            <input type="text" class="form-control" id="nextAction">
                        </div>
                        <div class="col-md-6">
                            <label for="nextFollowup" class="form-label">นัดติดตาม</label>
                            <input type="datetime-local" class="form-control" id="nextFollowup">
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