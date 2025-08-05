<?php
/**
 * User Management - List Users
 * แสดงรายการผู้ใช้ทั้งหมด
 */

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - CRM SalesTracker</title>
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
                        <i class="fas fa-users me-2"></i>
                        จัดการผู้ใช้
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="admin.php?action=users&action=create" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้ใหม่
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
                        case 'user_created':
                            $alertMessage = 'สร้างผู้ใช้ใหม่สำเร็จ';
                            break;
                        case 'user_updated':
                            $alertMessage = 'อัปเดตข้อมูลผู้ใช้สำเร็จ';
                            break;
                        case 'user_deleted':
                            $alertMessage = 'ลบผู้ใช้สำเร็จ';
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

                <!-- Users Table -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>รายการผู้ใช้ทั้งหมด
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="usersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>ชื่อผู้ใช้</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>อีเมล</th>
                                        <th>เบอร์โทร</th>
                                        <th>บทบาท</th>
                                        <th>บริษัท</th>
                                        <th>สถานะ</th>
                                        <th>วันที่สร้าง</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $userItem): ?>
                                    <tr>
                                        <td><?php echo $userItem['user_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($userItem['username']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($userItem['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($userItem['email'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($userItem['phone'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $roleBadgeClass = 'bg-secondary';
                                            switch ($userItem['role_name']) {
                                                case 'super_admin':
                                                    $roleBadgeClass = 'bg-danger';
                                                    break;
                                                case 'admin':
                                                    $roleBadgeClass = 'bg-primary';
                                                    break;
                                                case 'supervisor':
                                                    $roleBadgeClass = 'bg-warning';
                                                    break;
                                                case 'telesales':
                                                    $roleBadgeClass = 'bg-success';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $roleBadgeClass; ?>">
                                                <?php echo htmlspecialchars($userItem['role_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($userItem['company_name'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($userItem['is_active']): ?>
                                                <span class="badge bg-success">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($userItem['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="admin.php?action=users&action=edit&id=<?php echo $userItem['user_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($userItem['user_id'] != $_SESSION['user_id']): ?>
                                                <a href="admin.php?action=users&action=delete&id=<?php echo $userItem['user_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="ลบ"
                                                   onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
            $('#usersTable').DataTable({
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