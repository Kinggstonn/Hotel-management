<?php
session_start();
include "db.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    include "auth/auth_functions.php";
    redirectAfterLogin();
}

$error_message = "";
$success_message = "";

// Check if users table exists
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows === 0) {
        $error_message = "Database chưa được thiết lập! Vui lòng chạy setup database trước.";
    }
} catch (Exception $e) {
    $error_message = "Không thể kết nối database hoặc bảng users chưa tồn tại!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error_message)) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        try {
            // Find user
            $query = "SELECT * FROM users WHERE username = ? AND status = 'active'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
        } catch (Exception $e) {
            $error_message = "Lỗi truy vấn database: " . $e->getMessage();
        }
        
        if (!empty($result) && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check password - support both hashed passwords and demo accounts
            $password_valid = false;
            
            // First try password_verify for hashed passwords (registered users)
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            }
            // Fallback for demo accounts with plain passwords
            else {
                $demo_passwords = [
                    'admin' => 'admin123',
                    'staff' => 'staff123', 
                    'customer' => 'customer123'
                ];
                
                if (isset($demo_passwords[$username]) && $password === $demo_passwords[$username]) {
                    $password_valid = true;
                }
            }
            
            if ($password_valid) {
                // Update last login
                $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['login_time'] = time();
                
                $success_message = "Đăng nhập thành công!";
                
                // Redirect immediately without delay
                include "auth/auth_functions.php";
                $redirect_url = ($user['role'] === 'admin' ? 'admin/admin_dashboard.php' : 
                               ($user['role'] === 'staff' ? 'dashboard.php' : 'index.php'));
                header("Location: $redirect_url");
                exit();
            } else {
                $error_message = "Mật khẩu không chính xác!";
            }
        } else {
            $error_message = "Tài khoản không tồn tại hoặc đã bị khóa!";
        }
    } else {
        $error_message = "Vui lòng nhập đầy đủ thông tin!";
    }
}

$page_title = "Đăng nhập - Hotel Management";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/hotel-icon.ico">
</head>
<body class="bg-light">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="card shadow-lg border-0" style="max-width: 500px; width: 100%;">
            <div class="card-body p-5">
                <!-- Login Header -->
                <div class="text-center mb-4">
                    <h1 class="display-5 fw-bold text-primary mb-2">
                        <i class="fas fa-hotel text-primary"></i> Hotel Management
                    </h1>
                    <p class="text-muted fs-6">Đăng nhập vào hệ thống</p>
                </div>
                
                
                <!-- Error/Success Messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Tên đăng nhập
                        </label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" 
                               placeholder="Nhập tên đăng nhập" required>
                        <div class="invalid-feedback">
                            Vui lòng nhập tên đăng nhập
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Mật khẩu
                        </label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" 
                               placeholder="Nhập mật khẩu" required>
                        <div class="invalid-feedback">
                            Vui lòng nhập mật khẩu
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </button>
                </form>
                
                <!-- Forgot Password Link -->
                <div class="text-center mb-3">
                    <a href="forgot_password.php" class="text-decoration-none">
                        <i class="fas fa-key"></i> Quên mật khẩu?
                    </a>
                </div>
                
                <!-- Quick Login Buttons -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickLogin('admin', 'admin123')">
                        <i class="fas fa-id-badge"></i> Đăng nhập Admin
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="quickLogin('staff', 'staff123')">
                        <i class="fas fa-user-tie"></i> Đăng nhập Staff
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="quickLogin('customer', 'customer123')">
                        <i class="fas fa-user"></i> Đăng nhập Customer
                    </button>
                </div>
                
                <!-- Footer Links -->
                <div class="text-center mt-4">
                    <a href="index.php" class="text-decoration-none text-muted me-3">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a href="register.php" class="text-decoration-none text-muted">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Quick login function
        function quickLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.querySelector('form').submit();
        }
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
