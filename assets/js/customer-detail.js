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
        // Set customer ID in hidden field
        const customerIdField = document.getElementById('callCustomerId');
        if (customerIdField) {
            customerIdField.value = customerId;
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('logCallModal'));
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
        const callType = document.getElementById('callType').value;
        const callStatus = document.getElementById('callStatus').value;
        const callResult = document.getElementById('callResult').value;
        const duration = document.getElementById('callDuration').value;
        const notes = document.getElementById('callNotes').value;
        const nextAction = document.getElementById('nextAction').value;
        const nextFollowup = document.getElementById('nextFollowup').value;
        
        if (!callStatus) {
            alert('กรุณาเลือกสถานะการโทร');
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
        
        console.log('Submitting call log data:', data);
        
        fetch('api/customers.php?action=log_call', {
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
                alert('บันทึกการโทรสำเร็จ');
                const modal = bootstrap.Modal.getInstance(document.getElementById('logCallModal'));
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
    
    // Load appointments when page loads (if appointments tab is active or requested)
    const activeTab = document.querySelector('#historyTabs .nav-link.active');
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    
    if ((activeTab && activeTab.id === 'appointments-tab') || requestedTab === 'appointments') {
        console.log('Appointments tab is active or requested on page load, loading appointments...');
        loadAppointments();
    } else {
        // Pre-load appointments data even if tab is not active
        console.log('Pre-loading appointments data...');
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
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr>';
    html += '<th style="font-size: 14px;">วันที่</th>';
    html += '<th style="font-size: 14px;">ประเภท</th>';
    html += '<th style="font-size: 14px;">สถานะ</th>';
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
        
        html += '<tr>';
        html += `<td style="font-size: 14px;">${formattedDate}</td>`;
        html += `<td style="font-size: 14px;">${typeText}</td>`;
        html += `<td><span class="badge bg-${statusClass}" style="font-size: 12px;">${statusText}</span></td>`;
        html += `<td style="font-size: 14px;">`;
        html += `<button class="btn btn-sm btn-outline-primary me-1" onclick="viewAppointment(${appointment.appointment_id})">ดู</button>`;
        if (appointment.appointment_status === 'scheduled' || appointment.appointment_status === 'confirmed') {
            html += `<button class="btn btn-sm btn-outline-success me-1" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'completed')">เสร็จ</button>`;
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
            alert('อัปเดตสถานะสำเร็จ');
            loadAppointments(); // Reload the list
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error updating appointment status:', error);
        alert('เกิดข้อผิดพลาดในการอัปเดตสถานะ');
    });
} 