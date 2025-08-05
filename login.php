<?php
/**
 * CRM SalesTracker - Login Page
 * หน้าล็อกอินสำหรับผู้ใช้
 */

// Start session
session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Load configuration
require_once 'config/config.php';

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            // Load core classes
            require_once 'app/core/Database.php';
            require_once 'app/core/Auth.php';
            
            // Initialize database and auth
            $db = new Database();
            $auth = new Auth($db);
            
            // Attempt login
            if ($auth->login($username, $password)) {
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7c9885;
            --secondary-color: #a8b5c4;
            --success-color: #9bbf8b;
            --warning-color: #e6c27d;
            --danger-color: #d4a5a5;
            --text-color: #4a5568;
            --light-gray: #fafbfc;
            --border-color: #e2e8f0;
        }
        
        body {
            background: var(--light-gray);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sukhumvit Set', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color);
        }
        
        .login-header {
            background: var(--card-bg);
            color: var(--text-color);
            padding: 40px 30px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .login-header h2 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: var(--text-color);
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .login-header p {
            color: var(--text-muted);
            margin: 0;
            font-size: 14px;
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 8px;
            font-size: 14px;
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            font-size: 14px;
            background: var(--card-bg);
            transition: all 0.3s ease;
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(124, 152, 133, 0.15);
            outline: none;
        }
        
        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            color: var(--card-bg);
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .btn-login:hover {
            background: #6b8a72;
            transform: none;
            box-shadow: 0 2px 8px rgba(124, 152, 133, 0.3);
        }
        
        .input-group-text {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-right: none;
            color: var(--text-muted);
        }
        
        .form-control {
            border-left: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .company-info {
            text-align: center;
            margin-top: 30px;
            color: var(--text-muted);
            font-size: 12px;
            font-family: 'Sukhumvit Set', sans-serif;
        }
        
        .company-info hr {
            border-color: var(--border-color);
            margin: 20px 0;
        }
        
        .company-info p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-chart-line fa-3x mb-3"></i>
            <h2>CRM SalesTracker</h2>
            <p class="mb-0">ระบบจัดการลูกค้าสัมพันธ์</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               placeholder="กรอกชื่อผู้ใช้" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="กรอกรหัสผ่าน" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="company-info">
                <hr>
                <p><strong>บริษัท พรีม่าแพสชั่น 49 จำกัด</strong></p>
                <p>เวอร์ชัน 1.0.0</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
                return false;
            }
        });
    </script>
</body>
</html> 