/**
 * Customer Transfer JavaScript
 * จัดการฟังก์ชันการโอนย้ายลูกค้าระหว่างพนักงานขาย
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Customer Transfer page loaded');
    initializeTransferPage();
});

// Global variables
let telesalesList = [];
let currentCustomers = [];
let selectedCustomers = [];
let currentPage = 1;
let totalPages = 1;
let isLoading = false;

/**
 * เริ่มต้นหน้า Transfer
 */
async function initializeTransferPage() {
    try {
        // Load telesales list
        await loadTelesalesList();
        
        // Load transfer history
        await loadTransferHistory();
        
        // Setup event listeners
        setupEventListeners();
        
        // Initial form validation
        validateTransferForm();
        
        console.log('Transfer page initialized successfully');
    } catch (error) {
        console.error('Error initializing transfer page:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการโหลดหน้า: ' + error.message);
    }
}

/**
 * โหลดรายชื่อพนักงานขาย
 */
async function loadTelesalesList() {
    try {
        const response = await fetch('api/customer-transfer.php?action=telesales_list');
        const data = await response.json();
        
        if (data.success) {
            telesalesList = data.data;
            populateTelesalesDropdowns();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading telesales list:', error);
        throw error;
    }
}

/**
 * เติมข้อมูลใน dropdown พนักงานขาย
 */
function populateTelesalesDropdowns() {
    const sourceSelect = document.getElementById('sourceTelesales');
    const targetSelect = document.getElementById('targetTelesales');
    
    // Clear existing options (keep first option)
    sourceSelect.innerHTML = '<option value="">เลือกพนักงานขาย...</option>';
    targetSelect.innerHTML = '<option value="">เลือกพนักงานขาย...</option>';
    
    // Group by company
    const groupedTelesales = {};
    telesalesList.forEach(ts => {
        if (!groupedTelesales[ts.company_name]) {
            groupedTelesales[ts.company_name] = [];
        }
        groupedTelesales[ts.company_name].push(ts);
    });
    
    // Add options grouped by company
    Object.keys(groupedTelesales).sort().forEach(companyName => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = companyName;
        
        groupedTelesales[companyName].forEach(ts => {
            const option = document.createElement('option');
            option.value = ts.user_id;
            option.textContent = `${ts.full_name} (${ts.customer_count} ลูกค้า)`;
            option.dataset.companyId = ts.company_id;
            option.dataset.companyName = ts.company_name;
            option.dataset.customerCount = ts.customer_count;
            option.dataset.assignedCount = ts.assigned_count;
            option.dataset.activeCount = ts.active_count;
            optgroup.appendChild(option);
        });
        
        sourceSelect.appendChild(optgroup.cloneNode(true));
        targetSelect.appendChild(optgroup.cloneNode(true));
    });
}

/**
 * Setup Event Listeners
 */
function setupEventListeners() {
    // Source telesales change
    document.getElementById('sourceTelesales').addEventListener('change', onSourceTelesalesChange);
    
    // Target telesales change
    document.getElementById('targetTelesales').addEventListener('change', onTargetTelesalesChange);
    
    // Customer search
    document.getElementById('customerSearch').addEventListener('input', debounce(onCustomerSearchChange, 300));
    document.getElementById('gradeFilter').addEventListener('change', onCustomerFilterChange);
    
    // Search controls
    document.getElementById('clearSearchBtn').addEventListener('click', clearSearch);
    document.getElementById('clearSelectedBtn').addEventListener('click', clearSelectedCustomers);
    
    // Form controls
    document.getElementById('resetFormBtn').addEventListener('click', resetForm);
    document.getElementById('transferForm').addEventListener('submit', onTransferFormSubmit);
    
    // Modal controls
    const confirmBtn = document.getElementById('confirmTransferBtn');
    if (confirmBtn) {
        console.log('Adding click listener to confirmTransferBtn');
        confirmBtn.addEventListener('click', function(e) {
            console.log('confirmTransferBtn clicked!', e);
            confirmTransfer();
        });
    } else {
        console.error('confirmTransferBtn not found during setup');
    }
    
    // Transfer reason validation
    document.getElementById('transferReason').addEventListener('input', validateTransferForm);
}

/**
 * เมื่อเลือกพนักงานต้นทาง
 */
async function onSourceTelesalesChange() {
    const sourceSelect = document.getElementById('sourceTelesales');
    const selectedOption = sourceSelect.selectedOptions[0];
    
    if (!selectedOption || !selectedOption.value) {
        hideSourceStats();
        hideCustomerSelection();
        hideTransferSummary();
        return;
    }
    
    try {
        // Show loading
        showSourceStats(true);
        
        // Load telesales stats
        await loadTelesalesStats(selectedOption.value, 'source');
        
        // Show customer search section
        showCustomerSelection();
        
        // Update transfer summary source
        updateTransferSummary();
        
    } catch (error) {
        console.error('Error loading source telesales data:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูลพนักงานต้นทาง');
        hideSourceStats();
        hideCustomerSelection();
    }
}

/**
 * เมื่อเลือกพนักงานปลายทาง
 */
async function onTargetTelesalesChange() {
    const targetSelect = document.getElementById('targetTelesales');
    const selectedOption = targetSelect.selectedOptions[0];
    
    if (!selectedOption || !selectedOption.value) {
        hideTargetStats();
        hideTransferSummary();
        return;
    }
    
    try {
        // Show loading
        showTargetStats(true);
        
        // Load telesales stats
        await loadTelesalesStats(selectedOption.value, 'target');
        
        // Update transfer summary
        updateTransferSummary();
        
        // Validate form
        validateTransferForm();
        
    } catch (error) {
        console.error('Error loading target telesales data:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูลพนักงานปลายทาง');
        hideTargetStats();
    }
}

/**
 * โหลดสถิติพนักงานขาย
 */
async function loadTelesalesStats(telesalesId, type) {
    try {
        const response = await fetch(`api/customer-transfer.php?action=telesales_stats&telesales_id=${telesalesId}`);
        const data = await response.json();
        
        if (data.success) {
            displayTelesalesStats(data.data, type);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading telesales stats:', error);
        throw error;
    }
}

/**
 * แสดงสถิติพนักงานขาย
 */
function displayTelesalesStats(stats, type) {
    const prefix = type; // 'source' or 'target'
    
    document.getElementById(`${prefix}TotalCount`).textContent = stats.total_customers;
    document.getElementById(`${prefix}ActiveCount`).textContent = stats.active_count;
    document.getElementById(`${prefix}AssignedCount`).textContent = stats.assigned_count;
    
    if (type === 'source') {
        showSourceStats();
    } else {
        showTargetStats();
    }
}

/**
 * ค้นหาลูกค้า (แบบใหม่)
 */
async function searchCustomers(telesalesId, searchTerm, grade = '') {
    if (isLoading) return;
    
    try {
        isLoading = true;
        showSearchLoading();
        
        if (searchTerm.length < 3) {
            hideSearchResults();
            showEmptyState();
            return;
        }
        
        const params = new URLSearchParams({
            action: 'search_customers',
            source_telesales_id: telesalesId,
            search: searchTerm,
            grade: grade,
            limit: 20
        });
        
        const response = await fetch(`api/customer-transfer.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error searching customers:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการค้นหาลูกค้า');
    } finally {
        isLoading = false;
        hideSearchLoading();
    }
}

/**
 * แสดงรายชื่อลูกค้าในตาราง
 */
function displayCustomerList() {
    const tbody = document.getElementById('customerTableBody');
    
    if (currentCustomers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-users fa-2x mb-2"></i><br>
                    ไม่พบลูกค้าตามเงื่อนไขที่กำหนด
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = currentCustomers.map(customer => {
        const isSelected = selectedCustomers.includes(customer.customer_id);
        const rowClass = isSelected ? 'customer-selected' : '';
        
        return `
            <tr class="customer-row ${rowClass}" data-customer-id="${customer.customer_id}">
                <td>
                    <input type="checkbox" class="form-check-input customer-checkbox" 
                           value="${customer.customer_id}" ${isSelected ? 'checked' : ''}>
                </td>
                <td>
                    <code>${customer.customer_code}</code>
                </td>
                <td>
                    <strong>${customer.full_name}</strong>
                </td>
                <td>
                    <a href="tel:${customer.phone}" class="text-decoration-none">
                        ${customer.phone}
                    </a>
                </td>
                <td>
                    <span class="badge ${getGradeBadgeClass(customer.customer_grade)}">
                        ${customer.customer_grade}
                    </span>
                </td>
                <td>
                    <span class="badge ${getStatusBadgeClass(customer.customer_status)}">
                        ${getStatusText(customer.customer_status)}
                    </span>
                </td>
                <td>
                    <small>${formatDate(customer.customer_time_base)}</small>
                </td>
                <td>
                    <small class="${customer.days_remaining < 0 ? 'text-danger' : customer.days_remaining < 7 ? 'text-warning' : 'text-success'}">
                        ${formatDate(customer.customer_time_expiry)}
                        <br><span class="badge ${customer.days_remaining < 0 ? 'bg-danger' : customer.days_remaining < 7 ? 'bg-warning' : 'bg-success'}">
                            ${customer.days_remaining >= 0 ? customer.days_remaining + ' วัน' : 'หมดอายุ'}
                        </span>
                    </small>
                </td>
            </tr>
        `;
    }).join('');
    
    // Add event listeners to checkboxes and rows
    setupCustomerRowEvents();
}

/**
 * Setup events for customer rows
 */
function setupCustomerRowEvents() {
    // Checkbox events
    document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', onCustomerCheckboxChange);
    });
    
    // Row click events
    document.querySelectorAll('.customer-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.customer-checkbox');
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });
}

/**
 * เมื่อเลือก/ยกเลิกลูกค้า
 */
function onCustomerCheckboxChange(e) {
    const customerId = parseInt(e.target.value);
    const isChecked = e.target.checked;
    
    if (isChecked && !selectedCustomers.includes(customerId)) {
        selectedCustomers.push(customerId);
    } else if (!isChecked && selectedCustomers.includes(customerId)) {
        selectedCustomers = selectedCustomers.filter(id => id !== customerId);
    }
    
    // Update row styling
    const row = e.target.closest('tr');
    if (isChecked) {
        row.classList.add('customer-selected');
    } else {
        row.classList.remove('customer-selected');
    }
    
    // Update UI
    updateSelectedCount();
    updateSelectAllCheckbox();
    updateTransferSummary();
    validateTransferForm();
}

/**
 * เลือกลูกค้าทั้งหมดในหน้าปัจจุบัน
 */
function selectAllCustomers() {
    document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
    });
}

/**
 * ยกเลิกการเลือกทั้งหมด
 */
function clearAllCustomers() {
    selectedCustomers = [];
    document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.querySelectorAll('.customer-row').forEach(row => {
        row.classList.remove('customer-selected');
    });
    
    updateSelectedCount();
    updateSelectAllCheckbox();
    updateTransferSummary();
    validateTransferForm();
}

/**
 * เมื่อคลิก Select All checkbox
 */
function onSelectAllCheckboxChange(e) {
    if (e.target.checked) {
        selectAllCustomers();
    } else {
        clearAllCustomers();
    }
}

/**
 * อัปเดทจำนวนที่เลือก
 */
function updateSelectedCount() {
    document.getElementById('selectedCount').textContent = `เลือก: ${selectedCustomers.length} คน`;
}

/**
 * อัปเดท Select All checkbox
 */
function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
    
    if (customerCheckboxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        return;
    }
    
    const checkedCount = document.querySelectorAll('.customer-checkbox:checked').length;
    
    if (checkedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === customerCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

/**
 * เมื่อค้นหาลูกค้าเปลี่ยน
 */
function onCustomerSearchChange() {
    const sourceTelesalesId = document.getElementById('sourceTelesales').value;
    const searchTerm = document.getElementById('customerSearch').value;
    const grade = document.getElementById('gradeFilter').value;
    
    if (sourceTelesalesId && searchTerm.length >= 3) {
        searchCustomers(sourceTelesalesId, searchTerm, grade);
    } else if (searchTerm.length < 3) {
        hideSearchResults();
        showEmptyState();
    }
}

function onCustomerFilterChange() {
    onCustomerSearchChange();
}

/**
 * อัปเดทสรุปการโอน
 */
function updateTransferSummary() {
    const sourceSelect = document.getElementById('sourceTelesales');
    const targetSelect = document.getElementById('targetTelesales');
    const hasSource = sourceSelect.value;
    const hasTarget = targetSelect.value;
    const hasSelectedCustomers = selectedCustomers.length > 0;
    
    if (hasSource && hasTarget && hasSelectedCustomers) {
        // Update summary info
        const sourceOption = sourceSelect.selectedOptions[0];
        const targetOption = targetSelect.selectedOptions[0];
        
        document.getElementById('sourceTelesalesName').textContent = sourceOption.textContent.split(' (')[0];
        document.getElementById('targetTelesalesName').textContent = targetOption.textContent.split(' (')[0];
        document.getElementById('transferCustomerCount').textContent = selectedCustomers.length;
        
        showTransferSummary();
    } else {
        hideTransferSummary();
    }
    
    validateTransferForm();
}

/**
 * ตรวจสอบฟอร์มการโอน
 */
function validateTransferForm() {
    const sourceTelesalesId = document.getElementById('sourceTelesales').value;
    const targetTelesalesId = document.getElementById('targetTelesales').value;
    const hasSelectedCustomers = selectedCustomers.length > 0;
    const reason = document.getElementById('transferReason').value.trim();
    
    const isValid = sourceTelesalesId && 
                   targetTelesalesId && 
                   sourceTelesalesId !== targetTelesalesId &&
                   hasSelectedCustomers && 
                   reason.length >= 10;
    
    // Debug info
    console.log('Transfer Form Validation:', {
        sourceTelesalesId: sourceTelesalesId,
        targetTelesalesId: targetTelesalesId,
        hasSelectedCustomers: hasSelectedCustomers,
        selectedCount: selectedCustomers.length,
        reasonLength: reason.length,
        isValid: isValid
    });
    
    const transferBtn = document.getElementById('transferBtn');
    if (transferBtn) {
        transferBtn.disabled = !isValid;
        
        // Update button text based on what's missing
        if (!isValid) {
            if (!sourceTelesalesId) {
                transferBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เลือกพนักงานต้นทาง';
            } else if (!targetTelesalesId) {
                transferBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เลือกพนักงานปลายทาง';
            } else if (sourceTelesalesId === targetTelesalesId) {
                transferBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ต้นทางและปลายทางต้องไม่เหมือนกัน';
            } else if (!hasSelectedCustomers) {
                transferBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เลือกลูกค้าที่ต้องการโอน';
            } else if (reason.length < 10) {
                transferBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เหตุผลต้องมีอย่างน้อย 10 ตัวอักษร';
            }
        } else {
            transferBtn.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>ยืนยันการโอนย้าย';
            
            // Add emergency transfer option
            if (!document.getElementById('emergencyTransferBtn')) {
                const emergencyBtn = document.createElement('button');
                emergencyBtn.id = 'emergencyTransferBtn';
                emergencyBtn.type = 'button';
                emergencyBtn.className = 'btn btn-warning ms-2';
                emergencyBtn.innerHTML = '<i class="fas fa-bolt me-2"></i>โอนทันที (ไม่ผ่าน Modal)';
                emergencyBtn.onclick = function() {
                    if (confirm('โอนย้ายลูกค้าทันทีโดยไม่ผ่าน Modal?')) {
                        CustomerTransfer.forceTransfer();
                    }
                };
                
                transferBtn.parentNode.appendChild(emergencyBtn);
            }
        }
    }
    
    return isValid;
}

/**
 * เมื่อส่งฟอร์มการโอน
 */
function onTransferFormSubmit(e) {
    e.preventDefault();
    
    console.log('Form submitted!');
    console.log('Selected customers:', selectedCustomers);
    
    if (!validateTransferForm()) {
        console.log('Validation failed');
        showAlert('warning', 'กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง');
        return;
    }
    
    console.log('Validation passed, showing confirmation modal');
    
    // Show confirmation modal
    showConfirmationModal();
}

/**
 * แสดง Modal ยืนยันการโอน
 */
function showConfirmationModal() {
    console.log('showConfirmationModal called');
    
    const sourceSelect = document.getElementById('sourceTelesales');
    const targetSelect = document.getElementById('targetTelesales');
    const reason = document.getElementById('transferReason').value;
    
    console.log('Modal data:', {
        sourceValue: sourceSelect.value,
        targetValue: targetSelect.value,
        reason: reason,
        selectedCustomers: selectedCustomers
    });
    
    const sourceText = sourceSelect.selectedOptions[0].textContent;
    const targetText = targetSelect.selectedOptions[0].textContent;
    
    const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-info">จาก:</h6>
                <p><i class="fas fa-user me-2"></i>${sourceText}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-success">ไปยัง:</h6>
                <p><i class="fas fa-user me-2"></i>${targetText}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">จำนวนลูกค้า:</h6>
                <p><i class="fas fa-users me-2"></i>${selectedCustomers.length} คน</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">เหตุผล:</h6>
                <p><i class="fas fa-comment me-2"></i>${reason}</p>
            </div>
        </div>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>หมายเหตุ:</strong> ลูกค้าจะถูกรีเซ็ตวันที่เริ่มต้นเป็น 30 วัน และสถานะจะเปลี่ยนเป็น "Assigned"
        </div>
    `;
    
    const detailsElement = document.getElementById('confirmTransferDetails');
    if (!detailsElement) {
        console.error('confirmTransferDetails element not found');
        showAlert('error', 'เกิดข้อผิดพลาดในการแสดง Modal');
        return;
    }
    
    detailsElement.innerHTML = detailsHtml;
    
    console.log('Creating and showing Bootstrap modal');
    
    const modalElement = document.getElementById('confirmTransferModal');
    if (!modalElement) {
        console.error('confirmTransferModal element not found');
        showAlert('error', 'เกิดข้อผิดพลาดในการแสดง Modal');
        return;
    }
    
    console.log('Modal element found:', modalElement);
    
    try {
        const modal = new bootstrap.Modal(modalElement);
    
            modalElement.addEventListener('shown.bs.modal', function () {
            console.log('Modal shown successfully');
            
            // Re-check if confirm button exists after modal is shown
            const confirmBtnInModal = document.getElementById('confirmTransferBtn');
            if (confirmBtnInModal) {
                console.log('confirmTransferBtn found in modal:', confirmBtnInModal);
                
                // Add event listener again to be sure
                confirmBtnInModal.onclick = function(e) {
                    console.log('confirmTransferBtn clicked via onclick!', e);
                    e.preventDefault();
                    confirmTransfer();
                };
                
                console.log('onclick handler added to confirmTransferBtn');
            } else {
                console.error('confirmTransferBtn NOT found after modal shown');
            }
        });
        
        modalElement.addEventListener('hidden.bs.modal', function () {
            console.log('Modal hidden');
        });
        
        modal.show();
        console.log('Modal show() called');
        
    } catch (error) {
        console.error('Error creating/showing modal:', error);
        
        // Fallback: Use simple confirm dialog
        const confirmMessage = `คุณต้องการโอนลูกค้า ${selectedCustomers.length} คน\nจาก: ${sourceText}\nไปยัง: ${targetText}\nเหตุผล: ${reason}\n\nยืนยันการโอนหรือไม่?`;
        
        if (confirm(confirmMessage)) {
            console.log('Using fallback confirm, proceeding with transfer');
            confirmTransfer();
        }
    }
}

/**
 * ยืนยันการโอน
 */
async function confirmTransfer() {
    console.log('confirmTransfer() called');
    
    const confirmBtn = document.getElementById('confirmTransferBtn');
    if (!confirmBtn) {
        console.error('confirmTransferBtn not found!');
        return;
    }
    
    console.log('confirmTransferBtn found:', confirmBtn);
    const originalText = confirmBtn.innerHTML;
    
    try {
        // Show loading
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังโอน...';
        
        console.log('Starting transfer process...');
        
        const transferData = {
            source_telesales_id: document.getElementById('sourceTelesales').value,
            target_telesales_id: document.getElementById('targetTelesales').value,
            customer_ids: selectedCustomers,
            reason: document.getElementById('transferReason').value.trim()
        };
        
        console.log('Transfer data:', transferData);
        
        console.log('Sending transfer request...');
        
        const response = await fetch('api/customer-transfer.php?action=transfer_customers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(transferData)
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('Parsed response:', data);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success) {
            // Immediate cleanup before anything else
            forceCleanupModal();
            
            // Show success message
            showAlert('success', data.message);
            
            // Reset form
            resetForm();
            
            // Reload transfer history
            await loadTransferHistory();
            
            // Final cleanup and refresh
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('Transfer error:', error);
        
        // Force cleanup modal on error too
        forceCleanupModal();
        
        let errorMessage = 'เกิดข้อผิดพลาดในการโอนย้าย';
        if (error.message) {
            errorMessage += ': ' + error.message;
        }
        
        showAlert('error', errorMessage);
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    }
}

/**
 * รีเซ็ตฟอร์ม
 */
function resetForm() {
    // Reset dropdowns
    document.getElementById('sourceTelesales').value = '';
    document.getElementById('targetTelesales').value = '';
    
    // Reset search and filters
    document.getElementById('customerSearch').value = '';
    document.getElementById('gradeFilter').value = '';
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) statusFilter.value = '';
    document.getElementById('transferReason').value = '';
    
    // Reset selected customers
    selectedCustomers = [];
    currentCustomers = [];
    
    // Clear selected customers display
    const selectedContainer = document.getElementById('selectedCustomersContainer');
    if (selectedContainer) {
        selectedContainer.innerHTML = '';
    }
    
    // Hide sections
    hideSourceStats();
    hideTargetStats();
    hideCustomerSelection();
    hideTransferSummary();
    hideSearchResults();
    showEmptyState();
    
    // Clean up modal styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Remove any stuck modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Reset UI
    updateSelectedCount();
    validateTransferForm();
}

/**
 * โหลดประวัติการโอน
 */
async function loadTransferHistory() {
    try {
        const response = await fetch('api/customer-transfer.php?action=transfer_history&limit=50');
        const data = await response.json();
        
        if (data.success) {
            displayTransferHistory(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading transfer history:', error);
        document.getElementById('transferHistoryBody').innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                    เกิดข้อผิดพลาดในการโหลดประวัติ
                </td>
            </tr>
        `;
    }
}

/**
 * แสดงประวัติการโอน
 */
function displayTransferHistory(history) {
    const tbody = document.getElementById('transferHistoryBody');
    
    if (history.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-history fa-2x mb-2"></i><br>
                    ไม่มีประวัติการโอนย้าย
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = history.map(transfer => `
        <tr>
            <td>
                <small>${transfer.formatted_date}</small>
            </td>
            <td>
                <span class="badge bg-info">#${transfer.transfer_id}</span>
            </td>
            <td>
                <strong>${transfer.source_name}</strong>
                <br><small class="text-muted">${transfer.source_company}</small>
            </td>
            <td>
                <strong>${transfer.target_name}</strong>
                <br><small class="text-muted">${transfer.target_company}</small>
            </td>
            <td>
                <span class="badge bg-primary">${transfer.customer_count} คน</span>
            </td>
            <td>
                <small>${transfer.reason}</small>
            </td>
            <td>
                <small>${transfer.transferred_by_name}</small>
            </td>
        </tr>
    `).join('');
}

/**
 * แสดงผลการค้นหา
 */
function displaySearchResults(customers) {
    const searchResults = document.getElementById('searchResults');
    const searchResultsBody = document.getElementById('searchResultsBody');
    const searchResultCount = document.getElementById('searchResultCount');
    const emptyState = document.getElementById('emptyState');
    
    // Hide empty state
    emptyState.style.display = 'none';
    
    // Update count
    searchResultCount.textContent = customers.length;
    
    if (customers.length === 0) {
        searchResultsBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-search fa-2x mb-2"></i><br>
                    ไม่พบลูกค้าตามคำค้นหา
                </td>
            </tr>
        `;
    } else {
        searchResultsBody.innerHTML = customers.map(customer => `
            <tr class="customer-search-row" data-customer-id="${customer.customer_id}">
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary select-customer-btn" 
                            data-customer='${JSON.stringify(customer)}'>
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
                <td><code>${customer.customer_code}</code></td>
                <td><strong>${customer.full_name}</strong></td>
                <td><a href="tel:${customer.phone}">${customer.phone}</a></td>
                <td><span class="badge ${getGradeBadgeClass(customer.customer_grade)}">${customer.customer_grade}</span></td>
                <td><span class="badge ${getStatusBadgeClass(customer.customer_status)}">${getStatusText(customer.customer_status)}</span></td>
                <td>
                    <small class="${customer.days_remaining < 0 ? 'text-danger' : customer.days_remaining < 7 ? 'text-warning' : 'text-success'}">
                        ${formatDate(customer.customer_time_expiry)}
                        <br><span class="badge ${customer.days_remaining < 0 ? 'bg-danger' : customer.days_remaining < 7 ? 'bg-warning' : 'bg-success'}">
                            ${customer.days_remaining >= 0 ? customer.days_remaining + ' วัน' : 'หมดอายุ'}
                        </span>
                    </small>
                </td>
            </tr>
        `).join('');
        
        // Add event listeners to select buttons
        document.querySelectorAll('.select-customer-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const customerData = JSON.parse(this.dataset.customer);
                selectCustomerForTransfer(customerData);
            });
        });
    }
    
    // Show search results
    searchResults.style.display = 'block';
}

/**
 * เลือกลูกค้าสำหรับโอน
 */
function selectCustomerForTransfer(customer) {
    if (!selectedCustomers.includes(customer.customer_id)) {
        selectedCustomers.push(customer.customer_id);
        
        // Add to selected list display
        const selectedContainer = document.getElementById('selectedCustomersContainer');
        const customerCard = document.createElement('div');
        customerCard.className = 'card mb-2';
        customerCard.innerHTML = `
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${customer.full_name}</strong>
                        <small class="text-muted">
                            (${customer.customer_code}) - ${customer.phone}
                        </small>
                        <span class="badge ${getGradeBadgeClass(customer.customer_grade)} ms-2">${customer.customer_grade}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-customer-btn" 
                            data-customer-id="${customer.customer_id}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        selectedContainer.appendChild(customerCard);
        
        // Add remove event listener
        customerCard.querySelector('.remove-customer-btn').addEventListener('click', function() {
            const customerId = parseInt(this.dataset.customerId);
            removeCustomerFromSelection(customerId);
            customerCard.remove();
        });
        
        // Show selected customers list
        document.getElementById('selectedCustomersList').style.display = 'block';
        
        // Update counts
        updateSelectedCount();
        updateTransferSummary();
        validateTransferForm();
    }
}

/**
 * ลบลูกค้าออกจากการเลือก
 */
function removeCustomerFromSelection(customerId) {
    selectedCustomers = selectedCustomers.filter(id => id !== customerId);
    updateSelectedCount();
    updateTransferSummary();
    validateTransferForm();
    
    // Hide selected list if empty
    if (selectedCustomers.length === 0) {
        document.getElementById('selectedCustomersList').style.display = 'none';
    }
}

/**
 * ล้างการค้นหา
 */
function clearSearch() {
    document.getElementById('customerSearch').value = '';
    document.getElementById('gradeFilter').value = '';
    hideSearchResults();
    showEmptyState();
}

/**
 * ล้างลูกค้าที่เลือกทั้งหมด
 */
function clearSelectedCustomers() {
    selectedCustomers = [];
    document.getElementById('selectedCustomersContainer').innerHTML = '';
    document.getElementById('selectedCustomersList').style.display = 'none';
    updateSelectedCount();
    updateTransferSummary();
    validateTransferForm();
}

/**
 * บังคับทำความสะอาด Modal ทันที
 */
function forceCleanupModal() {
    try {
        // 1. Hide all modals immediately
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
            modal.removeAttribute('role');
        });
        
        // 2. Mark and remove all modal backdrops aggressively
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.classList.add('transfer-cleanup');
            backdrop.remove();
        });
        
        // 3. Clean body classes and styles immediately
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.body.style.marginRight = '';
        
        // 4. Reset any stuck Bootstrap modal instances
        const confirmModal = document.getElementById('confirmTransferModal');
        if (confirmModal) {
            const bsModal = bootstrap.Modal.getInstance(confirmModal);
            if (bsModal) {
                bsModal.dispose();
            }
        }
        
        console.log('Modal cleanup completed');
        
    } catch (error) {
        console.error('Error in modal cleanup:', error);
        // Force page reload if cleanup fails
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
}

/**
 * ทำความสะอาด Modal และ Page Styles หลังโอนสำเร็จ
 */
function cleanupModalAndPageStyles() {
    // Remove modal-open class from body
    document.body.classList.remove('modal-open');
    
    // Remove inline styles that might be stuck
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Remove any remaining modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Force page refresh after 2 seconds to ensure clean state
    setTimeout(() => {
        window.location.reload();
    }, 2000);
}

/**
 * Utility Functions
 */

// Show/Hide functions for new UI
function showSearchLoading() {
    const searchResults = document.getElementById('searchResults');
    const searchResultsBody = document.getElementById('searchResultsBody');
    searchResultsBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>
                กำลังค้นหาลูกค้า...
            </td>
        </tr>
    `;
    searchResults.style.display = 'block';
}

function hideSearchLoading() {
    // Loading will be replaced by actual results
}

function hideSearchResults() {
    document.getElementById('searchResults').style.display = 'none';
}

function showEmptyState() {
    document.getElementById('emptyState').style.display = 'block';
}

function hideEmptyState() {
    document.getElementById('emptyState').style.display = 'none';
}

// Show/Hide functions
function showSourceStats(loading = false) {
    const element = document.getElementById('sourceStats');
    element.classList.remove('d-none');
    if (loading) element.classList.add('loading');
    else element.classList.remove('loading');
}

function hideSourceStats() {
    document.getElementById('sourceStats').classList.add('d-none');
}

function showTargetStats(loading = false) {
    const element = document.getElementById('targetStats');
    element.classList.remove('d-none');
    if (loading) element.classList.add('loading');
    else element.classList.remove('loading');
}

function hideTargetStats() {
    document.getElementById('targetStats').classList.add('d-none');
}

function showCustomerSelection() {
    document.getElementById('customerSelectionRow').style.display = 'block';
}

function hideCustomerSelection() {
    document.getElementById('customerSelectionRow').style.display = 'none';
}

function showTransferSummary() {
    document.getElementById('transferSummaryRow').style.display = 'block';
}

function hideTransferSummary() {
    document.getElementById('transferSummaryRow').style.display = 'none';
}

// Removed old customer loading functions - replaced with search-based UI

/**
 * Badge helper functions
 */
function getGradeBadgeClass(grade) {
    const classes = {
        'A+': 'bg-warning text-dark',
        'A': 'bg-primary',
        'B': 'bg-success',
        'C': 'bg-info',
        'D': 'bg-secondary'
    };
    return classes[grade] || 'bg-secondary';
}

function getStatusBadgeClass(status) {
    const classes = {
        'assigned': 'bg-primary',
        'active': 'bg-success',
        'contacted': 'bg-info',
        'expired': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
}

function getStatusText(status) {
    const texts = {
        'assigned': 'Assigned',
        'active': 'Active',
        'contacted': 'Contacted',
        'expired': 'Expired'
    };
    return texts[status] || status;
}

/**
 * Format date helper
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Debounce helper
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Update pagination
 */
function updatePagination(pagination) {
    const paginationContainer = document.getElementById('customerPagination');
    
    if (pagination.total_pages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'block';
    
    let paginationHtml = '';
    
    // Previous button
    if (pagination.current_page > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === pagination.current_page;
        paginationHtml += `
            <li class="page-item ${isActive ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    paginationContainer.querySelector('.pagination').innerHTML = paginationHtml;
    
    // Add click events
    paginationContainer.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            if (page) {
                const sourceTelesalesId = document.getElementById('sourceTelesales').value;
                loadCustomerList(sourceTelesalesId, page);
            }
        });
    });
}

/**
 * Update customer counts
 */
function updateCustomerCounts(pagination) {
    document.getElementById('displayedCount').textContent = pagination.per_page;
    document.getElementById('totalCustomerCount').textContent = pagination.total_records;
}

/**
 * Show alert messages
 */
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert_' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            const bsAlert = new bootstrap.Alert(alertElement);
            bsAlert.close();
        }
    }, 5000);
}

// Export functions for external use
window.CustomerTransfer = {
    loadTelesalesList,
    searchCustomers,
    resetForm,
    showAlert,
    confirmTransfer,
    testConfirmButton: function() {
        const btn = document.getElementById('confirmTransferBtn');
        console.log('confirmTransferBtn test:', btn);
        if (btn) {
            console.log('Button found, attempting click simulation');
            btn.click();
        } else {
            console.log('Button not found');
        }
    },
    forceTransfer: async function() {
        console.log('🚀 FORCE TRANSFER INITIATED');
        
        const sourceId = document.getElementById('sourceTelesales').value;
        const targetId = document.getElementById('targetTelesales').value;
        const reason = document.getElementById('transferReason').value;
        
        if (!sourceId || !targetId || selectedCustomers.length === 0) {
            alert('ข้อมูลไม่ครบ! ตรวจสอบพนักงานและลูกค้าที่เลือก');
            return;
        }
        
        try {
            const transferData = {
                source_telesales_id: sourceId,
                target_telesales_id: targetId,
                customer_ids: selectedCustomers,
                reason: reason || 'Force transfer via console'
            };
            
            console.log('📤 Sending transfer data:', transferData);
            
            const response = await fetch('api/customer-transfer.php?action=transfer_customers', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(transferData)
            });
            
            console.log('📥 Response status:', response.status);
            
            const result = await response.text();
            console.log('📥 Raw response:', result);
            
            const data = JSON.parse(result);
            console.log('📥 Parsed data:', data);
            
            if (data.success) {
                alert('✅ โอนสำเร็จ! ' + data.message);
                window.location.reload();
            } else {
                alert('❌ โอนล้มเหลว: ' + data.message);
            }
            
        } catch (error) {
            console.error('💥 Transfer error:', error);
            alert('เกิดข้อผิดพลาด: ' + error.message);
        }
    }
};
