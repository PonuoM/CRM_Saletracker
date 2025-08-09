# Order Creation UI and Customer Status Fixes

## ปัญหาที่แก้ไข

### 1. ปรับปรุง UI ของหน้า Order Creation
**ปัญหาเดิม:** 
- ฟอร์มการเพิ่มสินค้ามีขนาดใหญ่และไม่สวยงาม
- ปุ่ม "เพิ่มสินค้า" อยู่ในบรรทัดเดียวกับ input fields ทำให้ดูแออัด
- Textbox จำนวน, ราคาต่อหน่วย, และส่วนลดมีขนาดใหญ่เกินไป

**การแก้ไข:**
- ย้ายปุ่ม "เพิ่มสินค้า" ไปอยู่บน card header
- ลดขนาด textbox ทั้งหมดเป็น `form-control-sm`
- ปรับ layout ให้อยู่ในบรรทัดเดียวกันและสวยงามขึ้น
- ย่อ label ให้กระชับขึ้น

### 2. สถานะลูกค้าไม่เปลี่ยนเป็น "ลูกค้าเก่า" หลังการขาย
**ปัญหาเดิม:** เมื่อสร้างคำสั่งซื้อสำเร็จ สถานะลูกค้ายังคงเป็น "new" ไม่เปลี่ยนเป็น "existing"

**การแก้ไข:** เพิ่มการอัปเดตสถานะลูกค้าในฟังก์ชัน `updateCustomerPurchaseHistory`

## การเปลี่ยนแปลงในโค้ด

### 1. HTML Form (`app/views/orders/create.php`)

#### Card Header
```diff
- <div class="card-header">
-     <h5 class="mb-0">สินค้า</h5>
- </div>

+ <div class="card-header d-flex justify-content-between align-items-center">
+     <h5 class="mb-0">สินค้า</h5>
+     <button type="button" class="btn btn-primary btn-sm" onclick="addProduct()">
+         <i class="fas fa-plus me-1"></i>เพิ่มสินค้า
+     </button>
+ </div>
```

#### Form Layout
```diff
- <div class="col-md-4">
+ <div class="col-md-5">
      <label for="product_search" class="form-label">ค้นหาสินค้า</label>
      <!-- Product search input -->
  </div>
- <div class="col-md-3">
+ <div class="col-md-2">
      <label for="quantity" class="form-label">จำนวน</label>
-     <input type="number" class="form-control" id="quantity" name="quantity" 
+     <input type="number" class="form-control form-control-sm" id="quantity" name="quantity" 
            value="1" min="1" max="999" required>
  </div>
- <div class="col-md-3">
-     <label for="unit_price" class="form-label">ราคาต่อหน่วย (฿)</label>
-     <input type="number" class="form-control" id="unit_price" name="unit_price" 
+ <div class="col-md-2">
+     <label for="unit_price" class="form-label">ราคา/หน่วย</label>
+     <input type="number" class="form-control form-control-sm" id="unit_price" name="unit_price" 
            value="0" min="0" step="0.01">
  </div>
- <div class="col-md-3">
-     <label for="discount_amount" class="form-label">ส่วนลด (฿)</label>
-     <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
+ <div class="col-md-2">
+     <label for="discount_amount" class="form-label">ส่วนลด</label>
+     <input type="number" class="form-control form-control-sm" id="discount_amount" name="discount_amount" 
            value="0" min="0" step="0.01">
  </div>
- <div class="col-md-2 d-flex align-items-end">
-     <button type="button" class="btn btn-primary" onclick="addProduct()">
-         <i class="fas fa-plus me-1"></i>เพิ่มสินค้า
-     </button>
+ <div class="col-md-1 d-flex align-items-end">
+     <!-- Empty space for alignment -->
  </div>
```

### 2. OrderService (`app/services/OrderService.php`)

#### Update Customer Purchase History
```diff
private function updateCustomerPurchaseHistory($customerId, $amount, $updateGrade = false) {
    // อัปเดตยอดซื้อรวม - ใช้ total_purchase_amount แทน total_purchase
    $this->db->query(
        "UPDATE customers SET total_purchase_amount = total_purchase_amount + :amount WHERE customer_id = :customer_id",
        ['amount' => $amount, 'customer_id' => $customerId]
    );
    
+   // อัปเดตสถานะลูกค้าเป็น "existing" (ลูกค้าเก่า) เมื่อมีการขาย
+   $this->db->query(
+       "UPDATE customers SET customer_status = 'existing' WHERE customer_id = :customer_id",
+       ['customer_id' => $customerId]
+   );
    
    // อัปเดตเกรดลูกค้า (เฉพาะเมื่อต้องการ)
    if ($updateGrade) {
        try {
            $customerService = new CustomerService();
            $customerService->updateCustomerGrade($customerId);
        } catch (Exception $e) {
            // Log error but don't fail the order creation
            error_log("Failed to update customer grade: " . $e->getMessage());
        }
    }
}
```

## ผลลัพธ์

### 1. UI Improvements
- **ก่อนการแก้ไข:** ฟอร์มแออัด, ปุ่มอยู่ในบรรทัดเดียวกับ input fields
- **หลังการแก้ไข:** 
  - ปุ่ม "เพิ่มสินค้า" ย้ายไปอยู่บน card header
  - Input fields มีขนาดเล็กลงและอยู่ในบรรทัดเดียวกัน
  - Layout สวยงามและใช้งานง่ายขึ้น
  - Labels กระชับและเข้าใจง่าย

### 2. Customer Status Update
- **ก่อนการแก้ไข:** ลูกค้าที่ซื้อแล้วยังคงสถานะ "new"
- **หลังการแก้ไข:** 
  - เมื่อสร้างคำสั่งซื้อสำเร็จ สถานะลูกค้าจะเปลี่ยนเป็น "existing" อัตโนมัติ
  - ลูกค้าจะปรากฏในแท็บ "ลูกค้าเก่า" แทน "ลูกค้าใหม่"
  - ระบบจะแสดงผลถูกต้องตามสถานะการซื้อ

## การทดสอบ

### 1. ทดสอบ UI
1. เข้าหน้า Order Creation
2. ตรวจสอบว่าปุ่ม "เพิ่มสินค้า" อยู่บน card header
3. ตรวจสอบว่า input fields มีขนาดเล็กลงและอยู่ในบรรทัดเดียวกัน
4. ทดสอบการใช้งานฟอร์ม

### 2. ทดสอบ Customer Status Update
1. สร้างคำสั่งซื้อใหม่สำหรับลูกค้าที่มีสถานะ "new"
2. ตรวจสอบว่าหลังจากสร้างคำสั่งซื้อสำเร็จ
3. ไปที่หน้า Customer List
4. ตรวจสอบว่าลูกค้าปรากฏในแท็บ "ลูกค้าเก่า" แทน "ลูกค้าใหม่"
5. ตรวจสอบสถานะลูกค้าในฐานข้อมูลว่าเป็น "existing"

## หมายเหตุ
- การอัปเดตสถานะลูกค้าเกิดขึ้นทันทีเมื่อสร้างคำสั่งซื้อสำเร็จ
- ไม่มีผลกระทบต่อคำสั่งซื้อที่มีอยู่แล้ว
- การเปลี่ยนแปลงนี้จะช่วยให้การจัดการลูกค้ามีความถูกต้องมากขึ้น
