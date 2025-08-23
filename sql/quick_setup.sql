-- ================================================
-- QUICK SETUP DATABASE - Hotel Management System
-- ================================================

CREATE DATABASE IF NOT EXISTS hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_management;

-- ================================================
-- TẠO CÁC BẢNG CƠ BẢN
-- ================================================

-- Bảng users (Authentication & Authorization)
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

-- Bảng rooms (Phòng khách sạn)
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

-- Bảng guests (Khách hàng)
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

-- Bảng bookings (Đặt phòng)
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

-- Bảng room_services (Dịch vụ phòng)
CREATE TABLE IF NOT EXISTS room_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    service_type ENUM('cleaning', 'maintenance', 'minibar', 'laundry', 'food_drink', 'towels', 'amenities', 'other') DEFAULT 'other',
    description TEXT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) DEFAULT 0,
    status ENUM('requested', 'in_progress', 'completed', 'cancelled') DEFAULT 'requested',
    requested_by INT,
    assigned_to INT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- ================================================
-- THÊM DỮ LIỆU MẪU
-- ================================================

-- Thêm users mặc định (password: admin123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@hotel.com', '0123456789', 'admin', 'active'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hotel Staff', 'staff@hotel.com', '0123456790', 'staff', 'active'),
('customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer Demo', 'customer@hotel.com', '0123456791', 'customer', 'active');

-- Thêm phòng (3 tầng, mỗi tầng 6 phòng: 4 Thường + 2 VIP)
INSERT INTO rooms (room_number, type, floor, price, status) VALUES
-- Tầng 1
('101', 'Thường', 1, 500000, 'available'),
('102', 'Thường', 1, 500000, 'available'),
('103', 'Thường', 1, 500000, 'available'),
('104', 'Thường', 1, 500000, 'available'),
('105', 'VIP', 1, 1000000, 'available'),
('106', 'VIP', 1, 1000000, 'available'),

-- Tầng 2
('201', 'Thường', 2, 500000, 'available'),
('202', 'Thường', 2, 500000, 'available'),
('203', 'Thường', 2, 500000, 'available'),
('204', 'Thường', 2, 500000, 'available'),
('205', 'VIP', 2, 1000000, 'available'),
('206', 'VIP', 2, 1000000, 'available'),

-- Tầng 3
('301', 'Thường', 3, 500000, 'available'),
('302', 'Thường', 3, 500000, 'available'),
('303', 'Thường', 3, 500000, 'available'),
('304', 'Thường', 3, 500000, 'available'),
('305', 'VIP', 3, 1000000, 'available'),
('306', 'VIP', 3, 1000000, 'available');

-- Thêm khách hàng mẫu
INSERT INTO guests (name, phone, id_card, user_id) VALUES
('Administrator', '0123456789', 'ADMIN001', 1),
('Hotel Staff', '0123456790', 'STAFF001', 2),
('Customer Demo', '0123456791', 'CUST001', 3);

-- ================================================
-- KIỂM TRA KẾT QUẢ
-- ================================================

-- Xem danh sách phòng
SELECT 
    room_number as 'Số phòng',
    type as 'Loại',
    floor as 'Tầng',
    CONCAT(FORMAT(price, 0), ' VND') as 'Giá/đêm',
    status as 'Trạng thái'
FROM rooms 
ORDER BY floor, room_number;

-- Xem thống kê phòng theo tầng
SELECT 
    floor as 'Tầng',
    COUNT(*) as 'Tổng phòng',
    SUM(CASE WHEN type = 'Thường' THEN 1 ELSE 0 END) as 'Phòng Thường',
    SUM(CASE WHEN type = 'VIP' THEN 1 ELSE 0 END) as 'Phòng VIP',
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as 'Còn trống'
FROM rooms 
GROUP BY floor 
ORDER BY floor;

-- Xem danh sách users
SELECT username, full_name, role, status FROM users;
