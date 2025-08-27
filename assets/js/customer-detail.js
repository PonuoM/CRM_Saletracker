/**
 * Customer Detail Page JavaScript Functions
 * ฟังก์ชัน JavaScript สำหรับหน้ารายละเอียดลูกค้า
 */

// Global variables
let currentCustomerId = null;

// Customer detail page specific functions - Define these first
window.logCall = function(customerId) {
    console.log('logCall function called with customer ID:', customerId);
    currentCustomerId = customerId;
    try {
        // Ensure existing backdrops are cleared to avoid blocking UI
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());

        const modalEl = document.getElementById('logCallModal');
        if (!modalEl) {
            console.error('logCallModal not found in DOM');
            alert('ไม่พบฟอร์มบันทึกการโทรในหน้านี้');
            return;
        }

        // Move modal under body to avoid transform/overflow clipping
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        // Bind values and reset form
        const customerIdField = document.getElementById('callCustomerId');
        if (customerIdField) customerIdField.value = customerId;
        const form = document.getElementById('logCallForm');
        if (form) form.reset();

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true, focus: true });
        modal.show();
        console.log('Log call modal opened successfully');
    } catch (error) {
        console.error('Error opening log call modal:', error);
        alert('เกิดข้อผิดพลาดในการเปิดหน้าต่างบันทึกการโทร');
    }
};

window.createAppointment = function(customerId) {
    console.log('createAppointment function called with customer ID:', customerId);
    currentCustomerId = customerId;
    
    try {
        // Create a simple appointment modal since appointments.php doesn't exist
        const appointmentModal = `
            <div class="modal fade" id="appointmentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">สร้างนัดหมาย</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="appointmentForm">
                                <input type="hidden" id="appointmentCustomerId" value="${customerId}">
                                <div class="mb-3">
                                    <label for="appointmentDate" class="form-label">วันที่นัดหมาย</label>
                                    <input type="datetime-local" class="form-control" id="appointmentDate" required>
                                </div>
                                <div class="mb-3">
                                    <label for="appointmentType" class="form-label">ประเภทนัดหมาย</label>
                                    <select class="form-select" id="appointmentType" required>
                                        <option value="">เลือกประเภท</option>
                                        <option value="call">โทรศัพท์</option>
                                        <option value="meeting">ประชุม</option>
                                        <option value="presentation">นำเสนอ</option>
                                        <option value="followup">ติดตาม</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="appointmentNotes" class="form-label">หมายเหตุ</label>
                                    <textarea class="form-control" id="appointmentNotes" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">เพิ่ม Tag</label>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showAddTagModalFromAppointment()">
                                            <i class="fas fa-plus"></i> เพิ่ม Tag
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="button" class="btn btn-primary" onclick="submitAppointment()">บันทึก</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('appointmentModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', appointmentModal);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
        modal.show();
        
        console.log('Appointment modal opened successfully');
    } catch (error) {
        console.error('Error creating appointment modal:', error);
        alert('เกิดข้อผิดพลาดในการสร้างหน้าต่างนัดหมาย');
    }
};

window.createOrder = function(customerId) {
    console.log('createOrder function called with customer ID:', customerId);
    
    try {
        // Redirect to order creation page
        const orderUrl = `orders.php?action=create&customer_id=${customerId}`;
        console.log('Redirecting to:', orderUrl);
        window.location.href = orderUrl;
    } catch (error) {
        console.error('Error redirecting to order creation:', error);
        alert('เกิดข้อผิดพลาดในการไปยังหน้าสร้างคำสั่งซื้อ');
    }
};

window.viewHistory = function(customerId) {
    console.log('viewHistory function called with customer ID:', customerId);
    
    try {
        // Show all history in a modal or redirect to history page
        window.location.href = `customers.php?action=history&id=${customerId}`;
    } catch (error) {
        console.error('Error viewing history:', error);
        alert('เกิดข้อผิดพลาดในการดูประวัติ');
    }
};

/**
 * เปิด modal เพิ่ม tag จากหน้าสร้างนัดหมาย
 */
window.showAddTagModalFromAppointment = function() {
    const customerId = document.getElementById('appointmentCustomerId').value;
    if (!customerId) {
        alert('ไม่พบ ID ลูกค้า');
        return;
    }
    // เรียกใช้ฟังก์ชันจาก tags.js
    if (typeof showAddTagModal === 'function') {
        showAddTagModal(parseInt(customerId));
    } else {
        alert('ไม่พบฟังก์ชันจัดการ Tags');
    }
};

window.viewAllCallLogs = function(customerId) {
    console.log('viewAllCallLogs function called with customer ID:', customerId);
    
    try {
        // Show all call logs
        window.location.href = `customers.php?action=call_logs&id=${customerId}`;
    } catch (error) {
        console.error('Error viewing call logs:', error);
        alert('เกิดข้อผิดพลาดในการดูประวัติการโทร');
    }
};

window.viewAllOrders = function(customerId) {
    console.log('viewAllOrders function called with customer ID:', customerId);
    
    try {
        // Show all orders
        window.location.href = `customers.php?action=orders&id=${customerId}`;
    } catch (error) {
        console.error('Error viewing orders:', error);
        alert('เกิดข้อผิดพลาดในการดูคำสั่งซื้อ');
    }
};

window.viewOrder = function(orderId) {
    console.log('viewOrder function called with order ID:', orderId);
    
    try {
        window.location.href = `orders.php?action=show&id=${orderId}`;
    } catch (error) {
        console.error('Error viewing order:', error);
        alert('เกิดข้อผิดพลาดในการดูคำสั่งซื้อ');
    }
};

// Submit appointment function
window.submitAppointment = function() {
    console.log('submitAppointment function called');
    
    try {
        const customerId = document.getElementById('appointmentCustomerId').value;
        const appointmentDate = document.getElementById('appointmentDate').value;
        const appointmentType = document.getElementById('appointmentType').value;
        const notes = document.getElementById('appointmentNotes').value;
        
        if (!appointmentDate || !appointmentType) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }
        
        const data = {
            customer_id: parseInt(customerId),
            appointment_date: appointmentDate,
            appointment_type: appointmentType,
            notes: notes
        };
        
        console.log('Submitting appointment data:', data);
        
        fetch('api/appointments.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('API response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);
            if (data.success) {
                alert('บันทึกนัดหมายสำเร็จ');
                const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentModal'));
                if (modal) {
                    modal.hide();
                }
                // Reload page to show updated data
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'));
            }
        })
        .catch(error => {
            console.error('Error submitting appointment:', error);
            alert('เกิดข้อผิดพลาดในการบันทึกนัดหมาย: ' + error.message);
        });
        
    } catch (error) {
        console.error('Error in submitAppointment:', error);
        alert('เกิดข้อผิดพลาดในการบันทึกนัดหมาย');
    }
};

// Submit call log function
window.submitCallLog = function() {
    console.log('submitCallLog function called');
    
    try {
        const customerId = document.getElementById('callCustomerId').value;
        const callType = document.getElementById('callType')?.value || 'outbound'; // default to outbound
        const callStatus = document.getElementById('callStatus').value;
        const callResult = document.getElementById('callResult').value;
        const duration = document.getElementById('callDuration').value;
        const notes = document.getElementById('callNotes').value;
        const nextAction = document.getElementById('nextAction')?.value || ''; // optional
        const nextFollowup = document.getElementById('nextFollowup')?.value || null;
        
        if (!callStatus) {
            alert('กรุณาเลือกสถานะการโทร');
            return;
        }
        
        const data = {
            customer_id: parseInt(customerId),
            call_type: callType,
            call_status: callStatus,
            call_result: callResult || null,
            duration_minutes: parseInt(duration) || 0,
            notes: notes,
            next_action: nextAction,
            next_followup_at: nextFollowup || null
        };
        
        console.log('Submitting call log data:', data);
        
        fetch('api/calls.php?action=log_call', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('API response status:', response.status);
            return response.json();
        })
        .then(async data => {
            console.log('API response data:', data);
            if (data.success) {
                // บันทึก Tags ที่เพิ่มใน Preview (ถ้ามี)
                let tagsMessage = '';
                if (typeof saveCallLogTags === 'function') {
                    const tagsSuccess = await saveCallLogTags(customerId);
                    if (!tagsSuccess) {
                        tagsMessage = ' แต่มี Tags บางตัวที่บันทึกไม่สำเร็จ';
                    }
                }
                
                alert('บันทึกการโทรสำเร็จ' + tagsMessage);
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('logCallModal'));
                if (modal) {
                    modal.hide();
                }
                
                // อัพเดทข้อมูลทันทีแทนการ reload หน้า
                await refreshCustomerDetailData(customerId);
                
            } else {
                alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'));
            }
        })
        .catch(error => {
            console.error('Error submitting call log:', error);
            alert('เกิดข้อผิดพลาดในการบันทึกการโทร: ' + error.message);
        });
    } catch (error) {
        console.error('Error in submitCallLog:', error);
        alert('เกิดข้อผิดพลาดในการบันทึกการโทร');
    }
};

// Utility functions
window.refreshCustomerData = function() {
    console.log('Refreshing customer data...');
    location.reload();
};

// ฟังก์ชันสำหรับรีเฟรชข้อมูลลูกค้าแบบ real-time (ไม่ reload หน้า)
async function refreshCustomerDetailData(customerId) {
    try {
        console.log('Refreshing customer detail data for ID:', customerId);
        
        // รีเฟรช call logs
        await refreshCallLogs(customerId);
        
        // รีเฟรช appointments  
        await refreshAppointments(customerId);
        
        // รีเฟรช customer tags ใน sidebar/header (ถ้ามี)
        await refreshCustomerTags(customerId);
        
        console.log('Customer detail data refreshed successfully');
        
    } catch (error) {
        console.error('Error refreshing customer detail data:', error);
    }
}

// รีเฟรช call logs ใน tab
async function refreshCallLogs(customerId) {
    try {
        const response = await fetch(`api/calls.php?customer_id=${customerId}`);
        if (!response.ok) return;
        
        const data = await response.json();
        if (data.success && data.call_logs) {
            // อัพเดท call logs table ถ้ามี
            const callLogsContainer = document.querySelector('#call-logs-content, .call-logs-container');
            if (callLogsContainer) {
                // สร้าง HTML สำหรับ call logs ใหม่
                let callLogsHtml = '';
                data.call_logs.forEach(log => {
                    callLogsHtml += `
                        <div class="call-log-item p-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">${log.call_status}</span>
                                <small class="text-muted">${formatDateTime(log.created_at)}</small>
                            </div>
                            ${log.call_result ? `<div><strong>ผล:</strong> ${log.call_result}</div>` : ''}
                            ${log.notes ? `<div><strong>หมายเหตุ:</strong> ${log.notes}</div>` : ''}
                            ${log.next_followup_at ? `<div><strong>ติดตาม:</strong> ${formatDateTime(log.next_followup_at)}</div>` : ''}
                        </div>
                    `;
                });
                callLogsContainer.innerHTML = callLogsHtml;
            }
        }
    } catch (error) {
        console.error('Error refreshing call logs:', error);
    }
}

// รีเฟรช appointments ใน tab  
async function refreshAppointments(customerId) {
    try {
        const response = await fetch(`api/appointments.php?customer_id=${customerId}`);
        if (!response.ok) return;
        
        const data = await response.json();
        if (data.success && data.appointments) {
            // อัพเดท appointments table ถ้ามี
            const appointmentsContainer = document.querySelector('#appointments-content, .appointments-container');
            if (appointmentsContainer) {
                // สร้าง HTML สำหรับ appointments ใหม่
                let appointmentsHtml = '';
                data.appointments.forEach(apt => {
                    const statusBadge = getAppointmentStatusBadge(apt.status);
                    appointmentsHtml += `
                        <div class="appointment-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">${formatDateTime(apt.appointment_date)}</div>
                                    ${apt.notes ? `<div class="text-muted">${apt.notes}</div>` : ''}
                                </div>
                                <div class="text-end">
                                    ${statusBadge}
                                    ${apt.status !== 'completed' ? `
                                        <button class="btn btn-sm btn-success ms-2" onclick="updateAppointmentStatus(${apt.appointment_id}, 'completed')">
                                            <i class="fas fa-check"></i> เสร็จ
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                appointmentsContainer.innerHTML = appointmentsHtml;
            }
        }
    } catch (error) {
        console.error('Error refreshing appointments:', error);
    }
}

// รีเฟรช customer tags
async function refreshCustomerTags(customerId) {
    try {
        const response = await fetch(`api/customers.php?action=get_customer&id=${customerId}`);
        if (!response.ok) return;
        
        const data = await response.json();
        if (data.success && data.customer) {
            // อัพเดท tags display ใน customer info section
            const tagsContainer = document.querySelector('.customer-tags, #customer-tags');
            if (tagsContainer && data.customer.tags) {
                let tagsHtml = '';
                data.customer.tags.forEach(tag => {
                    tagsHtml += `<span class="badge me-1" style="background-color: ${tag.tag_color}; color: ${getTextColor(tag.tag_color)};">${tag.tag_name}</span>`;
                });
                tagsContainer.innerHTML = tagsHtml || '<small class="text-muted">ไม่มี tag</small>';
            }
        }
    } catch (error) {
        console.error('Error refreshing customer tags:', error);
    }
}

// Helper functions
function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('th-TH', {
        year: 'numeric',
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getAppointmentStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning text-dark">รอดำเนินการ</span>',
        'confirmed': '<span class="badge bg-info">ยืนยันแล้ว</span>',
        'completed': '<span class="badge bg-success">เสร็จสิ้น</span>',
        'cancelled': '<span class="badge bg-danger">ยกเลิก</span>'
    };
    return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function getTextColor(backgroundColor) {
    if (!backgroundColor) return '#000000';
    const hex = backgroundColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16); 
    const b = parseInt(hex.substr(4, 2), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 128 ? '#000000' : '#ffffff';
}

window.showError = function(message) {
    console.error('Error:', message);
    alert('เกิดข้อผิดพลาด: ' + message);
};

window.showSuccess = function(message) {
    console.log('Success:', message);
    alert(message);
};

// Debug function to check if functions are loaded
console.log('Customer detail page functions loaded successfully');

// Auto-check functions on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customer detail page DOM loaded');
    
    // Check if all required functions are available
    const requiredFunctions = ['logCall', 'createAppointment', 'createOrder', 'submitCallLog', 'submitAppointment'];
    const missingFunctions = [];
    
    requiredFunctions.forEach(funcName => {
        if (typeof window[funcName] !== 'function') {
            missingFunctions.push(funcName);
        }
    });
    
    if (missingFunctions.length > 0) {
        console.error('Missing functions:', missingFunctions);
    } else {
        console.log('All required functions are loaded successfully');
    }
    
    // Load appointments if on customer detail page
    const appointmentsList = document.getElementById('appointmentsList');
    if (appointmentsList) {
        // Don't load immediately, wait for tab click
        console.log('Appointments list found, will load when tab is clicked');
    }
    
    // Add event listener for add appointment button
    const addAppointmentBtn = document.getElementById('addAppointmentBtn');
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function() {
            const customerId = this.getAttribute('data-customer-id');
            createAppointment(customerId);
        });
    }
    
    // Add event listener for appointments tab
    const appointmentsTab = document.getElementById('appointments-tab');
    console.log('appointmentsTab element:', appointmentsTab);
    if (appointmentsTab) {
        appointmentsTab.addEventListener('click', function() {
            console.log('Appointments tab clicked, loading appointments...');
            // Add a small delay to ensure the tab is fully shown
            setTimeout(loadAppointments, 100);
        });
        console.log('Event listener added to appointments tab');
        
        // Also listen for tab shown event (Bootstrap 5)
        appointmentsTab.addEventListener('shown.bs.tab', function() {
            console.log('Appointments tab shown, loading appointments...');
            loadAppointments();
        });
        
        // Listen for tab shown event on the tab content
        const appointmentsTabContent = document.getElementById('appointments');
        if (appointmentsTabContent) {
            appointmentsTabContent.addEventListener('shown.bs.tab', function() {
                console.log('Appointments tab content shown, loading appointments...');
                loadAppointments();
            });
        }
    } else {
        console.error('appointments-tab element not found');
    }
    
    // Add global error handler
    window.addEventListener('error', function(event) {
        console.error('Global error:', event.error);
    });
    
    // Add unhandled promise rejection handler
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
    });
    
    // Respect ?tab= to activate the requested tab (call-history | appointments | orders)
    const activeTab = document.querySelector('#historyTabs .nav-link.active');
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');

    if (requestedTab) {
        const btn = document.querySelector(`[data-bs-target="#${requestedTab}"]`);
        if (btn) {
            const t = new bootstrap.Tab(btn);
            t.show();
        }
    }

    // Load appointments data lazily
    if ((activeTab && activeTab.id === 'appointments-tab') || requestedTab === 'appointments') {
        loadAppointments();
    } else {
        setTimeout(loadAppointments, 500);
    }
});

// Load appointments for customer
function loadAppointments() {
    console.log('loadAppointments function called');
    
    const appointmentsList = document.getElementById('appointmentsList');
    console.log('appointmentsList element:', appointmentsList);
    
    if (!appointmentsList) {
        console.error('appointmentsList element not found');
        return;
    }
    
    // Check if appointments are already loaded
    if (appointmentsList.dataset.loaded === 'true') {
        console.log('Appointments already loaded, skipping...');
        return;
    }
    
    // Get customer ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('id');
    console.log('Customer ID from URL:', customerId);
    
    if (!customerId) {
        console.error('No customer ID found in URL');
        appointmentsList.innerHTML = '<p class="text-muted text-center mb-0">ไม่พบข้อมูลลูกค้า</p>';
        return;
    }
    
    const apiUrl = `api/appointments.php?action=get_by_customer&customer_id=${customerId}&limit=5`;
    console.log('Calling API:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('API response status:', response.status);
            console.log('API response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);
            if (data.success && data.data.length > 0) {
                console.log('Displaying appointments:', data.data);
                displayAppointments(data.data);
                appointmentsList.dataset.loaded = 'true';
            } else {
                console.log('No appointments found or API error');
                appointmentsList.innerHTML = '<p class="text-muted text-center mb-0">ไม่มีรายการนัดหมาย</p>';
                appointmentsList.dataset.loaded = 'true';
            }
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            appointmentsList.innerHTML = '<p class="text-danger text-center mb-0">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
            appointmentsList.dataset.loaded = 'true';
        });
}

// Display appointments in the list
function displayAppointments(appointments) {
    console.log('displayAppointments function called with:', appointments);
    
    const appointmentsList = document.getElementById('appointmentsList');
    console.log('appointmentsList element in displayAppointments:', appointmentsList);
    
    if (!appointmentsList) {
        console.error('appointmentsList element not found in displayAppointments');
        return;
    }
    
    // เพิ่มส่วนแสดง next_followup_at ของลูกค้า
    let headerHtml = '';
    if (window.customerNextFollowup) {
        const followupDate = new Date(window.customerNextFollowup);
        const formattedFollowup = followupDate.toLocaleDateString('th-TH', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        headerHtml = `<div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>วันที่ติดตามถัดไป:</strong> ${formattedFollowup}
            <small class="ms-2 text-muted">(Sync จากการบันทึกการโทร)</small>
        </div>`;
    }
    
    let html = headerHtml + '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr>';
    html += '<th style="font-size: 14px;">วันที่นัดหมาย</th>';
    html += '<th style="font-size: 14px;">ประเภท</th>';
    html += '<th style="font-size: 14px;">สถานะ</th>';
    html += '<th style="font-size: 14px;">แหล่งที่มา</th>';
    html += '<th style="font-size: 14px;">จัดการ</th>';
    html += '</tr></thead><tbody>';
    
    appointments.forEach(appointment => {
        const appointmentDate = new Date(appointment.appointment_date);
        const formattedDate = appointmentDate.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const typeText = {
            'call': 'โทรศัพท์',
            'meeting': 'ประชุม',
            'presentation': 'นำเสนอ',
            'followup': 'ติดตาม',
            'follow_up_call': 'ติดตามการโทร',
            'other': 'อื่นๆ'
        }[appointment.appointment_type] || appointment.appointment_type;
        
        const statusText = {
            'scheduled': 'นัดแล้ว',
            'confirmed': 'ยืนยันแล้ว',
            'completed': 'เสร็จสิ้น',
            'cancelled': 'ยกเลิก',
            'no_show': 'ไม่มา'
        }[appointment.appointment_status] || appointment.appointment_status;
        
        const statusClass = {
            'scheduled': 'warning',
            'confirmed': 'info',
            'completed': 'success',
            'cancelled': 'danger',
            'no_show': 'secondary'
        }[appointment.appointment_status] || 'secondary';
        
        // แหล่งที่มา - ตรวจสอบว่ามาจากการบันทึกการโทรหรือไม่
        let sourceInfo = '';
        if (appointment.call_log_id || appointment.appointment_type === 'follow_up_call') {
            sourceInfo = '<span class="badge bg-info">จากการโทร</span>';
        } else {
            sourceInfo = '<span class="badge bg-secondary">สร้างตรง</span>';
        }
        
        html += '<tr>';
        html += `<td style="font-size: 14px;">${formattedDate}</td>`;
        html += `<td style="font-size: 14px;">${typeText}</td>`;
        html += `<td><span class="badge bg-${statusClass}" style="font-size: 12px;">${statusText}</span></td>`;
        html += `<td style="font-size: 14px;">${sourceInfo}</td>`;
        html += `<td style="font-size: 14px;">`;
        html += `<button class="btn btn-sm btn-outline-primary me-1" onclick="viewAppointment(${appointment.appointment_id})">ดู</button>`;
        if (appointment.appointment_status === 'scheduled' || appointment.appointment_status === 'confirmed') {
            html += `<button class="btn btn-sm btn-outline-success me-1" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'completed')">เสร็จ</button>`;
        } else if (appointment.appointment_status === 'completed') {
            // แสดงไอคอนติ๊กถูกสำหรับ appointment ที่เสร็จแล้ว
            html += `<button class="btn btn-sm btn-outline-success me-1" disabled title="เสร็จสิ้นแล้ว">
                        <i class="fas fa-check-circle text-success"></i>
                     </button>`;
        }
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    appointmentsList.innerHTML = html;
}

// View appointment details
function viewAppointment(appointmentId) {
    fetch(`api/appointments.php?action=get_by_id&id=${appointmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAppointmentModal(data.data);
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error viewing appointment:', error);
            alert('เกิดข้อผิดพลาดในการดูรายละเอียดนัดหมาย');
        });
}

// Show appointment details modal
function showAppointmentModal(appointment) {
    const appointmentDate = new Date(appointment.appointment_date);
    const formattedDate = appointmentDate.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const typeText = {
        'call': 'โทรศัพท์',
        'meeting': 'ประชุม',
        'presentation': 'นำเสนอ',
        'followup': 'ติดตาม',
        'other': 'อื่นๆ'
    }[appointment.appointment_type] || appointment.appointment_type;
    
    const statusText = {
        'scheduled': 'นัดแล้ว',
        'confirmed': 'ยืนยันแล้ว',
        'completed': 'เสร็จสิ้น',
        'cancelled': 'ยกเลิก',
        'no_show': 'ไม่มา'
    }[appointment.appointment_status] || appointment.appointment_status;
    
    const modal = `
        <div class="modal fade" id="appointmentDetailModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">รายละเอียดนัดหมาย</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>วันที่:</strong> ${formattedDate}</p>
                        <p><strong>ประเภท:</strong> ${typeText}</p>
                        <p><strong>สถานะ:</strong> ${statusText}</p>
                        ${appointment.title ? `<p><strong>หัวข้อ:</strong> ${appointment.title}</p>` : ''}
                        ${appointment.description ? `<p><strong>รายละเอียด:</strong> ${appointment.description}</p>` : ''}
                        ${appointment.notes ? `<p><strong>หมายเหตุ:</strong> ${appointment.notes}</p>` : ''}
                        <p><strong>สร้างโดย:</strong> ${appointment.user_name || 'ไม่ระบุ'}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('appointmentDetailModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modal);
    
    // Show modal
    const modalInstance = new bootstrap.Modal(document.getElementById('appointmentDetailModal'));
    modalInstance.show();
}

// Update appointment status
function updateAppointmentStatus(appointmentId, status) {
    if (!confirm('คุณต้องการอัปเดตสถานะนัดหมายนี้หรือไม่?')) {
        return;
    }
    
    // หาปุ่มที่กดและแสดง loading state
    const button = event.target;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch('api/appointments.php?action=update_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            appointment_id: appointmentId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // อัปเดตทันทีในตาราง โดยไม่ต้องโหลดใหม่
            updateAppointmentInTable(appointmentId, status);
            
            // แสดงข้อความสำเร็จแบบ toast
            showSuccessToast('อัปเดตสถานะสำเร็จ');
        } else {
            // คืนสถานะปุ่มเดิม
            button.innerHTML = originalHTML;
            button.disabled = false;
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error updating appointment status:', error);
        // คืนสถานะปุ่มเดิม
        button.innerHTML = originalHTML;
        button.disabled = false;
        alert('เกิดข้อผิดพลาดในการอัปเดตสถานะ');
    });
}

// ฟังก์ชันอัปเดตในตารางทันที
function updateAppointmentInTable(appointmentId, status) {
    // หา row ที่มี appointment นี้
    const appointmentTable = document.querySelector('#appointments-tab table tbody');
    if (!appointmentTable) return;
    
    const rows = appointmentTable.querySelectorAll('tr');
    rows.forEach(row => {
        const buttons = row.querySelectorAll('button');
        const viewButton = Array.from(buttons).find(btn => 
            btn.onclick && btn.onclick.toString().includes(`viewAppointment(${appointmentId})`)
        );
        
        if (viewButton) {
            // พบ row ที่ต้องอัปเดต
            const statusCell = row.cells[2]; // คอลัมน์สถานะ
            const actionCell = row.cells[4]; // คอลัมน์การดำเนินการ
            
            if (status === 'completed') {
                // อัปเดตสถานะเป็นเสร็จสิ้น
                statusCell.innerHTML = '<span class="badge bg-success" style="font-size: 12px;">เสร็จสิ้น</span>';
                
                // เปลี่ยนปุ่ม "เสร็จ" เป็นไอคอนติ๊กถูก
                const completedButton = Array.from(actionCell.querySelectorAll('button')).find(btn => 
                    btn.onclick && btn.onclick.toString().includes(`updateAppointmentStatus(${appointmentId}, 'completed')`)
                );
                
                if (completedButton) {
                    completedButton.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                    completedButton.className = 'btn btn-sm btn-outline-success me-1';
                    completedButton.disabled = true;
                    completedButton.title = 'เสร็จสิ้นแล้ว';
                    completedButton.onclick = null;
                }
                
                // เพิ่ม animation effect
                row.style.transition = 'background-color 0.3s ease';
                row.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 1000);
            }
        }
    });
}

// ฟังก์ชันแสดง Toast แบบง่าย
function showSuccessToast(message) {
    // สร้าง toast element
    const toast = document.createElement('div');
    toast.className = 'position-fixed';
    toast.style.cssText = `
        top: 20px;
        right: 20px;
        background: #d4edda;
        color: #155724;
        padding: 12px 20px;
        border-radius: 5px;
        border: 1px solid #c3e6cb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 9999;
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(-20px);
    `;
    toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}`;
    
    document.body.appendChild(toast);
    
    // แสดง toast
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 100);
    
    // ซ่อน toast หลัง 3 วินาที
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
} 