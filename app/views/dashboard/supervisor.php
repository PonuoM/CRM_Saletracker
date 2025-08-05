<?php
/**
 * CRM SalesTracker - Supervisor Dashboard
 * หน้าหลักสำหรับ Supervisor
 */

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #e9ecef;
            --hot-color: #dc2626;
            --warm-color: #f59e0b;
            --cold-color: #3b82f6;
            --frozen-color: #6b7280;
        }
        
        body {
            background-color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            color: var(--text-color);
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .navbar-brand {
            color: var(--text-color) !important;
            font-weight: 600;
        }
        
        .navbar-nav .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
        }
        
        .sidebar {
            background: white;
            border-right: 1px solid var(--border-color);
            min-height: calc(100vh - 56px);
        }
        
        .sidebar .nav-link {
            color: var(--text-color);
            padding: 12px 16px;
            border-radius: 6px;
            margin: 2px 8px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .main-content {
            padding: 24px;
            background: white;
        }
        
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .breadcrumb {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .kpi-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .kpi-card h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .kpi-change {
            font-size: 0.75rem;
            color: var(--secondary-color);
        }
        
        .card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            background: white;
        }
        
        .card:hover {
            transform: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            color: var(--text-color);
        }
        
        .team-performance {
            margin-top: 24px;
        }
        
        .performance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .performance-table th,
        .performance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .performance-table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--text-color);
        }
        
        .performance-table tr:hover {
            background-color: var(--light-gray);
        }
        
        .chart-container {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            margin-top: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                CRM SalesTracker
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i>
                        <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>โปรไฟล์</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>ตั้งค่า</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h6 class="text-muted mb-3">เมนูหลัก</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php">
                                <i class="fas fa-users me-2"></i>
                                จัดการลูกค้า
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                คำสั่งซื้อ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                รายงาน
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="team.php">
                                <i class="fas fa-users-cog me-2"></i>
                                จัดการทีม
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="page-header">
                    <h1>Supervisor Dashboard</h1>
                    <div class="breadcrumb">หน้าแรก > แดชบอร์ด</div>
                </div>
                
                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>คำสั่งซื้อทั้งหมด</h3>
                            <div class="kpi-value">1,234</div>
                            <div class="kpi-change">+12% จากเดือนที่แล้ว</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>ยอดขายรวม</h3>
                            <div class="kpi-value">฿2,456,789</div>
                            <div class="kpi-change">+8% จากเดือนที่แล้ว</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>มูลค่าเฉลี่ย</h3>
                            <div class="kpi-value">฿1,987</div>
                            <div class="kpi-change">-3% จากเดือนที่แล้ว</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <h3>สมาชิกทีม</h3>
                            <div class="kpi-value">8</div>
                            <div class="kpi-change">+1 จากเดือนที่แล้ว</div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="chart-container">
                    <h5 class="mb-3">กราฟยอดขายรายเดือน</h5>
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Team Performance -->
                <div class="team-performance">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users me-2"></i>
                                ประสิทธิภาพทีม
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="performance-table">
                                <thead>
                                    <tr>
                                        <th>ชื่อ</th>
                                        <th>ยอดขาย</th>
                                        <th>จำนวนการโทร</th>
                                        <th>อัตราปิดการขาย</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>พนักงานขาย 1</td>
                                        <td>฿45,678</td>
                                        <td>120</td>
                                        <td>15%</td>
                                        <td><span class="badge bg-success">ออนไลน์</span></td>
                                    </tr>
                                    <tr>
                                        <td>พนักงานขาย 2</td>
                                        <td>฿38,920</td>
                                        <td>95</td>
                                        <td>12%</td>
                                        <td><span class="badge bg-warning">พัก</span></td>
                                    </tr>
                                    <tr>
                                        <td>พนักงานขาย 3</td>
                                        <td>฿52,145</td>
                                        <td>150</td>
                                        <td>18%</td>
                                        <td><span class="badge bg-success">ออนไลน์</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sample chart data
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
                datasets: [{
                    label: 'ยอดขายรายเดือน',
                    data: [1200000, 1350000, 1420000, 1380000, 1560000, 1680000],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '฿' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 