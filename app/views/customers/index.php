<?php
// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (!isset($customers)) $customers = [];
if (!isset($followUpCustomers)) $followUpCustomers = [];
if (!isset($telesalesList)) $telesalesList = [];
if (!isset($provinces)) $provinces = [];

$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการลูกค้า - CRM SalesTracker</title>
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
                    <h1 class="h2">จัดการลูกค้า</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportCustomers()">
                                <i class="fas fa-download me-1"></i>ส่งออก
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="customerTabs" role="tablist">
                    <?php if ($roleName === 'telesales'): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="do-tab" data-bs-toggle="tab" data-bs-target="#do" type="button" role="tab">
                            <i class="fas fa-tasks me-1"></i>Do
                            <span class="badge bg-danger ms-1"><?php echo count($followUpCustomers); ?></span>
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($roleName !== 'telesales') ? 'active' : ''; ?>" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab">
                            <i class="fas fa-user-plus me-1"></i>ลูกค้าใหม่
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="followup-tab" data-bs-toggle="tab" data-bs-target="#followup" type="button" role="tab">
                            <i class="fas fa-clock me-1"></i>ติดตาม
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing" type="button" role="tab">
                            <i class="fas fa-user me-1"></i>ลูกค้าเก่า
                        </button>
                    </li>
                </ul>

                <!-- Filters -->
                <div class="row mt-3 mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="tempFilter" class="form-label">สถานะ</label>
                                        <select class="form-select" id="tempFilter">
                                            <option value="">สถานะทั้งหมด</option>
                                            <option value="hot">🔥 Hot</option>
                                            <option value="warm">🌤️ Warm</option>
                                            <option value="cold">❄️ Cold</option>
                                            <option value="frozen">🧊 Frozen</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gradeFilter" class="form-label">เกรด</label>
                                        <select class="form-select" id="gradeFilter">
                                            <option value="">เกรดทั้งหมด</option>
                                            <option value="A+">A+</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="provinceFilter" class="form-label">จังหวัด</label>
                                        <select class="form-select" id="provinceFilter">
                                            <option value="">จังหวัดทั้งหมด</option>
                                            <?php foreach ($provinces as $province): ?>
                                            <option value="<?php echo htmlspecialchars($province['province']); ?>">
                                                <?php echo htmlspecialchars($province['province']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                                <i class="fas fa-filter me-1"></i>กรอง
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                                <i class="fas fa-times me-1"></i>ล้าง
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content" id="customerTabContent">
                    <!-- Do Tab (Telesales only) -->
                    <?php if ($roleName === 'telesales'): ?>
                    <div class="tab-pane fade show active" id="do" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tasks me-2"></i>สิ่งที่ต้องทำวันนี้
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($followUpCustomers)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <h5>ไม่มีงานที่ต้องทำ</h5>
                                    <p class="text-muted">คุณได้ทำงานเสร็จเรียบร้อยแล้ว</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ลูกค้า</th>
                                                <th>เบอร์โทร</th>
                                                <th>จังหวัด</th>
                                                <th>สถานะ</th>
                                                <th>เวลาที่เหลือ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($followUpCustomers as $customer): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($customer['customer_code'] ?? ''); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($customer['province'] ?? ''); ?></td>
                                                <td>
                                                    <?php
                                                    $tempIcons = [
                                                        'hot' => '🔥',
                                                        'warm' => '🌤️',
                                                        'cold' => '❄️',
                                                        'frozen' => '🧊'
                                                    ];
                                                    echo $tempIcons[$customer['temperature_status']] ?? '❓';
                                                    ?>
                                                    <?php echo ucfirst($customer['temperature_status']); ?>
                                                </td>
                                                <td>
                                                    <?php if ($customer['days_remaining'] <= 0): ?>
                                                    <span class="badge bg-danger">เกินกำหนด</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning"><?php echo $customer['days_remaining']; ?> วัน</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewCustomer(<?php echo $customer['customer_id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i>ดู
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="logCall(<?php echo $customer['customer_id']; ?>)">
                                                        <i class="fas fa-phone me-1"></i>โทร
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- New Customers Tab -->
                    <div class="tab-pane fade <?php echo ($roleName !== 'telesales') ? 'show active' : ''; ?>" id="new" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-plus me-2"></i>ลูกค้าใหม่
                                </h5>
                                <?php if (in_array($roleName, ['supervisor', 'admin', 'super_admin'])): ?>
                                <div>
                                    <button class="btn btn-primary btn-sm" onclick="showAssignModal()">
                                        <i class="fas fa-user-plus me-1"></i>มอบหมายลูกค้า
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div id="newCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Follow-up Tab -->
                    <div class="tab-pane fade" id="followup" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>ลูกค้าที่ต้องติดตาม
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="followupCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Customers Tab -->
                    <div class="tab-pane fade" id="existing" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>ลูกค้าเก่า
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="existingCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Assign Customers Modal -->
    <?php if (in_array($roleName, ['supervisor', 'admin', 'super_admin'])): ?>
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">มอบหมายลูกค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="telesalesSelect" class="form-label">เลือก Telesales</label>
                            <select class="form-select" id="telesalesSelect">
                                <option value="">เลือก Telesales</option>
                                <?php foreach ($telesalesList as $telesales): ?>
                                <option value="<?php echo $telesales['user_id']; ?>">
                                    <?php echo htmlspecialchars($telesales['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ลูกค้าที่เลือก</label>
                            <div id="selectedCustomers" class="border rounded p-2" style="min-height: 100px;">
                                <p class="text-muted">เลือกลูกค้าจากตารางด้านล่าง</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="availableCustomersTable">
                            <!-- Available customers will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="assignCustomers()">มอบหมาย</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                        <input type="hidden" id="callCustomerId">
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
        // Set user role for JavaScript
        window.currentUserRole = '<?php echo $_SESSION["role_name"] ?? ""; ?>';
    </script>
    <script src="assets/js/customers.js"></script>
</body>
</html> 