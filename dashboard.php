<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require login and view_reports permission
requireLogin();
requirePermission('view_reports');

$page_title = "Dashboard - Hotel Management";
include "includes/header.php"; 

$today = date('Y-m-d');

$room_stats_query = "
    SELECT 
        COUNT(*) as total_rooms,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_rooms,
        SUM(CASE WHEN type = 'VIP' THEN 1 ELSE 0 END) as vip_rooms,
        SUM(CASE WHEN type = 'Thường' THEN 1 ELSE 0 END) as regular_rooms
    FROM rooms
";

// Thống kê booking và doanh thu hôm nay
$booking_stats_query = "
    SELECT 
        COALESCE(SUM(CASE WHEN checkin = '$today' AND status != 'cancelled' THEN total_price ELSE 0 END), 0) as today_revenue,
        COUNT(CASE WHEN checkin = '$today' AND status != 'cancelled' THEN 1 END) as today_bookings
    FROM bookings
";

$room_stats = $conn->query($room_stats_query)->fetch_assoc();
$booking_stats = $conn->query($booking_stats_query)->fetch_assoc();

// Kết hợp thống kê
$stats = array_merge($room_stats, $booking_stats);

$room_stats = $conn->query($room_stats_query)->fetch_assoc();
$booking_stats = $conn->query($booking_stats_query)->fetch_assoc();

// Kết hợp thống kê
$stats = array_merge($room_stats, $booking_stats);


$recent_bookings = $conn->query("
    SELECT b.*, g.name as guest_name, r.room_number, r.type as room_type 
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    ORDER BY b.id DESC 
    LIMIT 5
");

// Check-in hôm nay
$today_checkins = $conn->query("
    SELECT b.*, g.name as guest_name, r.room_number 
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.checkin = '$today'
    ORDER BY r.room_number
");

// Check-out hôm nay  
$today_checkouts = $conn->query("
    SELECT b.*, g.name as guest_name, r.room_number 
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.checkout = '$today'
    ORDER BY r.room_number
");
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-tachometer-alt"></i> Dashboard Tổng Quan</h2>
        <p class="text-muted">Chào mừng bạn đến với hệ thống quản lý khách sạn! Dưới đây là tình hình hiện tại.</p>
    </div>
</div>

<!-- Main Statistics -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-primary text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['total_rooms']; ?></div>
                <div class="fs-5"><i class="fas fa-home"></i> Tổng phòng</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['available_rooms']; ?></div>
                <div class="fs-5"><i class="fas fa-check-circle"></i> Phòng trống</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-warning text-dark text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['booked_rooms']; ?></div>
                <div class="fs-5"><i class="fas fa-calendar-check"></i> Đã đặt</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-info text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['vip_rooms']; ?></div>
                <div class="fs-5"><i class="fas fa-crown"></i> Phòng VIP</div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Statistics -->
<div class="row g-4 mb-4">
    <div class="col-lg-6 col-md-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo number_format($stats['today_revenue'], 0, ',', '.'); ?></div>
                <div class="fs-5"><i class="fas fa-dollar-sign"></i> Doanh thu hôm nay (VND)</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-12">
        <div class="card bg-info text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $stats['today_bookings']; ?></div>
                <div class="fs-5"><i class="fas fa-list-alt"></i> Booking hôm nay</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card shadow mb-4">
    <div class="card-body">
        <h3 class="card-title fs-4 text-primary">⚡ Thao tác nhanh</h3>
        <div class="d-flex flex-wrap gap-2">
            <a href="index.php" class="btn btn-primary rounded-pill">
                <i class="fas fa-home"></i> Xem tất cả phòng
            </a>
            <a href="book.php" class="btn btn-success rounded-pill">
                <i class="fas fa-calendar-plus"></i> Đặt phòng mới
            </a>
            <a href="payment_billing.php" class="btn btn-info rounded-pill">
                <i class="fas fa-credit-card"></i> Thanh toán
            </a>
            <a href="demo_booking_flow.php" class="btn btn-outline-primary rounded-pill">
                <i class="fas fa-route"></i> Demo luồng đặt phòng
            </a>
            <a href="reports.php" class="btn btn-warning rounded-pill">
                <i class="fas fa-chart-line"></i> Báo cáo chi tiết
            </a>
            <a href="install_database.php" class="btn btn-secondary rounded-pill">
                🔧 Cài đặt Database
            </a>
        </div>
    </div>
</div>

<!-- Today's Activity -->
<div class="row g-4 mb-4">
    
    <!-- Check-in Today -->
    <div class="col-lg-6 col-md-12">
        <div class="card shadow h-100">
            <div class="card-header bg-success text-white">
                <h3 class="card-title fs-5 mb-0">📥 Check-in hôm nay (<?php echo date('d/m/Y'); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if ($today_checkins->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table id="checkinTable" class="table table-hover w-100">
                            <thead class="table-success">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Khách</th>
                                    <th>Ngày trả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($checkin = $today_checkins->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $checkin['room_number']; ?></strong></td>
                                    <td><?php echo $checkin['guest_name']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($checkin['checkout'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        📭 Không có check-in nào hôm nay
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Check-out Today -->
    <div class="col-lg-6 col-md-12">
        <div class="card shadow h-100">
            <div class="card-header bg-warning text-dark">
                <h3 class="card-title fs-5 mb-0">📤 Check-out hôm nay (<?php echo date('d/m/Y'); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if ($today_checkouts->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table id="checkoutTable" class="table table-hover w-100">
                            <thead class="table-warning">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Khách</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($checkout = $today_checkouts->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $checkout['room_number']; ?></strong></td>
                                    <td><?php echo $checkout['guest_name']; ?></td>
                                    <td><span class="badge bg-success">Đã check-out</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        📭 Không có check-out nào hôm nay  
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0">🕒 Booking gần đây</h3>
    </div>
    <div class="card-body">
        <?php if ($recent_bookings->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="recentBookingsTable" class="table table-hover w-100">
                    <thead class="table-info">
                        <tr>
                            <th>Khách hàng</th>
                            <th>Phòng</th>
                            <th>Loại</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $booking['guest_name']; ?></td>
                            <td><strong><?php echo $booking['room_number']; ?></strong></td>
                            <td>
                                <?php echo $booking['room_type'] == 'VIP' ? '<i class="fas fa-crown"></i> VIP' : '<i class="fas fa-home"></i> Thường'; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkout'])); ?></td>
                            <td><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <p class="text-muted text-center">
                    <i class="fas fa-list-alt"></i> Chưa có booking nào trong hệ thống
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- System Status -->
<div class="card shadow mb-4">
    <div class="card-header bg-secondary text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-cog"></i> Trạng thái hệ thống</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="bg-success bg-opacity-10 p-3 rounded text-center border border-success">
                    <strong class="text-success"><i class="fas fa-check-circle"></i> Database</strong><br>
                    <small class="text-muted">Kết nối thành công</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="bg-info bg-opacity-10 p-3 rounded text-center border border-info">
                    <strong class="text-info">🕒 Thời gian</strong><br>
                    <small class="text-muted" id="current-time"><?php echo date('H:i:s d/m/Y'); ?></small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="bg-success bg-opacity-10 p-3 rounded text-center border border-success">
                    <strong class="text-success">👤 Phiên làm việc</strong><br>
                    <small class="text-muted">Hoạt động bình thường</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="bg-warning bg-opacity-10 p-3 rounded text-center border border-warning">
                    <strong class="text-warning">🔄 Cập nhật cuối</strong><br>
                    <small class="text-muted"><?php echo date('H:i d/m/Y'); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // DataTable cho Check-in hôm nay
    if ($('#checkinTable').length) {
        $('#checkinTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[0, 'asc']], // Sắp xếp theo số phòng
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
    
    // DataTable cho Check-out hôm nay
    if ($('#checkoutTable').length) {
        $('#checkoutTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[0, 'asc']], // Sắp xếp theo số phòng
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
    
    // DataTable cho Booking gần đây
    if ($('#recentBookingsTable').length) {
        $('#recentBookingsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[3, 'desc']], // Sắp xếp theo ngày check-in mới nhất
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
