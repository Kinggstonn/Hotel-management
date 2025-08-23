<?php
include "db.php";

echo "<h2>Kiểm tra dữ liệu phòng</h2>";

// Kiểm tra kết nối database
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
echo "<p><i class='fas fa-check-circle'></i> Kết nối database thành công</p>";

// Kiểm tra bảng rooms
$check_table = $conn->query("SHOW TABLES LIKE 'rooms'");
if ($check_table->num_rows === 0) {
    echo "<p><i class='fas fa-exclamation-triangle'></i> Bảng 'rooms' không tồn tại</p>";
    exit;
}
echo "<p><i class='fas fa-check-circle'></i> Bảng 'rooms' tồn tại</p>";

// Kiểm tra cấu trúc bảng
$structure = $conn->query("DESCRIBE rooms");
echo "<h3>Cấu trúc bảng rooms:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Kiểm tra dữ liệu
$count = $conn->query("SELECT COUNT(*) as total FROM rooms");
$total = $count->fetch_assoc()['total'];
echo "<p><strong>Tổng số phòng: {$total}</strong></p>";

if ($total > 0) {
    // Hiển thị một số phòng mẫu
    $sample = $conn->query("SELECT * FROM rooms LIMIT 5");
    echo "<h3>Mẫu dữ liệu (5 phòng đầu):</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Số phòng</th><th>Tầng</th><th>Loại</th><th>Giá</th><th>Trạng thái</th></tr>";
    while ($room = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$room['id']}</td>";
        echo "<td>{$room['room_number']}</td>";
        echo "<td>{$room['floor']}</td>";
        echo "<td>{$room['type']}</td>";
        echo "<td>{$room['price']}</td>";
        echo "<td>{$room['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Thống kê theo trạng thái
    $status_stats = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM rooms 
        GROUP BY status
    ");
    echo "<h3>Thống kê theo trạng thái:</h3>";
    while ($stat = $status_stats->fetch_assoc()) {
        echo "<p>{$stat['status']}: {$stat['count']} phòng</p>";
    }
    
    // Thống kê theo loại
    $type_stats = $conn->query("
        SELECT 
            type,
            COUNT(*) as count
        FROM rooms 
        GROUP BY type
    ");
    echo "<h3>Thống kê theo loại:</h3>";
    while ($stat = $type_stats->fetch_assoc()) {
        echo "<p>{$stat['type']}: {$stat['count']} phòng</p>";
    }
    
} else {
    echo "<p><i class='fas fa-exclamation-triangle'></i> Không có dữ liệu phòng nào trong bảng</p>";
    
    // Tạo dữ liệu mẫu
    echo "<h3>Tạo dữ liệu mẫu:</h3>";
    $sample_rooms = [
        ['101', 1, 'Thường', 500000, 'available'],
        ['102', 1, 'Thường', 500000, 'available'],
        ['201', 2, 'VIP', 800000, 'available'],
        ['202', 2, 'VIP', 800000, 'available'],
        ['301', 3, 'Thường', 600000, 'available']
    ];
    
    $insert_sql = "INSERT INTO rooms (room_number, floor, type, price, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    $success_count = 0;
    foreach ($sample_rooms as $room) {
        $stmt->bind_param("sisis", $room[0], $room[1], $room[2], $room[3], $room[4]);
        if ($stmt->execute()) {
            $success_count++;
            echo "<p><i class='fas fa-check-circle'></i> Đã tạo phòng {$room[0]} - Tầng {$room[1]} - {$room[2]} - {$room[3]} VND</p>";
        } else {
            echo "<p><i class='fas fa-exclamation-triangle'></i> Lỗi tạo phòng {$room[0]}: " . $stmt->error . "</p>";
        }
    }
    
    if ($success_count > 0) {
        echo "<p><strong>Đã tạo thành công {$success_count} phòng mẫu!</strong></p>";
        echo "<p><a href='index.php'>Quay lại trang chủ để xem danh sách phòng</a></p>";
    }
}

$conn->close();
?>
