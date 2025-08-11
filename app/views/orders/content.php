<?php
/**
 * CRM SalesTracker - Orders List Content
 * เนื้อหาหน้ารายการคำสั่งซื้อ (เฉพาะ content)
 */

$orders = $orderList ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!-- Orders Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">จัดการคำสั่งซื้อ</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportOrders()">
                <i class="fas fa-download me-1"></i>ส่งออก
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">ค้นหา</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="หมายเลขคำสั่งซื้อ, ชื่อลูกค้า">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">ทั้งหมด</option>
                            <option value="pending">รอดำเนินการ</option>
                            <option value="confirmed">ยืนยันแล้ว</option>
                            <option value="shipped">จัดส่งแล้ว</option>
                            <option value="delivered">ส่งมอบแล้ว</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="payment_status" class="form-label">การชำระเงิน</label>
                        <select class="form-select" id="payment_status" name="payment_status">
                            <option value="">ทั้งหมด</option>
                            <option value="pending">รอชำระ</option>
                            <option value="paid">ชำระแล้ว</option>
                            <option value="partial">ชำระบางส่วน</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="date_from" name="date_from">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="date_to" name="date_to">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toggle for Paid Orders Only -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="paidOnlyToggle">
                    <label class="form-check-label" for="paidOnlyToggle">
                        <i class="fas fa-money-check-alt me-1"></i>
                        แสดงเฉพาะคำสั่งซื้อที่ชำระเงินแล้ว
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>รายการคำสั่งซื้อ
                </h5>
                <span class="badge bg-primary" id="orderCount">0 รายการ</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>หมายเลขคำสั่งซื้อ</th>
                                <th>ลูกค้า</th>
                                <th>วันที่สั่งซื้อ</th>
                                <th>ยอดรวม</th>
                                <th>สถานะ</th>
                                <th>การชำระเงิน</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Orders will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Loading indicator -->
                <div id="loadingIndicator" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                </div>
                
                <!-- No data message -->
                <div id="noDataMessage" class="text-center py-4" style="display: none;">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">ไม่พบข้อมูลคำสั่งซื้อ</p>
                </div>
                
                <!-- Load more button -->
                <div class="text-center py-3" id="loadMoreContainer" style="display: none;">
                    <button class="btn btn-outline-primary" id="loadMoreBtn">
                        <i class="fas fa-plus me-1"></i>แสดงเพิ่มอีก 10 รายการ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal removed as delete functionality is moved to edit page -->

<script>
// Orders JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let isLoading = false;
    let hasMoreData = true;
    let currentFilters = {};

    // Initialize
    loadOrders();

    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        hasMoreData = true;
        loadOrders(true);
    });

    // Paid only toggle
    document.getElementById('paidOnlyToggle').addEventListener('change', function() {
        currentPage = 1;
        hasMoreData = true;
        loadOrders(true);
    });

    // Load more button
    document.getElementById('loadMoreBtn').addEventListener('click', function() {
        currentPage++;
        loadOrders(false);
    });

    function loadOrders(reset = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        if (reset) {
            document.getElementById('ordersTableBody').innerHTML = '';
            document.getElementById('loadMoreContainer').style.display = 'none';
        }
        
        document.getElementById('loadingIndicator').style.display = 'block';
        document.getElementById('noDataMessage').style.display = 'none';

        // Collect filter data
        const formData = new FormData(document.getElementById('filterForm'));
        const paidOnly = document.getElementById('paidOnlyToggle').checked;
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', 10); // โหลด 10 รายการต่อครั้ง
        params.append('paid_only', paidOnly ? '1' : '0');

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        fetch(`orders.php?action=list&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (reset || currentPage === 1) {
                        document.getElementById('ordersTableBody').innerHTML = '';
                    }
                    
                    if (data.orders.length > 0) {
                        appendOrdersToTable(data.orders);
                        hasMoreData = data.has_more;
                        
                        if (hasMoreData) {
                            document.getElementById('loadMoreContainer').style.display = 'block';
                        } else {
                            document.getElementById('loadMoreContainer').style.display = 'none';
                        }
                    } else if (currentPage === 1) {
                        document.getElementById('noDataMessage').style.display = 'block';
                    }
                    
                    document.getElementById('orderCount').textContent = `${data.total} รายการ`;
                } else {
                    console.error('Error loading orders:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                isLoading = false;
                document.getElementById('loadingIndicator').style.display = 'none';
            });
    }

    function appendOrdersToTable(orders) {
        const tbody = document.getElementById('ordersTableBody');

        orders.forEach(order => {
            const row = createOrderRow(order);
            tbody.appendChild(row);
        });

        // Add event listeners for payment switches
        document.querySelectorAll('.payment-switch').forEach(switchEl => {
            switchEl.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const isPaid = this.checked;
                updatePaymentStatus(orderId, isPaid ? 'paid' : 'pending');
            });
        });
    }

    function createOrderRow(order) {
        const row = document.createElement('tr');
        
        // Status badge colors
        const statusColors = {
            'pending': 'warning',
            'confirmed': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'cancelled': 'danger'
        };

        const paymentColors = {
            'pending': 'warning',
            'paid': 'success',
            'partial': 'info',
            'cancelled': 'danger'
        };

        // Ensure we have valid status values
        const deliveryStatus = order.delivery_status || 'pending';
        const paymentStatus = order.payment_status || 'pending';

        row.innerHTML = `
            <td>
                <a href="orders.php?action=show&id=${order.order_id}" class="text-decoration-none">
                    ${order.order_number || order.orders_number || 'N/A'}
                </a>
            </td>
            <td>${order.customer_name || 'N/A'}</td>
            <td>${new Date(order.created_at).toLocaleDateString('th-TH')}</td>
            <td>฿${parseFloat(order.net_amount || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
            <td>
                <span class="badge bg-${statusColors[deliveryStatus] || 'warning'}">
                    ${getStatusText(deliveryStatus)}
                </span>
            </td>
            <td>
                <span class="badge bg-${paymentColors[paymentStatus] || 'warning'}">
                    ${getPaymentStatusText(paymentStatus)}
                </span>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <!-- Action Buttons -->
                    <div class="btn-group btn-group-sm">
                        <a href="orders.php?action=show&id=${order.order_id}" class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="orders.php?action=edit&id=${order.order_id}" class="btn btn-outline-warning btn-sm" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>

                    <!-- Payment Status Switch -->
                    <div class="form-check form-switch ms-2">
                        <input class="form-check-input payment-switch" type="checkbox"
                               data-order-id="${order.order_id}"
                               ${paymentStatus === 'paid' ? 'checked' : ''}
                               title="ชำระแล้ว">
                    </div>
                </div>
            </td>
        `;
        
        return row;
    }

    function getStatusText(status) {
        const statusTexts = {
            'pending': 'รอดำเนินการ',
            'confirmed': 'ยืนยันแล้ว',
            'shipped': 'จัดส่งแล้ว',
            'delivered': 'ส่งมอบแล้ว',
            'cancelled': 'ยกเลิก'
        };
        return statusTexts[status] || 'รอดำเนินการ';
    }

    function getPaymentStatusText(status) {
        const paymentTexts = {
            'pending': 'รอชำระ',
            'paid': 'ชำระแล้ว',
            'partial': 'ชำระบางส่วน',
            'cancelled': 'ยกเลิก'
        };
        return paymentTexts[status] || 'รอชำระ';
    }

    // Global functions - Delete functions removed as delete button is removed

    function updatePaymentStatus(orderId, status) {
        fetch(`orders.php?action=update_payment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                payment_status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the payment status badge in the table
                const row = document.querySelector(`[data-order-id="${orderId}"]`).closest('tr');
                const paymentBadge = row.querySelector('td:nth-child(6) .badge');

                if (status === 'paid') {
                    paymentBadge.className = 'badge bg-success';
                    paymentBadge.textContent = 'ชำระแล้ว';
                } else {
                    paymentBadge.className = 'badge bg-warning';
                    paymentBadge.textContent = 'รอชำระ';
                }

                console.log('Payment status updated successfully');
            } else {
                console.error('Failed to update payment status:', data.message);
                // Revert the switch
                const switchEl = document.querySelector(`[data-order-id="${orderId}"]`);
                switchEl.checked = !switchEl.checked;
                alert('เกิดข้อผิดพลาดในการอัปเดตสถานะการชำระเงิน');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revert the switch
            const switchEl = document.querySelector(`[data-order-id="${orderId}"]`);
            switchEl.checked = !switchEl.checked;
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });
    }

    window.exportOrders = function() {
        const params = new URLSearchParams();
        const formData = new FormData(document.getElementById('filterForm'));
        const paidOnly = document.getElementById('paidOnlyToggle').checked;

        params.append('paid_only', paidOnly ? '1' : '0');

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        window.location.href = `orders.php?action=export&${params.toString()}`;
    };
});
</script>
