<?php
// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (!isset($followupCustomers)) $followupCustomers = [];
if (!isset($callStats)) $callStats = [];

$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? '';
?>

<!-- Main Content -->
<div class="page-transition call-page">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">จัดการการโทรติดตาม</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> รีเฟรช
                </button>
            </div>
        </div>
    </div>

    <!-- สถิติการโทร -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">การโทรทั้งหมด</h6>
                            <h4 class="mb-0" id="totalCalls">0</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-phone fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">รับสาย</h6>
                            <h4 class="mb-0" id="answeredCalls">0</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">ต้องติดตาม</h6>
                            <h4 class="mb-0" id="needFollowup">0</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">เกินกำหนด</h6>
                            <h4 class="mb-0" id="overdueCalls">0</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตัวกรอง -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="urgencyFilter" class="form-label">ความเร่งด่วน</label>
                    <select class="form-select" id="urgencyFilter">
                        <option value="">ทั้งหมด</option>
                        <option value="overdue">เกินกำหนด</option>
                        <option value="urgent">เร่งด่วน (3 วัน)</option>
                        <option value="soon">เร็วๆ นี้ (7 วัน)</option>
                        <option value="normal">ปกติ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="resultFilter" class="form-label">ผลการโทร</label>
                    <select class="form-select" id="resultFilter">
                        <option value="">ทั้งหมด</option>
                        <option value="not_interested">ไม่สนใจ</option>
                        <option value="callback">ขอโทรกลับ</option>
                        <option value="interested">สนใจ</option>
                        <option value="complaint">ร้องเรียน</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priorityFilter" class="form-label">ความสำคัญ</label>
                    <select class="form-select" id="priorityFilter">
                        <option value="">ทั้งหมด</option>
                        <option value="urgent">เร่งด่วน</option>
                        <option value="high">สูง</option>
                        <option value="medium">ปานกลาง</option>
                        <option value="low">ต่ำ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> กรอง
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> ล้าง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางลูกค้าที่ต้องติดตาม -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-phone"></i> ลูกค้าที่ต้องติดตามการโทร
            </h5>
            <span class="badge bg-primary" id="customerCount">0 รายการ</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="followupTable">
                    <thead>
                        <tr>
                            <th>ลูกค้า</th>
                            <th>เบอร์โทร</th>
                            <th>ผู้รับผิดชอบ</th>
                            <th>ผลการโทรล่าสุด</th>
                            <th>วันติดตาม</th>
                            <th>ความสำคัญ</th>
                            <th>สถานะ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody id="followupTableBody">
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">แสดงสูงสุด 10 รายการต่อหน้า</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="followupPagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<script>
// ตัวแปรสำหรับเก็บข้อมูล
let followupCustomers = [];
let currentPage = 1;
const itemsPerPage = 10;

// โหลดข้อมูลเมื่อหน้าโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    loadFollowupCustomers();
    loadCallStats();
});

// โหลดลูกค้าที่ต้องติดตาม
function loadFollowupCustomers() {
    const filters = {
        urgency: document.getElementById('urgencyFilter').value,
        call_result: document.getElementById('resultFilter').value,
        priority: document.getElementById('priorityFilter').value
    };
    
    const queryParams = new URLSearchParams();
    if (filters.urgency) queryParams.append('urgency', filters.urgency);
    if (filters.call_result) queryParams.append('call_result', filters.call_result);
    if (filters.priority) queryParams.append('priority', filters.priority);
    
    fetch(`api/calls.php?action=get_followup_customers&${queryParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                followupCustomers = data.data || data.customers || [];
                renderFollowupTable();
                updateCustomerCount();
            } else {
                showAlert('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + (data.message || data.error));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });
}

// โหลดสถิติการโทร
function loadCallStats() {
    fetch('api/calls.php?action=get_stats&period=month')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCallStats(data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// แสดงตารางลูกค้าที่ต้องติดตาม
function renderFollowupTable() {
    const tbody = document.getElementById('followupTableBody');
    
    if (followupCustomers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> ไม่มีลูกค้าที่ต้องติดตาม
                </td>
            </tr>
        `;
        return;
    }
    
    // คำนวณข้อมูลสำหรับหน้า
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageData = followupCustomers.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageData.map(customer => `
        <tr>
            <td>
                <div>
                    <strong>${escapeHtml(customer.first_name)} ${escapeHtml(customer.last_name)}</strong>
                    <br><small class="text-muted">${escapeHtml(customer.customer_code || '')}</small>
                </div>
            </td>
            <td>${escapeHtml(customer.phone || '')}</td>
            <td>${escapeHtml(customer.assigned_to_name || '')}</td>
            <td>
                <span class="badge bg-${getCallResultColor(customer.call_result)}">
                    ${getCallResultText(customer.call_result)}
                </span>
                <br><small class="text-muted">${formatDate(customer.last_call_date)}</small>
            </td>
            <td>
                <div class="${getUrgencyClass(customer.urgency_status)}">
                    ${formatDate(customer.next_followup_at)}
                    <br><small>${getUrgencyText(customer.urgency_status)}</small>
                </div>
            </td>
            <td>
                <span class="badge bg-${getPriorityColor(customer.followup_priority)}">
                    ${getPriorityText(customer.followup_priority)}
                </span>
            </td>
            <td>
                <span class="badge bg-${getQueueStatusColor(customer.queue_status)}">
                    ${getQueueStatusText(customer.queue_status)}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-primary" onclick="logCall(${customer.customer_id})">
                        <i class="fas fa-phone"></i> โทร
                    </button>
                    <button type="button" class="btn btn-info" onclick="viewCustomer(${customer.customer_id})">
                        <i class="fas fa-eye"></i> ดู
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    // สร้าง pagination
    renderPagination();
}

// สร้าง pagination
function renderPagination() {
    const totalPages = Math.ceil(followupCustomers.length / itemsPerPage);
    const pagination = document.getElementById('followupPagination');
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // ปุ่มก่อนหน้า
    paginationHTML += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">ก่อนหน้า</a>
        </li>
    `;
    
    // หมายเลขหน้า
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            paginationHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // ปุ่มถัดไป
    paginationHTML += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">ถัดไป</a>
        </li>
    `;
    
    pagination.innerHTML = paginationHTML;
}

// เปลี่ยนหน้า
function changePage(page) {
    if (page < 1 || page > Math.ceil(followupCustomers.length / itemsPerPage)) {
        return;
    }
    currentPage = page;
    renderFollowupTable();
}

// อัปเดตจำนวนลูกค้า
function updateCustomerCount() {
    document.getElementById('customerCount').textContent = `${followupCustomers.length} รายการ`;
}

// อัปเดตสถิติการโทร
function updateCallStats(stats) {
    let totalCalls = 0;
    let answeredCalls = 0;
    let needFollowup = 0;
    let overdueCalls = 0;
    
    stats.forEach(stat => {
        totalCalls += parseInt(stat.total_calls);
        if (stat.call_status === 'answered') {
            answeredCalls += parseInt(stat.total_calls);
        }
        needFollowup += parseInt(stat.need_followup);
    });
    
    // คำนวณเกินกำหนดจากข้อมูลลูกค้า
    overdueCalls = followupCustomers.filter(c => c.urgency_status === 'overdue').length;
    
    document.getElementById('totalCalls').textContent = totalCalls;
    document.getElementById('answeredCalls').textContent = answeredCalls;
    document.getElementById('needFollowup').textContent = needFollowup;
    document.getElementById('overdueCalls').textContent = overdueCalls;
}

// ใช้ตัวกรอง
function applyFilters() {
    currentPage = 1;
    loadFollowupCustomers();
}

// ล้างตัวกรอง
function clearFilters() {
    document.getElementById('urgencyFilter').value = '';
    document.getElementById('resultFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    applyFilters();
}

// รีเฟรชข้อมูล
function refreshData() {
    loadFollowupCustomers();
    loadCallStats();
}

// บันทึกการโทร
function logCall(customerId) {
    window.location.href = `calls.php?action=log_call&customer_id=${customerId}`;
}

// ดูข้อมูลลูกค้า
function viewCustomer(customerId) {
    window.location.href = `customers.php?action=show&id=${customerId}`;
}

// ฟังก์ชันช่วยเหลือ
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH');
}

function getCallResultColor(result) {
    const colors = {
        'interested': 'success',
        'not_interested': 'secondary',
        'callback': 'warning',
        'order': 'primary',
        'complaint': 'danger'
    };
    return colors[result] || 'secondary';
}

function getCallResultText(result) {
    const texts = {
        'interested': 'สนใจ',
        'not_interested': 'ไม่สนใจ',
        'callback': 'ขอโทรกลับ',
        'order': 'สั่งซื้อ',
        'complaint': 'ร้องเรียน'
    };
    return texts[result] || result;
}

function getUrgencyClass(urgency) {
    const classes = {
        'overdue': 'text-danger fw-bold',
        'urgent': 'text-warning fw-bold',
        'soon': 'text-info',
        'normal': 'text-muted'
    };
    return classes[urgency] || 'text-muted';
}

function getUrgencyText(urgency) {
    const texts = {
        'overdue': 'เกินกำหนด',
        'urgent': 'เร่งด่วน',
        'soon': 'เร็วๆ นี้',
        'normal': 'ปกติ'
    };
    return texts[urgency] || 'ปกติ';
}

function getPriorityColor(priority) {
    const colors = {
        'urgent': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'secondary'
    };
    return colors[priority] || 'secondary';
}

function getPriorityText(priority) {
    const texts = {
        'urgent': 'เร่งด่วน',
        'high': 'สูง',
        'medium': 'ปานกลาง',
        'low': 'ต่ำ'
    };
    return texts[priority] || priority;
}

function getQueueStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'in_progress': 'info',
        'completed': 'success',
        'cancelled': 'secondary'
    };
    return colors[status] || 'secondary';
}

function getQueueStatusText(status) {
    const texts = {
        'pending': 'รอดำเนินการ',
        'in_progress': 'กำลังดำเนินการ',
        'completed': 'เสร็จสิ้น',
        'cancelled': 'ยกเลิก'
    };
    return texts[status] || status;
}

function showAlert(type, message) {
    // สร้าง alert แบบ Bootstrap
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // เพิ่ม alert ที่ด้านบนของหน้า
    const container = document.querySelector('.call-page');
    container.insertBefore(alertDiv, container.firstChild);
    
    // ลบ alert หลังจาก 5 วินาที
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
