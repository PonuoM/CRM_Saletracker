<?php
/**
 * Company Management - Create Company
 * สร้างบริษัทใหม่
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มบริษัทใหม่ - CRM SalesTracker</title>
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
                        <i class="fas fa-plus me-2"></i>
                        เพิ่มบริษัทใหม่
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

                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>
                            ข้อมูลบริษัท
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin.php?action=companies&subaction=create">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">
                                            <i class="fas fa-building me-1"></i>ชื่อบริษัท <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" 
                                               value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" 
                                               required>
                                        <div class="form-text">กรอกชื่อบริษัทที่ต้องการเพิ่ม</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_code" class="form-label">
                                            <i class="fas fa-code me-1"></i>รหัสบริษัท
                                        </label>
                                        <input type="text" class="form-control" id="company_code" name="company_code" 
                                               value="<?php echo htmlspecialchars($_POST['company_code'] ?? ''); ?>">
                                        <div class="form-text">รหัสประจำตัวบริษัท (ไม่บังคับ)</div>
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
                                        <div class="form-text">เบอร์โทรศัพท์ของบริษัท</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>อีเมล
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        <div class="form-text">อีเมลของบริษัท</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">
                                            <i class="fas fa-map-marker-alt me-1"></i>ที่อยู่
                                        </label>
                                        <textarea class="form-control" id="address" name="address" rows="3"
                                                  placeholder="ที่อยู่ของบริษัท"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                        <div class="form-text">ที่อยู่ของบริษัท</div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="admin.php?action=companies" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>ยกเลิก
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>บันทึกบริษัท
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
