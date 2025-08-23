<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require login and view_reports permission
requireLogin();
requirePermission('view_reports');

$page_title = "Advanced Reports & Analytics - Hotel Management";

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'overview';
$room_type = $_GET['room_type'] ?? 'all';

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-chart-bar"></i> Advanced Reports & Analytics</h2>
        <p class="text-muted">Báo cáo chi tiết và phân tích dữ liệu kinh doanh khách sạn</p>
    </div>
</div>

<!-- Report Filters -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0">🔍 Bộ lọc báo cáo</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="date_from" class="form-label">📅 Từ ngày:</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
        </div>
        
            <div class="col-md-2">
                <label for="date_to" class="form-label">📅 Đến ngày:</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
        </div>
        
            <div class="col-md-2">
                <label for="report_type" class="form-label">📋 Loại báo cáo:</label>
                <select class="form-select" id="report_type" name="report_type">
                <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>><i class="fas fa-chart-bar"></i> Tổng quan</option>
                <option value="revenue" <?php echo $report_type == 'revenue' ? 'selected' : ''; ?>><i class="fas fa-dollar-sign"></i> Doanh thu</option>
                <option value="room_usage" <?php echo $report_type == 'room_usage' ? 'selected' : ''; ?>><i class="fas fa-hotel"></i> Sử dụng phòng</option>
                <option value="customers" <?php echo $report_type == 'customers' ? 'selected' : ''; ?>><i class="fas fa-users"></i> Khách hàng</option>
                <option value="services" <?php echo $report_type == 'services' ? 'selected' : ''; ?>><i class="fas fa-bell"></i> Dịch vụ</option>
                <option value="performance" <?php echo $report_type == 'performance' ? 'selected' : ''; ?>><i class="fas fa-bolt"></i> Hiệu suất</option>
            </select>
        </div>
        
            <div class="col-md-2">
                <label for="room_type" class="form-label">🏠 Loại phòng:</label>
                <select class="form-select" id="room_type" name="room_type">
                <option value="all" <?php echo $room_type == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                <option value="VIP" <?php echo $room_type == 'VIP' ? 'selected' : ''; ?>>👑 VIP</option>
                <option value="Thường" <?php echo $room_type == 'Thường' ? 'selected' : ''; ?>><i class="fas fa-bed"></i> Thường</option>
            </select>
        </div>
        
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> 🔍 Tạo báo cáo
            </button>
        </div>
    </form>
    
    <!-- Quick Filters -->
        <div class="d-flex flex-wrap gap-2 mt-3">
        <a href="?report_type=<?php echo $report_type; ?>&room_type=<?php echo $room_type; ?>&date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-t'); ?>" 
               class="btn btn-outline-secondary btn-sm">
                📅 Tháng này
            </a>
        <a href="?report_type=<?php echo $report_type; ?>&room_type=<?php echo $room_type; ?>&date_from=<?php echo date('Y-m-01', strtotime('-1 month')); ?>&date_to=<?php echo date('Y-m-t', strtotime('-1 month')); ?>" 
               class="btn btn-outline-secondary btn-sm">
                📅 Tháng trước
            </a>
        <a href="?report_type=<?php echo $report_type; ?>&room_type=<?php echo $room_type; ?>&date_from=<?php echo date('Y-01-01'); ?>&date_to=<?php echo date('Y-12-31'); ?>" 
               class="btn btn-outline-secondary btn-sm">
                📅 Cả năm
            </a>
        <a href="?report_type=<?php echo $report_type; ?>&room_type=<?php echo $room_type; ?>&date_from=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>" 
               class="btn btn-outline-secondary btn-sm">
                📅 30 ngày qua
            </a>
        </div>
    </div>
</div>

<?php
// Build WHERE clause for room type filter
$room_type_filter = '';
if ($room_type != 'all') {
    $room_type_filter = "AND r.type = '$room_type'";
}

// Generate reports based on type
switch ($report_type) {
    case 'overview':
        // Overview Report
        $overview_query = $conn->query("
            SELECT 
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT CASE WHEN b.status NOT IN ('cancelled') THEN b.id END) as successful_bookings,
                COUNT(DISTINCT r.id) as rooms_used,
                SUM(CASE WHEN b.status NOT IN ('cancelled') AND p.payment_status = 'completed' THEN p.amount ELSE 0 END) as total_revenue,
                AVG(DATEDIFF(b.checkout, b.checkin)) as avg_stay_duration,
                AVG(CASE WHEN b.status NOT IN ('cancelled') THEN b.total_price ELSE NULL END) as avg_booking_value,
                COUNT(DISTINCT g.id) as unique_customers
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN guests g ON b.guest_id = g.id
            LEFT JOIN payments p ON b.id = p.booking_id
            WHERE b.checkin BETWEEN '$date_from' AND '$date_to'
                $room_type_filter
        ");
        
        if ($overview_query && $overview_query->num_rows > 0) {
            $overview_data = $overview_query->fetch_assoc();
            ?>
            
            <!-- Overview Report -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title fs-5 mb-0"><i class="fas fa-chart-bar"></i> Báo cáo tổng quan</h3>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="card bg-primary text-white text-center h-100 shadow">
                                <div class="card-body">
                                    <div class="display-4 fw-bold mb-2"><?php echo $overview_data['total_bookings']; ?></div>
                                    <div class="fs-5"><i class="fas fa-file-invoice"></i> Tổng booking</div>
                                </div>
                            </div>
                </div>
                
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="card bg-success text-white text-center h-100 shadow">
                                <div class="card-body">
                                    <div class="display-4 fw-bold mb-2"><?php echo $overview_data['successful_bookings']; ?></div>
                                    <div class="fs-5"><i class="fas fa-check-circle"></i> Booking thành công</div>
                                </div>
                            </div>
                </div>
                
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="card bg-info text-white text-center h-100 shadow">
                                <div class="card-body">
                                    <div class="display-4 fw-bold mb-2"><?php echo number_format($overview_data['total_revenue'], 0, ',', '.'); ?></div>
                                    <div class="fs-5"><i class="fas fa-dollar-sign"></i> Doanh thu (VND)</div>
                                </div>
                            </div>
                </div>
                
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="card bg-warning text-dark text-center h-100 shadow">
                                <div class="card-body">
                                    <div class="display-4 fw-bold mb-2"><?php echo number_format($overview_data['avg_booking_value'], 0, ',', '.'); ?></div>
                                    <div class="fs-5"><i class="fas fa-chart-bar"></i> Giá trị TB/booking</div>
                                </div>
                            </div>
                        </div>
                </div>
                
                    <!-- Additional Metrics -->
                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-info"><i class="fas fa-chart-line"></i> Chỉ số bổ sung</h5>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="border rounded p-3">
                                                <div class="fs-4 fw-bold text-primary"><?php echo number_format($overview_data['avg_stay_duration'], 1); ?></div>
                                                <div class="text-muted">Ngày lưu trú TB</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3">
                                                <div class="fs-4 fw-bold text-success"><?php echo $overview_data['unique_customers']; ?></div>
                                                <div class="text-muted">Khách hàng duy nhất</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                </div>
                
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-warning"><i class="fas fa-chart-bar"></i> Tỷ lệ thành công</h5>
                                    <?php 
                                    $success_rate = $overview_data['total_bookings'] > 0 ? 
                                        ($overview_data['successful_bookings'] / $overview_data['total_bookings']) * 100 : 0;
                                    ?>
                                    <div class="display-4 fw-bold text-warning"><?php echo number_format($success_rate, 1); ?>%</div>
                                    <div class="progress mt-2" style="height: 25px;">
                                        <div class="progress-bar bg-warning" 
                                             role="progressbar" 
                                             style="width: <?php echo $success_rate; ?>%"
                                             aria-valuenow="<?php echo $success_rate; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo number_format($success_rate, 1); ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        
        <?php
        }
        break;
        
    case 'revenue':
        // Revenue Report
        $revenue_query = $conn->query("
            SELECT 
                DATE(b.checkin) as booking_date,
                COUNT(b.id) as bookings,
                SUM(b.total_price) as revenue,
                AVG(b.total_price) as avg_price,
                COUNT(DISTINCT g.id) as customers
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN guests g ON b.guest_id = g.id
            WHERE b.checkin BETWEEN '$date_from' AND '$date_to'
            AND b.status NOT IN ('cancelled')
            $room_type_filter
            GROUP BY DATE(b.checkin)
            ORDER BY booking_date DESC
        ");
        
        if ($revenue_query && $revenue_query->num_rows > 0) {
            ?>
            
            <!-- Revenue Report -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title fs-5 mb-0"><i class="fas fa-dollar-sign"></i> Báo cáo doanh thu</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="revenueReportTable" class="table table-hover w-100">
                            <thead class="table-success">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Số booking</th>
                                    <th>Doanh thu</th>
                                    <th>Giá TB/booking</th>
                                    <th>Số khách hàng</th>
                                    <th>Biểu đồ doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                                $max_revenue = 0;
                                $revenue_data = [];
                                
                                // Reset pointer and get max revenue
                                $revenue_query->data_seek(0);
                                while ($day = $revenue_query->fetch_assoc()) {
                                    $revenue_data[] = $day;
                                    if ($day['revenue'] > $max_revenue) {
                                        $max_revenue = $day['revenue'];
                                    }
                                }
                                
                                foreach ($revenue_data as $day): 
                        ?>
                            <tr>
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($day['booking_date'])); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $day['bookings']; ?> booking</span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?php echo number_format($day['revenue'], 0, ',', '.'); ?> VND</strong>
                                        </td>
                                        <td>
                                            <?php echo number_format($day['avg_price'], 0, ',', '.'); ?> VND
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $day['customers']; ?> khách</span>
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
            </div>
        </div>
        
        <?php
        }
        break;
        
    case 'room_usage':
        // Room Usage Report
        $room_usage_query = $conn->query("
            SELECT 
                r.room_number,
                r.type,
                r.floor,
                COUNT(b.id) as total_bookings,
                SUM(CASE WHEN b.status NOT IN ('cancelled') THEN 1 ELSE 0 END) as successful_bookings,
                SUM(CASE WHEN b.status NOT IN ('cancelled') THEN b.total_price ELSE 0 END) as revenue,
                AVG(CASE WHEN b.status NOT IN ('cancelled') THEN DATEDIFF(b.checkout, b.checkin) ELSE NULL END) as avg_stay
            FROM rooms r
            LEFT JOIN bookings b ON r.id = b.room_id
                AND b.checkin BETWEEN '$date_from' AND '$date_to'
            WHERE r.type = '$room_type' OR '$room_type' = 'all'
            GROUP BY r.id
            ORDER BY revenue DESC
        ");
        
        if ($room_usage_query && $room_usage_query->num_rows > 0) {
            ?>
            
            <!-- Room Usage Report -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title fs-5 mb-0">🏠 Báo cáo sử dụng phòng</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="roomUsageTable" class="table table-hover w-100">
                            <thead class="table-info">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Loại</th>
                            <th>Tầng</th>
                                    <th>Tổng booking</th>
                                    <th>Booking thành công</th>
                                    <th>Doanh thu</th>
                                    <th>Thời gian lưu trú TB</th>
                                    <th>Hiệu suất</th>
                        </tr>
                    </thead>
                    <tbody>
                                <?php while ($room = $room_usage_query->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $room['room_number']; ?></strong>
                                </td>
                                <td>
                                            <span class="badge bg-primary"><?php echo $room['type']; ?></span>
                                </td>
                                        <td>Tầng <?php echo $room['floor']; ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $room['total_bookings']; ?></span>
                                </td>
                                <td>
                                            <span class="badge bg-success"><?php echo $room['successful_bookings']; ?></span>
                                </td>
                                        <td>
                                            <strong class="text-success"><?php echo number_format($room['revenue'], 0, ',', '.'); ?> VND</strong>
                                </td>
                                        <td>
                                            <?php if ($room['avg_stay']): ?>
                                                <?php echo number_format($room['avg_stay'], 1); ?> ngày
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                            $success_rate = $room['total_bookings'] > 0 ? 
                                                ($room['successful_bookings'] / $room['total_bookings']) * 100 : 0;
                                            $performance_class = $success_rate > 80 ? 'bg-success' : ($success_rate > 50 ? 'bg-warning' : 'bg-danger');
                                            ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?php echo $performance_class; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $success_rate; ?>%"
                                                     aria-valuenow="<?php echo $success_rate; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo number_format($success_rate, 1); ?>%
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
        
        <?php
        }
        break;
        
    default:
        echo '<div class="alert alert-info">Chọn loại báo cáo để xem dữ liệu</div>';
        break;
}
?>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables based on report type
    if ($('#revenueReportTable').length) {
        $('#revenueReportTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 15,
            order: [[0, 'desc']], // Sort by date
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[10, 15, 25, -1], [10, 15, 25, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
    
    if ($('#roomUsageTable').length) {
        $('#roomUsageTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 20,
            order: [[5, 'desc']], // Sort by revenue
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[10, 20, 50, -1], [10, 20, 50, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
});
</script>
