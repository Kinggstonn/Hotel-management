
DROP DATABASE IF EXISTS hotel_management;
CREATE DATABASE hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_management;

-- Users table (Authentication & Authorization)
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

-- Rooms table (Enhanced with floor, price)
CREATE TABLE rooms (
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

-- Bookings table (Enhanced with payment status, created_at)
CREATE TABLE bookings (
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
CREATE TABLE payments (
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

-- Refunds table (Refund Management)
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

-- Room Services table (Room Service Management)
CREATE TABLE room_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    service_type ENUM('cleaning', 'maintenance', 'minibar', 'laundry', 'food_drink', 'towels', 'amenities', 'other') DEFAULT 'other',
    description TEXT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) DEFAULT 0,
    status ENUM('requested', 'in_progress', 'completed', 'cancelled') DEFAULT 'requested',
    requested_by INT NOT NULL,
    assigned_to INT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Hotel Settings table (Settings Management)
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

-- Activity Logs table (Audit Trail)
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

-- Indexes for better performance
CREATE INDEX idx_bookings_dates ON bookings(checkin, checkout);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_payments_date ON payments(created_at);
CREATE INDEX idx_room_services_status ON room_services(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_rooms_status ON rooms(status);

-- Sample Data
-- Demo users
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@hotel.com', '0123456789', 'admin', 'active'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hotel Staff', 'staff@hotel.com', '0123456790', 'staff', 'active'),
('customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer Demo', 'customer@hotel.com', '0123456791', 'customer', 'active');

-- Hotel rooms (3 floors, 6 rooms per floor: 4 regular + 2 VIP)
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

-- Sample guests
INSERT INTO guests (user_id, name, phone, email, id_card) VALUES
(3, 'Nguyễn Văn A', '0987654321', 'customer@hotel.com', '123456789'),
(NULL, 'Trần Thị B', '0987654322', 'tran.b@email.com', '123456790'),
(NULL, 'Lê Văn C', '0987654323', 'le.c@email.com', '123456791');

-- Sample bookings
INSERT INTO bookings (guest_id, room_id, checkin, checkout, total_price, status, payment_status) VALUES
(1, 1, '2025-01-15', '2025-01-17', 1000000, 'checked_in', 'paid'),
(2, 5, '2025-01-16', '2025-01-19', 3000000, 'booked', 'unpaid'),
(3, 3, '2025-01-18', '2025-01-20', 1000000, 'booked', 'unpaid');

-- Sample payments
INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, processed_by) VALUES
(1, 1000000, 'cash', 'completed', 'TXN_20250115_1_001', 1);

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

-- Views for common queries
CREATE VIEW active_bookings AS
SELECT 
    b.id, b.checkin, b.checkout, b.total_price, b.status, b.payment_status,
    r.room_number, r.type, r.floor, r.price,
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

-- Stored procedures
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

-- Triggers for activity logging
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

COMMIT;
