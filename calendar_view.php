<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require login
requireLogin();

$page_title = "Calendar View - Hotel Management";

// Get month and year from URL parameters
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }
if ($year < 2020) $year = 2020;
if ($year > 2030) $year = 2030;

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-calendar-alt"></i> Calendar View - Booking Management</h2>
        <p class="text-muted">Xem lịch đặt phòng theo tháng và quản lý booking trực quan</p>
    </div>
</div>

<!-- Calendar Navigation -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="?month=<?php echo $month-1; ?>&year=<?php echo $year; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chevron-left"></i> Tháng trước
            </a>
            
            <h3 class="mb-0 text-primary">
            <?php 
            $monthNames = [
                1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
                5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
                9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'
            ];
            echo $monthNames[$month] . ' ' . $year;
            ?>
        </h3>
        
                        <a href="?month=<?php echo $month+1; ?>&year=<?php echo $year; ?>" class="btn btn-outline-secondary">
                Tháng sau <i class="fas fa-chevron-right"></i>
            </a>
    </div>
    
        <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-info">
                <i class="fas fa-calendar-day"></i> Hôm nay
            </a>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Danh sách phòng
            </a>
            <?php if (getUserRole() === 'admin' || getUserRole() === 'staff'): ?>
                <a href="manage_bookings.php" class="btn btn-warning">
                    <i class="fas fa-tools"></i> Quản lý Booking
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Get bookings for the month
$firstDay = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
$lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

$bookings_query = $conn->query("
    SELECT b.*, r.room_number, r.type, r.floor,
           g.name as guest_name, g.phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN guests g ON b.guest_id = g.id
    WHERE (b.checkin <= '$lastDay' AND b.checkout >= '$firstDay')
    AND b.status NOT IN ('cancelled')
    ORDER BY b.checkin ASC
");

// Organize bookings by date range
$calendar_data = [];
while ($booking = $bookings_query->fetch_assoc()) {
    $checkin = new DateTime($booking['checkin']);
    $checkout = new DateTime($booking['checkout']);
    
    // Add booking for each date in the range
    $current = clone $checkin;
    while ($current <= $checkout) {
        $date_key = $current->format('Y-m-d');
        if (!isset($calendar_data[$date_key])) {
            $calendar_data[$date_key] = [];
        }
        
        // Mark the type of day
        $day_type = 'occupied';
        if ($current->format('Y-m-d') == $booking['checkin']) {
            $day_type = 'checkin';
        } elseif ($current->format('Y-m-d') == $booking['checkout']) {
            $day_type = 'checkout';
        }
        
        $calendar_data[$date_key][] = [
            'id' => $booking['id'],
            'room_number' => $booking['room_number'],
            'type' => $booking['type'],
            'floor' => $booking['floor'],
            'guest_name' => $booking['guest_name'],
            'phone' => $booking['phone'],
            'status' => $booking['status'],
            'day_type' => $day_type
        ];
        
        $current->add(new DateInterval('P1D'));
    }
}

// Get first day of month and number of days
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth); // 0 = Sunday, 1 = Monday, etc.

// Calendar header
$weekDays = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
?>

<!-- Calendar Grid -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-calendar"></i> Lịch tháng <?php echo $monthNames[$month]; ?> năm <?php echo $year; ?></h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="min-height: 600px;">
                <thead class="table-primary">
                    <tr>
                        <?php foreach ($weekDays as $day): ?>
                            <th class="text-center py-3" style="width: 14.28%;">
                                <strong><?php echo $day; ?></strong>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dayCount = 1;
                    $currentDayOfWeek = $firstDayOfWeek;
                    
                    // Add empty cells for days before the first day of the month
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        if ($i == 0) echo '<tr>';
                        echo '<td class="bg-light"></td>';
                    }
                    
                    // Add days of the month
                    while ($dayCount <= $daysInMonth) {
                        if ($currentDayOfWeek == 0) {
                            echo '<tr>';
                        }
                        
                        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $dayCount);
                        $dateKey = $currentDate;
                        $isToday = ($currentDate == date('Y-m-d'));
                        $hasBookings = isset($calendar_data[$dateKey]) && !empty($calendar_data[$dateKey]);
                        
                        $cellClass = 'position-relative';
                        if ($isToday) {
                            $cellClass .= ' bg-warning bg-opacity-25';
                        }
                        
                        echo '<td class="' . $cellClass . '" style="height: 120px; vertical-align: top;">';
                        echo '<div class="d-flex justify-content-between align-items-start p-2">';
                        echo '<span class="badge ' . ($isToday ? 'bg-warning text-dark' : 'bg-secondary') . '">' . $dayCount . '</span>';
                        
                        if ($hasBookings) {
                            $bookingCount = count($calendar_data[$dateKey]);
                            echo '<span class="badge bg-info">' . $bookingCount . ' booking</span>';
                        }
                        echo '</div>';
                        
                        if ($hasBookings) {
                            echo '<div class="p-2">';
                            foreach ($calendar_data[$dateKey] as $booking) {
                                $statusClass = '';
                                $statusIcon = '';
                                
                                switch ($booking['day_type']) {
                                    case 'checkin':
                                        $statusClass = 'bg-success';
                                        $statusIcon = '<i class="fas fa-door-open"></i>';
                                        break;
                                    case 'checkout':
                                        $statusClass = 'bg-warning';
                                        $statusIcon = '<i class="fas fa-door-closed"></i>';
                                        break;
                                    default:
                                        $statusClass = 'bg-primary';
                                        $statusIcon = '<i class="fas fa-bed"></i>';
                                }
                                
                                echo '<div class="card mb-1 border-0 ' . $statusClass . ' text-white" style="font-size: 0.8rem;">';
                                echo '<div class="card-body p-2">';
                                echo '<div class="d-flex justify-content-between align-items-start">';
                                echo '<div>';
                                echo '<strong>' . $statusIcon . ' Phòng ' . $booking['room_number'] . '</strong><br>';
                                echo '<small>' . $booking['guest_name'] . '</small><br>';
                                echo '<small>' . $booking['type'] . ' - Tầng ' . $booking['floor'] . '</small>';
                                echo '</div>';
                                echo '<span class="badge bg-light text-dark">' . $booking['status'] . '</span>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        
                        echo '</td>';
                        
                        $currentDayOfWeek++;
                        if ($currentDayOfWeek == 7) {
                            echo '</tr>';
                            $currentDayOfWeek = 0;
                        }
                        
                        $dayCount++;
                    }
                    
                    // Add empty cells for the last week if needed
                    while ($currentDayOfWeek < 7) {
                        echo '<td class="bg-light"></td>';
                        $currentDayOfWeek++;
                    }
                    if ($currentDayOfWeek > 0) {
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-info-circle"></i> Chú thích</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="bg-success rounded me-2" style="width: 20px; height: 20px;"></div>
                                         <span><i class="fas fa-door-open"></i> Check-in</span>
                 </div>
             </div>
             <div class="col-md-3">
                 <div class="d-flex align-items-center">
                     <div class="bg-warning rounded me-2" style="width: 20px; height: 20px;"></div>
                     <span><i class="fas fa-door-closed"></i> Check-out</span>
                 </div>
             </div>
             <div class="col-md-3">
                 <div class="d-flex align-items-center">
                     <div class="bg-primary rounded me-2" style="width: 20px; height: 20px;"></div>
                     <span><i class="fas fa-bed"></i> Đang thuê</span>
         </div>
         </div>
             <div class="col-md-3">
                 <div class="d-flex align-items-center">
                     <div class="bg-warning bg-opacity-25 rounded me-2" style="width: 20px; height: 20px;"></div>
                     <span><i class="fas fa-calendar-day"></i> Hôm nay</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-chart-bar"></i> Thống kê tháng</h3>
    </div>
    <div class="card-body">
    <?php
        $totalBookings = 0;
        $totalCheckins = 0;
        $totalCheckouts = 0;
        $totalOccupied = 0;
        
        foreach ($calendar_data as $dateBookings) {
            foreach ($dateBookings as $booking) {
                $totalBookings++;
                switch ($booking['day_type']) {
                    case 'checkin':
                        $totalCheckins++;
                        break;
                    case 'checkout':
                        $totalCheckouts++;
                        break;
                    default:
                        $totalOccupied++;
                }
            }
        }
        ?>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-primary text-white text-center h-100 shadow">
                    <div class="card-body">
                        <div class="display-6 fw-bold mb-2"><?php echo $totalBookings; ?></div>
                                                 <div class="fs-6"><i class="fas fa-list"></i> Tổng booking</div>
                     </div>
                 </div>
             </div>
             
             <div class="col-lg-3 col-md-6 col-sm-12">
                 <div class="card bg-success text-white text-center h-100 shadow">
                     <div class="card-body">
                         <div class="display-6 fw-bold mb-2"><?php echo $totalCheckins; ?></div>
                         <div class="fs-6"><i class="fas fa-door-open"></i> Check-in</div>
                     </div>
                 </div>
             </div>
             
             <div class="col-lg-3 col-md-6 col-sm-12">
                 <div class="card bg-warning text-dark text-center h-100 shadow">
                     <div class="card-body">
                         <div class="display-6 fw-bold mb-2"><?php echo $totalCheckouts; ?></div>
                         <div class="fs-6"><i class="fas fa-door-closed"></i> Check-out</div>
                     </div>
                 </div>
             </div>
             
             <div class="col-lg-3 col-md-6 col-sm-12">
                 <div class="card bg-info text-white text-center h-100 shadow">
                     <div class="card-body">
                         <div class="display-6 fw-bold mb-2"><?php echo $totalOccupied; ?></div>
                         <div class="fs-6"><i class="fas fa-bed"></i> Đang thuê</div>
                    </div>
                </div>
                </div>
            </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card shadow mb-4">
    <div class="card-header bg-secondary text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-bolt"></i> Thao tác nhanh</h3>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="book.php" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Đặt phòng mới
            </a>
            <a href="checkin_checkout.php" class="btn btn-primary">
                <i class="fas fa-door-open"></i> Check-in/Check-out
            </a>
            <a href="reports.php" class="btn btn-info">
                <i class="fas fa-chart-line"></i> Báo cáo
            </a>
            <a href="advanced_reports.php" class="btn btn-warning">
                <i class="fas fa-chart-bar"></i> Báo cáo nâng cao
            </a>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Add hover effects to calendar cells
    $('td').hover(
        function() {
            if (!$(this).hasClass('bg-light')) {
                $(this).addClass('shadow-sm');
            }
        },
        function() {
            $(this).removeClass('shadow-sm');
        }
    );
    
    // Add click event to show booking details (optional)
    $('td').click(function() {
        if ($(this).find('.card').length > 0) {
            // You can implement a modal to show detailed booking information
            console.log('Booking details clicked');
        }
    });
});
</script>
