<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require admin permission only
requireLogin();
requirePermission('admin');

$page_title = "Hotel Settings - Hotel Management";
$success_message = "";
$error_message = "";

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $hotel_name = trim($_POST['hotel_name']);
    $hotel_address = trim($_POST['hotel_address']);
    $hotel_phone = trim($_POST['hotel_phone']);
    $hotel_email = trim($_POST['hotel_email']);
    $checkin_time = $_POST['checkin_time'];
    $checkout_time = $_POST['checkout_time'];
    $currency = $_POST['currency'];
    $tax_rate = (float)$_POST['tax_rate'];
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    try {
        // Create settings table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS hotel_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Update or insert settings
        $settings = [
            'hotel_name' => $hotel_name,
            'hotel_address' => $hotel_address,
            'hotel_phone' => $hotel_phone,
            'hotel_email' => $hotel_email,
            'checkin_time' => $checkin_time,
            'checkout_time' => $checkout_time,
            'currency' => $currency,
            'tax_rate' => $tax_rate,
            'maintenance_mode' => $maintenance_mode
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO hotel_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $success_message = "✅ Cài đặt khách sạn đã được cập nhật thành công!";
        
    } catch (Exception $e) {
        $error_message = "❌ Lỗi: " . $e->getMessage();
    }
}

// Load current settings
$current_settings = [];
$settings_query = $conn->query("SELECT setting_key, setting_value FROM hotel_settings");
if ($settings_query) {
    while ($setting = $settings_query->fetch_assoc()) {
        $current_settings[$setting['setting_key']] = $setting['setting_value'];
    }
}

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-cog"></i> Hotel Settings</h2>
        <p class="text-muted">Cài đặt hệ thống và thông tin khách sạn</p>
    </div>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<!-- General Settings -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0">🏨 Thông tin chung</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="hotel_name" class="form-label">
                        <i class="fas fa-hotel"></i> Tên khách sạn *
                        </label>
                    <input type="text" class="form-control" id="hotel_name" name="hotel_name" 
                           value="<?php echo htmlspecialchars($current_settings['hotel_name'] ?? 'Hotel Management System'); ?>" required>
                    <div class="invalid-feedback">
                        Vui lòng nhập tên khách sạn
        </div>
    </div>
    
                <div class="col-md-6">
                    <label for="hotel_phone" class="form-label">
                        <i class="fas fa-phone"></i> Số điện thoại
                    </label>
                    <input type="tel" class="form-control" id="hotel_phone" name="hotel_phone" 
                           value="<?php echo htmlspecialchars($current_settings['hotel_phone'] ?? ''); ?>">
                </div>
                
                <div class="col-12">
                    <label for="hotel_address" class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Địa chỉ
                    </label>
                    <textarea class="form-control" id="hotel_address" name="hotel_address" rows="3"
                              placeholder="Nhập địa chỉ đầy đủ của khách sạn..."><?php echo htmlspecialchars($current_settings['hotel_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-6">
                    <label for="hotel_email" class="form-label">
                        <i class="fas fa-envelope"></i> Email liên hệ
                    </label>
                    <input type="email" class="form-control" id="hotel_email" name="hotel_email" 
                           value="<?php echo htmlspecialchars($current_settings['hotel_email'] ?? ''); ?>">
        </div>
    </div>
    
            <hr class="my-4">
            
            <!-- Operational Settings -->
            <h5 class="text-primary mb-3">🕐 Cài đặt hoạt động</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="checkin_time" class="form-label">
                        <i class="fas fa-clock"></i> Giờ check-in
                    </label>
                    <input type="time" class="form-control" id="checkin_time" name="checkin_time" 
                           value="<?php echo $current_settings['checkin_time'] ?? '14:00'; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="checkout_time" class="form-label">
                        <i class="fas fa-clock"></i> Giờ check-out
                    </label>
                    <input type="time" class="form-control" id="checkout_time" name="checkout_time" 
                           value="<?php echo $current_settings['checkout_time'] ?? '12:00'; ?>">
    </div>
    
                <div class="col-md-3">
                    <label for="currency" class="form-label">
                        <i class="fas fa-exchange-alt"></i> Đơn vị tiền tệ
                    </label>
                    <select class="form-select" id="currency" name="currency">
                        <option value="VND" <?php echo ($current_settings['currency'] ?? 'VND') == 'VND' ? 'selected' : ''; ?>>🇻🇳 VND (Việt Nam)</option>
                        <option value="USD" <?php echo ($current_settings['currency'] ?? 'VND') == 'USD' ? 'selected' : ''; ?>>🇺🇸 USD (Đô la Mỹ)</option>
                        <option value="EUR" <?php echo ($current_settings['currency'] ?? 'VND') == 'EUR' ? 'selected' : ''; ?>>🇪🇺 EUR (Euro)</option>
                        <option value="SGD" <?php echo ($current_settings['currency'] ?? 'VND') == 'SGD' ? 'selected' : ''; ?>>🇸🇬 SGD (Đô la Singapore)</option>
                    </select>
        </div>
        
                <div class="col-md-3">
                    <label for="tax_rate" class="form-label">
                        <i class="fas fa-percent"></i> Thuế suất (%)
                    </label>
                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                           value="<?php echo $current_settings['tax_rate'] ?? '10'; ?>" 
                           min="0" max="100" step="0.1">
        </div>
    </div>
    
            <hr class="my-4">
            
            <!-- System Settings -->
            <h5 class="text-primary mb-3">🔧 Cài đặt hệ thống</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                               <?php echo ($current_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">
                            <i class="fas fa-tools"></i> Chế độ bảo trì
                    </label>
                        <div class="form-text">Khi bật, chỉ admin mới có thể truy cập hệ thống</div>
                </div>
        </div>
    </div>
    
            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> 💾 Lưu cài đặt
        </button>
    </div>
</form>
    </div>
</div>

<!-- System Information -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-info-circle"></i> Thông tin hệ thống</h3>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info"><i class="fas fa-chart-bar"></i> Thống kê cơ sở dữ liệu</h6>
    <?php
                        $db_stats = $conn->query("
                            SELECT 
                                (SELECT COUNT(*) FROM users) as total_users,
                                (SELECT COUNT(*) FROM rooms) as total_rooms,
                                (SELECT COUNT(*) FROM bookings) as total_bookings,
                                (SELECT COUNT(*) FROM guests) as total_guests
                        ")->fetch_assoc();
                        ?>
                        <ul class="list-unstyled">
                            <li><strong><i class="fas fa-users"></i> Người dùng:</strong> <?php echo $db_stats['total_users']; ?></li>
                            <li><strong><i class="fas fa-database"></i> MySQL Version:</strong> <?php echo $conn->server_info; ?></li>
                            <li><strong>📁 Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></li>
                            <li><strong>⏰ Timezone:</strong> <?php echo date_default_timezone_get(); ?></li>
                        </ul>
                    </div>
        </div>
        </div>
        
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success">🔧 Thông tin kỹ thuật</h6>
                        <ul class="list-unstyled">
                            <li><strong>🌐 PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                            <li><strong><i class="fas fa-database"></i> MySQL Version:</strong> <?php echo $conn->server_info; ?></li>
                            <li><strong>📁 Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></li>
                            <li><strong>⏰ Timezone:</strong> <?php echo date_default_timezone_get(); ?></li>
                        </ul>
        </div>
        </div>
        </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card shadow mb-4">
    <div class="card-header bg-secondary text-white">
        <h3 class="card-title fs-5 mb-0">⚡ Thao tác nhanh</h3>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> <i class="fas fa-chart-bar"></i> Dashboard
            </a>
            <a href="employee_management.php" class="btn btn-success">
                <i class="fas fa-users"></i> <i class="fas fa-users"></i> Quản lý nhân viên
            </a>
            <a href="reports.php" class="btn btn-warning">
                <i class="fas fa-chart-bar"></i> <i class="fas fa-chart-line"></i> Báo cáo
            </a>
            <a href="advanced_reports.php" class="btn btn-info">
                <i class="fas fa-chart-line"></i> <i class="fas fa-chart-bar"></i> Báo cáo nâng cao
            </a>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
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
    
    // Auto-save draft (optional feature)
    let autoSaveTimer;
    $('input, select, textarea').on('input change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // You can implement auto-save functionality here
            console.log('Auto-save triggered');
        }, 2000);
    });
});
</script>
