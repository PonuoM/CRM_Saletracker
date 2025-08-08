<?php
/**
 * Product Delete Confirmation Page
 * หน้าแสดงการยืนยันการลบสินค้า
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trash-alt text-danger me-2"></i>
                        ยืนยันการลบสินค้า
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($product) && $product): ?>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                คำเตือน
                            </h6>
                            <p class="mb-0">คุณกำลังจะลบสินค้า <strong>"<?php echo htmlspecialchars($product['product_name']); ?>"</strong> ออกจากระบบ</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>รายละเอียดสินค้า:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>รหัสสินค้า:</strong></td>
                                        <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ชื่อสินค้า:</strong></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>หมวดหมู่:</strong></td>
                                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>หน่วย:</strong></td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ราคาขาย:</strong></td>
                                        <td><?php echo number_format($product['selling_price'], 2); ?> บาท</td>
                                    </tr>
                                    <tr>
                                        <td><strong>จำนวนคงเหลือ:</strong></td>
                                        <td><?php echo number_format($product['stock_quantity']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>หมายเหตุ:</strong> การลบสินค้าจะไม่สามารถยกเลิกได้ และหากสินค้านี้ถูกใช้ในคำสั่งซื้อแล้ว จะไม่สามารถลบได้
                        </div>

                        <form method="POST" action="admin.php?action=products&subaction=delete&id=<?php echo $product['product_id']; ?>">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                    <i class="fas fa-trash-alt me-2"></i>
                                    ลบสินค้า
                                </button>
                                <a href="admin.php?action=products" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    ยกเลิก
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ไม่พบสินค้าที่ต้องการลบ
                        </div>
                        <a href="admin.php?action=products" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            กลับไปหน้าสินค้า
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
