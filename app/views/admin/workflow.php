
<?php
/**
 * Admin Workflow Management
 * จัดการ Workflow
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-project-diagram me-2"></i>
                        Workflow Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="runManualRecall()">
                                <i class="fas fa-sync me-1"></i>รัน Recall เอง
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="extendCustomerTime()">
                                <i class="fas fa-clock me-1"></i>ต่อเวลา
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="refreshStats()">
                                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Workflow Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            ลูกค้าที่ต้อง Recall
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['pending_recall']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            ลูกค้าใหม่เกิน 30 วัน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['new_customer_timeout']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
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
                                            ลูกค้าเก่าเกิน 90 วัน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['existing_customer_timeout']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
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
                                            ลูกค้า Active วันนี้
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['active_today']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Rules -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>
                                    กฎการทำงาน Workflow
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">📅 ลูกค้าใหม่ (30 วัน)</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>หากไม่มีการอัปเดตภายใน 30 วัน → ดึงกลับไป Distribution Basket</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>หากมีการสร้างนัดหมาย → ต่อเวลา 30 วัน</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>หากมีการขาย → ต่อเวลา 90 วัน</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-warning">⏰ ลูกค้าเก่า (90 วัน)</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>หากไม่มีคำสั่งซื้อภายใน 90 วัน → ดึงกลับไป Waiting Basket</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>หากมีการสร้างนัดหมาย → ต่อเวลา 30 วัน</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>หากมีการขาย → ต่อเวลา 90 วัน</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Lists -->
                <div class="row">
                    <!-- New Customers Timeout -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-clock me-2"></i>
                                    ลูกค้าใหม่เกิน 30 วัน
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="newCustomerTimeoutList">
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

                    <!-- Existing Customers Timeout -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-times me-2"></i>
                                    ลูกค้าเก่าเกิน 90 วัน
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="existingCustomerTimeoutList">
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

                <!-- Recent Activities -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    กิจกรรมล่าสุด
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="recentActivities">
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
// Initialize workflow page
function initWorkflow() {
    console.log('Initializing workflow page...');

    // Load initial stats
    refreshStats();

    // Auto refresh every 30 seconds
    setInterval(refreshStats, 30000);

    console.log('Workflow page initialized successfully');
}

// Initialize when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWorkflow);
} else {
    initWorkflow();
}

function refreshStats() {
    // Call real API endpoint with absolute path
    fetch('/Customer/api/workflow.php?action=stats')
        .then(response => {
            console.log('API Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const stats = data.data;

                // Update the stats cards with correct data structure
                updateStatsCard('pending_recall', stats.pending_recall || 0);
                updateStatsCard('new_customer_timeout', stats.new_customer_timeout || 0);
                updateStatsCard('existing_customer_timeout', stats.existing_customer_timeout || 0);
                updateStatsCard('active_today', stats.active_today || 0);

                console.log('Workflow stats loaded successfully:', stats);
            } else {
                console.error('Failed to load workflow stats:', data.message);
                showAlert('ไม่สามารถโหลดสถิติ Workflow ได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading workflow stats:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดสถิติ Workflow: ' + error.message, 'error');

            // Show error in stats cards
            updateStatsCard('pending_recall', 'Error');
            updateStatsCard('new_customer_timeout', 'Error');
            updateStatsCard('existing_customer_timeout', 'Error');
            updateStatsCard('active_today', 'Error');
        });

    // Load customer lists
    loadNewCustomerTimeoutList();
    loadExistingCustomerTimeoutList();

    // Update recent activities
    loadRecentActivities();
}

function updateStatsCard(statType, value) {
    // Find the stats card and update the value
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        const cardText = card.textContent;
        if (statType === 'pending_recall' && cardText.includes('ลูกค้าที่ต้อง Recall')) {
            const valueEl = card.querySelector('.h5');
            if (valueEl) valueEl.textContent = new Intl.NumberFormat('th-TH').format(value);
        } else if (statType === 'new_customer_timeout' && cardText.includes('ลูกค้าใหม่เกิน 30 วัน')) {
            const valueEl = card.querySelector('.h5');
            if (valueEl) valueEl.textContent = new Intl.NumberFormat('th-TH').format(value);
        } else if (statType === 'existing_customer_timeout' && cardText.includes('ลูกค้าเก่าเกิน 90 วัน')) {
            const valueEl = card.querySelector('.h5');
            if (valueEl) valueEl.textContent = new Intl.NumberFormat('th-TH').format(value);
        } else if (statType === 'active_today' && cardText.includes('ลูกค้า Active วันนี้')) {
            const valueEl = card.querySelector('.h5');
            if (valueEl) valueEl.textContent = new Intl.NumberFormat('th-TH').format(value);
        }
    });
}

function loadNewCustomerTimeoutList() {
    const listEl = document.getElementById('newCustomerTimeoutList');
    if (!listEl) return;

    fetch('/Customer/api/workflow.php?action=new_customer_timeout&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customers = data.data;

                if (customers.length === 0) {
                    listEl.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าใหม่ที่เกิน 30 วัน</div>';
                    return;
                }

                let html = '<div class="list-group list-group-flush">';
                customers.forEach(customer => {
                    const daysAgo = Math.floor((new Date() - new Date(customer.assigned_at)) / (1000 * 60 * 60 * 24));
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${customer.name}</h6>
                                <small class="text-danger">${daysAgo} วันที่แล้ว</small>
                            </div>
                            <p class="mb-1">${customer.phone || 'ไม่มีเบอร์โทร'}</p>
                            <small>มอบหมายให้: ${customer.assigned_to_name || 'ไม่ระบุ'}</small>
                        </div>
                    `;
                });
                html += '</div>';
                listEl.innerHTML = html;
            } else {
                listEl.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้</div>';
            }
        })
        .catch(error => {
            console.error('Error loading new customer timeout list:', error);
            listEl.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
}

function loadExistingCustomerTimeoutList() {
    const listEl = document.getElementById('existingCustomerTimeoutList');
    if (!listEl) return;

    fetch('/Customer/api/workflow.php?action=existing_customer_timeout&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customers = data.data;

                if (customers.length === 0) {
                    listEl.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าเก่าที่เกิน 90 วัน</div>';
                    return;
                }

                let html = '<div class="list-group list-group-flush">';
                customers.forEach(customer => {
                    const daysAgo = Math.floor((new Date() - new Date(customer.last_order_date || customer.assigned_at)) / (1000 * 60 * 60 * 24));
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${customer.name}</h6>
                                <small class="text-warning">${daysAgo} วันที่แล้ว</small>
                            </div>
                            <p class="mb-1">${customer.phone || 'ไม่มีเบอร์โทร'}</p>
                            <small>มอบหมายให้: ${customer.assigned_to_name || 'ไม่ระบุ'}</small>
                        </div>
                    `;
                });
                html += '</div>';
                listEl.innerHTML = html;
            } else {
                listEl.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้</div>';
            }
        })
        .catch(error => {
            console.error('Error loading existing customer timeout list:', error);
            listEl.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
}

function loadRecentActivities() {
    const activitiesEl = document.getElementById('recentActivities');
    if (!activitiesEl) return;

    // Call real API endpoint
    fetch('/Customer/api/workflow.php?action=recent_activities&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activities = data.data;

                if (activities.length === 0) {
                    activitiesEl.innerHTML = '<div class="alert alert-info">ไม่มีกิจกรรมล่าสุด</div>';
                    return;
                }

                let html = '';
                activities.forEach(activity => {
                    const iconClass = getActivityIcon(activity.activity_type);
                    const typeClass = getActivityTypeClass(activity.activity_type);
                    const timeAgo = formatTimeAgo(activity.created_at);

                    html += `
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <i class="fas fa-${iconClass} text-${typeClass}"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold">${activity.title || activity.activity_type}</div>
                                <div class="text-muted small">${activity.description || activity.notes}</div>
                                <div class="text-muted small">${timeAgo}</div>
                            </div>
                        </div>
                    `;
                });

                activitiesEl.innerHTML = html;
            } else {
                console.error('Failed to load recent activities:', data.message);
                activitiesEl.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดกิจกรรมล่าสุดได้</div>';
            }
        })
        .catch(error => {
            console.error('Error loading recent activities:', error);
            activitiesEl.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดกิจกรรมล่าสุด</div>';
        });
}

// Helper functions for activity display
function getActivityIcon(activityType) {
    const iconMap = {
        'distribution': 'user-plus',
        'recall': 'undo',
        'call': 'phone',
        'appointment': 'calendar',
        'order': 'shopping-cart',
        'assignment': 'user-check',
        'expiry': 'clock',
        'manual_recall': 'sync'
    };
    return iconMap[activityType] || 'info-circle';
}

function getActivityTypeClass(activityType) {
    const typeMap = {
        'distribution': 'success',
        'recall': 'warning',
        'call': 'info',
        'appointment': 'primary',
        'order': 'success',
        'assignment': 'success',
        'expiry': 'warning',
        'manual_recall': 'info'
    };
    return typeMap[activityType] || 'secondary';
}

function formatTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'เมื่อสักครู่';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} นาทีที่แล้ว`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} ชั่วโมงที่แล้ว`;
    return `${Math.floor(diffInSeconds / 86400)} วันที่แล้ว`;
}

function runManualRecall() {
    if (!confirm('คุณต้องการรัน Manual Recall หรือไม่?')) {
        return;
    }

    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังดำเนินการ...';
    button.disabled = true;

    // Simulate API call with timeout
    setTimeout(function() {
        showAlert('รัน Manual Recall สำเร็จ (Demo)', 'success');
        refreshStats();
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

function extendCustomerTime() {
    if (!confirm('คุณต้องการต่อเวลาลูกค้าหรือไม่?')) {
        return;
    }

    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังดำเนินการ...';
    button.disabled = true;

    // Simulate API call with timeout
    setTimeout(function() {
        showAlert('ต่อเวลาลูกค้าสำเร็จ (Demo)', 'success');
        refreshStats();
        button.innerHTML = originalText;
        button.disabled = false;
    }, 1500);
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