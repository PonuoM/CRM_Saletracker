<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้า/ส่งออกข้อมูล - CRM Sales Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <?php include __DIR__ . '/../components/header.php'; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-exchange-alt me-2"></i>
                        นำเข้า/ส่งออกข้อมูล
                    </h1>
                </div>

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
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="importExportTabContent">
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
                                        <form id="importSalesForm" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="salesCsvFile" class="form-label">เลือกไฟล์ CSV</label>
                                                <input type="file" class="form-control" id="salesCsvFile" name="csv_file" accept=".csv" required>
                                                <div class="form-text">รองรับไฟล์ CSV เท่านั้น</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <a href="import-export.php?action=downloadTemplate&type=sales" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-download me-1"></i>
                                                    ดาวน์โหลด Template ยอดขาย
                                                </a>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-1"></i>
                                                นำเข้ายอดขาย
                                            </button>
                                        </form>
                                        
                                        <div id="salesImportResults" class="mt-3" style="display: none;">
                                            <div class="alert" role="alert">
                                                <div id="salesImportMessage"></div>
                                                <div id="salesImportDetails" class="mt-2"></div>
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
                                        <form id="importCustomersOnlyForm" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="customersOnlyCsvFile" class="form-label">เลือกไฟล์ CSV</label>
                                                <input type="file" class="form-control" id="customersOnlyCsvFile" name="csv_file" accept=".csv" required>
                                                <div class="form-text">รองรับไฟล์ CSV เท่านั้น</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <a href="import-export.php?action=downloadTemplate&type=customers_only" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-download me-1"></i>
                                                    ดาวน์โหลด Template รายชื่อ
                                                </a>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-upload me-1"></i>
                                                นำเข้าส่วนรายชื่อ
                                            </button>
                                        </form>
                                        
                                        <div id="customersOnlyImportResults" class="mt-3" style="display: none;">
                                            <div class="alert" role="alert">
                                                <div id="customersOnlyImportMessage"></div>
                                                <div id="customersOnlyImportDetails" class="mt-2"></div>
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
                                                    <li>✅ <strong>ข้อมูลที่ต้องมี:</strong> ชื่อ, เบอร์โทร, สินค้า, จำนวน, ราคา, ยอดรวม</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>👥 นำเข้าเฉพาะรายชื่อ:</h6>
                                                <ul class="list-unstyled">
                                                    <li>✅ <strong>ไม่มียอดขาย</strong> → เข้าตะกร้าแจก</li>
                                                    <li>✅ <strong>ชื่อซ้ำ</strong> → ตัดออก</li>
                                                    <li>✅ <strong>ติดตามอยู่แล้ว</strong> → ตัดออก</li>
                                                    <li>✅ <strong>ข้อมูลที่ต้องมี:</strong> ชื่อ, เบอร์โทร</li>
                                                </ul>
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
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/import-export.js"></script>
</body>
</html> 