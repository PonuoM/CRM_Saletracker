
<?php
/**
 * Admin Customer Distribution
 * ระบบแจกลูกค้า
 */
?>

<?php 
// Company selector (เฉพาะ super_admin เท่านั้น)
$roleName = $_SESSION['role_name'] ?? '';
if ($roleName === 'super_admin'): 
    require_once __DIR__ . '/../../core/Database.php';
    $db = new Database();
    try {
        $companies = $db->fetchAll("SELECT company_id, company_name FROM companies WHERE is_active = 1 ORDER BY company_name");
    } catch (Exception $e) { $companies = []; }
    $currentCompany = $_SESSION['override_company_id'] ?? ($_SESSION['company_id'] ?? null);
?>
<form method="get" class="mt-2 px-3">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <label class="col-form-label"><i class="fas fa-building me-1"></i>บริษัท</label>
        </div>
        <div class="col-auto">
            <select class="form-select" name="company_override_id" onchange="this.form.submit()">
                <option value="">เลือกบริษัท...</option>
                <?php foreach ($companies as $co): ?>
                    <option value="<?php echo (int)$co['company_id']; ?>" <?php echo ($currentCompany == $co['company_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($co['company_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <input type="hidden" name="action" value="customer_distribution">
    <!-- preserve other query params if any -->
</form>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">
            <i class="fas fa-share-alt me-2"></i>
            ระบบแจกลูกค้า
        </h1>
        <p class="text-muted mb-0">จัดการการแจกลูกค้าให้กับ Telesales แบบเฉลี่ยและตามคำขอ</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button class="btn btn-sm btn-outline-primary" onclick="refreshDistributionStats()">
                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
            </button>
        </div>
    </div>
</div>

<!-- แท็บเลือกประเภทการแจก -->
<ul class="nav nav-tabs mb-4" id="distributionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="average-tab" data-bs-toggle="tab" data-bs-target="#average" type="button" role="tab">
            <i class="fas fa-balance-scale me-2"></i>การแจกแบบเฉลี่ย
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="request-tab" data-bs-toggle="tab" data-bs-target="#request" type="button" role="tab">
            <i class="fas fa-hand-paper me-2"></i>การแจกตามคำขอ
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="grade-a-tab" data-bs-toggle="tab" data-bs-target="#grade-a" type="button" role="tab">
            <i class="fas fa-star me-2"></i>การแจกเกรด A
        </button>
    </li>
</ul>

<div class="tab-content" id="distributionTabContent">
    <!-- Tab 1: การแจกแบบเฉลี่ย -->
    <div class="tab-pane fade show active" id="average" role="tabpanel">
        <!-- Distribution Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    ลูกค้าใน Distribution
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="distributionCount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Telesales ที่พร้อมรับงาน
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="availableTelesalesCount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    ลูกค้า Hot
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="hotCustomersCount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-fire fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    ลูกค้าถูกดึงกลับ (รอเวลา)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="waitingCustomersCount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Distribution Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-balance-scale me-2"></i>
                            แจกลูกค้าแบบเฉลี่ย
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="averageDistributionForm">
                            <!-- วันที่และปุ่มควบคุม -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="averageDateFrom" class="form-label">วันที่เริ่มต้น</label>
                                    <input type="date" class="form-control" id="averageDateFrom">
                                </div>
                                <div class="col-md-3">
                                    <label for="averageDateTo" class="form-label">วันที่สิ้นสุด</label>
                                    <input type="date" class="form-control" id="averageDateTo">
                                </div>
                                <div class="col-md-3">
                                    <label for="averageQuantity" class="form-label">จำนวนลูกค้าที่ต้องการ</label>
                                    <input type="number" class="form-control" id="averageQuantity" 
                                           min="1" max="0" value="0" required>
                                    <small class="form-text text-muted" id="maxQuantityText">
                                        กรุณาเลือกวันที่เพื่อดูจำนวนลูกค้าที่พร้อมแจก
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success" id="submitAverageBtn" disabled>
                                            <i class="fas fa-balance-scale me-1"></i>แจกแบบเฉลี่ย
                                        </button>
                                        <small class="text-muted text-center" id="validationMessage">
                                            กรุณากรอกข้อมูลให้ครบถ้วน
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- สถานะหลังแจก -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="averagePostStatus" class="form-label">สถานะหลังแจก</label>
                                    <select class="form-select" id="averagePostStatus" required>
                                        <option value="">— เลือกสถานะ —</option>
                                        <option value="new_customer">ลูกค้าใหม่</option>
                                        <option value="existing">ลูกค้าเก่า</option>
                                        <option value="daily_distribution">ลูกค้าแจกรายวัน</option>
                                    </select>
                                    <small class="form-text text-muted">ต้องระบุทุกครั้งก่อนกดแจก</small>
                                </div>
                            </div>
                            
                            <!-- ข้อความแจ้งเตือนจำนวนลูกค้าและปุ่มล้างฟอร์ม (แถวเดียว) -->
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="text-info" id="customerCountAlert" style="display: none;">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <span id="customerCountMessage"></span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearAverageForm()">
                                        <i class="fas fa-eraser me-1"></i>ล้างฟอร์ม
                                    </button>
                                    <button type="button" class="btn btn-outline-primary ms-2" onclick="manualRefresh()">
                                        <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                                    </button>
                                </div>
                            </div>
                            
                            <!-- ตารางพนักงาน -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="telesalesTable">
                                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" id="selectAllTelesales" class="form-check-input" style="transform: scale(0.8);">
                                        <label for="selectAllTelesales" class="form-check-label ms-1 small">All</label>
                                    </th>
                                    <th width="25%">ชื่อพนักงาน</th>
                                    <th width="20%">ลูกค้าที่ถืออยู่ในมือ</th>
                                    <th width="8%" class="text-center">A+</th>
                                    <th width="8%" class="text-center">A</th>
                                    <th width="8%" class="text-center">B</th>
                                    <th width="8%" class="text-center">C</th>
                                    <th width="8%" class="text-center">D</th>
                                </tr>
                            </thead>
                                            <tbody id="telesalesTableBody">
                                                <tr>
                                                    <td colspan="8" class="text-center">
                                                        <i class="fas fa-spinner fa-spin me-2"></i>กำลังโหลดข้อมูลพนักงาน...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: การแจกตามคำขอ -->
    <div class="tab-pane fade" id="request" role="tabpanel">
        <!-- Distribution Controls -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-random me-2"></i>
                            แจกลูกค้าตามคำขอ
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="distributionForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="distributionQuantity" class="form-label">จำนวนลูกค้าที่ต้องการ</label>
                                    <input type="number" class="form-control" id="distributionQuantity" 
                                           min="1" max="100" value="10" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="distributionPriority" class="form-label">ลำดับความสำคัญ</label>
                                    <select class="form-select" id="distributionPriority" required>
                                        <option value="hot_warm_cold">🔥 Hot → 🌤️ Warm → ❄️ Cold</option>
                                        <option value="hot_only">🔥 Hot เท่านั้น</option>
                                        <option value="warm_only">🌤️ Warm เท่านั้น</option>
                                        <option value="cold_only">❄️ Cold เท่านั้น</option>
                                        <option value="random">สุ่มทั้งหมด</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="distributionTelesales" class="form-label">Telesales ที่เลือก</label>
                                    <select class="form-select" id="distributionTelesales" multiple required>
                                        <option value="">กำลังโหลด...</option>
                                    </select>
                                    <small class="form-text text-muted">กด Ctrl+Click เพื่อเลือกหลายคน</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-random me-1"></i>แจกลูกค้า
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="clearFormBtn">
                                            <i class="fas fa-eraser me-1"></i>ล้างฟอร์ม
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

                <!-- Distribution Results -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    ผลการแจกลูกค้า
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="distributionResults">
                                    <div class="text-center py-4">
                                        <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                                        <h5>ยังไม่มีการแจกลูกค้า</h5>
                                        <p class="text-muted">กรุณาเลือกจำนวนและ Telesales แล้วกดแจกลูกค้า</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Customers Preview -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-eye me-2"></i>
                                    ตัวอย่างลูกค้าที่พร้อมแจก
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="availableCustomersPreview">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                        <span class="ms-2">กำลังโหลดข้อมูล...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    </div>

    <!-- Tab 3: การแจกเกรด A -->
    <div class="tab-pane fade" id="grade-a" role="tabpanel">
        <!-- Grade A Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    ลูกค้าเกรด A+
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="gradeAPlusCount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-star fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    ลูกค้าเกรด A
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="gradeACount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-star fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade A Distribution Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-star me-2"></i>
                            แจกลูกค้าเกรด A
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="gradeADistributionForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="gradeATelesales" class="form-label">Telesales ที่เลือก</label>
                                    <select class="form-select" id="gradeATelesales" multiple required>
                                        <option value="">กำลังโหลด...</option>
                                    </select>
                                    <small class="form-text text-muted">กด Ctrl+Click เพื่อเลือกหลายคน</small>
                                </div>
                                <div class="col-md-3">
                                    <label for="gradeACount" class="form-label">จำนวนลูกค้าต่อคน</label>
                                    <input type="number" class="form-control" id="gradeACount" 
                                           min="1" max="50" value="10" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="gradeASelection" class="form-label">เกรดที่เลือก</label>
                                    <select class="form-select" id="gradeASelection" multiple>
                                        <option value="A+" selected>เกรด A+</option>
                                        <option value="A" selected>เกรด A</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-star me-1"></i>แจกเกรด A
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearGradeAForm()">
                                            <i class="fas fa-eraser me-1"></i>ล้างฟอร์ม
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize customer distribution page
function initCustomerDistribution() {
    // Load initial data
    loadDistributionStats();
    loadAvailableCustomers();
    loadTelesalesList();
    loadGradeAStats();
    
    // ตรวจสอบเงื่อนไขเริ่มต้น
    setTimeout(validateAverageForm, 1000);

    // Auto refresh every 30 seconds
    setInterval(loadDistributionStats, 30000);
}

// Initialize when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomerDistribution);
} else {
    initCustomerDistribution();
}

function setupSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAllTelesales');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const telesalesCheckboxes = document.querySelectorAll('.telesales-checkbox');
            telesalesCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            // ตรวจสอบเงื่อนไขหลังจากเปลี่ยน checkbox
            validateAverageForm();
        });
    }
}

// Setup form event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Average distribution form
    const averageForm = document.getElementById('averageDistributionForm');
    if (averageForm) {
        averageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const quantity = document.getElementById('averageQuantity').value;
            const dateFrom = document.getElementById('averageDateFrom').value;
            const dateTo = document.getElementById('averageDateTo').value;
            const todayStr = new Date().toISOString().slice(0,10);
            // ถ้าไม่เลือกวันที่สิ้นสุด ให้ใช้วันนี้เป็นค่าเริ่มต้น และถ้าเลือกอนาคต ให้จำกัดไม่เกินวันนี้
            const effectiveDateTo = (dateTo && dateTo <= todayStr) ? dateTo : todayStr;
            const postStatus = document.getElementById('averagePostStatus').value;
            
            // ดึง telesales ที่เลือกจากตาราง
            const selectedTelesales = Array.from(document.querySelectorAll('.telesales-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            // ตรวจสอบเงื่อนไขต่างๆ
            if (!quantity || quantity <= 0) {
                showAlert('กรุณาระบุจำนวนลูกค้าที่ต้องการแจก (ต้องมากกว่า 0)', 'error');
                return;
            }
            
            if (selectedTelesales.length === 0) {
                showAlert('กรุณาเลือกพนักงานที่ต้องการแจกลูกค้าให้ (ต้องเลือกอย่างน้อย 1 คน)', 'error');
                return;
            }
            
            if (!dateFrom) {
                showAlert('กรุณาเลือกวันที่เริ่มต้น', 'error');
                return;
            }
            
            if (new Date(dateFrom) > new Date(effectiveDateTo)) {
                showAlert('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด', 'error');
                return;
            }
            
            if (!postStatus) {
                showAlert('กรุณาเลือกสถานะหลังแจก', 'error');
                return;
            }
            
            // Call distribution API
            distributeCustomersAverage(quantity, selectedTelesales, dateFrom, effectiveDateTo, postStatus);
        });
    }
    
    // Event listeners สำหรับการเปลี่ยนวันที่
    const dateFromInput = document.getElementById('averageDateFrom');
    const dateToInput = document.getElementById('averageDateTo');
    const quantityInput = document.getElementById('averageQuantity');
    const postStatusSelect = document.getElementById('averagePostStatus');
    
    if (dateFromInput && dateToInput) {
        [dateFromInput, dateToInput].forEach(input => {
            input.addEventListener('change', function() {
                updateAvailableCustomersCount();
                validateAverageForm();
            });
        });
    }
    
    // Event listener สำหรับจำนวนลูกค้า
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            validateAverageForm();
        });
    }
    if (postStatusSelect) {
        postStatusSelect.addEventListener('change', function() {
            validateAverageForm();
        });
    }
    
    // Event listener สำหรับ checkbox ในตาราง
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('telesales-checkbox') || e.target.id === 'selectAllTelesales') {
            validateAverageForm();
        }
    });

    // Request distribution form (การแจกตามคำขอ)
    const requestForm = document.getElementById('distributionForm');
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            bulkAssign(e);
        });
    }

    // Grade A distribution form
    const gradeAForm = document.getElementById('gradeADistributionForm');
    if (gradeAForm) {
        gradeAForm.addEventListener('submit', function(e) {
            e.preventDefault();
            distributeGradeA();
        });
    }

    // Tab change event
    const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target');
            if (targetId === '#grade-a') {
                loadGradeAStats();
            }
        });
    });
});

function loadDistributionStats() {
    console.log('Loading distribution stats...');
    // Call real API endpoint
    fetch('api/customer-distribution.php?action=stats')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Distribution stats response:', data);
            if (data.success) {
                const stats = data.data;

                // Update the correct element IDs that match the HTML
                const distributionEl = document.getElementById('distributionCount');
                const availableTelesalesEl = document.getElementById('availableTelesalesCount');
                const hotCustomersEl = document.getElementById('hotCustomersCount');
                const waitingCustomersEl = document.getElementById('waitingCustomersCount');
                const gradeAPlusEl = document.getElementById('gradeAPlusCount');
                const gradeAEl = document.getElementById('gradeACount');

                if (distributionEl) {
                    distributionEl.textContent = stats.distribution_count || 0;
                    console.log('Updated distribution count:', stats.distribution_count);
                }
                if (availableTelesalesEl) {
                    availableTelesalesEl.textContent = stats.available_telesales_count || 0;
                    console.log('Updated telesales count:', stats.available_telesales_count);
                }
                if (hotCustomersEl) {
                    hotCustomersEl.textContent = stats.hot_customers_count || 0;
                    console.log('Updated hot customers count:', stats.hot_customers_count);
                }
                if (waitingCustomersEl) {
                    waitingCustomersEl.textContent = stats.waiting_customers_count || 0;
                    console.log('Updated waiting customers count:', stats.waiting_customers_count);
                }
                if (gradeAPlusEl) {
                    gradeAPlusEl.textContent = stats.grade_a_plus_count || 0;
                    console.log('Updated grade A+ count:', stats.grade_a_plus_count);
                }
                if (gradeAEl) {
                    gradeAEl.textContent = stats.grade_a_count || 0;
                    console.log('Updated grade A count:', stats.grade_a_count);
                }

                // Add warm customers count if element exists
                const warmCustomersEl = document.getElementById('warmCustomersCount');
                if (warmCustomersEl) {
                    warmCustomersEl.textContent = stats.warm_customers_count || 0;
                    console.log('Updated warm customers count:', stats.warm_customers_count);
                }
            } else {
                console.error('Failed to load distribution stats:', data.message);
                showAlert('ไม่สามารถโหลดสถิติได้: ' + (data.message || 'ไม่ทราบสาเหตุ'), 'error');
            }
        })
        .catch(error => {
            console.error('Error loading distribution stats:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดสถิติ: ' + error.message, 'error');
        });
}

function loadGradeAStats() {
    fetch('api/customer-distribution.php?action=grade_a_stats&company=prima')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                const gradeAPlusEl = document.getElementById('gradeAPlusCount');
                const gradeAEl = document.getElementById('gradeACount');

                if (gradeAPlusEl) gradeAPlusEl.textContent = stats.grade_a_plus_count || 0;
                if (gradeAEl) gradeAEl.textContent = stats.grade_a_count || 0;
            } else {
                console.error('Failed to load grade A stats:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading grade A stats:', error);
        });
}

function loadAvailableCustomers() {
    const customersEl = document.getElementById('availableCustomersPreview');
    if (!customersEl) return;

    // Call real API endpoint
    fetch('api/customer-distribution.php?action=available_customers&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customers = data.data;

                if (customers.length === 0) {
                    customersEl.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าที่พร้อมแจกในขณะนี้</div>';
                    return;
                }

                let html = '<div class="list-group">';
                customers.forEach(customer => {
                    const tempStatus = customer.temperature_status || 'cold';
                    const gradeClass = tempStatus === 'hot' ? 'text-danger' :
                                      tempStatus === 'warm' ? 'text-warning' : 'text-info';
                    const gradeIcon = tempStatus === 'hot' ? 'fas fa-fire' :
                                     tempStatus === 'warm' ? 'fas fa-sun' : 'fas fa-snowflake';
                    const gradeName = tempStatus.charAt(0).toUpperCase() + tempStatus.slice(1);

                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${customer.first_name} ${customer.last_name}</strong>
                                <span class="badge bg-secondary ms-2">
                                    <i class="${gradeIcon} me-1"></i>${gradeName}
                                </span>
                                <br>
                                <small class="text-muted">${customer.phone || 'ไม่มีเบอร์โทร'}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary" onclick="assignCustomer(${customer.customer_id})">
                                    <i class="fas fa-user-plus"></i> มอบหมาย
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';

                customersEl.innerHTML = html;
            } else {
                console.error('Failed to load available customers:', data.message);
                customersEl.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดรายการลูกค้าได้</div>';
            }
        })
        .catch(error => {
            console.error('Error loading available customers:', error);
            customersEl.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดรายการลูกค้า</div>';
        });
}

function loadTelesalesList() {
    const selectEl = document.getElementById('distributionTelesales');
    const gradeASelectEl = document.getElementById('gradeATelesales');
    const telesalesTableBody = document.getElementById('telesalesTableBody');
    
    if (!selectEl && !gradeASelectEl && !telesalesTableBody) return;

    // Call real API endpoint
    fetch('api/customer-distribution.php?action=available_telesales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const telesales = data.data;

                if (telesales.length === 0) {
                    const noOption = '<option value="">ไม่มี Telesales ที่พร้อมรับงาน</option>';
                    if (selectEl) selectEl.innerHTML = noOption;
                    if (gradeASelectEl) gradeASelectEl.innerHTML = noOption;
                    if (telesalesTableBody) {
                        telesalesTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">ไม่มี Telesales ที่พร้อมรับงาน</td></tr>';
                    }
                    return;
                }

                // สำหรับ select elements
                let options = '';
                telesales.forEach(person => {
                    const customerCount = person.current_customers_count || 0;
                    options += `<option value="${person.user_id}">${person.full_name} (${customerCount} ลูกค้าที่กำลังติดตาม)</option>`;
                });

                if (selectEl) selectEl.innerHTML = options;
                if (gradeASelectEl) gradeASelectEl.innerHTML = options;

                // สำหรับตาราง
                if (telesalesTableBody) {
                    let tableRows = '';
                    telesales.forEach(person => {
                        const customerCount = person.current_customers_count || 0;
                        // Generate grade distribution info
                        const grades = person.grade_distribution || {};
                        const gradeAPlus = grades.A_plus || 0;
                        const gradeA = grades.A || 0;
                        const gradeB = grades.B || 0;
                        const gradeC = grades.C || 0;
                        const gradeD = grades.D || 0;
                        
                        tableRows += `
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input telesales-checkbox" 
                                           value="${person.user_id}" id="telesales_${person.user_id}" style="transform: scale(0.8);">
                                </td>
                                <td>
                                    <label for="telesales_${person.user_id}" class="form-check-label">
                                        <strong>${person.full_name}</strong>
                                    </label>
                                </td>
                                <td>
                                    <span class="badge bg-primary">${customerCount} ลูกค้า</span>
                                </td>
                                <td class="text-center">
                                    ${gradeAPlus}
                                </td>
                                <td class="text-center">
                                    ${gradeA}
                                </td>
                                <td class="text-center">
                                    ${gradeB}
                                </td>
                                <td class="text-center">
                                    ${gradeC}
                                </td>
                                <td class="text-center">
                                    ${gradeD}
                                </td>
                            </tr>
                        `;
                    });
                    telesalesTableBody.innerHTML = tableRows;
                    
                    // Setup checkbox functionality after table is loaded
                    setupSelectAllCheckbox();
                    
                    // ตรวจสอบเงื่อนไขหลังจากโหลดตารางเสร็จ
                    validateAverageForm();
                }
            } else {
                console.error('Failed to load telesales list:', data.message);
                const errorOption = '<option value="">ไม่สามารถโหลดรายการ Telesales ได้</option>';
                if (selectEl) selectEl.innerHTML = errorOption;
                if (gradeASelectEl) gradeASelectEl.innerHTML = errorOption;
                if (telesalesTableBody) {
                    telesalesTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">ไม่สามารถโหลดรายการ Telesales ได้</td></tr>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading telesales list:', error);
            const errorOption = '<option value="">เกิดข้อผิดพลาดในการโหลดรายการ Telesales</option>';
            if (selectEl) selectEl.innerHTML = errorOption;
            if (gradeASelectEl) gradeASelectEl.innerHTML = errorOption;
            if (telesalesTableBody) {
                telesalesTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดรายการ Telesales</td></tr>';
            }
        });
}

function assignCustomer(customerId) {
    const telesalesSelect = document.getElementById('distributionTelesales');
    const selectedOptions = telesalesSelect ? Array.from(telesalesSelect.selectedOptions) : [];

    if (selectedOptions.length === 0) {
        showAlert('กรุณาเลือก Telesales ก่อน', 'warning');
        return;
    }

    if (!confirm('คุณต้องการมอบหมายลูกค้านี้หรือไม่?')) {
        return;
    }

    // เรียกใช้ API จริง
    const telesalesIds = selectedOptions.map(option => parseInt(option.value));
    
    fetch('api/customer-distribution.php?action=distribute', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            quantity: 1,
            priority: 'hot_warm_cold',
            telesales_ids: telesalesIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('มอบหมายลูกค้าสำเร็จ', 'success');
            loadDistributionStats();
            loadAvailableCustomers();
            loadTelesalesList();
        } else {
            showAlert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    });
}

function bulkAssign(ev) {
    const form = (ev && ev.target && ev.target.tagName === 'FORM') ? ev.target : document.getElementById('distributionForm');
    const telesalesSelect = document.getElementById('distributionTelesales');
    const distributionQuantity = document.getElementById('distributionQuantity');
    const distributionPriority = document.getElementById('distributionPriority');

    const selectedOptions = telesalesSelect ? Array.from(telesalesSelect.selectedOptions) : [];
    const quantity = distributionQuantity ? parseInt(distributionQuantity.value) : 0;
    const priority = distributionPriority ? distributionPriority.value : 'hot_warm_cold';

    if (selectedOptions.length === 0) {
        showAlert('กรุณาเลือก Telesales ก่อน', 'warning');
        return;
    }

    if (!quantity || quantity < 1) {
        showAlert('กรุณาระบุจำนวนลูกค้า', 'warning');
        return;
    }

    if (!confirm(`คุณต้องการมอบหมายลูกค้า ${quantity} คนหรือไม่?`)) {
        return;
    }

    const button = form ? form.querySelector('button[type="submit"]') : null;
    const originalText = button ? button.innerHTML : '';
    if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังดำเนินการ...';
        button.disabled = true;
    }

    // เรียกใช้ API จริง
    const telesalesIds = selectedOptions.map(option => parseInt(option.value));
    
    fetch('api/customer-distribution.php?action=distribute', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            quantity: quantity,
            priority: priority,
            telesales_ids: telesalesIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`มอบหมายลูกค้า ${quantity} คนสำเร็จ`, 'success');
            loadDistributionStats();
            loadAvailableCustomers();
            loadTelesalesList();
        } else {
            showAlert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    })
    .finally(() => {
        if (button) {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Insert alert at the top of the page
    const borderBottom = document.querySelector('.border-bottom');
    if (borderBottom) {
        borderBottom.insertAdjacentHTML('afterend', alertHtml);
    }

    // Auto dismiss after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
}

function updateAvailableCustomersCount() {
    const dateFrom = document.getElementById('averageDateFrom').value;
    const dateTo = document.getElementById('averageDateTo').value;
    const postStatus = document.getElementById('averagePostStatus') ? document.getElementById('averagePostStatus').value : '';
    
    if (!dateFrom) {
        // ซ่อนข้อความแจ้งเตือนถ้าไม่มีวันที่เริ่มต้น
        const alertDiv = document.getElementById('customerCountAlert');
        if (alertDiv) { alertDiv.style.display = 'none'; }
        return;
    }
    const todayStr = new Date().toISOString().slice(0,10);
    const effectiveDateTo = (dateTo && dateTo <= todayStr) ? dateTo : todayStr;
    
    // เรียก API เพื่อดึงจำนวนลูกค้าที่พร้อมแจกตามวันที่
    fetch(`api/customer-distribution.php?action=available_customers_by_date&date_from=${dateFrom}&date_to=${effectiveDateTo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.count || 0;
                // อัปเดตจำนวนลูกค้าที่แสดงใน UI
                const quantityInput = document.getElementById('averageQuantity');
                const maxQuantityText = document.getElementById('maxQuantityText');
                if (quantityInput && maxQuantityText) {
                    quantityInput.max = count;
                    quantityInput.placeholder = `จำนวนลูกค้าที่พร้อมแจก: ${count} คน`;
                    
                    if (count > 0) {
                        maxQuantityText.textContent = `จำนวนลูกค้าสูงสุดที่พร้อมแจก: ${count} คน`;
                        maxQuantityText.className = 'form-text text-success';
                        
                        // ตั้งค่าเริ่มต้นเป็นจำนวนสูงสุด
                        if (!quantityInput.value || quantityInput.value > count) {
                            quantityInput.value = count;
                        }
                    } else {
                        maxQuantityText.textContent = 'ไม่มีลูกค้าที่พร้อมแจกในช่วงวันที่นี้';
                        maxQuantityText.className = 'form-text text-warning';
                        quantityInput.value = 0;
                    }
                    
                    // ตรวจสอบเงื่อนไขใหม่
                    validateAverageForm();
                }
                
                // แสดงข้อความแจ้งเตือนในแถวเดียวกับปุ่มล้างฟอร์ม
                const alertDiv = document.getElementById('customerCountAlert');
                const messageSpan = document.getElementById('customerCountMessage');
                if (alertDiv && messageSpan) {
                    messageSpan.textContent = `พบลูกค้าที่พร้อมแจก ${count} คน ในช่วงวันที่ ${dateFrom} ถึง ${effectiveDateTo}`;
                    alertDiv.style.display = 'block';
                }
            } else {
                console.error('Failed to load available customers count:', data.message);
                showAlert('ไม่สามารถโหลดจำนวนลูกค้าที่พร้อมแจกได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading available customers count:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดจำนวนลูกค้าที่พร้อมแจก', 'error');
        });
}

function clearAverageForm() {
    document.getElementById('averageDistributionForm').reset();
    
    // รีเซ็ตจำนวนลูกค้า
    const quantityInput = document.getElementById('averageQuantity');
    const maxQuantityText = document.getElementById('maxQuantityText');
    if (quantityInput && maxQuantityText) {
        quantityInput.max = 0;
        quantityInput.value = 0;
        maxQuantityText.textContent = 'กรุณาเลือกวันที่เพื่อดูจำนวนลูกค้าที่พร้อมแจก';
        maxQuantityText.className = 'form-text text-muted';
    }
    
    // ยกเลิกการเลือก checkbox ทั้งหมด
    const allCheckboxes = document.querySelectorAll('.telesales-checkbox');
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    const selectAllCheckbox = document.getElementById('selectAllTelesales');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
    
    // ซ่อนข้อความแจ้งเตือนจำนวนลูกค้า
    const alertDiv = document.getElementById('customerCountAlert');
    if (alertDiv) {
        alertDiv.style.display = 'none';
    }
    
    // ตรวจสอบเงื่อนไขใหม่
    validateAverageForm();
    
    showAlert('ล้างฟอร์มแจกแบบเฉลี่ยสำเร็จ', 'info');
}

function clearGradeAForm() {
    document.getElementById('gradeADistributionForm').reset();
    showAlert('ล้างฟอร์มแจกเกรด A สำเร็จ', 'info');
}

// ฟังก์ชันตรวจสอบเงื่อนไขแบบ real-time
function validateAverageForm() {
    const quantity = document.getElementById('averageQuantity').value;
    const dateFrom = document.getElementById('averageDateFrom').value;
    const dateTo = document.getElementById('averageDateTo').value;
    const postStatus = document.getElementById('averagePostStatus') ? document.getElementById('averagePostStatus').value : '';
    const selectedTelesales = Array.from(document.querySelectorAll('.telesales-checkbox:checked'));
    
    const submitBtn = document.getElementById('submitAverageBtn');
    const validationMsg = document.getElementById('validationMessage');
    
    let isValid = true;
    let message = '';
    
    // ตรวจสอบจำนวนลูกค้า
    if (!quantity || quantity <= 0) {
        isValid = false;
        message = 'กรุณาระบุจำนวนลูกค้าที่ต้องการแจก';
    }
    // ตรวจสอบวันที่ (ต้องมีอย่างน้อยวันที่เริ่มต้น)
    else if (!dateFrom) {
        isValid = false;
        message = 'กรุณาเลือกวันที่เริ่มต้น';
    }
    // ตรวจสอบวันที่เริ่มต้นไม่เกินวันที่สิ้นสุด (ถ้าไม่เลือกสิ้นสุดให้ใช้วันนี้)
    else if (new Date(dateFrom) > new Date(dateTo || new Date().toISOString().slice(0,10))) {
        isValid = false;
        message = 'วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด';
    }
    // ตรวจสอบการเลือกพนักงาน
    else if (selectedTelesales.length === 0) {
        isValid = false;
        message = 'กรุณาเลือกพนักงานที่ต้องการแจกลูกค้าให้';
    }
    else if (!postStatus) {
        isValid = false;
        message = 'กรุณาเลือกสถานะหลังแจก';
    }
    // ข้อมูลครบถ้วน
    else {
        message = `พร้อมแจกลูกค้า ${quantity} คน ให้ ${selectedTelesales.length} คน`;
    }
    
    // อัปเดตสถานะปุ่มและข้อความ
    submitBtn.disabled = !isValid;
    validationMsg.textContent = message;
    
    // เปลี่ยนสีข้อความตามสถานะ
    if (isValid) {
        validationMsg.className = 'text-success text-center small';
        submitBtn.className = 'btn btn-success';
    } else {
        validationMsg.className = 'text-danger text-center small';
        submitBtn.className = 'btn btn-secondary';
    }
}

// ฟังก์ชันแจกลูกค้าแบบเฉลี่ย
function distributeCustomersAverage(quantity, selectedTelesales, dateFrom, dateTo, postStatus) {
    // แสดง loading state
    const submitButton = document.querySelector('#averageDistributionForm button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังแจกลูกค้า...';
    submitButton.disabled = true;
    
    // เรียก API
    fetch('api/customer-distribution.php?action=distribute_average', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            company: '<?php echo $_SESSION['company_id'] ?? 'default'; ?>',
            customer_count: parseInt(quantity),
            telesales_ids: selectedTelesales.map(id => parseInt(id)),
            date_from: dateFrom,
            // ถ้าไม่เลือกวันที่สิ้นสุด ให้ใช้วันนี้ และจำกัดไม่เกินวันนี้
            date_to: (function(){ const todayStr = new Date().toISOString().slice(0,10); return (dateTo && dateTo <= todayStr) ? dateTo : todayStr; })(),
            post_status: postStatus
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // แสดง popup สำเร็จ และรีเฟรชข้อมูลแบบ in-place
            showDistributionSuccessPopup(data.data);
            loadDistributionStats();
            loadTelesalesList();
            loadAvailableCustomers();
            // อัปเดตจำนวนลูกค้าพร้อมแจกตามช่วงวันที่ที่เลือกใหม่อีกครั้ง
            updateAvailableCustomersCount();
        } else {
            // แสดง error
            showAlert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    })
    .finally(() => {
        // คืนสถานะปุ่ม
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// ฟังก์ชันรีเฟรชข้อมูลแบบครอบคลุม
function refreshAllData() {
    console.log('Refreshing all data...');
    try {
        loadDistributionStats();
        loadTelesalesList();
        loadAvailableCustomers();
        loadGradeAStats();
    } catch (error) {
        console.error('Error in refreshAllData:', error);
    }
}

// ฟังก์ชันรีเฟรชแบบ manual
function manualRefresh() {
    console.log('Manual refresh triggered');
    showAlert('กำลังรีเฟรชข้อมูล...', 'info');
    
    // รีเฟรชข้อมูลทั้งหมด
    refreshAllData();
}

// ฟังก์ชันแสดง popup ผลการแจกสำเร็จ
function showDistributionSuccessPopup(data) {
    const totalDistributed = data.total_distributed || 0;
    const telesalesCount = (data.telesales_count && data.telesales_count > 0)
        ? data.telesales_count
        : (Array.isArray(data.distributions) ? data.distributions.length : 0);
    const distributionDetails = data.distribution_details || [];
    
    let detailsHtml = '';
    if (distributionDetails.length > 0) {
        detailsHtml = '<div class="mt-3"><h6>รายละเอียดการแจก:</h6><ul class="list-unstyled">';
        distributionDetails.forEach(detail => {
            detailsHtml += `<li><strong>${detail.telesales_name}:</strong> ${detail.customer_count} ลูกค้า</li>`;
        });
        detailsHtml += '</ul></div>';
    }
    
    const popupHtml = `
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="successModalLabel">
                            <i class="fas fa-check-circle me-2"></i>แจกลูกค้าสำเร็จ
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-check-circle text-success fa-3x"></i>
                        </div>
                        <h5 class="text-center text-success">การแจกลูกค้าเสร็จสิ้น!</h5>
                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <h4 class="text-primary">${totalDistributed}</h4>
                                    <small class="text-muted">ลูกค้าทั้งหมด</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <h4 class="text-info">${telesalesCount}</h4>
                                    <small class="text-muted">พนักงานที่ได้รับ</small>
                                </div>
                            </div>
                        </div>
                        ${detailsHtml}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="handleDistributionSuccessOk()">
                            <i class="fas fa-check me-1"></i>ตกลง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่าถ้ามี
    const existingModal = document.getElementById('successModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', popupHtml);
    
    // แสดง modal
    const modalElement = document.getElementById('successModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // ตรวจสอบว่า modal แสดงขึ้นหรือไม่
        modalElement.addEventListener('shown.bs.modal', function() {
            console.log('Success modal displayed successfully');
        });
    } else {
        console.error('Modal element not found');
        showAlert('แจกลูกค้าสำเร็จ แต่ไม่สามารถแสดง popup ได้', 'success');
    }
    
    // ลบ modal หลังจากปิด และรีเฟรชข้อมูลแบบ in-place
    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
        // ลบ backdrop ที่อาจค้างอยู่และคืนค่า body
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        // เปลี่ยนเป็น redirect เพื่อป้องกันหน้าขาว
        window.location.replace('admin.php?action=customer_distribution');
    });
}

// ฟังก์ชันกดปุ่ม "ตกลง" ใน Success Modal ให้ redirect
function handleDistributionSuccessOk() {
    try {
        window.location.replace('admin.php?action=customer_distribution');
    } catch (e) {
        window.location.href = 'admin.php?action=customer_distribution';
    }
}
</script>
