/**
 * Customer Management JavaScript
 * จัดการการทำงานของหน้า Customer Management
 */

// Global variables
let selectedCustomers = [];
let currentBasketType = 'distribution';

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadCustomersByBasket('distribution', 'newCustomersTable');
    loadCustomersByBasket('waiting', 'followupCustomersTable');
    loadCustomersByBasket('assigned', 'existingCustomersTable');
    
    // Add event listeners
    addEventListeners();
});

/**
 * Add event listeners
 */
function addEventListeners() {
    // Tab change events
    const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            switch(target) {
                case '#new':
                    loadCustomersByBasket('distribution', 'newCustomersTable');
                    break;
                case '#followup':
                    loadCustomersByBasket('waiting', 'followupCustomersTable');
                    break;
                case '#existing':
                    loadCustomersByBasket('assigned', 'existingCustomersTable');
                    break;
            }
        });
    });
    
    // Filter change events
    const filterInputs = ['tempFilter', 'gradeFilter', 'provinceFilter'];
    filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                applyFilters();
            });
        }
    });
}

/**
 * Load customers by basket type
 */
function loadCustomersByBasket(basketType, tableId) {
    currentBasketType = basketType;
    
    // Build query parameters
    const params = new URLSearchParams();
    params.append('basket_type', basketType);
    
    // Add filters
    const tempFilter = document.getElementById('tempFilter')?.value;
    const gradeFilter = document.getElementById('gradeFilter')?.value;
    const provinceFilter = document.getElementById('provinceFilter')?.value;
    
    if (tempFilter) params.append('temperature', tempFilter);
    if (gradeFilter) params.append('grade', gradeFilter);
    if (provinceFilter) params.append('province', provinceFilter);
    
    // Show loading
    const tableElement = document.getElementById(tableId);
    if (tableElement) {
        tableElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">กำลังโหลดข้อมูล...</p></div>';
    }
    
    // Fetch data
    fetch(`api/customers.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCustomerTable(data.data, tableId, basketType);
            } else {
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });
}

/**
 * Render customer table
 */
function renderCustomerTable(customers, tableId, basketType) {
    const tableElement = document.getElementById(tableId);
    if (!tableElement) return;
    
    if (customers.length === 0) {
        tableElement.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>ไม่มีข้อมูลลูกค้า</h5>
                <p class="text-muted">ไม่พบลูกค้าในตะกร้านี้</p>
            </div>
        `;
        return;
    }
    
    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            ${basketType === 'distribution' ? '<input type="checkbox" id="selectAll" onchange="toggleSelectAll()">' : ''}
                        </th>
                        <th>วันที่ได้รับ</th>
                        <th>ชื่อลูกค้า</th>
                        <th>ผู้รับผิดชอบ</th>
                        <th>ที่อยู่</th>
                        <th>จังหวัด</th>
                        <th>เวลาที่เหลือ</th>
                        <th>สถานะ</th>
                        <th>เกรด</th>
                        <th>การติดต่อ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    customers.forEach(customer => {
        const daysRemaining = calculateDaysRemaining(customer.customer_time_expiry);
        const tempIcons = {
            'hot': '🔥',
            'warm': '🌤️',
            'cold': '❄️',
            'frozen': '🧊'
        };
        
        tableHTML += `
            <tr>
                <td>
                    ${basketType === 'distribution' ? 
                        `<input type="checkbox" class="customer-checkbox" value="${customer.customer_id}" onchange="toggleCustomerSelection(${customer.customer_id})">` : 
                        ''
                    }
                </td>
                <td>${formatDate(customer.created_at)}</td>
                <td>
                    <strong>${escapeHtml(customer.first_name + ' ' + customer.last_name)}</strong>
                    <br>
                    <small class="text-muted">${escapeHtml(customer.customer_code || '')}</small>
                </td>
                <td>
                    ${customer.assigned_to_name ? 
                        `<span class="badge bg-info">${escapeHtml(customer.assigned_to_name)}</span>` : 
                        '<span class="text-muted">-</span>'
                    }
                </td>
                <td>${escapeHtml(customer.address || '')}</td>
                <td>${escapeHtml(customer.province || '')}</td>
                <td>
                    ${daysRemaining <= 0 ? 
                        '<span class="badge bg-danger">เกินกำหนด</span>' : 
                        `<span class="badge bg-warning">${daysRemaining} วัน</span>`
                    }
                </td>
                <td>
                    ${tempIcons[customer.temperature_status] || '❓'}
                    ${customer.temperature_status ? customer.temperature_status.charAt(0).toUpperCase() + customer.temperature_status.slice(1) : ''}
                </td>
                <td>
                    <span class="badge bg-${getGradeColor(customer.customer_grade)}">${customer.customer_grade}</span>
                </td>
                <td>${escapeHtml(customer.phone || '')}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary" onclick="viewCustomer(${customer.customer_id})" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-success" onclick="logCall(${customer.customer_id})" title="บันทึกการโทร">
                            <i class="fas fa-phone"></i>
                        </button>
                        ${basketType === 'assigned' && window.currentUserRole !== 'telesales' ? 
                            `<button class="btn btn-warning" onclick="recallCustomer(${customer.customer_id})" title="ดึงกลับ">
                                <i class="fas fa-undo"></i>
                            </button>` : 
                            ''
                        }
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableHTML += `
                </tbody>
            </table>
        </div>
    `;
    
    tableElement.innerHTML = tableHTML;
}

/**
 * Apply filters
 */
function applyFilters() {
    loadCustomersByBasket(currentBasketType, getCurrentTableId());
}

/**
 * Clear filters
 */
function clearFilters() {
    document.getElementById('tempFilter').value = '';
    document.getElementById('gradeFilter').value = '';
    document.getElementById('provinceFilter').value = '';
    applyFilters();
}

/**
 * Get current table ID based on active tab
 */
function getCurrentTableId() {
    const activeTab = document.querySelector('.tab-pane.active');
    if (!activeTab) return 'newCustomersTable';
    
    switch (activeTab.id) {
        case 'new': return 'newCustomersTable';
        case 'followup': return 'followupCustomersTable';
        case 'existing': return 'existingCustomersTable';
        default: return 'newCustomersTable';
    }
}

/**
 * Toggle select all customers
 */
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            selectedCustomers.push(parseInt(checkbox.value));
        } else {
            selectedCustomers = selectedCustomers.filter(id => id !== parseInt(checkbox.value));
        }
    });
    
    updateSelectedCustomersDisplay();
}

/**
 * Toggle customer selection
 */
function toggleCustomerSelection(customerId) {
    const index = selectedCustomers.indexOf(customerId);
    if (index > -1) {
        selectedCustomers.splice(index, 1);
    } else {
        selectedCustomers.push(customerId);
    }
    
    updateSelectedCustomersDisplay();
}

/**
 * Update selected customers display
 */
function updateSelectedCustomersDisplay() {
    const display = document.getElementById('selectedCustomers');
    if (!display) return;
    
    if (selectedCustomers.length === 0) {
        display.innerHTML = '<p class="text-muted">เลือกลูกค้าจากตารางด้านล่าง</p>';
        return;
    }
    
    let html = '<div class="mb-2"><strong>ลูกค้าที่เลือก:</strong></div>';
    selectedCustomers.forEach(id => {
        html += `<span class="badge bg-primary me-1 mb-1">ID: ${id}</span>`;
    });
    html += `<div class="mt-2"><small class="text-muted">จำนวน: ${selectedCustomers.length} รายการ</small></div>`;
    
    display.innerHTML = html;
}

/**
 * Show assign modal
 */
function showAssignModal() {
    selectedCustomers = [];
    updateSelectedCustomersDisplay();
    
    // Load available customers
    loadAvailableCustomers();
    
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}

/**
 * Load available customers for assignment
 */
function loadAvailableCustomers() {
    const tableElement = document.getElementById('availableCustomersTable');
    tableElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>';
    
    fetch('api/customers.php?basket_type=distribution')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAvailableCustomersTable(data.data);
            } else {
                tableElement.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableElement.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ</div>';
        });
}

/**
 * Render available customers table
 */
function renderAvailableCustomersTable(customers) {
    const tableElement = document.getElementById('availableCustomersTable');
    
    if (customers.length === 0) {
        tableElement.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าในตะกร้าแจก</div>';
        return;
    }
    
    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllAvailable" onchange="toggleSelectAllAvailable()"></th>
                        <th>ชื่อลูกค้า</th>
                        <th>เบอร์โทร</th>
                        <th>จังหวัด</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    customers.forEach(customer => {
        const tempIcons = {
            'hot': '🔥',
            'warm': '🌤️',
            'cold': '❄️',
            'frozen': '🧊'
        };
        
        tableHTML += `
            <tr>
                <td>
                    <input type="checkbox" class="available-customer-checkbox" value="${customer.customer_id}" onchange="toggleAvailableCustomerSelection(${customer.customer_id})">
                </td>
                <td>${escapeHtml(customer.first_name + ' ' + customer.last_name)}</td>
                <td>${escapeHtml(customer.phone || '')}</td>
                <td>${escapeHtml(customer.province || '')}</td>
                <td>
                    ${tempIcons[customer.temperature_status] || '❓'}
                    ${customer.temperature_status ? customer.temperature_status.charAt(0).toUpperCase() + customer.temperature_status.slice(1) : ''}
                </td>
            </tr>
        `;
    });
    
    tableHTML += `
                </tbody>
            </table>
        </div>
    `;
    
    tableElement.innerHTML = tableHTML;
}

/**
 * Toggle select all available customers
 */
function toggleSelectAllAvailable() {
    const selectAll = document.getElementById('selectAllAvailable');
    const checkboxes = document.querySelectorAll('.available-customer-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            selectedCustomers.push(parseInt(checkbox.value));
        } else {
            selectedCustomers = selectedCustomers.filter(id => id !== parseInt(checkbox.value));
        }
    });
    
    updateSelectedCustomersDisplay();
}

/**
 * Toggle available customer selection
 */
function toggleAvailableCustomerSelection(customerId) {
    const index = selectedCustomers.indexOf(customerId);
    if (index > -1) {
        selectedCustomers.splice(index, 1);
    } else {
        selectedCustomers.push(customerId);
    }
    
    updateSelectedCustomersDisplay();
}

/**
 * Assign customers
 */
function assignCustomers() {
    const telesalesId = document.getElementById('telesalesSelect').value;
    
    if (!telesalesId) {
        showError('กรุณาเลือก Telesales');
        return;
    }
    
    if (selectedCustomers.length === 0) {
        showError('กรุณาเลือกลูกค้าที่จะมอบหมาย');
        return;
    }
    
    const data = {
        telesales_id: parseInt(telesalesId),
        customer_ids: selectedCustomers
    };
    
    fetch('api/customers.php', {
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
            bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
            loadCustomersByBasket('distribution', 'newCustomersTable');
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการมอบหมายลูกค้า');
    });
}

/**
 * View customer details
 */
function viewCustomer(customerId) {
    window.location.href = `customers.php?action=show&id=${customerId}`;
}

/**
 * View order details
 */
function viewOrder(orderId) {
    window.location.href = `orders.php?action=show&id=${orderId}`;
}

/**
 * Log call for customer
 */
function logCall(customerId) {
    document.getElementById('callCustomerId').value = customerId;
    
    const modal = new bootstrap.Modal(document.getElementById('logCallModal'));
    modal.show();
}

/**
 * Submit call log
 */
function submitCallLog() {
    const customerId = document.getElementById('callCustomerId').value;
    const callType = document.getElementById('callType').value;
    const callStatus = document.getElementById('callStatus').value;
    const callResult = document.getElementById('callResult').value;
    const duration = document.getElementById('callDuration').value;
    const notes = document.getElementById('callNotes').value;
    const nextAction = document.getElementById('nextAction').value;
    const nextFollowup = document.getElementById('nextFollowup').value;
    
    if (!callStatus) {
        showError('กรุณาเลือกสถานะการโทร');
        return;
    }
    
    const data = {
        customer_id: parseInt(customerId),
        call_type: callType,
        call_status: callStatus,
        call_result: callResult || null,
        duration: parseInt(duration) || 0,
        notes: notes,
        next_action: nextAction,
        next_followup: nextFollowup || null
    };
    
    fetch('api/customers.php?action=log_call', {
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
            bootstrap.Modal.getInstance(document.getElementById('logCallModal')).hide();
            // Reload current tab
            loadCustomersByBasket(currentBasketType, getCurrentTableId());
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการบันทึกการโทร');
    });
}

/**
 * Recall customer
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
            loadCustomersByBasket(currentBasketType, getCurrentTableId());
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการดึงลูกค้ากลับ');
    });
}

/**
 * Export customers
 */
function exportCustomers() {
    const params = new URLSearchParams();
    params.append('basket_type', currentBasketType);
    
    // Add filters
    const tempFilter = document.getElementById('tempFilter')?.value;
    const gradeFilter = document.getElementById('gradeFilter')?.value;
    const provinceFilter = document.getElementById('provinceFilter')?.value;
    
    if (tempFilter) params.append('temperature', tempFilter);
    if (gradeFilter) params.append('grade', gradeFilter);
    if (provinceFilter) params.append('province', provinceFilter);
    
    window.open(`api/customers.php?action=export&${params.toString()}`, '_blank');
}

// Utility functions

/**
 * Calculate days remaining
 */
function calculateDaysRemaining(recallDate) {
    if (!recallDate) return 0;
    
    const recall = new Date(recallDate);
    const now = new Date();
    const diffTime = recall - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays;
}

/**
 * Format date
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH');
}

/**
 * Get grade color
 */
function getGradeColor(grade) {
    switch (grade) {
        case 'A+': return 'success';
        case 'A': return 'primary';
        case 'B': return 'info';
        case 'C': return 'warning';
        case 'D': return 'secondary';
        default: return 'secondary';
    }
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show success message
 */
function showSuccess(message) {
    // You can implement a toast or alert system here
    alert('สำเร็จ: ' + message);
}

/**
 * Show error message
 */
function showError(message) {
    // You can implement a toast or alert system here
    alert('ข้อผิดพลาด: ' + message);
} 