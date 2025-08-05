<?php
/**
 * Product Management - List Products
 * แสดงรายการสินค้าทั้งหมด
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - CRM SalesTracker</title>
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
                        <i class="fas fa-box me-2"></i>
                        จัดการสินค้า
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="admin.php?action=products&action=create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                            </a>
                            <a href="admin.php?action=products&action=import" class="btn btn-info">
                                <i class="fas fa-upload me-2"></i>นำเข้า
                            </a>
                            <a href="admin.php?action=products&action=export" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>ส่งออก
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['message'])): ?>
                    <?php
                    $message = $_GET['message'];
                    $alertClass = 'alert-success';
                    $alertMessage = '';
                    
                    switch ($message) {
                        case 'product_created':
                            $alertMessage = 'สร้างสินค้าใหม่สำเร็จ';
                            break;
                        case 'product_updated':
                            $alertMessage = 'อัปเดตข้อมูลสินค้าสำเร็จ';
                            break;
                        case 'product_deleted':
                            $alertMessage = 'ลบสินค้าสำเร็จ';
                            break;
                        case 'products_imported':
                            $count = $_GET['count'] ?? 0;
                            $alertMessage = "นำเข้าสินค้า $count รายการสำเร็จ";
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

                <!-- Products Table -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>รายการสินค้าทั้งหมด
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="productsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>รหัสสินค้า</th>
                                        <th>ชื่อสินค้า</th>
                                        <th>หมวดหมู่</th>
                                        <th>หน่วย</th>
                                        <th>ต้นทุน</th>
                                        <th>ราคาขาย</th>
                                        <th>จำนวนคงเหลือ</th>
                                        <th>สถานะ</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['product_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_code']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                        <td class="text-end">฿<?php echo number_format($product['cost_price'], 2); ?></td>
                                        <td class="text-end">฿<?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo ($product['stock_quantity'] > 0) ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo number_format($product['stock_quantity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($product['is_active']): ?>
                                                <span class="badge bg-success">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="admin.php?action=products&action=edit&id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="admin.php?action=products&action=delete&id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="ลบ"
                                                   onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Product Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            สินค้าทั้งหมด
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($products); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            สินค้าเปิดใช้งาน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            สินค้าไม่มีสต็อก
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($products, function($p) { return $p['stock_quantity'] <= 0; })); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            หมวดหมู่สินค้า
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($categories); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tags fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#productsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
                },
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html> 