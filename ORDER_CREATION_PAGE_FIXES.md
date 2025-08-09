# Order Creation Page Fixes

## ปัญหาที่แก้ไข

### 1. ราคาต่อหน่วยไม่สามารถแก้ไขได้
**ปัญหาเดิม:** ราคาต่อหน่วยถูกกำหนดจากฐานข้อมูลและไม่สามารถแก้ไขได้

**การแก้ไข:**
- เพิ่มฟิลด์ "ราคาต่อหน่วย (฿)" ที่สามารถแก้ไขได้
- เมื่อเลือกสินค้า ราคาจะถูกเติมอัตโนมัติจากฐานข้อมูล แต่สามารถแก้ไขได้
- เพิ่มการตรวจสอบว่าต้องระบุราคาต่อหน่วยก่อนเพิ่มสินค้า

### 2. ส่วนลดคำนวณเป็นเปอร์เซ็นต์
**ปัญหาเดิม:** ส่วนลดคำนวณเป็นเปอร์เซ็นต์ (%) 

**การแก้ไข:**
- เปลี่ยนจาก "ส่วนลด (%)" เป็น "ส่วนลด (฿)" 
- ส่วนลดตอนนี้เป็นยอดเงินคงที่ ไม่ใช่เปอร์เซ็นต์
- การคำนวณ: ราคารวม = (จำนวน × ราคาต่อหน่วย) - ส่วนลด

## การเปลี่ยนแปลงในโค้ด

### 1. HTML Form (`app/views/orders/create.php`)
```diff
- <label for="discount_percentage" class="form-label">ส่วนลด (%)</label>
- <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
-        value="0" min="0" max="100" step="0.01">

+ <label for="unit_price" class="form-label">ราคาต่อหน่วย (฿)</label>
+ <input type="number" class="form-control" id="unit_price" name="unit_price" 
+        value="0" min="0" step="0.01">

+ <label for="discount_amount" class="form-label">ส่วนลด (฿)</label>
+ <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
+        value="0" min="0" step="0.01">
```

### 2. Table Headers
```diff
+ <th class="text-end">ส่วนลด</th>
```

### 3. JavaScript (`assets/js/orders.js`)

#### Event Listeners
```diff
- // Discount percentage change
- const discountPercentage = document.getElementById('discount_percentage');
- if (discountPercentage) {
-     discountPercentage.addEventListener('input', this.updateSummary.bind(this));
- }

+ // Unit price change
+ const unitPrice = document.getElementById('unit_price');
+ if (unitPrice) {
+     unitPrice.addEventListener('input', this.updateSummary.bind(this));
+ }

+ // Discount amount change
+ const discountAmount = document.getElementById('discount_amount');
+ if (discountAmount) {
+     discountAmount.addEventListener('input', this.updateSummary.bind(this));
+ }
```

#### Product Selection
```diff
selectProduct(productId) {
    const product = window.products.find(p => p.product_id == productId);
    if (product) {
        const searchInput = document.getElementById('product_search');
+       const unitPriceInput = document.getElementById('unit_price');
        searchInput.value = product.product_name;
        searchInput.dataset.productId = productId;
        searchInput.dataset.productPrice = product.selling_price;
+       unitPriceInput.value = product.selling_price || 0;
        document.getElementById('productResults').style.display = 'none';
    }
}
```

#### Add Product Function
```diff
addProduct() {
    const productId = document.getElementById('product_search').dataset.productId;
    const productName = document.getElementById('product_search').value;
    const quantity = parseInt(document.getElementById('quantity').value);
+   const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
+   const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    
    if (!productId || !productName || quantity <= 0) {
        this.showAlert('กรุณาเลือกสินค้าและระบุจำนวน', 'warning');
        return;
    }
    
+   if (unitPrice <= 0) {
+       this.showAlert('กรุณาระบุราคาต่อหน่วย', 'warning');
+       return;
+   }
    
    // ... existing code ...
    
    if (existingItem) {
        existingItem.quantity += quantity;
-       const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
-       const unitPrice = parseFloat(existingItem.unit_price || 0);
        const subtotal = existingItem.quantity * unitPrice;
-       existingItem.discount_amount = (subtotal * discountPercentage) / 100;
+       existingItem.discount_amount = discountAmount;
        existingItem.total_price = subtotal - discountAmount;
    } else {
-       const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
-       const unitPrice = parseFloat(product.selling_price || 0);
        const subtotal = quantity * unitPrice;
-       const discountAmount = (subtotal * discountPercentage) / 100;
        const totalPrice = subtotal - discountAmount;
        
        window.orderItems.push({
            product_id: productId,
            product_name: product.product_name,
            product_code: product.product_code,
            quantity: quantity,
            unit_price: unitPrice,
            discount_amount: discountAmount,
            total_price: totalPrice
        });
    }
}
```

#### Form Submission
```diff
const formData = {
    customer_id: customerId,
    items: window.orderItems,
    payment_method: document.getElementById('payment_method').value,
    payment_status: 'pending',
    delivery_date: document.getElementById('order_date').value || null,
    delivery_address: document.getElementById('delivery_address').value || null,
    use_customer_address: document.getElementById('use_customer_address').checked,
-   discount_percentage: parseFloat(document.getElementById('discount_percentage').value) || 0,
+   discount_amount: parseFloat(document.getElementById('discount_amount').value) || 0,
    notes: document.getElementById('notes').value || null
};
```

#### Clear Form Function
```diff
clearProductForm() {
    const searchInput = document.getElementById('product_search');
    searchInput.value = '';
    searchInput.dataset.productId = '';
    searchInput.dataset.productPrice = '';
    document.getElementById('quantity').value = '1';
+   document.getElementById('unit_price').value = '0';
+   document.getElementById('discount_amount').value = '0';
}
```

## ผลลัพธ์

### ก่อนการแก้ไข:
- ราคาต่อหน่วยถูกกำหนดจากฐานข้อมูลและไม่สามารถแก้ไขได้
- ส่วนลดคำนวณเป็นเปอร์เซ็นต์ (%)

### หลังการแก้ไข:
- ราคาต่อหน่วยสามารถแก้ไขได้ (เริ่มต้นจากราคาในฐานข้อมูล)
- ส่วนลดเป็นยอดเงินคงที่ (฿)
- การคำนวณ: ราคารวม = (จำนวน × ราคาต่อหน่วย) - ส่วนลด
- ตารางแสดงคอลัมน์ส่วนลดแยกต่างหาก

## การทดสอบ

1. **ทดสอบการแก้ไขราคาต่อหน่วย:**
   - เลือกสินค้า
   - ตรวจสอบว่าราคาถูกเติมอัตโนมัติ
   - แก้ไขราคา
   - เพิ่มสินค้า
   - ตรวจสอบว่าราคาที่แก้ไขถูกใช้ในการคำนวณ

2. **ทดสอบส่วนลดเป็นยอดเงิน:**
   - ใส่ส่วนลดเป็นยอดเงิน (เช่น 100 บาท)
   - เพิ่มสินค้า
   - ตรวจสอบว่าส่วนลดถูกหักออกจากราคารวม

3. **ทดสอบการคำนวณ:**
   - ราคารวม = (จำนวน × ราคาต่อหน่วย) - ส่วนลด
   - ยอดรวมทั้งสิ้น = ผลรวมของราคารวมทุกรายการ
