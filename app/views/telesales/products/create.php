<?php
/**
 * Telesales Product Management - Create Product
 * สร้างสินค้าใหม่สำหรับ telesales
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-plus me-2"></i>
        สร้างสินค้าใหม่
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="telesales.php?action=products" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>กลับไปรายการสินค้า
        </a>
    </div>
</div>

<!-- Error Messages -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Create Product Form -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-edit me-2"></i>ข้อมูลสินค้าใหม่
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="telesales.php?action=products&subaction=create">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="product_code" class="form-label">
                            <i class="fas fa-barcode me-1"></i>รหัสสินค้า <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="product_code" name="product_code" 
                               value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>" 
                               required>
                        <div class="form-text">รหัสสินค้าต้องไม่ซ้ำกับสินค้าอื่น</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">
                            <i class="fas fa-tag me-1"></i>ชื่อสินค้า <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="product_name" name="product_name" 
                               value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">
                            <i class="fas fa-folder me-1"></i>หมวดหมู่
                        </label>
                        <select class="form-select" id="category" name="category">
                            <option value="">เลือกหมวดหมู่</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                                        <?php echo (($_POST['category'] ?? '') === $category['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="unit" class="form-label">
                            <i class="fas fa-ruler me-1"></i>หน่วย
                        </label>
                        <select class="form-select" id="unit" name="unit">
                            <option value="">เลือกหน่วย</option>
                            <option value="ชิ้น" <?php echo (($_POST['unit'] ?? '') === 'ชิ้น') ? 'selected' : ''; ?>>ชิ้น</option>
                            <option value="แพ็ค" <?php echo (($_POST['unit'] ?? '') === 'แพ็ค') ? 'selected' : ''; ?>>แพ็ค</option>
                            <option value="กล่อง" <?php echo (($_POST['unit'] ?? '') === 'กล่อง') ? 'selected' : ''; ?>>กล่อง</option>
                            <option value="กิโลกรัม" <?php echo (($_POST['unit'] ?? '') === 'กิโลกรัม') ? 'selected' : ''; ?>>กิโลกรัม</option>
                            <option value="ลิตร" <?php echo (($_POST['unit'] ?? '') === 'ลิตร') ? 'selected' : ''; ?>>ลิตร</option>
                            <option value="เมตร" <?php echo (($_POST['unit'] ?? '') === 'เมตร') ? 'selected' : ''; ?>>เมตร</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="price" class="form-label">
                            <i class="fas fa-money-bill me-1"></i>ราคา <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">฿</span>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo $_POST['price'] ?? ''; ?>" 
                                   min="0" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-toggle-on me-1"></i>สถานะ
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                            <label class="form-check-label" for="is_active">
                                ใช้งาน
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">
                    <i class="fas fa-align-left me-1"></i>รายละเอียด
                </label>
                <textarea class="form-control" id="description" name="description" rows="3" 
                          placeholder="รายละเอียดเพิ่มเติมของสินค้า"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <a href="telesales.php?action=products" class="btn btn-secondary me-2">
                    <i class="fas fa-times me-2"></i>ยกเลิก
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>บันทึกสินค้า
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-generate product code if empty
    $('#product_name').on('blur', function() {
        if (!$('#product_code').val()) {
            const productName = $(this).val();
            if (productName) {
                const code = productName.replace(/\s+/g, '').toUpperCase().substring(0, 10);
                $('#product_code').val(code);
            }
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        const productCode = $('#product_code').val().trim();
        const productName = $('#product_name').val().trim();
        const price = $('#price').val();
        
        if (!productCode || !productName || !price) {
            e.preventDefault();
            alert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            return false;
        }
        
        if (parseFloat(price) < 0) {
            e.preventDefault();
            alert('ราคาต้องมากกว่าหรือเท่ากับ 0');
            return false;
        }
    });
});
</script>
