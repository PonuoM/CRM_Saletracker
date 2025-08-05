<?php
/**
 * CRM SalesTracker - Create Order View
 * สร้างคำสั่งซื้อใหม่
 */

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$customers = $customerList ?? [];
$products = $productList ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างคำสั่งซื้อใหม่ - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <!-- Include Header Component -->
    <?php include APP_VIEWS . 'components/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar Component -->
            <?php include APP_VIEWS . 'components/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">สร้างคำสั่งซื้อใหม่</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>กลับ
                        </a>
                    </div>
                </div>

                <form id="orderForm" method="POST" action="orders.php?action=store">
                    <div class="row">
                        <!-- Order Details -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">รายละเอียดคำสั่งซื้อ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="customer_id" class="form-label">ลูกค้า *</label>
                                            <select class="form-select" id="customer_id" name="customer_id" required>
                                                <option value="">เลือกลูกค้า</option>
                                                <?php foreach ($customers as $customer): ?>
                                                    <option value="<?php echo $customer['customer_id']; ?>" 
                                                            <?php echo (isset($selectedCustomerId) && $selectedCustomerId == $customer['customer_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($customer['customer_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="order_date" class="form-label">วันที่สั่งซื้อ *</label>
                                            <input type="date" class="form-control" id="order_date" name="order_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="payment_method" class="form-label">วิธีการชำระเงิน *</label>
                                            <select class="form-select" id="payment_method" name="payment_method" required>
                                                <option value="">เลือกวิธีการชำระเงิน</option>
                                                <option value="cash">เงินสด</option>
                                                <option value="transfer">โอนเงิน</option>
                                                <option value="cod">เก็บเงินปลายทาง</option>
                                                <option value="credit">เครดิต</option>
                                                <option value="other">อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_method" class="form-label">วิธีการจัดส่ง *</label>
                                            <select class="form-select" id="delivery_method" name="delivery_method" required>
                                                <option value="">เลือกวิธีการจัดส่ง</option>
                                                <option value="pickup">รับเอง</option>
                                                <option value="delivery">จัดส่ง</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="use_customer_address" name="use_customer_address">
                                            <label class="form-check-label" for="use_customer_address">
                                                ใช้ที่อยู่เดียวกับข้อมูลลูกค้า
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="delivery_address" class="form-label">ที่อยู่จัดส่ง</label>
                                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" 
                                                  placeholder="กรอกที่อยู่จัดส่ง"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">หมายเหตุ</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="หมายเหตุเพิ่มเติม"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Products -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">สินค้า</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="product_search" class="form-label">ค้นหาสินค้า</label>
                                            <div class="product-search position-relative">
                                                <input type="text" class="form-control" id="product_search" 
                                                       placeholder="พิมพ์ชื่อหรือรหัสสินค้า">
                                                <div id="productResults" class="position-absolute w-100 bg-white border rounded shadow-sm" 
                                                     style="display: none; z-index: 1000; max-height: 200px; overflow-y: auto;">
                                                    <!-- Product search results will appear here -->
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="quantity" class="form-label">จำนวน</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                                   value="1" min="1" max="999" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="discount_percentage" class="form-label">ส่วนลด (%)</label>
                                            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                                   value="0" min="0" max="100" step="0.01">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-primary" onclick="addProduct()">
                                                <i class="fas fa-plus me-1"></i>เพิ่มสินค้า
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Order Items Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>สินค้า</th>
                                                    <th>รหัส</th>
                                                    <th class="text-center">จำนวน</th>
                                                    <th class="text-end">ราคาต่อหน่วย</th>
                                                    <th class="text-end">ราคารวม</th>
                                                    <th class="text-center">จัดการ</th>
                                                </tr>
                                            </thead>
                                            <tbody id="orderItemsBody">
                                                <tr id="noItemsRow">
                                                    <td colspan="6" class="text-center py-4">
                                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                        <p class="text-muted mb-0">ยังไม่มีรายการสินค้า</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">สรุปคำสั่งซื้อ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>จำนวนรายการ:</span>
                                            <span id="item_count">0 รายการ</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>ยอดรวมสินค้า:</span>
                                            <span id="subtotal">฿0.00</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>ส่วนลด:</span>
                                            <span id="discount_amount">฿0.00</span>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <strong>ยอดรวมทั้งสิ้น:</strong>
                                            <strong id="net_amount">฿0.00</strong>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                        <i class="fas fa-save me-1"></i>สร้างคำสั่งซื้อ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/orders.js"></script>
    
    <!-- Initialize products data for JavaScript -->
    <script>
        // Pass products data to JavaScript
        window.products = <?php echo json_encode($productList); ?>;
        
        // Initialize OrderManager
        let orderManager;
        document.addEventListener('DOMContentLoaded', function() {
            orderManager = new OrderManager();
        });
    </script>
</body>
</html> 