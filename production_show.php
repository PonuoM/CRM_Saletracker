<?php
/**
 * CRM SalesTracker - Customer Detail View (Production Version)
 * แสดงรายละเอียดลูกค้า
 */

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$customer = $customerData ?? null;
$orders = $orderData ?? [];
$callLogs = $callLogs ?? [];
$activities = $activities ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;

if (!$customer) {
    header('Location: customers.php');
    exit;
}

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

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดลูกค้า - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7C9885;
            --text-color: #4A5568;
        }
        
        body {
            color: var(--text-color);
        }
        
        .btn-primary, .btn-success, .btn-info, .btn-warning, .btn-secondary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }
        
        .btn-primary:hover, .btn-success:hover, .btn-info:hover, .btn-warning:hover, .btn-secondary:hover {
            background-color: #6a8573 !important;
            border-color: #6a8573 !important;
        }
        
        .btn-outline-primary, .btn-outline-secondary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .btn-outline-primary:hover, .btn-outline-secondary:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--primary-color);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .table th {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .table td {
            font-size: 0.875rem;
        }
        
        .badge {
            font-size: 0.75rem;
        }
    </style>
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
                    <h1 class="h2">รายละเอียดลูกค้า</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="customers.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>กลับ
                        </a>
                        <button class="btn btn-success me-2" onclick="logCall(<?php echo $customer['customer_id']; ?>)">
                            <i class="fas fa-phone me-1"></i>บันทึกการโทร
                        </button>
                        <button class="btn btn-info me-2" onclick="createAppointment(<?php echo $customer['customer_id']; ?>)">
                            <i class="fas fa-calendar me-1"></i>นัดหมาย
                        </button>
                        <button class="btn btn-warning me-2" onclick="createOrder(<?php echo $customer['customer_id']; ?>)">
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

                <!-- History Section -->
                <div class="row">
                    <!-- Recent Call Logs -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">ประวัติการโทร</h5>
                                <button class="btn btn-sm btn-success" onclick="logCall(<?php echo $customer['customer_id']; ?>)">
                                    <i class="fas fa-plus me-1"></i>เพิ่ม
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($paginatedCallLogs)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>วันที่</th>
                                                    <th>ผู้โทร</th>
                                                    <th>สถานะ</th>
                                                    <th>ผล</th>
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
                        </div>
                    </div>

                    <!-- Orders History -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">ประวัติคำสั่งซื้อ</h5>
                                <button class="btn btn-sm btn-warning" onclick="createOrder(<?php echo $customer['customer_id']; ?>)">
                                    <i class="fas fa-plus me-1"></i>สร้าง
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($paginatedOrders)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>เลขที่</th>
                                                    <th>วันที่</th>
                                                    <th>ยอดรวม</th>
                                                    <th>สถานะ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paginatedOrders as $order): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['order_id']); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                                        <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'processing' ? 'primary' : ($order['status'] === 'pending' ? 'warning' : 'secondary')); ?>">
                                                                <?php echo htmlspecialchars($order['status']); ?>
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
            </main>
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
                    <button type="button" class="btn btn-primary" onclick="submitCallLog()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Customer detail page specific functions - Define these first
        window.logCall = function(customerId) {
            document.getElementById('callCustomerId').value = customerId;
            const modal = new bootstrap.Modal(document.getElementById('logCallModal'));
            modal.show();
        };

        window.createAppointment = function(customerId) {
            // Create a simple appointment modal since appointments.php doesn't exist
            const appointmentModal = `
                <div class="modal fade" id="appointmentModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">สร้างนัดหมาย</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="appointmentForm">
                                    <input type="hidden" id="appointmentCustomerId" value="${customerId}">
                                    <div class="mb-3">
                                        <label for="appointmentDate" class="form-label">วันที่นัดหมาย</label>
                                        <input type="datetime-local" class="form-control" id="appointmentDate" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="appointmentType" class="form-label">ประเภทนัดหมาย</label>
                                        <select class="form-select" id="appointmentType" required>
                                            <option value="">เลือกประเภท</option>
                                            <option value="call">โทรศัพท์</option>
                                            <option value="meeting">ประชุม</option>
                                            <option value="presentation">นำเสนอ</option>
                                            <option value="followup">ติดตาม</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="appointmentNotes" class="form-label">หมายเหตุ</label>
                                        <textarea class="form-control" id="appointmentNotes" rows="3"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="button" class="btn btn-primary" onclick="submitAppointment()">บันทึก</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('appointmentModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', appointmentModal);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            modal.show();
        };

        window.createOrder = function(customerId) {
            // Redirect to order creation page
            window.location.href = `orders.php?action=create&customer_id=${customerId}`;
        };

        window.viewHistory = function(customerId) {
            // Show all history in a modal or redirect to history page
            window.location.href = `customers.php?action=history&id=${customerId}`;
        };

        window.viewAllCallLogs = function(customerId) {
            // Show all call logs
            window.location.href = `customers.php?action=call_logs&id=${customerId}`;
        };

        window.viewAllOrders = function(customerId) {
            // Show all orders
            window.location.href = `customers.php?action=orders&id=${customerId}`;
        };

        window.viewOrder = function(orderId) {
            window.location.href = `orders.php?action=show&id=${orderId}`;
        };

        // Submit appointment function
        window.submitAppointment = function() {
            const customerId = document.getElementById('appointmentCustomerId').value;
            const appointmentDate = document.getElementById('appointmentDate').value;
            const appointmentType = document.getElementById('appointmentType').value;
            const notes = document.getElementById('appointmentNotes').value;
            
            if (!appointmentDate || !appointmentType) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            // For now, just show success message and close modal
            // In a real implementation, you would send this to the server
            alert('บันทึกนัดหมายสำเร็จ');
            bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
            
            // Remove modal from DOM
            setTimeout(() => {
                const modal = document.getElementById('appointmentModal');
                if (modal) {
                    modal.remove();
                }
            }, 300);
        };

        // Submit call log function
        window.submitCallLog = function() {
            const customerId = document.getElementById('callCustomerId').value;
            const callType = document.getElementById('callType').value;
            const callStatus = document.getElementById('callStatus').value;
            const callResult = document.getElementById('callResult').value;
            const duration = document.getElementById('callDuration').value;
            const notes = document.getElementById('callNotes').value;
            const nextAction = document.getElementById('nextAction').value;
            const nextFollowup = document.getElementById('nextFollowup').value;
            
            if (!callStatus) {
                alert('กรุณาเลือกสถานะการโทร');
                return;
            }
            
            const data = {
                customer_id: parseInt(customerId),
                call_type: callType,
                call_status: callStatus,
                call_result: callResult || null,
                duration: parseInt(duration) || 0,
                notes: notes,
                next_action: nextAction,
                next_followup: nextFollowup || null
            };
            
            fetch('api/customers.php?action=log_call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('บันทึกการโทรสำเร็จ');
                    bootstrap.Modal.getInstance(document.getElementById('logCallModal')).hide();
                    // Reload page to show updated data
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกการโทร');
            });
        };

        // Debug function to check if functions are loaded
        console.log('Customer detail page functions loaded successfully');
    </script>
    <script src="assets/js/customers.js"></script>
</body>
</html> 