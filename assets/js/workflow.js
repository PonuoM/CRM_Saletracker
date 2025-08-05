/**
 * Workflow Management JavaScript
 * จัดการการทำงานของหน้า Workflow Management
 */

// Global variables
let workflowStats = {};
let customersForExtension = [];

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Workflow Management initialized');
    loadWorkflowData();
});

/**
 * โหลดข้อมูล Workflow ทั้งหมด
 */
function loadWorkflowData() {
    loadNewCustomerTimeout();
    loadExistingCustomerTimeout();
    loadRecentActivities();
    loadCustomersForExtension();
}

/**
 * โหลดรายการลูกค้าใหม่ที่เกิน 30 วัน
 */
function loadNewCustomerTimeout() {
    const container = document.getElementById('newCustomerTimeoutList');
    if (!container) return;
    
    fetch('api/workflow.php?action=new_customer_timeout&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayNewCustomerTimeout(data.data);
            } else {
                container.innerHTML = '<p class="text-muted text-center mb-0">ไม่มีลูกค้าใหม่ที่เกิน 30 วัน</p>';
            }
        })
        .catch(error => {
            console.error('Error loading new customer timeout:', error);
            container.innerHTML = '<p class="text-danger text-center mb-0">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
        });
}

/**
 * แสดงรายการลูกค้าใหม่ที่เกิน 30 วัน
 */
function displayNewCustomerTimeout(customers) {
    const container = document.getElementById('newCustomerTimeoutList');
    
    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>ลูกค้า</th><th>ผู้รับผิดชอบ</th><th>เกินมา</th><th>การดำเนินการ</th></tr></thead><tbody>';
    
    customers.forEach(customer => {
        html += '<tr>';
        html += `<td><strong>${customer.customer_name}</strong><br><small class="text-muted">${customer.phone || '-'}</small></td>`;
        html += `<td>${customer.assigned_user_name || '-'}</td>`;
        html += `<td><span class="badge bg-danger">${customer.days_overdue} วัน</span></td>`;
        html += `<td>
                    <button class="btn btn-sm btn-warning me-1" onclick="extendCustomerTime(${customer.customer_id}, 30)">
                        <i class="fas fa-clock"></i> ต่อเวลา
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="recallCustomer(${customer.customer_id})">
                        <i class="fas fa-undo"></i> ดึงกลับ
                    </button>
                  </td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * โหลดรายการลูกค้าเก่าที่เกิน 90 วัน
 */
function loadExistingCustomerTimeout() {
    const container = document.getElementById('existingCustomerTimeoutList');
    if (!container) return;
    
    fetch('api/workflow.php?action=existing_customer_timeout&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayExistingCustomerTimeout(data.data);
            } else {
                container.innerHTML = '<p class="text-muted text-center mb-0">ไม่มีลูกค้าเก่าที่เกิน 90 วัน</p>';
            }
        })
        .catch(error => {
            console.error('Error loading existing customer timeout:', error);
            container.innerHTML = '<p class="text-danger text-center mb-0">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
        });
}

/**
 * แสดงรายการลูกค้าเก่าที่เกิน 90 วัน
 */
function displayExistingCustomerTimeout(customers) {
    const container = document.getElementById('existingCustomerTimeoutList');
    
    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>ลูกค้า</th><th>ผู้รับผิดชอบ</th><th>คำสั่งซื้อล่าสุด</th><th>การดำเนินการ</th></tr></thead><tbody>';
    
    customers.forEach(customer => {
        const lastOrderDate = customer.last_order_date ? new Date(customer.last_order_date).toLocaleDateString('th-TH') : '-';
        
        html += '<tr>';
        html += `<td><strong>${customer.customer_name}</strong><br><small class="text-muted">${customer.phone || '-'}</small></td>`;
        html += `<td>${customer.assigned_user_name || '-'}</td>`;
        html += `<td>${lastOrderDate}<br><span class="badge bg-warning">${customer.days_overdue} วัน</span></td>`;
        html += `<td>
                    <button class="btn btn-sm btn-warning me-1" onclick="extendCustomerTime(${customer.customer_id}, 90)">
                        <i class="fas fa-clock"></i> ต่อเวลา
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="recallCustomer(${customer.customer_id})">
                        <i class="fas fa-undo"></i> ดึงกลับ
                    </button>
                  </td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * โหลดกิจกรรมล่าสุด
 */
function loadRecentActivities() {
    const container = document.getElementById('recentActivities');
    if (!container) return;
    
    fetch('api/workflow.php?action=recent_activities&limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayRecentActivities(data.data);
            } else {
                container.innerHTML = '<p class="text-muted text-center mb-0">ไม่มีกิจกรรมล่าสุด</p>';
            }
        })
        .catch(error => {
            console.error('Error loading recent activities:', error);
            container.innerHTML = '<p class="text-danger text-center mb-0">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
        });
}

/**
 * แสดงกิจกรรมล่าสุด
 */
function displayRecentActivities(activities) {
    const container = document.getElementById('recentActivities');
    
    let html = '<div class="timeline">';
    
    activities.forEach(activity => {
        const activityDate = new Date(activity.created_at).toLocaleString('th-TH');
        let icon = 'fas fa-info-circle';
        let color = 'text-info';
        
        switch (activity.activity_type) {
            case 'order':
                icon = 'fas fa-shopping-cart';
                color = 'text-success';
                break;
            case 'appointment':
                icon = 'fas fa-calendar-check';
                color = 'text-primary';
                break;
            case 'recall':
                icon = 'fas fa-undo';
                color = 'text-warning';
                break;
        }
        
        html += `<div class="timeline-item mb-3">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="${icon} ${color} fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">${activity.description}</h6>
                            <p class="mb-1 text-muted">ลูกค้า: ${activity.customer_name}</p>
                            <small class="text-muted">โดย: ${activity.user_name || '-'} | ${activityDate}</small>
                        </div>
                    </div>
                </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * โหลดรายการลูกค้าที่พร้อมต่อเวลา
 */
function loadCustomersForExtension() {
    fetch('api/workflow.php?action=customers_for_extension')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customersForExtension = data.data;
                populateCustomerSelect();
            }
        })
        .catch(error => {
            console.error('Error loading customers for extension:', error);
        });
}

/**
 * เติมข้อมูลใน dropdown เลือกลูกค้า
 */
function populateCustomerSelect() {
    const select = document.getElementById('customerId');
    if (!select) return;
    
    select.innerHTML = '<option value="">เลือกลูกค้า...</option>';
    
    customersForExtension.forEach(customer => {
        const option = document.createElement('option');
        option.value = customer.customer_id;
        option.textContent = `${customer.customer_name} (${customer.assigned_user_name || 'ไม่ระบุ'})`;
        select.appendChild(option);
    });
}

/**
 * รัน Manual Recall
 */
function runManualRecall() {
    if (!confirm('คุณต้องการรัน Manual Recall หรือไม่? การดำเนินการนี้จะดึงลูกค้าที่เกินเวลากลับไปยังตะกร้าที่เหมาะสม')) {
        return;
    }
    
    fetch('api/workflow.php?action=run_recall', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            if (data.results) {
                const results = data.results;
                showInfo(`ผลลัพธ์: ลูกค้าใหม่ ${results.new_customers_recalled} ราย, ลูกค้าเก่า ${results.existing_customers_recalled} ราย, ย้ายไป Distribution ${results.moved_to_distribution} ราย`);
            }
            // รีโหลดข้อมูล
            setTimeout(() => {
                loadWorkflowData();
                location.reload(); // รีโหลดหน้าเพื่ออัปเดตสถิติ
            }, 2000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error running manual recall:', error);
        showError('เกิดข้อผิดพลาดในการรัน Manual Recall');
    });
}

/**
 * ต่อเวลาลูกค้า (แบบด่วน)
 */
function extendCustomerTime(customerId, defaultDays = 30) {
    const reason = prompt(`เหตุผลการต่อเวลา ${defaultDays} วัน:`);
    if (!reason) return;
    
    const data = {
        customer_id: parseInt(customerId),
        extension_days: defaultDays,
        reason: reason
    };
    
    fetch('api/workflow.php?action=extend_time', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            loadWorkflowData();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error extending customer time:', error);
        showError('เกิดข้อผิดพลาดในการต่อเวลา');
    });
}

/**
 * เปิด Modal ต่อเวลา
 */
function extendCustomerTime() {
    const modal = new bootstrap.Modal(document.getElementById('extendTimeModal'));
    modal.show();
}

/**
 * ส่งฟอร์มต่อเวลา
 */
function submitExtendTime() {
    const form = document.getElementById('extendTimeForm');
    const formData = new FormData(form);
    
    const data = {
        customer_id: parseInt(formData.get('customer_id')),
        extension_days: parseInt(formData.get('extension_days')),
        reason: formData.get('reason')
    };
    
    if (!data.customer_id || !data.extension_days || !data.reason) {
        showError('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    fetch('api/workflow.php?action=extend_time', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('extendTimeModal')).hide();
            form.reset();
            loadWorkflowData();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error submitting extend time:', error);
        showError('เกิดข้อผิดพลาดในการต่อเวลา');
    });
}

/**
 * ดึงลูกค้ากลับ
 */
function recallCustomer(customerId) {
    const reason = prompt('เหตุผลการดึงกลับ:');
    if (!reason) return;
    
    const data = {
        customer_id: parseInt(customerId),
        reason: reason
    };
    
    fetch('api/customers.php?action=recall', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            loadWorkflowData();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error recalling customer:', error);
        showError('เกิดข้อผิดพลาดในการดึงลูกค้ากลับ');
    });
}

/**
 * อัปเดตสถิติ
 */
function refreshStats() {
    location.reload();
}

/**
 * แสดงข้อความสำเร็จ
 */
function showSuccess(message) {
    // ใช้ Bootstrap toast หรือ alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * แสดงข้อความผิดพลาด
 */
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * แสดงข้อความข้อมูล
 */
function showInfo(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-info alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
} 