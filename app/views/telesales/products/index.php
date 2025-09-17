<?php
/**
 * Telesales Product Management - List Products
 * แสดงรายการสินค้าสำหรับ telesales
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-box me-2"></i>
        จัดการสินค้า
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="telesales.php?action=products&subaction=create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
            </a>
            <a href="telesales.php?action=products&subaction=import" class="btn btn-info">
                <i class="fas fa-upload me-2"></i>นำเข้า
            </a>
            <a href="telesales.php?action=products&subaction=export" class="btn btn-success">
                <i class="fas fa-download me-2"></i>ส่งออก
            </a>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $message = $_GET['message'];
    $alertClass = 'alert-success';
    $alertMessage = '';

    switch ($message) {
        case 'product_created':
            $alertMessage = 'สร้างสินค้าใหม่สำเร็จ';
            break;
        case 'product_updated':
            $alertMessage = 'อัปเดตข้อมูลสินค้าสำเร็จ';
            break;
        case 'product_deleted':
            $alertMessage = 'ลบสินค้าสำเร็จ';
            break;
        case 'products_imported':
            $count = $_GET['count'] ?? 0;
            $alertMessage = "นำเข้าสินค้า $count รายการสำเร็จ";
            break;
        default:
            $alertMessage = $message;
            break;
    }
    ?>
    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $alertMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Products Table -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>รายการสินค้า
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($products)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="productsTable">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>รายละเอียด</th>
                            <th>ราคา</th>
                            <th>หมวดหมู่</th>
                            <th>หน่วย</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($product['product_code']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">฿<?php echo number_format($product['price'], 2); ?></span>
                                </td>
                                <td>
                                    <?php if ($product['category']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($product['unit']); ?>
                                </td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge bg-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="telesales.php?action=products&subaction=edit&id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="telesales.php?action=products&subaction=delete&id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="ลบ"
                                           onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">ยังไม่มีสินค้า</h5>
                <p class="text-muted">เริ่มต้นด้วยการเพิ่มสินค้าใหม่</p>
                <a href="telesales.php?action=products&subaction=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#productsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        },
        "pageLength": 25,
        "order": [[1, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});
</script>
