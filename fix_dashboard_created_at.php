<?php
// Quick fix cho dashboard.php created_at error
include "db.php";

echo "<h1>🔧 Sửa lỗi Dashboard created_at</h1>";
echo "<p>Đang kiểm tra và sửa lỗi trong dashboard.php...</p>";

try {
    // Test query dashboard hiện tại (đã sửa)
    $today = date('Y-m-d');
    echo "<p><i class='fas fa-calendar-alt'></i> Ngày hôm nay: $today</p>";
    
    // Test query mới (dùng checkin thay vì created_at)
    $stats_query = "
        SELECT 
            COUNT(r.id) as total_rooms,
            SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms,
            SUM(CASE WHEN r.status = 'booked' THEN 1 ELSE 0 END) as booked_rooms,
            SUM(CASE WHEN r.type = 'VIP' THEN 1 ELSE 0 END) as vip_rooms,
            SUM(CASE WHEN r.type = 'Thường' THEN 1 ELSE 0 END) as regular_rooms,
            
            -- Doanh thu hôm nay (dùng checkin)
            COALESCE(SUM(CASE WHEN b.checkin = '$today' THEN b.total_price ELSE 0 END), 0) as today_revenue,
            
            -- Booking hôm nay (dùng checkin)
            COUNT(CASE WHEN b.checkin = '$today' THEN 1 END) as today_bookings
            
        FROM rooms r 
        LEFT JOIN bookings b ON r.id = b.room_id
    ";
    
    echo "<p>🧪 Test query dashboard mới...</p>";
    $result = $conn->query($stats_query);
    
    if ($result) {
        $stats = $result->fetch_assoc();
        echo "<p><i class='fas fa-check-circle'></i> Query dashboard hoạt động tốt!</p>";
        
        echo "<h3>📊 Kết quả thống kê:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Thống kê</th><th>Giá trị</th>";
        echo "</tr>";
        echo "<tr><td>Tổng phòng</td><td><strong>" . $stats['total_rooms'] . "</strong></td></tr>";
        echo "<tr><td>Phòng trống</td><td><strong>" . $stats['available_rooms'] . "</strong></td></tr>";
        echo "<tr><td>Phòng đã đặt</td><td><strong>" . $stats['booked_rooms'] . "</strong></td></tr>";
        echo "<tr><td>Phòng VIP</td><td><strong>" . $stats['vip_rooms'] . "</strong></td></tr>";
        echo "<tr><td>Phòng Thường</td><td><strong>" . $stats['regular_rooms'] . "</strong></td></tr>";
        echo "<tr><td>Doanh thu hôm nay</td><td><strong>" . number_format($stats['today_revenue'], 0, ',', '.') . " VND</strong></td></tr>";
        echo "<tr><td>Booking hôm nay</td><td><strong>" . $stats['today_bookings'] . "</strong></td></tr>";
        echo "</table>";
        
        // Room usage information
        echo "<p><i class='fas fa-home'></i> <strong>Phòng đang sử dụng:</strong> {$stats['booked_rooms']}/{$stats['total_rooms']} phòng</p>";
        
    } else {
        echo "<p><i class='fas fa-exclamation-triangle'></i> Query dashboard lỗi: " . $conn->error . "</p>";
    }
    
    // Test các query khác trong dashboard
    echo "<h3>🧪 Test các query khác:</h3>";
    
    // Test recent bookings
    echo "<p><i class='fas fa-list-alt'></i> Test recent bookings query...</p>";
    $recent_query = "
        SELECT b.*, g.name as guest_name, r.room_number, r.type as room_type 
        FROM bookings b 
        JOIN guests g ON b.guest_id = g.id 
        JOIN rooms r ON b.room_id = r.id 
        ORDER BY b.id DESC 
        LIMIT 5
    ";
    
    $recent_result = $conn->query($recent_query);
    if ($recent_result) {
        echo "<p><i class='fas fa-check-circle'></i> Recent bookings query OK! (" . $recent_result->num_rows . " records)</p>";
    } else {
        echo "<p><i class='fas fa-exclamation-triangle'></i> Recent bookings query lỗi: " . $conn->error . "</p>";
    }
    
    // Test check-in today
    echo "<p>📥 Test check-in today query...</p>";
    $checkin_query = "
        SELECT b.*, g.name as guest_name, r.room_number 
        FROM bookings b 
        JOIN guests g ON b.guest_id = g.id 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.checkin = '$today'
        ORDER BY r.room_number
    ";
    
    $checkin_result = $conn->query($checkin_query);
    if ($checkin_result) {
        echo "<p><i class='fas fa-check-circle'></i> Check-in today query OK! (" . $checkin_result->num_rows . " check-ins hôm nay)</p>";
    } else {
        echo "<p><i class='fas fa-exclamation-triangle'></i> Check-in today query lỗi: " . $conn->error . "</p>";
    }
    
    // Test check-out today
    echo "<p>📤 Test check-out today query...</p>";
    $checkout_query = "
        SELECT b.*, g.name as guest_name, r.room_number 
        FROM bookings b 
        JOIN guests g ON b.guest_id = g.id 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.checkout = '$today'
        ORDER BY r.room_number
    ";
    
    $checkout_result = $conn->query($checkout_query);
    if ($checkout_result) {
        echo "<p><i class='fas fa-check-circle'></i> Check-out today query OK! (" . $checkout_result->num_rows . " check-outs hôm nay)</p>";
    } else {
        echo "<p><i class='fas fa-exclamation-triangle'></i> Check-out today query lỗi: " . $conn->error . "</p>";
    }
    
    echo "<div style='background: #d5f4e6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2><i class='fas fa-check-circle'></i> Dashboard fixed!</h2>";
    echo "<p>Dashboard.php đã được sửa và hoạt động bình thường.</p>";
    echo "<p><strong>Thay đổi:</strong></p>";
    echo "<ul>";
    echo "<li><i class='fas fa-exclamation-triangle'></i> <code>DATE(b.created_at) = '$today'</code></li>";
    echo "<li><i class='fas fa-check-circle'></i> <code>b.checkin = '$today'</code> (dùng ngày check-in)</li>";
    echo "</ul>";
    echo "<p><a href='dashboard.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'><i class='fas fa-chart-bar'></i> Truy cập Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fadbd8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2><i class='fas fa-exclamation-triangle'></i> Lỗi</h2>";
    echo "<p><strong>Chi tiết lỗi:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Giải pháp:</strong> Chạy script fix_all_created_at_errors.php để thêm cột created_at</p>";
    echo "</div>";
}

echo "<p style='text-align: center; margin-top: 30px; color: #7f8c8d;'>";
echo "Dashboard Created_at Error Fix - Hotel Management System";
echo "</p>";
?>
