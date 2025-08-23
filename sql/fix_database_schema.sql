-- ================================================
-- FIX DATABASE SCHEMA - Thêm created_at cho bookings
-- Chạy script này để sửa lỗi admin_dashboard.php
-- ================================================

USE hotel_management;

-- Kiểm tra và thêm cột created_at cho bảng bookings nếu chưa có
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Cập nhật created_at cho các booking hiện có (sử dụng checkin date)
UPDATE bookings 
SET created_at = CONCAT(checkin, ' 10:00:00') 
WHERE created_at IS NULL;

-- Kiểm tra kết quả
SELECT 
    id,
    checkin,
    checkout,
    created_at,
    status
FROM bookings
ORDER BY id;

-- Thống kê sau khi sửa
SELECT 
    'bookings' as table_name,
    COUNT(*) as total_records,
    COUNT(created_at) as records_with_created_at
FROM bookings;
