<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require admin or staff permission
requireLogin();
requirePermission('view_reports');

$page_title = "Check-in/Check-out - Hotel Management";
$success_message = "";
$error_message = "";

// Handle check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkin'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    try {
        // Update booking status to checked-in and room status to occupied
        $conn->begin_transaction();
        
        $update_booking = $conn->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ? AND status = 'booked' AND checkin = CURDATE()");
        $update_booking->bind_param("i", $booking_id);
        
        if ($update_booking->execute() && $update_booking->affected_rows > 0) {
            // Update room status
            $conn->query("UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.status = 'occupied' WHERE b.id = $booking_id");
            
            $conn->commit();
            $success_message = "✅ Check-in thành công cho booking #$booking_id!";
        } else {
            $conn->rollback();
            $error_message = "❌ Không thể check-in. Kiểm tra lại booking ID hoặc ngày check-in!";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "❌ Lỗi: " . $e->getMessage();
    }
}

// Handle check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    try {
        $conn->begin_transaction();
        
        // Update booking status to checked-out and room status to cleaning
        $update_booking = $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ? AND status = 'checked_in'");
        $update_booking->bind_param("i", $booking_id);
        
        if ($update_booking->execute() && $update_booking->affected_rows > 0) {
            // Update room status to cleaning (housekeeping)
            $conn->query("UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.status = 'cleaning' WHERE b.id = $booking_id");
            
            $conn->commit();
            $success_message = "✅ Check-out thành công cho booking #$booking_id! Phòng chuyển trạng thái 'Đang dọn dẹp'.";
        } else {
            $conn->rollback();
            $error_message = "❌ Không thể check-out. Kiểm tra lại booking ID!";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "❌ Lỗi: " . $e->getMessage();
    }
}

// Handle room cleaning completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clean_complete'])) {
    $room_id = (int)$_POST['room_id'];
    
    try {
        $update_room = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ? AND status = 'cleaning'");
        $update_room->bind_param("i", $room_id);
        
        if ($update_room->execute() && $update_room->affected_rows > 0) {
            $success_message = "✅ Phòng #$room_id đã được dọn dẹp xong và sẵn sàng cho khách mới!";
        } else {
            $error_message = "❌ Không thể cập nhật trạng thái phòng!";
        }
    } catch (Exception $e) {
        $error_message = "❌ Lỗi: " . $e->getMessage();
    }
}

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary">🏨 Check-in & Check-out Management</h2>
        <p class="text-muted">Quản lý việc nhận phòng và trả phòng của khách hàng</p>
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

<!-- Today's Check-ins -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h3 class="card-title fs-5 mb-0">📅 Check-in hôm nay (<?php echo date('d/m/Y'); ?>)</h3>
    </div>
    <div class="card-body">
        <?php
        $today = date('Y-m-d');
        $checkin_today = $conn->query("
            SELECT b.id, b.status, r.room_number, r.floor, r.type,
                   g.name as guest_name, g.phone, g.id_card,
                   u.username
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN guests g ON b.guest_id = g.id
            LEFT JOIN users u ON g.user_id = u.id
            WHERE b.checkin = '$today' AND b.status IN ('booked', 'checked_in')
            ORDER BY r.room_number
        ");
        ?>
        
        <?php if ($checkin_today->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="checkinTable" class="table table-hover w-100">
                    <thead class="table-success">
                        <tr>
                            <th>Booking ID</th>
                            <th>Phòng</th>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <th>CMND/CCCD</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $checkin_today->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <strong><?php echo $booking['room_number']; ?></strong><br>
                                    <small class="text-muted">Tầng <?php echo $booking['floor']; ?> - <?php echo $booking['type']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                                <td><?php echo htmlspecialchars($booking['id_card'] ?: 'Chưa có'); ?></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'booked' => 'bg-primary',
                                        'checked_in' => 'bg-success'
                                    ];
                                    $status_text = [
                                        'booked' => '📋 Chờ check-in',
                                        'checked_in' => '🏠 Đã check-in'
                                    ];
                                    $badge_class = $status_badges[$booking['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $status_text[$booking['status']] ?? $booking['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] === 'booked'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="checkin" class="btn btn-success btn-lg">
                                                <i class="fas fa-key"></i> <i class="fas fa-lock"></i> Check-in
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success small">✅ Đã nhận phòng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">📅 Không có check-in nào hôm nay</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Today's Check-outs -->
<div class="card shadow mb-4">
    <div class="card-header bg-warning text-dark">
        <h3 class="card-title fs-5 mb-0">🚪 Check-out hôm nay (<?php echo date('d/m/Y'); ?>)</h3>
    </div>
    <div class="card-body">
        <?php
        $checkout_today = $conn->query("
            SELECT b.id, b.status, r.room_number, r.floor, r.type, b.total_price,
                   g.name as guest_name, g.phone,
                   DATEDIFF(b.checkout, b.checkin) as nights
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN guests g ON b.guest_id = g.id
            WHERE b.checkout = '$today' AND b.status IN ('checked_in', 'checked_out')
            ORDER BY r.room_number
        ");
        ?>
        
        <?php if ($checkout_today->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="checkoutTable" class="table table-hover w-100">
                    <thead class="table-warning">
                        <tr>
                            <th>Booking ID</th>
                            <th>Phòng</th>
                            <th>Khách hàng</th>
                            <th>Số đêm</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $checkout_today->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <strong><?php echo $booking['room_number']; ?></strong><br>
                                    <small class="text-muted">Tầng <?php echo $booking['floor']; ?> - <?php echo $booking['type']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?php echo $booking['nights']; ?> đêm</td>
                                <td><strong><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</strong></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'checked_in' => 'bg-warning',
                                        'checked_out' => 'bg-success'
                                    ];
                                    $status_text = [
                                        'checked_in' => '🏠 Đang ở',
                                        'checked_out' => '✅ Đã trả phòng'
                                    ];
                                    $badge_class = $status_badges[$booking['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $status_text[$booking['status']] ?? $booking['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] === 'checked_in'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận check-out cho booking này?')">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="checkout" class="btn btn-warning btn-sm">
                                                🚪 Check-out
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success small">✅ Đã trả phòng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">🚪 Không có check-out nào hôm nay</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Room Cleaning Status -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-broom"></i> Trạng thái dọn phòng</h3>
    </div>
    <div class="card-body">
        <?php
        $cleaning_rooms = $conn->query("
            SELECT r.id, r.room_number, r.floor, r.type, r.status
            FROM rooms r
            WHERE r.status IN ('cleaning', 'occupied')
            ORDER BY r.floor, r.room_number
        ");
        ?>
        
        <?php if ($cleaning_rooms->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="cleaningTable" class="table table-hover w-100">
                    <thead class="table-info">
                        <tr>
                            <th>Phòng</th>
                            <th>Loại</th>
                            <th>Tầng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($room = $cleaning_rooms->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $room['room_number']; ?></strong></td>
                                <td><?php echo $room['type']; ?></td>
                                <td>Tầng <?php echo $room['floor']; ?></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'occupied' => 'bg-warning',
                                        'cleaning' => 'bg-info'
                                    ];
                                    $status_text = [
                                        'occupied' => '🏠 Có khách',
                                        'cleaning' => '<i class="fas fa-broom"></i> Đang dọn'
                                    ];
                                    $badge_class = $status_badges[$room['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $status_text[$room['status']] ?? $room['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($room['status'] === 'cleaning'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" name="clean_complete" class="btn btn-success btn-sm">
                                                ✨ Hoàn thành
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-warning small">⏳ Chờ check-out</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-success py-4">
                <p class="fs-5">✨ Tất cả phòng đang sẵn sàng!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-4 mb-4">
    <?php
    $stats = $conn->query("
        SELECT 
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked
        FROM rooms
    ")->fetch_assoc();
    
    $checkin_pending = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE checkin = '$today' AND status = 'booked'")->fetch_assoc();
    $checkout_pending = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE checkout = '$today' AND status = 'checked_in'")->fetch_assoc();
    ?>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><i class="fas fa-home"></i></div>
                <div class="display-4 fw-bold mb-2"><?php echo $stats['available']; ?></div>
                <div class="fs-5">Phòng trống</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-warning text-dark text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><i class="fas fa-users"></i></div>
                <div class="display-4 fw-bold mb-2"><?php echo $stats['occupied']; ?></div>
                <div class="fs-5">Có khách</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-info text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><i class="fas fa-broom"></i></div>
                <div class="display-4 fw-bold mb-2"><?php echo $stats['cleaning']; ?></div>
                <div class="fs-5">Đang dọn</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-primary text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><i class="fas fa-calendar-check"></i></div>
                <div class="display-4 fw-bold mb-2"><?php echo $checkin_pending['count']; ?></div>
                <div class="fs-5">Chờ check-in</div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables for all tables
    if ($('#checkinTable').length) {
        $('#checkinTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[1, 'asc']], // Sort by room number
            columnDefs: [
                {
                    targets: [6], // Action column
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
    
    if ($('#checkoutTable').length) {
        $('#checkoutTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[1, 'asc']], // Sort by room number
            columnDefs: [
                {
                    targets: [6], // Action column
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
    
    if ($('#cleaningTable').length) {
        $('#cleaningTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[0, 'asc']], // Sort by room number
            columnDefs: [
                {
                    targets: [4], // Action column
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
});
</script>
