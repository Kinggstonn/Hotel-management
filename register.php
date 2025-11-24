<?php 
include "db.php"; 
include "auth/auth_functions.php";

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$page_title = "Đăng ký tài khoản - Hotel Management";
$success_message = "";
$error_message = "";
$form_data = array(); // To preserve form data on validation errors

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Preserve form data
    $form_data = array(
        'username' => $username,
        'full_name' => $full_name,
        'email' => $email,
        'phone' => $phone
    );
    
    // Basic validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($phone)) {
        $error_message = "Vui lòng điền đầy đủ thông tin!";
    } elseif (strlen($username) < 3) {
        $error_message = "Tên đăng nhập phải có ít nhất 3 ký tự!";
    } elseif (strlen($password) < 6) {
        $error_message = "Mật khẩu phải có ít nhất 6 ký tự!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Mật khẩu xác nhận không khớp!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ!";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error_message = "Số điện thoại phải có 10-11 chữ số!";
    } else {
        try {
            // Check if username already exists
            $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_username->bind_param("s", $username);
            $check_username->execute();
            if ($check_username->get_result()->num_rows > 0) {
                $error_message = "Tên đăng nhập đã tồn tại! Vui lòng chọn tên khác.";
            } else {
                // Check if email already exists
                $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                if ($check_email->get_result()->num_rows > 0) {
                    $error_message = "Email đã được sử dụng! Vui lòng dùng email khác.";
                } else {
                    // Hash password and insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert_user = $conn->prepare("
                        INSERT INTO users (username, password, full_name, email, phone, role, status) 
                        VALUES (?, ?, ?, ?, ?, 'customer', 'active')
                    ");
                    $insert_user->bind_param("sssss", $username, $hashed_password, $full_name, $email, $phone);
                    
                    if ($insert_user->execute()) {
                        $success_message = "🎉 Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
                        $form_data = array(); // Clear form data on success
                    } else {
                        $error_message = "Có lỗi xảy ra khi tạo tài khoản. Vui lòng thử lại!";
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

// Check if users table exists
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows === 0) {
        $error_message = "Database chưa được thiết lập! Vui lòng chạy setup database trước.";
    }
} catch (Exception $e) {
    $error_message = "Không thể kết nối database hoặc bảng users chưa tồn tại!";
}

include "includes/header.php"; 
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary">🔐 Đăng ký tài khoản mới</h2>
        <p class="text-muted">Tạo tài khoản khách hàng để đặt phòng và quản lý booking</p>
    </div>
</div>

<!-- Database setup error -->
<?php if (!empty($error_message) && strpos($error_message, 'Database chưa được thiết lập') !== false): ?>
<div class="alert alert-danger text-center mb-4">
    <h3 class="fs-4"><i class="fas fa-exclamation-triangle"></i> Database chưa được thiết lập!</h3>
    <p>Vui lòng thiết lập database với hệ thống login trước khi đăng ký.</p>
    <a href="setup_database.php" class="btn btn-outline-light">
        🔧 Thiết lập Database ngay
    </a>
</div>
<?php else: ?>

<!-- Success message -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success text-center mb-4">
    <div class="fs-5"><?php echo $success_message; ?></div>
    <div class="mt-3">
        <a href="login.php" class="btn btn-success rounded-pill me-2">🚪 Đăng nhập ngay</a>
        <a href="index.php" class="btn btn-primary rounded-pill">🏠 Về trang chủ</a>
    </div>
</div>
<?php endif; ?>

<!-- Error message -->
<?php if (!empty($error_message) && strpos($error_message, 'Database chưa được thiết lập') === false): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Registration Form -->
<?php if (empty($success_message)): ?>
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0">📝 Thông tin đăng ký</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate style="max-width: 600px; margin: 0 auto;">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">
                        👤 Tên đăng nhập *
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                           required minlength="3"
                           placeholder="Nhập tên đăng nhập (ít nhất 3 ký tự)">
                    <div class="invalid-feedback">
                        Tên đăng nhập phải có ít nhất 3 ký tự
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="full_name" class="form-label">
                        <i class="fas fa-id-badge"></i> <i class="fas fa-tag"></i> Họ và tên *
                    </label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>"
                           required 
                           placeholder="Nhập họ và tên đầy đủ">
                    <div class="invalid-feedback">
                        Vui lòng nhập họ và tên
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">
                        📧 Email *
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                           required 
                           placeholder="email@example.com">
                    <div class="invalid-feedback">
                        Vui lòng nhập email hợp lệ
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="phone" class="form-label">
                        📱 Số điện thoại *
                    </label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                           required pattern="[0-9]{10,11}"
                           placeholder="0123456789 (10-11 chữ số)">
                    <div class="invalid-feedback">
                        Số điện thoại phải có 10-11 chữ số
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="password" class="form-label">
                        <i class="fas fa-key"></i> <i class="fas fa-lock"></i> Mật khẩu *
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           required minlength="6"
                           placeholder="Nhập mật khẩu (ít nhất 6 ký tự)">
                    <div class="invalid-feedback">
                        Mật khẩu phải có ít nhất 6 ký tự
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> <i class="fas fa-lock"></i> Xác nhận mật khẩu *
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           required 
                           placeholder="Nhập lại mật khẩu">
                    <div class="invalid-feedback">
                        Vui lòng xác nhận mật khẩu
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="register" class="btn btn-primary btn-lg">
                    🎉 Đăng ký tài khoản
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Login Link -->
<div class="text-center mt-4">
    <p class="text-muted">Đã có tài khoản? 
        <a href="login.php" class="text-decoration-none text-primary fw-bold">Đăng nhập ngay</a>
    </p>
</div>

<?php endif; ?>

<?php endif; ?>

<?php include "includes/footer.php"; ?>

<script>
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

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Mật khẩu xác nhận không khớp!');
    } else {
        this.setCustomValidity('');
    }
});
</script>
