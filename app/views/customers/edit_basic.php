<?php if (!isset($customer) || !$customer) { echo '<div class="alert alert-danger">ไม่พบข้อมูลลูกค้า</div>'; return; } ?>
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">แก้ไขข้อมูลลูกค้า</h1>
    <a href="customers.php?action=show&id=<?php echo $customer['customer_id']; ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>กลับ
    </a>
 </div>

<div class="card">
  <div class="card-body">
    <form id="customerBasicForm">
        <input type="hidden" id="customer_id" value="<?php echo $customer['customer_id']; ?>" />
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">ชื่อ *</label>
                <input type="text" class="form-control" id="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required />
            </div>
            <div class="col-md-6">
                <label class="form-label">นามสกุล</label>
                <input type="text" class="form-control" id="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" />
            </div>
            <div class="col-md-6">
                <label class="form-label">เบอร์โทร *</label>
                <input type="text" class="form-control" id="phone" maxlength="10" inputmode="numeric" placeholder="เช่น 0912345678" value="<?php echo htmlspecialchars(preg_match('/^0\d{9}$/',$customer['phone'])? $customer['phone'] : ('0'.$customer['phone'])); ?>" required />
                <div class="form-text">กรอก 10 หลักขึ้นต้น 0 ระบบจะตัด 0 ออกให้เก็บ 9 หลัก</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">อีเมล</label>
                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" />
            </div>
            <div class="col-12">
                <label class="form-label">ที่อยู่</label>
                <input type="text" class="form-control" id="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" />
            </div>
            <div class="col-md-6">
                <label class="form-label">จังหวัด</label>
                <input type="text" class="form-control" id="province" value="<?php echo htmlspecialchars($customer['province'] ?? ''); ?>" />
            </div>
            <div class="col-md-6">
                <label class="form-label">รหัสไปรษณีย์</label>
                <input type="text" class="form-control" id="postal_code" maxlength="10" value="<?php echo htmlspecialchars($customer['postal_code'] ?? ''); ?>" />
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>บันทึก</button>
            <a href="customers.php?action=show&id=<?php echo $customer['customer_id']; ?>" class="btn btn-outline-secondary">ยกเลิก</a>
        </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const phone = document.getElementById('phone');
  phone.addEventListener('input', () => { phone.value = phone.value.replace(/[^0-9]/g,'').slice(0,10); });

  document.getElementById('customerBasicForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const digits = phone.value.replace(/[^0-9]/g,'');
    if (!/^0\d{9}$/.test(digits)) { alert('กรอกเบอร์ 10 หลักขึ้นต้น 0'); return; }
    const payload = {
      customer_id: document.getElementById('customer_id').value,
      first_name: document.getElementById('first_name').value.trim(),
      last_name: document.getElementById('last_name').value.trim(),
      phone: digits,
      email: document.getElementById('email').value.trim(),
      address: document.getElementById('address').value.trim(),
      province: document.getElementById('province').value.trim(),
      postal_code: document.getElementById('postal_code').value.trim()
    };
    try {
      const res = await fetch('customers.php?action=update_basic', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data.success) { alert(data.message || 'บันทึกไม่สำเร็จ'); return; }
      alert('บันทึกข้อมูลเรียบร้อย');
      window.location.href = 'customers.php?action=show&id=' + payload.customer_id;
    } catch (err) { alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'); }
  });
});
</script>

