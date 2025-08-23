<?php
include "db.php";

$page_title = "Thiết lập Database - Hotel Management";
ob_start(); // Start output buffering

// Tạo database nếu chưa có
$conn_temp = new mysqli($host, $user, $pass);
$conn_temp->query("CREATE DATABASE IF NOT EXISTS hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn_temp->close();

// Kết nối lại với database đã tạo
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Tạo tất cả các bảng cần thiết
$sql_tables = "
-- Users table (Authentication & Authorization)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Rooms table (Enhanced with floor, price)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    type ENUM('VIP', 'Thường') DEFAULT 'Thường',
    floor INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'booked', 'occupied', 'cleaning', 'maintenance') DEFAULT 'available',
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Guests table (Linked to users)
CREATE TABLE IF NOT EXISTS guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    id_card VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Bookings table (Enhanced with payment status, created_at)
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    checkin DATE NOT NULL,
    checkout DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('booked', 'checked_in', 'checked_out', 'completed', 'cancelled', 'early_checkout') DEFAULT 'booked',
    payment_status ENUM('unpaid', 'paid', 'partial', 'refunded') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Payments table (Payment & Billing Management)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'momo', 'zalopay') DEFAULT 'cash',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Hotel Settings table (Settings Management)
CREATE TABLE IF NOT EXISTS hotel_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
";

// Thực thi tạo bảng
$queries = explode(';', $sql_tables);
foreach($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($conn->query($query)) {
            echo "<div class='text-success'><i class='bi bi-check-circle-fill'></i> Thực thi thành công: " . substr($query, 0, 50) . "...</div>";
        } else {
            echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi: " . $conn->error . "</div>";
        }
    }
}

// Xóa dữ liệu cũ (tắt foreign key check tạm thời)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DELETE FROM payments");
$conn->query("DELETE FROM bookings");
$conn->query("DELETE FROM guests");
$conn->query("DELETE FROM rooms");
$conn->query("DELETE FROM users");
$conn->query("DELETE FROM hotel_settings");
$conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE rooms AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE bookings AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE guests AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE payments AUTO_INCREMENT = 1");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Thêm dữ liệu mẫu cho users (demo accounts)
$users_data = [
    ['admin', 'admin123', 'Administrator', 'admin@hotel.com', '0123456789', 'admin'],
    ['staff', 'staff123', 'Hotel Staff', 'staff@hotel.com', '0123456790', 'staff'],
    ['customer', 'customer123', 'Customer Demo', 'customer@hotel.com', '0123456791', 'customer']
];

$success_users = 0;
foreach ($users_data as $user) {
    $username = $conn->real_escape_string($user[0]);
    $password = password_hash($user[1], PASSWORD_DEFAULT);
    $full_name = $conn->real_escape_string($user[2]);
    $email = $conn->real_escape_string($user[3]);
    $phone = $conn->real_escape_string($user[4]);
    $role = $conn->real_escape_string($user[5]);
    
    $sql = "INSERT INTO users (username, password, full_name, email, phone, role, status) 
            VALUES ('$username', '$password', '$full_name', '$email', '$phone', '$role', 'active')";
    
    if ($conn->query($sql)) {
        $success_users++;
        echo "<div class='text-success'><i class='bi bi-check-circle-fill'></i> Thêm user thành công: $username</div>";
    } else {
        echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi thêm user $username: " . $conn->error . "</div>";
    }
}

// Thêm dữ liệu phòng: 3 tầng, mỗi tầng 6 phòng (4 thường + 2 VIP)
$rooms_data = [];

for ($floor = 1; $floor <= 3; $floor++) {
    $room_counter = 1;
    
    // 4 phòng thường mỗi tầng
    for ($i = 1; $i <= 4; $i++) {
        $room_number = $floor . str_pad($room_counter, 2, '0', STR_PAD_LEFT);
        $rooms_data[] = [
            'room_number' => $room_number,
            'floor' => $floor,
            'type' => 'Thường',
            'status' => 'available',
            'price' => 500000 // 500k VND/đêm
        ];
        $room_counter++;
    }
    
    // 2 phòng VIP mỗi tầng  
    for ($i = 1; $i <= 2; $i++) {
        $room_number = $floor . str_pad($room_counter, 2, '0', STR_PAD_LEFT);
        $rooms_data[] = [
            'room_number' => $room_number,
            'floor' => $floor,
            'type' => 'VIP',
            'status' => 'available',
            'price' => 1000000 // 1M VND/đêm
        ];
        $room_counter++;
    }
}

// Insert dữ liệu phòng
$success_rooms = 0;
foreach ($rooms_data as $room) {
    $sql = "INSERT INTO rooms (room_number, floor, type, status, price) 
            VALUES ('{$room['room_number']}', {$room['floor']}, '{$room['type']}', '{$room['status']}', {$room['price']})";
    if ($conn->query($sql)) {
        $success_rooms++;
    } else {
        echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi thêm phòng {$room['room_number']}: " . $conn->error . "</div>";
    }
}

// Thêm dữ liệu mẫu cho guests
$guests_data = [
    [3, 'Nguyễn Văn A', '0987654321', 'customer@hotel.com', '123456789'],
    [NULL, 'Trần Thị B', '0987654322', 'tran.b@email.com', '123456790'],
    [NULL, 'Lê Văn C', '0987654323', 'le.c@email.com', '123456791']
];

$success_guests = 0;
foreach ($guests_data as $guest) {
    $user_id = $guest[0] ? $guest[0] : 'NULL';
    $name = $conn->real_escape_string($guest[1]);
    $phone = $conn->real_escape_string($guest[2]);
    $email = $conn->real_escape_string($guest[3]);
    $id_card = $conn->real_escape_string($guest[4]);
    
    $sql = "INSERT INTO guests (user_id, name, phone, email, id_card) 
            VALUES ($user_id, '$name', '$phone', '$email', '$id_card')";
    
    if ($conn->query($sql)) {
        $success_guests++;
    } else {
        echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi thêm guest $name: " . $conn->error . "</div>";
    }
}

// Thêm dữ liệu mẫu cho bookings
$bookings_data = [
    [1, 1, '2025-01-15', '2025-01-17', 1000000, 'checked_in', 'paid'],
    [2, 5, '2025-01-16', '2025-01-19', 3000000, 'booked', 'unpaid'],
    [3, 3, '2025-01-18', '2025-01-20', 1000000, 'booked', 'unpaid']
];

$success_bookings = 0;
foreach ($bookings_data as $booking) {
    $guest_id = $booking[0];
    $room_id = $booking[1];
    $checkin = $booking[2];
    $checkout = $booking[3];
    $total_price = $booking[4];
    $status = $conn->real_escape_string($booking[5]);
    $payment_status = $conn->real_escape_string($booking[6]);
    
    $sql = "INSERT INTO bookings (guest_id, room_id, checkin, checkout, total_price, status, payment_status) 
            VALUES ($guest_id, $room_id, '$checkin', '$checkout', $total_price, '$status', '$payment_status')";
    
    if ($conn->query($sql)) {
        $success_bookings++;
    } else {
        echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi thêm booking: " . $conn->error . "</div>";
    }
}

// Thêm dữ liệu mẫu cho payments
$payments_data = [
    [1, 1000000, 'cash', 'completed', 'TXN_20250115_1_001', 1]
];

$success_payments = 0;
foreach ($payments_data as $payment) {
    $booking_id = $payment[0];
    $amount = $payment[1];
    $payment_method = $conn->real_escape_string($payment[2]);
    $payment_status = $conn->real_escape_string($payment[3]);
    $transaction_id = $conn->real_escape_string($payment[4]);
    $processed_by = $payment[5];
    
    $sql = "INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, processed_by) 
            VALUES ($booking_id, $amount, '$payment_method', '$payment_status', '$transaction_id', $processed_by)";
    
    if ($conn->query($sql)) {
        $success_payments++;
    } else {
        echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi thêm payment: " . $conn->error . "</div>";
    }
}

// Thêm cài đặt khách sạn
$settings_data = [
    ['hotel_name', 'Luxury Hotel Management', 'string', 'Tên khách sạn'],
    ['hotel_address', '123 Main Street, City, Country', 'string', 'Địa chỉ khách sạn'],
    ['hotel_phone', '0123456789', 'string', 'Số điện thoại khách sạn'],
    ['hotel_email', 'info@hotel.com', 'string', 'Email khách sạn'],
    ['check_in_time', '14:00', 'string', 'Giờ check-in'],
    ['check_out_time', '12:00', 'string', 'Giờ check-out'],
    ['currency', 'VND', 'string', 'Đơn vị tiền tệ'],
    ['tax_rate', '10', 'number', 'Thuế VAT (%)'],
    ['cancellation_policy', 'Miễn phí hủy trước 24h', 'string', 'Chính sách hủy phòng']
];

$success_settings = 0;
foreach ($settings_data as $setting) {
    $key = $conn->real_escape_string($setting[0]);
    $value = $conn->real_escape_string($setting[1]);
    $type = $conn->real_escape_string($setting[2]);
    $description = $conn->real_escape_string($setting[3]);
    
    $sql = "INSERT INTO hotel_settings (setting_key, setting_value, setting_type, description) 
            VALUES ('$key', '$value', '$type', '$description')";
    
    if ($conn->query($sql)) {
        $success_settings++;
    } else {
        echo "<div class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> Lỗi thêm setting $key: " . $conn->error . "</div>";
    }
}

// Capture all output
$output = ob_get_clean();
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
        
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .main-content {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            height: 100%;
            text-align: center;
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
            margin: 0 auto 1rem;
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
        
        .demo-account-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .demo-account-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .room-info-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: 1rem;
            padding: 1.5rem;
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
        
        .output-log {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 1rem;
            padding: 1.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .success-banner {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #198754;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Setup Header -->
    <div class="setup-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-2">
                        <i class="bi bi-database-fill me-3"></i>
                        Thiết lập Database
                    </h1>
                    <p class="lead mb-0">Quá trình tạo database và dữ liệu mẫu cho hệ thống khách sạn</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="index.php" class="btn btn-outline-light btn-custom">
                        <i class="bi bi-house-fill me-2"></i>Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Success Banner -->
        <div class="success-banner">
            <h2 class="text-success mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>
                Thiết lập hoàn tất!
            </h2>
            <p class="lead mb-0">Database và dữ liệu đã được tạo thành công</p>
        </div>

        <!-- Output Log -->
        <?php if (!empty($output)): ?>
        <div class="main-content">
            <h3 class="mb-3">
                <i class="bi bi-terminal-fill me-2"></i>
                Log thiết lập
            </h3>
            <div class="output-log">
                <?php echo $output; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="main-content">
            <h3 class="mb-4">
                <i class="bi bi-bar-chart-fill me-2"></i>
                Thống kê thiết lập
            </h3>
            <div class="row g-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stats-number text-primary"><?php echo $success_users; ?></div>
                        <div class="stats-label">Tài khoản đã tạo</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-building-fill"></i>
                        </div>
                        <div class="stats-number text-success"><?php echo $success_rooms; ?></div>
                        <div class="stats-label">Phòng đã tạo</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <div class="stats-number text-info"><?php echo $success_guests; ?></div>
                        <div class="stats-label">Khách hàng mẫu</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <div class="stats-number text-warning"><?php echo $success_bookings; ?></div>
                        <div class="stats-label">Đặt phòng mẫu</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Structure -->
        <div class="main-content">
            <h3 class="mb-4">
                <i class="bi bi-building-fill me-2"></i>
                Cấu trúc phòng đã tạo
            </h3>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="room-info-card">
                        <h5 class="text-primary mb-3">
                            <i class="bi bi-1-circle-fill me-2"></i>
                            Tầng 1
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Phòng 101-104 (Thường)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Phòng 105-106 (VIP)</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="room-info-card">
                        <h5 class="text-primary mb-3">
                            <i class="bi bi-2-circle-fill me-2"></i>
                            Tầng 2
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Phòng 201-204 (Thường)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Phòng 205-206 (VIP)</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="room-info-card">
                        <h5 class="text-primary mb-3">
                            <i class="bi bi-3-circle-fill me-2"></i>
                            Tầng 3
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Phòng 301-304 (Thường)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Phòng 305-306 (VIP)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="main-content text-center">
            <div class="row g-3 justify-content-center">
                <div class="col-lg-3 col-md-6">
                    <a href="login.php" class="btn btn-primary btn-custom w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Đăng nhập ngay
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="index.php" class="btn btn-success btn-custom w-100">
                        <i class="bi bi-house-fill me-2"></i>
                        Xem danh sách phòng
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="dashboard.php" class="btn btn-info btn-custom w-100">
                        <i class="bi bi-graph-up me-2"></i>
                        Về Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center py-4 text-white">
            <p class="mb-0">
                <i class="bi bi-database-check me-2"></i>
                Database Setup Complete - Hotel Management System v2.0
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
