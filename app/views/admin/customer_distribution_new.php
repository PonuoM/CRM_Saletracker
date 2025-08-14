<?php
/**
 * Admin Customer Distribution (New Version)
 * ระบบแจกลูกค้าแบบใหม่ - แยกบริษัทชัดเจน
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">
            <i class="fas fa-share-alt me-2"></i>
            ระบบแจกลูกค้า
        </h1>
        <p class="text-muted mb-0">จัดการการแจกลูกค้าให้กับ Telesales แบบเฉลี่ยและตามคำขอ (แยกตามบริษัท)</p>
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
</ul>

<div class="tab-content" id="distributionTabContent">
    <!-- แท็บการแจกแบบเฉลี่ย -->
    <div class="tab-pane fade show active" id="average" role="tabpanel">
        <!-- สถิติลูกค้าแยกตามบริษัท -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>สถิติลูกค้าแยกตามบริษัท
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Prima Stats -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-building me-2"></i>บริษัท พรีม่า (PRIMA)
                                </h6>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    ลูกค้าพร้อมแจก</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaDistributionCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Telesales</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaTelesalesCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Hot</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaHotCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Warm</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaWarmCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prionic Stats -->
                            <div class="col-md-6">
                                <h6 class="text-info mb-3">
                                    <i class="fas fa-building me-2"></i>บริษัท พรีออนิค (PRIONIC)
                                </h6>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    ลูกค้าพร้อมแจก</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicDistributionCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Telesales</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicTelesalesCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Hot</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicHotCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Warm</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicWarmCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ฟอร์มการแจกแบบเฉลี่ย -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-balance-scale me-2"></i>การแจกแบบเฉลี่ย - พรีม่า
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="averageDistributionFormPrima">
                            <div class="mb-3">
                                <label class="form-label">ช่วงวันที่ลูกค้าเข้าระบบ</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="primaDateFrom" name="date_from" value="<?php echo date('Y-m-d'); ?>" onchange="updateAvailableCustomers('prima')">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="primaDateTo" name="date_to" value="<?php echo date('Y-m-d'); ?>" onchange="updateAvailableCustomers('prima')">
                                    </div>
                                </div>
                                <small class="form-text text-muted">เลือกช่วงวันที่ลูกค้าเข้าระบบเพื่อไม่ให้ซ้ำกับรายชื่อเก่า</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลูกค้าพร้อมแจกในช่วงวันที่เลือก</label>
                                <div id="primaAvailableCustomers" class="form-control-plaintext text-primary fw-bold">กำลังโหลด...</div>
                                <small class="form-text text-muted">จำนวนลูกค้าที่มีในระบบตามช่วงวันที่ที่เลือก</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">จำนวนลูกค้าที่ต้องการแจก</label>
                                <input type="number" class="form-control" id="primaCustomerCount" min="1" placeholder="ใส่จำนวนหรือกดปุ่มใช้ทั้งหมด">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="useAllAvailableCustomers('prima')">
                                        <i class="fas fa-users me-1"></i>ใช้ลูกค้าทั้งหมดในช่วงวันที่
                                    </button>
                                </div>
                                <small class="form-text text-muted">รายชื่อใหม่จาก admin page ในแต่ละวัน</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">จำนวน Telesales ที่เลือก</label>
                                <div id="primaSelectedTelesalesCount" class="form-control-plaintext">0 คน</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลูกค้าต่อคน (เฉลี่ย)</label>
                                <div id="primaAveragePerPerson" class="form-control-plaintext">-</div>
                                <small class="form-text text-muted">เศษที่เหลือจะส่งให้คนที่ยอดขายเยอะที่สุดในเดือนนั้น</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เลือก Telesales (พรีม่า)</label>
                                <div id="primaTelesalesList" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="calculateAverageDistribution('prima')">
                                <i class="fas fa-calculator me-2"></i>คำนวณการแจก
                            </button>
                            <button type="button" class="btn btn-success ms-2" onclick="confirmAverageDistribution('prima')" disabled id="primaConfirmBtn">
                                <i class="fas fa-check me-2"></i>ยืนยันการแจก
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-balance-scale me-2"></i>การแจกแบบเฉลี่ย - พรีออนิค
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="averageDistributionFormPrionic">
                            <div class="mb-3">
                                <label class="form-label">ช่วงวันที่ลูกค้าเข้าระบบ</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="prionicDateFrom" name="date_from" value="<?php echo date('Y-m-d'); ?>" onchange="updateAvailableCustomers('prionic')">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="prionicDateTo" name="date_to" value="<?php echo date('Y-m-d'); ?>" onchange="updateAvailableCustomers('prionic')">
                                    </div>
                                </div>
                                <small class="form-text text-muted">เลือกช่วงวันที่ลูกค้าเข้าระบบเพื่อไม่ให้ซ้ำกับรายชื่อเก่า</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลูกค้าพร้อมแจกในช่วงวันที่เลือก</label>
                                <div id="prionicAvailableCustomers" class="form-control-plaintext text-primary fw-bold">กำลังโหลด...</div>
                                <small class="form-text text-muted">จำนวนลูกค้าที่มีในระบบตามช่วงวันที่ที่เลือก</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">จำนวนลูกค้าที่ต้องการแจก</label>
                                <input type="number" class="form-control" id="prionicCustomerCount" min="1" placeholder="ใส่จำนวนหรือกดปุ่มใช้ทั้งหมด">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="useAllAvailableCustomers('prionic')">
                                        <i class="fas fa-users me-1"></i>ใช้ลูกค้าทั้งหมดในช่วงวันที่
                                    </button>
                                </div>
                                <small class="form-text text-muted">รายชื่อใหม่จาก admin page ในแต่ละวัน</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">จำนวน Telesales ที่เลือก</label>
                                <div id="prionicSelectedTelesalesCount" class="form-control-plaintext">0 คน</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลูกค้าต่อคน (เฉลี่ย)</label>
                                <div id="prionicAveragePerPerson" class="form-control-plaintext">-</div>
                                <small class="form-text text-muted">เศษที่เหลือจะส่งให้คนที่ยอดขายเยอะที่สุดในเดือนนั้น</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เลือก Telesales (พรีออนิค)</label>
                                <div id="prionicTelesalesList" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="calculateAverageDistribution('prionic')">
                                <i class="fas fa-calculator me-2"></i>คำนวณการแจก
                            </button>
                            <button type="button" class="btn btn-success ms-2" onclick="confirmAverageDistribution('prionic')" disabled id="prionicConfirmBtn">
                                <i class="fas fa-check me-2"></i>ยืนยันการแจก
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- แท็บการแจกตามคำขอ -->
    <div class="tab-pane fade" id="request" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-hand-paper me-2"></i>การแจกตามคำขอ - พรีม่า
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>โควต้า:</strong> คนละ 300 รายชื่อ/สัปดาห์ (ไม่เกิน 150 รายชื่อต่อครั้ง)
                        </div>
                        <form id="requestDistributionFormPrima">
                            <div class="mb-3">
                                <label class="form-label">จำนวนลูกค้าที่ต้องการ</label>
                                <input type="number" class="form-control" id="primaRequestCount" min="1" max="150" placeholder="สูงสุด 150 รายชื่อ">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลำดับความสำคัญ</label>
                                <select class="form-select" id="primaRequestPriority">
                                    <option value="hot_warm_cold">🔥 Hot → 🌤️ Warm → ❄️ Cold</option>
                                    <option value="hot_only">🔥 Hot เท่านั้น</option>
                                    <option value="warm_only">🌤️ Warm เท่านั้น</option>
                                    <option value="cold_only">❄️ Cold เท่านั้น</option>
                                    <option value="stock_only">📦 สต๊อคเก่า (30+ วัน)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เลือก Telesales (พรีม่า)</label>
                                <select class="form-select" id="primaRequestTelesales">
                                    <option value="">กำลังโหลด...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">โควต้าคงเหลือ</label>
                                <div id="primaQuotaRemaining" class="form-control-plaintext">กำลังตรวจสอบ...</div>
                            </div>
                            <button type="button" class="btn btn-warning" onclick="requestDistribution('prima')">
                                <i class="fas fa-hand-paper me-2"></i>ขอรายชื่อ
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-hand-paper me-2"></i>การแจกตามคำขอ - พรีออนิค
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>โควต้า:</strong> คนละ 300 รายชื่อ/สัปดาห์ (ไม่เกิน 150 รายชื่อต่อครั้ง)
                        </div>
                        <form id="requestDistributionFormPrionic">
                            <div class="mb-3">
                                <label class="form-label">จำนวนลูกค้าที่ต้องการ</label>
                                <input type="number" class="form-control" id="prionicRequestCount" min="1" max="150" placeholder="สูงสุด 150 รายชื่อ">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลำดับความสำคัญ</label>
                                <select class="form-select" id="prionicRequestPriority">
                                    <option value="hot_warm_cold">🔥 Hot → 🌤️ Warm → ❄️ Cold</option>
                                    <option value="hot_only">🔥 Hot เท่านั้น</option>
                                    <option value="warm_only">🌤️ Warm เท่านั้น</option>
                                    <option value="cold_only">❄️ Cold เท่านั้น</option>
                                    <option value="stock_only">📦 สต๊อคเก่า (30+ วัน)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เลือก Telesales (พรีออนิค)</label>
                                <select class="form-select" id="prionicRequestTelesales">
                                    <option value="">กำลังโหลด...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">โควต้าคงเหลือ</label>
                                <div id="prionicQuotaRemaining" class="form-control-plaintext">กำลังตรวจสอบ...</div>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="requestDistribution('prionic')">
                                <i class="fas fa-hand-paper me-2"></i>ขอรายชื่อ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ผลการแจกตามคำขอ -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>ผลการแจกตามคำขอ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="requestDistributionResults">
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                                <h5>ยังไม่มีการแจกตามคำขอ</h5>
                                <p class="text-muted">กรุณาเลือกจำนวนและ Telesales แล้วกดขอรายชื่อ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// โหลดข้อมูลเมื่อหน้าเว็บพร้อม
document.addEventListener('DOMContentLoaded', function() {
    refreshDistributionStats();
    loadTelesalesLists();

    // โหลดจำนวนลูกค้าพร้อมแจกตามวันที่เริ่มต้น
    updateAvailableCustomers('prima');
    updateAvailableCustomers('prionic');

    // โหลดโควต้าคงเหลือ
    loadQuotaRemaining('prima');
    loadQuotaRemaining('prionic');
});

// รีเฟรชสถิติ
function refreshDistributionStats() {
    // โหลดสถิติแยกตามบริษัท
    loadCompanyStats('prima');
    loadCompanyStats('prionic');
}

// โหลดสถิติของแต่ละบริษัท
function loadCompanyStats(company) {
    fetch(`api/customer-distribution.php?action=company_stats&company=${company}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById(`${company}DistributionCount`).textContent = stats.distribution_count || 0;
                document.getElementById(`${company}TelesalesCount`).textContent = stats.telesales_count || 0;
                document.getElementById(`${company}HotCount`).textContent = stats.hot_count || 0;
                document.getElementById(`${company}WarmCount`).textContent = stats.warm_count || 0;
            }
        })
        .catch(error => {
            console.error(`Error loading ${company} stats:`, error);
        });
}

// โหลดรายการ Telesales แยกตามบริษัท
function loadTelesalesLists() {
    loadTelesalesList('prima');
    loadTelesalesList('prionic');
}

function loadTelesalesList(company) {
    fetch(`api/customer-distribution.php?action=telesales_by_company&company=${company}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const telesales = data.data;

                // อัปเดตรายการ checkbox สำหรับการแจกแบบเฉลี่ย
                const listContainer = document.getElementById(`${company}TelesalesList`);
                if (listContainer) {
                    if (telesales.length === 0) {
                        listContainer.innerHTML = '<div class="text-muted">ไม่มี Telesales ในบริษัทนี้</div>';
                    } else {
                        let html = '';
                        telesales.forEach(person => {
                            const customerCount = person.current_customers_count || 0;
                            html += `
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="${person.user_id}"
                                           id="${company}Telesales${person.user_id}"
                                           onchange="updateSelectedCount('${company}')">
                                    <label class="form-check-label" for="${company}Telesales${person.user_id}">
                                        ${person.full_name} (${customerCount} ลูกค้า)
                                    </label>
                                </div>
                            `;
                        });
                        listContainer.innerHTML = html;
                    }
                }

                // อัปเดต dropdown สำหรับการแจกตามคำขอ
                const selectEl = document.getElementById(`${company}RequestTelesales`);
                if (selectEl) {
                    if (telesales.length === 0) {
                        selectEl.innerHTML = '<option value="">ไม่มี Telesales ในบริษัทนี้</option>';
                    } else {
                        let options = '<option value="">เลือก Telesales</option>';
                        telesales.forEach(person => {
                            const customerCount = person.current_customers_count || 0;
                            options += `<option value="${person.user_id}">${person.full_name} (${customerCount} ลูกค้า)</option>`;
                        });
                        selectEl.innerHTML = options;
                    }

                    // เพิ่ม event listener สำหรับการเปลี่ยน Telesales
                    selectEl.addEventListener('change', function() {
                        if (this.value) {
                            loadQuotaForTelesales(company, this.value);
                        } else {
                            document.getElementById(`${company}QuotaRemaining`).textContent = 'กรุณาเลือก Telesales';
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error(`Error loading ${company} telesales:`, error);
        });
}

// อัปเดตจำนวนลูกค้าพร้อมแจกตามวันที่
function updateAvailableCustomers(company) {
    const dateFrom = document.getElementById(`${company}DateFrom`).value;
    const dateTo = document.getElementById(`${company}DateTo`).value;

    if (!dateFrom || !dateTo) return;

    const displayEl = document.getElementById(`${company}AvailableCustomers`);
    displayEl.textContent = 'กำลังโหลด...';

    fetch(`api/customer-distribution.php?action=available_customers_by_date&company=${company}&date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.count || 0;
                displayEl.textContent = `${count.toLocaleString()} คน`;
                displayEl.setAttribute('data-count', count);

                // อัปเดตการคำนวณ
                calculateAverageDistribution(company);
            } else {
                displayEl.textContent = 'ไม่สามารถโหลดข้อมูลได้';
            }
        })
        .catch(error => {
            console.error(`Error loading available customers for ${company}:`, error);
            displayEl.textContent = 'เกิดข้อผิดพลาด';
        });
}

// ใช้ลูกค้าทั้งหมดในช่วงวันที่
function useAllAvailableCustomers(company) {
    const displayEl = document.getElementById(`${company}AvailableCustomers`);
    const count = parseInt(displayEl.getAttribute('data-count')) || 0;

    if (count > 0) {
        document.getElementById(`${company}CustomerCount`).value = count;
        calculateAverageDistribution(company);
    } else {
        showAlert('ไม่มีลูกค้าในช่วงวันที่ที่เลือก', 'warning');
    }
}

// โหลดโควต้าคงเหลือ
function loadQuotaRemaining(company) {
    const quotaEl = document.getElementById(`${company}QuotaRemaining`);
    quotaEl.textContent = 'กรุณาเลือก Telesales';
}

// โหลดโควต้าสำหรับ Telesales คนนั้น
function loadQuotaForTelesales(company, telesalesId) {
    const quotaEl = document.getElementById(`${company}QuotaRemaining`);
    quotaEl.textContent = 'กำลังตรวจสอบ...';

    fetch(`api/customer-distribution.php?action=check_quota&company=${company}&telesales_id=${telesalesId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const quota = data.data;
                const weeklyUsed = quota.weekly_used || 0;
                const weeklyRemaining = quota.weekly_remaining || 0;
                const dailyUsed = quota.daily_used || 0;
                const dailyRemaining = quota.daily_remaining || 0;

                // กำหนดสีตามโควต้าคงเหลือ
                const weeklyColor = weeklyRemaining > 100 ? 'success' : weeklyRemaining > 50 ? 'warning' : 'danger';
                const dailyColor = dailyRemaining > 75 ? 'success' : dailyRemaining > 25 ? 'warning' : 'danger';

                quotaEl.innerHTML = `
                    <div class="mb-2">
                        <strong class="text-${weeklyColor}">สัปดาห์: ${weeklyRemaining.toLocaleString()} รายชื่อ</strong>
                        <small class="text-muted d-block">ใช้แล้ว ${weeklyUsed.toLocaleString()}/300 รายชื่อ</small>
                    </div>
                    <div>
                        <strong class="text-${dailyColor}">วันนี้: ${dailyRemaining.toLocaleString()} รายชื่อ</strong>
                        <small class="text-muted d-block">ใช้แล้ว ${dailyUsed.toLocaleString()}/150 รายชื่อ</small>
                    </div>
                `;

                // อัปเดตปุ่มขอรายชื่อ
                const requestBtn = document.querySelector(`#${company}RequestTelesales`).closest('.card-body').querySelector('button[onclick*="requestDistribution"]');
                if (requestBtn) {
                    if (quota.can_request && weeklyRemaining > 0 && dailyRemaining > 0) {
                        requestBtn.disabled = false;
                        requestBtn.classList.remove('btn-secondary');
                        requestBtn.classList.add(company === 'prima' ? 'btn-warning' : 'btn-secondary');
                    } else {
                        requestBtn.disabled = true;
                        requestBtn.classList.add('btn-secondary');
                        requestBtn.classList.remove('btn-warning');
                    }
                }
            } else {
                quotaEl.innerHTML = '<span class="text-danger">ไม่สามารถตรวจสอบโควต้าได้</span>';
            }
        })
        .catch(error => {
            console.error(`Error loading quota for ${company}:`, error);
            quotaEl.innerHTML = '<span class="text-danger">เกิดข้อผิดพลาดในการตรวจสอบโควต้า</span>';
        });
}

// อัปเดตจำนวนที่เลือก
function updateSelectedCount(company) {
    const checkboxes = document.querySelectorAll(`#${company}TelesalesList input[type="checkbox"]:checked`);
    const count = checkboxes.length;
    document.getElementById(`${company}SelectedTelesalesCount`).textContent = `${count} คน`;

    // คำนวณการแจกใหม่
    calculateAverageDistribution(company);
}

// คำนวณการแจกแบบเฉลี่ย
function calculateAverageDistribution(company) {
    const customerCount = parseInt(document.getElementById(`${company}CustomerCount`).value) || 0;
    const selectedCount = document.querySelectorAll(`#${company}TelesalesList input[type="checkbox"]:checked`).length;

    if (customerCount > 0 && selectedCount > 0) {
        const averagePerPerson = Math.floor(customerCount / selectedCount);
        const remainder = customerCount % selectedCount;

        let text = `${averagePerPerson} คน/คน`;
        if (remainder > 0) {
            text += ` (เศษ ${remainder} คนส่งให้คนที่ยอดขายเยอะที่สุด)`;
        }

        document.getElementById(`${company}AveragePerPerson`).textContent = text;
        document.getElementById(`${company}ConfirmBtn`).disabled = false;
    } else {
        document.getElementById(`${company}AveragePerPerson`).textContent = '-';
        document.getElementById(`${company}ConfirmBtn`).disabled = true;
    }
}

// ยืนยันการแจกแบบเฉลี่ย
function confirmAverageDistribution(company) {
    if (!confirm(`คุณต้องการยืนยันการแจกลูกค้าแบบเฉลี่ยสำหรับบริษัท ${company.toUpperCase()} หรือไม่?`)) {
        return;
    }

    const customerCount = parseInt(document.getElementById(`${company}CustomerCount`).value);
    const dateFrom = document.getElementById(`${company}DateFrom`).value;
    const dateTo = document.getElementById(`${company}DateTo`).value;
    const selectedTelesales = Array.from(document.querySelectorAll(`#${company}TelesalesList input[type="checkbox"]:checked`))
                                   .map(cb => cb.value);

    // Frontend validations
    if (!customerCount || customerCount < 1) {
        showAlert('กรุณาระบุจำนวนลูกค้าที่ต้องการแจกให้ถูกต้อง', 'warning');
        return;
    }
    if (!selectedTelesales || selectedTelesales.length === 0) {
        showAlert('กรุณาเลือก Telesales อย่างน้อย 1 คน', 'warning');
        return;
    }

    const data = {
        type: 'average',
        company: company,
        customer_count: customerCount,
        date_from: dateFrom,
        date_to: dateTo,
        telesales_ids: selectedTelesales
    };

    fetch('api/customer-distribution.php?action=distribute_average', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        if (!response.ok) {
            const text = await response.text().catch(() => '');
            throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showAlert(`แจกลูกค้าแบบเฉลี่ยสำเร็จ: ${result.message}`, 'success');
            refreshDistributionStats();
        } else {
            showAlert(`เกิดข้อผิดพลาด: ${result.message}`, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการแจกลูกค้า: ' + (error.message || ''), 'danger');
    });
}

// แจกตามคำขอ
function requestDistribution(company) {
    const count = parseInt(document.getElementById(`${company}RequestCount`).value);
    const priority = document.getElementById(`${company}RequestPriority`).value;
    const telesalesId = document.getElementById(`${company}RequestTelesales`).value;

    if (!count || count < 1 || count > 150) {
        showAlert('กรุณาระบุจำนวนลูกค้า 1-150 คน', 'warning');
        return;
    }

    if (!telesalesId) {
        showAlert('กรุณาเลือก Telesales', 'warning');
        return;
    }

    // ยืนยันการขอรายชื่อ
    if (!confirm(`คุณต้องการขอรายชื่อลูกค้า ${count} คนสำหรับ ${company.toUpperCase()} หรือไม่?`)) {
        return;
    }

    const data = {
        type: 'request',
        company: company,
        quantity: count,
        priority: priority,
        telesales_id: telesalesId
    };

    // แสดง loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังแจก...';
    btn.disabled = true;

    fetch('api/customer-distribution.php?action=distribute_request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        if (!response.ok) {
            const text = await response.text().catch(() => '');
            throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showAlert(`แจกลูกค้าตามคำขอสำเร็จ: ${result.message}`, 'success');
            refreshDistributionStats();

            // แสดงผลการแจก
            displayRequestResults(result.data);

            // รีเซ็ตฟอร์ม
            document.getElementById(`${company}RequestCount`).value = '';

            // โหลดโควต้าใหม่
            loadQuotaForTelesales(company, telesalesId);
        } else {
            showAlert(`เกิดข้อผิดพลาด: ${result.message}`, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการแจกลูกค้า: ' + (error.message || ''), 'danger');
    })
    .finally(() => {
        // คืนสถานะปุ่ม
        btn.innerHTML = originalText;
        btn.disabled = false;
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

    const borderBottom = document.querySelector('.border-bottom');
    if (borderBottom) {
        borderBottom.insertAdjacentHTML('afterend', alertHtml);
    }

    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
}
</script>
