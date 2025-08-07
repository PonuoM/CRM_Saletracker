<?php
/**
 * Company Management - List Companies
 * แสดงรายการบริษัททั้งหมด
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบริษัท - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 page-transition">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-building me-2"></i>
                        จัดการบริษัท
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="admin.php?action=companies&subaction=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>เพิ่มบริษัทใหม่
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        $message = $_GET['message'];
                        switch ($message) {
                            case 'company_created':
                                echo '<i class="fas fa-check-circle me-2"></i>สร้างบริษัทใหม่สำเร็จ';
                                break;
                            case 'company_updated':
                                echo '<i class="fas fa-check-circle me-2"></i>อัปเดตบริษัทสำเร็จ';
                                break;
                            case 'company_deleted':
                                echo '<i class="fas fa-check-circle me-2"></i>ลบบริษัทสำเร็จ';
                                break;
                            default:
                                echo 'ดำเนินการสำเร็จ';
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php
                        $error = $_GET['error'];
                        switch ($error) {
                            case 'invalid_id':
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>รหัสบริษัทไม่ถูกต้อง';
                                break;
                            case 'company_not_found':
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>ไม่พบบริษัทที่ต้องการ';
                                break;
                            default:
                                echo 'เกิดข้อผิดพลาด';
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            รายการบริษัททั้งหมด
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($companies)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">ยังไม่มีข้อมูลบริษัท</h5>
                                <p class="text-muted">เริ่มต้นโดยการเพิ่มบริษัทใหม่</p>
                                <a href="admin.php?action=companies&subaction=create" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>เพิ่มบริษัทใหม่
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>รหัส</th>
                                            <th>ชื่อบริษัท</th>
                                            <th>รหัสบริษัท</th>
                                            <th>เบอร์โทร</th>
                                            <th>อีเมล</th>
                                            <th>สถานะ</th>
                                            <th>วันที่สร้าง</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td><?php echo $company['company_id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($company['company_name']); ?></strong>
                                                    <?php if ($company['address']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($company['address']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($company['company_code']): ?>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($company['company_code']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($company['phone']): ?>
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($company['phone']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($company['email']): ?>
                                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($company['email']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($company['is_active']): ?>
                                                        <span class="badge bg-success">ใช้งาน</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">ไม่ใช้งาน</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y H:i', strtotime($company['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="admin.php?action=companies&subaction=edit&id=<?php echo $company['company_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="admin.php?action=companies&subaction=delete&id=<?php echo $company['company_id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger" title="ลบ">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Page transition animation
        $(document).ready(function() {
            // Add fade-in animation to main content
            $('.page-transition').addClass('fadeIn');
            
            // Smooth page transitions for all links
            $('a[href*="admin.php"]').on('click', function(e) {
                const href = $(this).attr('href');
                if (href && !href.includes('#')) {
                    e.preventDefault();
                    
                    // Add fade-out animation
                    $('.page-transition').css({
                        'opacity': '0',
                        'transform': 'translateY(-10px)',
                        'transition': 'all 0.2s ease-out'
                    });
                    
                    // Navigate after animation
                    setTimeout(function() {
                        window.location.href = href;
                    }, 200);
                }
            });
            
            // Smooth transitions for form submissions
            $('form').on('submit', function() {
                $('.page-transition').css({
                    'opacity': '0',
                    'transform': 'translateY(-10px)',
                    'transition': 'all 0.2s ease-out'
                });
            });
        });
    </script>
</body>
</html>
