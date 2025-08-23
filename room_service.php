<?php 
include "db.php"; 
include "auth/auth_functions.php";

requireLogin();

$page_title = "Room Service - Hotel Management";
$success_message = "";
$error_message = "";

// Create room_services table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS room_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        room_id INT,
        service_type ENUM('cleaning', 'maintenance', 'minibar', 'laundry', 'food_drink', 'towels', 'amenities', 'other') DEFAULT 'other',
        description TEXT,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) DEFAULT 0,
        status ENUM('requested', 'in_progress', 'completed', 'cancelled') DEFAULT 'requested',
        requested_by INT,
        assigned_to INT,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        notes TEXT,
        FOREIGN KEY (booking_id) REFERENCES bookings(id),
        FOREIGN KEY (room_id) REFERENCES rooms(id),
        FOREIGN KEY (requested_by) REFERENCES users(id),
        FOREIGN KEY (assigned_to) REFERENCES users(id)
    )
");

// Thêm dữ liệu mẫu nếu bảng trống
$check_data = $conn->query("SELECT COUNT(*) as count FROM room_services");
$data_count = $check_data->fetch_assoc()['count'];

if ($data_count == 0) {
    // Kiểm tra xem có booking nào không
    $booking_check = $conn->query("SELECT id, room_id FROM bookings LIMIT 1");
    if ($booking_check->num_rows > 0) {
        $sample_booking = $booking_check->fetch_assoc();
        $booking_id = $sample_booking['id'];
        $room_id = $sample_booking['room_id'];
        $user_id = 1; // Giả sử user ID = 1
        
        // Thêm dữ liệu mẫu
        $conn->query("
            INSERT INTO room_services (booking_id, room_id, service_type, description, quantity, price, status, requested_by) VALUES
            ($booking_id, $room_id, 'cleaning', 'Dọn dẹp phòng', 1, 0, 'requested', $user_id),
            ($booking_id, $room_id, 'maintenance', 'Sửa chữa điều hòa', 1, 50000, 'in_progress', $user_id),
            ($booking_id, $room_id, 'minibar', 'Bổ sung minibar', 1, 0, 'completed', $user_id)
        ");
    }
}

// Handle new service request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_service'])) {
    $booking_id = (int)$_POST['booking_id'];
    $service_type = $_POST['service_type'];
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    
    try {
        // Get room_id from booking
        $booking_query = $conn->query("SELECT room_id FROM bookings WHERE id = $booking_id AND status IN ('checked_in', 'booked')");
        if ($booking_query->num_rows === 0) {
            throw new Exception("Booking không hợp lệ hoặc khách chưa check-in!");
        }
        
        $booking = $booking_query->fetch_assoc();
        $room_id = $booking['room_id'];
        $user_id = $_SESSION['user_id'];
        
        $insert_service = $conn->prepare("
            INSERT INTO room_services (booking_id, room_id, service_type, description, quantity, price, requested_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert_service->bind_param("iissidi", $booking_id, $room_id, $service_type, $description, $quantity, $price, $user_id);
        
        if ($insert_service->execute()) {
            $success_message = "<i class='fas fa-check-circle'></i> Yêu cầu dịch vụ đã được gửi thành công!";
        } else {
            $error_message = "<i class='fas fa-exclamation-triangle'></i> Có lỗi khi tạo yêu cầu dịch vụ!";
        }
        
    } catch (Exception $e) {
        $error_message = "<i class='fas fa-exclamation-triangle'></i> Lỗi: " . $e->getMessage();
    }
}

// Handle service status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $service_id = (int)$_POST['service_id'];
    $new_status = $_POST['new_status'];
    $notes = trim($_POST['notes']);
    
    try {
        $user_id = $_SESSION['user_id'];
        
        if ($new_status === 'completed') {
            $update_query = $conn->prepare("
                UPDATE room_services 
                SET status = ?, completed_at = NOW(), assigned_to = ?, notes = ? 
                WHERE id = ?
            ");
            $update_query->bind_param("sisi", $new_status, $user_id, $notes, $service_id);
        } else {
            $update_query = $conn->prepare("
                UPDATE room_services 
                SET status = ?, assigned_to = ?, notes = ? 
                WHERE id = ?
            ");
            $update_query->bind_param("sisi", $new_status, $user_id, $notes, $service_id);
        }
        
        if ($update_query->execute()) {
            $success_message = "<i class='fas fa-check-circle'></i> Cập nhật trạng thái dịch vụ thành công!";
        } else {
            $error_message = "<i class='fas fa-exclamation-triangle'></i> Không thể cập nhật trạng thái!";
        }
        
    } catch (Exception $e) {
        $error_message = "<i class='fas fa-exclamation-triangle'></i> Lỗi: " . $e->getMessage();
    }
}

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary">🔔 Room Service Management</h2>
        <p class="text-muted">Quản lý dịch vụ phòng và yêu cầu của khách hàng</p>
    </div>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- New Service Request -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0">➕ Yêu cầu dịch vụ mới</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="booking_id" class="form-label">
                        <i class="fas fa-calendar-check"></i> Booking ID *
                    </label>
                    <select class="form-select" id="booking_id" name="booking_id" required>
                        <option value="">Chọn booking...</option>
                        <?php
                        $active_bookings = $conn->query("
                            SELECT b.id, r.room_number, g.name as guest_name, b.checkin, b.checkout
                            FROM bookings b
                            JOIN rooms r ON b.room_id = r.id
                            JOIN guests g ON b.guest_id = g.id
                            WHERE b.status IN ('checked_in', 'booked')
                            ORDER BY b.checkin DESC
                        ");
                        while ($booking = $active_bookings->fetch_assoc()):
                        ?>
                            <option value="<?php echo $booking['id']; ?>">
                                #<?php echo $booking['id']; ?> - Phòng <?php echo $booking['room_number']; ?> 
                                (<?php echo htmlspecialchars($booking['guest_name']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="invalid-feedback">
                        Vui lòng chọn booking
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="service_type" class="form-label">
                        <i class="fas fa-tools"></i> Loại dịch vụ *
                    </label>
                    <select class="form-select" id="service_type" name="service_type" required>
                        <option value="">Chọn loại dịch vụ...</option>
                        <option value="cleaning"><i class="fas fa-broom"></i> Dọn dẹp phòng</option>
                        <option value="maintenance">🔧 Bảo trì/sửa chữa</option>
                        <option value="minibar">🍷 Minibar</option>
                        <option value="laundry">👕 Giặt ủi</option>
                        <option value="food_drink"><i class="fas fa-utensils"></i> Đồ ăn/đồ uống</option>
                        <option value="towels">🛁 Khăn tắm</option>
                        <option value="amenities">🧴 Đồ dùng cá nhân</option>
                        <option value="other">📝 Khác</option>
                    </select>
                    <div class="invalid-feedback">
                        Vui lòng chọn loại dịch vụ
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="description" class="form-label">
                        <i class="fas fa-id-card"></i> Mô tả chi tiết *
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Mô tả chi tiết yêu cầu dịch vụ..." required></textarea>
                    <div class="invalid-feedback">
                        Vui lòng nhập mô tả dịch vụ
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="quantity" class="form-label">
                        <i class="fas fa-hashtag"></i> Số lượng
                    </label>
                    <input type="number" class="form-control" id="quantity" name="quantity" 
                           value="1" min="1" max="100">
                </div>
                
                <div class="col-md-3">
                    <label for="price" class="form-label">
                        <i class="fas fa-dollar-sign"></i> Giá (VND)
                    </label>
                    <input type="number" class="form-control" id="price" name="price" 
                           value="0" min="0" step="1000">
                </div>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="request_service" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane"></i> 📤 Gửi yêu cầu dịch vụ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Active Services -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0">🔄 Dịch vụ đang hoạt động</h3>
    </div>
    <div class="card-body">
        <?php
        // Kiểm tra xem bảng room_services có tồn tại không
        $table_exists = $conn->query("SHOW TABLES LIKE 'room_services'")->num_rows > 0;
        
        if ($table_exists) {
            $active_services = $conn->query("
                SELECT rs.*, r.room_number, r.floor, r.type,
                       g.name as guest_name, g.phone,
                       u1.username as requested_by_user,
                       u2.username as assigned_to_user
                FROM room_services rs
                JOIN rooms r ON rs.room_id = r.id
                JOIN bookings b ON rs.booking_id = b.id
                JOIN guests g ON b.guest_id = g.id
                LEFT JOIN users u1 ON rs.requested_by = u1.id
                LEFT JOIN users u2 ON rs.assigned_to = u2.id
                WHERE rs.status IN ('requested', 'in_progress')
                ORDER BY rs.requested_at DESC
            ");
        } else {
            $active_services = null;
        }
        ?>
        
        <?php if (!$table_exists): ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">⚠️ Bảng room_services chưa được tạo!</p>
                <p class="text-muted">Vui lòng chạy setup database để tạo bảng cần thiết.</p>
            </div>
        <?php elseif ($active_services && $active_services->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="activeServicesTable" class="table table-hover w-100">
                    <thead class="table-info">
                        <tr>
                            <th>ID</th>
                            <th>Phòng</th>
                            <th>Khách hàng</th>
                            <th>Loại dịch vụ</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th>Ngày yêu cầu</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $active_services->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $service['id']; ?></td>
                                <td>
                                    <strong><?php echo $service['room_number']; ?></strong><br>
                                    <small class="text-muted">Tầng <?php echo $service['floor']; ?> - <?php echo $service['type']; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($service['guest_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($service['phone']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $service_icons = [
                                        'cleaning' => '<i class="fas fa-broom"></i>',
                                        'maintenance' => '🔧',
                                        'minibar' => '🍷',
                                        'laundry' => '👕',
                                        'food_drink' => '<i class="fas fa-utensils"></i>',
                                        'towels' => '🛁',
                                        'amenities' => '🧴',
                                        'other' => '📝'
                                    ];
                                    $service_names = [
                                        'cleaning' => 'Dọn dẹp',
                                        'maintenance' => 'Bảo trì',
                                        'minibar' => 'Minibar',
                                        'laundry' => 'Giặt ủi',
                                        'food_drink' => 'Đồ ăn',
                                        'towels' => 'Khăn tắm',
                                        'amenities' => 'Đồ dùng',
                                        'other' => 'Khác'
                                    ];
                                    ?>
                                    <span class="badge bg-primary">
                                        <?php echo $service_icons[$service['service_type']] ?? '📝'; ?> 
                                        <?php echo $service_names[$service['service_type']] ?? $service['service_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($service['description']); ?>">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </div>
                                    <?php if ($service['quantity'] > 1): ?>
                                        <small class="text-muted">Số lượng: <?php echo $service['quantity']; ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($service['price'] > 0): ?>
                                        <small class="text-success">Giá: <?php echo number_format($service['price'], 0, ',', '.'); ?> VND</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'requested' => 'bg-warning',
                                        'in_progress' => 'bg-info'
                                    ];
                                    $status_text = [
                                        'requested' => '⏳ Chờ xử lý',
                                        'in_progress' => '🔄 Đang thực hiện'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$service['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo $status_text[$service['status']] ?? $service['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($service['requested_at'])); ?><br>
                                    <small class="text-muted">Bởi: <?php echo $service['requested_by_user'] ?? 'N/A'; ?></small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#updateStatusModal<?php echo $service['id']; ?>">
                                        ✏️ Cập nhật
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Update Status Modal -->
                            <div class="modal fade" id="updateStatusModal<?php echo $service['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Cập nhật trạng thái dịch vụ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Phòng:</label>
                                                    <input type="text" class="form-control" value="<?php echo $service['room_number']; ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Loại dịch vụ:</label>
                                                    <input type="text" class="form-control" value="<?php echo $service_names[$service['service_type']] ?? $service['service_type']; ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="new_status<?php echo $service['id']; ?>" class="form-label">Trạng thái mới *</label>
                                                    <select class="form-select" id="new_status<?php echo $service['id']; ?>" name="new_status" required>
                                                        <option value="requested" <?php echo $service['status'] === 'requested' ? 'selected' : ''; ?>>⏳ Chờ xử lý</option>
                                                        <option value="in_progress" <?php echo $service['status'] === 'in_progress' ? 'selected' : ''; ?>>🔄 Đang thực hiện</option>
                                                        <option value="completed">✅ Hoàn thành</option>
                                                        <option value="cancelled">❌ Đã hủy</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="notes<?php echo $service['id']; ?>" class="form-label">Ghi chú</label>
                                                    <textarea class="form-control" id="notes<?php echo $service['id']; ?>" name="notes" rows="3" 
                                                              placeholder="Ghi chú về trạng thái dịch vụ..."><?php echo htmlspecialchars($service['notes'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" name="update_status" class="btn btn-primary">
                                                    ✏️ Cập nhật
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">✨ Không có dịch vụ nào đang hoạt động!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Completed Services -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-check-circle"></i> Dịch vụ đã hoàn thành</h3>
    </div>
    <div class="card-body">
        <?php
        // Kiểm tra xem bảng room_services có tồn tại không
        $table_exists = $conn->query("SHOW TABLES LIKE 'room_services'")->num_rows > 0;
        
        if ($table_exists) {
            $completed_services = $conn->query("
                SELECT rs.*, r.room_number, r.floor, r.type,
                       g.name as guest_name, g.phone,
                       u1.username as requested_by_user,
                       u2.username as assigned_to_user
                FROM room_services rs
                JOIN rooms r ON rs.room_id = r.id
                JOIN bookings b ON rs.booking_id = b.id
                JOIN guests g ON b.guest_id = g.id
                LEFT JOIN users u1 ON rs.requested_by = u1.id
                LEFT JOIN users u2 ON rs.assigned_to = u2.id
                WHERE rs.status IN ('completed', 'cancelled')
                ORDER BY rs.completed_at DESC, rs.requested_at DESC
                LIMIT 50
            ");
        } else {
            $completed_services = null;
        }
        ?>
        
        <?php if (!$table_exists): ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">⚠️ Bảng room_services chưa được tạo!</p>
                <p class="text-muted">Vui lòng chạy setup database để tạo bảng cần thiết.</p>
            </div>
        <?php elseif ($completed_services && $completed_services->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="completedServicesTable" class="table table-hover w-100">
                    <thead class="table-success">
                        <tr>
                            <th>ID</th>
                            <th>Phòng</th>
                            <th>Khách hàng</th>
                            <th>Loại dịch vụ</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th>Ngày hoàn thành</th>
                            <th>Người thực hiện</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $completed_services->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $service['id']; ?></td>
                                <td>
                                    <strong><?php echo $service['room_number']; ?></strong><br>
                                    <small class="text-muted">Tầng <?php echo $service['floor']; ?> - <?php echo $service['type']; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($service['guest_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($service['phone']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $service_icons = [
                                        'cleaning' => '<i class="fas fa-broom"></i>',
                                        'maintenance' => '🔧',
                                        'minibar' => '🍷',
                                        'laundry' => '👕',
                                        'food_drink' => '<i class="fas fa-utensils"></i>',
                                        'towels' => '🛁',
                                        'amenities' => '🧴',
                                        'other' => '📝'
                                    ];
                                    $service_names = [
                                        'cleaning' => 'Dọn dẹp',
                                        'maintenance' => 'Bảo trì',
                                        'minibar' => 'Minibar',
                                        'laundry' => 'Giặt ủi',
                                        'food_drink' => 'Đồ ăn',
                                        'towels' => 'Khăn tắm',
                                        'amenities' => 'Đồ dùng',
                                        'other' => 'Khác'
                                    ];
                                    ?>
                                    <span class="badge bg-primary">
                                        <?php echo $service_icons[$service['service_type']] ?? '📝'; ?> 
                                        <?php echo $service_names[$service['service_type']] ?? $service['service_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($service['description']); ?>">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </div>
                                    <?php if ($service['quantity'] > 1): ?>
                                        <small class="text-muted">Số lượng: <?php echo $service['quantity']; ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($service['price'] > 0): ?>
                                        <small class="text-success">Giá: <?php echo number_format($service['price'], 0, ',', '.'); ?> VND</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $status_text = [
                                        'completed' => '✅ Hoàn thành',
                                        'cancelled' => '❌ Đã hủy'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$service['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo $status_text[$service['status']] ?? $service['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($service['completed_at']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($service['completed_at'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($service['assigned_to_user']): ?>
                                        <?php echo $service['assigned_to_user']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">📭 Chưa có dịch vụ nào hoàn thành!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<?php if (!$table_exists): ?>
    <div class="alert alert-warning text-center mb-4">
        <h5><i class="fas fa-exclamation-triangle"></i> Bảng room_services chưa được tạo!</h5>
        <p class="mb-0">Vui lòng chạy setup database để tạo bảng cần thiết.</p>
    </div>
<?php else: ?>
     <div class="row g-4 mb-4">
     <?php
     // Debug: Kiểm tra dữ liệu trong bảng room_services
     $debug_query = $conn->query("SELECT status, COUNT(*) as count FROM room_services GROUP BY status");
     $debug_data = [];
     while ($row = $debug_query->fetch_assoc()) {
         $debug_data[$row['status']] = $row['count'];
     }
     
     // Sửa lại query để đảm bảo hoạt động đúng
     $service_stats = $conn->query("
         SELECT 
             COUNT(*) as total_services,
             SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) as pending_services,
             SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_services,
             SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_services,
             SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_services
         FROM room_services
     ");
     
     if ($service_stats) {
         $service_stats = $service_stats->fetch_assoc();
     } else {
         $service_stats = [
             'total_services' => 0,
             'pending_services' => 0,
             'active_services' => 0,
             'completed_services' => 0,
             'cancelled_services' => 0
         ];
     }
     
     // Đảm bảo các giá trị không null
     $service_stats['total_services'] = $service_stats['total_services'] ?? 0;
     $service_stats['pending_services'] = $service_stats['pending_services'] ?? 0;
     $service_stats['active_services'] = $service_stats['active_services'] ?? 0;
     $service_stats['completed_services'] = $service_stats['completed_services'] ?? 0;
     $service_stats['cancelled_services'] = $service_stats['cancelled_services'] ?? 0;
     
     $today_services = $conn->query("
         SELECT COUNT(*) as count
         FROM room_services 
         WHERE DATE(requested_at) = CURDATE()
     ");
     
     if ($today_services) {
         $today_services = $today_services->fetch_assoc();
     } else {
         $today_services = ['count' => 0];
     }
     
     $today_services['count'] = $today_services['count'] ?? 0;
     ?>
     
     
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-primary text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $service_stats['total_services']; ?></div>
                <div class="fs-5">🔔 Tổng dịch vụ</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-warning text-dark text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $service_stats['pending_services']; ?></div>
                <div class="fs-5">⏳ Chờ xử lý</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-info text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $service_stats['active_services']; ?></div>
                <div class="fs-5">🔄 Đang thực hiện</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $today_services['count']; ?></div>
                <div class="fs-5"><i class="fas fa-calendar-day"></i> Hôm nay</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($('#activeServicesTable').length) {
        $('#activeServicesTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[6, 'desc']], // Sort by requested date
            columnDefs: [
                {
                    targets: [7], // Action column
                    orderable: false,
                    searchable: false,
                    width: '100px'
                }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
    
    if ($('#completedServicesTable').length) {
        $('#completedServicesTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 25,
            order: [[6, 'desc']], // Sort by completion date
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
    
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
});
</script>
