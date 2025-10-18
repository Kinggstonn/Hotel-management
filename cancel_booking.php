<?php
include "db.php";
include "auth/auth_functions.php";

// Require login
requireLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    header('Location: dashboard.php');
    exit();
}

// Get booking details
$booking_query = $conn->query("
    SELECT b.*, r.room_number, r.type as room_type, r.floor,
           g.name as guest_name, g.phone as guest_phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN guests g ON b.guest_id = g.id
    WHERE b.id = $booking_id
");

if ($booking_query->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$booking = $booking_query->fetch_assoc();

// Check if booking can be cancelled (only pending_payment status)
if ($booking['status'] !== 'pending_payment') {
    $_SESSION['error'] = 'Không thể hủy booking này! Booking đã được xác nhận hoặc đã hủy.';
    header('Location: dashboard.php');
    exit();
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_cancel'])) {
    try {
        $conn->begin_transaction();
        
        // Update booking status to cancelled
        $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
        
        // Room remains available (was never marked as booked)
        // No need to update room status since it was never changed
        
        $conn->commit();
        
        $_SESSION['success'] = 'Đã hủy đặt phòng thành công! Phòng đã được giải phóng.';
        header('Location: dashboard.php');
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Lỗi khi hủy booking: ' . $e->getMessage();
    }
}

$page_title = "Hủy đặt phòng - Hotel Management";
include "includes/header.php";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title fs-4 mb-0"><i class="fas fa-times-circle"></i> Hủy đặt phòng</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Xác nhận hủy đặt phòng</h5>
                        <p class="mb-0">Bạn có chắc chắn muốn hủy đặt phòng này không?</p>
                    </div>

                    <!-- Booking Details -->
                    <div class="card bg-light mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin đặt phòng</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Booking ID:</strong></td>
                                            <td>#<?php echo $booking['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Khách hàng:</strong></td>
                                            <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số điện thoại:</strong></td>
                                            <td><?php echo $booking['guest_phone']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Phòng:</strong></td>
                                            <td><?php echo $booking['room_number']; ?> (<?php echo $booking['room_type']; ?>)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Check-in:</strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Check-out:</strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($booking['checkout'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Tổng tiền:</strong></td>
                                            <td><span class="text-primary fs-5"><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Trạng thái:</strong></td>
                                            <td><span class="badge bg-warning">⏳ Chờ thanh toán</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Lưu ý:</h6>
                        <ul class="mb-0">
                            <li>Việc hủy đặt phòng không thể hoàn tác</li>
                            <li>Phòng sẽ được giải phóng và có thể được đặt bởi khách khác</li>
                            <li>Bạn sẽ cần đặt phòng lại nếu muốn sử dụng dịch vụ</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <form method="POST">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="payment_form.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                <i class="fas fa-credit-card"></i> Quay lại thanh toán
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Về trang chủ
                            </a>
                            <button type="submit" name="confirm_cancel" class="btn btn-danger">
                                <i class="fas fa-times"></i> Xác nhận hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
