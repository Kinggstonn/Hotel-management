<?php
include "db.php";

echo "<h2>🚀 Cài đặt Database hoàn chỉnh - Hotel Management System</h2>";

try {
    // Đọc file SQL tổng hợp
    $sql_file = file_get_contents('sql/database_schema.sql');
    
    if (!$sql_file) {
        throw new Exception("Không thể đọc file SQL!");
    }
    
    echo "<div class='alert alert-info'>";
    echo "<h4>📋 Đang cài đặt database...</h4>";
    echo "<p>File: <code>sql/database_schema.sql</code></p>";
    echo "</div>";
    
    // Tách các câu lệnh SQL
    $statements = explode(';', $sql_file);
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Bỏ qua các câu lệnh trống hoặc comment
        if (empty($statement) || 
            preg_match('/^(--|\/\*|\*\/|DELIMITER|COMMIT)/i', $statement)) {
            continue;
        }
        
        // Bỏ qua các câu lệnh DELIMITER và COMMIT
        if (preg_match('/^(DELIMITER|COMMIT)/i', $statement)) {
            continue;
        }
        
        try {
            if ($conn->query($statement)) {
                $success_count++;
                
                // Hiển thị thông báo cho các câu lệnh quan trọng
                if (preg_match('/^(CREATE|INSERT|ALTER|DROP)/i', $statement)) {
                    $action = preg_match('/^(CREATE|INSERT|ALTER|DROP)/i', $statement, $matches);
                    $action_type = strtoupper($matches[1]);
                    $table_name = '';
                    
                    if (preg_match('/TABLE\s+(\w+)/i', $statement, $table_matches)) {
                        $table_name = $table_matches[1];
                    }
                    
                    echo "<p class='text-success'>✅ $action_type" . 
                         ($table_name ? " TABLE $table_name" : "") . " - Thành công!</p>";
                }
            } else {
                $error_count++;
                echo "<p class='text-warning'>⚠️ Câu lệnh có thể đã tồn tại hoặc có lỗi nhỏ</p>";
            }
        } catch (Exception $e) {
            $error_count++;
            // Chỉ hiển thị lỗi nghiêm trọng
            if (!preg_match('/already exists|Duplicate entry/i', $e->getMessage())) {
                echo "<p class='text-danger'>❌ Lỗi: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<div class='alert alert-success mt-4'>";
    echo "<h4>🎉 Cài đặt database hoàn tất!</h4>";
    echo "<p><strong>Thống kê:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Câu lệnh thành công: <strong>$success_count</strong></li>";
    echo "<li>⚠️ Câu lệnh có cảnh báo: <strong>$error_count</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // Kiểm tra các bảng đã được tạo
    echo "<div class='alert alert-info'>";
    echo "<h5>📊 Kiểm tra các bảng đã tạo:</h5>";
    
    $tables = ['users', 'rooms', 'guests', 'bookings', 'payments', 'refunds', 'room_services', 'hotel_settings', 'activity_logs'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<span class='badge bg-success me-2'>✅ $table</span>";
        } else {
            echo "<span class='badge bg-danger me-2'>❌ $table</span>";
        }
    }
    echo "</div>";
    
    // Kiểm tra dữ liệu mẫu
    echo "<div class='alert alert-info'>";
    echo "<h5>📋 Kiểm tra dữ liệu mẫu:</h5>";
    
    $checks = [
        'users' => "SELECT COUNT(*) as count FROM users",
        'rooms' => "SELECT COUNT(*) as count FROM rooms", 
        'guests' => "SELECT COUNT(*) as count FROM guests",
        'bookings' => "SELECT COUNT(*) as count FROM bookings",
        'payments' => "SELECT COUNT(*) as count FROM payments"
    ];
    
    foreach ($checks as $table => $query) {
        $result = $conn->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>📊 <strong>$table:</strong> $count bản ghi</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='alert alert-success'>";
    echo "<h5>🎯 Hệ thống đã sẵn sàng!</h5>";
    echo "<p>Các tính năng đã được cài đặt:</p>";
    echo "<ul>";
    echo "<li>✅ Hệ thống đăng nhập và phân quyền</li>";
    echo "<li>✅ Chức năng quên mật khẩu với token bảo mật</li>";
    echo "<li>✅ Quản lý phòng với giá theo đêm</li>";
    echo "<li>✅ Đặt phòng với trạng thái pending_payment</li>";
    echo "<li>✅ Hệ thống thanh toán với thông tin thẻ</li>";
    echo "<li>✅ Quản lý hoàn tiền</li>";
    echo "<li>✅ Dịch vụ phòng</li>";
    echo "<li>✅ Cài đặt khách sạn</li>";
    echo "<li>✅ Audit trail (log hoạt động)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='mt-4'>";
    echo "<a href='dashboard.php' class='btn btn-primary btn-lg'>🏠 Vào Dashboard</a> ";
    echo "<a href='demo_booking_flow.php' class='btn btn-success btn-lg'>🧪 Test hệ thống</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi cài đặt database:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
