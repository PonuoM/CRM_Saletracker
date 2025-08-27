<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    session_start();
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    require_once 'app/core/Auth.php';
    require_once 'app/services/SearchService.php';
    require_once 'app/controllers/SearchController.php';

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Role-based access control for search
$allowedRoles = ['supervisor', 'telesales', 'admin', 'super_admin'];
if (!in_array($_SESSION['role_name'] ?? '', $allowedRoles)) {
    header("Location: dashboard.php");
    exit;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $controller = new SearchController();
    
    switch ($_GET['action']) {
        case 'search':
            header('Content-Type: application/json');
            echo json_encode($controller->search());
            exit;
        
        case 'customer_details':
            header('Content-Type: application/json');
            echo json_encode($controller->getCustomerDetails());
            exit;
            
        case 'order_details':
            header('Content-Type: application/json');
            echo json_encode($controller->getOrderDetails());
            exit;
    }
}

    // Get user source for display
    $searchService = new SearchService();
    $userSource = $searchService->getCurrentUserSource();
    
} catch (Exception $e) {
    // Handle initialization errors
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<div style='background: #ffebee; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h3 style='color: red;'>❌ Initialization Error</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><a href='debug_search_improved.php'>🔧 Debug Page</a></p>";
    echo "</div></body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาลูกค้า - CRM System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>

<body class="search-page-body">
    <!-- Include header and sidebar -->
    <?php include APP_ROOT . '/app/views/components/header.php'; ?>
    
    <div class="wrapper">
        <?php include APP_ROOT . '/app/views/components/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-search text-primary me-2"></i>
                        ค้นหาลูกค้า
                    </h1>
                    <span class="badge bg-info">
                        <i class="fas fa-building me-1"></i>
                        บริษัท: <?php echo htmlspecialchars($userSource ?? 'Unknown'); ?>
                    </span>
                </div>

                <!-- Search Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form id="searchForm" class="row g-3">
                            <div class="col-md-8">
                                <label for="searchTerm" class="form-label">ค้นหาด้วยชื่อหรือเบอร์โทร</label>
                                <input type="text" class="form-control form-control-lg" id="searchTerm" 
                                       placeholder="กรุณาใส่ชื่อลูกค้าหรือเบอร์โทรศัพท์" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>ค้นหา
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Loading Card -->
                <div id="loadingCard" class="card shadow-sm mb-4" style="display: none;">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">กำลังค้นหา...</span>
                        </div>
                        <h5>กำลังค้นหาข้อมูล...</h5>
                    </div>
                </div>

                <!-- No Results Card -->
                <div id="noResultsCard" class="card shadow-sm mb-4" style="display: none;">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search-minus text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">ไม่พบข้อมูลการขาย</h5>
                        <p class="text-muted">ลองค้นหาด้วยคำอื่นหรือตรวจสอบการสะกดคำ</p>
                    </div>
                </div>

                <!-- Customer Details Container -->
                <div id="customerContainer" style="display: none;">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>ข้อมูลลูกค้า
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="customerDetails"></div>
                        </div>
                    </div>

                    <!-- Order History -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>ประวัติการสั่งซื้อ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="orderHistory">
                                <div class="text-center py-4">
                                    <i class="fas fa-box text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">ไม่มีประวัติการสั่งซื้อ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รายละเอียดคำสั่งซื้อ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">กำลังโหลด...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Search page loaded');
        
        const searchForm = document.getElementById('searchForm');
        let currentCustomerId = null;
        
        // Auto-search for URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const testTerm = urlParams.get('test');
        if (testTerm) {
            console.log('Auto-searching for:', testTerm);
            document.getElementById('searchTerm').value = testTerm;
            setTimeout(() => {
                searchForm.dispatchEvent(new Event('submit'));
            }, 1000);
        }
        
        if (searchForm) {
            searchForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const searchTerm = document.getElementById('searchTerm').value.trim();
                console.log('Search term:', searchTerm);
                
                if (!searchTerm) {
                    alert('กรุณาใส่คำที่ต้องการค้นหา');
                    return;
                }
                
                // Show loading
                showLoading();
                hideResults();
                
                try {
                    const response = await fetch(`search_improved.php?action=search&term=${encodeURIComponent(searchTerm)}`);
                    console.log('Response status:', response.status);
                    
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    hideLoading();
                    
                    if (data.success && data.customers && data.customers.length > 0) {
                        displaySearchResults(data.customers);
                    } else {
                        showNoResults();
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    hideLoading();
                    alert('เกิดข้อผิดพลาด: ' + error.message);
                }
            });
        }
        
        function showLoading() {
            document.getElementById('loadingCard').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loadingCard').style.display = 'none';
        }
        
        function showNoResults() {
            document.getElementById('noResultsCard').style.display = 'block';
        }
        
        function hideResults() {
            document.getElementById('noResultsCard').style.display = 'none';
            document.getElementById('customerContainer').style.display = 'none';
        }
        
        function displaySearchResults(customers) {
            console.log('Displaying results for customers:', customers);
            
            if (customers.length === 1) {
                // Single customer - show details immediately
                displayCustomerDetails(customers[0]);
            } else {
                // Multiple customers - show selection list
                displayCustomerList(customers);
            }
        }
        
        function displayCustomerList(customers) {
            const customerDetails = document.getElementById('customerDetails');
            
            let html = '<h6 class="mb-3">พบลูกค้าหลายรายการ กรุณาเลือก:</h6>';
            html += '<div class="list-group">';
            
            customers.forEach(customer => {
                html += `
                    <button type="button" class="list-group-item list-group-item-action" 
                            onclick="selectCustomer(${customer.customer_id})">
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
        
        function displayCustomerDetails(customer) {
            console.log('Displaying customer details:', customer);
            currentCustomerId = customer.customer_id;
            
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
                            <span class="badge ${getGradeBadgeClass(customer.customer_grade)}" style="font-size: 12px;">
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
                            <strong>วันที่ติดต่อล่าสุด:</strong> ${formatDate(customer.last_contact_date) || 'ไม่มีประวัติติดต่อ'}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('customerContainer').style.display = 'block';
            loadOrderHistory(customer.customer_id);
        }
        
        async function loadOrderHistory(customerId) {
            try {
                const response = await fetch(`search_improved.php?action=customer_details&customer_id=${customerId}`);
                const data = await response.json();
                
                console.log('Order history data:', data);
                
                if (data.success && data.orders && data.orders.length > 0) {
                    displayOrderHistory(data.orders);
                } else {
                    document.getElementById('orderHistory').innerHTML = `
                        <div class="text-center py-4">
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
                        ไม่สามารถโหลดประวัติการสั่งซื้อได้
                    </div>
                `;
            }
        }
        
        function displayOrderHistory(orders) {
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>รหัสออเดอร์</th>
                                <th>วันที่</th>
                                <th>ผู้ขาย</th>
                                <th>ยอดรวม</th>
                                <th>สถานะ</th>
                                <th>รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            orders.forEach(order => {
                html += `
                    <tr>
                        <td><strong>${order.order_id}</strong></td>
                        <td>${formatDate(order.order_date)}</td>
                        <td>${order.seller_name || 'ไม่ระบุ'}</td>
                        <td><strong>${parseInt(order.total_amount || 0).toLocaleString()} บาท</strong></td>
                        <td>
                            <span class="badge ${getOrderStatusClass(order.order_status)}" style="font-size: 11px;">
                                ${getOrderStatusText(order.order_status)}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(${order.order_id})">
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
            
            document.getElementById('orderHistory').innerHTML = html;
        }
        
        // Utility functions
        function getGradeBadgeClass(grade) {
            const classes = {
                'A+': 'bg-danger',
                'A': 'bg-warning',
                'B': 'bg-info',
                'C': 'bg-success',
                'D': 'bg-secondary'
            };
            return classes[grade] || 'bg-secondary';
        }
        
        function getOrderStatusClass(status) {
            const classes = {
                'pending': 'bg-warning',
                'confirmed': 'bg-info',
                'processing': 'bg-primary',
                'shipped': 'bg-success',
                'delivered': 'bg-success',
                'cancelled': 'bg-danger'
            };
            return classes[status] || 'bg-secondary';
        }
        
        function getOrderStatusText(status) {
            const texts = {
                'pending': 'รอดำเนินการ',
                'confirmed': 'ยืนยันแล้ว',
                'processing': 'กำลังดำเนินการ',
                'shipped': 'จัดส่งแล้ว',
                'delivered': 'จัดส่งสำเร็จ',
                'cancelled': 'ยกเลิก'
            };
            return texts[status] || status;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Global functions for onclick events
        window.selectCustomer = async function(customerId) {
            try {
                const response = await fetch(`search_improved.php?action=customer_details&customer_id=${customerId}`);
                const data = await response.json();
                
                if (data.success && data.customer) {
                    displayCustomerDetails(data.customer);
                }
            } catch (error) {
                console.error('Error loading customer details:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกค้า');
            }
        };
        
        window.viewOrderDetails = async function(orderId) {
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
                const response = await fetch(`search_improved.php?action=order_details&order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success && data.order) {
                    displayOrderDetailsModal(data.order);
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
        };
        
        function displayOrderDetailsModal(order) {
            const content = document.getElementById('orderDetailsContent');
            
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>รหัสออเดอร์:</strong> ${order.order_id}<br>
                        <strong>วันที่สั่งซื้อ:</strong> ${formatDate(order.order_date)}<br>
                        <strong>ผู้ขาย:</strong> ${order.seller_name || 'ไม่ระบุ'}
                    </div>
                    <div class="col-md-6">
                        <strong>สถานะ:</strong> 
                        <span class="badge ${getOrderStatusClass(order.order_status)}">
                            ${getOrderStatusText(order.order_status)}
                        </span><br>
                        <strong>ยอดรวม:</strong> <span class="text-success fw-bold">${parseInt(order.total_amount || 0).toLocaleString()} บาท</span>
                    </div>
                </div>
            `;
            
            if (order.items && order.items.length > 0) {
                html += `
                    <h6>รายการสินค้า:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>สินค้า</th>
                                    <th>จำนวน</th>
                                    <th>ราคา/หน่วย</th>
                                    <th>รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                order.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.product_name || 'ไม่ระบุ'}</td>
                            <td>${item.quantity || 0}</td>
                            <td>${parseInt(item.unit_price || 0).toLocaleString()} บาท</td>
                            <td><strong>${parseInt(item.line_total || 0).toLocaleString()} บาท</strong></td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            content.innerHTML = html;
        }
    });
    </script>

    <style>
    .customer-info-item {
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 0.375rem;
        border-left: 3px solid #007bff;
        margin-bottom: 0.5rem;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .search-page-body .main-content {
        margin-left: 250px;
        padding: 20px;
    }
    
    @media (max-width: 768px) {
        .search-page-body .main-content {
            margin-left: 0;
        }
    }
    </style>

</body>
</html>
