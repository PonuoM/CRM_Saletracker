<?php
/**
 * Company Management - Delete Company
 * ลบบริษัท
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลบบริษัท - CRM SalesTracker</title>
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
                        <i class="fas fa-trash me-2"></i>
                        ลบบริษัท
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="admin.php?action=companies" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>กลับไปรายการบริษัท
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ยืนยันการลบบริษัท
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">
                                <i class="fas fa-warning me-2"></i>
                                คำเตือน!
                            </h5>
                            <p>คุณกำลังจะลบบริษัท <strong><?php echo htmlspecialchars($company['company_name']); ?></strong></p>
                            <p class="mb-0">การดำเนินการนี้ไม่สามารถยกเลิกได้ และจะลบข้อมูลบริษัทออกจากระบบอย่างถาวร</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>ข้อมูลบริษัทที่จะลบ:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>รหัส:</strong></td>
                                        <td><?php echo $company['company_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ชื่อบริษัท:</strong></td>
                                        <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>รหัสบริษัท:</strong></td>
                                        <td><?php echo htmlspecialchars($company['company_code'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>เบอร์โทร:</strong></td>
                                        <td><?php echo htmlspecialchars($company['phone'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>อีเมล:</strong></td>
                                        <td><?php echo htmlspecialchars($company['email'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ที่อยู่:</strong></td>
                                        <td><?php echo htmlspecialchars($company['address'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>สถานะ:</strong></td>
                                        <td>
                                            <?php if ($company['is_active']): ?>
                                                <span class="badge bg-success">ใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ไม่ใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>วันที่สร้าง:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($company['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>ผลกระทบที่อาจเกิดขึ้น:</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item text-danger">
                                        <i class="fas fa-times-circle me-2"></i>
                                        ข้อมูลบริษัทจะถูกลบออกจากระบบอย่างถาวร
                                    </li>
                                    <li class="list-group-item text-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        ผู้ใช้ที่เกี่ยวข้องกับบริษัทนี้จะไม่สามารถเข้าถึงได้
                                    </li>
                                    <li class="list-group-item text-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        ข้อมูลลูกค้าและคำสั่งซื้อจะไม่ได้รับผลกระทบ
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <hr>

                        <form method="POST" action="admin.php?action=companies&subaction=delete&id=<?php echo $company['company_id']; ?>">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="admin.php?action=companies" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบบริษัทนี้?')">
                                            <i class="fas fa-trash me-2"></i>ยืนยันการลบ
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
