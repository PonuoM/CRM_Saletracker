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
    // ลูกค้าใหม่: แสดงตามสถานะ customer_status = 'new'
    const basketType = (window.currentUserRole === 'telesales' || window.currentUserRole === 'supervisor') ? 'assigned' : 'all';
    loadCustomersByBasket(basketType, 'newCustomersTable', { customer_status: 'new' });
    // ติดตาม: ใช้ API เฉพาะ followups เพื่อดึงลูกค้าที่มีนัด/ครบกำหนด
    loadFollowups('followupCustomersTable');
    // ลูกค้าเก่า: เฉพาะสถานะ customer_status = 'existing' (ใน assigned)
    loadCustomersByBasket('assigned', 'existingCustomersTable', { customer_status: 'existing' });

    // สำหรับ telesales และ supervisor: โหลด call followups อัตโนมัติ
    if (window.currentUserRole === 'telesales' || window.currentUserRole === 'supervisor') {
        loadCallFollowups('all');
    }
    
    // Add event listeners
    addEventListeners();
    
    // Load call statistics
    loadCallStats();
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
                    const basketType = (window.currentUserRole === 'telesales' || window.currentUserRole === 'supervisor') ? 'assigned' : 'all';
                    loadCustomersByBasket(basketType, 'newCustomersTable', { customer_status: 'new' });
                    break;
                case '#followup':
                    loadFollowups('followupCustomersTable');
                    break;
                case '#existing':
                    loadCustomersByBasket('assigned', 'existingCustomersTable', { customer_status: 'existing' });
                    break;
                case '#calls':
                    loadCallFollowups('all');
                    loadCallStats();
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
    
    // Text filter events (name and phone)
    const textFilters = ['nameFilter', 'phoneFilter'];
    textFilters.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', function() {
                applyFilters();
            });
        }
    });
}

/**
 * Load customers by basket type
 */
function loadCustomersByBasket(basketType, tableId, extraFilters = {}) {
    currentBasketType = basketType;
    
    // Build query parameters
    const params = new URLSearchParams();
    params.append('basket_type', basketType);
    
    // Add filters
    const tempFilter = document.getElementById('tempFilter')?.value;
    const gradeFilter = document.getElementById('gradeFilter')?.value;
    const provinceFilter = document.getElementById('provinceFilter')?.value;
    const nameFilter = document.getElementById('nameFilter')?.value;
    const phoneFilter = document.getElementById('phoneFilter')?.value;
    
    if (tempFilter) params.append('temperature', tempFilter);
    if (gradeFilter) params.append('grade', gradeFilter);
    if (provinceFilter) params.append('province', provinceFilter);
    if (nameFilter) params.append('name', nameFilter);
    if (phoneFilter) params.append('phone', phoneFilter);
    // add extra filters (e.g., customer_status)
    Object.entries(extraFilters).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') params.append(k, v);
    });
    
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

// Load followups list
function loadFollowups(tableId) {
    const tableElement = document.getElementById(tableId);
    if (tableElement) {
        tableElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">กำลังโหลดข้อมูล...</p></div>';
    }
    fetch('api/customers.php?action=followups')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCustomerTable(data.data, tableId, 'followups');
            } else {
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        })
        .catch(() => showError('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
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
                        ${basketType === 'distribution' ? '<th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>' : ''}
                        ${basketType !== 'followups' ? '<th>วันที่ได้รับ</th>' : ''}
                        <th>ชื่อลูกค้า</th>
                        <th>ผู้รับผิดชอบ</th>
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
                ${basketType === 'distribution' ? 
                    `<td><input type="checkbox" class="customer-checkbox" value="${customer.customer_id}" onchange="toggleCustomerSelection(${customer.customer_id})"></td>` : 
                    ''
                }
                ${basketType !== 'followups' ? `<td>${customer.created_at ? formatDate(customer.created_at) : '-'}</td>` : ''}
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
                <td>${escapeHtml(customer.province || '')}</td>
                <td>
                    ${daysRemaining <= 0 ? 
                        '<span class="badge bg-danger">เกินกำหนด</span>' : 
                        `<span class="badge bg-warning">${daysRemaining} วัน</span>`
                    }
                    ${customer.reason_type ? 
                        `<br><small class="text-muted">${
                            customer.reason_type === 'expiry' ? '⏰ ใกล้หมดระยะดูแล' :
                            customer.reason_type === 'appointment' ? '📅 มีนัดหมาย' :
                            '📋 อื่นๆ'
                        }</small>` : 
                        ''
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
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableHTML += `
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">แสดงสูงสุด 10 รายการต่อหน้า</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="${tableId}-pagination"></ul>
                </nav>
            </div>
        </div>
    `;
    
    tableElement.innerHTML = tableHTML;
    paginateTable(tableElement.querySelector('table'), `${tableId}-pagination`, 10);
}
/**
 * Simple client-side pagination for rendered tables
 */
function paginateTable(table, paginationId, pageSize = 10) {
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    let current = 1;

    function renderPage(page) {
        current = Math.min(Math.max(1, page), totalPages);
        rows.forEach((row, idx) => {
            const start = (current - 1) * pageSize;
            const end = start + pageSize;
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });
        renderPager();
    }

    function renderPager() {
        const pager = document.getElementById(paginationId);
        if (!pager) return;
        let html = '';
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}"><a class="page-link" href="#">«</a></li>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}"><a class="page-link" href="#">${i}</a></li>`;
        }
        html += `<li class="page-item ${current === totalPages ? 'disabled' : ''}"><a class="page-link" href="#">»</a></li>`;
        pager.innerHTML = html;
        Array.from(pager.querySelectorAll('.page-item')).forEach((li, idx, arr) => {
            li.addEventListener('click', (e) => {
                e.preventDefault();
                if (idx === 0 && current > 1) renderPage(current - 1);
                else if (idx === arr.length - 1 && current < totalPages) renderPage(current + 1);
                else if (idx > 0 && idx < arr.length - 1) renderPage(idx);
            });
        });
    }

    renderPage(1);
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
    document.getElementById('nameFilter').value = '';
    document.getElementById('phoneFilter').value = '';
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

/**
 * Load call statistics
 */
function loadCallStats() {
    fetch('api/calls.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-calls').textContent = data.stats.total_calls || 0;
                document.getElementById('answered-calls').textContent = data.stats.answered_calls || 0;
                document.getElementById('need-followup').textContent = data.stats.need_followup || 0;
                document.getElementById('overdue-followup').textContent = data.stats.overdue_followup || 0;
            }
        })
        .catch(error => {
            console.error('Error loading call stats:', error);
        });
}

/**
 * Load call follow-up customers
 */
function loadCallFollowups(filter = 'all') {
    const tableElement = document.getElementById('call-followup-table');
    if (tableElement) {
        tableElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">กำลังโหลดข้อมูล...</p></div>';
    }
    
    fetch(`api/calls.php?action=get_followup_customers&filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCallFollowupTable(data.data);
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
 * Render call follow-up table
 */
function renderCallFollowupTable(customers) {
    const tableElement = document.getElementById('call-followup-table');
    
    if (!customers || customers.length === 0) {
        tableElement.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <h5>ไม่มีลูกค้าที่ต้องติดตามการโทร</h5>
                <p class="text-muted">ทุกอย่างเรียบร้อยแล้ว</p>
            </div>
        `;
        return;
    }
    
    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ลูกค้า</th>
                        <th>เบอร์โทร</th>
                        <th>จังหวัด</th>
                        <th>ผลการโทรล่าสุด</th>
                        <th>วันที่ติดตาม</th>
                        <th>ความสำคัญ</th>
                        <th>สถานะ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    customers.forEach(customer => {
        const urgencyClass = getUrgencyClass(customer.urgency_status);
        const priorityClass = getPriorityClass(customer.followup_priority);
        
        tableHTML += `
            <tr>
                <td>
                    <strong>${escapeHtml(customer.first_name + ' ' + customer.last_name)}</strong>
                    <br>
                    <small class="text-muted">${escapeHtml(customer.customer_code || '')}</small>
                </td>
                <td>${escapeHtml(customer.phone || '')}</td>
                <td>${escapeHtml(customer.province || '')}</td>
                <td>
                    <span class="badge bg-secondary">${escapeHtml(customer.call_result || 'ไม่ระบุ')}</span>
                    <br>
                    <small class="text-muted">${formatDate(customer.last_call_date)}</small>
                </td>
                <td>
                    <span class="${urgencyClass}">${formatDate(customer.next_followup_at)}</span>
                    ${customer.days_until_followup ? `<br><small class="text-muted">${customer.days_until_followup} วัน</small>` : ''}
                </td>
                <td>
                    <span class="badge ${priorityClass}">${escapeHtml(customer.followup_priority || 'medium')}</span>
                </td>
                <td>
                    <span class="badge bg-warning">รอติดตาม</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary" onclick="viewCustomer(${customer.customer_id})" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-success" onclick="logCall(${customer.customer_id})" title="บันทึกการโทร">
                            <i class="fas fa-phone"></i>
                        </button>
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
 * Get urgency class for styling
 */
function getUrgencyClass(urgencyStatus) {
    switch (urgencyStatus) {
        case 'overdue': return 'text-danger fw-bold';
        case 'urgent': return 'text-warning fw-bold';
        case 'soon': return 'text-info';
        default: return 'text-muted';
    }
}

/**
 * Get priority class for badge styling
 */
function getPriorityClass(priority) {
    switch (priority) {
        case 'urgent': return 'bg-danger';
        case 'high': return 'bg-warning';
        case 'medium': return 'bg-info';
        case 'low': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

 