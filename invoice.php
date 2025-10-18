<?php
include "db.php";
include "auth/auth_functions.php";

// Require login
requireLogin();

$page_title = "Hóa đơn - Hotel Management";
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';

if (!$booking_id || !$transaction_id) {
    header('Location: dashboard.php');
    exit();
}

// Get booking and payment details
$booking_query = $conn->query("
    SELECT b.*, r.room_number, r.type as room_type, r.floor, r.price,
           g.name as guest_name, g.phone as guest_phone, g.email as guest_email,
           p.transaction_id, p.amount as payment_amount, p.card_number, p.card_name, 
           p.created_at as payment_date, u.username as processed_by
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN guests g ON b.guest_id = g.id
    JOIN payments p ON b.id = p.booking_id
    LEFT JOIN users u ON p.processed_by = u.id
    WHERE b.id = $booking_id AND p.transaction_id = '$transaction_id'
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

include "includes/header.php";
?>

<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <div class="row justify-content-center mb-3">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="text-decoration-none d-flex align-items-center">
                            <i class="fas fa-home me-1"></i> 
                            <span>Trang chủ</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item active d-flex align-items-center" aria-current="page">
                        <i class="fas fa-file-invoice me-1"></i> 
                        <span>Hóa đơn</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Invoice Card -->
            <div class="card shadow mb-4" id="invoiceCard">
                <div class="card-header bg-success text-white text-center">
                    <h2 class="card-title fs-3 mb-0"><i class="fas fa-check-circle"></i> Thanh toán thành công!</h2>
                </div>
                <div class="card-body">
                    <!-- Hotel Header -->
                    <div class="text-center mb-4">
                        <h1 class="display-6 text-primary fw-bold">🏨 HOTEL MANAGEMENT</h1>
                        <p class="text-muted">Hệ thống quản lý khách sạn</p>
                        <hr>
                    </div>
                    
                    <!-- Invoice Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary"><i class="fas fa-file-invoice"></i> Thông tin hóa đơn</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Mã hóa đơn:</strong></td>
                                    <td><code><?php echo $transaction_id; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Booking ID:</strong></td>
                                    <td>#<?php echo $booking['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày thanh toán:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['payment_date'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phương thức:</strong></td>
                                    <td>💳 Thẻ tín dụng/ghi nợ</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary"><i class="fas fa-user"></i> Thông tin khách hàng</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Tên:</strong></td>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Số điện thoại:</strong></td>
                                    <td><?php echo $booking['guest_phone']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo $booking['guest_email'] ?: 'Không có'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Xử lý bởi:</strong></td>
                                    <td><?php echo $booking['processed_by'] ?: 'Hệ thống'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Booking Details -->
                    <div class="mb-4">
                        <h5 class="text-primary"><i class="fas fa-bed"></i> Chi tiết đặt phòng</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Phòng</th>
                                        <th>Loại</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Số đêm</th>
                                        <th>Giá/đêm</th>
                                        <th>Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo $booking['room_number']; ?></strong><br>
                                            <small class="text-muted">Tầng <?php echo $booking['floor']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $booking['room_type'] === 'VIP' ? '👑 VIP' : '🏠 Thường'; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($booking['checkout'])); ?></td>
                                        <td class="text-center"><?php echo $nights; ?></td>
                                        <td class="text-end"><?php echo number_format($booking['price'], 0, ',', '.'); ?> VND</td>
                                        <td class="text-end"><strong><?php echo number_format($total_amount, 0, ',', '.'); ?> VND</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Payment Summary -->
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary"><i class="fas fa-credit-card"></i> Thông tin thanh toán</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Thẻ:</strong></td>
                                    <td><?php echo $booking['card_number']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Chủ thẻ:</strong></td>
                                    <td><?php echo htmlspecialchars($booking['card_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td><span class="badge bg-success">✅ Hoàn thành</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Tổng thanh toán</h6>
                                    <h3 class="text-success fw-bold">
                                        <?php echo number_format($booking['payment_amount'], 0, ',', '.'); ?> VND
                                    </h3>
                                    <small class="text-muted">Đã bao gồm thuế</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <button type="button" class="btn btn-primary btn-lg" onclick="printInvoice()">
                            <i class="fas fa-print"></i> In hóa đơn
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="downloadPDF()">
                            <i class="fas fa-download"></i> Tải PDF
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-lg" style="background-color: #6c757d; border-color: #6c757d;">
                            <i class="fas fa-home"></i> Về trang chủ
                        </a>
                        <a href="my_bookings.php" class="btn btn-info btn-lg">
                            <i class="fas fa-list"></i> Xem booking
                        </a>
                        <a href="book.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus"></i> Đặt phòng mới
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
function printInvoice() {
    // Hide action buttons for printing
    const actionCard = document.querySelector('.card:last-child');
    actionCard.style.display = 'none';
    
    // Print the invoice
    window.print();
    
    // Show action buttons again
    setTimeout(() => {
        actionCard.style.display = 'block';
    }, 1000);
}

function downloadPDF() {
    // Simple PDF generation using browser's print to PDF
    alert('Chức năng tải PDF sẽ được cập nhật trong phiên bản tiếp theo.\nHiện tại bạn có thể sử dụng chức năng "In hóa đơn" và chọn "Lưu dưới dạng PDF".');
}

// Auto-focus print button for better UX
$(document).ready(function() {
    // Add print styles
    const printStyles = `
        <style media="print">
            @page {
                margin: 1cm;
                size: A4;
            }
            body {
                font-size: 12pt;
                line-height: 1.4;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .btn {
                display: none !important;
            }
            .card-header {
                background: #f8f9fa !important;
                color: #000 !important;
            }
            .table {
                font-size: 11pt;
            }
            .display-6 {
                font-size: 18pt !important;
            }
            .fs-3 {
                font-size: 14pt !important;
            }
            .fs-5 {
                font-size: 12pt !important;
            }
        </style>
        
        <style>
            /* Breadcrumb Styling */
            .breadcrumb {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 25px;
                padding: 0.75rem 1.5rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border: 1px solid #dee2e6;
                margin: 0;
            }
            
            .breadcrumb-item {
                font-size: 0.95rem;
                font-weight: 500;
            }
            
            .breadcrumb-item a {
                color: #007bff;
                transition: all 0.3s ease;
            }
            
            .breadcrumb-item a:hover {
                color: #0056b3;
                transform: translateY(-1px);
            }
            
            .breadcrumb-item.active {
                color: #6c757d;
                font-weight: 600;
            }
            
            .breadcrumb-item + .breadcrumb-item::before {
                content: "›";
                color: #6c757d;
                font-weight: bold;
                font-size: 1.1rem;
                margin: 0 0.5rem;
            }
            
            .breadcrumb i {
                font-size: 0.9rem;
            }
        </style>
    `;
    $('head').append(printStyles);
});
</script>
