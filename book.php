
<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require login for booking
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Bạn cần đăng nhập để đặt phòng!';
    header('Location: login.php');
    exit();
}

// Check permission for booking
requirePermission('create_booking');

// Xử lý logic trước khi include header
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = $conn->real_escape_string($_POST['name']);
    $phone    = $conn->real_escape_string($_POST['phone']);
    $id_card  = $conn->real_escape_string($_POST['id_card']);
    $checkin  = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $room_id  = (int)$_POST['room_id'];

    // Lấy thông tin phòng để tính giá
    $room_result = $conn->query("SELECT * FROM rooms WHERE id = $room_id");
    $room = $room_result->fetch_assoc();
    
    // Kiểm tra xem phòng có tồn tại không
    if (!$room) {
        $_SESSION['error'] = 'Phòng không tồn tại!';
        header('Location: index.php');
        exit();
    }
    
    // Tính số đêm
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $nights = $checkin_date->diff($checkout_date)->days;
    
    if ($nights <= 0) {
        $_SESSION['error'] = 'Ngày trả phòng phải lớn hơn ngày nhận phòng!';
        header('Location: book.php?room_id=' . $room_id);
        exit();
    }
    
    $total_price = round($room['price'] * $nights, 2);

    // Lấy user_id hiện tại
    $current_user_id = getUserInfo()['id'];
    
    // thêm khách (liên kết với user)
    $conn->query("INSERT INTO guests (name, phone, id_card, user_id) VALUES ('$name','$phone','$id_card', $current_user_id)");
    $guest_id = $conn->insert_id;

    // thêm booking với tổng giá (trạng thái pending - chờ thanh toán)
    $conn->query("INSERT INTO bookings (guest_id, room_id, checkin, checkout, total_price, status) 
                  VALUES ($guest_id, $room_id, '$checkin', '$checkout', $total_price, 'pending_payment')");
    $booking_id = $conn->insert_id;

    // KHÔNG cập nhật trạng thái phòng - phòng vẫn available cho đến khi thanh toán thành công
    
    // Redirect đến trang thanh toán sau khi đặt phòng thành công
    $_SESSION['success'] = 'Đặt phòng thành công! Vui lòng thanh toán để xác nhận đặt phòng.';
    header('Location: payment_form.php?booking_id=' . $booking_id);
    exit();
}

// Xử lý GET request
$room_id = (int)($_GET['room_id'] ?? 0);

// Nếu không có room_id, chuyển về trang chủ để chọn phòng
if ($room_id <= 0) {
    $_SESSION['error'] = 'Vui lòng chọn phòng để đặt!';
    header('Location: index.php');
    exit();
}

// Lấy thông tin phòng
$room_result = $conn->query("SELECT * FROM rooms WHERE id = $room_id");
$room = $room_result->fetch_assoc();

// Kiểm tra xem phòng có tồn tại không
if (!$room) {
    $_SESSION['error'] = 'Phòng không tồn tại!';
    header('Location: index.php');
    exit();
}

// Kiểm tra xem phòng có sẵn sàng để đặt không
if ($room['status'] !== 'available') {
    $_SESSION['error'] = 'Phòng này không khả dụng để đặt!';
    header('Location: index.php');
    exit();
}

$page_title = "Đặt phòng - Hotel Management";
include "includes/header.php"; 
?>

<?php
// Hiển thị thông báo lỗi nếu có
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>' . $_SESSION['error'] . '
          </div>';
    unset($_SESSION['error']);
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="fas fa-calendar-plus"></i> Đặt phòng</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Thông tin phòng</h5>
                        <p class="mb-2"><strong>Phòng:</strong> <?php echo $room['room_number']; ?> - <?php echo $room['type']; ?></p>
                        <p class="mb-2"><strong>Giá:</strong> <span class="text-success fw-bold"><?php echo number_format($room['price'], 0, ',', '.'); ?> VND</span> / đêm</p>
                        <p class="mb-0"><strong>Mô tả:</strong> <?php echo htmlspecialchars($room['description'] ?? 'Không có mô tả'); ?></p>
                    </div>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user"></i> Họ tên *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">
                                    Vui lòng nhập họ tên
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Số điện thoại *
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">
                                    Vui lòng nhập số điện thoại
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="id_card" class="form-label">
                                    <i class="fas fa-id-card"></i> CMND/CCCD *
                                </label>
                                <input type="text" class="form-control" id="id_card" name="id_card" required>
                                <div class="invalid-feedback">
                                    Vui lòng nhập CMND/CCCD
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="checkin" class="form-label">
                                    <i class="fas fa-calendar-check"></i> Ngày nhận phòng *
                                </label>
                                <input type="date" class="form-control" id="checkin" name="checkin" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                                <div class="invalid-feedback" id="checkinError" style="display: none;">
                                    Vui lòng chọn ngày nhận phòng hợp lệ
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="checkout" class="form-label">
                                    <i class="fas fa-calendar-times"></i> Ngày trả phòng *
                                </label>
                                <input type="date" class="form-control" id="checkout" name="checkout" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                <div class="invalid-feedback" id="checkoutError" style="display: none;">
                                    Vui lòng chọn ngày trả phòng hợp lệ
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert alert-info" id="totalPrice" style="display:none;">
                                    <strong><i class="fas fa-calculator"></i> Tổng tiền: <span id="totalAmount" class="text-danger">0</span> VND</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Xác nhận đặt phòng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const checkinInput = document.getElementById('checkin');
const checkoutInput = document.getElementById('checkout');
const totalPriceDiv = document.getElementById('totalPrice');
const totalAmountSpan = document.getElementById('totalAmount');
const roomPrice = <?php echo isset($room) && $room ? $room['price'] : 0; ?>;

function calculateTotal() {
    const checkin = new Date(checkinInput.value);
    const checkout = new Date(checkoutInput.value);
    
    // Clear previous error messages
    clearDateErrors();
    
    if (checkin && checkout) {
        if (checkout <= checkin) {
            showDateError('checkout', 'Vui lòng chọn ngày trả phòng hợp lệ (sau ngày nhận phòng)');
            totalPriceDiv.style.display = 'none';
            return;
        }
        
        const nights = (checkout - checkin) / (1000 * 60 * 60 * 24);
        const total = roomPrice * nights;
        totalAmountSpan.textContent = total.toLocaleString('vi-VN');
        totalPriceDiv.style.display = 'block';
        totalPriceDiv.innerHTML = `<strong><i class="fas fa-calculator"></i> Số đêm: ${nights} | Tổng tiền: <span class="text-danger">${total.toLocaleString('vi-VN')}</span> VND</strong>`;
    } else {
        totalPriceDiv.style.display = 'none';
    }
}

function showDateError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + 'Error');
    
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

function clearDateErrors() {
    const fields = ['checkin', 'checkout'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + 'Error');
        
        field.classList.remove('is-invalid');
        field.classList.remove('is-valid');
        
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    });
}

function validateDate(fieldId) {
    const field = document.getElementById(fieldId);
    const value = field.value;
    
    if (!value) {
        showDateError(fieldId, 'Vui lòng chọn ngày');
        return false;
    }
    
    const selectedDate = new Date(value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (fieldId === 'checkin') {
        if (selectedDate < today) {
            showDateError(fieldId, 'Ngày nhận phòng không được là ngày trong quá khứ');
            return false;
        }
    }
    
    if (fieldId === 'checkout') {
        const checkinDate = new Date(document.getElementById('checkin').value);
        if (selectedDate <= checkinDate) {
            showDateError(fieldId, 'Ngày trả phòng phải sau ngày nhận phòng');
            return false;
        }
    }
    
    // Valid
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    const errorDiv = document.getElementById(fieldId + 'Error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    
    return true;
}

checkinInput.addEventListener('change', function() {
    // Cập nhật ngày trả phòng tối thiểu
    const checkin = new Date(this.value);
    checkin.setDate(checkin.getDate() + 1);
    checkoutInput.min = checkin.toISOString().split('T')[0];
    
    // Validate checkin date
    validateDate('checkin');
    
    // Clear checkout if it's now invalid
    if (checkoutInput.value && new Date(checkoutInput.value) <= checkin) {
        checkoutInput.value = '';
        clearDateErrors();
    }
    
    calculateTotal();
});

checkoutInput.addEventListener('change', function() {
    validateDate('checkout');
    calculateTotal();
});

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                // Validate date fields first
                const checkinValid = validateDate('checkin');
                const checkoutValid = validateDate('checkout');
                
                if (form.checkValidity() === false || !checkinValid || !checkoutValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include "includes/footer.php"; ?>
