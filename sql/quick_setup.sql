-- Quick Database Setup for Hotel Management System
-- Run this in phpMyAdmin or MySQL command line

-- Create database
CREATE DATABASE IF NOT EXISTS hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_management;

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

-- Rooms table
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

-- Guests table
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

-- Bookings table
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

-- Payments table
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

-- Hotel Settings table
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

-- Insert demo users
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@hotel.com', '0123456789', 'admin', 'active'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hotel Staff', 'staff@hotel.com', '0123456790', 'staff', 'active'),
('customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer Demo', 'customer@hotel.com', '0123456791', 'customer', 'active');

-- Insert rooms (3 floors, 6 rooms per floor: 4 regular + 2 VIP)
INSERT INTO rooms (room_number, type, floor, price, status) VALUES
-- Floor 1
('101', 'Thường', 1, 500000, 'available'),
('102', 'Thường', 1, 500000, 'available'),
('103', 'Thường', 1, 500000, 'available'),
('104', 'Thường', 1, 500000, 'available'),
('105', 'VIP', 1, 1000000, 'available'),
('106', 'VIP', 1, 1000000, 'available'),

-- Floor 2
('201', 'Thường', 2, 500000, 'available'),
('202', 'Thường', 2, 500000, 'available'),
('203', 'Thường', 2, 500000, 'available'),
('204', 'Thường', 2, 500000, 'available'),
('205', 'VIP', 2, 1000000, 'available'),
('206', 'VIP', 2, 1000000, 'available'),

-- Floor 3
('301', 'Thường', 3, 500000, 'available'),
('302', 'Thường', 3, 500000, 'available'),
('303', 'Thường', 3, 500000, 'available'),
('304', 'Thường', 3, 500000, 'available'),
('305', 'VIP', 3, 1000000, 'available'),
('306', 'VIP', 3, 1000000, 'available');

-- Insert sample guests
INSERT INTO guests (user_id, name, phone, email, id_card) VALUES
(3, 'Nguyễn Văn A', '0987654321', 'customer@hotel.com', '123456789'),
(NULL, 'Trần Thị B', '0987654322', 'tran.b@email.com', '123456790'),
(NULL, 'Lê Văn C', '0987654323', 'le.c@email.com', '123456791');

-- Insert sample bookings
INSERT INTO bookings (guest_id, room_id, checkin, checkout, total_price, status, payment_status) VALUES
(1, 1, '2025-01-15', '2025-01-17', 1000000, 'checked_in', 'paid'),
(2, 5, '2025-01-16', '2025-01-19', 3000000, 'booked', 'unpaid'),
(3, 3, '2025-01-18', '2025-01-20', 1000000, 'booked', 'unpaid');

-- Insert sample payments
INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, processed_by) VALUES
(1, 1000000, 'cash', 'completed', 'TXN_20250115_1_001', 1);

-- Insert hotel settings
INSERT INTO hotel_settings (setting_key, setting_value, setting_type, description) VALUES
('hotel_name', 'Luxury Hotel Management', 'string', 'Tên khách sạn'),
('hotel_address', '123 Main Street, City, Country', 'string', 'Địa chỉ khách sạn'),
('hotel_phone', '0123456789', 'string', 'Số điện thoại khách sạn'),
('hotel_email', 'info@hotel.com', 'string', 'Email khách sạn'),
('check_in_time', '14:00', 'string', 'Giờ check-in'),
('check_out_time', '12:00', 'string', 'Giờ check-out'),
('currency', 'VND', 'string', 'Đơn vị tiền tệ'),
('tax_rate', '10', 'number', 'Thuế VAT (%)'),
('cancellation_policy', 'Miễn phí hủy trước 24h', 'string', 'Chính sách hủy phòng');

-- Demo account passwords (plain text for easy access):
-- admin: admin123
-- staff: staff123  
-- customer: customer123
