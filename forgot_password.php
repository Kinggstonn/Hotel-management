<?php
session_start();
include "db.php";

$page_title = "Quên mật khẩu - Hotel Management";

$error_message = "";
$success_message = "";

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($email) || empty($phone)) {
        $error_message = "Vui lòng nhập đầy đủ email và số điện thoại!";
    } else {
        // Clean input data
        $email = trim($email);
        $phone = preg_replace('/\D+/', '', $phone); // remove all formatting chars
        
        // Debug: Log the search parameters
        error_log("Forgot password search - Email: '$email', Phone: '$phone'");
        
        // Check if user exists with matching email and phone
        $user_query = $conn->prepare("SELECT id, username, full_name, email, phone FROM users WHERE email = ? AND phone = ?");
        $user_query->bind_param("ss", $email, $phone);
        $user_query->execute();
        $result = $user_query->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Store reset token in database
            $token_query = $conn->prepare("
                INSERT INTO password_resets (user_id, email, token, expires_at, created_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                expires_at = VALUES(expires_at), 
                created_at = NOW(),
                used = 0
            ");
            $token_query->bind_param("isss", $user['id'], $email, $reset_token, $expires_at);
            
            if ($token_query->execute()) {
                // Lưu thông tin vào session để chuyển thẳng sang bước đặt mật khẩu mới
                $_SESSION['password_reset_context'] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'token' => $reset_token,
                    'expires_at' => strtotime($expires_at)
                ];
                
                header('Location: reset_password.php');
                exit();
            } else {
                $error_message = "Có lỗi xảy ra khi tạo link đặt lại mật khẩu!";
            }
        } else {
            $error_message = "Không tìm thấy tài khoản với email và số điện thoại này!";
        }
    }
}

include "includes/header.php";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="card-title fs-3 mb-0">
                        <i class="fas fa-key"></i> Quên mật khẩu
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">Nhập thông tin để đặt lại mật khẩu</p>
                </div>
                <div class="card-body p-4">
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

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope text-primary"></i> Email *
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   placeholder="Nhập email của bạn" required>
                            <div class="invalid-feedback">
                                Vui lòng nhập email hợp lệ
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone text-primary"></i> Số điện thoại *
                            </label>
                            <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                                   placeholder="Nhập số điện thoại" required>
                            <div class="invalid-feedback">
                                Vui lòng nhập số điện thoại
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="reset_password" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Gửi link đặt lại mật khẩu
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-muted mb-2">Nhớ mật khẩu rồi?</p>
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

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

// Phone number formatting
document.getElementById('phone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.substring(0, 3) + ' ' + value.substring(3);
        } else if (value.length <= 10) {
            value = value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6);
        } else {
            value = value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6, 10);
        }
    }
    this.value = value;
});
</script>
