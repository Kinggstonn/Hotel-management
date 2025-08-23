<?php
// Quick Database Setup for Login System
include "db.php";

echo "<h1>🔧 Thiết lập nhanh Database với Login System</h1>";
echo "<p>Đang tạo database và bảng users...</p>";

try {
    // Create database if not exists
    $conn_temp = new mysqli($host, $user, $pass);
    $conn_temp->query("CREATE DATABASE IF NOT EXISTS hotel_management");
    $conn_temp->close();

    // Check if users table exists
    $check_users = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($check_users->num_rows === 0) {
        echo "<p>✅ Tạo bảng users...</p>";
        
        // Create users table
        $users_sql = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )";
        
        if ($conn->query($users_sql)) {
            echo "<p>✅ Đã tạo bảng users!</p>";
            
            // Add default users
            echo "<p>✅ Thêm tài khoản demo...</p>";
            $demo_users = "
            INSERT INTO users (username, password, full_name, email, phone, role) VALUES
            ('admin', 'hashed_admin', 'Quản trị viên', 'admin@hotel.com', '0901234567', 'admin'),
            ('staff', 'hashed_staff', 'Nhân viên lễ tân', 'staff@hotel.com', '0907654321', 'staff'),
            ('customer', 'hashed_customer', 'Khách hàng VIP', 'customer@email.com', '0905555555', 'customer')
            ";
            
            if ($conn->query($demo_users)) {
                echo "<p>✅ Đã tạo 3 tài khoản demo!</p>";
            }
        } else {
            echo "<p>❌ Lỗi tạo bảng users: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ Bảng users đã tồn tại!</p>";
    }
    
    // Check if guests table has user_id column
    $check_user_id = $conn->query("SHOW COLUMNS FROM guests LIKE 'user_id'");
    if ($check_user_id->num_rows === 0) {
        echo "<p>✅ Cập nhật bảng guests...</p>";
        $conn->query("ALTER TABLE guests ADD COLUMN user_id INT NULL");
        $conn->query("ALTER TABLE guests ADD FOREIGN KEY (user_id) REFERENCES users(id)");
        echo "<p>✅ Đã liên kết bảng guests với users!</p>";
    }
    
    echo "<div style='background: #d5f4e6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>✅ Thiết lập hoàn tất!</h2>";
    echo "<p><strong>Tài khoản demo đã tạo:</strong></p>";
    echo "<ul>";
    echo "<li><i class='fas fa-user-tie'></i> <strong>admin</strong> / admin123 - Quản trị viên</li>";
    echo "<li><i class='fas fa-user-tie'></i> <strong>staff</strong> / staff123 - Nhân viên</li>";
    echo "<li><i class='fas fa-user'></i> <strong>customer</strong> / customer123 - Khách hàng</li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'><i class='fas fa-key'></i> Đến trang đăng nhập</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "<p>Vui lòng kiểm tra kết nối database trong db.php</p>";
}
?>
