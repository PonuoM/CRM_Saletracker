<?php
/**
 * Authentication Class
 * จัดการการยืนยันตัวตน session และ permissions
 */

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            // Get user by username
            $sql = "SELECT u.*, r.role_name, r.permissions, c.company_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.role_id 
                    LEFT JOIN companies c ON u.company_id = c.company_id 
                    WHERE u.username = :username AND u.is_active = 1";
            
            $user = $this->db->fetchOne($sql, ['username' => $username]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
            }
            
            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'user_id = :user_id', 
                ['user_id' => $user['user_id']]
            );
            
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['company_name'] = $user['company_name'];
            $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear session
        session_unset();
        session_destroy();
        
        // Redirect to login
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role_id' => $_SESSION['role_id'],
            'role_name' => $_SESSION['role_name'],
            'company_id' => $_SESSION['company_id'],
            'company_name' => $_SESSION['company_name'],
            'permissions' => $_SESSION['permissions']
        ];
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'] ?? [];
        
        // Super admin has all permissions
        if (in_array('all', $permissions)) {
            return true;
        }
        
        return in_array($permission, $permissions);
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($roleName) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['role_name'] === $roleName;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
    
    /**
     * Require permission
     */
    public function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            echo "<h1>Access Denied</h1>";
            echo "<p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>";
            echo "<p><a href='" . BASE_URL . "'>กลับหน้าหลัก</a></p>";
            exit;
        }
    }
    
    /**
     * Require role
     */
    public function requireRole($roleName) {
        $this->requireAuth();
        
        if (!$this->hasRole($roleName)) {
            http_response_code(403);
            echo "<h1>Access Denied</h1>";
            echo "<p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>";
            echo "<p><a href='" . BASE_URL . "'>กลับหน้าหลัก</a></p>";
            exit;
        }
    }
    
    /**
     * Create user
     */
    public function createUser($userData) {
        try {
            // Hash password
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']); // Remove plain password
            
            // Set default values
            $userData['is_active'] = 1;
            $userData['created_at'] = date('Y-m-d H:i:s');
            
            $userId = $this->db->insert('users', $userData);
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการสร้างผู้ใช้'];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userData) {
        try {
            $userId = $userData['user_id'];
            
            // Hash password if provided
            if (isset($userData['password']) && !empty($userData['password'])) {
                $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
                unset($userData['password']);
            }
            
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            $affected = $this->db->update('users', $userData, 'user_id = :user_id', ['user_id' => $userId]);
            
            return ['success' => true, 'affected' => $affected];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตผู้ใช้'];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            // ตรวจสอบว่าผู้ใช้มีคำสั่งซื้อหรือไม่
            $sql = "SELECT COUNT(*) as count FROM orders WHERE created_by = :user_id";
            $result = $this->db->fetchOne($sql, ['user_id' => $userId]);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'ไม่สามารถลบผู้ใช้ได้ เนื่องจากมีคำสั่งซื้อที่เกี่ยวข้อง'];
            }
            
            // ตรวจสอบว่าผู้ใช้มีลูกค้าที่ได้รับมอบหมายหรือไม่
            $sql = "SELECT COUNT(*) as count FROM customers WHERE assigned_to = :user_id";
            $result = $this->db->fetchOne($sql, ['user_id' => $userId]);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'ไม่สามารถลบผู้ใช้ได้ เนื่องจากมีลูกค้าที่ได้รับมอบหมาย'];
            }
            
            // ลบผู้ใช้
            $sql = "DELETE FROM users WHERE user_id = :user_id";
            $this->db->execute($sql, ['user_id' => $userId]);
            
            return ['success' => true, 'message' => 'ลบผู้ใช้สำเร็จ'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบผู้ใช้'];
        }
    }
    
    /**
     * Get all users
     */
    public function getAllUsers() {
        $sql = "SELECT u.*, r.role_name, c.company_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                LEFT JOIN companies c ON u.company_id = c.company_id 
                ORDER BY u.created_at DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $sql = "SELECT u.*, r.role_name, c.company_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                LEFT JOIN companies c ON u.company_id = c.company_id 
                WHERE u.user_id = :user_id";
        
        return $this->db->fetchOne($sql, ['user_id' => $userId]);
    }
}
?> 