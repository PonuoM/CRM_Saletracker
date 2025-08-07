<?php
/**
 * Product Management - Export Products
 * หน้าส่งออกสินค้าเป็นไฟล์ CSV
 */

$user = $_SESSION['user'] ?? null;
$error = $error ?? '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งออกสินค้า - CRM SalesTracker</title>
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
                        <i class="fas fa-download me-2"></i>
                        ส่งออกสินค้า
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

                <!-- Export Options -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-cog me-2"></i>ตัวเลือกการส่งออก
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="exportForm">
                                    <!-- Export Format -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-file me-1"></i>รูปแบบไฟล์
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format" id="format_csv" value="csv" checked>
                                            <label class="form-check-label" for="format_csv">
                                                <i class="fas fa-file-csv text-success me-2"></i>CSV (Comma Separated Values)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format" id="format_excel" value="excel">
                                            <label class="form-check-label" for="format_excel">
                                                <i class="fas fa-file-excel text-success me-2"></i>Excel (.xlsx)
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Filter Options -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-filter me-1"></i>ตัวกรองข้อมูล
                                        </label>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="category_filter" class="form-label">หมวดหมู่</label>
                                                <select class="form-select" id="category_filter" name="category_filter">
                                                    <option value="">ทุกหมวดหมู่</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                                            <?php echo htmlspecialchars($category); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="status_filter" class="form-label">สถานะ</label>
                                                <select class="form-select" id="status_filter" name="status_filter">
                                                    <option value="">ทุกสถานะ</option>
                                                    <option value="active">เปิดใช้งาน</option>
                                                    <option value="inactive">ปิดใช้งาน</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Filter -->
                                    <div class="mb-3">
                                        <label for="stock_filter" class="form-label">กรองตามสต็อก</label>
                                        <select class="form-select" id="stock_filter" name="stock_filter">
                                            <option value="">ทุกสินค้า</option>
                                            <option value="in_stock">มีสต็อก</option>
                                            <option value="out_of_stock">หมดสต็อก</option>
                                            <option value="low_stock">สต็อกต่ำ (น้อยกว่า 10)</option>
                                        </select>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-calendar me-1"></i>ช่วงวันที่
                                        </label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="date_from" class="form-label">จากวันที่</label>
                                                <input type="date" class="form-control" id="date_from" name="date_from">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="date_to" class="form-label">ถึงวันที่</label>
                                                <input type="date" class="form-control" id="date_to" name="date_to">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Columns to Export -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-columns me-1"></i>คอลัมน์ที่ต้องการส่งออก
                                        </label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_id" name="columns[]" value="product_id" checked>
                                                    <label class="form-check-label" for="col_id">ID</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_code" name="columns[]" value="product_code" checked>
                                                    <label class="form-check-label" for="col_code">รหัสสินค้า</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_name" name="columns[]" value="product_name" checked>
                                                    <label class="form-check-label" for="col_name">ชื่อสินค้า</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_category" name="columns[]" value="category" checked>
                                                    <label class="form-check-label" for="col_category">หมวดหมู่</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_unit" name="columns[]" value="unit" checked>
                                                    <label class="form-check-label" for="col_unit">หน่วย</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_cost" name="columns[]" value="cost_price" checked>
                                                    <label class="form-check-label" for="col_cost">ต้นทุน</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_price" name="columns[]" value="selling_price" checked>
                                                    <label class="form-check-label" for="col_price">ราคาขาย</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="col_stock" name="columns[]" value="stock_quantity" checked>
                                                    <label class="form-check-label" for="col_stock">จำนวนคงเหลือ</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="admin.php?action=products" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-download me-2"></i>ส่งออกสินค้า
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
                                    <i class="fas fa-info-circle me-2"></i>ข้อมูลการส่งออก
                                </h6>
                            </div>
                            <div class="card-body">
                                <h6>สถิติสินค้า:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-box text-primary me-2"></i>สินค้าทั้งหมด: <strong><?php echo $totalProducts; ?></strong></li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>เปิดใช้งาน: <strong><?php echo $activeProducts; ?></strong></li>
                                    <li><i class="fas fa-times-circle text-danger me-2"></i>ปิดใช้งาน: <strong><?php echo $inactiveProducts; ?></strong></li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>หมดสต็อก: <strong><?php echo $outOfStockProducts; ?></strong></li>
                                </ul>
                                
                                <hr>
                                
                                <h6>หมวดหมู่สินค้า:</h6>
                                <ul class="list-unstyled small">
                                    <?php foreach ($categories as $category): ?>
                                        <li><i class="fas fa-folder text-info me-2"></i><?php echo htmlspecialchars($category); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <hr>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>เคล็ดลับ:</strong> ใช้ตัวกรองเพื่อส่งออกเฉพาะข้อมูลที่ต้องการ
                                </div>
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
            // Select all columns by default
            $('input[name="columns[]"]').prop('checked', true);
            
            // Date validation
            $('#date_from, #date_to').on('change', function() {
                const dateFrom = $('#date_from').val();
                const dateTo = $('#date_to').val();
                
                if (dateFrom && dateTo && dateFrom > dateTo) {
                    alert('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด');
                    $(this).val('');
                }
            });
            
            // Form validation
            $('#exportForm').on('submit', function(e) {
                const selectedColumns = $('input[name="columns[]"]:checked').length;
                if (selectedColumns === 0) {
                    e.preventDefault();
                    alert('กรุณาเลือกคอลัมน์ที่ต้องการส่งออกอย่างน้อย 1 คอลัมน์');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
