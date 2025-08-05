<?php
/**
 * System Settings
 * ตั้งค่าระบบสำหรับ Admin
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-cog me-2"></i>
                        ตั้งค่าระบบ
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="admin.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>กลับไป Admin Dashboard
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['message'])): ?>
                    <?php
                    $message = $_GET['message'];
                    $alertClass = 'alert-success';
                    $alertMessage = '';
                    
                    switch ($message) {
                        case 'settings_updated':
                            $alertMessage = 'อัปเดตการตั้งค่าระบบสำเร็จ';
                            break;
                        default:
                            $alertClass = 'alert-info';
                            $alertMessage = 'ดำเนินการสำเร็จ';
                    }
                    ?>
                    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $alertMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- System Settings Form -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit me-2"></i>การตั้งค่าระบบ
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin.php?action=settings">
                            
                            <!-- Customer Grade Settings -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-star me-2"></i>เกณฑ์การจัดเกรดลูกค้า
                                    </h5>
                                    <p class="text-muted">กำหนดเกณฑ์จำนวนเงินยอดซื้อสำหรับการจัดเกรดลูกค้า</p>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="customer_grade_a_plus" class="form-label">
                                            <i class="fas fa-star text-warning me-1"></i>เกรด A+ (บาท)
                                        </label>
                                        <input type="number" class="form-control" id="customer_grade_a_plus" 
                                               name="customer_grade_a_plus" 
                                               value="<?php echo $settings['customer_grade_a_plus'] ?? 50000; ?>" 
                                               min="0" step="1000" required>
                                        <div class="form-text">ยอดซื้อตั้งแต่ 50,000 บาทขึ้นไป</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="customer_grade_a" class="form-label">
                                            <i class="fas fa-star text-warning me-1"></i>เกรด A (บาท)
                                        </label>
                                        <input type="number" class="form-control" id="customer_grade_a" 
                                               name="customer_grade_a" 
                                               value="<?php echo $settings['customer_grade_a'] ?? 10000; ?>" 
                                               min="0" step="1000" required>
                                        <div class="form-text">ยอดซื้อตั้งแต่ 10,000 บาทขึ้นไป</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="customer_grade_b" class="form-label">
                                            <i class="fas fa-star text-warning me-1"></i>เกรด B (บาท)
                                        </label>
                                        <input type="number" class="form-control" id="customer_grade_b" 
                                               name="customer_grade_b" 
                                               value="<?php echo $settings['customer_grade_b'] ?? 5000; ?>" 
                                               min="0" step="1000" required>
                                        <div class="form-text">ยอดซื้อตั้งแต่ 5,000 บาทขึ้นไป</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="customer_grade_c" class="form-label">
                                            <i class="fas fa-star text-warning me-1"></i>เกรด C (บาท)
                                        </label>
                                        <input type="number" class="form-control" id="customer_grade_c" 
                                               name="customer_grade_c" 
                                               value="<?php echo $settings['customer_grade_c'] ?? 2000; ?>" 
                                               min="0" step="1000" required>
                                        <div class="form-text">ยอดซื้อตั้งแต่ 2,000 บาทขึ้นไป</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Recall Settings -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-clock me-2"></i>การตั้งค่าการเรียกกลับลูกค้า
                                    </h5>
                                    <p class="text-muted">กำหนดระยะเวลาสำหรับการเรียกกลับลูกค้า</p>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="new_customer_recall_days" class="form-label">
                                            <i class="fas fa-user-plus text-success me-1"></i>ลูกค้าใหม่ (วัน)
                                        </label>
                                        <input type="number" class="form-control" id="new_customer_recall_days" 
                                               name="new_customer_recall_days" 
                                               value="<?php echo $settings['new_customer_recall_days'] ?? 30; ?>" 
                                               min="1" max="365" required>
                                        <div class="form-text">ระยะเวลาก่อนเรียกกลับลูกค้าใหม่</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="existing_customer_recall_days" class="form-label">
                                            <i class="fas fa-user text-info me-1"></i>ลูกค้าเก่า (วัน)
                                        </label>
                                        <input type="number" class="form-control" id="existing_customer_recall_days" 
                                               name="existing_customer_recall_days" 
                                               value="<?php echo $settings['existing_customer_recall_days'] ?? 90; ?>" 
                                               min="1" max="365" required>
                                        <div class="form-text">ระยะเวลาก่อนเรียกกลับลูกค้าเก่า</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="waiting_basket_days" class="form-label">
                                            <i class="fas fa-hourglass-half text-warning me-1"></i>ตะกร้ารอ (วัน)
                                        </label>
                                        <input type="number" class="form-control" id="waiting_basket_days" 
                                               name="waiting_basket_days" 
                                               value="<?php echo $settings['waiting_basket_days'] ?? 30; ?>" 
                                               min="1" max="365" required>
                                        <div class="form-text">ระยะเวลาที่ลูกค้าอยู่ในตะกร้ารอ</div>
                                    </div>
                                </div>
                            </div>

                            <!-- System Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-info-circle me-2"></i>ข้อมูลระบบ
                                    </h5>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-server me-2"></i>ข้อมูลเซิร์ฟเวอร์
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>PHP Version:</strong></td>
                                                    <td><?php echo phpversion(); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Server Software:</strong></td>
                                                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Memory Limit:</strong></td>
                                                    <td><?php echo ini_get('memory_limit'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Max Execution Time:</strong></td>
                                                    <td><?php echo ini_get('max_execution_time'); ?> วินาที</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Upload Max Filesize:</strong></td>
                                                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-database me-2"></i>ข้อมูลฐานข้อมูล
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>Database Name:</strong></td>
                                                    <td><?php echo defined('DB_NAME') ? DB_NAME : 'primacom_Customer'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Database Host:</strong></td>
                                                    <td><?php echo defined('DB_HOST') ? DB_HOST : 'localhost'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Connection Status:</strong></td>
                                                    <td>
                                                        <span class="badge bg-success">เชื่อมต่อสำเร็จ</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tables Count:</strong></td>
                                                    <td>11 ตาราง</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Last Backup:</strong></td>
                                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end">
                                        <a href="admin.php" class="btn btn-secondary me-2">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Information -->
                <div class="card shadow mt-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-question-circle me-2"></i>คำแนะนำการตั้งค่า
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-star text-warning me-2"></i>การจัดเกรดลูกค้า</h6>
                                <ul class="list-unstyled">
                                    <li><strong>A+:</strong> ลูกค้ามูลค่าสูง (VIP)</li>
                                    <li><strong>A:</strong> ลูกค้าปกติที่ซื้อสม่ำเสมอ</li>
                                    <li><strong>B:</strong> ลูกค้าที่ซื้อเป็นครั้งคราว</li>
                                    <li><strong>C:</strong> ลูกค้าใหม่หรือซื้อน้อย</li>
                                    <li><strong>D:</strong> ลูกค้าที่ยังไม่เคยซื้อ</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock text-info me-2"></i>การเรียกกลับลูกค้า</h6>
                                <ul class="list-unstyled">
                                    <li><strong>ลูกค้าใหม่:</strong> เรียกกลับหลังจาก 30 วัน</li>
                                    <li><strong>ลูกค้าเก่า:</strong> เรียกกลับหลังจาก 90 วัน</li>
                                    <li><strong>ตะกร้ารอ:</strong> ย้ายไป Distribution หลังจาก 30 วัน</li>
                                </ul>
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