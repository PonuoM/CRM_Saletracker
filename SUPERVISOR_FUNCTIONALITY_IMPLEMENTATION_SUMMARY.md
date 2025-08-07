# Supervisor Functionality Implementation Summary

## ภาพรวมการแก้ไข

ได้ทำการแก้ไขระบบ CRM เพื่อให้ Supervisor (role 3) มีการทำงานที่ถูกต้องตามที่ต้องการ:

1. **การจำกัดการมองเห็นข้อมูล**: Supervisor จะเห็นเฉพาะข้อมูลของทีมตัวเอง (เหมือน telesales)
2. **การเปิดใช้งานฟังก์ชันจัดการทีม**: เพิ่มการเข้าถึงหน้า "จัดการทีม" สำหรับ Supervisor

## ไฟล์ที่แก้ไข

### 1. `app/controllers/CustomerController.php`

#### การเปลี่ยนแปลงหลัก:
- **แก้ไข method `index()`**: เปลี่ยนการแสดงข้อมูลลูกค้าสำหรับ supervisor ให้เห็นเฉพาะลูกค้าของทีมตัวเอง
- **เพิ่ม method `getTeamCustomerIds()`**: ดึง user_id ของสมาชิกทีมทั้งหมด
- **แก้ไข method `show()`**: จำกัดการเข้าถึงรายละเอียดลูกค้าเฉพาะทีมตัวเอง
- **แก้ไข method `getCustomerAddress()`**: จำกัดการเข้าถึงข้อมูลที่อยู่ลูกค้า
- **แก้ไข method `assignCustomers()`**: จำกัดการมอบหมายลูกค้าเฉพาะสมาชิกในทีม

#### รายละเอียดการแก้ไข:

```php
// ใน method index() - เปลี่ยนจาก admin-like เป็น team-specific
case 'supervisor':
    // Supervisor เห็นเฉพาะลูกค้าของทีมตัวเอง
    $teamCustomerIds = $this->getTeamCustomerIds($userId);
    if (!empty($teamCustomerIds)) {
        $customers = $this->customerService->getCustomersByBasket('assigned', ['assigned_to' => $teamCustomerIds]);
    } else {
        $customers = [];
    }
    break;

// เพิ่ม method ใหม่
private function getTeamCustomerIds($supervisorId) {
    $teamMembers = $this->db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        ['supervisor_id' => $supervisorId]
    );
    $teamCustomerIds = [];
    foreach ($teamMembers as $member) {
        $teamCustomerIds[] = $member['user_id'];
    }
    return $teamCustomerIds;
}
```

### 2. `app/controllers/OrderController.php`

#### การเปลี่ยนแปลงหลัก:
- **แก้ไข method `index()`**: เปลี่ยนการแสดงข้อมูลคำสั่งซื้อสำหรับ supervisor ให้เห็นเฉพาะคำสั่งซื้อของทีมตัวเอง
- **เพิ่ม method `getTeamMemberIds()`**: ดึง user_id ของสมาชิกทีมทั้งหมด
- **แก้ไขการดึงข้อมูลลูกค้าสำหรับตัวกรอง**: จำกัดเฉพาะลูกค้าของทีม

#### รายละเอียดการแก้ไข:

```php
// ใน method index() - เพิ่มการกรองสำหรับ supervisor
} elseif ($roleName === 'supervisor') {
    // Supervisor เห็นเฉพาะคำสั่งซื้อของทีมตัวเอง
    $teamMemberIds = $this->getTeamMemberIds($userId);
    if (!empty($teamMemberIds)) {
        $filters['created_by'] = $teamMemberIds;
    } else {
        $filters['created_by'] = [-1]; // ไม่มีทีม
    }
}

// เพิ่ม method ใหม่
private function getTeamMemberIds($supervisorId) {
    $teamMembers = $this->db->fetchAll(
        "SELECT user_id FROM users WHERE supervisor_id = :supervisor_id AND is_active = 1",
        ['supervisor_id' => $supervisorId]
    );
    $teamMemberIds = [];
    foreach ($teamMembers as $member) {
        $teamMemberIds[] = $member['user_id'];
    }
    return $teamMemberIds;
}
```

### 3. `app/services/DashboardService.php` ⭐ **แก้ไขใหม่**

#### การเปลี่ยนแปลงหลัก:
- **แก้ไข method `getDashboardData()`**: เพิ่มการตรวจสอบ role supervisor และใช้ข้อมูลเฉพาะทีม
- **เพิ่ม methods สำหรับ supervisor team data**: เพิ่ม methods ใหม่สำหรับดึงข้อมูลเฉพาะทีม

#### รายละเอียดการแก้ไข:

```php
// แก้ไข method getDashboardData()
public function getDashboardData($userId = null, $role = null) {
    try {
        // สำหรับ supervisor ใช้ข้อมูลเฉพาะทีม
        if ($role === 'supervisor') {
            $data = [
                'total_customers' => $this->getTeamTotalCustomers($userId),
                'hot_customers' => $this->getTeamHotCustomers($userId),
                'total_orders' => $this->getTeamTotalOrders($userId),
                'total_sales' => $this->getTeamTotalSales($userId),
                'monthly_sales' => $this->getTeamMonthlySales($userId),
                'recent_activities' => $this->getTeamRecentActivities($userId),
                'customer_grades' => $this->getTeamCustomerGrades($userId),
                'order_status' => $this->getTeamOrderStatus($userId)
            ];
        } else {
            // สำหรับ admin และ super_admin ใช้ข้อมูลทั้งหมด
            $data = [
                'total_customers' => $this->getTotalCustomers(),
                'hot_customers' => $this->getHotCustomers(),
                'total_orders' => $this->getTotalOrders(),
                'total_sales' => $this->getTotalSales(),
                'monthly_sales' => $this->getMonthlySales(),
                'recent_activities' => $this->getRecentActivities($userId),
                'customer_grades' => $this->getCustomerGrades(),
                'order_status' => $this->getOrderStatus()
            ];
        }
        
        return ['success' => true, 'data' => $data];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()];
    }
}

// เพิ่ม methods ใหม่สำหรับ supervisor team data
private function getTeamMemberIds($supervisorId) { /* ... */ }
private function getTeamTotalCustomers($supervisorId) { /* ... */ }
private function getTeamHotCustomers($supervisorId) { /* ... */ }
private function getTeamTotalOrders($supervisorId) { /* ... */ }
private function getTeamTotalSales($supervisorId) { /* ... */ }
private function getTeamMonthlySales($supervisorId) { /* ... */ }
private function getTeamRecentActivities($supervisorId) { /* ... */ }
private function getTeamCustomerGrades($supervisorId) { /* ... */ }
private function getTeamOrderStatus($supervisorId) { /* ... */ }
```

### 4. `app/services/CustomerService.php` ⭐ **แก้ไขใหม่**

#### การเปลี่ยนแปลงหลัก:
- **แก้ไข method `getCustomersByBasket()`**: เพิ่มการรองรับ array ของ user_id สำหรับ supervisor

#### รายละเอียดการแก้ไข:

```php
// แก้ไขการจัดการ assigned_to filter
if (!empty($filters['assigned_to'])) {
    if (is_array($filters['assigned_to'])) {
        // สำหรับ supervisor ที่ส่ง array ของ user_id
        $placeholders = str_repeat('?,', count($filters['assigned_to']) - 1) . '?';
        $sql .= " AND c.assigned_to IN ($placeholders)";
        $params = array_merge($params, $filters['assigned_to']);
    } else {
        // สำหรับ telesales ที่ส่ง user_id เดียว
        $sql .= " AND c.assigned_to = :assigned_to";
        $params['assigned_to'] = $filters['assigned_to'];
    }
}
```

### 5. `app/core/Router.php`

#### การเปลี่ยนแปลงหลัก:
- **เพิ่ม case สำหรับ team.php**: เพิ่มการจัดการ route สำหรับหน้า team management
- **เพิ่ม method `handleTeam()`**: จัดการการเข้าถึงหน้า team management

#### รายละเอียดการแก้ไข:

```php
// เพิ่มใน switch statement
case 'team.php':
    $this->handleTeam();
    break;

// เพิ่ม method ใหม่
private function handleTeam() {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        $this->redirect('login.php');
        return;
    }
    // Check permission - only supervisor
    $roleName = $_SESSION['role_name'] ?? '';
    if ($roleName !== 'supervisor') {
        $this->showError('Access Denied', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        return;
    }
    include 'team.php';
}
```

### 6. `app/views/components/sidebar.php`

#### การตรวจสอบ:
- ยืนยันว่ามีลิงก์ "จัดการทีม" สำหรับ supervisor role
- ลิงก์ชี้ไปที่ `team.php` อย่างถูกต้อง

```php
<?php elseif ($roleName === 'supervisor'): ?>
<li class="nav-item">
    <a class="nav-link <?php echo ($currentPage === 'team') ? 'active' : ''; ?>" href="team.php">
        <i class="fas fa-users-cog me-2"></i>
        จัดการทีม
    </a>
</li>
<?php endif; ?>
```

### 7. `team.php`

#### การตรวจสอบ:
- ยืนยันว่าไฟล์มีอยู่และมีการตรวจสอบสิทธิ์ supervisor
- มีการดึงข้อมูลทีมและแสดงผลอย่างถูกต้อง

## การแก้ไขปัญหา

### 1. Linter Errors
- **ปัญหา**: เกิด error "Use of unassigned variable" ใน `assignCustomers()`
- **การแก้ไข**: เพิ่ม null coalescing operator (`??`) เพื่อป้องกัน undefined variable

```php
// แก้ไขจาก
if (!in_array($input['telesales_id'], $teamMemberIds)) {

// เป็น
if (!in_array($input['telesales_id'] ?? null, $teamMemberIds)) {
```

### 2. Dashboard Data Issue ⭐ **แก้ไขใหม่**
- **ปัญหา**: Supervisor เห็นข้อมูลทั้งหมดเหมือน admin
- **การแก้ไข**: เพิ่มการตรวจสอบ role ใน DashboardService และใช้ข้อมูลเฉพาะทีม

### 3. Customer Management Issue ⭐ **แก้ไขใหม่**
- **ปัญหา**: CustomerService ไม่รองรับ array ของ user_id
- **การแก้ไข**: แก้ไข method `getCustomersByBasket()` ให้รองรับทั้ง single user_id และ array

### 4. Terminal Issues
- **ปัญหา**: ไม่สามารถรัน `php -l` เพื่อตรวจสอบ syntax ได้
- **สถานะ**: ยังไม่สามารถแก้ไขได้เนื่องจากปัญหา path ใน WSL environment

## ไฟล์ทดสอบ

### `test_supervisor_fix.php` ⭐ **ไฟล์ใหม่**
สร้างไฟล์ทดสอบเพื่อตรวจสอบ:
1. การเชื่อมต่อฐานข้อมูล
2. ข้อมูลผู้ใช้ Supervisor
3. สมาชิกทีมของ Supervisor
4. ทดสอบ DashboardService สำหรับ Supervisor
5. ทดสอบ CustomerService สำหรับ Supervisor
6. การเข้าถึงไฟล์ team.php
7. การ routing ใน Router.php

## สรุปการเปลี่ยนแปลง

### ✅ สิ่งที่ทำเสร็จแล้ว:
1. **จำกัดการมองเห็นข้อมูลลูกค้า**: Supervisor เห็นเฉพาะลูกค้าของทีมตัวเอง
2. **จำกัดการมองเห็นข้อมูลคำสั่งซื้อ**: Supervisor เห็นเฉพาะคำสั่งซื้อของทีมตัวเอง
3. **จำกัดการมองเห็นข้อมูล Dashboard**: Supervisor เห็นเฉพาะข้อมูลของทีมตัวเอง ⭐ **แก้ไขใหม่**
4. **เพิ่มการเข้าถึงหน้าจัดการทีม**: Supervisor สามารถเข้าถึงหน้า team.php ได้
5. **เพิ่มการตรวจสอบสิทธิ์**: ทุกฟังก์ชันมีการตรวจสอบสิทธิ์อย่างเหมาะสม
6. **แก้ไข linter errors**: แก้ไขปัญหา undefined variables
7. **แก้ไข CustomerService**: รองรับ array ของ user_id สำหรับ supervisor ⭐ **แก้ไขใหม่**

### 🔄 สิ่งที่ต้องตรวจสอบเพิ่มเติม:
1. **การทดสอบจริง**: ต้องทดสอบการทำงานจริงในระบบ
2. **การตรวจสอบ syntax**: ต้องแก้ไขปัญหา terminal เพื่อตรวจสอบ PHP syntax
3. **การทดสอบ UI**: ตรวจสอบว่าหน้าเว็บแสดงผลถูกต้อง

## คำแนะนำสำหรับการทดสอบ

1. **เข้าสู่ระบบด้วยบัญชี Supervisor**
2. **ตรวจสอบหน้า Dashboard**: ควรเห็นเฉพาะข้อมูลของทีม (จำนวนลูกค้า, คำสั่งซื้อ, ยอดขาย)
3. **ตรวจสอบหน้าจัดการลูกค้า**: ควรเห็นเฉพาะลูกค้าของทีม
4. **ตรวจสอบหน้าจัดการคำสั่งซื้อ**: ควรเห็นเฉพาะคำสั่งซื้อของทีม
5. **ทดสอบลิงก์จัดการทีม**: ควรเข้าถึงหน้า team.php ได้
6. **ทดสอบการมอบหมายลูกค้า**: ควรมอบหมายได้เฉพาะสมาชิกในทีม

## หมายเหตุ

การเปลี่ยนแปลงทั้งหมดนี้ทำให้ระบบ CRM มีการทำงานที่ถูกต้องตามหลัก Role-Based Access Control (RBAC) โดย Supervisor จะมีสิทธิ์ในการจัดการทีมของตัวเองเท่านั้น ไม่สามารถเข้าถึงข้อมูลของทีมอื่นได้

### ⭐ **การแก้ไขล่าสุด**:
- แก้ไขปัญหา Dashboard แสดงข้อมูลทั้งหมด
- แก้ไขปัญหา CustomerService ไม่รองรับ supervisor team data
- เพิ่มไฟล์ทดสอบใหม่สำหรับตรวจสอบการทำงาน
