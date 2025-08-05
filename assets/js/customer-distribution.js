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
            showSuccess(data.message);
            displayDistributionResults(data.results);
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
 * Display distribution results
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