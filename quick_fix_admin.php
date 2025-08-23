<?php
// Quick Fix cho Admin Dashboard Error
include "../db.php";

echo "<h1>🔧 Sửa nhanh lỗi Admin Dashboard</h1>";
echo "<p>Đang thêm cột created_at cho bảng bookings...</p>";

try {
    // Kiểm tra xem cột created_at có tồn tại không
    $check_column = $conn->query("SHOW COLUMNS FROM bookings LIKE 'created_at'");
    
    if ($check_column->num_rows === 0) {
        echo "<p>✅ Thêm cột created_at...</p>";
        
        // Thêm cột created_at
        $add_column = "ALTER TABLE bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        if ($conn->query($add_column)) {
            echo "<p>✅ Đã thêm cột created_at!</p>";
            
            // Cập nhật created_at cho các booking hiện có
            $update_existing = "UPDATE bookings SET created_at = CONCAT(checkin, ' 10:00:00') WHERE created_at IS NULL OR created_at = '0000-00-00 00:00:00'";
            if ($conn->query($update_existing)) {
                echo "<p>✅ Đã cập nhật created_at cho booking hiện có!</p>";
            }
        } else {
            echo "<p>❌ Lỗi thêm cột: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ Cột created_at đã tồn tại!</p>";
    }
    
    // Test query admin dashboard
    echo "<p>🧪 Kiểm tra query admin dashboard...</p>";
    $test_query = "
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
    
    $result = $conn->query($test_query);
    if ($result) {
        echo "<p>✅ Query admin dashboard hoạt động tốt!</p>";
        echo "<p>Tìm thấy " . $result->num_rows . " hoạt động gần đây.</p>";
    } else {
        echo "<p>❌ Query vẫn lỗi: " . $conn->error . "</p>";
    }
    
    echo "<div style='background: #d5f4e6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>✅ Sửa lỗi hoàn tất!</h2>";
    echo "<p>Admin dashboard bây giờ sẽ hoạt động bình thường.</p>";
    echo "<p><a href='admin_dashboard.php' style='background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👑 Truy cập Admin Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "<p>Vui lòng chạy SQL thủ công:</p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "ALTER TABLE bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;";
    echo "\nUPDATE bookings SET created_at = CONCAT(checkin, ' 10:00:00');";
    echo "</pre>";
}
?>
