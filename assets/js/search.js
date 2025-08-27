/**
 * Search Page JavaScript
 * JavaScript สำหรับหน้าค้นหาลูกค้า
 */

// Initialize when page loads
let searchManager;
document.addEventListener('DOMContentLoaded', function() {
    searchManager = new SearchManager();
    // Global function for onclick events
    window.searchManager = searchManager;
});

class SearchManager {
    constructor() {
        this.initializeEventListeners();
        this.checkForTestSearch();
    }

    initializeEventListeners() {
        const searchForm = document.getElementById('searchForm');
        
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
    }

    checkForTestSearch() {
        const urlParams = new URLSearchParams(window.location.search);
        const testTerm = urlParams.get('test');
        if (testTerm) {
            console.log('Auto-searching for:', testTerm);
            document.getElementById('searchTerm').value = testTerm;
            setTimeout(() => {
                this.performSearch();
            }, 500);
        }
    }

    async performSearch() {
        const searchTerm = document.getElementById('searchTerm').value.trim();
        console.log('Search term:', searchTerm);
        
        if (!searchTerm) {
            alert('กรุณาใส่คำที่ต้องการค้นหา');
            return;
        }
        
        // Show loading
        this.showLoading();
        this.hideResults();
        
        try {
            const response = await fetch(`search.php?action=search&term=${encodeURIComponent(searchTerm)}`);
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            this.hideLoading();
            
            if (data.success && data.customers && data.customers.length > 0) {
                this.displaySearchResults(data.customers);
            } else {
                this.showNoResults();
            }
        } catch (error) {
            console.error('Search error:', error);
            this.hideLoading();
            alert('เกิดข้อผิดพลาด: ' + error.message);
        }
    }

    showLoading() {
        const loadingCard = document.getElementById('loadingCard');
        loadingCard.classList.add('show');
        loadingCard.style.display = 'block';
        console.log('Loading card shown');
    }
    
    hideLoading() {
        const loadingCard = document.getElementById('loadingCard');
        loadingCard.classList.remove('show');
        loadingCard.style.display = 'none';
        console.log('Loading card hidden');
    }
    
    showNoResults() {
        const noResultsCard = document.getElementById('noResultsCard');
        noResultsCard.classList.add('show');
        noResultsCard.style.display = 'block';
        console.log('No results card shown');
    }
    
    hideResults() {
        const noResultsCard = document.getElementById('noResultsCard');
        const customerContainer = document.getElementById('customerContainer');
        
        noResultsCard.classList.remove('show');
        noResultsCard.style.display = 'none';
        
        customerContainer.classList.remove('show');
        customerContainer.style.display = 'none';
        
        console.log('All result cards hidden');
    }

    displaySearchResults(customers) {
        console.log('Displaying results for customers:', customers);
        
        if (customers.length === 1) {
            // Single customer - show details immediately
            this.displayCustomerDetails(customers[0]);
        } else {
            // Multiple customers - show selection list
            this.displayCustomerList(customers);
        }
    }

    displayCustomerList(customers) {
        const customerDetails = document.getElementById('customerDetails');
        
        let html = '<h6 class="mb-3">พบลูกค้าหลายรายการ กรุณาเลือก:</h6>';
        html += '<div class="list-group">';
        
        customers.forEach(customer => {
            html += `
                <button type="button" class="list-group-item list-group-item-action" 
                        onclick="window.searchManager.selectCustomer(${customer.customer_id})">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${customer.full_name}</h6>
                        <small>รหัส: ${customer.customer_code}</small>
                    </div>
                    <p class="mb-1">${customer.phone}</p>
                    <small>ยอดซื้อ: ${parseInt(customer.total_purchase_amount || 0).toLocaleString()} บาท</small>
                </button>
            `;
        });
        
        html += '</div>';
        customerDetails.innerHTML = html;
        document.getElementById('customerContainer').style.display = 'block';
    }

    displayCustomerDetails(customer) {
        console.log('Displaying customer details:', customer);
        
        // Force show container first
        const customerContainer = document.getElementById('customerContainer');
        customerContainer.classList.add('show');
        customerContainer.style.display = 'block';
        customerContainer.style.opacity = '1';
        customerContainer.style.visibility = 'visible';
        
        console.log('Customer container classes:', customerContainer.classList.toString());
        
        const customerDetails = document.getElementById('customerDetails');
        customerDetails.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>ชื่อ:</strong> ${customer.full_name || 'ไม่ระบุ'}
                    </div>
                    <div class="mb-3">
                        <strong>รหัสลูกค้า:</strong> ${customer.customer_code || 'ไม่ระบุ'}
                    </div>
                    <div class="mb-3">
                        <strong>เบอร์โทร:</strong> ${customer.phone || 'ไม่ระบุ'}
                    </div>
                    <div class="mb-3">
                        <strong>อีเมล:</strong> ${customer.email || 'ไม่ระบุ'}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>เกรดลูกค้า:</strong> 
                        <span class="badge ${this.getGradeBadgeClass(customer.customer_grade)}" style="font-size: 12px;">
                            ${customer.customer_grade || 'D'}
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>ยอดซื้อรวม:</strong> ${parseInt(customer.total_purchase_amount || 0).toLocaleString()} บาท
                    </div>
                    <div class="mb-3">
                        <strong>จำนวนครั้งที่ซื้อ:</strong> ${customer.total_orders || 0} ครั้ง
                    </div>
                    <div class="mb-3">
                        <strong>จำนวนครั้งที่ติดต่อ:</strong> ${customer.total_calls || 0} ครั้ง
                    </div>
                    <div class="mb-3">
                        <strong>วันที่ติดต่อล่าสุด:</strong> ${this.formatDate(customer.last_contact_date) || 'ไม่มีประวัติติดต่อ'}
                    </div>
                </div>
            </div>
        `;
        
        // Force show container again after content update
        customerContainer.classList.add('show');
        customerContainer.style.display = 'block';
        customerContainer.style.opacity = '1';
        customerContainer.style.visibility = 'visible';
        
        console.log('Customer container should be visible now');
        console.log('Final container style:', {
            display: customerContainer.style.display,
            opacity: customerContainer.style.opacity,
            visibility: customerContainer.style.visibility,
            classList: customerContainer.classList.toString()
        });
        
        this.loadOrderHistory(customer.customer_id);
    }

    async selectCustomer(customerId) {
        try {
            const response = await fetch(`search.php?action=customer_details&customer_id=${customerId}`);
            const data = await response.json();
            
            if (data.success && data.customer) {
                this.displayCustomerDetails(data.customer);
            }
        } catch (error) {
            console.error('Error loading customer details:', error);
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกค้า');
        }
    }

    async loadOrderHistory(customerId) {
        console.log('Loading order history for customer:', customerId);
        
        try {
            const url = `search.php?action=customer_details&customer_id=${customerId}`;
            console.log('Fetching URL:', url);
            
            const response = await fetch(url);
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Order history response:', data);
            
            if (data.success && data.customer && data.customer.orders && data.customer.orders.length > 0) {
                console.log(`Found ${data.customer.orders.length} orders`);
                this.displayOrderHistory(data.customer.orders);
            } else {
                console.log('No orders found:', data.message);
                
                document.getElementById('orderHistory').innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-box text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">ไม่มีประวัติการสั่งซื้อ</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading order history:', error);
            document.getElementById('orderHistory').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ไม่สามารถโหลดประวัติการสั่งซื้อได้: ${error.message}
                </div>
            `;
        }
    }

    displayOrderHistory(orders) {
        console.log('Displaying order history with', orders.length, 'orders');
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสออเดอร์</th>
                            <th>เลขที่ออเดอร์</th>
                            <th>วันที่</th>
                            <th>ผู้ขาย</th>
                            <th>ยอดรวม</th>
                            <th>สถานะการชำระ</th>
                            <th>สถานะการส่ง</th>
                            <th>รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        orders.forEach(order => {
            html += `
                <tr>
                    <td><strong>${order.order_id}</strong></td>
                    <td><small class="text-muted">${order.order_number || 'ไม่ระบุ'}</small></td>
                    <td>${this.formatDate(order.order_date)}</td>
                    <td>${order.created_by_name || 'ไม่ระบุ'}</td>
                    <td><strong>${parseInt(order.total_amount || 0).toLocaleString()} บาท</strong></td>
                    <td>
                        <span class="badge ${this.getPaymentStatusClass(order.payment_status)}" style="font-size: 10px;">
                            ${this.getPaymentStatusText(order.payment_status)}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${this.getDeliveryStatusClass(order.delivery_status)}" style="font-size: 10px;">
                            ${this.getDeliveryStatusText(order.delivery_status)}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="window.searchManager.viewOrderDetails(${order.order_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        const orderHistoryElement = document.getElementById('orderHistory');
        orderHistoryElement.innerHTML = html;
        
        console.log('Order history HTML updated');
        
        // Force visibility of parent container
        const customerContainer = document.getElementById('customerContainer');
        customerContainer.style.display = 'block';
        customerContainer.style.opacity = '1';
        customerContainer.style.visibility = 'visible';
    }

    async viewOrderDetails(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        const content = document.getElementById('orderDetailsContent');
        
        // Show loading
        content.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">กำลังโหลด...</span>
                </div>
            </div>
        `;
        
        modal.show();
        
        try {
            const response = await fetch(`search.php?action=order_details&order_id=${orderId}`);
            const data = await response.json();
            
            if (data.success && data.order) {
                this.displayOrderDetailsModal(data.order);
            } else {
                content.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ไม่สามารถโหลดรายละเอียดออเดอร์ได้
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading order details:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    เกิดข้อผิดพลาดในการโหลดข้อมูล
                </div>
            `;
        }
    }

    displayOrderDetailsModal(order) {
        const content = document.getElementById('orderDetailsContent');
        
        let html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>รหัสออเดอร์:</strong> ${order.order_id}<br>
                    <strong>วันที่สั่งซื้อ:</strong> ${this.formatDate(order.order_date)}<br>
                    <strong>ผู้ขาย:</strong> ${order.created_by_name || 'ไม่ระบุ'}
                </div>
                <div class="col-md-6">
                    <strong>สถานะการชำระ:</strong> 
                    <span class="badge ${this.getPaymentStatusClass(order.payment_status)}">
                        ${this.getPaymentStatusText(order.payment_status)}
                    </span><br>
                    <strong>สถานะการส่ง:</strong> 
                    <span class="badge ${this.getDeliveryStatusClass(order.delivery_status)}">
                        ${this.getDeliveryStatusText(order.delivery_status)}
                    </span><br>
                    <strong>ยอดรวม:</strong> <span class="text-success fw-bold">${parseInt(order.total_amount || 0).toLocaleString()} บาท</span>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
    }

    // Utility functions
    getGradeBadgeClass(grade) {
        const classes = {
            'A+': 'bg-danger',
            'A': 'bg-warning',
            'B': 'bg-info',
            'C': 'bg-success',
            'D': 'bg-secondary'
        };
        return classes[grade] || 'bg-secondary';
    }

    getPaymentStatusClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'paid': 'bg-success',
            'partial': 'bg-info',
            'cancelled': 'bg-danger',
            'returned': 'bg-secondary'
        };
        return classes[status] || 'bg-secondary';
    }

    getPaymentStatusText(status) {
        const texts = {
            'pending': 'รอชำระ',
            'paid': 'ชำระแล้ว',
            'partial': 'ชำระบางส่วน',
            'cancelled': 'ยกเลิก',
            'returned': 'คืนเงิน'
        };
        return texts[status] || status;
    }

    getDeliveryStatusClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'confirmed': 'bg-info',
            'shipped': 'bg-primary',
            'delivered': 'bg-success',
            'cancelled': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }

    getDeliveryStatusText(status) {
        const texts = {
            'pending': 'รอจัดส่ง',
            'confirmed': 'ยืนยันแล้ว',
            'shipped': 'จัดส่งแล้ว',
            'delivered': 'จัดส่งสำเร็จ',
            'cancelled': 'ยกเลิกการส่ง'
        };
        return texts[status] || status;
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }
}