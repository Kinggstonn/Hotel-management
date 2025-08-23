<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Allow guest access to view rooms (they can see but not book without login)
$page_title = "Danh sách phòng - Hotel Management";
include "includes/header.php"; 
?>

<!-- Statistics Cards -->
<?php
// Lấy thống kê
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_rooms,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_rooms,
        SUM(CASE WHEN type = 'VIP' THEN 1 ELSE 0 END) as vip_rooms,
        SUM(CASE WHEN type = 'Thường' THEN 1 ELSE 0 END) as regular_rooms
    FROM rooms
")->fetch_assoc();

// Removed occupancy rate calculation
?>

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

<!-- Room List Card -->
<div class="card shadow">
    <div class="card-header bg-dark text-white">
                    <h2 class="card-title mb-0 fs-4"><i class="fas fa-list-ul"></i> Danh sách phòng chi tiết</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="roomsTable" class="table table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th><i class="fas fa-door-open"></i> Số phòng</th>
                        <th><i class="fas fa-building"></i> Tầng</th>
                        <th><i class="fas fa-star"></i> Loại phòng</th>
                        <th><i class="fas fa-dollar-sign"></i> Giá/đêm</th>
                        <th><i class="fas fa-info-circle"></i> Tình trạng</th>
                        <th><i class="fas fa-cog"></i> Hành động</th>
                    </tr>
                </thead>
                <tbody>
<?php
$result = $conn->query("SELECT * FROM rooms ORDER BY floor, room_number");

if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        // Định dạng giá tiền
        $price = number_format($r['price'], 0, ',', '.') . ' VND';
        
        // Class CSS cho loại phòng
        $room_class = ($r['type'] == 'VIP') ? 'table-warning' : 'table-light';
        
        // Class CSS cho trạng thái
        $status_text = ($r['status'] == 'available') ? 'Trống' : 'Đã đặt';
        $status_class = 'status-' . $r['status'];
        
        // Icon cho loại phòng
        $type_icon = ($r['type'] == 'VIP') ? '<i class="fas fa-crown text-warning"></i> VIP' : '<i class="fas fa-home text-primary"></i> Thường';
        
        echo "<tr class='{$room_class}'>
            <td><strong>{$r['room_number']}</strong></td>
            <td>{$r['floor']}</td>
            <td>{$type_icon}</td>
            <td>{$price}</td>
            <td><span class='badge {$status_class}'>{$status_text}</span></td>
            <td>";
        
        if ($r['status'] == 'available') {
            if (isLoggedIn() && hasPermission('create_booking')) {
                echo "<a href='book.php?room_id={$r['id']}' class='btn btn-success btn-sm'>
                                            <i class='fas fa-calendar-plus'></i> Đặt phòng
                </a>";
            } else {
                echo "<a href='login.php' class='btn btn-warning btn-sm'>
                                            <i class='fas fa-lock'></i> Đăng nhập để đặt
                </a>";
            }
        } else {
            echo "<span class='text-muted'><i class='fas fa-times-circle'></i> Không khả dụng</span>";
        }
        
        echo "</td></tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center text-muted py-4'>Không có dữ liệu phòng</td></tr>";
}
?>
                </tbody>
            </table>
        </div>
        
        <!-- Floor Summary Cards -->
        <div class="row g-3 mt-4">
            <?php
            // Lấy thống kê theo tầng
            $floor_stats = $conn->query("
                SELECT 
                    floor,
                    COUNT(*) as total_rooms,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_rooms
                FROM rooms 
                GROUP BY floor 
                ORDER BY floor
            ");
            
            if ($floor_stats && $floor_stats->num_rows > 0) {
                while ($floor = $floor_stats->fetch_assoc()):
            ?>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card border-primary h-100">
                    <div class="card-header bg-primary text-white text-center">
                        <h6 class="mb-0"><i class="fas fa-building"></i> TẦNG <?php echo $floor['floor']; ?></h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="bg-success bg-opacity-10 rounded p-2">
                                    <small class="text-success fw-bold">Trống</small><br>
                                    <span class="fs-5 fw-bold text-success"><?php echo $floor['available_rooms']; ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-danger bg-opacity-10 rounded p-2">
                                    <small class="text-danger fw-bold">Đã đặt</small><br>
                                    <span class="fs-5 fw-bold text-danger"><?php echo $floor['booked_rooms']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Tổng: <?php echo $floor['total_rooms']; ?> phòng</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            } else {
                echo '<div class="col-12"><div class="alert alert-warning">Không có dữ liệu phòng để hiển thị</div></div>';
            }
            ?>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Đợi một chút để đảm bảo DOM đã sẵn sàng
    setTimeout(function() {
        // Destroy existing DataTable if exists
        if ($.fn.DataTable.isDataTable('#roomsTable')) {
            $('#roomsTable').DataTable().destroy();
        }
        
        // Kiểm tra xem có dữ liệu không
        var hasData = $('#roomsTable tbody tr').length > 0 && 
                     !$('#roomsTable tbody tr td').text().includes('Không có dữ liệu');
        
        if (hasData) {
            // Reinitialize DataTable
            $('#roomsTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                pageLength: 25,
                order: [[1, 'asc'], [0, 'asc']], // Sắp xếp theo tầng, sau đó theo số phòng
                columnDefs: [
                    {
                        targets: [5], // Cột hành động
                        orderable: false,
                        searchable: false,
                        width: '120px'
                    },
                    {
                        targets: [0], // Cột số phòng
                        width: '100px'
                    },
                    {
                        targets: [1], // Cột tầng
                        width: '80px'
                    },
                    {
                        targets: [2], // Cột loại phòng
                        width: '120px'
                    },
                    {
                        targets: [3], // Cột giá
                        width: '150px'
                    },
                    {
                        targets: [4], // Cột trạng thái
                        width: '100px'
                    }
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
                autoWidth: false,
                scrollX: true,
                initComplete: function() {
                    // Thêm class Bootstrap cho các elements của DataTables
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    
                    // Recalculate columns after initialization
                    this.api().columns.adjust().responsive.recalc();
                },
                drawCallback: function() {
                    // Ensure proper column widths after drawing
                    this.api().columns.adjust();
                }
            });
        } else {
            // Nếu không có dữ liệu, hiển thị thông báo
            $('#roomsTable tbody').html('<tr><td colspan="6" class="text-center text-muted py-4">Không có dữ liệu phòng để hiển thị</td></tr>');
        }
    }, 100);
});
</script>
