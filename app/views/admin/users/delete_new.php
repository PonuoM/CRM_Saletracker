                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-times me-2"></i>
                        ลบผู้ใช้
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="admin.php?action=users" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>กลับไปรายการผู้ใช้
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

                <!-- Delete User Confirmation -->
                <?php if ($user): ?>
                    <div class="card shadow">
                        <div class="card-header bg-danger text-white py-3">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบผู้ใช้
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning" role="alert">
                                <h5 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>คำเตือน!
                                </h5>
                                <p class="mb-0">
                                    การลบผู้ใช้นี้ไม่สามารถยกเลิกได้ กรุณาตรวจสอบข้อมูลให้ถี่ถ้วนก่อนดำเนินการ
                                </p>
                            </div>

                            <h5 class="mb-3">ข้อมูลผู้ใช้ที่จะลบ:</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>ID:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($user['user_id']); ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>ชื่อผู้ใช้:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>ชื่อ-นามสกุล:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>อีเมล:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>เบอร์โทรศัพท์:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>บทบาท:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($user['role_name'] ?? 'ไม่ระบุ'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>บริษัท:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($user['company_name'] ?? '-'); ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>สถานะ:</strong></div>
                                <div class="col-md-9">
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">เปิดใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ปิดใช้งาน</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>วันที่สร้าง:</strong></div>
                                <div class="col-md-9">
                                    <?php 
                                    if ($user['created_at']) {
                                        echo date('d/m/Y H:i', strtotime($user['created_at']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </div>
                            </div>

                            <hr>

                            <form method="POST" action="admin.php?action=users&subaction=delete&id=<?php echo $user['user_id']; ?>">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="1" required>
                                            <label class="form-check-label" for="confirm_delete">
                                                <strong>ฉันเข้าใจและยืนยันที่จะลบผู้ใช้นี้</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end">
                                            <a href="admin.php?action=users" class="btn btn-secondary me-2">
                                                <i class="fas fa-times me-2"></i>ยกเลิก
                                            </a>
                                            <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                                <i class="fas fa-trash me-2"></i>ลบผู้ใช้
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>ไม่พบผู้ใช้
                        </h5>
                        <p class="mb-0">
                            ไม่พบผู้ใช้ที่ต้องการลบ หรือคุณไม่มีสิทธิ์ในการเข้าถึงข้อมูลนี้
                        </p>
                        <hr>
                        <a href="admin.php?action=users" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>กลับไปรายการผู้ใช้
                        </a>
                    </div>
                <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirm_delete');
    const deleteBtn = document.getElementById('deleteBtn');
    
    if (confirmCheckbox && deleteBtn) {
        confirmCheckbox.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
        });
    }
});
</script>
            
