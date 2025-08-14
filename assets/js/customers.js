/**
 * Customer Management JavaScript
 * จัดการการทำงานของหน้า Customer Management
 */

// Global variables
let selectedCustomers = [];
let currentBasketType = 'distribution';

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // If this page is a reload, clear session state for consistency across users
    try {
        const navEntry = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]);
        const isReload = (navEntry && navEntry.type === 'reload') || (performance.navigation && performance.navigation.type === 1);
        if (isReload) {
            sessionStorage.removeItem('customers_active_tab');
            sessionStorage.removeItem('customers_filters');
            sessionStorage.removeItem('customers_page_newCustomersTable');
            sessionStorage.removeItem('customers_page_followupCustomersTable');
            sessionStorage.removeItem('customers_page_existingCustomersTable');
            const params = new URLSearchParams(window.location.search);
            params.delete('tab');
            params.delete('page');
            history.replaceState(null, '', window.location.pathname + (params.toString()?('?'+params.toString()):''));
        }
    } catch(_) {}
    // Load initial data
    restoreFiltersFromStorage();
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
    // Tab change events + remember active tab
    const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            const targetId = (target || '').replace('#','');
            // persist active tab
            try {
                sessionStorage.setItem('customers_active_tab', targetId);
                const params = new URLSearchParams(window.location.search);
                params.set('tab', targetId);
                history.replaceState(null, '', window.location.pathname + '?' + params.toString());
            } catch(_) {}

            switch(target) {
                case '#new':
                    const basketType = (window.currentUserRole === 'telesales' || window.currentUserRole === 'supervisor') ? 'assigned' : 'all';
                    loadCustomersByBasket(basketType, 'newCustomersTable', { customer_status: 'new' });
                    break;
                case '#followup':
                    loadFollowups('followupCustomersTable');
                    // paginate similar to new
                    setTimeout(() => {
                        const followTable = document.querySelector('#followupCustomersTable table');
                        if (followTable) paginateTable(followTable, 'followupCustomersTable-pagination', 10, 'customers_page_followupCustomersTable');
                    }, 100);
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

    // Header filters: listen for changes in each tab
    const prefixes = ['do','new','followup','existing'];
    prefixes.forEach(p => {
        ['tempFilter','gradeFilter','provinceFilter'].forEach(id => {
            const el = document.getElementById(`${id}_${p}`);
            if (el) el.addEventListener('change', () => applyFilters());
        });
        ['nameFilter','phoneFilter'].forEach(id => {
            const el = document.getElementById(`${id}_${p}`);
            if (el) el.addEventListener('input', () => applyFilters());
        });
    });

    // Activate tab from URL or last state
    (function activateInitialTab(){
        const params = new URLSearchParams(window.location.search);
        const tabParam = params.get('tab');
        const stored = sessionStorage.getItem('customers_active_tab');
        const initial = tabParam || stored;
        if (initial) {
            const btn = document.querySelector(`[data-bs-target="#${initial}"]`);
            if (btn) {
                const t = new bootstrap.Tab(btn);
                t.show();
            }
        }
    })();
    
    // Filter change events
    const filterInputs = ['tempFilter', 'gradeFilter', 'provinceFilter'];
    filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                saveFiltersToStorage();
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
                saveFiltersToStorage();
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
                // Add paginator like New tab
                setTimeout(() => {
                    const tbl = document.querySelector(`#${tableId} table`);
                    if (tbl) paginateTable(tbl, `${tableId}-pagination`, 10, `customers_page_${tableId}`);
                }, 50);
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
            <tr 
                data-name="${escapeHtml((customer.first_name||'') + ' ' + (customer.last_name||''))}"
                data-phone="${escapeHtml(customer.phone||'')}"
                data-province="${escapeHtml(customer.province||'')}"
                data-temp="${escapeHtml(customer.temperature_status||'')}"
                data-grade="${escapeHtml(customer.customer_grade||'')}"
            >
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
    paginateTable(tableElement.querySelector('table'), `${tableId}-pagination`, 10, `customers_page_${tableId}`);

    // If rendering Do tab server-side table exists (#doTable), add pagination for it too
    try {
        const doTable = document.getElementById('doTable');
        if (doTable) paginateTable(doTable, 'doTable-pagination', 10, 'customers_page_doTable');
    } catch(_) {}
}
/**
 * Simple client-side pagination for rendered tables
 */
function paginateTable(table, paginationId, pageSize = 10, storageKey = null) {
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    let current = 1;

    const getVisibleRows = () => rows.filter(r => r.style.display !== 'none');
    const getTotalPages = () => {
        // Prefer dataset.filtered flag (set by applyFilters) → count all match candidates
        const filteredCount = rows.filter(r => (r.dataset.filtered || '1') !== '0').length;
        const visibleCount = getVisibleRows().length;
        const total = filteredCount || visibleCount || rows.length; // priority: filtered > visible > all
        return Math.max(1, Math.ceil(total / pageSize));
    };

    // restore saved page (clamped later once we know total pages)
    try {
        if (storageKey) {
            const saved = parseInt(sessionStorage.getItem(storageKey));
            if (!isNaN(saved) && saved >= 1) current = saved;
        }
    } catch(_) {}

    function renderPage(page) {
        const totalPages = getTotalPages();
        current = Math.min(Math.max(1, page), totalPages);

        // Prefer rows that pass filter (dataset.filtered !== '0');
        const filtered = rows.filter(r => (r.dataset.filtered || '1') !== '0');
        const visible = getVisibleRows();
        const candidates = (filtered.length ? filtered : (visible.length ? visible : rows));

        // hide all first
        rows.forEach(r => { r.style.display = 'none'; });

        const start = (current - 1) * pageSize;
        const end = start + pageSize;
        candidates.slice(start, end).forEach(r => { r.style.display = ''; });

        try { if (storageKey) sessionStorage.setItem(storageKey, String(current)); } catch(_) {}
        renderPager(totalPages);
    }

    function renderPager(totalPages) {
        const pager = document.getElementById(paginationId);
        if (!pager) return;

        // Minimal pager: «« « [select current/total] » »»
        let html = '';
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1">««</a></li>`;
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${current - 1}">«</a></li>`;
        // Page select
        html += `<li class="page-item">
                    <select class="form-select form-select-sm page-select" aria-label="Select page">
                        ${Array.from({length: totalPages}, (_, i) => i + 1).map(p => `<option value="${p}" ${p === current ? 'selected' : ''}>${p}</option>`).join('')}
                    </select>
                 </li>`;
        html += `<li class="page-item ${current === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${current + 1}">»</a></li>`;
        html += `<li class="page-item ${current === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}">»»</a></li>`;

        pager.innerHTML = html;

        // Click handlers for arrows
        Array.from(pager.querySelectorAll('.page-link')).forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = e.currentTarget;
                const pageAttr = target.getAttribute('data-page');
                const page = parseInt(pageAttr, 10);
                if (!isNaN(page) && page >= 1 && page !== current) {
                    renderPage(page);
                }
            });
        });
        // Change handler for select
        const sel = pager.querySelector('.page-select');
        if (sel) {
            sel.addEventListener('change', (e) => {
                const p = parseInt(e.target.value, 10);
                if (!isNaN(p)) renderPage(p);
            });
        }
    }

    // Clamp current to valid range after computing total pages once
    renderPage(current);
}

/**
 * Apply filters
 */
function applyFilters() {
    // เลือก prefix ตามแท็บแอคทีฟ
    const activePane = document.querySelector('.tab-pane.active');
    const paneId = activePane ? activePane.id : '';
    let prefix = '';
    if (paneId === 'do') prefix = 'do';
    else if (paneId === 'new') prefix = 'new';
    else if (paneId === 'followup') prefix = 'followup';
    else if (paneId === 'existing') prefix = 'existing';

    const filters = readTabFilters(prefix);
    // Normalizer: trim + collapse whitespace; remove zero-width; digits-only for phone
    const norm = (s)=> (s||'').toString().trim().replace(/\u200B|\u200C|\u200D|\uFEFF/g,'').replace(/\s+/g,' ');
    const digits = (s)=> (s||'').toString().replace(/\D/g,'');
    filters.name = norm(filters.name).toLowerCase();
    filters.phone = digits(filters.phone);

    // ฟังก์ชันใช้กรองตารางที่เรนเดอร์แล้ว (client-side)
    const filterTableRows = (tableEl) => {
        const rows = Array.from(tableEl.querySelectorAll('tbody tr'));
        rows.forEach(row => {
            const rTemp = (row.dataset.temp || '').toLowerCase();
            const rGrade = (row.dataset.grade || '').toUpperCase();
            const rProv = (row.dataset.province || '');
            const rName = norm(row.dataset.name || '').toLowerCase();
            const rPhone = digits(row.dataset.phone || '');

            const fPhone = filters.phone;
            const fPhoneNoLead = fPhone.replace(/^0+/, '');
            const phoneMatch = (!fPhone) || rPhone.includes(fPhone) || rPhone.includes(fPhoneNoLead) || ('0' + rPhone).includes(fPhone);

            const match =
                (!filters.temp || rTemp === filters.temp.toLowerCase()) &&
                (!filters.grade || rGrade === filters.grade.toUpperCase()) &&
                (!filters.province || rProv === filters.province) &&
                (!filters.name || rName.includes(filters.name)) &&
                phoneMatch;

            // mark filtered state; actual showing handled by paginator
            row.dataset.filtered = match ? '1' : '0';
        });
    };

    if (prefix === 'do') {
        const doTable = document.getElementById('doTable');
        if (doTable) {
            filterTableRows(doTable);

            // จัดเรียงความสำคัญเฉพาะรายการที่แสดงอยู่
            const rows = Array.from(doTable.querySelectorAll('tbody tr')).filter(r => r.style.display !== 'none');
            rows.sort((a, b) => {
                const aNext = a.dataset.next ? Date.parse(a.dataset.next) : null;
                const bNext = b.dataset.next ? Date.parse(b.dataset.next) : null;
                const aIsNew = a.dataset.isNew === '1';
                const bIsNew = b.dataset.isNew === '1';
                if (aNext && !bNext) return -1;
                if (!aNext && bNext) return 1;
                if (aNext && bNext) return aNext - bNext;
                if (aIsNew !== bIsNew) return aIsNew ? -1 : 1;
                const aCreated = a.dataset.created ? Date.parse(a.dataset.created) : 0;
                const bCreated = b.dataset.created ? Date.parse(b.dataset.created) : 0;
                return bCreated - aCreated;
            });
            const tbody = doTable.querySelector('tbody');
            rows.forEach(r => tbody.appendChild(r));
            paginateTable(doTable, 'doTable-pagination', 10, 'customers_page_doTable');
        }
        return;
    }

    // สำหรับ new/existing/followup ใช้ client-side filter บนตารางที่โหลดแล้ว
    const tableId = getCurrentTableId();
    const table = document.querySelector(`#${tableId} table`) || document.querySelector('#call-followup-table table');
        if (table) {
            filterTableRows(table);
            // รีเฟรชเพจจิ้งถ้ามี
            const pagerId = (tableId ? `${tableId}-pagination` : 'followupCustomersTable-pagination');
            paginateTable(table, pagerId, 10, `customers_page_${tableId||'followupCustomersTable'}`);
        }
}

function readTabFilters(prefix) {
    const g = id => document.getElementById(`${id}_${prefix}`);
    return {
        name: (g('nameFilter')?.value || '').trim(),
        phone: (g('phoneFilter')?.value || '').trim(),
        temp: g('tempFilter')?.value || '',
        grade: g('gradeFilter')?.value || '',
        province: g('provinceFilter')?.value || ''
    };
}

function clearTabFilters(prefix) {
    const ids = ['nameFilter','phoneFilter','tempFilter','gradeFilter','provinceFilter'];
    ids.forEach(id => { const el = document.getElementById(`${id}_${prefix}`); if (el) el.value = ''; });
    applyFilters();
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
    saveFiltersToStorage();
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

// Persist/restore filters
function saveFiltersToStorage() {
    try {
        const filters = {
            temp: document.getElementById('tempFilter')?.value || '',
            grade: document.getElementById('gradeFilter')?.value || '',
            province: document.getElementById('provinceFilter')?.value || '',
            name: document.getElementById('nameFilter')?.value || '',
            phone: document.getElementById('phoneFilter')?.value || ''
        };
        sessionStorage.setItem('customers_filters', JSON.stringify(filters));
    } catch(_) {}
}

function restoreFiltersFromStorage() {
    try {
        const raw = sessionStorage.getItem('customers_filters');
        if (!raw) return;
        const f = JSON.parse(raw);
        if (document.getElementById('tempFilter')) document.getElementById('tempFilter').value = f.temp || '';
        if (document.getElementById('gradeFilter')) document.getElementById('gradeFilter').value = f.grade || '';
        if (document.getElementById('provinceFilter')) document.getElementById('provinceFilter').value = f.province || '';
        if (document.getElementById('nameFilter')) document.getElementById('nameFilter').value = f.name || '';
        if (document.getElementById('phoneFilter')) document.getElementById('phoneFilter').value = f.phone || '';
    } catch(_) {}
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
    try {
        const idField = document.getElementById('callCustomerId');
        const modalEl = document.getElementById('logCallModal');
        if (!idField || !modalEl) {
            console.error('LogCall modal elements not found', { idField, modalEl });
            alert('ไม่พบฟอร์มบันทึกการโทรในหน้านี้');
            return;
        }
        idField.value = customerId;
        const form = document.getElementById('logCallForm');
        if (form) form.reset();
        // เตรียมชุดตัวเลือกผลการโทรแบบครบถ้วน (ไม่จำกัดตามสถานะ)
        try { updateCallResultOptions(true); } catch(_) {}
        // Ensure modal is under <body> to avoid transform/fixed-position issues
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
        modal.show();
    } catch (e) {
        console.error('Error opening log call modal:', e);
        alert('เกิดข้อผิดพลาดในการเปิดหน้าต่างบันทึกการโทร');
    }
}

function updateCallResultOptions(forceAll = false) {
    try {
        const resultSel = document.getElementById('callResult');
        if (!resultSel) return;

        const keep = resultSel.value; // keep current selection if still available
        const list = ['สั่งซื้อ','สนใจ','Add Line แล้ว','ต้องการซื้อทางเพจ','น้ำท่วม','รอติดต่อใหม่','นัดหมาย','เบอร์ไม่ถูก','ไม่สะดวกคุย','ไม่สนใจ','อย่าโทรมาอีก','ตัดสายทิ้ง','สายไม่ว่าง','ติดต่อไม่ได้'];
        resultSel.innerHTML = '<option value="">เลือกผลการโทร</option>' + list.map(t=>`<option value="${t}">${t}</option>`).join('');
        // try restore
        if (keep && list.includes(keep)) resultSel.value = keep;
    } catch(_) {}
}

/**
 * Submit call log
 */
function submitCallLog() {
    const customerId = document.getElementById('callCustomerId').value;
    const callType = document.getElementById('callType').value;
    const callStatus = document.getElementById('callStatus').value;
    let callResult = document.getElementById('callResult').value;
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
    
    const submitBtn = document.querySelector('#logCallModal .btn-primary');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...'; }

    fetch('api/customers.php?action=log_call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        if (!response.ok) {
            const txt = await response.text().catch(()=>'');
            throw new Error(`HTTP ${response.status}: ${txt.substring(0,200)}`);
        }
        return response.json();
    })
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
        showError('เกิดข้อผิดพลาดในการบันทึกการโทร: ' + (error.message || ''));
    })
    .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = 'บันทึก'; } });
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
                    <span class="badge bg-secondary">${escapeHtml(mapCallResultToThai(customer.call_result) || 'ไม่ระบุ')}</span>
                    <br>
                    <small class="text-muted">${formatDate(customer.next_followup_at || customer.last_call_date)}</small>
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
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="small text-muted">แสดงสูงสุด 10 รายการต่อหน้า</div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="callFollowup-pagination"></ul>
            </nav>
        </div>
    `;
    
    tableElement.innerHTML = tableHTML;
    // Apply minimal pager to calls table as well
    const tbl = tableElement.querySelector('table');
    if (tbl) paginateTable(tbl, 'callFollowup-pagination', 10, 'customers_page_callFollowup');
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

// Map call result code to Thai label (for list rendering)
function mapCallResultToThai(result) {
    switch (result) {
        case 'order': return 'สั่งซื้อ';
        case 'interested': return 'สนใจ';
        case 'add_line': return 'Add Line แล้ว';
        case 'buy_on_page': return 'ต้องการซื้อทางเพจ';
        case 'flood': return 'น้ำท่วม';
        case 'callback': return 'รอติดต่อใหม่';
        case 'appointment': return 'นัดหมาย';
        case 'invalid_number': return 'เบอร์ไม่ถูก';
        case 'not_convenient': return 'ไม่สะดวกคุย';
        case 'not_interested': return 'ไม่สนใจ';
        case 'do_not_call': return 'อย่าโทรมาอีก';
        case 'busy': return 'สายไม่ว่าง';
        case 'unable_to_contact': return 'ติดต่อไม่ได้';
        case 'hangup': return 'ตัดสายทิ้ง';
        default: return result || '';
    }
}

 