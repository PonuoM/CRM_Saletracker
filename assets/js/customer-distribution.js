/**
 * Customer Distribution JavaScript
 * จัดการการทำงานของระบบแจกลูกค้าตามคำขอ
 */

// Global variables
let distributionStats = {};
let availableTelesales = [];
let availableCustomers = [];

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customer Distribution initialized');
    loadDistributionData();
    setupEventListeners();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Distribution form submission
    const form = document.getElementById('distributionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            distributeCustomers();
        });
    }

    // Priority change event
    const prioritySelect = document.getElementById('distributionPriority');
    if (prioritySelect) {
        prioritySelect.addEventListener('change', function() {
            updateAvailableCustomersPreview();
        });
    }

    // Add clear form button event listener
    const clearFormBtn = document.getElementById('clearFormBtn');
    if (clearFormBtn) {
        clearFormBtn.addEventListener('click', clearDistributionForm);
    }
}

/**
 * Clear distribution form and reset all fields
 */
function clearDistributionForm() {
    // Reset form fields
    const form = document.getElementById('distributionForm');
    if (form) {
        form.reset();
    }

    // Clear selected telesales
    const telesalesSelect = document.getElementById('distributionTelesales');
    if (telesalesSelect) {
        telesalesSelect.selectedIndex = -1;
    }

    // Clear distribution results
    const resultsContainer = document.getElementById('distributionResults');
    if (resultsContainer) {
        resultsContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                <h5>ยังไม่มีการแจกลูกค้า</h5>
                <p class="text-muted">กรุณาเลือกจำนวนและ Telesales แล้วกดแจกลูกค้า</p>
            </div>
        `;
    }

    // Clear available customers preview
    const previewContainer = document.getElementById('availableCustomersPreview');
    if (previewContainer) {
        previewContainer.innerHTML = `
            <div class="text-center">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">กำลังโหลด...</span>
                </div>
                <span class="ms-2">กำลังโหลดข้อมูล...</span>
            </div>
        `;
    }

    // Refresh data
    loadDistributionData();
    
    // Show success message
    showSuccess('ล้างฟอร์มเรียบร้อยแล้ว');
}

/**
 * Clear average distribution form
 */
function clearAverageForm() {
    const form = document.getElementById('averageDistributionForm');
    if (form) {
        form.reset();
    }

    // Clear selected telesales
    const telesalesSelect = document.getElementById('averageTelesales');
    if (telesalesSelect) {
        telesalesSelect.selectedIndex = -1;
    }

    showSuccess('ล้างฟอร์มแจกแบบเฉลี่ยเรียบร้อยแล้ว');
}

/**
 * Clear grade A distribution form
 */
function clearGradeAForm() {
    const form = document.getElementById('gradeADistributionForm');
    if (form) {
        form.reset();
    }

    // Clear selected telesales
    const telesalesSelect = document.getElementById('gradeATelesales');
    if (telesalesSelect) {
        telesalesSelect.selectedIndex = -1;
    }

    showSuccess('ล้างฟอร์มแจกเกรด A เรียบร้อยแล้ว');
}

/**
 * Distribute customers using average distribution
 */
function distributeAverage() {
    const quantity = parseInt(document.getElementById('averageQuantity').value);
    const telesalesSelect = document.getElementById('averageTelesales');
    const dateFrom = document.getElementById('averageDateFrom').value;
    
    if (!telesalesSelect) {
        showError('ไม่พบ dropdown Telesales');
        return;
    }

    const selectedTelesales = Array.from(telesalesSelect.selectedOptions).map(option => option.value);
    
    if (selectedTelesales.length === 0) {
        showError('กรุณาเลือก Telesales อย่างน้อย 1 คน');
        return;
    }

    if (quantity <= 0) {
        showError('กรุณาระบุจำนวนลูกค้าที่ต้องการแจก');
        return;
    }

    // Show loading
    const submitBtn = document.querySelector('#averageDistributionForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังแจกแบบเฉลี่ย...';
    submitBtn.disabled = true;

    const data = {
        company: 'prima', // Default company
        customer_count: quantity,
        telesales_ids: selectedTelesales,
        date_from: dateFrom,
        date_to: null
    };

    fetch('api/customer-distribution.php?action=distribute_average', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(data.message, 'แจกลูกค้าแบบเฉลี่ยสำเร็จ');
            displayAverageDistributionResults(data.data);
            
            // Auto-clear form after successful distribution
            setTimeout(() => {
                clearAverageForm();
            }, 3000);
            
            // Refresh data
            setTimeout(() => {
                loadDistributionData();
            }, 2000);
        } else {
            showError(data.message || 'เกิดข้อผิดพลาดในการแจกลูกค้าแบบเฉลี่ย');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    })
    .finally(() => {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Distribute grade A customers
 */
function distributeGradeA() {
    const telesalesSelect = document.getElementById('gradeATelesales');
    const countPerPerson = parseInt(document.getElementById('gradeACount').value);
    const gradeSelection = Array.from(document.getElementById('gradeASelection').selectedOptions).map(option => option.value);
    
    if (!telesalesSelect) {
        showError('ไม่พบ dropdown Telesales');
        return;
    }

    const selectedTelesales = Array.from(telesalesSelect.selectedOptions).map(option => option.value);
    
    if (selectedTelesales.length === 0) {
        showError('กรุณาเลือก Telesales อย่างน้อย 1 คน');
        return;
    }

    if (countPerPerson <= 0) {
        showError('กรุณาระบุจำนวนลูกค้าต่อคน');
        return;
    }

    if (gradeSelection.length === 0) {
        showError('กรุณาเลือกเกรดที่ต้องการแจก');
        return;
    }

    // Show loading
    const submitBtn = document.querySelector('#gradeADistributionForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังแจกเกรด A...';
    submitBtn.disabled = true;

    // Prepare allocations
    const allocations = selectedTelesales.map(telesalesId => ({
        telesales_id: parseInt(telesalesId),
        count: countPerPerson
    }));

    const data = {
        company: 'prima', // Default company
        allocations: allocations,
        selected_grades: gradeSelection
    };

    fetch('api/customer-distribution.php?action=distribute_grade_a', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(data.message, 'แจกลูกค้าเกรด A สำเร็จ');
            displayGradeADistributionResults(data.data);
            
            // Auto-clear form after successful distribution
            setTimeout(() => {
                clearGradeAForm();
            }, 3000);
            
            // Refresh data
            setTimeout(() => {
                loadDistributionData();
            }, 2000);
        } else {
            showError(data.message || 'เกิดข้อผิดพลาดในการแจกลูกค้าเกรด A');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    })
    .finally(() => {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Display average distribution results
 */
function displayAverageDistributionResults(data) {
    const container = document.getElementById('distributionResults');
    if (!container) return;

    let html = `
        <div class="alert alert-success mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-balance-scale fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">✅ แจกลูกค้าแบบเฉลี่ยสำเร็จแล้ว!</h5>
                    <p class="mb-0">แจกลูกค้า <strong>${data.total_distributed || 0}</strong> รายการให้ Telesales <strong>${data.distributions?.length || 0}</strong> คน</p>
                </div>
            </div>
        </div>
    `;

    if (data.distributions && data.distributions.length > 0) {
        html += `
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>สรุปการแจกลูกค้าแบบเฉลี่ย</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Telesales</th>
                                    <th class="text-center">จำนวนลูกค้า</th>
                                    <th class="text-center">รายละเอียด</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        data.distributions.forEach(distribution => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(distribution.telesales_name)}</strong></td>
                    <td class="text-center"><span class="badge bg-success fs-6">${distribution.count}</span></td>
                    <td>
                        <small class="text-muted">
                            ${distribution.customers?.length > 0 ? 
                                `ลูกค้า: ${distribution.customers.map(c => c.full_name).join(', ')}` : 
                                'ไม่มีรายละเอียดลูกค้า'
                            }
                        </small>
                    </td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // Action buttons
    html += `
        <div class="text-center">
            <button type="button" class="btn btn-outline-secondary me-2" onclick="clearAverageForm()">
                <i class="fas fa-eraser me-1"></i>ล้างฟอร์มและเริ่มใหม่
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="refreshDistributionStats()">
                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
            </button>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Display grade A distribution results
 */
function displayGradeADistributionResults(data) {
    const container = document.getElementById('distributionResults');
    if (!container) return;

    let html = `
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-star fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">⭐ แจกลูกค้าเกรด A สำเร็จแล้ว!</h5>
                    <p class="mb-0">แจกลูกค้าเกรด A <strong>${data.total_distributed || 0}</strong> รายการให้ Telesales <strong>${data.distributions?.length || 0}</strong> คน</p>
                </div>
            </div>
        </div>
    `;

    if (data.distributions && data.distributions.length > 0) {
        html += `
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-star me-2"></i>สรุปการแจกลูกค้าเกรด A</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Telesales</th>
                                    <th class="text-center">จำนวนลูกค้า</th>
                                    <th class="text-center">เกรด</th>
                                    <th class="text-center">รายละเอียด</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        data.distributions.forEach(distribution => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(distribution.telesales_name)}</strong></td>
                    <td class="text-center"><span class="badge bg-warning fs-6">${distribution.count}</span></td>
                    <td class="text-center"><span class="badge bg-success">${distribution.grade}</span></td>
                    <td>
                        <small class="text-muted">
                            ${distribution.customers?.length > 0 ? 
                                `ลูกค้า: ${distribution.customers.map(c => c.name).join(', ')}` : 
                                'ไม่มีรายละเอียดลูกค้า'
                            }
                        </small>
                    </td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // Action buttons
    html += `
        <div class="text-center">
            <button type="button" class="btn btn-outline-secondary me-2" onclick="clearGradeAForm()">
                <i class="fas fa-eraser me-1"></i>ล้างฟอร์มและเริ่มใหม่
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="refreshDistributionStats()">
                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
            </button>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Load all distribution data
 */
function loadDistributionData() {
    loadDistributionStats();
    loadAvailableTelesales();
    loadAvailableCustomersPreview();
}

/**
 * Load distribution statistics
 */
function loadDistributionStats() {
    fetch('api/customer-distribution.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                distributionStats = data.data;
                updateStatsDisplay();
            } else {
                console.error('Error loading stats:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

/**
 * Update statistics display
 */
function updateStatsDisplay() {
    const elements = {
        'distributionCount': distributionStats.distribution_count || 0,
        'availableTelesalesCount': distributionStats.available_telesales_count || 0,
        'hotCustomersCount': distributionStats.hot_customers_count || 0,
        'warmCustomersCount': distributionStats.warm_customers_count || 0
    };

    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = elements[id].toLocaleString();
        }
    });
}

/**
 * Load available telesales
 */
function loadAvailableTelesales() {
    fetch('api/customer-distribution.php?action=available_telesales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableTelesales = data.data;
                populateTelesalesSelect();
            } else {
                console.error('Error loading telesales:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

/**
 * Populate telesales select dropdown
 */
function populateTelesalesSelect() {
    const select = document.getElementById('distributionTelesales');
    if (!select) return;

    select.innerHTML = '';

    availableTelesales.forEach(telesales => {
        const option = document.createElement('option');
        option.value = telesales.user_id;
        option.textContent = `${telesales.full_name} (ลูกค้าปัจจุบัน: ${telesales.current_customers_count})`;
        select.appendChild(option);
    });
}

/**
 * Load available customers preview
 */
function loadAvailableCustomersPreview() {
    const priority = document.getElementById('distributionPriority')?.value || 'hot_warm_cold';
    
    fetch(`api/customer-distribution.php?action=available_customers&priority=${priority}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableCustomers = data.data;
                updateAvailableCustomersPreview();
            } else {
                console.error('Error loading customers:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

/**
 * Update available customers preview display
 */
function updateAvailableCustomersPreview() {
    const container = document.getElementById('availableCustomersPreview');
    if (!container) return;

    if (availableCustomers.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-inbox text-muted fa-3x mb-3"></i>
                <h5>ไม่มีลูกค้าที่พร้อมแจก</h5>
                <p class="text-muted">ลูกค้าทั้งหมดอาจถูกแจกไปแล้วหรืออยู่ในสถานะ Frozen</p>
            </div>
        `;
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>ชื่อลูกค้า</th><th>เบอร์โทร</th><th>จังหวัด</th><th>สถานะ</th><th>เกรด</th><th>วันที่ได้รับ</th></tr></thead><tbody>';

    availableCustomers.forEach(customer => {
        const tempIcons = {
            'hot': '🔥',
            'warm': '🌤️',
            'cold': '❄️',
            'frozen': '🧊'
        };

        html += '<tr>';
        html += `<td><strong>${escapeHtml(customer.first_name + ' ' + customer.last_name)}</strong></td>`;
        html += `<td>${escapeHtml(customer.phone || '-')}</td>`;
        html += `<td>${escapeHtml(customer.province || '-')}</td>`;
        html += `<td>${tempIcons[customer.temperature_status] || '❓'} ${customer.temperature_status || '-'}</td>`;
        html += `<td><span class="badge bg-${getGradeColor(customer.customer_grade)}">${customer.customer_grade || '-'}</span></td>`;
        html += `<td>${formatDate(customer.created_at)}</td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * Distribute customers based on form data
 */
function distributeCustomers() {
    const quantity = parseInt(document.getElementById('distributionQuantity').value);
    const priority = document.getElementById('distributionPriority').value;
    const telesalesSelect = document.getElementById('distributionTelesales');
    
    if (!telesalesSelect) {
        showError('ไม่พบ dropdown Telesales');
        return;
    }

    const selectedTelesales = Array.from(telesalesSelect.selectedOptions).map(option => option.value);
    
    if (selectedTelesales.length === 0) {
        showError('กรุณาเลือก Telesales อย่างน้อย 1 คน');
        return;
    }

    if (quantity <= 0) {
        showError('กรุณาระบุจำนวนลูกค้าที่ต้องการแจก');
        return;
    }

    // Show loading
    const submitBtn = document.querySelector('#distributionForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังแจก...';
    submitBtn.disabled = true;

    const data = {
        quantity: quantity,
        priority: priority,
        telesales_ids: selectedTelesales
    };

    fetch('api/customer-distribution.php?action=distribute', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(data.message, 'แจกลูกค้าสำเร็จ');
            // Use enhanced display function
            if (data.results) {
                displayDistributionResultsEnhanced(data.results);
            } else {
                displayDistributionResults(data);
            }
            
            // Auto-clear form after successful distribution
            setTimeout(() => {
                clearDistributionForm();
            }, 3000); // Clear after 3 seconds
            
            // Refresh data
            setTimeout(() => {
                loadDistributionData();
            }, 2000);
        } else {
            showError(data.message || 'เกิดข้อผิดพลาดในการแจกลูกค้า');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    })
    .finally(() => {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Display distribution results with clear status
 */
function displayDistributionResults(results) {
    const container = document.getElementById('distributionResults');
    if (!container) return;

    let html = '<div class="alert alert-success mb-3">';
    html += `<h6><i class="fas fa-check-circle me-2"></i>แจกลูกค้าสำเร็จ</h6>`;
    html += `<p>แจกลูกค้า ${results.total_distributed} รายการให้ Telesales ${results.telesales_count} คน</p>`;
    html += '</div>';

    html += '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>Telesales</th><th>จำนวนลูกค้า</th><th>รายละเอียด</th></tr></thead><tbody>';

    results.distribution_details.forEach(detail => {
        html += '<tr>';
        html += `<td><strong>${escapeHtml(detail.telesales_name)}</strong></td>`;
        html += `<td><span class="badge bg-primary">${detail.customer_count}</span></td>`;
        html += `<td>`;
        if (detail.hot_count > 0) html += `<span class="badge bg-danger me-1">🔥 Hot: ${detail.hot_count}</span>`;
        if (detail.warm_count > 0) html += `<span class="badge bg-warning me-1">🌤️ Warm: ${detail.warm_count}</span>`;
        if (detail.cold_count > 0) html += `<span class="badge bg-info me-1">❄️ Cold: ${detail.cold_count}</span>`;
        html += `</td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';

    // Add customer details if available
    if (results.customer_details && results.customer_details.length > 0) {
        html += '<div class="mt-3"><h6>รายละเอียดลูกค้าที่แจก:</h6>';
        html += '<div class="table-responsive"><table class="table table-sm">';
        html += '<thead><tr><th>ชื่อลูกค้า</th><th>เบอร์โทร</th><th>สถานะ</th><th>มอบหมายให้</th></tr></thead><tbody>';

        results.customer_details.forEach(customer => {
            const tempIcons = {
                'hot': '🔥',
                'warm': '🌤️',
                'cold': '❄️',
                'frozen': '🧊'
            };

            html += '<tr>';
            html += `<td><strong>${escapeHtml(customer.first_name + ' ' + customer.last_name)}</strong></td>`;
            html += `<td>${escapeHtml(customer.phone || '-')}</td>`;
            html += `<td>${tempIcons[customer.temperature_status] || '❓'} ${customer.temperature_status || '-'}</td>`;
            html += `<td><span class="badge bg-success">${escapeHtml(customer.assigned_to_name)}</span></td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div></div>';
    }

    // Add clear form button
    html += `
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-outline-secondary" onclick="clearDistributionForm()">
                <i class="fas fa-eraser me-1"></i>ล้างฟอร์มและเริ่มใหม่
            </button>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Display distribution results with enhanced status display
 */
function displayDistributionResultsEnhanced(results) {
    const container = document.getElementById('distributionResults');
    if (!container) return;

    // Clear previous results
    container.innerHTML = '';

    // Success header
    let html = `
        <div class="alert alert-success mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">✅ แจกลูกค้าสำเร็จแล้ว!</h5>
                    <p class="mb-0">แจกลูกค้า <strong>${results.total_distributed || 0}</strong> รายการให้ Telesales <strong>${results.telesales_count || 0}</strong> คน</p>
                </div>
            </div>
        </div>
    `;

    // Distribution summary
    if (results.distribution_details && results.distribution_details.length > 0) {
        html += `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>สรุปการแจกลูกค้า</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Telesales</th>
                                    <th class="text-center">จำนวนลูกค้า</th>
                                    <th class="text-center">🔥 Hot</th>
                                    <th class="text-center">🌤️ Warm</th>
                                    <th class="text-center">❄️ Cold</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        results.distribution_details.forEach(detail => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(detail.telesales_name)}</strong></td>
                    <td class="text-center"><span class="badge bg-primary fs-6">${detail.customer_count}</span></td>
                    <td class="text-center"><span class="badge bg-danger">${detail.hot_count || 0}</span></td>
                    <td class="text-center"><span class="badge bg-warning">${detail.warm_count || 0}</span></td>
                    <td class="text-center"><span class="badge bg-info">${detail.cold_count || 0}</span></td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // Customer details
    if (results.customer_details && results.customer_details.length > 0) {
        html += `
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>รายละเอียดลูกค้าที่แจก</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ชื่อลูกค้า</th>
                                    <th>เบอร์โทร</th>
                                    <th>สถานะ</th>
                                    <th>เกรด</th>
                                    <th>มอบหมายให้</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        results.customer_details.forEach(customer => {
            const tempIcons = {
                'hot': '🔥',
                'warm': '🌤️',
                'cold': '❄️',
                'frozen': '🧊'
            };

            const gradeColors = {
                'A+': 'success',
                'A': 'primary',
                'B': 'info',
                'C': 'warning',
                'D': 'danger'
            };

            html += `
                <tr>
                    <td><strong>${escapeHtml(customer.first_name + ' ' + customer.last_name)}</strong></td>
                    <td>${escapeHtml(customer.phone || '-')}</td>
                    <td>
                        <span class="badge bg-${customer.temperature_status === 'hot' ? 'danger' : customer.temperature_status === 'warm' ? 'warning' : 'info'}">
                            ${tempIcons[customer.temperature_status] || '❓'} ${customer.temperature_status || '-'}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-${gradeColors[customer.customer_grade] || 'secondary'}">
                            ${customer.customer_grade || '-'}
                        </span>
                    </td>
                    <td><span class="badge bg-success">${escapeHtml(customer.assigned_to_name)}</span></td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // Action buttons
    html += `
        <div class="text-center">
            <button type="button" class="btn btn-outline-secondary me-2" onclick="clearDistributionForm()">
                <i class="fas fa-eraser me-1"></i>ล้างฟอร์มและเริ่มใหม่
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="refreshDistributionStats()">
                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
            </button>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Distribute customers to selected telesales
 */
function distributeCustomers() {
    const telesalesSelect = document.getElementById('distributionTelesales');
    const quantityInput = document.getElementById('distributionQuantity');
    const prioritySelect = document.getElementById('distributionPriority');

    if (!telesalesSelect || !quantityInput || !prioritySelect) {
        showError('ไม่พบฟอร์มการแจกลูกค้า');
        return;
    }

    const selectedOptions = Array.from(telesalesSelect.selectedOptions);
    const quantity = parseInt(quantityInput.value);
    const priority = prioritySelect.value;

    if (selectedOptions.length === 0) {
        showError('กรุณาเลือก Telesales ก่อน');
        return;
    }

    if (!quantity || quantity < 1) {
        showError('กรุณาระบุจำนวนลูกค้าที่ต้องการ');
        return;
    }

    if (!confirm(`คุณต้องการแจกลูกค้า ${quantity} คนให้ Telesales ที่เลือกหรือไม่?`)) {
        return;
    }

    // Disable form during processing
    const submitBtn = document.querySelector('#distributionForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังแจกลูกค้า...';
    submitBtn.disabled = true;

    const telesalesIds = selectedOptions.map(option => parseInt(option.value));

    fetch('api/customer-distribution.php?action=distribute', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            quantity: quantity,
            priority: priority,
            telesales_ids: telesalesIds
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(`แจกลูกค้า ${data.results.total_distributed} คนสำเร็จ`);
            displayDistributionResults(data.results);
            loadDistributionData(); // Refresh data
        } else {
            showError('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
    })
    .finally(() => {
        // Re-enable form
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Display distribution results
 */
function displayDistributionResults(results) {
    const container = document.getElementById('distributionResults');
    if (!container) return;

    let html = '<div class="alert alert-success">';
    html += `<h5><i class="fas fa-check-circle me-2"></i>แจกลูกค้าสำเร็จ</h5>`;
    html += `<p>แจกลูกค้า ${results.total_distributed} คนให้ Telesales ${results.telesales_count} คน</p>`;
    html += '</div>';

    if (results.distribution_details && results.distribution_details.length > 0) {
        html += '<div class="table-responsive mt-3">';
        html += '<table class="table table-sm table-bordered">';
        html += '<thead><tr><th>Telesales</th><th>จำนวนลูกค้า</th><th>Hot</th><th>Warm</th><th>Cold</th></tr></thead><tbody>';

        results.distribution_details.forEach(detail => {
            html += '<tr>';
            html += `<td><strong>${escapeHtml(detail.telesales_name)}</strong></td>`;
            html += `<td class="text-center">${detail.customer_count}</td>`;
            html += `<td class="text-center"><span class="badge bg-danger">${detail.hot_count}</span></td>`;
            html += `<td class="text-center"><span class="badge bg-warning">${detail.warm_count}</span></td>`;
            html += `<td class="text-center"><span class="badge bg-info">${detail.cold_count}</span></td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div>';
    }

    container.innerHTML = html;
}

/**
 * Refresh distribution statistics
 */
function refreshDistributionStats() {
    loadDistributionData();
    showSuccess('อัปเดตสถิติเรียบร้อย');
}

/**
 * Utility functions
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH');
}

function getGradeColor(grade) {
    const colors = {
        'A+': 'success',
        'A': 'primary',
        'B': 'info',
        'C': 'warning',
        'D': 'danger'
    };
    return colors[grade] || 'secondary';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
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
 * Show success modal in center of screen with detailed information
 */
function showSuccessModal(message, title = 'สำเร็จ') {
    // Create modal backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.style.cssText = 'z-index: 9998;';
    document.body.appendChild(backdrop);

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal fade show d-block';
    modal.style.cssText = 'z-index: 9999;';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>${title}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="closeSuccessModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>${message}
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>ข้อมูลสรุป</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>สถานะ:</strong> <span class="badge bg-success">สำเร็จ</span></p>
                                    <p><strong>เวลา:</strong> ${new Date().toLocaleString('th-TH')}</p>
                                    <p><strong>ประเภท:</strong> ${title}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>คำแนะนำ</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>ฟอร์มจะถูกล้างอัตโนมัติใน 3 วินาที</li>
                                        <li>ข้อมูลจะถูกอัปเดตอัตโนมัติ</li>
                                        <li>สามารถดูรายละเอียดเพิ่มเติมได้ด้านล่าง</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeSuccessModal()">
                        <i class="fas fa-times me-1"></i>ปิด
                    </button>
                    <button type="button" class="btn btn-success" onclick="closeSuccessModal()">
                        <i class="fas fa-check me-1"></i>เข้าใจแล้ว
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Auto close after 8 seconds
    setTimeout(() => {
        closeSuccessModal();
    }, 8000);
}

/**
 * Close success modal
 */
function closeSuccessModal() {
    const modal = document.querySelector('.modal.show');
    const backdrop = document.querySelector('.modal-backdrop.show');
    
    if (modal) {
        modal.classList.remove('show');
        modal.classList.add('fade');
        setTimeout(() => modal.remove(), 150);
    }
    
    if (backdrop) {
        backdrop.classList.remove('show');
        setTimeout(() => backdrop.remove(), 150);
    }
} 