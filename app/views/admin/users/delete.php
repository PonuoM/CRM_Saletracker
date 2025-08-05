<?php
/**
 * User Management - Delete User
 * ลบผู้ใช้
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลบผู้ใช้ - CRM SalesTracker</title>
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

                <!-- Delete Confirmation -->
                <div class="card shadow">
                    <div class="card-header py-3 bg-danger text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบผู้ใช้
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-warning me-2"></i>คำเตือน</h5>
                            <p>การลบผู้ใช้นี้จะไม่สามารถกู้คืนได้ และอาจส่งผลกระทบต่อข้อมูลที่เกี่ยวข้อง</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>ข้อมูลผู้ใช้ที่จะลบ:</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>ID:</strong></td>
                                        <td><?php echo $user['user_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ชื่อผู้ใช้:</strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ชื่อ-นามสกุล:</strong></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>อีเมล:</strong></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>เบอร์โทร:</strong></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>บทบาท:</strong></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($user['role_name']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>บริษัท:</strong></td>
                                        <td><?php echo htmlspecialchars($user['company_name'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>วันที่สร้าง:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>ผลกระทบที่อาจเกิดขึ้น:</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="fas fa-shopping-cart text-warning me-2"></i>
                                        คำสั่งซื้อที่สร้างโดยผู้ใช้นี้จะยังคงอยู่ แต่จะไม่แสดงชื่อผู้สร้าง
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-user-friends text-warning me-2"></i>
                                        ลูกค้าที่ได้รับมอบหมายจะถูกย้ายไปยัง Distribution Basket
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-history text-warning me-2"></i>
                                        ประวัติการโทรและกิจกรรมจะยังคงอยู่
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-chart-line text-warning me-2"></i>
                                        ข้อมูลสถิติการขายจะยังคงอยู่
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <hr>

                        <form method="POST" action="admin.php?action=users&action=delete&id=<?php echo $user['user_id']; ?>">
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
        // Enable delete button only when checkbox is checked
        document.getElementById('confirm_delete').addEventListener('change', function() {
            document.getElementById('deleteBtn').disabled = !this.checked;
        });
    </script>
</body>
</html> 