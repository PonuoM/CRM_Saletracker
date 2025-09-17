<?php
/**
 * User Management - List Users
 * แสดงรายการผู้ใช้ทั้งหมด
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-users me-2"></i>
        จัดการผู้ใช้
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="admin.php?action=users&subaction=create" class="btn btn-primary me-2">
            <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้ใหม่
        </a>
        <?php 
        // Hide company management button for role_id 6 (company_admin)
        $currentRoleId = $_SESSION['role_id'] ?? 0;
        if ($currentRoleId != 6): 
        ?>
        <a href="admin.php?action=companies" class="btn btn-info">
            <i class="fas fa-building me-2"></i>จัดการบริษัท
        </a>
        <?php endif; ?>
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

                <!-- Role Filter -->
                <div class="card shadow mb-3">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-filter me-2"></i>กรองตามบทบาท
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check-inline">
                                    <input class="form-check-input role-filter" type="checkbox" value="all" id="roleAll" checked>
                                    <label class="form-check-label" for="roleAll">
                                        <span class="badge bg-light text-dark border">ทั้งหมด</span>
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="form-check-input role-filter" type="checkbox" value="super_admin" id="roleSuperAdmin">
                                    <label class="form-check-label" for="roleSuperAdmin">
                                        <span class="badge bg-dark">Super Admin</span>
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="form-check-input role-filter" type="checkbox" value="admin" id="roleAdmin">
                                    <label class="form-check-label" for="roleAdmin">
                                        <span class="badge bg-primary">Admin</span>
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="form-check-input role-filter" type="checkbox" value="supervisor" id="roleSupervisor">
                                    <label class="form-check-label" for="roleSupervisor">
                                        <span class="badge bg-secondary">Supervisor</span>
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="form-check-input role-filter" type="checkbox" value="telesales" id="roleTelesales">
                                    <label class="form-check-label" for="roleTelesales">
                                        <span class="badge bg-success">Telesales</span>
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="form-check-input role-filter" type="checkbox" value="admin_page" id="roleAdminPage">
                                    <label class="form-check-label" for="roleAdminPage">
                                        <span class="badge bg-info">Admin Page</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>รายการผู้ใช้ทั้งหมด
                        </h6>
                        <div class="text-muted">
                            <small>แสดงสูงสุด 7 รายการต่อหน้า</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Table Container with Fixed Height and Scroll -->
                        <div class="table-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                            <table class="table table-bordered table-hover mb-0" id="usersTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="min-width: 60px;">ID</th>
                                        <th style="min-width: 120px;">ชื่อผู้ใช้</th>
                                        <th style="min-width: 150px;">ชื่อ-นามสกุล</th>
                                        <th style="min-width: 150px;">อีเมล</th>
                                        <th style="min-width: 120px;">เบอร์โทร</th>
                                        <th style="min-width: 120px;">บทบาท</th>
                                        <th style="min-width: 120px;">บริษัท</th>
                                        <th style="min-width: 100px;">สถานะ</th>
                                        <th style="min-width: 130px;">วันที่สร้าง</th>
                                        <th style="min-width: 120px;">การดำเนินการ</th>
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
                                            $roleDisplayName = $userItem['role_name'];
                                            switch ($userItem['role_name']) {
                                                case 'super_admin':
                                                    $roleBadgeClass = 'bg-dark';
                                                    $roleDisplayName = 'Super Admin';
                                                    break;
                                                case 'admin':
                                                    $roleBadgeClass = 'bg-primary';
                                                    $roleDisplayName = 'Admin';
                                                    break;
                                                case 'supervisor':
                                                    $roleBadgeClass = 'bg-secondary';
                                                    $roleDisplayName = 'Supervisor';
                                                    break;
                                                case 'telesales':
                                                    $roleBadgeClass = 'bg-success';
                                                    $roleDisplayName = 'Telesales';
                                                    break;
                                                case 'admin_page':
                                                    $roleBadgeClass = 'bg-info';
                                                    $roleDisplayName = 'Admin Page';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $roleBadgeClass; ?>" data-role="<?php echo htmlspecialchars($userItem['role_name']); ?>">
                                                <?php echo htmlspecialchars($roleDisplayName); ?>
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
                                                <a href="admin.php?action=users&subaction=edit&id=<?php echo $userItem['user_id']; ?>"
                                                   class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($userItem['user_id'] != $_SESSION['user_id']): ?>
                                                <a href="admin.php?action=users&subaction=delete&id=<?php echo $userItem['user_id']; ?>"
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

<!-- Pagination Controls -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    แสดง <span id="showingStart">1</span> ถึง <span id="showingEnd">7</span>
                                    จาก <span id="totalRecords"><?php echo count($users); ?></span> รายการ
                                    (<span id="filteredRecords"><?php echo count($users); ?></span> รายการที่กรอง)
                                </small>
                            </div>
                            <nav aria-label="User pagination">
                                <ul class="pagination pagination-sm mb-0" id="userPagination">
                                    <!-- Pagination will be generated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

<style>
/* Table Container Styling */
.table-container {
    background-color: #fff;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table-container .table {
    margin-bottom: 0;
}

.table-container .table thead th {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 10;
    font-weight: 600;
    color: #495057;
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    border-right: 1px solid #dee2e6;
}

.table-container .table thead th:last-child {
    border-right: none;
}

.table-container .table tbody tr {
    border-bottom: 1px solid #dee2e6;
    transition: background-color 0.15s ease-in-out;
}

.table-container .table tbody tr:hover {
    background-color: #f8f9fa;
}

.table-container .table tbody tr:last-child {
    border-bottom: none;
}

.table-container .table tbody td {
    padding: 10px 8px;
    vertical-align: middle;
    border-right: 1px solid #e9ecef;
    text-align: center;
}

.table-container .table tbody td:last-child {
    border-right: none;
}

/* Role Filter Styling */
.role-filter {
    margin-right: 8px;
}

.role-filter:checked + label .badge {
    box-shadow: 0 0 0 2px #007bff;
    transform: scale(1.05);
    transition: all 0.2s ease;
}

.form-check-inline {
    margin-right: 1rem;
    margin-bottom: 0.5rem;
}

.form-check-inline .form-check-label {
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-check-inline .form-check-label:hover .badge {
    transform: scale(1.02);
}

/* Badge Styling */
.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-weight: 500;
}

/* Pagination Styling */
.pagination-sm .page-link {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.pagination-sm .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

/* Animation */
.user-row {
    transition: all 0.3s ease;
}

.user-row.hidden {
    display: none;
}

/* Card Header Enhancement */
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.card-header h6 {
    color: #495057 !important;
    margin: 0;
}

/* Button Group Styling */
.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Status Badge Styling */
.badge.bg-success {
    background-color: #28a745 !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
}

.badge.bg-primary {
    background-color: #007bff !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .table-container {
        font-size: 0.875rem;
    }

    .table-container .table thead th,
    .table-container .table tbody td {
        padding: 8px 4px;
    }

    .form-check-inline {
        display: block;
        margin-bottom: 0.5rem;
    }
}

/* Loading Animation */
.table-container.loading {
    opacity: 0.7;
    pointer-events: none;
}

.table-container.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>


<!-- jQuery (required for the filters) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const usersPerPage = 7;
    let currentPage = 1;
    let filteredUsers = [];
    let allUsers = [];

    // เก็บข้อมูลผู้ใช้ทั้งหมด
    $('#usersTable tbody tr').each(function() {
        const $row = $(this);
        const role = $row.find('[data-role]').attr('data-role');
        if (role) {
            allUsers.push({
                element: $row,
                role: role
            });
        }
    });



    // ฟังก์ชันกรองผู้ใช้ตาม role
    function filterUsers() {
        const selectedRoles = [];
        const isAllSelected = $('#roleAll').is(':checked');

        if (isAllSelected) {
            // ถ้าเลือก "ทั้งหมด" ให้แสดงทุก role
            filteredUsers = [...allUsers];
        } else {
            // เก็บ role ที่เลือก
            $('.role-filter:not(#roleAll):checked').each(function() {
                selectedRoles.push($(this).val());
            });

            // กรองผู้ใช้ตาม role ที่เลือก
            filteredUsers = allUsers.filter(user => selectedRoles.includes(user.role));
        }

        currentPage = 1; // รีเซ็ตไปหน้าแรก
        updateDisplay();
        updatePagination();
    }

    // ฟังก์ชันอัปเดตการแสดงผล
    function updateDisplay() {
        // ซ่อนทุกแถว
        allUsers.forEach(user => user.element.hide());

        // คำนวณช่วงข้อมูลที่จะแสดง
        const startIndex = (currentPage - 1) * usersPerPage;
        const endIndex = Math.min(startIndex + usersPerPage, filteredUsers.length);

        // แสดงแถวที่อยู่ในหน้าปัจจุบัน
        for (let i = startIndex; i < endIndex; i++) {
            if (filteredUsers[i]) {
                filteredUsers[i].element.show();
            }
        }

        // อัปเดตข้อมูลสถิติ
        $('#showingStart').text(filteredUsers.length > 0 ? startIndex + 1 : 0);
        $('#showingEnd').text(endIndex);
        $('#filteredRecords').text(filteredUsers.length);
    }

    // ฟังก์ชันอัปเดต pagination
    function updatePagination() {
        const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
        const $pagination = $('#userPagination');

        $pagination.empty();

        if (totalPages <= 1) return;

        // ปุ่ม Previous
        $pagination.append(`
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">ก่อนหน้า</a>
            </li>
        `);

        // หมายเลขหน้า
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                $pagination.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                $pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }

        // ปุ่ม Next
        $pagination.append(`
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">ถัดไป</a>
            </li>
        `);
    }

    // Event listeners
    $('.role-filter').on('change', function() {
        if ($(this).val() === 'all') {
            if ($(this).is(':checked')) {
                $('.role-filter:not(#roleAll)').prop('checked', false);
            }
        } else {
            if ($(this).is(':checked')) {
                $('#roleAll').prop('checked', false);
            } else {
                // ถ้าไม่มี role ไหนถูกเลือก ให้เลือก "ทั้งหมด"
                if ($('.role-filter:not(#roleAll):checked').length === 0) {
                    $('#roleAll').prop('checked', true);
                }
            }
        }
        filterUsers();
    });

    // Pagination click handler
    $(document).on('click', '#userPagination .page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && page !== currentPage) {
            currentPage = page;
            updateDisplay();
            updatePagination();

            // เลื่อนกลับไปด้านบนของตาราง
            $('.table-container').scrollTop(0);


        }
    });

    // เริ่มต้นการแสดงผล
    filterUsers();
});
</script>