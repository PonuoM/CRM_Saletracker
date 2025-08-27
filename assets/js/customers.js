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
            // เก็บ active tab ไว้หลัง reload (สำหรับการบันทึกการโทร)
            // sessionStorage.removeItem('customers_active_tab'); // ไม่ลบเพื่อให้จำ tab ได้
            // sessionStorage.removeItem('customers_filters'); // ไม่ลบ filters เพื่อให้จำได้
            // sessionStorage.removeItem('customers_all_filters'); // ไม่ลบ filters เพื่อให้จำได้
            sessionStorage.removeItem('customers_page_allCustomersTable');
            const params = new URLSearchParams(window.location.search);
            params.delete('page');
            // เก็บ tab parameter ไว้
            const activeTab = sessionStorage.getItem('customers_active_tab');
            if (activeTab) {
                params.set('tab', activeTab);
            } else {
                params.delete('tab');
            }
            history.replaceState(null, '', window.location.pathname + (params.toString()?('?'+params.toString()):''));
        }
    } catch(_) {}
    
    // Restore filters from storage instead of clearing them
    restoreFiltersFromStorage();
    
    // Initialize tags
    if (typeof loadUserTags === 'function') {
        loadUserTags();
    }
    
    // โหลด tag filter state ที่บันทึกไว้ หรือ โหลดลูกค้าทั้งหมด
    const hasSavedTagFilters = loadSavedTagFilters();
    
    // ถ้าไม่มี saved tag filters ให้โหลดลูกค้าทั้งหมดตามปกติ
    if (!hasSavedTagFilters) {
        loadAllCustomers();
    }
    
    // Add event listeners
    addEventListeners();
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

            // Restore filters when switching tabs instead of clearing them
            if (target === '#all') {
                restoreFiltersFromStorage();
                // Apply the restored filters to load filtered data
                setTimeout(() => {
                    const filters = getAllCustomersFilters();
                    if (filters && Object.values(filters).some(v => v !== '' && v !== false)) {
                        loadAllCustomersWithFilters(filters);
                    } else {
                        loadAllCustomers();
                    }
                }, 100);
            } else if (target === '#do') {
                // For Do tab, load customers without clearing filters
                loadAllCustomers();
            }
        });
    });

    // Header filters: listen for changes in each tab
    const prefixes = ['do'];
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
                const filters = getAllCustomersFilters();
                saveFiltersToStorage(filters);
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
                const filters = getAllCustomersFilters();
                saveFiltersToStorage(filters);
                applyFilters();
            });
        }
    });
    
    // Add event listeners for "All Customers" tab filters
    const allTabFilterIds = ['nameFilter_all', 'phoneFilter_all', 'temperatureFilter_all', 'gradeFilter_all', 'provinceFilter_all', 'customerTypeFilter_all'];
    allTabFilterIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            if (id.includes('Filter_all') && (id.includes('name') || id.includes('phone'))) {
                // Text filters (name, phone) - use input event
                element.addEventListener('input', function() {
                    const filters = getAllCustomersFilters();
                    saveFiltersToStorage(filters);
                    // Apply filters automatically after a short delay
                    setTimeout(() => {
                        if (filters && Object.values(filters).some(v => v !== '' && v !== false)) {
                            loadAllCustomersWithFilters(filters);
                        }
                    }, 500);
                });
            } else {
                // Select filters (temperature, grade, province, customerType) - use change event
                element.addEventListener('change', function() {
                    const filters = getAllCustomersFilters();
                    saveFiltersToStorage(filters);
                    // Apply filters automatically
                    if (filters && Object.values(filters).some(v => v !== '' && v !== false)) {
                        loadAllCustomersWithFilters(filters);
                    } else {
                        loadAllCustomers();
                    }
                });
            }
        }
    });
    
    // Event listener สำหรับ checkbox "ซ่อนลูกค้าที่โทรแล้ววันนี้"
    const hideCalledTodayCheckbox = document.getElementById('hideCalledToday');
    if (hideCalledTodayCheckbox) {
        // กู้คืนสถานะจาก sessionStorage
        const savedState = sessionStorage.getItem('hideCalledToday');
        if (savedState === 'true') {
            hideCalledTodayCheckbox.checked = true;
            
            // ถ้ามีการตั้งค่า hideCalledToday ให้ยกเลิก date range
            const hideDateRangeCheckbox = document.getElementById('hideDateRange');
            const hideDateFrom = document.getElementById('hideDateFrom');
            const hideDateTo = document.getElementById('hideDateTo');
            
            if (hideDateRangeCheckbox) {
                hideDateRangeCheckbox.checked = false;
                sessionStorage.setItem('hideDateRange', 'false');
            }
            if (hideDateFrom) {
                hideDateFrom.disabled = true;
                sessionStorage.removeItem('hideDateFrom');
            }
            if (hideDateTo) {
                hideDateTo.disabled = true;
                sessionStorage.removeItem('hideDateTo');
            }
            
            // Apply filter ทันทีหลัง restore
            setTimeout(() => {
                const filters = getAllCustomersFilters();
                saveFiltersToStorage(filters);
                loadAllCustomersWithFilters(filters);
            }, 200);
        }
        
        hideCalledTodayCheckbox.addEventListener('change', function() {
            console.log('Hide called today checkbox changed:', this.checked);
            
            // ถ้าติ๊กตัวนี้ ให้ยกเลิกตัว date range
            if (this.checked) {
                const hideDateRangeCheckbox = document.getElementById('hideDateRange');
                const hideDateFrom = document.getElementById('hideDateFrom');
                const hideDateTo = document.getElementById('hideDateTo');
                
                if (hideDateRangeCheckbox) {
                    hideDateRangeCheckbox.checked = false;
                    sessionStorage.setItem('hideDateRange', 'false');
                }
                if (hideDateFrom) {
                    hideDateFrom.disabled = true;
                    sessionStorage.removeItem('hideDateFrom');
                }
                if (hideDateTo) {
                    hideDateTo.disabled = true;
                    sessionStorage.removeItem('hideDateTo');
                }
            }
            
            // เก็บสถานะใน sessionStorage
            sessionStorage.setItem('hideCalledToday', this.checked.toString());
            
            // อัปเดตข้อมูลทันทีโดยไม่ต้องกดปุ่มกรอง
            const filters = getAllCustomersFilters();
            saveFiltersToStorage(filters);
            loadAllCustomersWithFilters(filters);
        });
    }
    
    // Event listener สำหรับ checkbox "ซ่อนลูกค้าระหว่างวันที่"
    const hideDateRangeCheckbox = document.getElementById('hideDateRange');
    const hideDateFrom = document.getElementById('hideDateFrom');
    const hideDateTo = document.getElementById('hideDateTo');
    
    if (hideDateRangeCheckbox && hideDateFrom && hideDateTo) {
        // เปิด/ปิด date inputs เมื่อ checkbox เปลี่ยน
        hideDateRangeCheckbox.addEventListener('change', function() {
            const isEnabled = this.checked;
            hideDateFrom.disabled = !isEnabled;
            hideDateTo.disabled = !isEnabled;
            
            // ถ้าติ๊กตัวนี้ ให้ยกเลิกตัว hideCalledToday
            if (this.checked) {
                const hideCalledTodayCheckbox = document.getElementById('hideCalledToday');
                if (hideCalledTodayCheckbox) {
                    hideCalledTodayCheckbox.checked = false;
                    sessionStorage.setItem('hideCalledToday', 'false');
                }
            }
            
            // เก็บสถานะใน sessionStorage
            sessionStorage.setItem('hideDateRange', this.checked.toString());
            if (isEnabled && hideDateFrom.value && hideDateTo.value) {
                sessionStorage.setItem('hideDateFrom', hideDateFrom.value);
                sessionStorage.setItem('hideDateTo', hideDateTo.value);
            }
            
            // อัปเดตข้อมูลทันที
            const filters = getAllCustomersFilters();
            loadAllCustomersWithFilters(filters);
        });
        
        // Event listeners สำหรับ date inputs
        hideDateFrom.addEventListener('change', function() {
            if (hideDateRangeCheckbox.checked) {
                sessionStorage.setItem('hideDateFrom', this.value);
                const filters = getAllCustomersFilters();
                loadAllCustomersWithFilters(filters);
            }
        });
        
        hideDateTo.addEventListener('change', function() {
            if (hideDateRangeCheckbox.checked) {
                sessionStorage.setItem('hideDateTo', this.value);
                const filters = getAllCustomersFilters();
                loadAllCustomersWithFilters(filters);
            }
        });
        
        // กู้คืนสถานะจาก sessionStorage
        const savedDateRange = sessionStorage.getItem('hideDateRange');
        const savedDateFrom = sessionStorage.getItem('hideDateFrom');
        const savedDateTo = sessionStorage.getItem('hideDateTo');
        
        if (savedDateRange === 'true') {
            hideDateRangeCheckbox.checked = true;
            hideDateFrom.disabled = false;
            hideDateTo.disabled = false;
            
            if (savedDateFrom) hideDateFrom.value = savedDateFrom;
            if (savedDateTo) hideDateTo.value = savedDateTo;
            
            // ถ้ามีการตั้งค่า date range ให้ยกเลิก hideCalledToday
            const hideCalledTodayCheckbox = document.getElementById('hideCalledToday');
            if (hideCalledTodayCheckbox) {
                hideCalledTodayCheckbox.checked = false;
                sessionStorage.setItem('hideCalledToday', 'false');
            }
            
            // Apply filter ทันทีหลัง restore
            setTimeout(() => {
                const filters = getAllCustomersFilters();
                saveFiltersToStorage(filters);
                loadAllCustomersWithFilters(filters);
            }, 200);
        }
    }
    
    // Event listeners สำหรับ filters ในแท็บ "ลูกค้าทั้งหมด" - ให้ทำงานทันที
    const allFilters = ['nameFilter_all', 'phoneFilter_all', 'temperatureFilter_all', 'gradeFilter_all', 'provinceFilter_all', 'customerTypeFilter_all'];
    allFilters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            const eventType = element.tagName === 'SELECT' ? 'change' : 'input';
            element.addEventListener(eventType, function() {
                console.log(`Filter ${filterId} changed:`, this.value);
                const filters = getAllCustomersFilters();
                loadAllCustomersWithFilters(filters);
            });
        }
    });
    
    // Event listeners สำหรับ filters ในแท็บอื่นๆ - ให้ทำงานทันที
    const tabFilters = [
        // Do tab
        ['nameFilter_do', 'phoneFilter_do', 'tempFilter_do', 'gradeFilter_do', 'provinceFilter_do'],
        // New tab  
        ['nameFilter_new', 'phoneFilter_new', 'tempFilter_new', 'gradeFilter_new', 'provinceFilter_new'],
        // Followup tab
        ['nameFilter_followup', 'phoneFilter_followup', 'tempFilter_followup', 'gradeFilter_followup', 'provinceFilter_followup'],
        // Existing tab
        ['nameFilter_existing', 'phoneFilter_existing', 'tempFilter_existing', 'gradeFilter_existing', 'provinceFilter_existing']
    ];
    
    tabFilters.forEach(filterGroup => {
        filterGroup.forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                const eventType = element.tagName === 'SELECT' ? 'change' : 'input';
                element.addEventListener(eventType, function() {
                    console.log(`Filter ${filterId} changed:`, this.value);
                    // เรียกใช้ฟังก์ชัน applyFilters ที่มีอยู่แล้ว
                    applyFilters();
                });
            }
        });
    });
    
    // Event listeners สำหรับ "All Customers" tab filters
    const allCustomersFilters = [
        'nameFilter_all', 'phoneFilter_all', 'temperatureFilter_all', 
        'gradeFilter_all', 'provinceFilter_all', 'customerTypeFilter_all'
    ];
    
    allCustomersFilters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            const eventType = element.tagName === 'SELECT' ? 'change' : 'input';
            element.addEventListener(eventType, function() {
                console.log(`All customers filter ${filterId} changed:`, this.value);
                // ใช้ getAllCustomersFilters แทน applyFilters
                const filters = getAllCustomersFilters();
                
                // บันทึก filters ลง sessionStorage
                saveFiltersToStorage(filters);
                
                loadAllCustomersWithFilters(filters);
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
                const emptyMessage = getEmptyMessageForTable(tableId);
                renderStandardTable(data.data, tableId, emptyMessage);
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
                const emptyMessage = getEmptyMessageForTable(tableId);
                renderStandardTable(data.data, tableId, emptyMessage);
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

// ฟังก์ชันนี้ถูกแทนที่ด้วย renderStandardTable() แล้ว - ไม่ใช้งาน
function renderCustomerTable(customers, tableId, basketType) {
    // ไม่ทำอะไร - ใช้ renderStandardTable() แทน
    console.log('renderCustomerTable deprecated - use renderStandardTable instead');
    return;
    
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
                        ${basketType === 'distribution' ? '<th class="text-center"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>' : ''}
                        ${basketType !== 'followups' ? '<th class="text-center">วันที่ได้รับ</th>' : ''}
                        <th class="text-center">ชื่อลูกค้า</th>
                        <th class="text-center">ผู้รับผิดชอบ</th>
                        <th class="text-center">จังหวัด</th>
                        <th class="text-center">เวลาที่เหลือ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">เกรด</th>
                        <th class="text-center">การติดต่อ</th>
                        <th class="text-center">การดำเนินการ</th>
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
    if (!activeTab) return 'allCustomersTable';
    
    switch (activeTab.id) {
        case 'all': return 'allCustomersTable';
        case 'do': return 'doTable';
        default: return 'allCustomersTable';
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
        const raw = sessionStorage.getItem('customers_all_filters');
        if (!raw) return;
        const f = JSON.parse(raw);
        
        // Restore "All Customers" tab filters
        if (document.getElementById('nameFilter_all')) document.getElementById('nameFilter_all').value = f.name || '';
        if (document.getElementById('phoneFilter_all')) document.getElementById('phoneFilter_all').value = f.phone || '';
        if (document.getElementById('temperatureFilter_all')) document.getElementById('temperatureFilter_all').value = f.temperature || '';
        if (document.getElementById('gradeFilter_all')) document.getElementById('gradeFilter_all').value = f.grade || '';
        if (document.getElementById('provinceFilter_all')) document.getElementById('provinceFilter_all').value = f.province || '';
        if (document.getElementById('customerTypeFilter_all')) document.getElementById('customerTypeFilter_all').value = f.customerType || '';
        
        // Restore checkboxes
        if (document.getElementById('hideCalledToday')) document.getElementById('hideCalledToday').checked = f.hideCalledToday || false;
        if (document.getElementById('hideDateRange')) document.getElementById('hideDateRange').checked = f.hideDateRange || false;
        if (document.getElementById('hideDateFrom')) document.getElementById('hideDateFrom').value = f.hideDateFrom || '';
        if (document.getElementById('hideDateTo')) document.getElementById('hideDateTo').value = f.hideDateTo || '';
        
        // Enable/disable date inputs based on checkbox
        const hideDateRange = document.getElementById('hideDateRange');
        const hideDateFrom = document.getElementById('hideDateFrom');
        const hideDateTo = document.getElementById('hideDateTo');
        
        if (hideDateRange && hideDateFrom && hideDateTo) {
            const isEnabled = hideDateRange.checked;
            hideDateFrom.disabled = !isEnabled;
            hideDateTo.disabled = !isEnabled;
        }
        
        console.log('Restored filters from storage:', f);
        
        // Apply the restored filters automatically to load filtered data
        setTimeout(() => {
            const filters = getAllCustomersFilters();
            if (filters && Object.values(filters).some(v => v !== '' && v !== false)) {
                loadAllCustomersWithFilters(filters);
            } else {
                loadAllCustomers();
            }
        }, 200);
        
    } catch(e) {
        console.error('Error restoring filters:', e);
    }
}

/**
 * บันทึก filters ลง sessionStorage
 */
function saveFiltersToStorage(filters) {
    try {
        sessionStorage.setItem('customers_all_filters', JSON.stringify(filters));
        console.log('Saved filters to storage:', filters);
    } catch(e) {
        console.error('Error saving filters:', e);
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
                        <th class="text-center"><input type="checkbox" id="selectAllAvailable" onchange="toggleSelectAllAvailable()"></th>
                        <th class="text-center">ชื่อลูกค้า</th>
                        <th class="text-center">เบอร์โทร</th>
                        <th class="text-center">จังหวัด</th>
                        <th class="text-center">สถานะ</th>
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
        // ตรวจสอบว่ามี modal อยู่แล้วหรือไม่
        const existingModal = document.getElementById('logCallModal');
        if (!existingModal) {
            // ถ้าไม่มี modal ให้สร้างใหม่
            console.log('Creating new call log modal for customer:', customerId);
            showLogCallModal(customerId);
            return;
        }
        
        // ถ้ามี modal แล้ว ให้ใช้ modal นั้น
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
        
        // ล้าง field นัดติดตาม
        const nextFollowupField = document.getElementById('nextFollowup');
        if (nextFollowupField) nextFollowupField.value = '';
        
        // ล้าง Tags Preview
        if (typeof clearCallLogTags === 'function') {
            clearCallLogTags();
        }
        
        // เตรียมชุดตัวเลือกผลการโทรแบบครบถ้วน (ไม่จำกัดตามสถานะ)
        try { updateCallResultOptions(true); } catch(_) {}
        
        // เพิ่ม Event Listener สำหรับ Auto-fill ผลการโทร (สำหรับ existing modal)
        const callStatusElement = document.getElementById('callStatus');
        if (callStatusElement) {
            // ลบ event listener เก่า (ถ้ามี)
            callStatusElement.removeEventListener('change', window.autoFillCallResult);
            
            // สร้าง function สำหรับ auto-fill
            window.autoFillCallResult = function() {
                updateCallResultOptions();
            };
            
            // เพิ่ม event listener ใหม่
            callStatusElement.addEventListener('change', window.autoFillCallResult);
        }
        
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
        const statusSel = document.getElementById('callStatus');
        if (!resultSel) return;

        const keep = resultSel.value; // keep current selection if still available
        const list = ['สนใจ','ไม่สนใจ','ลังเล','เบอร์ผิด','ได้คุย','ตัดสายทิ้ง'];
        resultSel.innerHTML = '<option value="">เลือกผลการโทร</option>' + list.map(t=>`<option value="${t}">${t}</option>`).join('');
        
        // Auto-fill ผลการโทรเมื่อสถานะการโทรไม่ใช่ "รับสาย"
        if (statusSel && statusSel.value && statusSel.value !== 'answered') {
            const statusValueMap = {
                'no_answer': 'ไม่รับสาย',
                'busy': 'สายไม่ว่าง', 
                'invalid': 'เบอร์ผิด',
                'hang_up': 'ตัดสายทิ้ง'
            };
            const autoFillValue = statusValueMap[statusSel.value];
            if (autoFillValue && list.includes(autoFillValue)) {
                resultSel.value = autoFillValue;
                return; // ไม่ต้อง restore ค่าเดิม
            }
        }
        
        // try restore เฉพาะเมื่อไม่ได้ auto-fill
        if (keep && list.includes(keep)) resultSel.value = keep;
    } catch(_) {}
}

/**
 * Submit call log
 */
async function submitCallLog() {
    const customerId = document.getElementById('callCustomerId').value;
    const callType = document.getElementById('callType')?.value || 'outbound';
    const callStatus = document.getElementById('callStatus').value;
    let callResult = document.getElementById('callResult').value;
    const duration = document.getElementById('callDuration').value;
    const notes = document.getElementById('callNotes').value;
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
        next_followup_at: nextFollowup || null
    };
    
    const submitBtn = document.querySelector('#logCallModal .btn-success');
    if (submitBtn) { 
        submitBtn.disabled = true; 
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...'; 
    }

    try {
        // บันทึกการโทรก่อน
        const response = await fetch('api/calls.php?action=log_call', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const txt = await response.text().catch(()=>'');
            throw new Error(`HTTP ${response.status}: ${txt.substring(0,200)}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // บันทึก Tags หลังจากบันทึกการโทรสำเร็จ
            if (typeof saveCallLogTags === 'function') {
                const tagsSaved = await saveCallLogTags(customerId);
                if (!tagsSaved) {
                    console.warn('Failed to save some tags, but call log was saved successfully');
                }
            }
            
            showSuccess(result.message || 'บันทึกการโทรสำเร็จ');
            bootstrap.Modal.getInstance(document.getElementById('logCallModal')).hide();
            
            // รีเฟรชตามแท็บปัจจุบัน
            refreshCurrentTab();
        } else {
            showError(result.message || 'เกิดข้อผิดพลาดในการบันทึก');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการบันทึกการโทร: ' + (error.message || ''));
    } finally {
        if (submitBtn) { 
            submitBtn.disabled = false; 
            submitBtn.innerHTML = 'บันทึกการโทร'; 
        }
    }
}

// showAddTagModalFromCall() function ถูกย้ายไปใน tags.js แล้ว

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






// ฟังก์ชันนี้ถูกลบออกแล้วเพราะไม่ใช้งาน (เอาแท็บ calls ออกแล้ว)
function renderCallFollowupTable(customers) {
    // ไม่ทำอะไร - ฟังก์ชันนี้จะถูกลบออกทั้งหมดในอนาคต
        return;
    
    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-center">ลูกค้า</th>
                        <th class="text-center">เบอร์โทร</th>
                        <th class="text-center">จังหวัด</th>
                        <th class="text-center">ผลการโทรล่าสุด</th>
                        <th class="text-center">วันที่ติดตาม</th>
                        <th class="text-center">ความสำคัญ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">การดำเนินการ</th>
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

/**
 * โหลดลูกค้าทั้งหมดสำหรับ tab "ลูกค้าทั้งหมด"
 */
async function loadAllCustomers() {
    console.log('loadAllCustomers called');
    const tableElement = document.getElementById('allCustomersTable');
    if (!tableElement) {
        console.log('allCustomersTable element not found');
        return;
    }
    
    console.log('allCustomersTable element found');
    
    try {
        // แสดง loading แบบ smooth
        tableElement.style.opacity = '0.6';
        tableElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p></div>';
        
        // กำหนด basket type ตาม role
        const basketType = (window.currentUserRole === 'telesales' || window.currentUserRole === 'supervisor') ? 'assigned' : 'all';
        
        // Build query parameters
        const params = new URLSearchParams();
        params.append('basket_type', basketType);
        
        // Fetch data
        console.log('Fetching from:', `api/customers.php?${params.toString()}`);
        const response = await fetch(`api/customers.php?${params.toString()}`);
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (response.ok && (data.customers || data.data)) {
            const customers = data.customers || data.data || [];
            console.log('Found customers:', customers.length);
            
            // บันทึกข้อมูลลูกค้าใน window เพื่อให้ modal เข้าถึงได้
            window.currentCustomersData = customers;
            
            renderStandardTable(customers, 'allCustomersTable', 'ไม่พบข้อมูลลูกค้า');
            
            // คืนค่า opacity กลับมา
            tableElement.style.opacity = '1';
            
            // เพิ่ม pagination (ตรวจสอบว่ายังไม่มี)
            setTimeout(() => {
                const table = document.querySelector('#allCustomersTable table');
                const paginationContainer = document.getElementById('allCustomersTable-pagination');
                if (table && paginationContainer && !paginationContainer.hasChildNodes()) {
                    paginateTable(table, 'allCustomersTable-pagination', 10, 'customers_page_allCustomersTable');
                }
            }, 100);
            
            // อัปเดต badge count
            const countBadge = document.getElementById('allCustomersCount');
            if (countBadge) {
                countBadge.textContent = customers.length;
            }
        } else {
            console.log('No customers found or error:', data);
            tableElement.innerHTML = '<div class="text-center py-4"><p class="text-muted">ไม่พบข้อมูลลูกค้า</p></div>';
        }
    } catch (error) {
        console.error('Error loading all customers:', error);
        tableElement.innerHTML = '<div class="text-center py-4"><p class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p></div>';
    }
}

/**
 * คำนวณเวลาที่เหลือ
 */
function calculateTimeRemaining(customer) {
    if (!customer.customer_time_expiry) return '-';
    
    const expiry = new Date(customer.customer_time_expiry);
    const now = new Date();
    const diffTime = expiry - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return `<span class="text-danger">เกิน ${Math.abs(diffDays)} วัน</span>`;
    } else if (diffDays === 0) {
        return '<span class="text-warning">วันนี้</span>';
    } else if (diffDays <= 7) {
        return `<span class="text-warning">${diffDays} วัน</span>`;
    } else {
        return `${diffDays} วัน`;
    }
}

/**
 * จัดรูปแบบวันที่
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit'
    });
}



/**
 * ฟังก์ชันสำหรับสร้างข้อความเมื่อไม่มีข้อมูล
 */
function getEmptyMessageForTable(tableId) {
    switch(tableId) {
        case 'allCustomersTable':
            return 'ไม่พบข้อมูลลูกค้า';
        case 'doTable':
            return 'ไม่มีงานที่ต้องทำวันนี้';
        default:
            return 'ไม่พบข้อมูล';
    }
}

/**
 * สร้างตารางมาตรฐานสำหรับทุกหน้า
 */
function renderStandardTable(customers, tableElementId, emptyMessage = 'ไม่พบข้อมูลลูกค้า') {
    const tableElement = document.getElementById(tableElementId);
    if (!tableElement) return;
    
    if (!customers || customers.length === 0) {
        tableElement.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-search fa-2x text-muted mb-3"></i>
                <p class="text-muted">${emptyMessage}</p>
            </div>`;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="8%" class="text-center">วันที่ได้รับ</th>
                        <th width="18%" class="text-center">ชื่อลูกค้า</th>
                        <th width="12%" class="text-center">เบอร์โทร</th>
                        <th width="8%" class="text-center">จังหวัด</th>
                        <th width="8%" class="text-center">เวลาที่เหลือ</th>
                        <th width="10%" class="text-center">ประเภทลูกค้า</th>
                        <th width="8%" class="text-center">สถานะลูกค้า</th>
                        <th width="6%" class="text-center">เกรด</th>
                        <th width="16%" class="text-center">Tag</th>
                        <th width="12%" class="text-center">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    customers.forEach(customer => {
        const statusIcon = getTemperatureIcon(customer.temperature_status);
        const gradeClass = getGradeClass(customer.customer_grade);
        const tagsHtml = renderCustomerTags(customer);
        const customerType = getCustomerType(customer);
        
        html += `
            <tr class="customer-row" data-customer-id="${customer.customer_id}">
                <td class="text-center">
                    <small class="text-muted">${formatDate(customer.created_at)}</small>
                </td>
                <td>
                    <div>
                        <strong>${escapeHtml(customer.first_name)} ${escapeHtml(customer.last_name)}</strong>
                        <br><small class="text-muted">${escapeHtml(customer.customer_code)}</small>
                    </div>
                </td>
                <td class="text-center">
                    <span class="fw-medium">${escapeHtml(customer.phone)}</span>
                </td>
                <td class="text-center">
                    <small>${escapeHtml(customer.province || '-')}</small>
                </td>
                <td class="text-center">
                    <small class="text-muted">${calculateTimeRemaining(customer)}</small>
                </td>
                <td class="text-center">
                    <span class="badge ${customerType.class}">${customerType.text}</span>
                </td>
                <td class="text-center">
                    <span class="badge ${getTemperatureClass(customer.temperature_status)}" style="font-size: 0.7rem;">
                        ${statusIcon} ${customer.temperature_status}
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge ${gradeClass}" style="font-size: 0.7rem;">${customer.customer_grade || '-'}</span>
                </td>
                <td>
                    <div data-customer-tags="${customer.customer_id}">
                        ${tagsHtml}
                    </div>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="customers.php?action=show&id=${customer.customer_id}" 
                           class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn btn-outline-success btn-sm" 
                                onclick="logCall(${customer.customer_id})" 
                                title="บันทึกการโทร">
                            <i class="fas fa-phone"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    tableElement.innerHTML = html;
    
    // เพิ่ม pagination
    paginateTable(tableElement.querySelector('table'), 'allCustomersTable-pagination', 10, 'customers_page_allCustomersTable');
}

/**
 * สร้าง HTML สำหรับแสดง tags ของลูกค้า
 */
function renderCustomerTags(customer) {
    if (!customer.customer_tags) {
        // ไม่มี tags - แสดงเฉพาะปุ่มเพิ่ม
        return `
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary" 
                        onclick="showAddTagModal(${customer.customer_id})" 
                        title="เพิ่ม tag">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        `;
    }
    
    const tagNames = customer.customer_tags.split(',');
    const tagColors = customer.tag_colors ? customer.tag_colors.split(',') : [];
    const maxVisibleTags = 2; // แสดงได้สูงสุด 2 tags ตามที่ user ต้องการ
    
    let html = '<div class="d-flex align-items-center gap-1" style="flex-wrap: nowrap; overflow: hidden;">';
    
    // แสดง tags ที่มองเห็นได้ (แบบ inline ไม่ stacking)
    tagNames.slice(0, maxVisibleTags).forEach((tagName, index) => {
        const tagColor = tagColors[index] || '#007bff';
        
        html += `
            <span class="badge" 
                  style="background-color: ${tagColor}; cursor: pointer; font-size: 0.65rem; white-space: nowrap;" 
                  onclick="removeCustomerTag(${customer.customer_id}, '${tagName.trim()}', this)"
                  title="คลิกเพื่อลบ tag: ${escapeHtml(tagName.trim())}">
                ${escapeHtml(tagName.trim())} <i class="fas fa-times ms-1"></i>
            </span>
        `;
    });
    
    // ถ้ามี tags เกิน แสดงปุ่ม "+N" ตามรูปแบบที่ user ต้องการ
    if (tagNames.length > maxVisibleTags) {
        const remainingCount = tagNames.length - maxVisibleTags;
        html += `
            <span class="badge bg-light text-dark border" 
                  style="cursor: pointer; font-size: 0.65rem;"
                  onclick="showAllTagsModal(${customer.customer_id})" 
                  title="ดู tags ทั้งหมด (${tagNames.length} tags)">
                +${remainingCount}
            </span>
        `;
    }
    
    // ปุ่มเพิ่ม tag (ขนาดเล็ก)
    html += `
        <button class="btn btn-sm btn-outline-secondary ms-1" 
                style="padding: 1px 4px; font-size: 0.7rem;"
                onclick="showAddTagModal(${customer.customer_id})" 
                title="เพิ่ม tag">
            <i class="fas fa-plus"></i>
        </button>
    `;
    
    html += '</div>';
    return html;
}

/**
 * แสดง modal สำหรับดู tags ทั้งหมดของลูกค้า
 */
function showAllTagsModal(customerId) {
    // หาข้อมูลลูกค้า
    const customerRow = document.querySelector(`tr[data-customer-id="${customerId}"]`);
    if (!customerRow) return;
    
    const customerName = customerRow.querySelector('td:nth-child(2) strong').textContent;
    
    // หา tag data จาก customer row
    const customerData = getCurrentCustomerData(customerId);
    if (!customerData || !customerData.customer_tags) return;
    
    const tagNames = customerData.customer_tags.split(',');
    const tagColors = customerData.tag_colors ? customerData.tag_colors.split(',') : [];
    
    let tagsHtml = '<div class="d-flex flex-wrap gap-2">';
    tagNames.forEach((tagName, index) => {
        const tagColor = tagColors[index] || '#007bff';
        tagsHtml += `
            <span class="badge" style="background-color: ${tagColor}; cursor: pointer;" 
                  onclick="removeCustomerTag(${customerId}, '${tagName.trim()}', this)"
                  title="คลิกเพื่อลบ tag">
                ${escapeHtml(tagName.trim())} <i class="fas fa-times ms-1"></i>
            </span>
        `;
    });
    tagsHtml += '</div>';
    
    const modalHtml = `
        <div class="modal fade" id="allTagsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-tags me-2"></i>Tags ทั้งหมดของ ${escapeHtml(customerName)}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${tagsHtml}
                        <hr>
                        <button class="btn btn-sm btn-primary" onclick="showAddTagModal(${customerId}); bootstrap.Modal.getInstance(document.getElementById('allTagsModal')).hide();">
                            <i class="fas fa-plus me-1"></i>เพิ่ม Tag ใหม่
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่า (ถ้ามี)
    const existingModal = document.getElementById('allTagsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('allTagsModal'));
    modal.show();
}

/**
 * หาข้อมูลลูกค้าปัจจุบันจาก DOM หรือ cache
 */
function getCurrentCustomerData(customerId) {
    // ถ้ามี cache data ให้ใช้
    if (window.currentCustomersData) {
        return window.currentCustomersData.find(c => c.customer_id == customerId);
    }
    
    // หรือดึงจาก DOM attributes (ถ้าเก็บไว้)
    const row = document.querySelector(`tr[data-customer-id="${customerId}"]`);
    if (row && row.dataset.customerTags) {
        return {
            customer_id: customerId,
            customer_tags: row.dataset.customerTags,
            tag_colors: row.dataset.tagColors
        };
    }
    
    return null;
}

/**
 * Toggle การเลือกลูกค้าทั้งหมด
 */
function toggleSelectAllCustomers() {
    const selectAll = document.getElementById('selectAllCustomers');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        toggleCustomerSelection(parseInt(checkbox.value));
    });
    
    updateBulkActionsVisibility();
}

/**
 * Toggle การเลือกลูกค้ารายบุคคล
 */
function toggleCustomerSelection(customerId) {
    const index = selectedCustomers.indexOf(customerId);
    
    if (index > -1) {
        selectedCustomers.splice(index, 1);
    } else {
        selectedCustomers.push(customerId);
    }
    
    updateBulkActionsVisibility();
    updateSelectAllCheckbox();
}

/**
 * อัปเดตการแสดงผล bulk actions
 */
function updateBulkActionsVisibility() {
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) {
        bulkActions.style.display = selectedCustomers.length > 0 ? 'block' : 'none';
    }
}

/**
 * อัปเดต checkbox "เลือกทั้งหมด"
 */
function updateSelectAllCheckbox() {
    const selectAll = document.getElementById('selectAllCustomers');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    
    if (selectAll && checkboxes.length > 0) {
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        selectAll.checked = checkedCount === checkboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }
}

/**
 * ใช้งาน filters ทั้งหมด
 */
function applyAllFilters() {
    // ดึงค่า filters
    const filters = readAllFilters();
    
    // โหลดข้อมูลใหม่ตาม filters
    loadAllCustomersWithFilters(filters);
}

/**
 * ล้าง filters ทั้งหมด
 */
function clearAllFilters() {
    // ล้างค่า input fields สำหรับแท็บ "ลูกค้าทั้งหมด"
    const filterIds = [
        'nameFilter_all', 'phoneFilter_all', 'temperatureFilter_all', 
        'gradeFilter_all', 'provinceFilter_all', 'customerTypeFilter_all'
    ];
    
    filterIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.value = '';
    });
    
    // ล้าง tag filters
    if (typeof clearTagFilter === 'function') {
        clearTagFilter();
    }
    
    // ล้าง hide called today
    const hideCalledToday = document.getElementById('hideCalledToday');
    if (hideCalledToday) {
        hideCalledToday.checked = false;
        sessionStorage.removeItem('hideCalledToday');
    }
    
    // ล้าง date range filter
    const hideDateRange = document.getElementById('hideDateRange');
    const hideDateFrom = document.getElementById('hideDateFrom');
    const hideDateTo = document.getElementById('hideDateTo');
    
    if (hideDateRange) {
        hideDateRange.checked = false;
        sessionStorage.removeItem('hideDateRange');
    }
    if (hideDateFrom) {
        hideDateFrom.value = '';
        hideDateFrom.disabled = true;
        sessionStorage.removeItem('hideDateFrom');
    }
    if (hideDateTo) {
        hideDateTo.value = '';
        hideDateTo.disabled = true;
        sessionStorage.removeItem('hideDateTo');
    }
    
    // ลบ saved filters ทั้งหมด
    sessionStorage.removeItem('customers_all_filters');
    sessionStorage.removeItem('customers_filters');
    
    // ล้างตัวกรองสำหรับแท็บ Do ด้วย
    clearTabFilters('do');
    
    // โหลดข้อมูลใหม่
    loadAllCustomers();
}

/**
 * อ่านค่า filters ทั้งหมด
 */
function readAllFilters() {
    return {
        name: document.getElementById('nameFilter_all')?.value || '',
        phone: document.getElementById('phoneFilter_all')?.value || '',
        temperature: document.getElementById('tempFilter_all')?.value || '',
        grade: document.getElementById('gradeFilter_all')?.value || '',
        province: document.getElementById('provinceFilter_all')?.value || '',
        customerType: document.getElementById('customerTypeFilter_all')?.value || '',
        hideCalledToday: document.getElementById('hideCalledToday')?.checked || false
    };
}

/**
 * โหลดลูกค้าทั้งหมดพร้อม filters
 */
async function loadAllCustomersWithFilters(filters) {
    const tableElement = document.getElementById('allCustomersTable');
    if (!tableElement) return;
    
    try {
        // แสดง loading แบบ smooth
        tableElement.style.opacity = '0.6';
        tableElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p></div>';
        
        // กำหนด basket type ตาม role
        const basketType = (window.currentUserRole === 'telesales' || window.currentUserRole === 'supervisor') ? 'assigned' : 'all';
        
        // Build query parameters
        const params = new URLSearchParams();
        params.append('basket_type', basketType);
        
        // เพิ่ม filters (แต่ไม่รวม client-side filters)
        Object.entries(filters).forEach(([key, value]) => {
            if (value && key !== 'hideCalledToday' && key !== 'hideDateRange' && key !== 'hideDateFrom' && key !== 'hideDateTo') {
                // แปลง customerType เป็น parameter ที่ API เข้าใจ
                if (key === 'customerType') {
                    params.append('customer_status', value);
                } else {
                    params.append(key, value);
                }
            }
        });
        
        // Fetch data
        const response = await fetch(`api/customers.php?${params.toString()}`);
        const data = await response.json();
        
        if (response.ok && (data.customers || data.data)) {
            let customers = data.customers || data.data || [];
            
            // Apply client-side filters
            if (filters.hideCalledToday) {
                const today = new Date().toDateString();
                customers = customers.filter(customer => {
                    // ตรวจสอบว่าไม่ได้โทรวันนี้
                    return !customer.last_call_date || new Date(customer.last_call_date).toDateString() !== today;
                });
            }
            
            // Apply date range filter
            if (filters.hideDateRange && filters.hideDateFrom && filters.hideDateTo) {
                const fromDate = new Date(filters.hideDateFrom);
                const toDate = new Date(filters.hideDateTo);
                // เซ็ต toDate ให้เป็นสิ้นวัน
                toDate.setHours(23, 59, 59, 999);
                
                customers = customers.filter(customer => {
                    if (!customer.last_call_date) {
                        return true; // แสดงลูกค้าที่ไม่เคยโทร
                    }
                    
                    const callDate = new Date(customer.last_call_date);
                    // ซ่อนลูกค้าที่โทรในช่วงวันที่เลือก
                    return !(callDate >= fromDate && callDate <= toDate);
                });
            }
            
            // บันทึกข้อมูลลูกค้าใน window เพื่อให้ modal เข้าถึงได้
            window.currentCustomersData = customers;
            
            renderStandardTable(customers, 'allCustomersTable', 'ไม่มีข้อมูลที่ตรงกับเงื่อนไขการกรอง');
            
            // คืนค่า opacity กลับมา
            tableElement.style.opacity = '1';
            
            // เพิ่ม pagination (ตรวจสอบว่ายังไม่มี)
            setTimeout(() => {
                const table = document.querySelector('#allCustomersTable table');
                const paginationContainer = document.getElementById('allCustomersTable-pagination');
                if (table && paginationContainer && !paginationContainer.hasChildNodes()) {
                    paginateTable(table, 'allCustomersTable-pagination', 10, 'customers_page_allCustomersTable');
                }
            }, 100);
            
            // อัปเดต badge count
            const countBadge = document.getElementById('allCustomersCount');
            if (countBadge) {
                countBadge.textContent = customers.length;
            }
        } else {
            tableElement.innerHTML = '<div class="text-center py-4"><p class="text-muted">ไม่พบข้อมูลลูกค้า</p></div>';
        }
    } catch (error) {
        console.error('Error loading all customers with filters:', error);
        tableElement.innerHTML = '<div class="text-center py-4"><p class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p></div>';
    }
}

/**
 * Utility functions for rendering
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function getTemperatureIcon(status) {
    switch (status) {
        case 'hot': return '🔥';
        case 'warm': return '🌤️';
        case 'cold': return '❄️';
        case 'frozen': return '🧊';
        default: return '📊';
    }
}

function getTemperatureClass(status) {
    switch (status) {
        case 'hot': return 'bg-danger text-white';
        case 'warm': return 'bg-warning text-dark';
        case 'cold': return 'bg-info text-white';
        case 'frozen': return 'bg-secondary text-white';
        default: return 'bg-light text-dark';
    }
}

function getGradeClass(grade) {
    switch (grade) {
        case 'A+': return 'bg-success text-white';
        case 'A': return 'bg-primary text-white';
        case 'B': return 'bg-info text-white';
        case 'C': return 'bg-warning text-dark';
        case 'D': return 'bg-secondary text-white';
        default: return 'bg-light text-dark';
    }
}

function getCustomerType(customer) {
    // มี next_followup_at = ติดตาม (ลำดับแรก)
    if (customer.next_followup_at) {
        return {
            text: 'ติดตาม',
            class: 'bg-warning text-dark'
        };
    }
    
    // ใช้ customer_status เป็นหลักในการจำแนกประเภท
    switch (customer.customer_status) {
        case 'new':
            return {
                text: 'ลูกค้าใหม่',
                class: 'bg-success text-white'
            };
        case 'existing':
            return {
                text: 'ลูกค้าเก่า',
                class: 'bg-secondary text-white'
            };
        case 'followup':
            return {
                text: 'ติดตาม',
                class: 'bg-warning text-dark'
            };
        default:
            // หากไม่มี status หรือ status ไม่ถูกต้อง ให้ดูจากวันที่สร้าง
            const createdDate = new Date(customer.created_at);
            const now = new Date();
            const daysDiff = Math.floor((now - createdDate) / (1000 * 60 * 60 * 24));
            
            if (daysDiff <= 7) {
                return {
                    text: 'ลูกค้าใหม่',
                    class: 'bg-success text-white'
                };
            } else {
                return {
                    text: 'ลูกค้าเก่า',
                    class: 'bg-secondary text-white'
                };
            }
    }
}

// Tag filtering functions
function saveTagFilterState() {
    const tagOptions = document.querySelectorAll('#tagFilterOptions input[type="checkbox"]:checked');
    const selectedTags = Array.from(tagOptions).map(option => option.value);
    sessionStorage.setItem('selectedTagFilters', JSON.stringify(selectedTags));
}

function restoreTagFilterState() {
    try {
        const savedTags = sessionStorage.getItem('selectedTagFilters');
        if (savedTags) {
            const selectedTags = JSON.parse(savedTags);
            selectedTags.forEach(tagName => {
                const tagOption = document.querySelector(`#tagFilterOptions input[value="${tagName}"]`);
                if (tagOption) {
                    tagOption.checked = true;
                }
            });
            // อัปเดต badge count
            updateTagFilterCount();
        }
    } catch (error) {
        console.error('Error restoring tag filter state:', error);
    }
}

function updateTagFilterCount() {
    const checkedTags = document.querySelectorAll('#tagFilterOptions input[type="checkbox"]:checked');
    const countBadge = document.getElementById('selectedTagsCount');
    if (countBadge) {
        countBadge.textContent = checkedTags.length;
    }
}

function clearTagFilter() {
    // ล้าง tag selections ใน modal แบบใหม่
    const newTagOptions = document.querySelectorAll('#modalTagFilterOptions .tag-selectable');
    newTagOptions.forEach(option => {
        option.classList.remove('selected');
        option.style.border = '2px solid transparent';
        option.style.boxShadow = 'none';
    });
    
    // ล้าง tag selections ใน UI แบบเก่า (ถ้ามี)
    const oldTagOptions = document.querySelectorAll('#tagFilterOptions input[type="checkbox"]');
    oldTagOptions.forEach(option => option.checked = false);
    
    // ล้าง saved state
    sessionStorage.removeItem('selectedTagFilters');
    
    // อัปเดต badge count
    const countBadge = document.getElementById('selectedTagsCount');
    if (countBadge) {
        countBadge.textContent = '0';
    }
    
    // อัปเดต modal count (ถ้า modal เปิดอยู่)
    const modalCountSpan = document.getElementById('modalSelectedCount');
    if (modalCountSpan) {
        modalCountSpan.textContent = '0';
    }
    
    // โหลดข้อมูลใหม่โดยไม่มี tag filter
    loadAllCustomers();
}

/**
 * โหลด tag filter state ที่บันทึกไว้เมื่อเริ่มหน้า
 * @returns {boolean} true ถ้ามี saved tag filters และโหลดแล้ว, false ถ้าไม่มี
 */
function loadSavedTagFilters() {
    try {
        const savedTags = sessionStorage.getItem('selectedTagFilters');
        if (savedTags) {
            const selectedTags = JSON.parse(savedTags);
            
            // อัปเดต badge count
            const countBadge = document.getElementById('selectedTagsCount');
            if (countBadge) {
                countBadge.textContent = selectedTags.length;
            }
            
            // ถ้ามี tags ที่เลือกไว้ ให้ใช้กรองทันที
            if (selectedTags.length > 0) {
                // เรียกใช้ searchCustomersByTags และแสดงผล
                if (typeof searchCustomersByTags === 'function') {
                    searchCustomersByTags(selectedTags).then(customers => {
                        if (typeof renderStandardTable === 'function') {
                            renderStandardTable(customers, 'allCustomersTable', 'ไม่มีลูกค้าที่มี tags ที่เลือก');
                            
                            // อัปเดต all customers count
                            const allCountBadge = document.getElementById('allCustomersCount');
                            if (allCountBadge) {
                                allCountBadge.textContent = customers.length;
                            }
                            
                            // เพิ่ม pagination
                            setTimeout(() => {
                                const table = document.querySelector('#allCustomersTable table');
                                const paginationContainer = document.getElementById('allCustomersTable-pagination');
                                if (table && paginationContainer) {
                                    paginationContainer.innerHTML = '';
                                    if (typeof paginateTable === 'function') {
                                        paginateTable(table, 'allCustomersTable-pagination', 10, 'customers_page_allCustomersTable');
                                    }
                                }
                            }, 100);
                        }
                    }).catch(error => {
                        console.error('Error loading saved tag filters:', error);
                    });
                }
                return true; // มี saved tags และโหลดแล้ว
            }
        }
        return false; // ไม่มี saved tags
    } catch (error) {
        console.error('Error loading saved tag filters:', error);
        return false;
    }
}

/**
 * ดึงค่า filters ทั้งหมดสำหรับ "ลูกค้าทั้งหมด"
 */
function getAllCustomersFilters() {
    return {
        name: document.getElementById('nameFilter_all')?.value || '',
        phone: document.getElementById('phoneFilter_all')?.value || '',
        temperature: document.getElementById('temperatureFilter_all')?.value || '',
        grade: document.getElementById('gradeFilter_all')?.value || '',
        province: document.getElementById('provinceFilter_all')?.value || '',
        customerType: document.getElementById('customerTypeFilter_all')?.value || '',
        hideCalledToday: document.getElementById('hideCalledToday')?.checked || false,
        hideDateRange: document.getElementById('hideDateRange')?.checked || false,
        hideDateFrom: document.getElementById('hideDateFrom')?.value || '',
        hideDateTo: document.getElementById('hideDateTo')?.value || ''
    };
}

function applyTagFilter() {
    console.log('applyTagFilter called');
    
    // รวม filters ทั้งหมด
    const filters = getAllCustomersFilters();
    console.log('Current filters:', filters);
    
    // โหลดข้อมูลใหม่พร้อม filters
    loadAllCustomersWithFilters(filters);
}

function manualApplyTagFilter() {
    // ดึง selected tags
    const selectedTags = Array.from(document.querySelectorAll('#tagFilterOptions input[type="checkbox"]:checked'))
        .map(input => input.value);
    
    // บันทึกสถานะการกรอง
    saveTagFilterState();
    
    // อัปเดต badge count
    updateTagFilterCount();
    
    // ถ้าไม่มี tags ที่เลือก ให้แสดงลูกค้าทั้งหมด
    if (selectedTags.length === 0) {
        loadAllCustomers();
        return;
    }
    
    // ใช้ tags API ในการค้นหา
    searchCustomersByTags(selectedTags).then(customers => {
        renderStandardTable(customers, 'allCustomersTable');
        
        // อัปเดต count badge
        const countBadge = document.getElementById('allCustomersCount');
        if (countBadge) {
            countBadge.textContent = customers.length;
        }
    });
}

// Placeholder functions for bulk tag operations
function showBulkTagModal() {
    if (selectedCustomers.length === 0) {
        alert('กรุณาเลือกลูกค้าก่อน');
        return;
    }
    // Will be implemented with bulk tag functionality
    console.log('Bulk add tags for customers:', selectedCustomers);
}

function showBulkRemoveTagModal() {
    if (selectedCustomers.length === 0) {
        alert('กรุณาเลือกลูกค้าก่อน');
        return;
    }
    // Will be implemented with bulk tag functionality
    console.log('Bulk remove tags for customers:', selectedCustomers);
}



/**
 * แสดง modal บันทึกการโทร
 */
function showLogCallModal(customerId) {
    const modalHtml = `
        <div class="modal fade" id="logCallModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">บันทึกการโทร</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="logCallForm">
                            <input type="hidden" id="callCustomerId" value="${customerId}">
                            
                            <div class="mb-3">
                                <label for="callStatus" class="form-label">สถานะการโทร <span class="text-danger">*</span></label>
                                <select class="form-select" id="callStatus" required>
                                    <option value="">เลือกสถานะ</option>
                                    <option value="answered">รับสาย</option>
                                    <option value="no_answer">ไม่รับสาย</option>
                                    <option value="busy">สายไม่ว่าง</option>
                                    <option value="invalid">เบอร์ผิด</option>
                                    <option value="hang_up">ตัดสายทิ้ง</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="callResult" class="form-label">ผลการโทร</label>
                                <select class="form-select" id="callResult">
                                    <option value="">เลือกผลการโทร</option>
                                    <option value="สนใจ">สนใจ</option>
                                    <option value="ไม่สนใจ">ไม่สนใจ</option>
                                    <option value="ลังเล">ลังเล</option>
                                    <option value="เบอร์ผิด">เบอร์ผิด</option>
                                    <option value="ได้คุย">ได้คุย</option>
                                    <option value="ตัดสายทิ้ง">ตัดสายทิ้ง</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="callDuration" class="form-label">ระยะเวลา (นาที)</label>
                                <input type="number" class="form-control" id="callDuration" min="0" placeholder="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="callNotes" class="form-label">หมายเหตุ</label>
                                <textarea class="form-control" id="callNotes" rows="3" placeholder="บันทึกรายละเอียดการโทร..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nextFollowup" class="form-label">วันที่ติดตามครั้งถัดไป</label>
                                <input type="datetime-local" class="form-control" id="nextFollowup">
                            </div>
                            
                            <!-- Tags Section -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Tags</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showAddTagModalFromCall()">
                                        <i class="fas fa-plus me-1"></i>เพิ่ม Tag
                                    </button>
                                </div>
                                <div id="callTagsPreview" class="d-flex flex-wrap gap-2">
                                    <!-- Tags จะแสดงที่นี่ -->
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-success" onclick="submitCallLog()">บันทึกการโทร</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่า (ถ้ามี)
    const existingModal = document.getElementById('logCallModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // โหลด Tags ที่มีอยู่ (ถ้า Tags functionality พร้อมใช้งาน)
    if (typeof loadUserTags === 'function') {
        try {
            loadUserTags();
            console.log('User tags loaded for call log modal');
        } catch (e) {
            console.warn('Failed to load user tags:', e);
        }
    }
    
    // เพิ่ม Event Listener สำหรับ Auto-fill ผลการโทร
    const callStatusElement = document.getElementById('callStatus');
    if (callStatusElement) {
        callStatusElement.addEventListener('change', function() {
            updateCallResultOptions();
        });
    }
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('logCallModal'));
    modal.show();
}

/**
 * รีเฟรช customer ตัวเดียวในตาราง (หลังจากเพิ่ม tags)
 */
async function refreshCustomerInTable(customerId) {
    try {
        // ดึงข้อมูลลูกค้าล่าสุด (พร้อม tags)
        const response = await fetch(`api/customers.php?action=get_customer&id=${customerId}`);
        if (!response.ok) return;
        
        const data = await response.json();
        if (!data.success || !data.customer) return;
        
        const customer = data.customer;
        
        // หา row ของลูกค้าในทุก table ที่มีอยู่ (Do, New, All, etc.)
        const allTables = document.querySelectorAll('#allCustomersTable, #doTable, #newTable, #followupTable');
        
        for (const table of allTables) {
            const tableRows = table.querySelectorAll('tbody tr[data-customer-id]');
            for (const row of tableRows) {
                if (row.getAttribute('data-customer-id') == customerId) {
                    
                    // อัพเดท tags column โดยใช้ data-customer-tags selector
                    const tagsContainer = row.querySelector(`[data-customer-tags="${customerId}"]`);
                    if (tagsContainer) {
                        // สร้าง temporary customer object สำหรับ renderCustomerTags function
                        const tempCustomer = {
                            customer_id: customerId
                        };
                        
                        // ถ้ามี tags ให้ใส่ข้อมูล
                        if (customer.tags && customer.tags.length > 0) {
                            tempCustomer.customer_tags = customer.tags.map(tag => tag.tag_name).join(',');
                            tempCustomer.tag_colors = customer.tags.map(tag => tag.tag_color).join(',');
                        }
                        
                        // ใช้ renderCustomerTags function ที่มีอยู่แล้ว
                        tagsContainer.innerHTML = renderCustomerTags(tempCustomer);
                    }
                    
                    // ไม่ break เพราะลูกค้าอาจอยู่ในหลาย table
                }
            }
        }
    } catch (error) {
        console.error('Error refreshing customer in table:', error);
    }
}

/**
 * Helper functions สำหรับ status
 */
function getStatusClass(status) {
    const statusClasses = {
        'new': 'bg-primary',
        'followup': 'bg-warning text-dark',
        'existing': 'bg-success',
        'inactive': 'bg-secondary'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getStatusText(status) {
    const statusTexts = {
        'new': 'ลูกค้าใหม่',
        'followup': 'ติดตาม',
        'existing': 'ลูกค้าเก่า',
        'inactive': 'ไม่ใช้งาน'
    };
    return statusTexts[status] || status;
}

/**
 * คำนวณสีข้อความที่เหมาะสมสำหรับพื้นหลัง tag
 */
function getTextColor(backgroundColor) {
    const hex = backgroundColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 128 ? '#000000' : '#ffffff';
}

/**
 * รีเฟรช tab ปัจจุบันที่ user อยู่
 */
function refreshCurrentTab() {
    try {
        // หาว่า tab ไหนที่ active อยู่
        const activeTab = document.querySelector('.nav-tabs .nav-link.active');
        if (!activeTab) {
            // fallback ถ้าไม่เจอ active tab ให้โหลด tab ที่มีตาราง
            const visibleTable = document.querySelector('#allCustomersTable, #doTable, #newTable, #followupTable');
            if (visibleTable && visibleTable.offsetParent !== null) {
                loadAllCustomers(); // fallback
            }
            return;
        }
        
        const tabId = activeTab.getAttribute('data-bs-target') || activeTab.getAttribute('href');
        console.log('Current active tab:', tabId);
        
        // โหลดข้อมูลตาม tab ที่ active
        switch (tabId) {
            case '#do':
                // Tab Do - โหลดลูกค้าที่ต้องติดตาม
                if (typeof loadFollowups === 'function') {
                    loadFollowups('doTable');
                }
                break;
                
            case '#new':
                // Tab ลูกค้าใหม่
                loadCustomersByBasket('distribution', 'newTable');
                break;
                
            case '#all':
            default:
                // Tab ลูกค้าทั้งหมด (default)
                loadAllCustomers();
                break;
        }
        
    } catch (error) {
        console.error('Error refreshing current tab:', error);
        // fallback ถ้า error
        loadAllCustomers();
    }
}

/**
 * ส่งข้อมูลบันทึกการโทร
 */
async function submitCallLog() {
    const customerId = document.getElementById('callCustomerId').value;
    const callStatus = document.getElementById('callStatus').value;
    const callResult = document.getElementById('callResult').value;
    const duration = document.getElementById('callDuration').value;
    const notes = document.getElementById('callNotes').value;
    const nextFollowup = document.getElementById('nextFollowup').value;
    
    if (!callStatus) {
        alert('กรุณาเลือกสถานะการโทร');
        return;
    }
    
    const callData = {
        customer_id: customerId,
        call_status: callStatus,
        call_result: callResult || null,
        duration_minutes: duration ? parseInt(duration) : 0,
        notes: notes,
        next_followup_at: nextFollowup || null
    };
    
    try {
        const response = await fetch('api/calls.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(callData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // บันทึก Tags ที่เพิ่มใน Preview
            if (typeof saveCallLogTags === 'function') {
                const tagsSuccess = await saveCallLogTags(customerId);
                if (!tagsSuccess) {
                    // แม้ Tags บันทึกไม่สำเร็จ แต่การโทรบันทึกแล้ว ให้แจ้งเตือน
                    alert('บันทึกการโทรสำเร็จ แต่มี Tags บางตัวที่บันทึกไม่สำเร็จ');
                } else {
                    // บันทึกทั้งการโทรและ Tags สำเร็จ
                    alert('บันทึกการโทรและ Tags สำเร็จ');
                }
            } else {
                // ไม่มี Tags หรือฟังก์ชัน Tags ไม่พร้อม
                alert('บันทึกการโทรสำเร็จ');
            }
            
            // ปิด modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('logCallModal'));
            modal.hide();
            
            // รีเฟรชตารางและ tags
            await refreshCustomerInTable(customerId);
            
            // รีเฟรช tab ปัจจุบันแทนที่จะไป loadAllCustomers เสมอ
            refreshCurrentTab();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (result.message || 'ไม่สามารถบันทึกได้'));
        }
    } catch (error) {
        console.error('Error submitting call log:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}