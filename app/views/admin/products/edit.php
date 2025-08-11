
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-edit me-2"></i>
                        แก้ไขสินค้า: <?php echo htmlspecialchars($product['product_name'] ?? ''); ?>
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

                <!-- Product Form -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit me-2"></i>ข้อมูลสินค้า
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin.php?action=products&subaction=edit&id=<?php echo $product['product_id'] ?? ''; ?>" id="productForm">
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
                                               value="<?php echo htmlspecialchars($product['product_code'] ?? ''); ?>" 
                                               required>
                                        <div class="form-text">รหัสสินค้าที่ไม่ซ้ำกับสินค้าอื่น</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>ชื่อสินค้า <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" 
                                               value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>" 
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
                                                        <?php echo (($product['category'] ?? '') === $category) ? 'selected' : ''; ?>>
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
                                            <option value="กระสอบ" <?php echo (($product['unit'] ?? '') === 'กระสอบ') ? 'selected' : ''; ?>>กระสอบ</option>
                                            <option value="ขวด" <?php echo (($product['unit'] ?? '') === 'ขวด') ? 'selected' : ''; ?>>ขวด</option>
                                            <option value="ซอง" <?php echo (($product['unit'] ?? '') === 'ซอง') ? 'selected' : ''; ?>>ซอง</option>
                                            <option value="ถุง" <?php echo (($product['unit'] ?? '') === 'ถุง') ? 'selected' : ''; ?>>ถุง</option>
                                            <option value="ชิ้น" <?php echo (($product['unit'] ?? '') === 'ชิ้น') ? 'selected' : ''; ?>>ชิ้น</option>
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
                                               value="<?php echo htmlspecialchars($product['cost_price'] ?? '0'); ?>" 
                                               step="0.01" min="0" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="selling_price" class="form-label">
                                            <i class="fas fa-tag me-1"></i>ราคาขาย (บาท) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="selling_price" name="selling_price" 
                                               value="<?php echo htmlspecialchars($product['selling_price'] ?? '0'); ?>" 
                                               step="0.01" min="0" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="stock_quantity" class="form-label">
                                            <i class="fas fa-boxes me-1"></i>จำนวนคงเหลือ <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                               value="<?php echo htmlspecialchars($product['stock_quantity'] ?? '0'); ?>" 
                                               min="0" required>
                                    </div>

                                    <!-- Status -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-toggle-on me-1"></i>สถานะ
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   value="1" <?php echo (($product['is_active'] ?? 1) == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                เปิดใช้งานสินค้า
                                            </label>
                                        </div>
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
                                                  placeholder="อธิบายรายละเอียดของสินค้า..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Information -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-info-circle me-2"></i>ข้อมูลระบบ
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <small class="text-muted">รหัสสินค้า:</small>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($product['product_id'] ?? ''); ?></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">วันที่สร้าง:</small>
                                                    <div class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($product['created_at'] ?? 'now')); ?></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">วันที่แก้ไขล่าสุด:</small>
                                                    <div class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($product['updated_at'] ?? 'now')); ?></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">สถานะ:</small>
                                                    <div class="fw-bold">
                                                        <?php if (($product['is_active'] ?? 1) == 1): ?>
                                                            <span class="badge bg-success">เปิดใช้งาน</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">ปิดใช้งาน</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                                                <i class="fas fa-save me-2"></i>บันทึกการแก้ไข
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            