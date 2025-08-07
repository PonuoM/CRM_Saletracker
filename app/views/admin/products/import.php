<?php
/**
 * Product Management - Import Products
 * หน้านำเข้าสินค้าจากไฟล์ CSV
 */

$user = $_SESSION['user'] ?? null;
$error = $error ?? '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าสินค้า - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 page-transition">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-upload me-2"></i>
                        นำเข้าสินค้า
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="admin.php?action=products" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>กลับไปรายการสินค้า
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Import Form -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-file-upload me-2"></i>อัปโหลดไฟล์ CSV
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="importForm">
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">
                                            <i class="fas fa-file-csv me-1"></i>เลือกไฟล์ CSV <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                                               accept=".csv" required>
                                        <div class="form-text">รองรับไฟล์ CSV เท่านั้น ขนาดไม่เกิน 5MB</div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="skip_header" name="skip_header" checked>
                                            <label class="form-check-label" for="skip_header">
                                                ข้ามแถวหัวข้อ (Header Row)
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing">
                                            <label class="form-check-label" for="update_existing">
                                                อัปเดตสินค้าที่มีอยู่แล้ว (ใช้รหัสสินค้าเป็นตัวอ้างอิง)
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="admin.php?action=products" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>นำเข้าสินค้า
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-info">
                                    <i class="fas fa-info-circle me-2"></i>ข้อมูลการนำเข้า
                                </h6>
                            </div>
                            <div class="card-body">
                                <h6>รูปแบบไฟล์ CSV ที่รองรับ:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>รหัสสินค้า</li>
                                    <li><i class="fas fa-check text-success me-2"></i>ชื่อสินค้า</li>
                                    <li><i class="fas fa-check text-success me-2"></i>หมวดหมู่</li>
                                    <li><i class="fas fa-check text-success me-2"></i>หน่วย</li>
                                    <li><i class="fas fa-check text-success me-2"></i>ต้นทุน</li>
                                    <li><i class="fas fa-check text-success me-2"></i>ราคาขาย</li>
                                    <li><i class="fas fa-check text-success me-2"></i>จำนวนคงเหลือ</li>
                                    <li><i class="fas fa-check text-success me-2"></i>รายละเอียด</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>ข้อกำหนด:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>รหัสสินค้าต้องไม่ซ้ำกัน</li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>ชื่อสินค้าไม่ควรว่าง</li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>ราคาต้องเป็นตัวเลข</li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>จำนวนคงเหลือต้องเป็นตัวเลข</li>
                                </ul>
                                
                                <hr>
                                
                                <a href="templates/products_template.csv" class="btn btn-outline-info btn-sm w-100">
                                    <i class="fas fa-download me-2"></i>ดาวน์โหลดเทมเพลต
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
        $(document).ready(function() {
            // File validation
            $('#csv_file').on('change', function() {
                const file = this.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) { // 5MB
                        alert('ไฟล์มีขนาดใหญ่เกินไป กรุณาเลือกไฟล์ที่มีขนาดไม่เกิน 5MB');
                        this.value = '';
                        return;
                    }
                    
                    if (!file.name.toLowerCase().endsWith('.csv')) {
                        alert('กรุณาเลือกไฟล์ CSV เท่านั้น');
                        this.value = '';
                        return;
                    }
                }
            });
            
            // Form validation
            $('#importForm').on('submit', function(e) {
                const file = $('#csv_file')[0].files[0];
                if (!file) {
                    e.preventDefault();
                    alert('กรุณาเลือกไฟล์ CSV');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
