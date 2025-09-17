<?php
/**
 * Import/Export Index
 * หน้านำเข้า/ส่งออกข้อมูล
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-exchange-alt me-2"></i>
        นำเข้า/ส่งออกข้อมูล
    </h1>
</div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['upload_success'])): ?>
                    <div class="alert alert-success alert-dismissible show permanent-alert" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['upload_success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                    </div>
                    <?php unset($_SESSION['upload_success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['upload_error'])): ?>
                    <div class="alert alert-danger alert-dismissible show permanent-alert" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['upload_error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                    </div>
                    <?php unset($_SESSION['upload_error']); ?>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="importExportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button" role="tab">
                            <i class="fas fa-upload me-2"></i>นำเข้าข้อมูล
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button" role="tab">
                            <i class="fas fa-download me-2"></i>ส่งออกข้อมูล
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                            <i class="fas fa-database me-2"></i>Backup/Restore
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="calllogs-tab" data-bs-toggle="tab" data-bs-target="#calllogs" type="button" role="tab">
                            <i class="fas fa-phone me-2"></i>ประวัติการโทร
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="importExportTabContent">
                    <?php if (($roleName ?? '') === 'super_admin'): ?>
                    <div class="alert alert-secondary d-flex align-items-center justify-content-between mt-3">
                        <div>
                            <i class="fas fa-building me-2"></i>
                            <strong>บริษัทปลายทาง:</strong>
                            <select class="form-select d-inline-block w-auto ms-2" form="importSalesForm" name="company_override_id">
                                <option value="">เลือกบริษัท...</option>
                                <?php foreach (($companies ?? []) as $co): ?>
                                    <option value="<?php echo (int)$co['company_id']; ?>"><?php echo htmlspecialchars($co['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                            <?php if (($roleName ?? '') === 'super_admin'): ?>
                            <input type="hidden" form="importCustomersOnlyForm" name="company_override_id" id="companyOverrideHidden">
                            <script>
                            // Keep both forms in sync: when top dropdown changes, update hidden input of customers-only form
                            (function(){
                                const topSelect = document.querySelector('select[name="company_override_id"][form="importSalesForm"]');
                                const hidden = document.getElementById('companyOverrideHidden');
                                if (topSelect && hidden) {
                                    const sync = () => { hidden.value = topSelect.value; };
                                    topSelect.addEventListener('change', sync);
                                    sync();
                                }
                            })();
                            </script>
                            <?php endif; ?>
                        <small class="text-muted">ไม่เลือก = ใช้บริษัทของผู้ใช้งาน</small>
                    </div>
                    <?php endif; ?>
                    <!-- Import Tab -->
                    <div class="tab-pane fade show active" id="import" role="tabpanel">
                        <div class="row mt-4">
                            <!-- นำเข้ายอดขาย -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            นำเข้ายอดขาย
                                        </h5>
                                    </div>
                                    <div class="card-body">

                                        <form id="importSalesForm" class="ajax-form no-transition" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="salesCsvFile" class="form-label">เลือกไฟล์ CSV</label>
                                                <input type="file" class="form-control" id="salesCsvFile" name="csv_file" accept=".csv" required>
                                                <div class="form-text">รองรับไฟล์ CSV เท่านั้น</div>
                                            </div>

                                            <div class="mb-3">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadTemplate('sales')">
                                                    <i class="fas fa-download me-1"></i>
                                                    ดาวน์โหลด Template ยอดขาย (แบบเต็ม)
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="downloadTemplate('sales_simple')">
                                                    <i class="fas fa-download me-1"></i>
                                                    ดาวน์โหลด Template ยอดขาย (แบบง่าย)
                                                </button>
                                            </div>

                                            <div class="mb-3">
                                                <label for="salesCustomerStatus" class="form-label">สถานะลูกค้า</label>
                                                <select class="form-select" id="salesCustomerStatus" name="customer_status">
                                                    <option value="">ยึดตามกฎอัตโนมัติ</option>
                                                    <option value="new">ลูกค้าใหม่</option>
                                                    <option value="existing">ลูกค้าเก่า</option>
                                                    <option value="followup">ติดตาม</option>
                                                    <option value="call_followup">ติดตามโทร</option>
                                                    <option value="daily_distribution">แจกรายวัน</option>
                                                </select>
                                                <div class="form-text">ถ้าไม่เลือก ระบบจะตั้งตามกฎ (มีผู้ติดตาม = existing, ไม่มี = new)</div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="updateCustomerTimeExpiry" name="update_customer_time_expiry" value="1">
                                                    <label class="form-check-label" for="updateCustomerTimeExpiry">
                                                        <strong>อัปเดตวันคงเหลือเป็น 90 วัน</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <strong>ติ๊ก:</strong> สำหรับข้อมูลใหม่ - อัปเดตวันคงเหลือเป็น 90 วัน<br>
                                                    <strong>ไม่ติ๊ก:</strong> สำหรับข้อมูลเก่า - เพิ่มประวัติและอัปเดตสถานะเป็น "ลูกค้าเก่า 3 เดือน" (เฉพาะในกรอบ 90 วัน)
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-1"></i>
                                                นำเข้ายอดขาย
                                            </button>

                                            <a href="test_import_dry_run.php" class="btn btn-warning ms-2">
                                                <i class="fas fa-flask me-1"></i>
                                                ทดสอบการนำเข้า (Dry Run)
                                            </a>
                                        </form>

                                        <div id="salesImportResults" class="mt-3" style="display: none;">
                                            <div class="alert alert-dismissible" role="alert">
                                                <div id="salesImportMessage"></div>
                                                <div id="salesImportDetails" class="mt-2"></div>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- นำเข้าเฉพาะรายชื่อ -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-users me-2"></i>
                                            นำเข้าเฉพาะรายชื่อ
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                         <form id="importCustomersOnlyForm" class="ajax-form no-transition" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="customersOnlyCsvFile" class="form-label">เลือกไฟล์ CSV</label>
                                                <input type="file" class="form-control" id="customersOnlyCsvFile" name="csv_file" accept=".csv" required>
                                                <div class="form-text">รองรับไฟล์ CSV เท่านั้น</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="customerStatusSelect" class="form-label">สถานะลูกค้า</label>
                                                <select class="form-select" id="customerStatusSelect" name="customer_status">
                                                    <option value="new">ลูกค้าใหม่</option>
                                                    <option value="existing">ลูกค้าเก่า</option>
                                                    <option value="followup">ติดตาม</option>
                                                    <option value="call_followup">ติดตามโทร</option>
                                                    <option value="daily_distribution">แจกรายวัน</option>
                                                </select>
                                                <div class="form-text">สถานะที่จะตั้งให้กับลูกค้าที่นำเข้า</div>
                                            </div>

                                            <div class="mb-3">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadTemplate('customers_only')">
                                                    <i class="fas fa-download me-1"></i>
                                                    ดาวน์โหลด Template รายชื่อ
                                                </button>
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-upload me-1"></i>
                                                นำเข้าส่วนรายชื่อ
                                            </button>
                                        </form>

                                        <div id="customersOnlyImportResults" class="mt-3" style="display: none;">
                                            <div class="alert alert-dismissible" role="alert">
                                                <div id="customersOnlyImportMessage"></div>
                                                <div id="customersOnlyImportDetails" class="mt-2"></div>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- คำแนะนำ -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            คำแนะนำการนำเข้าข้อมูล
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>📊 นำเข้ายอดขาย:</h6>
                                                <ul class="list-unstyled">
                                                    <li>✅ <strong>มีรายชื่ออยู่แล้ว</strong> → อัพเดทยอดขายเท่านั้น</li>
                                                    <li>✅ <strong>ไม่มีรายชื่อ</strong> → เพิ่มทั้งรายชื่อ + ยอดขาย → เข้าตะกร้าแจก</li>
                                                    <li>✅ <strong>ข้อมูลที่ต้องมี:</strong> ชื่อ, เบอร์โทร, รหัสสินค้า, ชื่อสินค้า, จำนวน, ยอดรวม</li>
                                                    <li>✅ <strong>Template แบบเต็ม:</strong> มีคอลัมน์ "ราคาต่อชิ้น" และ "ยอดรวม"</li>
                                                    <li>✅ <strong>Template แบบง่าย:</strong> มีแค่คอลัมน์ "ยอดรวม" (ระบบจะคำนวณราคาต่อชิ้นให้อัตโนมัติ)</li>
                                                    <li>✅ <strong>ฟิลด์ใหม่:</strong> ผู้ติดตาม (ชื่อหรือรหัสพนักงาน)</li>
                                                    <li>✅ <strong>ฟิลด์ใหม่:</strong> ผู้ขาย (ชื่อหรือรหัสพนักงาน) - เพื่อเป็นผลงานของคนนั้น</li>
                                                    <li>✅ <strong>ฟิลด์ใหม่:</strong> วิธีการชำระเงิน, สถานะการชำระเงิน</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>👥 นำเข้าเฉพาะรายชื่อ:</h6>
                                                <ul class="list-unstyled">
                                                    <li>✅ <strong>ไม่มียอดขาย</strong> → เข้าตะกร้าแจก</li>
                                                    <li>✅ <strong>ชื่อซ้ำ</strong> → ตัดออก</li>
                                                    <li>✅ <strong>ติดตามอยู่แล้ว</strong> → ตัดออก</li>
                                                    <li>✅ <strong>ข้อมูลที่ต้องมี:</strong> ชื่อ, เบอร์โทร</li>
                                                    <li>✅ <strong>ฟิลด์ใหม่:</strong> รหัสสินค้า, ผู้ติดตาม (ชื่อหรือรหัสพนักงาน)</li>
                                                    <li>🔥 <strong>ฟีเจอร์ใหม่:</strong> วันคงเหลือ (ไม่ใส่ = 30 วัน)</li>
                                                    <li>🔥 <strong>สถานะ:</strong> ลูกค้าร้อน (Hot) + ลูกค้าใหม่ เสมอ</li>
                                                </ul>
                                            </div>
                                        </div>

                                                                        <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-lightbulb me-2"></i>ฟิลด์ใหม่ที่เพิ่มเข้ามา:</h6>
                                            <ul class="mb-0">
                                                <li><strong>รหัสสินค้า:</strong> รหัสสินค้าที่ลูกค้าสนใจ (เช่น P001, P002)</li>
                                                <li><strong>ผู้ติดตาม:</strong> ชื่อพนักงานหรือรหัสพนักงานที่จะติดตามลูกค้า</li>
                                                <li><strong>ผู้ขาย:</strong> ชื่อพนักงานหรือรหัสพนักงานที่เป็นผู้ขาย (เพื่อเป็นผลงานของคนนั้น)</li>
                                                <li><strong>รหัสไปรษณีย์:</strong> มีอยู่แล้วในระบบ</li>
                                                <li><strong>วิธีการชำระเงิน:</strong> เงินสด, โอนเงิน, เก็บเงินปลายทาง, รับสินค้าก่อนชำระ, เครดิต, อื่นๆ</li>
                                                <li><strong>สถานะการชำระเงิน:</strong> รอดำเนินการ, ชำระแล้ว, ชำระบางส่วน, ยกเลิก</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Tab -->
                    <div class="tab-pane fade" id="export" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-users me-2"></i>
                                            ส่งออกข้อมูลลูกค้า
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="exportCustomersForm" method="post" action="import-export.php?action=exportCustomers">
                                            <div class="mb-3">
                                                <label for="customerStatus" class="form-label">สถานะ</label>
                                                <select class="form-select" id="customerStatus" name="status">
                                                    <option value="">ทั้งหมด</option>
                                                    <option value="active">ใช้งาน</option>
                                                    <option value="inactive">ไม่ใช้งาน</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="customerTemperature" class="form-label">อุณหภูมิ</label>
                                                <select class="form-select" id="customerTemperature" name="temperature">
                                                    <option value="">ทั้งหมด</option>
                                                    <option value="hot">ร้อน</option>
                                                    <option value="warm">อุ่น</option>
                                                    <option value="cold">เย็น</option>
                                                    <option value="frozen">แช่แข็ง</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="customerGrade" class="form-label">เกรด</label>
                                                <select class="form-select" id="customerGrade" name="grade">
                                                    <option value="">ทั้งหมด</option>
                                                    <option value="A+">A+</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                </select>
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-download me-1"></i>
                                                ส่งออก CSV
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            ส่งออกคำสั่งซื้อ
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="exportOrdersForm" method="post" action="import-export.php?action=exportOrders">
                                            <div class="mb-3">
                                                <label for="orderStatus" class="form-label">สถานะการจัดส่ง</label>
                                                <select class="form-select" id="orderStatus" name="delivery_status">
                                                    <option value="">ทั้งหมด</option>
                                                    <option value="pending">รอดำเนินการ</option>
                                                    <option value="processing">กำลังดำเนินการ</option>
                                                    <option value="shipped">จัดส่งแล้ว</option>
                                                    <option value="delivered">จัดส่งสำเร็จ</option>
                                                    <option value="cancelled">ยกเลิก</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="startDate" class="form-label">วันที่เริ่มต้น</label>
                                                <input type="date" class="form-control" id="startDate" name="start_date">
                                            </div>

                                            <div class="mb-3">
                                                <label for="endDate" class="form-label">วันที่สิ้นสุด</label>
                                                <input type="date" class="form-control" id="endDate" name="end_date">
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-download me-1"></i>
                                                ส่งออก CSV
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar me-2"></i>
                                            รายงานสรุป
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="exportSummaryForm" method="post" action="import-export.php?action=exportSummaryReport">
                                            <div class="mb-3">
                                                <label for="summaryStartDate" class="form-label">วันที่เริ่มต้น</label>
                                                <input type="date" class="form-control" id="summaryStartDate" name="start_date">
                                            </div>

                                            <div class="mb-3">
                                                <label for="summaryEndDate" class="form-label">วันที่สิ้นสุด</label>
                                                <input type="date" class="form-control" id="summaryEndDate" name="end_date">
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-download me-1"></i>
                                                ส่งออกรายงาน
                                            </button>
                                        </form>

                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                รายงานจะรวมสถิติลูกค้า คำสั่งซื้อ และรายได้
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Tab -->
                    <div class="tab-pane fade" id="backup" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-save me-2"></i>
                                            สร้าง Backup
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">สร้างไฟล์ backup ฐานข้อมูลทั้งหมด</p>

                                        <button type="button" class="btn btn-primary" id="createBackupBtn">
                                            <i class="fas fa-database me-1"></i>
                                            สร้าง Backup
                                        </button>

                                        <div id="backupResult" class="mt-3" style="display: none;">
                                            <div class="alert" role="alert">
                                                <div id="backupMessage"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-undo me-2"></i>
                                            Restore จาก Backup
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="backupFile" class="form-label">เลือกไฟล์ Backup</label>
                                            <select class="form-select" id="backupFile" name="backup_file">
                                                <option value="">เลือกไฟล์...</option>
                                                <?php foreach ($backupFiles as $file): ?>
                                                    <option value="<?php echo htmlspecialchars($file['name']); ?>">
                                                        <?php echo htmlspecialchars($file['name']); ?>
                                                        (<?php echo number_format($file['size'] / 1024, 2); ?> KB)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <button type="button" class="btn btn-warning" id="restoreBackupBtn" disabled>
                                            <i class="fas fa-undo me-1"></i>
                                            Restore
                                        </button>

                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>คำเตือน:</strong> การ Restore จะทับข้อมูลปัจจุบันทั้งหมด
                                        </div>

                                        <div id="restoreResult" class="mt-3" style="display: none;">
                                            <div class="alert" role="alert">
                                                <div id="restoreMessage"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Backup Files List -->
                        <?php if (!empty($backupFiles)): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-list me-2"></i>
                                            ไฟล์ Backup ที่มีอยู่
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ชื่อไฟล์</th>
                                                        <th>ขนาด</th>
                                                        <th>วันที่สร้าง</th>
                                                        <th>การดำเนินการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($backupFiles as $file): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($file['name']); ?></td>
                                                            <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                                                            <td><?php echo $file['date']; ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary restore-file-btn"
                                                                        data-file="<?php echo htmlspecialchars($file['name']); ?>">
                                                                    <i class="fas fa-undo me-1"></i>
                                                                    Restore
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Call Logs Tab -->
                    <div class="tab-pane fade" id="calllogs" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-phone me-2"></i>นำเข้าประวัติการโทร</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="importCallLogsForm" class="ajax-form no-transition" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label class="form-label" for="callLogsCsvFile">เลือกไฟล์ CSV</label>
                                                <input type="file" class="form-control" id="callLogsCsvFile" name="csv_file" accept=".csv" required>
                                                <div class="form-text">หัวตาราง: customer_code, call_type, call_status, call_result, duration_minutes, notes, next_action, next_followup_at, called_at, recorded_by</div>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadTemplate('call_logs')"><i class="fas fa-download me-1"></i>ดาวน์โหลด Template ประวัติการโทร</button>
                                            </div>
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>นำเข้า Call Logs</button>
                                        </form>
                                        <div id="callLogsImportResults" class="mt-3" style="display:none;">
                                            <div class="alert" role="alert">
                                                <div id="callLogsImportMessage"></div>
                                                <div id="callLogsImportDetails" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script src="assets/js/import-export.js"></script>

    <script>
    /**
     * Download template and show success message
     */
    function downloadTemplate(type) {
        // Show loading state
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังดาวน์โหลด...';
        button.disabled = true;

        // Create download link
        const link = document.createElement('a');
        link.href = `import-export.php?action=downloadTemplate&type=${type}`;
        link.style.display = 'none';
        document.body.appendChild(link);

        // Trigger download
        link.click();
        document.body.removeChild(link);

        // Show success message immediately
        const templateNames = {
            'sales': 'Template ยอดขาย (แบบเต็ม)',
            'sales_simple': 'Template ยอดขาย (แบบง่าย)',
            'customers_only': 'Template รายชื่อ',
            'customers': 'Template ลูกค้า'
        };
        const templateName = templateNames[type] || 'Template';

        showPageMessage(`ดาวน์โหลด${templateName} สำเร็จแล้ว`, 'success');

        // Reset button after a short delay
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 1000);
    }
    </script>

    <style>
    /* Prevent automatic fade out of permanent alerts */
    .permanent-alert {
        animation: none !important;
    }
    
    .permanent-alert.fade {
        opacity: 1 !important;
    }
    
    /* Ensure permanent alerts stay visible */
    .permanent-alert.show {
        display: block !important;
        opacity: 1 !important;
    }
    </style>
