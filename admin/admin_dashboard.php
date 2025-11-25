<?php
include "../db.php";
include "../auth/auth_functions.php";

// Require admin role
requireLogin();
if (getUserRole() !== 'admin') {
    $_SESSION['error'] = 'Bạn không có quyền truy cập trang quản trị!';
    header('Location: ../dashboard.php');
    exit();
}

$page_title = "Quản trị hệ thống - Hotel Management";

// Handle user management actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $new_status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
                
                // Kiểm tra xem bảng users có tồn tại không
                $table_exists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
                if ($table_exists) {
                    $conn->query("UPDATE users SET status = '$new_status' WHERE id = $user_id");
                    $success_message = "Đã cập nhật trạng thái người dùng!";
                } else {
                    $error_message = "Bảng users chưa được tạo!";
                }
                break;
                
            case 'create_user':
                // Kiểm tra xem bảng users có tồn tại không
                $table_exists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
                if (!$table_exists) {
                    $error_message = "Bảng users chưa được tạo! Vui lòng chạy setup database trước.";
                    break;
                }
                
                $username = $conn->real_escape_string($_POST['username']);
                $password = password_hash('default123', PASSWORD_DEFAULT); // Hash password
                $full_name = $conn->real_escape_string($_POST['full_name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone']);
                $role = $_POST['role'];
                
                $check_query = "SELECT id FROM users WHERE username = '$username'";
                if ($conn->query($check_query)->num_rows === 0) {
                    $conn->query("INSERT INTO users (username, password, full_name, email, phone, role) VALUES ('$username', '$password', '$full_name', '$email', '$phone', '$role')");
                    $success_message = "Đã tạo người dùng mới! Mật khẩu mặc định: default123";
                } else {
                    $error_message = "Username đã tồn tại!";
                }
                break;
        }
    }
}

// Get system statistics with error handling
$stats = [
    'active_users' => 0,
    'admin_count' => 0,
    'staff_count' => 0,
    'customer_count' => 0,
    'total_rooms' => 0,
    'total_bookings' => 0,
    'total_revenue' => 0,
    'total_services' => 0,
    'pending_services' => 0,
    'in_progress_services' => 0,
    'today_services' => 0
];

// Helper function to check if table exists
function tableExists(mysqli $conn, string $schema, string $table): bool {
    $schema_safe = $conn->real_escape_string($schema);
    $table_safe = $conn->real_escape_string($table);
    $sql = "
        SELECT COUNT(*) AS table_exists
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = '$schema_safe' AND TABLE_NAME = '$table_safe'
    ";
    
    $result = $conn->query($sql);
    if (!$result) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    return $row && (int)$row['table_exists'] > 0;
}

try {
    // Get database name
    $db_result = $conn->query("SELECT DATABASE()");
    $db_name = $db_result ? $db_result->fetch_row()[0] : 'hotel_management';
    if (empty($db_name)) {
        $db_name = 'hotel_management';
    }
    
    // Kiểm tra các bảng tồn tại
    $users_exists = tableExists($conn, $db_name, 'users');
    $rooms_exists = tableExists($conn, $db_name, 'rooms');
    $bookings_exists = tableExists($conn, $db_name, 'bookings');
    $room_services_exists = tableExists($conn, $db_name, 'room_services');
    
    if ($users_exists) {
        $users_result = $conn->query("
            SELECT 
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
                (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_count,
                (SELECT COUNT(*) FROM users WHERE role = 'staff') as staff_count,
                (SELECT COUNT(*) FROM users WHERE role = 'customer') as customer_count
        ");
        
        if ($users_result && $users_result->num_rows > 0) {
            $users_stats = $users_result->fetch_assoc();
            $stats['active_users'] = (int)($users_stats['active_users'] ?? 0);
            $stats['admin_count'] = (int)($users_stats['admin_count'] ?? 0);
            $stats['staff_count'] = (int)($users_stats['staff_count'] ?? 0);
            $stats['customer_count'] = (int)($users_stats['customer_count'] ?? 0);
        }
    }
    
    if ($rooms_exists) {
        $rooms_result = $conn->query("SELECT COUNT(*) as total_rooms FROM rooms");
        if ($rooms_result && $rooms_result->num_rows > 0) {
            $rooms_count = $rooms_result->fetch_assoc();
            $stats['total_rooms'] = (int)($rooms_count['total_rooms'] ?? 0);
        }
    }
    
    if ($bookings_exists) {
        $bookings_result = $conn->query("
            SELECT 
                COUNT(*) as total_bookings,
                COALESCE(SUM(total_price), 0) as total_revenue
            FROM bookings
        ");
        
        if ($bookings_result && $bookings_result->num_rows > 0) {
            $bookings_stats = $bookings_result->fetch_assoc();
            $stats['total_bookings'] = (int)($bookings_stats['total_bookings'] ?? 0);
            $stats['total_revenue'] = (float)($bookings_stats['total_revenue'] ?? 0);
        }
    }
    
    if ($room_services_exists) {
        $room_services_result = $conn->query("
            SELECT 
                COUNT(*) as total_services,
                COUNT(CASE WHEN status = 'requested' THEN 1 END) as pending_services,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_services,
                COUNT(CASE WHEN DATE(requested_at) = CURDATE() THEN 1 END) as today_services
        ");
        
        if ($room_services_result && $room_services_result->num_rows > 0) {
            $room_services_stats = $room_services_result->fetch_assoc();
            $stats['total_services'] = (int)($room_services_stats['total_services'] ?? 0);
            $stats['pending_services'] = (int)($room_services_stats['pending_services'] ?? 0);
            $stats['in_progress_services'] = (int)($room_services_stats['in_progress_services'] ?? 0);
            $stats['today_services'] = (int)($room_services_stats['today_services'] ?? 0);
        }
    }
    
} catch (Exception $e) {
    $error_message = "Lỗi khi lấy thống kê: " . $e->getMessage();
    // Stats đã được set giá trị mặc định ở đầu, không cần set lại
}

// Get all users if table exists
$users = null;
if ($users_exists) {
    try {
        $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
    } catch (Exception $e) {
        $error_message = "Lỗi khi lấy danh sách người dùng: " . $e->getMessage();
    }
}

// Recent activities
$recent_activities = null;
if ($bookings_exists && $rooms_exists) {
    try {
        $recent_activities = $conn->query("
            SELECT 
                'booking' as type,
                b.checkin as activity_date,
                g.name as user_name,
                CONCAT('Đặt phòng ', r.room_number, ' (', b.checkin, ')') as description
            FROM bookings b 
            JOIN guests g ON b.guest_id = g.id 
            JOIN rooms r ON b.room_id = r.id 
            ORDER BY b.id DESC 
            LIMIT 10
        ");
    } catch (Exception $e) {
        // Không hiển thị lỗi nếu không có dữ liệu
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/hotel-style.css">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .main-content {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
            border: none;
        }
        
        .btn-custom {
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .table-custom {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            table-layout: fixed;
            width: 100%;
        }
        
        .table-custom thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .table-custom tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-custom th:nth-child(1),
        .table-custom td:nth-child(1) {
            width: 7%;
            text-align: center;
        }
        
        .table-custom th:nth-child(2),
        .table-custom td:nth-child(2) {
            width: 18%;
        }
        
        .table-custom th:nth-child(3),
        .table-custom td:nth-child(3) {
            width: 15%;
        }
        
        .table-custom th:nth-child(4),
        .table-custom td:nth-child(4) {
            width: 20%;
        }
        
        .table-custom th:nth-child(5),
        .table-custom td:nth-child(5) {
            width: 15%;
            text-align: center;
        }
        
        .table-custom th:nth-child(6),
        .table-custom td:nth-child(6) {
            width: 15%;
            text-align: center;
        }
        
        .table-custom th:nth-child(7),
        .table-custom td:nth-child(7) {
            width: 10%;
            text-align: center;
        }
        
        .database-status {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .database-status.warning {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-color: #dc3545;
        }
        
        .database-status.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #198754;
        }
        
        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .role-admin { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .role-staff { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .role-customer { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); }
        
        .form-control-custom {
            border-radius: 0.75rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control-custom:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .alert-custom {
            border-radius: 1rem;
            border: none;
            padding: 1rem 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-2">
                        <i class="bi bi-crown-fill me-3"></i>
                        Bảng điều khiển quản trị
                    </h1>
                    <p class="lead mb-0">Quản lý toàn bộ hệ thống Hotel Management</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end">
                        <a href="../dashboard.php" class="btn btn-outline-light btn-custom">
                            <i class="bi bi-graph-up me-2"></i>Dashboard chung
                        </a>
                        <a href="../index.php" class="btn btn-light btn-custom">
                            <i class="bi bi-house-fill me-2"></i>Trang chủ
                        </a>
                        <a href="../logout.php" class="btn btn-warning btn-custom">
                            <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Database Status Check -->
        <div class="database-status <?php echo (!$users_exists || !$rooms_exists || !$bookings_exists) ? 'warning' : 'success'; ?>">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h4 class="mb-3">
                        <i class="bi bi-database-fill me-2"></i>
                        Trạng thái Database
                    </h4>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <strong>Bảng users:</strong> 
                            <span class="badge <?php echo $users_exists ? 'bg-success' : 'bg-danger'; ?> ms-2">
                                <?php echo $users_exists ? 'Đã tạo' : 'Chưa tạo'; ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Bảng rooms:</strong> 
                            <span class="badge <?php echo $rooms_exists ? 'bg-success' : 'bg-danger'; ?> ms-2">
                                <?php echo $rooms_exists ? 'Đã tạo' : 'Chưa tạo'; ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Bảng bookings:</strong> 
                            <span class="badge <?php echo $bookings_exists ? 'bg-success' : 'bg-danger'; ?> ms-2">
                                <?php echo $bookings_exists ? 'Đã tạo' : 'Chưa tạo'; ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Bảng services:</strong> 
                            <span class="badge <?php echo $room_services_exists ? 'bg-success' : 'bg-danger'; ?> ms-2">
                                <?php echo $room_services_exists ? 'Đã tạo' : 'Chưa tạo'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!$users_exists || !$rooms_exists || !$bookings_exists || !$room_services_exists): ?>
                        <a href="../setup_database.php" class="btn btn-warning btn-custom">
                            <i class="bi bi-tools me-2"></i>Thiết lập Database
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-primary"><?php echo $stats['active_users']; ?></div>
                            <div class="stats-label">Người dùng hoạt động</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-success"><?php echo $stats['total_bookings']; ?></div>
                            <div class="stats-label">Tổng booking</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-warning"><?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></div>
                            <div class="stats-label">Tổng doanh thu (VND)</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-building-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-info"><?php echo $stats['total_rooms']; ?></div>
                            <div class="stats-label">Tổng phòng</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Services Statistics -->
        <?php if ($room_services_exists): ?>
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-primary"><?php echo $stats['total_services']; ?></div>
                            <div class="stats-label">Tổng dịch vụ</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-warning"><?php echo $stats['pending_services']; ?></div>
                            <div class="stats-label">Chờ xử lý</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-arrow-clockwise"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-info"><?php echo $stats['in_progress_services']; ?></div>
                            <div class="stats-label">Đang thực hiện</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number text-success"><?php echo $stats['today_services']; ?></div>
                            <div class="stats-label">Hôm nay</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Roles Statistics -->
        <?php if ($users_exists): ?>
        <div class="main-content">
            <div class="card-header-custom">
                <h3 class="mb-0">
                    <i class="bi bi-pie-chart-fill me-2"></i>
                    Thống kê người dùng theo vai trò
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                     <div class="col-md-4">
                         <div class="text-center p-4 rounded-3" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white;">
                             <h2 class="mb-2"><?php echo $stats['admin_count']; ?></h2>
                             <p class="mb-0 fs-5">Quản trị viên</p>
                         </div>
                     </div>
                     <div class="col-md-4">
                         <div class="text-center p-4 rounded-3" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white;">
                             <h2 class="mb-2"><?php echo $stats['staff_count']; ?></h2>
                             <p class="mb-0 fs-5">Nhân viên</p>
                         </div>
                     </div>
                     <div class="col-md-4">
                         <div class="text-center p-4 rounded-3" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white;">
                             <h2 class="mb-2"><?php echo $stats['customer_count']; ?></h2>
                             <p class="mb-0 fs-5">Khách hàng</p>
                         </div>
                     </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Management -->
        <?php if ($users_exists): ?>
        <div class="main-content">
            <div class="card-header-custom">
                <h3 class="mb-0">
                    <i class="bi bi-people-fill me-2"></i>
                    Quản lý người dùng
                </h3>
            </div>
            <div class="card-body p-4">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-custom">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Create User Form -->
                <div class="bg-light rounded-3 p-4 mb-4">
                    <h5 class="mb-3">
                        <i class="bi bi-plus-circle-fill text-success me-2"></i>
                        Tạo người dùng mới
                    </h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_user">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username:</label>
                                <input type="text" name="username" class="form-control form-control-custom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Họ tên:</label>
                                <input type="text" name="full_name" class="form-control form-control-custom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email:</label>
                                <input type="email" name="email" class="form-control form-control-custom">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Số điện thoại:</label>
                                <input type="text" name="phone" class="form-control form-control-custom">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vai trò:</label>
                                <select name="role" class="form-select form-control-custom" required>
                                                                         <option value="customer"><i class="fas fa-user"></i> Khách hàng</option>
                                     <option value="staff"><i class="fas fa-user-tie"></i> Nhân viên</option>
                                     <option value="admin"><i class="fas fa-crown"></i> Quản trị viên</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-custom">
                                    <i class="bi bi-plus-circle-fill me-2"></i>Tạo người dùng
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Users Table -->
                <?php if ($users): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                            <th>Vai trò</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?php echo $user['id']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo getRoleDisplayName($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> btn-custom"
                                                onclick="return confirm('Bạn có chắc muốn <?php echo $user['status'] === 'active' ? 'khóa' : 'mở khóa'; ?> tài khoản này?')">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <i class="bi bi-lock-fill me-1"></i>Khóa
                                            <?php else: ?>
                                                <i class="bi bi-unlock-fill me-1"></i>Mở khóa
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="badge bg-info">Tài khoản hiện tại</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Actions -->
        <div class="main-content">
            <div class="card-header-custom">
                <h3 class="mb-0">
                    <i class="bi bi-gear-fill me-2"></i>
                    Hành động hệ thống
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <a href="../setup_database.php" class="btn btn-warning btn-custom w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="bi bi-tools display-6 mb-3"></i>
                            <span class="fw-semibold">Thiết lập Database</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="../quick_setup.php" class="btn btn-info btn-custom w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="bi bi-lightning-fill display-6 mb-3"></i>
                            <span class="fw-semibold">Thiết lập nhanh</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="../reports.php" class="btn btn-success btn-custom w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="bi bi-graph-up display-6 mb-3"></i>
                            <span class="fw-semibold">Báo cáo chi tiết</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="../manage_bookings.php" class="btn btn-primary btn-custom w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="bi bi-calendar-event display-6 mb-3"></i>
                            <span class="fw-semibold">Quản lý đặt phòng</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center py-4 text-white">
            <p class="mb-0">
                <i class="bi bi-crown-fill me-2"></i>
                Admin Panel - Hotel Management System v2.0
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
