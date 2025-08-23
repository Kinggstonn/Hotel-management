
CREATE DATABASE IF NOT EXISTS hotel_management;
USE hotel_management;

-- Bảng phòng (Updated structure)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10),
    floor INT NOT NULL,
    type VARCHAR(50) DEFAULT 'Thường', -- Thường, VIP
    status VARCHAR(20) DEFAULT 'available', -- available, booked, occupied
    price DECIMAL(10,2) NOT NULL
);

-- Bảng khách hàng (Keep user's structure)
CREATE TABLE IF NOT EXISTS guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    phone VARCHAR(20),
    id_card VARCHAR(20)
);

-- Bảng đặt phòng (Enhanced with total_price)
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT,
    room_id INT,
    checkin DATE,
    checkout DATE,
    total_price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'booked',
    FOREIGN KEY (guest_id) REFERENCES guests(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Xóa dữ liệu phòng cũ (nếu có)
DELETE FROM rooms;
ALTER TABLE rooms AUTO_INCREMENT = 1;

-- Insert dữ liệu phòng: 3 tầng, mỗi tầng 6 phòng (4 Thường + 2 VIP)
-- Tầng 1: 101-104 (Thường), 105-106 (VIP)
INSERT INTO rooms (room_number, floor, type, status, price) VALUES
('101', 1, 'Thường', 'available', 500000.00),
('102', 1, 'Thường', 'available', 500000.00),
('103', 1, 'Thường', 'available', 500000.00),
('104', 1, 'Thường', 'available', 500000.00),
('105', 1, 'VIP', 'available', 1000000.00),
('106', 1, 'VIP', 'available', 1000000.00),

-- Tầng 2: 201-204 (Thường), 205-206 (VIP)
('201', 2, 'Thường', 'available', 500000.00),
('202', 2, 'Thường', 'available', 500000.00),
('203', 2, 'Thường', 'available', 500000.00),
('204', 2, 'Thường', 'available', 500000.00),
('205', 2, 'VIP', 'available', 1000000.00),
('206', 2, 'VIP', 'available', 1000000.00),

-- Tầng 3: 301-304 (Thường), 305-306 (VIP)  
('301', 3, 'Thường', 'available', 500000.00),
('302', 3, 'Thường', 'available', 500000.00),
('303', 3, 'Thường', 'available', 500000.00),
('304', 3, 'Thường', 'available', 500000.00),
('305', 3, 'VIP', 'available', 1000000.00),
('306', 3, 'VIP', 'available', 1000000.00);

-- Query để xem kết quả
SELECT 
    room_number as 'Số phòng',
    floor as 'Tầng', 
    type as 'Loại',
    CONCAT(FORMAT(price, 0), ' VND') as 'Giá/đêm',
    status as 'Trạng thái'
FROM rooms 
ORDER BY floor, room_number;
