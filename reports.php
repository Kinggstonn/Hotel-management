<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require login and view_reports permission
requireLogin();
requirePermission('view_reports');

$page_title = "Báo cáo - Hotel Management";
include "includes/header.php"; 

// Thống kê theo tháng hiện tại (chỉ tính booking đã thực hiện, không tính cancelled)
$current_month = date('Y-m');
$monthly_stats = $conn->query("
    SELECT 
        COUNT(*) as total_bookings,
        COALESCE(SUM(total_price), 0) as total_revenue
    FROM bookings 
    WHERE DATE_FORMAT(checkin, '%Y-%m') = '$current_month'
    AND status != 'cancelled'
    AND checkin <= CURDATE()
")->fetch_assoc();
$monthly_stats['avg_booking_value'] = $monthly_stats['total_bookings'] > 0
    ? $monthly_stats['total_revenue'] / $monthly_stats['total_bookings']
    : 0;

// Top khách hàng - hiển thị tất cả khách có giao dịch, thống kê chính xác số booking và tổng chi tiêu
$top_customers = $conn->query("
    SELECT 
        g.id,
        g.name, 
        g.phone, 
        COUNT(b.id) as bookings, 
        COALESCE(SUM(b.total_price), 0) as total_spent
    FROM bookings b
    JOIN guests g ON g.id = b.guest_id
    WHERE b.status != 'cancelled'
    AND b.checkin <= CURDATE()
    GROUP BY g.id, g.name, g.phone
    HAVING total_spent > 0
    ORDER BY total_spent DESC
");

// Thống kê theo loại phòng
$room_type_stats = $conn->query("
    SELECT 
        r.type,
        COUNT(r.id) as total_rooms,
        SUM(CASE WHEN r.status = 'booked' THEN 1 ELSE 0 END) as booked_rooms
    FROM rooms r
    GROUP BY r.type
");

// Doanh thu 7 ngày gần đây (chỉ tính booking đã thực hiện, không tính cancelled)
$daily_revenue = $conn->query("
    SELECT 
        DATE(checkin) as booking_date,
        COUNT(*) as bookings,
        SUM(total_price) as revenue
    FROM bookings 
    WHERE checkin >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND checkin <= CURDATE()
    AND status != 'cancelled'
    GROUP BY DATE(checkin)
    ORDER BY booking_date DESC
");
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-chart-line"></i> Báo cáo chi tiết</h2>
        <p class="text-muted">Báo cáo tổng hợp về hoạt động kinh doanh và tình hình phòng</p>
    </div>
</div>

<!-- Monthly Overview -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-calendar-alt"></i> Tổng quan tháng <?php echo date('m/Y'); ?></h3>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-primary text-white text-center h-100 shadow">
                    <div class="card-body">
                        <div class="display-4 fw-bold mb-2"><?php echo $monthly_stats['total_bookings']; ?></div>
                        <div class="fs-5">📝 Tổng booking</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-success text-white text-center h-100 shadow">
                    <div class="card-body">
                        <div class="display-4 fw-bold mb-2"><?php echo number_format($monthly_stats['total_revenue'], 0, ',', '.'); ?></div>
                        <div class="fs-5"><i class="fas fa-dollar-sign"></i> Doanh thu (VND)</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-info text-white text-center h-100 shadow">
                    <div class="card-body">
                        <div class="display-4 fw-bold mb-2"><?php echo number_format($monthly_stats['avg_booking_value'], 0, ',', '.'); ?></div>
                        <div class="fs-5"><i class="fas fa-chart-bar"></i> Giá trị TB/booking</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-warning text-dark text-center h-100 shadow">
                    <div class="card-body">
                        <div class="display-4 fw-bold mb-2"><?php echo date('d'); ?></div>
                        <div class="fs-5">📆 Ngày trong tháng</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Type Statistics -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-home"></i> Thống kê theo loại phòng</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="roomTypeStatsTable" class="table table-hover w-100">
                <thead class="table-info">
                    <tr>
                        <th>Loại phòng</th>
                        <th>Tổng phòng</th>
                        <th>Đang sử dụng</th>
                        <th>Trạng thái sử dụng</th>
                        <th>Tỷ lệ sử dụng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($room_type = $room_type_stats->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo ucfirst($room_type['type']); ?></strong>
                            </td>
                            <td><?php echo $room_type['total_rooms']; ?></td>
                            <td><?php echo $room_type['booked_rooms']; ?></td>
                            <td>
                                <?php 
                                $usage_rate = $room_type['total_rooms'] > 0 ? ($room_type['booked_rooms'] / $room_type['total_rooms']) * 100 : 0;
                                $status_class = $usage_rate > 80 ? 'bg-success' : ($usage_rate > 50 ? 'bg-warning' : 'bg-info');
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo number_format($usage_rate, 1); ?>%
                                </span>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?php echo $status_class; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $usage_rate; ?>%"
                                         aria-valuenow="<?php echo $usage_rate; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo number_format($usage_rate, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Customers -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-crown"></i> Top khách hàng tiềm năng</h3>
    </div>
    <div class="card-body">
        <?php if ($top_customers->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="topCustomersTable" class="table table-hover w-100">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Số lần đặt phòng</th>
                            <th>Tổng chi tiêu</th>
                            <th>Giá trị trung bình</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($customer = $top_customers->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <?php if ($rank == 1): ?>
                                        <span class="badge bg-warning">🥇</span>
                                    <?php elseif ($rank == 2): ?>
                                        <span class="badge bg-secondary">🥈</span>
                                    <?php elseif ($rank == 3): ?>
                                        <span class="badge bg-warning">🥉</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                </td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $customer['bookings']; ?> lần</span>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo number_format($customer['total_spent'], 0, ',', '.'); ?> VND</strong>
                                </td>
                                <td>
                                    <?php echo number_format($customer['total_spent'] / $customer['bookings'], 0, ',', '.'); ?> VND
                                </td>
                            </tr>
                        <?php 
                            $rank++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">📭 Chưa có dữ liệu khách hàng!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Daily Revenue Chart -->
<div class="card shadow mb-4">
    <div class="card-header bg-warning text-dark">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-chart-bar"></i> Doanh thu 7 ngày gần đây</h3>
    </div>
    <div class="card-body">
        <?php if ($daily_revenue->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="dailyRevenueTable" class="table table-hover w-100">
                    <thead class="table-warning">
                        <tr>
                            <th>Ngày</th>
                            <th>Số booking</th>
                            <th>Doanh thu</th>
                            <th>Biểu đồ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_revenue = 0;
                        $daily_data = [];
                        
                        // Reset pointer and get max revenue
                        $daily_revenue->data_seek(0);
                        while ($day = $daily_revenue->fetch_assoc()) {
                            $daily_data[] = $day;
                            if ($day['revenue'] > $max_revenue) {
                                $max_revenue = $day['revenue'];
                            }
                        }
                        
                        foreach ($daily_data as $day): 
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('d/m/Y', strtotime($day['booking_date'])); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $day['bookings']; ?> booking</span>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo number_format($day['revenue'], 0, ',', '.'); ?> VND</strong>
                                </td>
                                <td>
                                    <?php 
                                    $bar_width = $max_revenue > 0 ? ($day['revenue'] / $max_revenue) * 100 : 0;
                                    ?>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             style="width: <?php echo $bar_width; ?>%"
                                             aria-valuenow="<?php echo $day['revenue']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $max_revenue; ?>">
                                            <?php echo number_format($day['revenue'], 0, ',', '.'); ?> VND
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5"><i class="fas fa-chart-bar"></i> Chưa có dữ liệu doanh thu!</p>
            </div>
        <?php endif; ?>
    </div>
</div>



<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($('#roomTypeStatsTable').length) {
        $('#roomTypeStatsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            ordering: false, // No sorting for stats table
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[5, 10, -1], [5, 10, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
    
    if ($('#topCustomersTable').length) {
        $('#topCustomersTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[4, 'desc']], // Sort by total spent
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[5, 10, -1], [5, 10, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
    
    if ($('#dailyRevenueTable').length) {
        $('#dailyRevenueTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 7,
            order: [[0, 'desc']], // Sort by date
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[7, 14, -1], [7, 14, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
});
</script>
