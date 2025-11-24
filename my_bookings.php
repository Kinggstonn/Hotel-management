<?php
include "db.php";
include "auth/auth_functions.php";

// Require customer login
requireLogin();
$page_title = "Booking của tôi - Hotel Management";

$user = getUserInfo();
$user_id = $user['id'];

// Handle cancel booking
if (isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    if (canCancelBooking($booking_id, $conn)) {
        // Get room info to update status
        $room_query = "SELECT room_id FROM bookings WHERE id = $booking_id";
        $room_result = $conn->query($room_query);
        $room_data = $room_result->fetch_assoc();
        
        // Cancel booking
        $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
        
        // Update room status
        $conn->query("UPDATE rooms SET status = 'available' WHERE id = " . $room_data['room_id']);
        
        $success_message = "Đã hủy booking thành công!";
    } else {
        $error_message = "Bạn không có quyền hủy booking này!";
    }
}

// Get user's bookings (sử dụng id thay vì created_at)
$bookings_query = "
    SELECT b.*, r.room_number, r.type as room_type, r.price,
           g.name as guest_name, g.phone as guest_phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id  
    JOIN guests g ON b.guest_id = g.id
    WHERE g.user_id = $user_id
    ORDER BY b.id DESC
";

$bookings = $conn->query($bookings_query);

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary">📅 Booking của tôi</h2>
        <p class="text-muted">Quản lý các booking bạn đã thực hiện</p>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if ($bookings->num_rows > 0): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h3 class="card-title fs-5 mb-0">📋 Danh sách booking</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="myBookingsTable" class="table table-hover w-100">
                    <thead class="table-info">
                        <tr>
                            <th>Booking ID</th>
                            <th>Phòng</th>
                            <th>Loại</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $booking['id']; ?></strong></td>
                            <td><?php echo $booking['room_number']; ?></td>
                            <td>
                                <?php echo $booking['room_type'] === 'VIP' ? '👑 VIP' : '🏠 Thường'; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkout'])); ?></td>
                            <td><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</td>
                            <td>
                                <?php
                                switch ($booking['status']) {
                                    case 'booked':
                                        echo '<span class="badge bg-primary">📅 Đã đặt</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="badge bg-danger">❌ Đã hủy</span>';
                                        break;
                                    case 'completed':
                                        echo '<span class="badge bg-success">✅ Hoàn thành</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">❓ ' . $booking['status'] . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'booked' && $booking['checkin'] > date('Y-m-d')): ?>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Bạn có chắc muốn hủy booking này?')">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="cancel_booking" 
                                                class="btn btn-warning btn-sm">
                                            ❌ Hủy booking
                                        </button>
                                    </form>
                                <?php elseif ($booking['checkin'] <= date('Y-m-d')): ?>
                                    <span class="text-muted small">Không thể hủy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Booking Statistics -->
    <?php
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as active_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN status IN ('booked', 'early_checkout') AND checkin <= CURDATE() THEN total_price ELSE 0 END) as total_spent
        FROM bookings b
        JOIN guests g ON b.guest_id = g.id  
        WHERE g.user_id = $user_id
    ")->fetch_assoc();
    ?>
    
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
                    <div class="display-4 fw-bold mb-2"><?php echo number_format($stats['total_spent'], 0, ',', '.'); ?></div>
                    <div class="fs-5"><i class="fas fa-dollar-sign"></i> Tổng chi tiêu (VND)</div>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <div class="card shadow text-center">
        <div class="card-body py-5">
            <h3 class="text-muted mb-3">📭 Chưa có booking nào</h3>
            <p class="text-muted mb-4">Bạn chưa thực hiện booking nào. Hãy đặt phòng ngay!</p>
            <a href="index.php" class="btn btn-primary btn-lg">
                🏠 Xem danh sách phòng
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    if ($('#myBookingsTable').length) {
        $('#myBookingsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[0, 'desc']], // Sắp xếp theo ID booking mới nhất
            columnDefs: [
                {
                    targets: [7], // Cột hành động
                    orderable: false,
                    searchable: false,
                    width: '120px'
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
});
</script>
