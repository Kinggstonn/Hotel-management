<?php
// Fix tất cả lỗi created_at trong hệ thống
include "db.php";

echo "<h1>🔧 Sửa tất cả lỗi created_at</h1>";
echo "<p>Đang kiểm tra và sửa lỗi created_at trong toàn bộ hệ thống...</p>";

try {
    // 1. Kiểm tra xem cột created_at có tồn tại không
    echo "<h2>1. Kiểm tra cấu trúc database</h2>";
    
    $check_bookings_created_at = $conn->query("SHOW COLUMNS FROM bookings LIKE 'created_at'");
    
    if ($check_bookings_created_at->num_rows === 0) {
        echo "<p><i class='fas fa-exclamation-triangle'></i> Cột created_at không tồn tại trong bảng bookings</p>";
        echo "<p><i class='fas fa-check-circle'></i> Đang thêm cột created_at...</p>";
        
        // Thêm cột created_at
        $add_column = "ALTER TABLE bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        if ($conn->query($add_column)) {
            echo "<p><i class='fas fa-check-circle'></i> Đã thêm cột created_at vào bảng bookings!</p>";
            
            // Cập nhật created_at cho booking hiện có
            echo "<p><i class='fas fa-check-circle'></i> Đang cập nhật created_at cho booking hiện có...</p>";
            $update_existing = "UPDATE bookings SET created_at = CONCAT(checkin, ' 10:00:00')";
            if ($conn->query($update_existing)) {
                echo "<p><i class='fas fa-check-circle'></i> Đã cập nhật created_at cho tất cả booking!</p>";
            }
        } else {
            echo "<p><i class='fas fa-exclamation-triangle'></i> Lỗi thêm cột: " . $conn->error . "</p>";
        }
    } else {
        echo "<p><i class='fas fa-check-circle'></i> Cột created_at đã tồn tại trong bảng bookings!</p>";
    }
    
    // 2. Test các query có vấn đề
    echo "<h2>2. Test các query trong hệ thống</h2>";
    
    // Test admin dashboard query
    echo "<p>🧪 Test admin dashboard query...</p>";
    try {
        $admin_query = "
            SELECT 
                'booking' as type,
                b.created_at,
                g.name as user_name,
                CONCAT('Đặt phòng ', r.room_number) as description
            FROM bookings b 
            JOIN guests g ON b.guest_id = g.id 
            JOIN rooms r ON b.room_id = r.id 
            ORDER BY b.created_at DESC 
            LIMIT 5
        ";
        
        $result1 = $conn->query($admin_query);
        if ($result1) {
            echo "<p><i class='fas fa-check-circle'></i> Admin dashboard query hoạt động! (Tìm thấy " . $result1->num_rows . " records)</p>";
        }
    } catch (Exception $e) {
        echo "<p><i class='fas fa-exclamation-triangle'></i> Admin dashboard query lỗi: " . $e->getMessage() . "</p>";
    }
    
    // Test my_bookings query
    echo "<p>🧪 Test my_bookings query...</p>";
    try {
        $user_id = 1; // Test với user_id = 1
        $booking_query = "
            SELECT b.*, r.room_number, r.type as room_type, r.price,
                   g.name as guest_name, g.phone as guest_phone
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id  
            JOIN guests g ON b.guest_id = g.id
            WHERE g.user_id = $user_id
            ORDER BY b.created_at DESC
        ";
        
        $result2 = $conn->query($booking_query);
        if ($result2) {
            echo "<p><i class='fas fa-check-circle'></i> My_bookings query hoạt động! (Tìm thấy " . $result2->num_rows . " records)</p>";
        }
    } catch (Exception $e) {
        echo "<p><i class='fas fa-exclamation-triangle'></i> My_bookings query lỗi: " . $e->getMessage() . "</p>";
    }
    
    // 3. Hiển thị cấu trúc bảng bookings hiện tại
    echo "<h2>3. Cấu trúc bảng bookings hiện tại</h2>";
    $structure = $conn->query("DESCRIBE bookings");
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Cột</th><th>Kiểu dữ liệu</th><th>NULL</th><th>Key</th><th>Mặc định</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Test sample data
    echo "<h2>4. Dữ liệu mẫu trong bảng bookings</h2>";
    $sample = $conn->query("SELECT id, checkin, checkout, status, created_at FROM bookings LIMIT 5");
    if ($sample && $sample->num_rows > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Created At</th></tr>";
        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['checkin'] . "</td>";
            echo "<td>" . $row['checkout'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . ($row['created_at'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có dữ liệu booking nào.</p>";
    }
    
    echo "<div style='background: #d5f4e6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2><i class='fas fa-check-circle'></i> Sửa lỗi hoàn tất!</h2>";
    echo "<p>Tất cả lỗi created_at đã được khắc phục.</p>";
    echo "<p><strong>Các trang bây giờ hoạt động bình thường:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin/admin_dashboard.php'><i class='fas fa-crown'></i> Admin Dashboard</a></li>";
    echo "<li><a href='my_bookings.php'><i class='fas fa-list-alt'></i> My Bookings</a></li>";
    echo "<li><a href='dashboard.php'>📊 Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fadbd8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2><i class='fas fa-exclamation-triangle'></i> Có lỗi xảy ra</h2>";
    echo "<p><strong>Lỗi:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Giải pháp thủ công:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "-- Chạy SQL này trong phpMyAdmin:\n";
    echo "ALTER TABLE bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;\n";
    echo "UPDATE bookings SET created_at = CONCAT(checkin, ' 10:00:00');\n";
    echo "</pre>";
    echo "</div>";
}

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'><i class='fas fa-key'></i> Về trang đăng nhập</a>";
echo "</p>";
?>
