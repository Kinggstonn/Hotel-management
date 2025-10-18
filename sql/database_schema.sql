-- ================================================
-- HOTEL MANAGEMENT SYSTEM - COMPLETE DATABASE SCHEMA
-- ================================================
-- Tác giả: Hotel Management System
-- Phiên bản: 2.0 (Updated with Payment System)
-- Ngày tạo: 2025
-- Mô tả: Schema hoàn chỉnh cho hệ thống quản lý khách sạn với thanh toán

-- ================================================
-- 1. TẠO DATABASE VÀ CẤU HÌNH
-- ================================================

DROP DATABASE IF EXISTS hotel_management;
CREATE DATABASE hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_management;

-- ================================================
-- 2. BẢNG NGƯỜI DÙNG (USERS)
-- ================================================

CREATE TABLE users (
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

-- ================================================
-- 3. BẢNG PHÒNG (ROOMS) - CẬP NHẬT VỚI PRICE_PER_NIGHT
-- ================================================

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    type ENUM('VIP', 'Thường') DEFAULT 'Thường',
    floor INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,  -- Cột mới cho hệ thống thanh toán
    status ENUM('available', 'booked', 'occupied', 'cleaning', 'maintenance') DEFAULT 'available',
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================================
-- 4. BẢNG KHÁCH HÀNG (GUESTS)
-- ================================================

CREATE TABLE guests (
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

-- ================================================
-- 5. BẢNG ĐẶT PHÒNG (BOOKINGS) - CẬP NHẬT VỚI PENDING_PAYMENT
-- ================================================

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    checkin DATE NOT NULL,
    checkout DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending_payment', 'booked', 'checked_in', 'checked_out', 'completed', 'cancelled', 'early_checkout') DEFAULT 'pending_payment',
    payment_status ENUM('unpaid', 'paid', 'partial', 'refunded') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- ================================================
-- 6. BẢNG THANH TOÁN (PAYMENTS) - CẬP NHẬT VỚI THÔNG TIN THẺ
-- ================================================

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'momo', 'zalopay') DEFAULT 'card',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE,
    card_number VARCHAR(20),        -- Thông tin thẻ (đã mask)
    card_name VARCHAR(100),         -- Tên chủ thẻ
    card_expiry VARCHAR(10),        -- Ngày hết hạn thẻ
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ================================================
-- 7. BẢNG HOÀN TIỀN (REFUNDS)
-- ================================================

CREATE TABLE refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    booking_id INT NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL,
    refund_reason TEXT,
    refund_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ================================================
-- 8. BẢNG DỊCH VỤ PHÒNG (ROOM SERVICES)
-- ================================================

CREATE TABLE room_services (
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
-- 9. BẢNG CÀI ĐẶT KHÁCH SẠN (HOTEL SETTINGS)
-- ================================================

CREATE TABLE hotel_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ================================================
-- 10. BẢNG LOG HOẠT ĐỘNG (ACTIVITY LOGS)
-- ================================================

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Password Resets table (Forgot Password)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ================================================
-- 11. INDEXES CHO HIỆU SUẤT
-- ================================================

CREATE INDEX idx_bookings_dates ON bookings(checkin, checkout);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_payments_date ON payments(created_at);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_payments_card ON payments(card_number);
CREATE INDEX idx_room_services_status ON room_services(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_rooms_status ON rooms(status);
CREATE INDEX idx_password_resets_token ON password_resets(token);
CREATE INDEX idx_password_resets_email ON password_resets(email);

-- ================================================
-- 12. DỮ LIỆU MẪU
-- ================================================

-- Demo users
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@hotel.com', '0123456789', 'admin', 'active'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hotel Staff', 'staff@hotel.com', '0123456790', 'staff', 'active'),
('customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer Demo', 'customer@hotel.com', '0123456791', 'customer', 'active');

-- Hotel rooms (3 floors, 6 rooms per floor: 4 regular + 2 VIP)
INSERT INTO rooms (room_number, type, floor, price, price_per_night, status) VALUES
-- Floor 1
('101', 'Thường', 1, 500000, 500000, 'available'),
('102', 'Thường', 1, 500000, 500000, 'available'),
('103', 'Thường', 1, 500000, 500000, 'available'),
('104', 'Thường', 1, 500000, 500000, 'available'),
('105', 'VIP', 1, 1000000, 1000000, 'available'),
('106', 'VIP', 1, 1000000, 1000000, 'available'),

-- Floor 2
('201', 'Thường', 2, 500000, 500000, 'available'),
('202', 'Thường', 2, 500000, 500000, 'available'),
('203', 'Thường', 2, 500000, 500000, 'available'),
('204', 'Thường', 2, 500000, 500000, 'available'),
('205', 'VIP', 2, 1000000, 1000000, 'available'),
('206', 'VIP', 2, 1000000, 1000000, 'available'),

-- Floor 3
('301', 'Thường', 3, 500000, 500000, 'available'),
('302', 'Thường', 3, 500000, 500000, 'available'),
('303', 'Thường', 3, 500000, 500000, 'available'),
('304', 'Thường', 3, 500000, 500000, 'available'),
('305', 'VIP', 3, 1000000, 1000000, 'available'),
('306', 'VIP', 3, 1000000, 1000000, 'available');

-- Sample guests
INSERT INTO guests (user_id, name, phone, email, id_card) VALUES
(3, 'Nguyễn Văn A', '0987654321', 'customer@hotel.com', '123456789'),
(NULL, 'Trần Thị B', '0987654322', 'tran.b@email.com', '123456790'),
(NULL, 'Lê Văn C', '0987654323', 'le.c@email.com', '123456791');

-- Sample bookings (với trạng thái pending_payment)
INSERT INTO bookings (guest_id, room_id, checkin, checkout, total_price, status, payment_status) VALUES
(1, 1, '2025-01-15', '2025-01-17', 1000000, 'booked', 'paid'),
(2, 5, '2025-01-16', '2025-01-19', 3000000, 'pending_payment', 'unpaid'),
(3, 3, '2025-01-18', '2025-01-20', 1000000, 'pending_payment', 'unpaid');

-- Sample payments (với thông tin thẻ)
INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, card_number, card_name, card_expiry, processed_by) VALUES
(1, 1000000, 'cash', 'completed', 'TXN_20250115_1_001', NULL, NULL, NULL, 1),
(2, 3000000, 'card', 'completed', 'TXN_20250116_2_002', '4111****1111', 'Trần Thị B', '12/25', 1),
(3, 1000000, 'card', 'completed', 'TXN_20250118_3_003', '4111****1111', 'Lê Văn C', '12/25', 1);

-- Hotel settings
INSERT INTO hotel_settings (setting_key, setting_value, setting_type, description) VALUES
('hotel_name', 'Luxury Hotel Management', 'string', 'Tên khách sạn'),
('hotel_address', '123 Main Street, City, Country', 'string', 'Địa chỉ khách sạn'),
('hotel_phone', '0123456789', 'string', 'Số điện thoại khách sạn'),
('hotel_email', 'info@hotel.com', 'string', 'Email khách sạn'),
('check_in_time', '14:00', 'string', 'Giờ check-in'),
('check_out_time', '12:00', 'string', 'Giờ check-out'),
('currency', 'VND', 'string', 'Đơn vị tiền tệ'),
('tax_rate', '10', 'number', 'Thuế VAT (%)'),
('cancellation_policy', 'Miễn phí hủy trước 24h', 'string', 'Chính sách hủy phòng'),
('max_occupancy_regular', '2', 'number', 'Số người tối đa - phòng thường'),
('max_occupancy_vip', '4', 'number', 'Số người tối đa - phòng VIP');

-- ================================================
-- 13. VIEWS CHO CÁC TRUY VẤN THƯỜNG DÙNG
-- ================================================

CREATE VIEW active_bookings AS
SELECT 
    b.id, b.checkin, b.checkout, b.total_price, b.status, b.payment_status,
    r.room_number, r.type, r.floor, r.price, r.price_per_night,
    g.name as guest_name, g.phone, g.email,
    u.username, u.role
FROM bookings b
JOIN rooms r ON b.room_id = r.id
JOIN guests g ON b.guest_id = g.id
LEFT JOIN users u ON g.user_id = u.id
WHERE b.status IN ('booked', 'checked_in', 'checked_out');

CREATE VIEW payment_summary AS
SELECT 
    DATE(p.created_at) as payment_date,
    p.payment_method,
    COUNT(*) as transaction_count,
    SUM(p.amount) as total_amount,
    SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END) as completed_amount
FROM payments p
GROUP BY DATE(p.created_at), p.payment_method;

CREATE VIEW room_usage AS
SELECT 
    r.floor,
    r.type,
    COUNT(*) as total_rooms,
    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms,
    SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
    SUM(CASE WHEN r.status = 'cleaning' THEN 1 ELSE 0 END) as cleaning_rooms
FROM rooms r
GROUP BY r.floor, r.type;

-- ================================================
-- 14. STORED PROCEDURES
-- ================================================

DELIMITER //

-- Procedure to automatically complete expired bookings
CREATE PROCEDURE CompleteExpiredBookings()
BEGIN
    UPDATE bookings 
    SET status = 'completed' 
    WHERE checkout < CURDATE() 
    AND status IN ('checked_in', 'checked_out');
    
    UPDATE rooms r
    JOIN bookings b ON r.id = b.room_id
    SET r.status = 'available'
    WHERE b.status = 'completed' AND r.status != 'available';
END //

-- Procedure to calculate daily revenue
CREATE PROCEDURE GetDailyRevenue(IN target_date DATE)
BEGIN
    SELECT 
        target_date as revenue_date,
        COALESCE(SUM(p.amount), 0) as total_revenue,
        COUNT(p.id) as transaction_count,
        COALESCE(AVG(p.amount), 0) as avg_transaction
    FROM payments p
    WHERE DATE(p.created_at) = target_date 
    AND p.payment_status = 'completed';
END //

DELIMITER ;

-- ================================================
-- 15. TRIGGERS CHO AUDIT TRAIL
-- ================================================

DELIMITER //

CREATE TRIGGER booking_insert_log AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values)
    VALUES (NULL, 'INSERT', 'bookings', NEW.id, JSON_OBJECT(
        'guest_id', NEW.guest_id,
        'room_id', NEW.room_id,
        'checkin', NEW.checkin,
        'checkout', NEW.checkout,
        'total_price', NEW.total_price,
        'status', NEW.status
    ));
END //

CREATE TRIGGER payment_insert_log AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values)
    VALUES (NEW.processed_by, 'INSERT', 'payments', NEW.id, JSON_OBJECT(
        'booking_id', NEW.booking_id,
        'amount', NEW.amount,
        'payment_method', NEW.payment_method,
        'transaction_id', NEW.transaction_id
    ));
END //

DELIMITER ;

-- ================================================
-- 16. HOÀN TẤT
-- ================================================

COMMIT;

-- ================================================
-- THÔNG TIN VỀ SCHEMA
-- ================================================
-- 
-- Các tính năng chính:
-- ✅ Hệ thống đăng nhập và phân quyền
-- ✅ Chức năng quên mật khẩu với token bảo mật
-- ✅ Quản lý phòng với giá theo đêm
-- ✅ Đặt phòng với trạng thái pending_payment
-- ✅ Hệ thống thanh toán với thông tin thẻ
-- ✅ Quản lý hoàn tiền
-- ✅ Dịch vụ phòng
-- ✅ Cài đặt khách sạn
-- ✅ Audit trail (log hoạt động)
-- ✅ Views và Stored Procedures
-- ✅ Triggers tự động
-- 
-- Trạng thái booking:
-- - pending_payment: Chờ thanh toán
-- - booked: Đã thanh toán và xác nhận
-- - checked_in: Đã check-in
-- - checked_out: Đã check-out
-- - completed: Hoàn thành
-- - cancelled: Đã hủy
-- - early_checkout: Check-out sớm
-- 
-- Trạng thái thanh toán:
-- - unpaid: Chưa thanh toán
-- - paid: Đã thanh toán
-- - partial: Thanh toán một phần
-- - refunded: Đã hoàn tiền
-- 
-- Chức năng quên mật khẩu:
-- - password_resets: Bảng lưu token đặt lại mật khẩu
-- - Token có hiệu lực 1 giờ
-- - One-time use (chỉ dùng 1 lần)
-- - Bảo mật với random token 64 ký tự
-- 
-- Files liên quan:
-- - forgot_password.php: Trang yêu cầu đặt lại mật khẩu
-- - reset_password.php: Trang đặt lại mật khẩu mới
-- - login.php: Có link "Quên mật khẩu?"
-- 
-- ================================================
