<?php
include "db.php";
include "auth/auth_functions.php";

// Require login
requireLogin();

$page_title = "Thanh toán - Hotel Management";
$success_message = "";
$error_message = "";

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    header('Location: dashboard.php');
    exit();
}

// Get booking details
$booking_query = $conn->query("
    SELECT b.*, r.room_number, r.type as room_type, r.floor, r.price,
           g.name as guest_name, g.phone as guest_phone, g.email as guest_email
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

// Calculate total amount
$checkin = new DateTime($booking['checkin']);
$checkout = new DateTime($booking['checkout']);
$nights = $checkin->diff($checkout)->days;
$total_amount = $booking['price'] * $nights;

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $card_number = trim($_POST['card_number']);
    $card_name = trim($_POST['card_name']);
    $card_cvv = trim($_POST['card_cvv']);
    $card_expiry = trim($_POST['card_expiry']);
    
    // Validation
    if (empty($card_number) || empty($card_name) || empty($card_cvv) || empty($card_expiry)) {
        $error_message = "❌ Vui lòng nhập đầy đủ thông tin thanh toán";
    } else {
        // Validate card number (Luhn algorithm)
        function validateCardNumber($cardNumber) {
            $cardNumber = preg_replace('/\D/', '', $cardNumber);
            $length = strlen($cardNumber);
            $sum = 0;
            $alternate = false;
            
            for ($i = $length - 1; $i >= 0; $i--) {
                $digit = intval($cardNumber[$i]);
                
                if ($alternate) {
                    $digit *= 2;
                    if ($digit > 9) {
                        $digit = ($digit % 10) + 1;
                    }
                }
                
                $sum += $digit;
                $alternate = !$alternate;
            }
            
            return ($sum % 10) === 0;
        }
        
        // Check for valid test card
        if ($card_number === '4111111111111111') {
            // Valid test card - process payment
            try {
                $conn->begin_transaction();
                
                // Insert payment record
                $insert_payment = $conn->prepare("
                    INSERT INTO payments (booking_id, amount, payment_method, payment_status, 
                                        transaction_id, card_number, card_name, card_expiry, processed_by) 
                    VALUES (?, ?, 'card', 'completed', ?, ?, ?, ?, ?)
                ");
                
                $transaction_id = 'TXN_' . date('Ymd') . '_' . $booking_id . '_' . time();
                $user_id = $_SESSION['user_id'];
                
                // Mask card number for security
                $masked_card = substr($card_number, 0, 4) . '****' . substr($card_number, -4);
                
                $insert_payment->bind_param("idssssi", $booking_id, $total_amount, $transaction_id, 
                                           $masked_card, $card_name, $card_expiry, $user_id);
                $insert_payment->execute();
                
                // Update booking payment status and room status
                $conn->query("UPDATE bookings SET payment_status = 'paid', status = 'booked' WHERE id = $booking_id");
                
                // Update room status to booked (chỉ khi thanh toán thành công)
                $conn->query("UPDATE rooms SET status = 'booked' WHERE id = " . $booking['room_id']);
                
                $conn->commit();
                
                // Redirect to invoice page
                header("Location: invoice.php?booking_id=$booking_id&transaction_id=$transaction_id");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "❌ Lỗi xử lý thanh toán: " . $e->getMessage();
            }
        } else {
            // Invalid card
            $error_message = "❌ Thẻ không hợp lệ";
        }
    }
}

include "includes/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Payment Form -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title fs-4 mb-0"><i class="fas fa-credit-card"></i> Thông tin thanh toán</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <div class="alert alert-warning mb-4">
                            <h6><i class="fas fa-info-circle"></i> Thanh toán thất bại!</h6>
                            <p class="mb-2">Bạn có thể:</p>
                            <div class="d-flex gap-2">
                                <a href="book.php?room_id=<?php echo $booking['room_id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-arrow-left"></i> Quay lại đặt phòng
                                </a>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="location.reload()">
                                    <i class="fas fa-redo"></i> Thử lại
                                </button>
                                <a href="cancel_booking.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Hủy đặt phòng
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <form method="POST" id="paymentForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Số thẻ *</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" 
                                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="card_name" class="form-label">Tên chủ thẻ *</label>
                                    <input type="text" class="form-control" id="card_name" name="card_name" 
                                           placeholder="Nguyen Van A" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="card_expiry" class="form-label">Ngày hết hạn *</label>
                                    <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                                           placeholder="MM/YY" maxlength="5" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="card_cvv" class="form-label">CVV *</label>
                                    <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                                           placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Phương thức</label>
                                    <input type="text" class="form-control" value="💳 Thẻ tín dụng/ghi nợ" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="process_payment" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Xác nhận thanh toán
                            </button>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <a href="book.php?room_id=<?php echo $booking['room_id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Quay lại đặt phòng
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Booking Summary -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title fs-5 mb-0"><i class="fas fa-receipt"></i> Tóm tắt đặt phòng</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6"><strong>Booking ID:</strong></div>
                        <div class="col-6">#<?php echo $booking['id']; ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Khách hàng:</strong></div>
                        <div class="col-6"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Phòng:</strong></div>
                        <div class="col-6">
                            <?php echo $booking['room_number']; ?><br>
                            <small class="text-muted">Tầng <?php echo $booking['floor']; ?> - <?php echo $booking['room_type']; ?></small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Check-in:</strong></div>
                        <div class="col-6"><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Check-out:</strong></div>
                        <div class="col-6"><?php echo date('d/m/Y', strtotime($booking['checkout'])); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Số đêm:</strong></div>
                        <div class="col-6"><?php echo $nights; ?> đêm</div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mb-2">
                        <div class="col-6"><strong>Giá/đêm:</strong></div>
                        <div class="col-6"><?php echo number_format($booking['price'], 0, ',', '.'); ?> VND</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Tổng tiền:</strong></div>
                        <div class="col-6">
                            <strong class="text-primary fs-5">
                                <?php echo number_format($total_amount, 0, ',', '.'); ?> VND
                            </strong>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" onclick="calculateTotal()">
                            <i class="fas fa-calculator"></i> Tính tiền lại
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Format card number input
    $('#card_number').on('input', function() {
        let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        this.value = formattedValue;
    });
    
    // Format expiry date input
    $('#card_expiry').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        this.value = value;
    });
    
    // Format CVV input
    $('#card_cvv').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // Auto-fill test data on page load
    $('#card_number').val('4111111111111111');
    $('#card_name').val('Nguyen Van A');
    $('#card_cvv').val('123');
    $('#card_expiry').val('12/25');
    
    // Auto-fill test data when focus (backup)
    $('#card_number').on('focus', function() {
        if (this.value === '') {
            this.value = '4111111111111111';
            $('#card_name').val('Nguyen Van A');
            $('#card_cvv').val('123');
            $('#card_expiry').val('12/25');
        }
    });
});

function calculateTotal() {
    // This function can be expanded to recalculate based on any changes
    alert('Tổng tiền: <?php echo number_format($total_amount, 0, ',', '.'); ?> VND\nSố đêm: <?php echo $nights; ?> đêm\nGiá/đêm: <?php echo number_format($booking['price'], 0, ',', '.'); ?> VND');
}
</script>
