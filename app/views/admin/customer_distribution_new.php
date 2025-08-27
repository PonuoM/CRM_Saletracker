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
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="grade-a-tab" data-bs-toggle="tab" data-bs-target="#grade-a" type="button" role="tab">
            <i class="fas fa-star me-2"></i>การแจกเกรด A
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

        <!-- ผลการแจกแบบเฉลี่ย -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>ผลการแจกแบบเฉลี่ย
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="averageDistributionResults">
                            <div class="text-center py-4">
                                <i class="fas fa-balance-scale text-muted fa-3x mb-3"></i>
                                <h5>ยังไม่มีการแจกแบบเฉลี่ย</h5>
                                <p class="text-muted">กรุณาเลือกจำนวนและ Telesales แล้วกดยืนยันการแจก</p>
                            </div>
                        </div>
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

    <!-- แท็บการแจกเกรด A -->
    <div class="tab-pane fade" id="grade-a" role="tabpanel">
        <!-- สถิติลูกค้าเกรด A แยกตามบริษัท -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>สถิติลูกค้าเกรด A แยกตามบริษัท
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Prima Grade A Stats -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-building me-2"></i>บริษัท พรีม่า (PRIMA)
                                </h6>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    ลูกค้าเกรด A+</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaGradeAPlusCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    ลูกค้าเกรด A</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaGradeACount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    รวมลูกค้าเกรด A พร้อมแจก</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="primaTotalGradeA">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prionic Grade A Stats -->
                            <div class="col-md-6">
                                <h6 class="text-info mb-3">
                                    <i class="fas fa-building me-2"></i>บริษัท พรีออนิค (PRIONIC)
                                </h6>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    ลูกค้าเกรด A+</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicGradeAPlusCount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    ลูกค้าเกรด A</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicGradeACount">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    รวมลูกค้าเกรด A พร้อมแจก</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="prionicTotalGradeA">
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

        <!-- Dynamic Grade A Distribution -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>การแจกเกรด A - พรีม่า
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- เลือกเกรดที่ต้องการแจก -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-filter me-2"></i>เลือกเกรดที่ต้องการแจก
                            </label>
                            <div class="card border-info">
                                <div class="card-body py-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="primaIncludeAPlus" value="A+" onchange="updateGradeASelection('prima')">
                                        <label class="form-check-label" for="primaIncludeAPlus">
                                            <span class="badge bg-warning me-1">A+</span>
                                            รวมเกรด A+
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="primaIncludeA" value="A" checked onchange="updateGradeASelection('prima')">
                                        <label class="form-check-label" for="primaIncludeA">
                                            <span class="badge bg-primary me-1">A</span>
                                            รวมเกรด A
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            จำนวนลูกค้าที่สามารถแจกได้: <strong id="primaAvailableCount">0</strong> คน
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribution Mode Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">🎯 โหมดการแจก</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="primaGradeAMode" id="primaMode1" value="equal" checked>
                                <label class="btn btn-outline-primary btn-sm" for="primaMode1">
                                    <i class="fas fa-balance-scale me-1"></i>เฉลี่ย
                                </label>
                                
                                <input type="radio" class="btn-check" name="primaGradeAMode" id="primaMode2" value="numbers">
                                <label class="btn btn-outline-success btn-sm" for="primaMode2">
                                    <i class="fas fa-hashtag me-1"></i>จำนวน
                                </label>
                                
                                <input type="radio" class="btn-check" name="primaGradeAMode" id="primaMode3" value="percentage">
                                <label class="btn btn-outline-warning btn-sm" for="primaMode3">
                                    <i class="fas fa-percentage me-1"></i>เปอร์เซ็นต์
                                </label>
                                
                                <input type="radio" class="btn-check" name="primaGradeAMode" id="primaMode4" value="mixed">
                                <label class="btn btn-outline-info btn-sm" for="primaMode4">
                                    <i class="fas fa-adjust me-1"></i>ผสม
                                </label>
                            </div>
                        </div>

                        <!-- Telesales Allocation List -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">👥 การจัดสรรลูกค้า</label>
                            <div id="primaGradeATelesalesList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="card bg-light">
                            <div class="card-body py-3">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block">รวมที่แจก</small>
                                        <strong id="primaGradeATotalAllocated" class="text-primary">0</strong>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block">ที่มีทั้งหมด</small>
                                        <strong id="primaGradeATotalAvailable" class="text-info">0</strong>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block">สถานะ</small>
                                        <span id="primaGradeAStatus" class="badge bg-secondary">รอข้อมูล</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetGradeAAllocation('prima')">
                                <i class="fas fa-undo me-1"></i>รีเซ็ต
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="autoEqualGradeA('prima')">
                                <i class="fas fa-balance-scale me-1"></i>เฉลี่ยอัตโนมัติ
                            </button>
                            <button type="button" class="btn btn-success" onclick="confirmGradeADistribution('prima')" disabled id="primaGradeAConfirmBtn">
                                <i class="fas fa-star me-1"></i>ยืนยันการแจกเกรด A
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>การแจกเกรด A - พรีออนิค
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- เลือกเกรดที่ต้องการแจก -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-filter me-2"></i>เลือกเกรดที่ต้องการแจก
                            </label>
                            <div class="card border-info">
                                <div class="card-body py-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="prionicIncludeAPlus" value="A+" onchange="updateGradeASelection('prionic')">
                                        <label class="form-check-label" for="prionicIncludeAPlus">
                                            <span class="badge bg-warning me-1">A+</span>
                                            รวมเกรด A+
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="prionicIncludeA" value="A" checked onchange="updateGradeASelection('prionic')">
                                        <label class="form-check-label" for="prionicIncludeA">
                                            <span class="badge bg-primary me-1">A</span>
                                            รวมเกรด A
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            จำนวนลูกค้าที่สามารถแจกได้: <strong id="prionicAvailableCount">0</strong> คน
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribution Mode Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">🎯 โหมดการแจก</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="prionicGradeAMode" id="prionicMode1" value="equal" checked>
                                <label class="btn btn-outline-primary btn-sm" for="prionicMode1">
                                    <i class="fas fa-balance-scale me-1"></i>เฉลี่ย
                                </label>
                                
                                <input type="radio" class="btn-check" name="prionicGradeAMode" id="prionicMode2" value="numbers">
                                <label class="btn btn-outline-success btn-sm" for="prionicMode2">
                                    <i class="fas fa-hashtag me-1"></i>จำนวน
                                </label>
                                
                                <input type="radio" class="btn-check" name="prionicGradeAMode" id="prionicMode3" value="percentage">
                                <label class="btn btn-outline-warning btn-sm" for="prionicMode3">
                                    <i class="fas fa-percentage me-1"></i>เปอร์เซ็นต์
                                </label>
                                
                                <input type="radio" class="btn-check" name="prionicGradeAMode" id="prionicMode4" value="mixed">
                                <label class="btn btn-outline-info btn-sm" for="prionicMode4">
                                    <i class="fas fa-adjust me-1"></i>ผสม
                                </label>
                            </div>
                        </div>

                        <!-- Telesales Allocation List -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">👥 การจัดสรรลูกค้า</label>
                            <div id="prionicGradeATelesalesList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="card bg-light">
                            <div class="card-body py-3">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block">รวมที่แจก</small>
                                        <strong id="prionicGradeATotalAllocated" class="text-primary">0</strong>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block">ที่มีทั้งหมด</small>
                                        <strong id="prionicGradeATotalAvailable" class="text-info">0</strong>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block">สถานะ</small>
                                        <span id="prionicGradeAStatus" class="badge bg-secondary">รอข้อมูล</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetGradeAAllocation('prionic')">
                                <i class="fas fa-undo me-1"></i>รีเซ็ต
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="autoEqualGradeA('prionic')">
                                <i class="fas fa-balance-scale me-1"></i>เฉลี่ยอัตโนมัติ
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="confirmGradeADistribution('prionic')" disabled id="prionicGradeAConfirmBtn">
                                <i class="fas fa-star me-1"></i>ยืนยันการแจกเกรด A
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade A Distribution Results -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>ผลการแจกเกรด A
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="gradeADistributionResults">
                            <div class="text-center py-4">
                                <i class="fas fa-star text-muted fa-3x mb-3"></i>
                                <h5>ยังไม่มีการแจกลูกค้าเกรด A</h5>
                                <p class="text-muted">กรุณาเลือกโหมดการแจกและจัดสรรจำนวนลูกค้าแล้วกดยืนยัน</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงรายละเอียดลูกค้าที่แจก -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1" aria-labelledby="customerDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="customerDetailsModalLabel">
                    <i class="fas fa-users me-2"></i>
                    รายละเอียดลูกค้าที่แจก
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- ข้อมูลสรุป -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                <h6 class="card-title">Telesales</h6>
                                <p class="card-text" id="customerDetailsTelesalesName">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x text-info mb-2"></i>
                                <h6 class="card-title">บริษัท</h6>
                                <p class="card-text" id="customerDetailsCompany">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-success mb-2"></i>
                                <h6 class="card-title">จำนวนลูกค้า</h6>
                                <p class="card-text"><span id="customerDetailsCount">0</span> คน</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ตารางลูกค้า -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-info">
                            <tr>
                                <th>#</th>
                                <th>รหัสลูกค้า</th>
                                <th>ชื่อลูกค้า</th>
                                <th>เบอร์โทร</th>
                                <th>เกรด</th>
                                <th>สถานะ</th>
                                <th>วันที่หมดอายุ</th>
                            </tr>
                        </thead>
                        <tbody id="customerDetailsTableBody">
                            <!-- จะถูกเติมด้วย JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    ปิด
                </button>
                <button type="button" class="btn btn-primary" onclick="downloadCustomerDetails()">
                    <i class="fas fa-download me-1"></i>
                    ดาวน์โหลดรายการนี้
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS แก้ปัญหา modal-backdrop -->
<style>
    /* แก้ปัญหา modal z-index */
    #gradeASuccessModal {
        z-index: 99999 !important;
    }
    
    #gradeASuccessModal .modal-dialog {
        z-index: 99999 !important;
        position: relative;
    }
    
    #gradeASuccessModal .modal-content {
        z-index: 99999 !important;
        background-color: white !important;
    }
    
    .modal-backdrop {
        display: none !important;
        z-index: -1 !important;
    }
    
    /* ป้องกันการ scroll ของ body เมื่อ modal เปิด */
    body.modal-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }
    
    /* แก้ปัญหา modal แสดงไม่เต็มหน้าจอ */
    .modal {
        padding-right: 0 !important;
    }
    
    /* ทำให้ modal-content แสดงด้านหน้าสุด */
    #gradeASuccessModal .modal-content {
        position: relative;
        z-index: 10001 !important;
        background-color: white !important;
        border: none !important;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3) !important;
    }
</style>

<!-- Grade A Distribution Success Modal -->
<div class="modal fade" id="gradeASuccessModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    แจกลูกค้าเกรด A สำเร็จ!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Summary Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-3">
                                        <div class="h4 text-success mb-1" id="modalTotalDistributed">0</div>
                                        <small class="text-muted">ลูกค้าทั้งหมด</small>
                                    </div>
                                    <div class="col-3">
                                        <div class="h4 text-primary mb-1" id="modalTotalTelesales">0</div>
                                        <small class="text-muted">Telesales</small>
                                    </div>
                                    <div class="col-3">
                                        <div class="h4 text-warning mb-1" id="modalCompany">-</div>
                                        <small class="text-muted">บริษัท</small>
                                    </div>
                                    <div class="col-3">
                                        <div class="h4 text-info mb-1">A</div>
                                        <small class="text-muted">เกรด</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Details -->
                <div class="row">
                    <div class="col-12">
                        <h6 class="mb-3">
                            <i class="fas fa-list me-2"></i>
                            รายละเอียดการแจก
                        </h6>
                        <div id="modalDistributionDetails">
                            <!-- Dynamic content will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>ปิด
                </button>
                <button type="button" class="btn btn-primary" onclick="downloadDistributionReport()">
                    <i class="fas fa-download me-1"></i>ดาวน์โหลดรายงาน
                </button>
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

    // โหลดข้อมูลสำหรับ Grade A Distribution
    loadGradeAStats();
    loadGradeATelesalesLists();
    
    // อัปเดต available count ตามการเลือกเริ่มต้น (A เท่านั้น)
    setTimeout(() => {
        updateAvailableCount('prima');
        updateAvailableCount('prionic');
    }, 1000);
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
            
            // แสดงผลการแจก
            displayAverageResults(result.data);
            
            // เลื่อนไปยังส่วนผลการแจก
            document.getElementById('averageDistributionResults').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
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
            
            // เลื่อนไปยังส่วนผลการแจก
            document.getElementById('requestDistributionResults').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });

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

// ===== GRADE A DISTRIBUTION FUNCTIONS =====

// Global variables สำหรับเก็บข้อมูล Grade A
window.gradeAData = {
    prima: { 
        available: 0, 
        allocations: {}, 
        mode: 'equal',
        grades: { 'A+': 0, 'A': 0 },
        selectedGrades: ['A'] // ค่าเริ่มต้นเลือกเฉพาะ A
    },
    prionic: { 
        available: 0, 
        allocations: {}, 
        mode: 'equal',
        grades: { 'A+': 0, 'A': 0 },
        selectedGrades: ['A'] // ค่าเริ่มต้นเลือกเฉพาะ A
    }
};

// โหลดสถิติลูกค้าเกรด A
function loadGradeAStats() {
    ['prima', 'prionic'].forEach(company => {
        fetch(`api/customer-distribution.php?action=grade_a_stats&company=${company}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    const aPlusCount = stats.grade_a_plus_count || 0;
                    const aCount = stats.grade_a_count || 0;
                    
                    document.getElementById(`${company}GradeAPlusCount`).textContent = aPlusCount;
                    document.getElementById(`${company}GradeACount`).textContent = aCount;
                    document.getElementById(`${company}TotalGradeA`).textContent = stats.total_grade_a || 0;
                    
                    // เก็บข้อมูลแยกตามเกรดใน global variable
                    window.gradeAData[company].grades['A+'] = aPlusCount;
                    window.gradeAData[company].grades['A'] = aCount;
                    
                    // คำนวณจำนวนที่สามารถแจกได้ตามการเลือก
                    updateAvailableCount(company);
                    
                    updateGradeAStatus(company);
                }
            })
            .catch(error => {
                console.error(`Error loading ${company} Grade A stats:`, error);
            });
    });
}

// อัปเดตการเลือกเกรดและคำนวณจำนวนที่สามารถแจกได้
function updateGradeASelection(company) {
    const selectedGrades = [];
    
    // ตรวจสอบการเลือก A+
    if (document.getElementById(`${company}IncludeAPlus`).checked) {
        selectedGrades.push('A+');
    }
    
    // ตรวจสอบการเลือก A
    if (document.getElementById(`${company}IncludeA`).checked) {
        selectedGrades.push('A');
    }
    
    // ต้องเลือกอย่างน้อยหนึ่งเกรด
    if (selectedGrades.length === 0) {
        showAlert('กรุณาเลือกอย่างน้อยหนึ่งเกรด', 'warning');
        // กลับไปเลือก A
        document.getElementById(`${company}IncludeA`).checked = true;
        selectedGrades.push('A');
    }
    
    // อัปเดต global data
    window.gradeAData[company].selectedGrades = selectedGrades;
    
    // อัปเดตจำนวนที่สามารถแจกได้
    updateAvailableCount(company);
    
    // รีเซ็ตการจัดสรร
    resetGradeAAllocation(company);
    
    console.log(`${company} selected grades:`, selectedGrades);
}

// คำนวณและแสดงจำนวนลูกค้าที่สามารถแจกได้
function updateAvailableCount(company) {
    const grades = window.gradeAData[company].grades;
    const selectedGrades = window.gradeAData[company].selectedGrades;
    
    let availableCount = 0;
    selectedGrades.forEach(grade => {
        availableCount += grades[grade] || 0;
    });
    
    // อัปเดต global data
    window.gradeAData[company].available = availableCount;
    
    // อัปเดต UI
    document.getElementById(`${company}AvailableCount`).textContent = availableCount;
    document.getElementById(`${company}GradeATotalAvailable`).textContent = availableCount;
    
    // อัปเดตสถานะ
    updateGradeAStatus(company);
    
    console.log(`${company} available count:`, availableCount);
}

// โหลดรายการ Telesales สำหรับ Grade A
function loadGradeATelesalesLists() {
    ['prima', 'prionic'].forEach(company => {
        fetch(`api/customer-distribution.php?action=telesales_by_company&company=${company}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const telesales = data.data;
                    renderGradeATelesalesList(company, telesales);
                }
            })
            .catch(error => {
                console.error(`Error loading ${company} Grade A telesales:`, error);
            });
    });
}

// แสดง Telesales List สำหรับ Grade A
function renderGradeATelesalesList(company, telesales) {
    const container = document.getElementById(`${company}GradeATelesalesList`);
    if (!container) return;

    if (telesales.length === 0) {
        container.innerHTML = '<div class="text-muted">ไม่มี Telesales ในบริษัทนี้</div>';
        return;
    }

    let html = '';
    telesales.forEach(person => {
        const userId = person.user_id;
        const customerCount = person.current_customers_count || 0;
        const allocation = window.gradeAData[company].allocations[userId] || { count: 0, percentage: 0 };

        html += `
            <div class="row align-items-center mb-2 p-2 border rounded telesales-row" data-user-id="${userId}">
                <div class="col-1">
                    <input class="form-check-input" type="checkbox" id="${company}GradeA${userId}" 
                           onchange="toggleGradeATelesales('${company}', ${userId})" ${allocation.count > 0 ? 'checked' : ''}>
                </div>
                <div class="col-4">
                    <label class="form-check-label" for="${company}GradeA${userId}">
                        <strong>${person.full_name}</strong><br>
                        <small class="text-muted">${customerCount} ลูกค้าปัจจุบัน</small>
                    </label>
                </div>
                <div class="col-3">
                    <input type="number" class="form-control form-control-sm allocation-number" 
                           placeholder="จำนวน" min="0" value="${allocation.count}"
                           onchange="updateGradeAAllocation('${company}', ${userId}, 'count', this.value)"
                           ${allocation.count === 0 ? 'disabled' : ''}>
                </div>
                <div class="col-3">
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control allocation-percentage" 
                               placeholder="%" min="0" max="100" step="0.1" value="${allocation.percentage}"
                               onchange="updateGradeAAllocation('${company}', ${userId}, 'percentage', this.value)"
                               ${allocation.count === 0 ? 'disabled' : ''}>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-1">
                    <small class="text-muted preview-count">≈ ${Math.round(allocation.count)}</small>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    
    // เพิ่ม event listeners สำหรับ mode switching
    document.querySelectorAll(`input[name="${company}GradeAMode"]`).forEach(radio => {
        radio.addEventListener('change', function() {
            changeGradeAMode(company, this.value);
        });
    });
}

// สลับการเลือก Telesales
function toggleGradeATelesales(company, userId) {
    const checkbox = document.getElementById(`${company}GradeA${userId}`);
    const row = document.querySelector(`[data-user-id="${userId}"]`);
    const numberInput = row.querySelector('.allocation-number');
    const percentageInput = row.querySelector('.allocation-percentage');

    if (checkbox.checked) {
        // เปิดการใช้งาน
        numberInput.disabled = false;
        percentageInput.disabled = false;
        
        // ถ้าไม่มีค่า ให้ใส่ค่าเริ่มต้น
        if (!numberInput.value || numberInput.value === '0') {
            const defaultValue = getDefaultAllocationValue(company);
            numberInput.value = defaultValue.count;
            percentageInput.value = defaultValue.percentage;
            updateGradeAAllocation(company, userId, 'count', defaultValue.count);
        }
    } else {
        // ปิดการใช้งาน
        numberInput.disabled = true;
        percentageInput.disabled = true;
        numberInput.value = '0';
        percentageInput.value = '0';
        updateGradeAAllocation(company, userId, 'count', 0);
    }
}

// คำนวณค่าเริ่มต้นสำหรับการแจก
function getDefaultAllocationValue(company) {
    const available = window.gradeAData[company].available;
    const selectedCount = Object.keys(window.gradeAData[company].allocations).filter(
        userId => window.gradeAData[company].allocations[userId].count > 0
    ).length + 1; // +1 เพราะกำลังเพิ่มคนใหม่

    const defaultCount = Math.floor(available / selectedCount);
    const defaultPercentage = (defaultCount / available * 100).toFixed(1);

    return { count: defaultCount, percentage: defaultPercentage };
}

// อัปเดตการจัดสรร
function updateGradeAAllocation(company, userId, type, value) {
    const numValue = parseFloat(value) || 0;
    const available = window.gradeAData[company].available;
    
    if (!window.gradeAData[company].allocations[userId]) {
        window.gradeAData[company].allocations[userId] = { count: 0, percentage: 0 };
    }
    
    const allocation = window.gradeAData[company].allocations[userId];
    const row = document.querySelector(`[data-user-id="${userId}"]`);
    const numberInput = row.querySelector('.allocation-number');
    const percentageInput = row.querySelector('.allocation-percentage');
    const previewElement = row.querySelector('.preview-count');

    if (type === 'count') {
        allocation.count = numValue;
        allocation.percentage = available > 0 ? (numValue / available * 100).toFixed(1) : 0;
        percentageInput.value = allocation.percentage;
    } else if (type === 'percentage') {
        allocation.percentage = numValue;
        allocation.count = Math.round(available * numValue / 100);
        numberInput.value = allocation.count;
    }

    previewElement.textContent = `≈ ${Math.round(allocation.count)}`;
    updateGradeASummary(company);
}

// อัปเดตสรุปการจัดสรร
function updateGradeASummary(company) {
    const allocations = window.gradeAData[company].allocations;
    const available = window.gradeAData[company].available;
    
    let totalAllocated = 0;
    Object.values(allocations).forEach(allocation => {
        totalAllocated += allocation.count || 0;
    });

    document.getElementById(`${company}GradeATotalAllocated`).textContent = totalAllocated;
    updateGradeAStatus(company);
}

// อัปเดตสถานะ
function updateGradeAStatus(company) {
    const allocations = window.gradeAData[company].allocations;
    const available = window.gradeAData[company].available;
    const statusElement = document.getElementById(`${company}GradeAStatus`);
    const confirmBtn = document.getElementById(`${company}GradeAConfirmBtn`);
    
    let totalAllocated = 0;
    Object.values(allocations).forEach(allocation => {
        totalAllocated += allocation.count || 0;
    });

    if (totalAllocated === 0) {
        statusElement.className = 'badge bg-secondary';
        statusElement.textContent = 'รอข้อมูล';
        confirmBtn.disabled = true;
    } else if (totalAllocated === available) {
        statusElement.className = 'badge bg-success';
        statusElement.textContent = 'สมดุล ✅';
        confirmBtn.disabled = false;
    } else if (totalAllocated < available) {
        statusElement.className = 'badge bg-warning';
        statusElement.textContent = `เหลือ ${available - totalAllocated}`;
        confirmBtn.disabled = false;
    } else {
        statusElement.className = 'badge bg-danger';
        statusElement.textContent = `เกิน ${totalAllocated - available}`;
        confirmBtn.disabled = true;
    }
}

// เปลี่ยนโหมดการแจก
function changeGradeAMode(company, mode) {
    window.gradeAData[company].mode = mode;
    
    // อัปเดต UI ตามโหมด
    const container = document.getElementById(`${company}GradeATelesalesList`);
    const numberInputs = container.querySelectorAll('.allocation-number');
    const percentageInputs = container.querySelectorAll('.allocation-percentage');

    switch (mode) {
        case 'equal':
            autoEqualGradeA(company);
            break;
        case 'numbers':
            percentageInputs.forEach(input => input.style.display = 'none');
            numberInputs.forEach(input => input.style.display = 'block');
            break;
        case 'percentage':
            numberInputs.forEach(input => input.style.display = 'none');
            percentageInputs.forEach(input => input.style.display = 'block');
            break;
        case 'mixed':
            numberInputs.forEach(input => input.style.display = 'block');
            percentageInputs.forEach(input => input.style.display = 'block');
            break;
    }
}

// แจกเฉลี่ยอัตโนมัติ
function autoEqualGradeA(company) {
    const available = window.gradeAData[company].available;
    const checkboxes = document.querySelectorAll(`#${company}GradeATelesalesList input[type="checkbox"]:checked`);
    
    if (checkboxes.length === 0) {
        showAlert('กรุณาเลือก Telesales อย่างน้อย 1 คน', 'warning');
        return;
    }

    const averageCount = Math.floor(available / checkboxes.length);
    const remainder = available % checkboxes.length;

    checkboxes.forEach((checkbox, index) => {
        const userId = checkbox.id.replace(`${company}GradeA`, '');
        const extraCount = index < remainder ? 1 : 0;
        const finalCount = averageCount + extraCount;
        
        updateGradeAAllocation(company, userId, 'count', finalCount);
    });
}

// รีเซ็ตการจัดสรร
function resetGradeAAllocation(company) {
    window.gradeAData[company].allocations = {};
    window.gradeAData[company].mode = 'equal';
    
    // รีเซ็ต radio buttons
    document.getElementById(`${company}Mode1`).checked = true;
    
    // รีโหลด Telesales list
    loadGradeATelesalesLists();
}

// ยืนยันการแจกเกรด A
function confirmGradeADistribution(company) {
    const allocations = window.gradeAData[company].allocations;
    const available = window.gradeAData[company].available;
    
    // ตรวจสอบการจัดสรร
    const activeAllocations = Object.entries(allocations).filter(([userId, allocation]) => allocation.count > 0);
    
    if (activeAllocations.length === 0) {
        showAlert('กรุณาจัดสรรลูกค้าให้กับ Telesales อย่างน้อย 1 คน', 'warning');
        return;
    }

    let totalAllocated = 0;
    activeAllocations.forEach(([userId, allocation]) => {
        totalAllocated += allocation.count;
    });

    if (totalAllocated > available) {
        showAlert(`จำนวนที่จัดสรรเกินจำนวนที่มี (${totalAllocated}/${available})`, 'danger');
        return;
    }

    const selectedGrades = window.gradeAData[company].selectedGrades;
    const gradesText = selectedGrades.join(', ');
    
    if (!confirm(`ยืนยันการแจกลูกค้าเกรด ${gradesText} สำหรับบริษัท ${company.toUpperCase()}?\nจำนวนรวม: ${totalAllocated}/${available} คน`)) {
        return;
    }

    // เตรียมข้อมูลสำหรับส่ง API
    const distributionData = {
        company: company,
        selected_grades: window.gradeAData[company].selectedGrades, // ส่งเกรดที่เลือก
        allocations: activeAllocations.map(([userId, allocation]) => ({
            telesales_id: parseInt(userId),
            count: allocation.count
        }))
    };

    const confirmBtn = document.getElementById(`${company}GradeAConfirmBtn`);
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังแจก...';
    confirmBtn.disabled = true;

    fetch('api/customer-distribution.php?action=distribute_grade_a', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(distributionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดง Success Notification Popup
            showGradeASuccessModal(data.data);
            
            // รีเซ็ตและรีโหลดข้อมูล
            resetGradeAAllocation(company);
            loadGradeAStats();
            refreshDistributionStats();
            
            // แสดงผลการแจกในตาราง
            displayGradeAResults(data.data);
        } else {
            showAlert(`เกิดข้อผิดพลาด: ${data.message}`, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการแจกลูกค้า: ' + (error.message || ''), 'danger');
    })
    .finally(() => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

// แสดงผลการแจกเกรด A
function displayGradeAResults(results) {
    const container = document.getElementById('gradeADistributionResults');
    if (!container || !results) return;

    let html = `
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    ผลการแจกเกรด A เสร็จสิ้น
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary">${results.total_distributed || 0}</h4>
                            <small class="text-muted">ลูกค้าทั้งหมด</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info">${results.distributions ? results.distributions.length : 0}</h4>
                            <small class="text-muted">Telesales</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning">${(results.company || '').toUpperCase()}</h4>
                            <small class="text-muted">บริษัท</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadDistributionReport()">
                                <i class="fas fa-download me-1"></i>
                                ดาวน์โหลดรายงาน
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Telesales</th>
                                <th>บริษัท</th>
                                <th>จำนวนลูกค้า</th>
                                <th>เกรด</th>
                                <th>เวลาที่แจก</th>
                                <th>ดูรายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>`;

    if (results.distributions && results.distributions.length > 0) {
        results.distributions.forEach((dist, index) => {
            const customers = dist.customers || [];
            html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-tie text-primary me-2"></i>
                            <strong>${dist.telesales_name}</strong>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${dist.company === 'prima' ? 'primary' : 'info'}">
                            ${(dist.company || '').toUpperCase()}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-success fs-6">
                            ${customers.length} คน
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-warning">เกรด A</span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            ${new Date().toLocaleString('th-TH')}
                        </small>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="showCustomerDetails(${index})" 
                                ${customers.length === 0 ? 'disabled' : ''}>
                            <i class="fas fa-eye me-1"></i>
                            ดูลูกค้า (${customers.length})
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html += `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    ยังไม่มีผลการแจก
                </td>
            </tr>
        `;
    }

    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // เก็บข้อมูลสำหรับการแสดงรายละเอียด
    window.currentDistributionData = results;
}

// แสดงรายละเอียดลูกค้าที่แจกให้ Telesales
function showCustomerDetails(distributionIndex) {
    if (!window.currentDistributionData || !window.currentDistributionData.distributions) return;
    
    const distribution = window.currentDistributionData.distributions[distributionIndex];
    if (!distribution) return;
    
    const customers = distribution.customers || [];
    
    // อัปเดตข้อมูลใน modal
    document.getElementById('customerDetailsTelesalesName').textContent = distribution.telesales_name;
    document.getElementById('customerDetailsCompany').textContent = (distribution.company || '').toUpperCase();
    document.getElementById('customerDetailsCount').textContent = customers.length;
    
    // สร้างตารางลูกค้า
    const tableBody = document.getElementById('customerDetailsTableBody');
    let tableHtml = '';
    
    if (customers.length > 0) {
        customers.forEach((customer, index) => {
            tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td><code class="text-primary">${customer.customer_code || ''}</code></td>
                    <td><strong>${customer.name || ''}</strong></td>
                    <td>${customer.phone || ''}</td>
                    <td>
                        <span class="badge bg-warning">${customer.grade || 'A'}</span>
                    </td>
                    <td>
                        <span class="badge bg-${getTemperatureColor(customer.temperature)}">
                            ${getTemperatureText(customer.temperature)}
                        </span>
                    </td>
                    <td>
                        <small class="text-success">
                            <i class="fas fa-calendar-check me-1"></i>
                            ${customer.time_expiry ? formatDateTime(customer.time_expiry) : 'ไม่ระบุ'}
                            <br>
                            <strong class="text-primary">${customer.days_remaining || 30} วัน</strong>
                        </small>
                    </td>
                </tr>
            `;
        });
    } else {
        tableHtml = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    ไม่มีลูกค้า
                </td>
            </tr>
        `;
    }
    
    tableBody.innerHTML = tableHtml;
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
    modal.show();
    
    // เก็บ index สำหรับการดาวน์โหลด
    window.currentDistributionIndex = distributionIndex;
}

// ดาวน์โหลดรายการลูกค้าของ Telesales เฉพาะคน
function downloadCustomerDetails() {
    let distribution, distributionType = 'เกรด A';
    
    // ตรวจสอบประเภทการแจก
    if (window.currentDistributionIndex === 'request' && window.currentRequestData) {
        // การแจกตามคำขอ
        distribution = {
            telesales_name: window.currentRequestData.telesales_name,
            company: window.currentRequestData.company,
            customers: window.currentRequestData.customers || []
        };
        distributionType = 'ตามคำขอ';
    } else if (window.currentDistributionType === 'average' && window.currentAverageData && window.currentDistributionIndex !== undefined) {
        // การแจกแบบเฉลี่ย
        distribution = window.currentAverageData.distributions[window.currentDistributionIndex];
        distributionType = 'เฉลี่ย';
    } else if (window.currentDistributionData && window.currentDistributionIndex !== undefined) {
        // การแจกเกรด A
        distribution = window.currentDistributionData.distributions[window.currentDistributionIndex];
        distributionType = 'เกรด A';
    }
    
    if (!distribution) {
        showAlert('ไม่พบข้อมูลการแจก', 'error');
        return;
    }
    
    const customers = distribution.customers || [];
    const timestamp = new Date().toLocaleString('th-TH');
    
    // สร้างเนื้อหา CSV
    let csvContent = '\ufeff'; // BOM สำหรับ UTF-8
    csvContent += `รายละเอียดลูกค้าที่แจก (${distributionType})\n`;
    csvContent += `วันที่: ${timestamp}\n`;
    csvContent += `Telesales: ${distribution.telesales_name}\n`;
    csvContent += `บริษัท: ${(distribution.company || '').toUpperCase()}\n`;
    csvContent += `ประเภทการแจก: ${distributionType}\n`;
    csvContent += `จำนวนลูกค้า: ${customers.length} คน\n\n`;
    
    csvContent += '#,รหัสลูกค้า,ชื่อลูกค้า,เบอร์โทร,เกรด,สถานะ,วันที่หมดอายุ,วันที่เหลือ\n';
    
    if (customers.length > 0) {
        customers.forEach((customer, idx) => {
            const expiry = customer.time_expiry ? formatDateTime(customer.time_expiry) : 'ไม่ระบุ';
            const daysRemaining = customer.days_remaining || 30;
            const temperatureText = getTemperatureText(customer.temperature);
            
            csvContent += `${idx + 1},${customer.customer_code || ''},${customer.name || ''},${customer.phone || ''},${customer.grade || 'A'},${temperatureText},${expiry},${daysRemaining} วัน\n`;
        });
    } else {
        csvContent += ',,ไม่มีลูกค้า,,,,,\n';
    }
    
    // ดาวน์โหลดไฟล์
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `ลูกค้าที่แจก${distributionType}_${distribution.telesales_name}_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert(`ดาวน์โหลดรายการลูกค้าของ ${distribution.telesales_name} เสร็จสิ้น`, 'success');
}

// แสดงผลการแจกตามคำขอ (ใช้คอนเซ็ปเดียวกับเกรด A)
function displayRequestResults(results) {
    const container = document.getElementById('requestDistributionResults');
    if (!container || !results) return;

    let html = `
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    ผลการแจกตามคำขอเสร็จสิ้น
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary">${results.distributed_count || 0}</h4>
                            <small class="text-muted">ลูกค้าที่แจก</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info">1</h4>
                            <small class="text-muted">Telesales</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning">${(results.company || '').toUpperCase()}</h4>
                            <small class="text-muted">บริษัท</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadRequestReport()">
                                <i class="fas fa-download me-1"></i>
                                ดาวน์โหลดรายงาน
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Telesales</th>
                                <th>บริษัท</th>
                                <th>จำนวนลูกค้า</th>
                                <th>ประเภท</th>
                                <th>เวลาที่แจก</th>
                                <th>ดูรายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie text-primary me-2"></i>
                                        <strong>${results.telesales_name || 'ไม่ระบุ'}</strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-${results.company === 'prima' ? 'primary' : 'info'}">
                                        ${(results.company || '').toUpperCase()}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success fs-6">
                                        ${results.distributed_count || 0} คน
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">ตามคำขอ</span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        ${new Date().toLocaleString('th-TH')}
                                    </small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-info btn-sm" 
                                            onclick="showRequestCustomerDetails()" 
                                            ${!results.customers || results.customers.length === 0 ? 'disabled' : ''}>
                                        <i class="fas fa-eye me-1"></i>
                                        ดูลูกค้า (${results.distributed_count || 0})
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // เก็บข้อมูลสำหรับการแสดงรายละเอียด
    window.currentRequestData = results;
}

// แสดงรายละเอียดลูกค้าที่แจกตามคำขอ
function showRequestCustomerDetails() {
    if (!window.currentRequestData) return;
    
    const results = window.currentRequestData;
    const customers = results.customers || [];
    
    // อัปเดตข้อมูลใน modal
    document.getElementById('customerDetailsTelesalesName').textContent = results.telesales_name || 'ไม่ระบุ';
    document.getElementById('customerDetailsCompany').textContent = (results.company || '').toUpperCase();
    document.getElementById('customerDetailsCount').textContent = customers.length;
    
    // สร้างตารางลูกค้า
    const tableBody = document.getElementById('customerDetailsTableBody');
    let tableHtml = '';
    
    if (customers.length > 0) {
        customers.forEach((customer, index) => {
            tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td><code class="text-primary">${customer.customer_code || ''}</code></td>
                    <td><strong>${customer.full_name || customer.name || ''}</strong></td>
                    <td>${customer.phone || ''}</td>
                    <td>
                        <span class="badge bg-warning">${customer.customer_grade || customer.grade || 'N/A'}</span>
                    </td>
                    <td>
                        <span class="badge bg-${getTemperatureColor(customer.temperature_status || customer.temperature)}">
                            ${getTemperatureText(customer.temperature_status || customer.temperature)}
                        </span>
                    </td>
                    <td>
                        <small class="text-success">
                            <i class="fas fa-calendar-check me-1"></i>
                            ${customer.customer_time_expiry ? formatDateTime(customer.customer_time_expiry) : 'ไม่ระบุ'}
                            <br>
                            <strong class="text-primary">30 วัน</strong>
                        </small>
                    </td>
                </tr>
            `;
        });
    } else {
        tableHtml = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    ไม่มีลูกค้า
                </td>
            </tr>
        `;
    }
    
    tableBody.innerHTML = tableHtml;
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
    modal.show();
    
    // เก็บข้อมูลสำหรับการดาวน์โหลด
    window.currentDistributionIndex = 'request';
}

// ดาวน์โหลดรายงานการแจกตามคำขอ
function downloadRequestReport() {
    if (!window.currentRequestData) {
        showAlert('ไม่มีข้อมูลสำหรับดาวน์โหลด', 'warning');
        return;
    }
    
    const results = window.currentRequestData;
    const customers = results.customers || [];
    const timestamp = new Date().toLocaleString('th-TH');
    
    // สร้างเนื้อหา CSV
    let csvContent = '\ufeff'; // BOM สำหรับ UTF-8
    csvContent += `รายงานการแจกลูกค้าตามคำขอ\n`;
    csvContent += `วันที่: ${timestamp}\n`;
    csvContent += `Telesales: ${results.telesales_name || 'ไม่ระบุ'}\n`;
    csvContent += `บริษัท: ${(results.company || '').toUpperCase()}\n`;
    csvContent += `จำนวนลูกค้า: ${customers.length} คน\n\n`;
    
    csvContent += '#,รหัสลูกค้า,ชื่อลูกค้า,เบอร์โทร,เกรด,สถานะ,วันที่หมดอายุ\n';
    
    if (customers.length > 0) {
        customers.forEach((customer, idx) => {
            const expiry = customer.customer_time_expiry ? formatDateTime(customer.customer_time_expiry) : 'ไม่ระบุ';
            const temperatureText = getTemperatureText(customer.temperature_status || customer.temperature);
            
            csvContent += `${idx + 1},${customer.customer_code || ''},${customer.full_name || customer.name || ''},${customer.phone || ''},${customer.customer_grade || customer.grade || 'N/A'},${temperatureText},${expiry}\n`;
        });
    } else {
        csvContent += ',,ไม่มีลูกค้า,,,,\n';
    }
    
    // ดาวน์โหลดไฟล์
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `การแจกตามคำขอ_${results.telesales_name || 'Unknown'}_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert(`ดาวน์โหลดรายงานการแจกตามคำขอเสร็จสิ้น`, 'success');
}

// แสดงผลการแจกแบบเฉลี่ย (ใช้คอนเซ็ปเดียวกับเกรด A)
function displayAverageResults(results) {
    const container = document.getElementById('averageDistributionResults');
    if (!container || !results) return;

    // นับจำนวนลูกค้าทั้งหมด
    const totalCustomers = results.distributions ? 
        results.distributions.reduce((sum, dist) => sum + (dist.customers ? dist.customers.length : 0), 0) : 0;

    let html = `
        <div class="card mt-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    ผลการแจกแบบเฉลี่ยเสร็จสิ้น
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary">${totalCustomers}</h4>
                            <small class="text-muted">ลูกค้าทั้งหมด</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info">${results.distributions ? results.distributions.length : 0}</h4>
                            <small class="text-muted">Telesales</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning">${(results.company || '').toUpperCase()}</h4>
                            <small class="text-muted">บริษัท</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadAverageReport()">
                                <i class="fas fa-download me-1"></i>
                                ดาวน์โหลดรายงาน
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Telesales</th>
                                <th>บริษัท</th>
                                <th>จำนวนลูกค้า</th>
                                <th>ประเภท</th>
                                <th>เวลาที่แจก</th>
                                <th>ดูรายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>`;

    if (results.distributions && results.distributions.length > 0) {
        results.distributions.forEach((dist, index) => {
            const customers = dist.customers || [];
            html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-tie text-primary me-2"></i>
                            <strong>${dist.telesales_name}</strong>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${dist.company === 'prima' ? 'primary' : 'info'}">
                            ${(dist.company || '').toUpperCase()}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-success fs-6">
                            ${customers.length} คน
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-warning">เฉลี่ย</span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            ${new Date().toLocaleString('th-TH')}
                        </small>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="showAverageCustomerDetails(${index})" 
                                ${customers.length === 0 ? 'disabled' : ''}>
                            <i class="fas fa-eye me-1"></i>
                            ดูลูกค้า (${customers.length})
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html += `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    ยังไม่มีผลการแจก
                </td>
            </tr>
        `;
    }

    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // เก็บข้อมูลสำหรับการแสดงรายละเอียด
    window.currentAverageData = results;
}

// แสดงรายละเอียดลูกค้าที่แจกแบบเฉลี่ย
function showAverageCustomerDetails(distributionIndex) {
    if (!window.currentAverageData || !window.currentAverageData.distributions) return;
    
    const distribution = window.currentAverageData.distributions[distributionIndex];
    if (!distribution) return;
    
    const customers = distribution.customers || [];
    
    // อัปเดตข้อมูลใน modal
    document.getElementById('customerDetailsTelesalesName').textContent = distribution.telesales_name;
    document.getElementById('customerDetailsCompany').textContent = (distribution.company || '').toUpperCase();
    document.getElementById('customerDetailsCount').textContent = customers.length;
    
    // สร้างตารางลูกค้า
    const tableBody = document.getElementById('customerDetailsTableBody');
    let tableHtml = '';
    
    if (customers.length > 0) {
        customers.forEach((customer, index) => {
            tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td><code class="text-primary">${customer.customer_code || ''}</code></td>
                    <td><strong>${customer.full_name || customer.name || ''}</strong></td>
                    <td>${customer.phone || ''}</td>
                    <td>
                        <span class="badge bg-warning">${customer.customer_grade || customer.grade || 'N/A'}</span>
                    </td>
                    <td>
                        <span class="badge bg-${getTemperatureColor(customer.temperature_status || customer.temperature)}">
                            ${getTemperatureText(customer.temperature_status || customer.temperature)}
                        </span>
                    </td>
                    <td>
                        <small class="text-success">
                            <i class="fas fa-calendar-check me-1"></i>
                            ${customer.customer_time_expiry ? formatDateTime(customer.customer_time_expiry) : 'ไม่ระบุ'}
                            <br>
                            <strong class="text-primary">30 วัน</strong>
                        </small>
                    </td>
                </tr>
            `;
        });
    } else {
        tableHtml = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    ไม่มีลูกค้า
                </td>
            </tr>
        `;
    }
    
    tableBody.innerHTML = tableHtml;
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
    modal.show();
    
    // เก็บข้อมูลสำหรับการดาวน์โหลด
    window.currentDistributionIndex = distributionIndex;
    window.currentDistributionType = 'average';
}

// ดาวน์โหลดรายงานการแจกแบบเฉลี่ย
function downloadAverageReport() {
    if (!window.currentAverageData) {
        showAlert('ไม่มีข้อมูลสำหรับดาวน์โหลด', 'warning');
        return;
    }
    
    const results = window.currentAverageData;
    const distributions = results.distributions || [];
    const timestamp = new Date().toLocaleString('th-TH');
    
    // สร้างเนื้อหา CSV
    let csvContent = '\ufeff'; // BOM สำหรับ UTF-8
    csvContent += `รายงานการแจกลูกค้าแบบเฉลี่ย\n`;
    csvContent += `วันที่: ${timestamp}\n`;
    csvContent += `บริษัท: ${(results.company || '').toUpperCase()}\n`;
    csvContent += `จำนวน Telesales: ${distributions.length} คน\n`;
    csvContent += `จำนวนลูกค้าทั้งหมด: ${distributions.reduce((sum, dist) => sum + (dist.customers ? dist.customers.length : 0), 0)} คน\n\n`;
    
    csvContent += 'Telesales,จำนวน,รหัสลูกค้า,ชื่อลูกค้า,เบอร์โทร,เกรด,สถานะ,วันที่หมดอายุ\n';
    
    distributions.forEach(distribution => {
        const customers = distribution.customers || [];
        if (customers.length === 0) {
            csvContent += `${distribution.telesales_name},0,,,,,\n`;
        } else {
            customers.forEach((customer, idx) => {
                const expiry = customer.customer_time_expiry ? formatDateTime(customer.customer_time_expiry) : 'ไม่ระบุ';
                const temperatureText = getTemperatureText(customer.temperature_status || customer.temperature);
                
                csvContent += `${idx === 0 ? distribution.telesales_name : ''},${idx === 0 ? customers.length : ''},${customer.customer_code || ''},${customer.full_name || customer.name || ''},${customer.phone || ''},${customer.customer_grade || customer.grade || 'N/A'},${temperatureText},${expiry}\n`;
            });
        }
    });
    
    // ดาวน์โหลดไฟล์
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `การแจกแบบเฉลี่ย_${(results.company || 'Unknown').toUpperCase()}_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert(`ดาวน์โหลดรายงานการแจกแบบเฉลี่ยเสร็จสิ้น`, 'success');
}

// แสดง Success Modal พร้อมรายละเอียดการแจก
function showGradeASuccessModal(data) {
    if (!data || !data.distributions) return;

    // อัปเดตข้อมูลสรุป
    document.getElementById('modalTotalDistributed').textContent = data.total_distributed || 0;
    document.getElementById('modalTotalTelesales').textContent = data.distributions.length || 0;
    document.getElementById('modalCompany').textContent = (data.company || '').toUpperCase();

    // สร้างรายละเอียดการแจก
    const detailsContainer = document.getElementById('modalDistributionDetails');
    let detailsHtml = '';

    data.distributions.forEach((distribution, index) => {
        const customers = distribution.customers || [];
        const customerCount = customers.length;
        
        detailsHtml += `
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="mb-0">
                                <i class="fas fa-user-tie me-2"></i>
                                <strong>${distribution.telesales_name}</strong>
                            </h6>
                        </div>
                        <div class="col-4 text-end">
                            <span class="badge bg-success fs-6">
                                ${customerCount} ลูกค้า
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    ${customerCount > 0 ? `
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>รหัส</th>
                                                <th>ชื่อลูกค้า</th>
                                                <th>เบอร์โทร</th>
                                                <th>เกรด</th>
                                                <th>สถานะ</th>
                                                <th>วันที่หมดอายุ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${customers.map((customer, idx) => `
                                                <tr>
                                                    <td>${idx + 1}</td>
                                                    <td>
                                                        <code class="text-primary">${customer.customer_code || '-'}</code>
                                                    </td>
                                                    <td>
                                                        <strong>${customer.name || 'ไม่ระบุ'}</strong>
                                                    </td>
                                                    <td>
                                                        <a href="tel:${customer.phone}" class="text-decoration-none">
                                                            ${customer.phone || '-'}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning">
                                                            ${customer.grade || 'A'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-${getTemperatureColor(customer.temperature)}">
                                                            ${getTemperatureText(customer.temperature)}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-success">
                                                            <i class="fas fa-calendar-check me-1"></i>
                                                            ${customer.time_expiry ? formatDateTime(customer.time_expiry) : 'ไม่ระบุ'}
                                                            <br>
                                                            <strong class="text-primary">
                                                                ${customer.days_remaining || 30} วัน
                                                            </strong>
                                                        </small>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-info-circle me-2"></i>
                            ไม่มีลูกค้าที่แจกให้
                        </div>
                    `}
                </div>
            </div>
        `;
    });

    detailsContainer.innerHTML = detailsHtml;

    // เก็บข้อมูลสำหรับดาวน์โหลดรายงาน
    window.lastDistributionData = data;

    // แสดงผลการแจกในส่วนด้านล่าง (ไม่ใช้ popup modal)
    displayGradeAResults(data);
    
    // แสดงข้อความสำเร็จ
    showAlert(`แจกลูกค้าเกรด A สำเร็จ! แจก ${data.total_distributed} คนให้ ${data.distributions.length} Telesales`, 'success');
    
    // เลื่อนไปยังส่วนผลการแจก
    document.getElementById('gradeADistributionResults').scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
    });
    
    console.log('✅ Grade A distribution completed, results displayed below');
}

// Helper functions สำหรับแสดงสี temperature
function getTemperatureColor(temperature) {
    switch (temperature) {
        case 'hot': return 'danger';
        case 'warm': return 'warning';
        case 'cold': return 'info';
        default: return 'secondary';
    }
}

function getTemperatureText(temperature) {
    switch (temperature) {
        case 'hot': return '🔥 Hot';
        case 'warm': return '🌤️ Warm';
        case 'cold': return '❄️ Cold';
        default: return '⚪ Normal';
    }
}

// ฟังก์ชันจัดรูปแบบวันที่
function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'ไม่ระบุ';
    
    try {
        const date = new Date(dateTimeStr);
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Bangkok'
        };
        return date.toLocaleDateString('th-TH', options);
    } catch (e) {
        return dateTimeStr;
    }
}

// ดาวน์โหลดรายงานการแจก
function downloadDistributionReport() {
    if (!window.lastDistributionData) {
        showAlert('ไม่มีข้อมูลการแจกสำหรับดาวน์โหลด', 'warning');
        return;
    }

    const data = window.lastDistributionData;
    const timestamp = new Date().toLocaleString('th-TH');
    
    // สร้างข้อมูล CSV
    let csvContent = 'รายงานการแจกลูกค้าเกรด A\n';
    csvContent += `วันที่: ${timestamp}\n`;
    csvContent += `บริษัท: ${(data.company || '').toUpperCase()}\n`;
    csvContent += `ลูกค้าทั้งหมด: ${data.total_distributed} คน\n`;
    csvContent += `Telesales: ${data.distributions.length} คน\n\n`;
    
    csvContent += 'Telesales,จำนวน,รหัสลูกค้า,ชื่อลูกค้า,เบอร์โทร,เกรด,สถานะ,วันที่หมดอายุ,วันที่เหลือ\n';
    
    data.distributions.forEach(distribution => {
        const customers = distribution.customers || [];
        if (customers.length === 0) {
            csvContent += `${distribution.telesales_name},0,,,,,,,,\n`;
        } else {
            customers.forEach((customer, idx) => {
                const expiry = customer.time_expiry ? formatDateTime(customer.time_expiry) : 'ไม่ระบุ';
                const daysRemaining = customer.days_remaining || 30;
                csvContent += `${idx === 0 ? distribution.telesales_name : ''},${idx === 0 ? customers.length : ''},${customer.customer_code || ''},${customer.name || ''},${customer.phone || ''},${customer.grade || 'A'},${customer.temperature || ''},${expiry},${daysRemaining} วัน\n`;
            });
        }
    });

    // ดาวน์โหลดไฟล์
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `grade_a_distribution_${data.company}_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('ดาวน์โหลดรายงานเสร็จสิ้น', 'success');
}

// ฟังก์ชันแก้ปัญหา modal-backdrop
function cleanupModalBackdrops() {
    // ลบ backdrop เก่าทั้งหมด
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // ลบ class modal-open จาก body ถ้าไม่มี modal เปิดอยู่
    const openModals = document.querySelectorAll('.modal.show');
    if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
    }
}

function forceModalToFront() {
    const modal = document.getElementById('gradeASuccessModal');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    
    // แก้ปัญหา backdrop หลายตัว
    backdrops.forEach((backdrop, index) => {
        if (index === backdrops.length - 1) {
            // backdrop ตัวสุดท้าย (ของ modal นี้)
            backdrop.style.zIndex = '9998';
            backdrop.style.opacity = '0.5';
        } else {
            // backdrop ตัวอื่นๆ ลบทิ้ง
            backdrop.remove();
        }
    });
    
    if (modal) {
        // ตั้งค่า modal ให้แสดงด้านหน้าสุด
        modal.style.zIndex = '9999';
        modal.style.display = 'block';
        modal.style.opacity = '1';
        modal.style.visibility = 'visible';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.backgroundColor = 'rgba(0,0,0,0)'; // ทำให้โปร่งใส
        
        // ตั้งค่า modal-dialog
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.zIndex = '10000';
            modalDialog.style.position = 'relative';
            modalDialog.style.margin = '1.75rem auto';
            modalDialog.style.maxWidth = '800px';
        }
        
        // ตั้งค่า modal-content
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.zIndex = '10001';
            modalContent.style.position = 'relative';
            modalContent.style.backgroundColor = 'white';
            modalContent.style.border = '1px solid #dee2e6';
            modalContent.style.borderRadius = '0.375rem';
            modalContent.style.boxShadow = '0 10px 40px rgba(0,0,0,0.3)';
        }
        
        // ตรวจสอบว่า modal แสดงอย่างถูกต้อง
        console.log('Modal forced to front:', {
            modal: modal.style.zIndex,
            backdrops: backdrops.length,
            display: modal.style.display,
            visible: modal.style.visibility
        });
    }
}

// เพิ่ม event listener สำหรับ modal events
document.addEventListener('DOMContentLoaded', function() {
    const gradeAModal = document.getElementById('gradeASuccessModal');
    if (gradeAModal) {
        gradeAModal.addEventListener('show.bs.modal', function() {
            cleanupModalBackdrops();
        });
        
        gradeAModal.addEventListener('shown.bs.modal', function() {
            forceModalToFront();
        });
        
        gradeAModal.addEventListener('hidden.bs.modal', function() {
            cleanupModalBackdrops();
            // รีเซ็ต modal styles
            gradeAModal.style.zIndex = '';
            gradeAModal.style.display = '';
            gradeAModal.style.opacity = '';
            gradeAModal.style.visibility = '';
            gradeAModal.style.position = '';
            gradeAModal.style.backgroundColor = '';
        });
    }
    
    // ตรวจสอบและแก้ปัญหา backdrop ทุก 100ms (ถ้ามี modal เปิดอยู่)
    setInterval(() => {
        const gradeAModal = document.getElementById('gradeASuccessModal');
        if (gradeAModal && gradeAModal.classList.contains('show')) {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                console.log('🧹 Backdrop detected, removing...', backdrops.length);
                backdrops.forEach(backdrop => backdrop.remove());
            }
            
            // ตรวจสอบ z-index
            if (gradeAModal.style.zIndex !== '99999') {
                gradeAModal.style.zIndex = '99999';
                console.log('🔧 Fixed modal z-index');
            }
        }
    }, 100);
});
</script>
