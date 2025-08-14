<?php
// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

$products = $productList ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>สร้างคำสั่งซื้อ (ลูกค้าใหม่) - CRM SalesTracker</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
	<?php include APP_VIEWS . 'components/header.php'; ?>
	<div class="container-fluid">
		<div class="row">
			<?php include APP_VIEWS . 'components/sidebar.php'; ?>

			<main class="col-md-9 col-lg-10 main-content">
				<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
					<h1 class="h2">สร้างคำสั่งซื้อ (ลูกค้าใหม่)</h1>
					<div class="btn-toolbar mb-2 mb-md-0">
						<a href="orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>กลับ</a>
					</div>
				</div>

				<form id="orderForm" method="POST" action="orders.php?action=store">
					<div class="row">
						<div class="col-lg-8">
							<div class="card mb-4">
								<div class="card-header"><h5 class="mb-0">ข้อมูลลูกค้าใหม่</h5></div>
								<div class="card-body">
									<div class="row g-3">
										<div class="col-md-6">
											<label class="form-label">ชื่อ *</label>
											<input type="text" class="form-control" id="guest_first_name" required>
										</div>
										<div class="col-md-6">
											<label class="form-label">นามสกุล</label>
											<input type="text" class="form-control" id="guest_last_name">
										</div>
										<div class="col-md-6">
											<label class="form-label">เบอร์โทร *</label>
                    <input type="text" class="form-control" id="guest_phone" maxlength="10" inputmode="numeric" pattern="0[0-9]{9}" title="กรอกเบอร์ 10 หลักขึ้นต้น 0" required>
										</div>
										<div class="col-md-6">
											<label class="form-label">อีเมล</label>
											<input type="email" class="form-control" id="guest_email">
										</div>
										<div class="col-12">
											<label class="form-label">ที่อยู่ (บรรทัดรวม) *</label>
											<input type="text" class="form-control" id="guest_address" required>
										</div>
										<div class="col-md-4">
											<label class="form-label">จังหวัด *</label>
											<input type="text" class="form-control" id="guest_province" required>
										</div>
										<div class="col-md-4">
											<label class="form-label">อำเภอ/เขต</label>
											<input type="text" class="form-control" id="guest_district">
										</div>
										<div class="col-md-4">
											<label class="form-label">รหัสไปรษณีย์ *</label>
											<input type="text" class="form-control" id="guest_postal" required>
										</div>
									</div>
								</div>
							</div>

							<!-- รายละเอียดคำสั่งซื้อ (เหมือนหน้า create เดิม ยกเว้นไม่เลือก customer) -->
							<div class="card mb-4">
								<div class="card-header"><h5 class="mb-0">รายละเอียดคำสั่งซื้อ</h5></div>
								<div class="card-body">
									<div class="row g-3">
										<div class="col-md-6">
											<label class="form-label">วันที่สั่งซื้อ *</label>
											<input type="date" class="form-control" id="order_date" value="<?php echo date('Y-m-d'); ?>" required>
										</div>
										<div class="col-md-6">
											<label class="form-label">วิธีการชำระเงิน *</label>
											<select class="form-select" id="payment_method" required>
												<option value="cash">เงินสด</option>
												<option value="transfer">โอนเงิน</option>
												<option value="cod">เก็บเงินปลายทาง</option>
												<option value="credit">รับสินค้าก่อนชำระ</option>
												<option value="other">อื่นๆ</option>
											</select>
										</div>
									</div>

									<div class="mb-3">
										<label class="form-label">หมายเหตุ</label>
										<textarea id="notes" class="form-control" rows="3"></textarea>
									</div>
								</div>
							</div>

							<!-- สินค้า -->
							<div class="card mb-4">
								<div class="card-header d-flex justify-content-between align-items-center">
									<h5 class="mb-0">สินค้า</h5>
									<button type="button" class="btn btn-primary btn-sm" onclick="addProduct()"><i class="fas fa-plus me-1"></i>เพิ่มสินค้า</button>
								</div>
								<div class="card-body">
									<div class="row mb-3">
										<div class="col-md-5">
											<label class="form-label">ค้นหาสินค้า</label>
											<div class="product-search position-relative">
												<input type="text" class="form-control" id="product_search" placeholder="พิมพ์ชื่อหรือรหัสสินค้า">
												<div id="productResults" class="position-absolute w-100 bg-white border rounded shadow-sm" style="display:none; z-index:1000; max-height:200px; overflow-y:auto;"></div>
											</div>
										</div>
										<div class="col-md-2">
											<label class="form-label">จำนวน</label>
											<input type="number" class="form-control form-control-sm" id="quantity" value="1" min="1" max="999" required>
										</div>
										<div class="col-md-2">
											<label class="form-label">ราคา/หน่วย</label>
											<input type="number" class="form-control form-control-sm" id="unit_price" value="0" min="0" step="0.01">
										</div>
										<div class="col-md-2">
											<label class="form-label">ส่วนลด</label>
											<input type="number" class="form-control form-control-sm" id="discount_amount" value="0" min="0" step="0.01">
										</div>
										<div class="col-md-1 d-flex align-items-end">
											<div class="form-check">
												<input class="form-check-input" type="checkbox" id="is_gift">
												<label class="form-check-label" for="is_gift" style="font-size:0.875rem;">แถม</label>
											</div>
										</div>
									</div>

									<div class="table-responsive">
										<table class="table table-striped table-hover">
											<thead class="table-dark">
												<tr>
													<th>สินค้า</th>
													<th>รหัส</th>
													<th class="text-center">จำนวน</th>
													<th class="text-end">ราคาต่อหน่วย</th>
													<th class="text-end">ส่วนลด</th>
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

						<div class="col-lg-4">
							<div class="card">
								<div class="card-header"><h5 class="mb-0">สรุปคำสั่งซื้อ</h5></div>
								<div class="card-body">
									<div class="d-flex justify-content-between mb-2"><span>จำนวนรายการ:</span><span id="item_count">0 รายการ</span></div>
									<div class="d-flex justify-content-between mb-2"><span>ยอดรวมสินค้า:</span><span id="subtotal">฿0.00</span></div>
									<div class="d-flex justify-content-between mb-2"><span>ส่วนลด:</span><span id="discount_amount">฿0.00</span></div>
									<hr>
									<div class="d-flex justify-content-between mb-3"><strong>ยอดรวมทั้งสิ้น:</strong><strong id="net_amount">฿0.00</strong></div>
									<button type="submit" class="btn btn-primary w-100" id="submitBtn"><i class="fas fa-save me-1"></i>สร้างคำสั่งซื้อ</button>
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
	<script>
		// ส่ง products ให้ JS
		window.products = <?php echo json_encode($productList); ?>;

		// แทนที่ฟอร์มส่งปกติ: สร้างลูกค้าใหม่แล้วสร้างออเดอร์
        // Restrict phone input to digits only and length 10
        const phoneEl = document.getElementById('guest_phone');
        phoneEl.addEventListener('input', () => {
            phoneEl.value = phoneEl.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        document.getElementById('orderForm').addEventListener('submit', async function(e){
			e.preventDefault();
			const btn = document.getElementById('submitBtn');
			btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังบันทึก...';
			try {
				// 1) สร้างลูกค้าใหม่ (minimal) ผ่าน customers API ถ้ามี หรือใช้ order create ทางลัด
				const customerPayload = {
					first_name: document.getElementById('guest_first_name').value.trim(),
					last_name: document.getElementById('guest_last_name').value.trim(),
                    phone: document.getElementById('guest_phone').value.trim(),
					email: document.getElementById('guest_email').value.trim(),
					address: document.getElementById('guest_address').value.trim(),
					province: document.getElementById('guest_province').value.trim(),
					district: document.getElementById('guest_district').value.trim(),
					postal_code: document.getElementById('guest_postal').value.trim()
				};

                // Validate phone: must be exactly 10 digits and start with 0
                const digits = customerPayload.phone.replace(/[^0-9]/g, '');
                if (!customerPayload.first_name || digits.length !== 10 || digits[0] !== '0') {
                    alert('กรุณากรอกชื่อ และเบอร์โทร 10 หลักขึ้นต้น 0');
					btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i>สร้างคำสั่งซื้อ';
					return;
				}

				// สร้างออเดอร์โดยใช้ endpoint เดิม: ส่ง items และข้อมูลลูกค้าแฝง
				const payload = {
					// ไม่มี customer_id: backend จะต้องรองรับสร้างลูกค้าชั่วคราวจาก payload นี้ (เพิ่มภายหลังถ้าต้องการ)
					customer_temp: customerPayload,
					items: window.orderItems || [],
					payment_method: document.getElementById('payment_method').value,
					payment_status: 'pending',
					delivery_date: document.getElementById('order_date').value,
					delivery_address: `${customerPayload.address}${customerPayload.province? ', '+customerPayload.province:''}${customerPayload.postal_code? ' '+customerPayload.postal_code:''}`,
					discount_amount: parseFloat(document.getElementById('discount_amount').value) || 0,
					notes: document.getElementById('notes').value || null
				};

				if (!payload.items || payload.items.length === 0) {
					alert('กรุณาเพิ่มสินค้าอย่างน้อย 1 รายการ');
					btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i>สร้างคำสั่งซื้อ';
					return;
				}

				const res = await fetch('orders.php?action=store', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(payload)
				});
				const result = await res.json();
				if (result.success) {
					window.location.href = 'orders.php?action=show&id=' + result.order_id;
					return;
				}
				alert('เกิดข้อผิดพลาด: ' + (result.message || 'ไม่สามารถสร้างคำสั่งซื้อ'));
			} catch (err) {
				console.error(err);
				alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
			} finally {
				btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i>สร้างคำสั่งซื้อ';
			}
		});

		// ใช้ OrderManager สำหรับตารางสินค้าบนฟอร์ม
		let orderManager;
		document.addEventListener('DOMContentLoaded', function(){
			orderManager = new OrderManager();
		});
	</script>
</body>
</html>

