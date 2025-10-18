<?php
session_start();
include "db.php";

$page_title = "Đặt lại mật khẩu - Hotel Management";

$error_message = "";
$success_message = "";

$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$user_info = null;

// Validate token
if (!empty($token)) {
    $token_query = $conn->prepare("
        SELECT pr.*, u.username, u.full_name, u.email 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
    ");
    $token_query->bind_param("s", $token);
    $token_query->execute();
    $result = $token_query->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user_info = $result->fetch_assoc();
    } else {
        $error_message = "Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!";
    }
} else {
    $error_message = "Thiếu token đặt lại mật khẩu!";
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password']) && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "Vui lòng nhập đầy đủ mật khẩu mới!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Mật khẩu phải có ít nhất 6 ký tự!";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Mật khẩu xác nhận không khớp!";
    } else {
        try {
            $conn->begin_transaction();
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user password
            $update_query = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_query->bind_param("si", $hashed_password, $user_info['user_id']);
            
            if ($update_query->execute()) {
                // Mark token as used
                $mark_used_query = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $mark_used_query->bind_param("s", $token);
                $mark_used_query->execute();
                
                $conn->commit();
                
                $success_message = "Đặt lại mật khẩu thành công! Bạn có thể đăng nhập với mật khẩu mới.";
                $valid_token = false; // Prevent further use
            } else {
                $conn->rollback();
                $error_message = "Có lỗi xảy ra khi cập nhật mật khẩu!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

include "includes/header.php";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <h2 class="card-title fs-3 mb-0">
                        <i class="fas fa-lock"></i> Đặt lại mật khẩu
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">Nhập mật khẩu mới cho tài khoản của bạn</p>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($valid_token && empty($success_message)): ?>
                        <!-- User Info -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-user"></i> Thông tin tài khoản:</h6>
                            <p class="mb-1"><strong>Tên:</strong> <?php echo htmlspecialchars($user_info['full_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                            <p class="mb-0"><strong>Username:</strong> <?php echo htmlspecialchars($user_info['username']); ?></p>
                        </div>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key text-success"></i> Mật khẩu mới *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg" id="new_password" 
                                           name="new_password" placeholder="Nhập mật khẩu mới" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye" id="new_password_icon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Mật khẩu phải có ít nhất 6 ký tự
                                </div>
                                <div class="form-text">
                                    <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-key text-success"></i> Xác nhận mật khẩu *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg" id="confirm_password" 
                                           name="confirm_password" placeholder="Nhập lại mật khẩu mới" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirm_password_icon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Mật khẩu xác nhận không khớp
                                </div>
                            </div>

                            <!-- Password Strength Indicator -->
                            <div class="mb-4">
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <small class="strength-text" id="strengthText">Nhập mật khẩu để kiểm tra độ mạnh</small>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="update_password" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Cập nhật mật khẩu
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (!$valid_token && empty($success_message)): ?>
                        <div class="text-center">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h5>Link không hợp lệ</h5>
                                <p class="mb-0">Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.</p>
                            </div>
                            
                            <a href="forgot_password.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Yêu cầu link mới
                            </a>
                        </div>
                    <?php endif; ?>

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

<style>
.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak { background-color: #dc3545; }
.strength-fair { background-color: #ffc107; }
.strength-good { background-color: #17a2b8; }
.strength-strong { background-color: #28a745; }
</style>

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

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    let strengthLabel = '';
    let strengthClass = '';
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    switch (strength) {
        case 0:
        case 1:
            strengthLabel = 'Rất yếu';
            strengthClass = 'strength-weak';
            break;
        case 2:
            strengthLabel = 'Yếu';
            strengthClass = 'strength-fair';
            break;
        case 3:
            strengthLabel = 'Trung bình';
            strengthClass = 'strength-good';
            break;
        case 4:
        case 5:
            strengthLabel = 'Mạnh';
            strengthClass = 'strength-strong';
            break;
    }
    
    const percentage = (strength / 5) * 100;
    strengthFill.style.width = percentage + '%';
    strengthFill.className = 'strength-fill ' + strengthClass;
    strengthText.textContent = 'Độ mạnh: ' + strengthLabel;
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Mật khẩu xác nhận không khớp');
    } else {
        this.setCustomValidity('');
    }
});
</script>
