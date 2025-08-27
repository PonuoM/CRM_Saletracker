
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-plus me-2"></i>
                        เพิ่มสินค้าใหม่
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
                        <strong>เกิดข้อผิดพลาด:</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Product Form -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit me-2"></i>ข้อมูลสินค้า
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin.php?action=products&subaction=create" id="productForm">
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">
                                        <i class="fas fa-info-circle me-2"></i>ข้อมูลพื้นฐาน
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label for="product_code" class="form-label">
                                            <i class="fas fa-barcode me-1"></i>รหัสสินค้า <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="product_code" name="product_code" 
                                               value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>" 
                                               required>
                                        <div class="form-text">รหัสสินค้าที่ไม่ซ้ำกับสินค้าอื่น</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>ชื่อสินค้า <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" 
                                               value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" 
                                               required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category" class="form-label">
                                            <i class="fas fa-folder me-1"></i>หมวดหมู่
                                        </label>
                                        <select class="form-select" id="category" name="category">
                                            <option value="">เลือกหมวดหมู่</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                                        <?php echo (($_POST['category'] ?? '') === $category) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="unit" class="form-label">
                                            <i class="fas fa-ruler me-1"></i>หน่วย <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="unit" name="unit" required>
                                            <option value="กระสอบ" <?php echo (($_POST['unit'] ?? '') === 'กระสอบ') ? 'selected' : ''; ?>>กระสอบ</option>
                                            <option value="ขวด" <?php echo (($_POST['unit'] ?? '') === 'ขวด') ? 'selected' : ''; ?>>ขวด</option>
                                            <option value="ซอง" <?php echo (($_POST['unit'] ?? '') === 'ซอง') ? 'selected' : ''; ?>>ซอง</option>
                                            <option value="ถุง" <?php echo (($_POST['unit'] ?? '') === 'ถุง') ? 'selected' : ''; ?>>ถุง</option>
                                            <option value="ชิ้น" <?php echo (($_POST['unit'] ?? '') === 'ชิ้น') ? 'selected' : ''; ?>>ชิ้น</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Pricing & Stock -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">
                                        <i class="fas fa-dollar-sign me-2"></i>ราคาและสต็อก
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label for="cost_price" class="form-label">
                                            <i class="fas fa-shopping-cart me-1"></i>ต้นทุน (บาท) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                               value="<?php echo htmlspecialchars($_POST['cost_price'] ?? '0'); ?>" 
                                               step="0.01" min="0" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="selling_price" class="form-label">
                                            <i class="fas fa-tag me-1"></i>ราคาขาย (บาท) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="selling_price" name="selling_price" 
                                               value="<?php echo htmlspecialchars($_POST['selling_price'] ?? '0'); ?>" 
                                               step="0.01" min="0" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="stock_quantity" class="form-label">
                                            <i class="fas fa-boxes me-1"></i>จำนวนคงเหลือ <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                               value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>" 
                                               min="0" required>
                                    </div>

                                    <!-- Profit Calculation -->
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-calculator me-2"></i>การคำนวณกำไร
                                            </h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">กำไรต่อหน่วย:</small>
                                                    <div id="profit_per_unit" class="fw-bold text-success">฿0.00</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">อัตรากำไร:</small>
                                                    <div id="profit_margin" class="fw-bold text-info">0%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-align-left me-1"></i>รายละเอียดสินค้า
                                        </label>
                                        <textarea class="form-control" id="description" name="description" rows="4" 
                                                  placeholder="อธิบายรายละเอียดของสินค้า..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="admin.php?action=products" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <div>
                                            <button type="reset" class="btn btn-outline-secondary me-2">
                                                <i class="fas fa-undo me-2"></i>รีเซ็ต
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>บันทึกสินค้า
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- JavaScript Validation -->
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const productCodeInput = document.getElementById("product_code");
                    const productForm = document.getElementById("productForm");
                    
                    if (productCodeInput && productForm) {
                        // ตรวจสอบรหัสสินค้าที่ซ้ำก่อนส่งฟอร์ม
                        productForm.addEventListener("submit", function(e) {
                            const productCode = productCodeInput.value.trim();
                            
                            if (productCode === "") {
                                e.preventDefault();
                                alert("กรุณากรอกรหัสสินค้า");
                                productCodeInput.focus();
                                return false;
                            }
                            
                            // ตรวจสอบรูปแบบรหัสสินค้า
                            if (productCode.length < 3) {
                                e.preventDefault();
                                alert("รหัสสินค้าต้องมีความยาวอย่างน้อย 3 ตัวอักษร");
                                productCodeInput.focus();
                                return false;
                            }
                        });
                        
                        // แสดงข้อความแนะนำเมื่อ focus ที่ input
                        productCodeInput.addEventListener("focus", function() {
                            const helpText = this.parentNode.querySelector(".form-text");
                            if (helpText) {
                                helpText.style.color = "#007bff";
                                helpText.style.fontWeight = "bold";
                            }
                        });
                        
                        // ซ่อนข้อความแนะนำเมื่อ blur
                        productCodeInput.addEventListener("blur", function() {
                            const helpText = this.parentNode.querySelector(".form-text");
                            if (helpText) {
                                helpText.style.color = "#6c757d";
                                helpText.style.fontWeight = "normal";
                            }
                        });
                    }
                });
                </script>
            