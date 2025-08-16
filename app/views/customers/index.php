<?php
// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (!isset($customers)) $customers = [];
if (!isset($followUpCustomers)) $followUpCustomers = [];
if (!isset($telesalesList)) $telesalesList = [];
if (!isset($provinces)) $provinces = [];

$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? '';
?>

<!-- Main Content (content wrapper only; grid handled by layout) -->
<div class="page-transition customer-page">
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
                    <?php if ($roleName === 'telesales' || $roleName === 'supervisor'): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="do-tab" data-bs-toggle="tab" data-bs-target="#do" type="button" role="tab">
                            <i class="fas fa-tasks me-1"></i>Do
                            <span class="badge bg-danger ms-1"><?php echo count($followUpCustomers); ?></span>
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($roleName !== 'telesales' && $roleName !== 'supervisor') ? 'active' : ''; ?>" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab">
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                            <i class="fas fa-users me-1"></i>ลูกค้าทั้งหมด
                            <span class="badge bg-info ms-1" id="allCustomersCount">0</span>
                        </button>
                    </li>
                </ul>

                <!-- Global filters removed; each tab now has its own compact header filters -->

                <!-- Tab Content -->
                <div class="tab-content" id="customerTabContent">
                    <!-- Do Tab (Telesales only) -->
                    <?php if ($roleName === 'telesales' || $roleName === 'supervisor'): ?>
                    <div class="tab-pane fade show active" id="do" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tasks me-2"></i>สิ่งที่ต้องทำวันนี้
                                </h5>
                                <form class="d-flex gap-2 flex-wrap" onsubmit="event.preventDefault(); applyFilters();">
                                    <input type="text" class="form-control form-control-sm" style="width: 160px;" id="nameFilter_do" placeholder="ชื่อลูกค้า">
                                    <input type="text" class="form-control form-control-sm" style="width: 140px;" id="phoneFilter_do" placeholder="เบอร์โทร">
                                    <select class="form-select form-select-sm" style="width: 120px;" id="tempFilter_do">
                                        <option value="">สถานะ</option>
                                        <option value="hot">Hot</option>
                                        <option value="warm">Warm</option>
                                        <option value="cold">Cold</option>
                                        <option value="frozen">Frozen</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 100px;" id="gradeFilter_do">
                                        <option value="">เกรด</option>
                                        <option value="A+">A+</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 140px;" id="provinceFilter_do">
                                        <option value="">จังหวัด</option>
                                        <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo htmlspecialchars($province['province']); ?>"><?php echo htmlspecialchars($province['province']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearTabFilters('do')">
                                        <i class="fas fa-times"></i> ล้าง
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($followUpCustomers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="doTable">
                                        <thead class="table-dark">
                                            <tr
                                                data-name="<?php echo htmlspecialchars(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))); ?>"
                                                data-phone="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                                                data-province="<?php echo htmlspecialchars($customer['province'] ?? ''); ?>"
                                                data-temp="<?php echo htmlspecialchars($customer['temperature_status'] ?? ''); ?>"
                                                data-grade="<?php echo htmlspecialchars($customer['customer_grade'] ?? ''); ?>"
                                                data-next="<?php echo htmlspecialchars($customer['next_followup_at'] ?? ''); ?>"
                                                data-created="<?php echo htmlspecialchars($customer['created_at'] ?? ''); ?>"
                                                data-is-new="<?php echo (($customer['customer_status'] ?? '') === 'new') ? '1' : '0'; ?>"
                                            >
                                                <th>ลูกค้า</th>
                                                <th>เบอร์โทร</th>
                                                <th>จังหวัด</th>
                                                <th>สถานะ</th>
                                                <th>หมายเหตุ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($followUpCustomers as $customer): ?>
                                            <tr 
                                                data-name="<?php echo htmlspecialchars(trim($customer['first_name'] . ' ' . $customer['last_name'])); ?>"
                                                data-phone="<?php echo htmlspecialchars($customer['phone']); ?>"
                                                data-province="<?php echo htmlspecialchars($customer['province']); ?>"
                                                data-temp="<?php echo htmlspecialchars($customer['temperature_status']); ?>"
                                                data-grade="<?php echo htmlspecialchars($customer['customer_grade'] ?? ''); ?>"
                                                data-next="<?php echo htmlspecialchars($customer['next_followup_at'] ?? ''); ?>"
                                                data-created="<?php echo htmlspecialchars($customer['created_at'] ?? ''); ?>"
                                                data-is-new="<?php echo (($customer['customer_status'] ?? '') === 'new') ? '1' : '0'; ?>"
                                            >
                                                <td>
                                                    <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($customer['customer_code']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['province']); ?></td>
                                                <td>
                                                    <?php 
                                                        $statusIcon = '';
                                                        $statusText = ucfirst(htmlspecialchars($customer['temperature_status']));
                                                        switch($customer['temperature_status']) {
                                                            case 'hot': $statusIcon = '🔥'; break;
                                                            case 'warm': $statusIcon = '🌤️'; break;
                                                            case 'cold': $statusIcon = '❄️'; break;
                                                            case 'frozen': $statusIcon = '🧊'; break;
                                                            default: $statusIcon = '❓';
                                                        }
                                                        echo $statusIcon . ' ' . $statusText;
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        if (!empty($customer['next_followup_at'])) {
                                                            echo '<div><strong>ลูกค้าต้องติดตาม</strong></div>';
                                                            echo '<div class="text-muted">' . date('d/m/Y', strtotime($customer['next_followup_at'])) . '</div>';
                                                        } elseif (($customer['customer_status'] ?? '') === 'new') {
                                                            echo '<div><strong>ลูกค้าแจกใหม่</strong></div>';
                                                            echo '<div class="text-muted">&nbsp;</div>';
                                                        } elseif (!empty($customer['customer_time_expiry'])) {
                                                            echo '<div><strong>ติดตามก่อนหมดอายุ</strong></div>';
                                                            echo '<div class="text-muted">' . date('d/m/Y', strtotime($customer['customer_time_expiry'])) . '</div>';
                                                        } else {
                                                            echo '<div class="text-muted">-</div>';
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" onclick="viewCustomer(<?php echo $customer['customer_id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i>ดู
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" onclick="logCall(<?php echo $customer['customer_id']; ?>)">
                                                        <i class="fas fa-phone me-1"></i>โทร
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                                                    <div class="d-flex justify-content-end mt-3">
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="doTable-pagination"></ul>
                                    </nav>
                                </div>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                    <p class="mt-2">ไม่มีงานที่ต้องทำวันนี้</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- New Customers Tab -->
                    <div class="tab-pane fade <?php echo ($roleName !== 'telesales' && $roleName !== 'supervisor') ? 'show active' : ''; ?>" id="new" role="tabpanel">
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
                                <form class="d-flex gap-2 flex-wrap ms-auto" onsubmit="event.preventDefault(); applyFilters();">
                                    <input type="text" class="form-control form-control-sm" style="width: 160px;" id="nameFilter_new" placeholder="ชื่อลูกค้า">
                                    <input type="text" class="form-control form-control-sm" style="width: 140px;" id="phoneFilter_new" placeholder="เบอร์โทร">
                                    <select class="form-select form-select-sm" style="width: 120px;" id="tempFilter_new">
                                        <option value="">สถานะ</option>
                                        <option value="hot">Hot</option>
                                        <option value="warm">Warm</option>
                                        <option value="cold">Cold</option>
                                        <option value="frozen">Frozen</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 100px;" id="gradeFilter_new">
                                        <option value="">เกรด</option>
                                        <option value="A+">A+</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 140px;" id="provinceFilter_new">
                                        <option value="">จังหวัด</option>
                                        <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo htmlspecialchars($province['province']); ?>"><?php echo htmlspecialchars($province['province']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearTabFilters('new')">
                                        <i class="fas fa-times"></i> ล้าง
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div id="newCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="newCustomersTable-pagination"></ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Follow-up Tab -->
                    <div class="tab-pane fade" id="followup" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>ลูกค้าที่ต้องติดตาม
                                </h5>
                                <form class="d-flex gap-2 flex-wrap ms-auto" onsubmit="event.preventDefault(); applyFilters();">
                                    <input type="text" class="form-control form-control-sm" style="width: 160px;" id="nameFilter_followup" placeholder="ชื่อลูกค้า">
                                    <input type="text" class="form-control form-control-sm" style="width: 140px;" id="phoneFilter_followup" placeholder="เบอร์โทร">
                                    <select class="form-select form-select-sm" style="width: 120px;" id="tempFilter_followup">
                                        <option value="">สถานะ</option>
                                        <option value="hot">Hot</option>
                                        <option value="warm">Warm</option>
                                        <option value="cold">Cold</option>
                                        <option value="frozen">Frozen</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 100px;" id="gradeFilter_followup">
                                        <option value="">เกรด</option>
                                        <option value="A+">A+</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 140px;" id="provinceFilter_followup">
                                        <option value="">จังหวัด</option>
                                        <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo htmlspecialchars($province['province']); ?>"><?php echo htmlspecialchars($province['province']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearTabFilters('followup')">
                                        <i class="fas fa-times"></i> ล้าง
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div id="followupCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="followupCustomersTable-pagination"></ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Customers Tab -->
                    <div class="tab-pane fade" id="existing" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>ลูกค้าเก่า
                                </h5>
                                <form class="d-flex gap-2 flex-wrap ms-auto" onsubmit="event.preventDefault(); applyFilters();">
                                    <input type="text" class="form-control form-control-sm" style="width: 160px;" id="nameFilter_existing" placeholder="ชื่อลูกค้า">
                                    <input type="text" class="form-control form-control-sm" style="width: 140px;" id="phoneFilter_existing" placeholder="เบอร์โทร">
                                    <select class="form-select form-select-sm" style="width: 120px;" id="tempFilter_existing">
                                        <option value="">สถานะ</option>
                                        <option value="hot">Hot</option>
                                        <option value="warm">Warm</option>
                                        <option value="cold">Cold</option>
                                        <option value="frozen">Frozen</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 100px;" id="gradeFilter_existing">
                                        <option value="">เกรด</option>
                                        <option value="A+">A+</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                    <select class="form-select form-select-sm" style="width: 140px;" id="provinceFilter_existing">
                                        <option value="">จังหวัด</option>
                                        <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo htmlspecialchars($province['province']); ?>"><?php echo htmlspecialchars($province['province']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearTabFilters('existing')">
                                        <i class="fas fa-times"></i> ล้าง
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div id="existingCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="existingCustomersTable-pagination"></ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Customers Tab -->
                    <div class="tab-pane fade" id="all" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>ลูกค้าทั้งหมด
                                    </h5>
                                    
                                    <!-- Advanced Filters -->
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <!-- Tag Filter -->
                                        <button class="btn btn-sm btn-outline-primary" type="button" onclick="showTagFilterModal()">
                                            <i class="fas fa-tags me-1"></i>Tags
                                            <span class="badge bg-primary ms-1" id="selectedTagsCount">0</span>
                                        </button>
                                        
                                        <!-- Customer Type Filter -->
                                        <select class="form-select form-select-sm" style="width: 120px;" id="customerTypeFilter_all">
                                            <option value="">ประเภทลูกค้า</option>
                                            <option value="new">ลูกค้าใหม่</option>
                                            <option value="existing">ลูกค้าเก่า</option>
                                            <option value="followup">ติดตาม</option>
                                        </select>
                                        
                                        <!-- Basic Filters -->
                                        <input type="text" class="form-control form-control-sm" style="width: 160px;" id="nameFilter_all" placeholder="ชื่อลูกค้า">
                                        <input type="text" class="form-control form-control-sm" style="width: 140px;" id="phoneFilter_all" placeholder="เบอร์โทร">
                                        
                                        <select class="form-select form-select-sm" style="width: 120px;" id="temperatureFilter_all">
                                            <option value="">สถานะ</option>
                                            <option value="hot">Hot</option>
                                            <option value="warm">Warm</option>
                                            <option value="cold">Cold</option>
                                            <option value="frozen">Frozen</option>
                                        </select>
                                        
                                        <select class="form-select form-select-sm" style="width: 100px;" id="gradeFilter_all">
                                            <option value="">เกรด</option>
                                            <option value="A+">A+</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                        
                                        <select class="form-select form-select-sm" style="width: 140px;" id="provinceFilter_all">
                                            <option value="">จังหวัด</option>
                                            <?php foreach ($provinces as $province): ?>
                                            <option value="<?php echo htmlspecialchars($province['province']); ?>"><?php echo htmlspecialchars($province['province']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllFilters()">
                                            <i class="fas fa-times"></i> ล้าง
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Action Bar -->
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="hideCalledToday">
                                            <label class="form-check-label" for="hideCalledToday">
                                                ซ่อนลูกค้าที่โทรแล้ววันนี้
                                            </label>
                                        </div>
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hideDateRange">
                                                <label class="form-check-label" for="hideDateRange">
                                                    ซ่อนลูกค้าที่โทรแล้วระหว่าง
                                                </label>
                                            </div>
                                            <input type="date" class="form-control form-control-sm" id="hideDateFrom" style="width: 130px;" disabled>
                                            <span class="text-muted">ถึง</span>
                                            <input type="date" class="form-control form-control-sm" id="hideDateTo" style="width: 130px;" disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="btn-group" style="display: none;" id="bulkActions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="showBulkTagModal()">
                                            <i class="fas fa-tags me-1"></i>เพิ่ม Tags
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="showBulkRemoveTagModal()">
                                            <i class="fas fa-minus me-1"></i>ลบ Tags
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div id="allCustomersTable">
                                    <!-- Customer table will be loaded here -->
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="allCustomersTable-pagination"></ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Call Management Tab -->

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
                                <option value="รับสาย">รับสาย</option>
                                <option value="ไม่รับสาย">ไม่รับสาย</option>
                                <option value="สายไม่ว่าง">สายไม่ว่าง</option>
                                <option value="ตัดสายทิ้ง">ตัดสายทิ้ง</option>
                                <option value="ติดต่อไม่ได้">ติดต่อไม่ได้</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="callResult" class="form-label">ผลการโทร</label>
                            <select class="form-select" id="callResult">
                                <option value="">เลือกผลการโทร</option>
                                <option value="สั่งซื้อ">สั่งซื้อ</option>
                                <option value="สนใจ">สนใจ</option>
                                <option value="Add Line แล้ว">Add Line แล้ว</option>
                                <option value="ต้องการซื้อทางเพจ">ต้องการซื้อทางเพจ</option>
                                <option value="น้ำท่วม">น้ำท่วม</option>
                                <option value="รอติดต่อใหม่">รอติดต่อใหม่</option>
                                <option value="นัดหมาย">นัดหมาย</option>
                                <option value="เบอร์ไม่ถูก">เบอร์ไม่ถูก</option>
                                <option value="ไม่สะดวกคุย">ไม่สะดวกคุย</option>
                                <option value="ไม่สนใจ">ไม่สนใจ</option>
                                <option value="อย่าโทรมาอีก">อย่าโทรมาอีก</option>
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
                            <label for="callTags" class="form-label">เพิ่ม Tag</label>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showAddTagModalFromCall()">
                                    <i class="fas fa-plus"></i> เพิ่ม Tag
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Reserved for future use -->
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

<script>
    // Set user role for JavaScript
    window.currentUserRole = '<?php echo $_SESSION["role_name"] ?? ""; ?>';
</script>