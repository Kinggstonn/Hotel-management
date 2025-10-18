<?php
include "db.php";
include "auth/auth_functions.php";

// Require staff or admin
requireLogin();
if (!in_array(getUserRole(), ['admin', 'staff'])) {
    $_SESSION['error'] = 'Chỉ admin và nhân viên mới có quyền quản lý booking!';
    header('Location: dashboard.php');
    exit();
}

$page_title = "Quản lý Booking - Hotel Management";

// Handle cancel booking
if (isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Get booking and room info
    $booking_query = "SELECT b.room_id, b.checkin, b.checkout, b.status FROM bookings b WHERE b.id = $booking_id";
    $booking_result = $conn->query($booking_query);
    
    if ($booking_result && $booking_result->num_rows > 0) {
        $booking_data = $booking_result->fetch_assoc();
        $today = date('Y-m-d');
        
        // Check if booking can be cancelled
        if ($booking_data['checkin'] > $today) {
            // Booking chưa check-in -> Hủy hoàn toàn (ảnh hưởng thống kê)
            $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
            $success_message = "✅ Đã hủy booking #$booking_id (chưa check-in) - Không ảnh hưởng doanh thu!";
        } elseif ($booking_data['checkin'] <= $today && $booking_data['checkout'] > $today) {
            // Booking đang diễn ra -> Chuyển sang early checkout
            $conn->query("UPDATE bookings SET status = 'early_checkout', checkout = '$today' WHERE id = $booking_id");
            $success_message = "✅ Đã checkout sớm booking #$booking_id - Doanh thu được tính từ ngày check-in đến hôm nay!";
        } else {
            // Booking đã hoàn thành -> Không thể hủy
            $error_message = "❌ Không thể hủy booking đã hoàn thành!";
        }
        
        // Update room status to available (nếu không có lỗi)
        if (!isset($error_message)) {
            $conn->query("UPDATE rooms SET status = 'available' WHERE id = " . $booking_data['room_id']);
        }
    } else {
        $error_message = "❌ Không tìm thấy booking!";
    }
}

// Handle restore booking
if (isset($_POST['restore_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Get room info to update status
    $room_query = "SELECT room_id FROM bookings WHERE id = $booking_id";
    $room_result = $conn->query($room_query);
    
    if ($room_result && $room_result->num_rows > 0) {
        $room_data = $room_result->fetch_assoc();
        
        // Check if room is available
        $room_status_query = "SELECT status FROM rooms WHERE id = " . $room_data['room_id'];
        $room_status_result = $conn->query($room_status_query);
        $room_status = $room_status_result->fetch_assoc()['status'];
        
        if ($room_status === 'available') {
            // Restore booking
            $conn->query("UPDATE bookings SET status = 'booked' WHERE id = $booking_id");
            
            // Update room status to booked
            $conn->query("UPDATE rooms SET status = 'booked' WHERE id = " . $room_data['room_id']);
            
            $success_message = "✅ Đã khôi phục booking #$booking_id thành công!";
        } else {
            $error_message = "❌ Không thể khôi phục - phòng đã được đặt bởi khách khác!";
        }
    }
}

// Get all bookings with filters
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';

$where_conditions = [];
if ($filter_status && $filter_status !== 'all') {
    $where_conditions[] = "b.status = '$filter_status'";
}
if ($filter_date) {
    $where_conditions[] = "b.checkin = '$filter_date'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$bookings_query = "
    SELECT b.*, r.room_number, r.type as room_type, r.floor,
           g.name as guest_name, g.phone as guest_phone,
           u.username, u.role as user_role
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id  
    JOIN guests g ON b.guest_id = g.id
    LEFT JOIN users u ON g.user_id = u.id
    $where_clause
    ORDER BY b.id DESC
";

$bookings = $conn->query($bookings_query);

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as active_bookings,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        SUM(CASE WHEN status = 'booked' THEN total_price ELSE 0 END) as active_revenue
    FROM bookings
")->fetch_assoc();

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-tools"></i> Quản lý Booking - <?php echo getRoleDisplayName(getUserRole()); ?></h2>
        <p class="text-muted">Quản lý tất cả booking trong hệ thống (Chỉ Admin & Staff)</p>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-primary text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['total_bookings']; ?></div>
                <div class="fs-5"><i class="fas fa-chart-bar"></i> Tổng booking</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['active_bookings']; ?></div>
                <div class="fs-5">📅 Đang hoạt động</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-danger text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['cancelled_bookings']; ?></div>
                <div class="fs-5">❌ Đã hủy</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-warning text-dark text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo number_format($stats['active_revenue'], 0, ',', '.'); ?></div>
                <div class="fs-5"><i class="fas fa-dollar-sign"></i> Doanh thu hoạt động (VND)</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0">🔍 Lọc booking</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">Trạng thái:</label>
                <select name="status" id="status" class="form-select">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="booked" <?php echo $filter_status === 'booked' ? 'selected' : ''; ?>>Đang hoạt động</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Ngày check-in:</label>
                <input type="date" name="date" id="date" value="<?php echo $filter_date; ?>" class="form-control">
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search"></i> 🔍 Lọc
                </button>
                <a href="manage_bookings.php" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> 🔄 Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bookings List -->
<?php if ($bookings && $bookings->num_rows > 0): ?>
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0">📋 Danh sách booking (<?php echo $bookings->num_rows; ?> booking)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="manageBookingsTable" class="table table-hover w-100">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>Phòng</th>
                        <th>Loại</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>User</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <tr class="<?php echo $booking['status'] === 'cancelled' ? 'table-secondary' : ''; ?>">
                        <td><strong>#<?php echo $booking['id']; ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                            <small class="text-muted"><?php echo $booking['guest_phone']; ?></small>
                        </td>
                        <td>
                            <strong><?php echo $booking['room_number']; ?></strong><br>
                            <small class="text-muted">Tầng <?php echo $booking['floor']; ?></small>
                        </td>
                        <td>
                            <?php echo $booking['room_type'] === 'VIP' ? '👑 VIP' : '🏠 Thường'; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($booking['checkout'])); ?></td>
                        <td><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</td>
                        <td>
                            <?php
                            switch ($booking['status']) {
                                case 'pending_payment':
                                    echo '<span class="badge bg-warning">⏳ Chờ thanh toán</span>';
                                    break;
                                case 'booked':
                                    echo '<span class="badge bg-primary">📅 Đang hoạt động</span>';
                                    break;
                                case 'cancelled':
                                    echo '<span class="badge bg-danger">❌ Đã hủy</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">' . $booking['status'] . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($booking['username']): ?>
                                <strong><?php echo $booking['username']; ?></strong><br>
                                <small class="text-muted"><?php echo getRoleDisplayName($booking['user_role']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">Walk-in</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <?php if ($booking['status'] === 'pending_payment'): ?>
                                    <!-- Payment button for pending payment -->
                                    <a href="payment_form.php?booking_id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-warning btn-sm">
                                        💳 Thanh toán
                                    </a>
                                    <!-- Cancel booking -->
                                    <a href="cancel_booking.php?booking_id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-danger btn-sm">
                                        ❌ Hủy booking
                                    </a>
                                <?php elseif ($booking['status'] === 'booked'): ?>
                                    <!-- Payment button -->
                                    <a href="payment_form.php?booking_id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        💳 Thanh toán
                                    </a>
                                    <!-- Cancel booking -->
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Bạn có chắc muốn HỦY booking #<?php echo $booking['id']; ?>?\n\nPhòng <?php echo $booking['room_number']; ?> sẽ trở về trạng thái trống.')">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="cancel_booking" 
                                                class="btn btn-warning btn-sm">
                                            ❌ Hủy booking
                                        </button>
                                    </form>
                                <?php elseif ($booking['status'] === 'cancelled'): ?>
                                    <!-- Restore booking -->
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Bạn có chắc muốn KHÔI PHỤC booking #<?php echo $booking['id']; ?>?\n\nPhòng <?php echo $booking['room_number']; ?> sẽ được đánh dấu là đã đặt.')">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="restore_booking" 
                                                class="btn btn-success btn-sm">
                                            🔄 Khôi phục
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<div class="card shadow text-center">
    <div class="card-body py-5">
        <h3 class="text-muted mb-3">📭 Không có booking nào</h3>
        <?php if ($filter_status || $filter_date): ?>
            <p class="text-muted mb-4">Không tìm thấy booking với bộ lọc hiện tại.</p>
            <a href="manage_bookings.php" class="btn btn-primary btn-lg">
                🔄 Xem tất cả booking
            </a>
        <?php else: ?>
            <p class="text-muted mb-4">Chưa có booking nào trong hệ thống.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card shadow">
    <div class="card-header bg-secondary text-white">
        <h3 class="card-title fs-5 mb-0">⚡ Thao tác nhanh</h3>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="index.php" class="btn btn-primary">
                                        <i class="fas fa-home"></i> 🏠 Xem danh sách phòng
            </a>
            <a href="book.php" class="btn btn-success">
                                        <i class="fas fa-calendar-plus"></i> 📅 Tạo booking mới
            </a>
            <?php if (getUserRole() === 'admin'): ?>
            <a href="admin/admin_dashboard.php" class="btn btn-warning">
                                        <i class="fas fa-crown"></i> 👑 Admin Dashboard
            </a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-chart-line"></i> <i class="fas fa-chart-bar"></i> Về Dashboard
            </a>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    if ($('#manageBookingsTable').length) {
        $('#manageBookingsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 25,
            order: [[0, 'desc']], // Sắp xếp theo ID booking mới nhất
            columnDefs: [
                {
                    targets: [9], // Cột hành động
                    orderable: false,
                    searchable: false,
                    width: '120px'
                }
            ],
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
});
</script>
