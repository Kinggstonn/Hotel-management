<?php
// Auto complete bookings that have passed checkout date
include "db.php";

echo "<h1><i class='fas fa-sync-alt'></i> Tự động hoàn thành booking đã hết hạn</h1>";
echo "<p>Tự động chuyển trạng thái booking đã checkout thành 'completed'</p>";

try {
    $today = date('Y-m-d');
    
    // Find bookings that should be completed (checkout date has passed)
    $to_complete_query = "
        SELECT id, room_id, guest_id, checkin, checkout, total_price
        FROM bookings 
        WHERE checkout < '$today' 
        AND status = 'booked'
    ";
    
    $to_complete = $conn->query($to_complete_query);
    
    if ($to_complete && $to_complete->num_rows > 0) {
        echo "<h3><i class='fas fa-list-alt'></i> Booking cần hoàn thành:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Booking ID</th><th>Check-in</th><th>Check-out</th><th>Tổng tiền</th><th>Trạng thái</th>";
        echo "</tr>";
        
        $completed_count = 0;
        while ($booking = $to_complete->fetch_assoc()) {
            echo "<tr>";
            echo "<td>#" . $booking['id'] . "</td>";
            echo "<td>" . $booking['checkin'] . "</td>";
            echo "<td>" . $booking['checkout'] . "</td>";
            echo "<td>" . number_format($booking['total_price'], 0, ',', '.') . " VND</td>";
            
            // Update booking status to completed
            $update_query = "UPDATE bookings SET status = 'completed' WHERE id = " . $booking['id'];
            if ($conn->query($update_query)) {
                echo "<td style='color: green;'><strong>✅ Completed</strong></td>";
                $completed_count++;
                
                // Update room status to available (if not already booked by someone else)
                $conn->query("UPDATE rooms SET status = 'available' WHERE id = " . $booking['room_id'] . " AND status = 'booked'");
            } else {
                echo "<td style='color: red;'>❌ Error</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #d5f4e6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2><i class='fas fa-check-circle'></i> Hoàn thành!</h2>";
        echo "<p>Đã chuyển <strong>$completed_count</strong> booking sang trạng thái 'completed'</p>";
        echo "<p>Những booking này giờ sẽ được tính vào doanh thu và thống kê khách hàng thân thiết.</p>";
        echo "</div>";
        
    } else {
        echo "<p style='background: #fff3cd; padding: 15px; border-radius: 10px;'>📋 Không có booking nào cần hoàn thành.</p>";
    }
    
    // Show current booking status distribution
    echo "<h3><i class='fas fa-chart-bar'></i> Phân bố trạng thái booking:</h3>";
    $status_stats = $conn->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_price) as total_revenue
        FROM bookings 
        GROUP BY status
        ORDER BY count DESC
    ");
    
    if ($status_stats) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Trạng thái</th><th>Số lượng</th><th>Doanh thu</th><th>Ý nghĩa</th>";
        echo "</tr>";
        
        while ($stat = $status_stats->fetch_assoc()) {
            $status_meaning = match($stat['status']) {
                'booked' => 'Đang hoạt động (được tính vào thống kê)',
                'completed' => 'Đã hoàn thành (được tính vào doanh thu)',
                'cancelled' => 'Đã hủy (KHÔNG tính vào doanh thu)',
                'early_checkout' => 'Checkout sớm (được tính vào doanh thu)',
                default => 'Trạng thái khác'
            };
            
            $status_color = match($stat['status']) {
                'booked' => '#007cba',
                'completed' => '#27ae60',
                'cancelled' => '#e74c3c',
                'early_checkout' => '#f39c12',
                default => '#6c757d'
            };
            
            echo "<tr>";
            echo "<td style='color: $status_color;'><strong>" . $stat['status'] . "</strong></td>";
            echo "<td>" . $stat['count'] . "</td>";
            echo "<td>" . number_format($stat['total_revenue'], 0, ',', '.') . " VND</td>";
            echo "<td style='font-size: 0.9rem; color: #6c757d;'>$status_meaning</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div class='nav-buttons' style='text-align: center; margin-top: 30px;'>";
    echo "<a href='reports.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'><i class='fas fa-chart-line'></i> Xem báo cáo cập nhật</a> ";
    echo "<a href='dashboard.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'><i class='fas fa-chart-bar'></i> Dashboard</a> ";
    echo "<a href='manage_bookings.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'><i class='fas fa-tools'></i> Quản lý Booking</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fadbd8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2><i class='fas fa-times-circle'></i> Lỗi</h2>";
    echo "<p><strong>Chi tiết:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p style='text-align: center; margin-top: 30px; color: #7f8c8d; font-size: 0.9rem;'>";
echo "Auto Complete Bookings - Hotel Management System<br>";
echo "Chạy script này định kỳ để đảm bảo dữ liệu thống kê chính xác";
echo "</p>";
?>
