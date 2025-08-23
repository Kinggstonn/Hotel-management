
CREATE DATABASE IF NOT EXISTS hotel_management;
USE hotel_management;

-- Xóa bảng cũ nếu có (cẩn thận với dữ liệu!)
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS guests; 
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;

-- Tạo bảng users (Authentication)
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
);

-- Tạo bảng phòng
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10),
    floor INT NOT NULL,
    type VARCHAR(50) DEFAULT 'Thường', -- Thường, VIP
    status VARCHAR(20) DEFAULT 'available', -- available, booked, occupied
    price DECIMAL(10,2) NOT NULL
);

-- Tạo bảng khách hàng  
CREATE TABLE guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    phone VARCHAR(20),
    id_card VARCHAR(20),
    user_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tạo bảng đặt phòng
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT,
    room_id INT,
    checkin DATE,
    checkout DATE,
    total_price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- ================================================
-- THÊM DỮ LIỆU USERS VÀ PHÒNG
-- ================================================

-- Thêm users mặc định
INSERT INTO users (username, password, full_name, email, phone, role) VALUES
-- Admin account (password: admin123)
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', 'admin@hotel.com', '0901234567', 'admin'),
-- Staff account (password: staff123)  
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nhân viên lễ tân', 'staff@hotel.com', '0907654321', 'staff'),
-- Customer account (password: customer123)
('customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Khách hàng VIP', 'customer@email.com', '0905555555', 'customer');

-- THÊM DỮ LIỆU PHÒNG: 3 TẦNG x 6 PHÒNG

-- TẦNG 1: Phòng 101-106 (4 Thường + 2 VIP)
INSERT INTO rooms (room_number, floor, type, status, price) VALUES
('101', 1, 'Thường', 'available', 500000.00),
('102', 1, 'Thường', 'available', 500000.00),
('103', 1, 'Thường', 'available', 500000.00),
('104', 1, 'Thường', 'available', 500000.00),
('105', 1, 'VIP', 'available', 1000000.00),
('106', 1, 'VIP', 'available', 1000000.00);

-- TẦNG 2: Phòng 201-206 (4 Thường + 2 VIP)
INSERT INTO rooms (room_number, floor, type, status, price) VALUES
('201', 2, 'Thường', 'available', 500000.00),
('202', 2, 'Thường', 'available', 500000.00),
('203', 2, 'Thường', 'available', 500000.00),
('204', 2, 'Thường', 'available', 500000.00),
('205', 2, 'VIP', 'available', 1000000.00),
('206', 2, 'VIP', 'available', 1000000.00);

-- TẦNG 3: Phòng 301-306 (4 Thường + 2 VIP)  
INSERT INTO rooms (room_number, floor, type, status, price) VALUES
('301', 3, 'Thường', 'available', 500000.00),
('302', 3, 'Thường', 'available', 500000.00),
('303', 3, 'Thường', 'available', 500000.00),
('304', 3, 'Thường', 'available', 500000.00),
('305', 3, 'VIP', 'available', 1000000.00),
('306', 3, 'VIP', 'available', 1000000.00);

-- ================================================
-- THÊM DỮ LIỆU MẪU (tuỳ chọn)
-- ================================================

-- Thêm vài khách hàng mẫu (liên kết với user accounts)
INSERT INTO guests (name, phone, id_card, user_id) VALUES
('Quản trị viên', '0901234567', 'ADMIN001', 1),
('Nhân viên lễ tân', '0907654321', 'STAFF001', 2),
('Khách hàng VIP', '0905555555', 'CUST001', 3);

-- Đặt vài phòng mẫu
INSERT INTO bookings (guest_id, room_id, checkin, checkout, total_price, status) VALUES
(1, 1, '2024-01-15', '2024-01-17', 1000000.00, 'booked'),
(2, 5, '2024-01-20', '2024-01-22', 2000000.00, 'booked');

-- Cập nhật trạng thái phòng đã đặt
UPDATE rooms SET status = 'booked' WHERE id IN (1, 5);

-- ================================================
-- QUERY KIỂM TRA KẾT QUẢ
-- ================================================

-- Xem danh sách phòng
SELECT 
    room_number as 'Số phòng',
    floor as 'Tầng', 
    type as 'Loại',
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
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as 'Còn trống',
    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as 'Đã đặt'
FROM rooms 
GROUP BY floor 
ORDER BY floor;
