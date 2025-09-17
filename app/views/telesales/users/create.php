<?php
/**
 * Telesales User Management - Create User
 * สร้างผู้ใช้ใหม่สำหรับ telesales
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-plus me-2"></i>
        สร้างผู้ใช้ใหม่
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="telesales.php?action=users" class="btn btn-secondary">
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

<!-- Create User Form -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-edit me-2"></i>ข้อมูลผู้ใช้ใหม่
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="telesales.php?action=users&subaction=create">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i>ชื่อผู้ใช้ <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required>
                        <div class="form-text">ชื่อผู้ใช้ต้องไม่ซ้ำกับผู้ใช้อื่น</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>รหัสผ่าน <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required>
                        <div class="form-text">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">
                            <i class="fas fa-id-card me-1"></i>ชื่อ-นามสกุล <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>อีเมล
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone me-1"></i>โทรศัพท์
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="role_id" class="form-label">
                            <i class="fas fa-user-tag me-1"></i>บทบาท <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="">เลือกบทบาท</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['role_id']; ?>" 
                                        <?php echo (($_POST['role_id'] ?? '') == $role['role_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">สามารถสร้างได้เฉพาะ Supervisor และ Telesales</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="supervisor_id" class="form-label">
                            <i class="fas fa-user-check me-1"></i>ผู้ดูแล
                        </label>
                        <select class="form-select" id="supervisor_id" name="supervisor_id">
                            <option value="">เลือกผู้ดูแล</option>
                            <?php foreach ($supervisors as $supervisor): ?>
                                <option value="<?php echo $supervisor['user_id']; ?>" 
                                        <?php echo (($_POST['supervisor_id'] ?? '') == $supervisor['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supervisor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">เลือกผู้ดูแลสำหรับ Telesales</div>
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
            
            <div class="d-flex justify-content-end">
                <a href="telesales.php?action=users" class="btn btn-secondary me-2">
                    <i class="fas fa-times me-2"></i>ยกเลิก
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>บันทึกผู้ใช้
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-generate username if empty
    $('#full_name').on('blur', function() {
        if (!$('#username').val()) {
            const fullName = $(this).val();
            if (fullName) {
                const username = fullName.replace(/\s+/g, '').toLowerCase();
                $('#username').val(username);
            }
        }
    });
    
    // Show/hide supervisor field based on role
    $('#role_id').on('change', function() {
        const roleId = $(this).val();
        const supervisorField = $('#supervisor_id').closest('.mb-3');
        
        if (roleId == 4) { // Telesales
            supervisorField.show();
        } else {
            supervisorField.hide();
            $('#supervisor_id').val('');
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const fullName = $('#full_name').val().trim();
        const roleId = $('#role_id').val();
        
        if (!username || !password || !fullName || !roleId) {
            e.preventDefault();
            alert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            return false;
        }
        
        if (roleId == 4 && !$('#supervisor_id').val()) {
            e.preventDefault();
            alert('กรุณาเลือกผู้ดูแลสำหรับ Telesales');
            return false;
        }
    });
    
    // Trigger role change on page load
    $('#role_id').trigger('change');
});
</script>
