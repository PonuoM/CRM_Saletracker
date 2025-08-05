<?php
/**
 * CRM SalesTracker - Edit Order View
 * แก้ไขคำสั่งซื้อ
 */

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$customers = $customerList ?? [];
$products = $productList ?? [];
$order = $order ?? [];
$items = $items ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขคำสั่งซื้อ - CRM SalesTracker</title>
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
                    <h1 class="h2">แก้ไขคำสั่งซื้อ #<?php echo htmlspecialchars($order['order_number'] ?? ''); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>กลับ
                        </a>
                    </div>
                </div>

                <form id="orderForm" method="POST" action="orders.php?action=update&id=<?php echo $order['order_id'] ?? ''; ?>">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id'] ?? ''; ?>">
                    
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
                                                            <?php echo ($order['customer_id'] ?? '') == $customer['customer_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($customer['customer_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="order_date" class="form-label">วันที่สั่งซื้อ *</label>
                                            <input type="date" class="form-control" id="order_date" name="order_date" 
                                                   value="<?php echo $order['order_date'] ?? date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="payment_method" class="form-label">วิธีการชำระเงิน *</label>
                                            <select class="form-select" id="payment_method" name="payment_method" required>
                                                <option value="">เลือกวิธีการชำระเงิน</option>
                                                <option value="cash" <?php echo ($order['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>เงินสด</option>
                                                <option value="transfer" <?php echo ($order['payment_method'] ?? '') === 'transfer' ? 'selected' : ''; ?>>โอนเงิน</option>
                                                <option value="cod" <?php echo ($order['payment_method'] ?? '') === 'cod' ? 'selected' : ''; ?>>เก็บเงินปลายทาง</option>
                                                <option value="credit" <?php echo ($order['payment_method'] ?? '') === 'credit' ? 'selected' : ''; ?>>เครดิต</option>
                                                <option value="other" <?php echo ($order['payment_method'] ?? '') === 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_method" class="form-label">วิธีการจัดส่ง *</label>
                                            <select class="form-select" id="delivery_method" name="delivery_method" required>
                                                <option value="">เลือกวิธีการจัดส่ง</option>
                                                <option value="pickup" <?php echo ($order['delivery_method'] ?? '') === 'pickup' ? 'selected' : ''; ?>>รับเอง</option>
                                                <option value="delivery" <?php echo ($order['delivery_method'] ?? '') === 'delivery' ? 'selected' : ''; ?>>จัดส่ง</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="use_customer_address" name="use_customer_address">
                                            <label class="form-check-label" for="use_customer_address">
                                                ใช้ที่อยู่ของลูกค้า
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="delivery_address" class="form-label">ที่อยู่จัดส่ง</label>
                                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3"><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">หมายเหตุ</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Selection -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">เพิ่มสินค้า</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="product_search" class="form-label">ค้นหาสินค้า</label>
                                            <input type="text" class="form-control" id="product_search" placeholder="พิมพ์ชื่อหรือรหัสสินค้า...">
                                            <div id="product_results" class="list-group mt-2" style="display: none;"></div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="quantity" class="form-label">จำนวน</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="999" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="discount_percentage" class="form-label">ส่วนลด (%)</label>
                                            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" value="0" min="0" max="100" step="0.01">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="addProduct()">
                                        <i class="fas fa-plus me-1"></i>เพิ่มสินค้า
                                    </button>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">รายการสินค้า</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>สินค้า</th>
                                                    <th>จำนวน</th>
                                                    <th>ราคาต่อหน่วย</th>
                                                    <th>ส่วนลด</th>
                                                    <th>รวม</th>
                                                    <th>ดำเนินการ</th>
                                                </tr>
                                            </thead>
                                            <tbody id="orderItemsBody">
                                                <!-- Existing items will be loaded here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">สรุปคำสั่งซื้อ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>จำนวนสินค้า:</span>
                                        <span id="item_count">0 รายการ</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>ราคารวม:</span>
                                        <span id="subtotal">฿0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>ส่วนลด:</span>
                                        <span id="discount_amount">฿0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>ยอดรวม:</strong>
                                        <strong id="net_amount">฿0.00</strong>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100" id="submitBtn">
                                        <i class="fas fa-save me-1"></i>บันทึกการแก้ไข
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
    <script>
        // Pass data to JavaScript
        window.orderItems = <?php echo json_encode($items); ?>;
        window.products = <?php echo json_encode($products); ?>;
        window.orderId = <?php echo json_encode($order['order_id'] ?? null); ?>;
    </script>
    <script src="assets/js/orders.js"></script>
</body>
</html> 