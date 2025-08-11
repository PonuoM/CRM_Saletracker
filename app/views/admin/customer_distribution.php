
<?php
/**
 * Admin Customer Distribution
 * ระบบแจกลูกค้า
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i>
                        ระบบแจกลูกค้า
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshDistributionStats()">
                                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Distribution Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            ลูกค้าใน Distribution
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="distributionCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Telesales ที่พร้อมรับงาน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="availableTelesalesCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            ลูกค้า Hot
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="hotCustomersCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fire fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            ลูกค้า Warm
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="warmCustomersCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-sun fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Controls -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-random me-2"></i>
                                    แจกลูกค้าตามคำขอ
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="distributionForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="distributionQuantity" class="form-label">จำนวนลูกค้าที่ต้องการ</label>
                                            <input type="number" class="form-control" id="distributionQuantity" 
                                                   min="1" max="100" value="10" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="distributionPriority" class="form-label">ลำดับความสำคัญ</label>
                                            <select class="form-select" id="distributionPriority" required>
                                                <option value="hot_warm_cold">🔥 Hot → 🌤️ Warm → ❄️ Cold</option>
                                                <option value="hot_only">🔥 Hot เท่านั้น</option>
                                                <option value="warm_only">🌤️ Warm เท่านั้น</option>
                                                <option value="cold_only">❄️ Cold เท่านั้น</option>
                                                <option value="random">สุ่มทั้งหมด</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="distributionTelesales" class="form-label">Telesales ที่เลือก</label>
                                            <select class="form-select" id="distributionTelesales" multiple required>
                                                <option value="">กำลังโหลด...</option>
                                            </select>
                                            <small class="form-text text-muted">กด Ctrl+Click เพื่อเลือกหลายคน</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-random me-1"></i>แจกลูกค้า
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Results -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    ผลการแจกลูกค้า
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="distributionResults">
                                    <div class="text-center py-4">
                                        <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                                        <h5>ยังไม่มีการแจกลูกค้า</h5>
                                        <p class="text-muted">กรุณาเลือกจำนวนและ Telesales แล้วกดแจกลูกค้า</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Customers Preview -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-eye me-2"></i>
                                    ตัวอย่างลูกค้าที่พร้อมแจก
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="availableCustomersPreview">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                        <span class="ms-2">กำลังโหลดข้อมูล...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<script>
// Initialize customer distribution page
function initCustomerDistribution() {
    // Load initial data
    loadDistributionStats();
    loadAvailableCustomers();
    loadTelesalesList();

    // Auto refresh every 30 seconds
    setInterval(loadDistributionStats, 30000);
}

// Initialize when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomerDistribution);
} else {
    initCustomerDistribution();
}

function loadDistributionStats() {
    // Call real API endpoint
    fetch('api/customer-distribution.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;

                // Update the correct element IDs that match the HTML
                const distributionEl = document.getElementById('distributionCount');
                const availableTelesalesEl = document.getElementById('availableTelesalesCount');
                const hotCustomersEl = document.getElementById('hotCustomersCount');
                const warmCustomersEl = document.getElementById('warmCustomersCount');

                if (distributionEl) distributionEl.textContent = stats.distribution_count || 0;
                if (availableTelesalesEl) availableTelesalesEl.textContent = stats.available_telesales_count || 0;
                if (hotCustomersEl) hotCustomersEl.textContent = stats.hot_customers_count || 0;
                if (warmCustomersEl) warmCustomersEl.textContent = stats.warm_customers_count || 0;
            } else {
                console.error('Failed to load distribution stats:', data.message);
                showAlert('ไม่สามารถโหลดสถิติได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading distribution stats:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดสถิติ', 'error');
        });
}

function loadAvailableCustomers() {
    const customersEl = document.getElementById('availableCustomersPreview');
    if (!customersEl) return;

    // Call real API endpoint
    fetch('api/customer-distribution.php?action=available_customers&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customers = data.data;

                if (customers.length === 0) {
                    customersEl.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าที่พร้อมแจกในขณะนี้</div>';
                    return;
                }

                let html = '<div class="list-group">';
                customers.forEach(customer => {
                    const tempStatus = customer.temperature_status || 'cold';
                    const gradeClass = tempStatus === 'hot' ? 'text-danger' :
                                      tempStatus === 'warm' ? 'text-warning' : 'text-info';
                    const gradeIcon = tempStatus === 'hot' ? 'fas fa-fire' :
                                     tempStatus === 'warm' ? 'fas fa-sun' : 'fas fa-snowflake';
                    const gradeName = tempStatus.charAt(0).toUpperCase() + tempStatus.slice(1);

                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${customer.first_name} ${customer.last_name}</strong>
                                <span class="badge bg-secondary ms-2">
                                    <i class="${gradeIcon} me-1"></i>${gradeName}
                                </span>
                                <br>
                                <small class="text-muted">${customer.phone || 'ไม่มีเบอร์โทร'}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary" onclick="assignCustomer(${customer.customer_id})">
                                    <i class="fas fa-user-plus"></i> มอบหมาย
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';

                customersEl.innerHTML = html;
            } else {
                console.error('Failed to load available customers:', data.message);
                customersEl.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดรายการลูกค้าได้</div>';
            }
        })
        .catch(error => {
            console.error('Error loading available customers:', error);
            customersEl.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดรายการลูกค้า</div>';
        });
}

function loadTelesalesList() {
    const selectEl = document.getElementById('distributionTelesales');
    if (!selectEl) return;

    // Call real API endpoint
    fetch('api/customer-distribution.php?action=available_telesales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const telesales = data.data;

                if (telesales.length === 0) {
                    selectEl.innerHTML = '<option value="">ไม่มี Telesales ที่พร้อมรับงาน</option>';
                    return;
                }

                let options = '';
                telesales.forEach(person => {
                    const customerCount = person.customer_count || 0;
                    options += `<option value="${person.user_id}">${person.full_name} (${customerCount} ลูกค้า)</option>`;
                });

                selectEl.innerHTML = options;
            } else {
                console.error('Failed to load telesales list:', data.message);
                selectEl.innerHTML = '<option value="">ไม่สามารถโหลดรายการ Telesales ได้</option>';
            }
        })
        .catch(error => {
            console.error('Error loading telesales list:', error);
            selectEl.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดรายการ Telesales</option>';
        });
}

function assignCustomer(customerId) {
    const telesalesSelect = document.getElementById('distributionTelesales');
    const selectedOptions = telesalesSelect ? Array.from(telesalesSelect.selectedOptions) : [];

    if (selectedOptions.length === 0) {
        showAlert('กรุณาเลือก Telesales ก่อน', 'warning');
        return;
    }

    if (!confirm('คุณต้องการมอบหมายลูกค้านี้หรือไม่?')) {
        return;
    }

    // Simulate API call
    setTimeout(function() {
        showAlert('มอบหมายลูกค้าสำเร็จ (Demo)', 'success');
        loadDistributionStats();
        loadAvailableCustomers();
        loadTelesalesList();
    }, 1000);
}

function bulkAssign() {
    const telesalesSelect = document.getElementById('telesalesSelect');
    const bulkCountInput = document.getElementById('bulkCount');

    const telesalesId = telesalesSelect ? telesalesSelect.value : '';
    const count = bulkCountInput ? bulkCountInput.value : '';

    if (!telesalesId) {
        showAlert('กรุณาเลือก Telesales ก่อน', 'warning');
        return;
    }

    if (!count || count < 1) {
        showAlert('กรุณาระบุจำนวนลูกค้า', 'warning');
        return;
    }

    if (!confirm(`คุณต้องการมอบหมายลูกค้า ${count} คนหรือไม่?`)) {
        return;
    }

    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังดำเนินการ...';
    button.disabled = true;

    // Simulate API call
    setTimeout(function() {
        showAlert(`มอบหมายลูกค้า ${count} คนสำเร็จ (Demo)`, 'success');
        loadDistributionStats();
        loadAvailableCustomers();
        loadTelesalesList();
        if (bulkCountInput) bulkCountInput.value = '';

        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Insert alert at the top of the page
    const borderBottom = document.querySelector('.border-bottom');
    if (borderBottom) {
        borderBottom.insertAdjacentHTML('afterend', alertHtml);
    }

    // Auto dismiss after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
}
</script>