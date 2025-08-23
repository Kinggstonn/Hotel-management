<?php
// Check if auth functions are needed
if (!function_exists('isLoggedIn')) {
    if (file_exists('auth/auth_functions.php')) {
        include_once 'auth/auth_functions.php';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Hotel Management System'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Free 6.4.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/hotel-style.css">
    <link rel="stylesheet" href="assets/css/fontawesome-custom.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/hotel-icon.ico">
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="Made by Kingston">
    

</head>
<body class="bg-light">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg sticky-top">
        <div class="container">
            <!-- Brand/Logo -->
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-hotel me-2"></i>
                Hotel Management
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Home/Rooms -->
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Danh sách phòng
                        </a>
                    </li>
                    
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- Dashboard -->
                        <?php if (hasPermission('view_reports') || getUserRole() === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-chart-line me-1"></i>Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Reports -->
                        <?php if (hasPermission('view_reports')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="reports.php">
                                    <i class="fas fa-chart-bar me-1"></i>Báo cáo
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="calendar_view.php">
                                    <i class="fas fa-calendar-alt me-1"></i>Lịch
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Management -->
                        <?php if (getUserRole() === 'admin' || getUserRole() === 'staff'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cogs me-1"></i>Quản lý
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="manage_bookings.php">
                                        <i class="fas fa-calendar-check me-2"></i>Quản lý Booking
                                    </a></li>
                                    <li><a class="dropdown-item" href="checkin_checkout.php">
                                        <i class="fas fa-building me-2"></i>Check-in/out
                                    </a></li>
                                    <li><a class="dropdown-item" href="payment_billing.php">
                                        <i class="fas fa-credit-card me-2"></i>Thanh toán
                                    </a></li>
                                    <li><a class="dropdown-item" href="room_service.php">
                                        <i class="fas fa-bell me-2"></i>Dịch vụ
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Admin Panel -->
                        <?php if (getUserRole() === 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-crown me-1"></i>Quản trị
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="admin/admin_dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="employee_management.php">
                                        <i class="fas fa-users me-2"></i>Nhân viên
                                    </a></li>
                                    <li><a class="dropdown-item" href="advanced_reports.php">
                                        <i class="fas fa-chart-pie me-2"></i>Báo cáo nâng cao
                                    </a></li>
                                    <li><a class="dropdown-item" href="hotel_settings.php">
                                        <i class="fas fa-cog me-2"></i>Cài đặt
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="setup_database.php">
                                        <i class="fas fa-database me-2"></i>Thiết lập DB
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Customer Menu -->
                        <?php if (getUserRole() === 'customer'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="my_bookings.php">
                                    <i class="fas fa-calendar-check me-1"></i>Booking của tôi
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- Right Side Navigation -->
                <ul class="navbar-nav">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- User Info Dropdown -->
                        <?php 
                        $user = getUserInfo();
                        $role_class = 'role-' . $user['role'];
                        $role_display = getRoleDisplayName($user['role']);
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>
                                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['full_name']); ?>
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><span class="dropdown-item-text">
                                    <i class="fas fa-shield-alt me-2"></i>Vai trò: 
                                    <span class="badge <?php echo $role_class; ?> ms-1"><?php echo $role_display; ?></span>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest Navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-key me-1"></i>Đăng nhập
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Đăng ký
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-4">
        <!-- Page Header -->
        <header class="text-center mb-4">
            <h1 class="display-4 fw-bold text-primary mb-2">
                <i class="fas fa-hotel text-primary"></i> Hotel Management System
            </h1>
            <p class="lead text-muted">Hệ thống quản lý khách sạn</p>
        </header>
        
        <main>
        
        <!-- Bootstrap 5 JavaScript Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- jQuery (required for DataTables) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        
        <!-- DataTables JavaScript -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
