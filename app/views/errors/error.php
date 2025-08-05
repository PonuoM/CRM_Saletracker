<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'ข้อผิดพลาด'); ?> - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-exclamation-triangle text-warning fa-4x mb-4"></i>
                        <h2 class="card-title text-danger mb-3">
                            <?php echo htmlspecialchars($title ?? 'เกิดข้อผิดพลาด'); ?>
                        </h2>
                        <p class="card-text text-muted mb-4">
                            <?php echo htmlspecialchars($message ?? 'เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง'); ?>
                        </p>
                        <div class="d-grid gap-2 d-md-block">
                            <a href="javascript:history.back()" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>ย้อนกลับ
                            </a>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i>หน้าแรก
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 