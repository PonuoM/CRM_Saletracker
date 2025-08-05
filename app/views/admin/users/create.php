<?php
/**
 * User Management - Create User
 * สร้างผู้ใช้ใหม่
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างผู้ใช้ใหม่ - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-plus me-2"></i>
                        สร้างผู้ใช้ใหม่
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

                <!-- Create User Form -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit me-2"></i>ข้อมูลผู้ใช้ใหม่
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin.php?action=users&action=create">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user me-1"></i>ชื่อผู้ใช้ <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                               required>
                                        <div class="form-text">ชื่อผู้ใช้สำหรับเข้าสู่ระบบ (ไม่ซ้ำกับผู้อื่น)</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>รหัสผ่าน <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password" required>
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
                                            <i class="fas fa-phone me-1"></i>เบอร์โทรศัพท์
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
                                                        <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_id" class="form-label">
                                            <i class="fas fa-building me-1"></i>บริษัท
                                        </label>
                                        <select class="form-select" id="company_id" name="company_id">
                                            <option value="">เลือกบริษัท (ถ้ามี)</option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?php echo $company['company_id']; ?>" 
                                                        <?php echo (isset($_POST['company_id']) && $_POST['company_id'] == $company['company_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end">
                                        <a href="admin.php?action=users" class="btn btn-secondary me-2">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>สร้างผู้ใช้
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
        // Password strength validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = {
                length: password.length >= 6,
                hasNumber: /\d/.test(password),
                hasLetter: /[a-zA-Z]/.test(password)
            };
            
            let strengthText = '';
            let strengthClass = 'text-danger';
            
            if (strength.length && strength.hasNumber && strength.hasLetter) {
                strengthText = 'รหัสผ่านแข็งแกร่ง';
                strengthClass = 'text-success';
            } else if (strength.length && (strength.hasNumber || strength.hasLetter)) {
                strengthText = 'รหัสผ่านปานกลาง';
                strengthClass = 'text-warning';
            } else if (strength.length) {
                strengthText = 'รหัสผ่านอ่อน';
                strengthClass = 'text-danger';
            }
            
            // Update strength indicator
            const strengthIndicator = document.getElementById('password-strength');
            if (strengthIndicator) {
                strengthIndicator.textContent = strengthText;
                strengthIndicator.className = strengthClass;
            }
        });
    </script>
</body>
</html> 