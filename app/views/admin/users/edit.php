<?php
/**
 * User Management - Edit User
 * แก้ไขข้อมูลผู้ใช้
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้ - CRM SalesTracker</title>
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
                        <i class="fas fa-user-edit me-2"></i>
                        แก้ไขผู้ใช้: <?php echo htmlspecialchars($user['full_name']); ?>
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

                <!-- Edit User Form -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit me-2"></i>ข้อมูลผู้ใช้
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin.php?action=users&action=edit&id=<?php echo $user['user_id']; ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user me-1"></i>ชื่อผู้ใช้ <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" 
                                               required>
                                        <div class="form-text">ชื่อผู้ใช้สำหรับเข้าสู่ระบบ</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>รหัสผ่านใหม่
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <div class="form-text">เว้นว่างถ้าไม่ต้องการเปลี่ยนรหัสผ่าน</div>
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
                                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>อีเมล
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email'] ?? ''); ?>">
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
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? ''); ?>">
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
                                                        <?php 
                                                        $selectedRoleId = $_POST['role_id'] ?? $user['role_id'];
                                                        echo ($selectedRoleId == $role['role_id']) ? 'selected' : ''; 
                                                        ?>>
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
                                                        <?php 
                                                        $selectedCompanyId = $_POST['company_id'] ?? $user['company_id'];
                                                        echo ($selectedCompanyId == $company['company_id']) ? 'selected' : ''; 
                                                        ?>>
                                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-toggle-on me-1"></i>สถานะ
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   value="1" <?php echo (($_POST['is_active'] ?? $user['is_active']) ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="is_active">
                                                เปิดใช้งาน
                                            </label>
                                        </div>
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
                                            <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- User Information -->
                <div class="card shadow mt-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle me-2"></i>ข้อมูลเพิ่มเติม
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>วันที่สร้าง:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>อัปเดตล่าสุด:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>เข้าสู่ระบบล่าสุด:</strong></td>
                                        <td>
                                            <?php 
                                            if ($user['last_login']) {
                                                echo date('d/m/Y H:i', strtotime($user['last_login']));
                                            } else {
                                                echo 'ยังไม่เคยเข้าสู่ระบบ';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
</body>
</html> 