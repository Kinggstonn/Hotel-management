<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require admin or staff permission
requireLogin();
requirePermission('view_reports');

$page_title = "Payment & Billing - Hotel Management";
$success_message = "";
$error_message = "";

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $booking_id = (int)$_POST['booking_id'];
    $amount = (float)$_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    
    try {
        $conn->begin_transaction();
        
        // Get booking info
        $booking_query = $conn->query("
            SELECT b.*, r.room_number, g.name as guest_name 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            JOIN guests g ON b.guest_id = g.id 
            WHERE b.id = $booking_id
        ");
        
        if ($booking_query->num_rows === 0) {
            throw new Exception("Booking không tồn tại!");
        }
        
        $booking = $booking_query->fetch_assoc();
        
        // Create payment table if not exists
        $conn->query("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT,
                amount DECIMAL(10,2),
                payment_method ENUM('cash', 'card', 'bank_transfer', 'momo', 'zalopay') DEFAULT 'cash',
                payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                transaction_id VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_by INT,
                FOREIGN KEY (booking_id) REFERENCES bookings(id),
                FOREIGN KEY (processed_by) REFERENCES users(id)
            )
        ");
        
        // Insert payment record
        $insert_payment = $conn->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, payment_status, notes, processed_by, transaction_id) 
            VALUES (?, ?, ?, 'completed', ?, ?, ?)
        ");
        $transaction_id = 'TXN_' . date('Ymd') . '_' . $booking_id . '_' . time();
        $user_id = $_SESSION['user_id'];
        
        $insert_payment->bind_param("idssis", $booking_id, $amount, $payment_method, $notes, $user_id, $transaction_id);
        $insert_payment->execute();
        
        // Update booking payment status
        $conn->query("UPDATE bookings SET payment_status = 'paid' WHERE id = $booking_id");
        
        $conn->commit();
        $success_message = "✅ Thanh toán thành công! Mã giao dịch: $transaction_id";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "❌ Lỗi: " . $e->getMessage();
    }
}

// Handle refund
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_refund'])) {
    $payment_id = (int)$_POST['payment_id'];
    $refund_amount = (float)$_POST['refund_amount'];
    $refund_reason = trim($_POST['refund_reason']);
    
    try {
        $conn->begin_transaction();
        
        // Create refund record
        $conn->query("
            CREATE TABLE IF NOT EXISTS refunds (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_id INT,
                booking_id INT,
                refund_amount DECIMAL(10,2),
                refund_reason TEXT,
                refund_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_by INT,
                FOREIGN KEY (payment_id) REFERENCES payments(id),
                FOREIGN KEY (booking_id) REFERENCES bookings(id),
                FOREIGN KEY (processed_by) REFERENCES users(id)
            )
        ");
        
        $insert_refund = $conn->prepare("
            INSERT INTO refunds (payment_id, booking_id, refund_amount, refund_reason, processed_by) 
            VALUES (?, (SELECT booking_id FROM payments WHERE id = ?), ?, ?, ?)
        ");
        $user_id = $_SESSION['user_id'];
        $insert_refund->bind_param("iidsi", $payment_id, $payment_id, $refund_amount, $refund_reason, $user_id);
        $insert_refund->execute();
        
        // Update payment status
        $conn->query("UPDATE payments SET payment_status = 'refunded' WHERE id = $payment_id");
        
        $conn->commit();
        $success_message = "✅ Hoàn tiền thành công! Số tiền: " . number_format($refund_amount, 0, ',', '.') . " VND";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "❌ Lỗi hoàn tiền: " . $e->getMessage();
    }
}

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary">💳 Payment & Billing Management</h2>
        <p class="text-muted">Quản lý thanh toán và hóa đơn khách sạn</p>
    </div>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<!-- Pending Payments -->
<div class="card shadow mb-4">
    <div class="card-header bg-warning text-dark">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-dollar-sign"></i> Thanh toán chờ xử lý</h3>
    </div>
    <div class="card-body">
    <?php
    // Add payment_status column if not exists
    $conn->query("ALTER TABLE bookings ADD COLUMN payment_status ENUM('unpaid', 'paid', 'partial') DEFAULT 'unpaid'");
    
    $pending_payments = $conn->query("
        SELECT b.id, b.total_price, b.checkin, b.checkout, b.status, b.payment_status,
               r.room_number, r.type, r.floor,
               g.name as guest_name, g.phone,
               COALESCE(SUM(p.amount), 0) as paid_amount
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN guests g ON b.guest_id = g.id
        LEFT JOIN payments p ON b.id = p.booking_id AND p.payment_status = 'completed'
        WHERE b.status IN ('booked', 'checked_in', 'checked_out') 
        AND (b.payment_status != 'paid' OR b.payment_status IS NULL)
        GROUP BY b.id
        ORDER BY b.checkin ASC
    ");
    ?>
    
    <?php if ($pending_payments->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="pendingPaymentsTable" class="table table-hover w-100">
                    <thead class="table-warning">
                    <tr>
                        <th>Booking ID</th>
                        <th>Khách hàng</th>
                        <th>Phòng</th>
                        <th>Check-in</th>
                        <th>Tổng tiền</th>
                        <th>Đã thanh toán</th>
                        <th>Còn lại</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $pending_payments->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($booking['guest_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['phone']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo $booking['room_number']; ?></strong><br>
                                    <small class="text-muted">Tầng <?php echo $booking['floor']; ?> - <?php echo $booking['type']; ?></small>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkin'])); ?></td>
                            <td><strong><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</strong></td>
                                <td><span class="text-success"><?php echo number_format($booking['paid_amount'], 0, ',', '.'); ?> VND</span></td>
                            <td>
                                <?php 
                                $remaining = $booking['total_price'] - $booking['paid_amount'];
                                    $text_class = $remaining > 0 ? 'text-danger' : 'text-success';
                                ?>
                                    <strong class="<?php echo $text_class; ?>">
                                    <?php echo number_format($remaining, 0, ',', '.'); ?> VND
                                </strong>
                            </td>
                            <td>
                                <?php if ($remaining > 0): ?>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#paymentModal<?php echo $booking['id']; ?>">
                                        💳 Thanh toán
                                    </button>
                                <?php else: ?>
                                        <span class="badge bg-success">✅ Đã thanh toán</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                            
                            <!-- Payment Modal -->
                            <div class="modal fade" id="paymentModal<?php echo $booking['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">💳 Xử lý thanh toán</h5>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Khách hàng:</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($booking['guest_name']); ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Phòng:</label>
                                                    <input type="text" class="form-control" value="<?php echo $booking['room_number']; ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Số tiền còn lại:</label>
                                                    <input type="text" class="form-control" value="<?php echo number_format($remaining, 0, ',', '.'); ?> VND" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="amount<?php echo $booking['id']; ?>" class="form-label">Số tiền thanh toán *</label>
                                                    <input type="number" class="form-control" id="amount<?php echo $booking['id']; ?>" 
                                                           name="amount" value="<?php echo $remaining; ?>" min="0" max="<?php echo $remaining; ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="payment_method<?php echo $booking['id']; ?>" class="form-label">Phương thức thanh toán *</label>
                                                    <select class="form-select" id="payment_method<?php echo $booking['id']; ?>" name="payment_method" required>
                                                        <option value="cash">💵 Tiền mặt</option>
                                                        <option value="card">💳 Thẻ tín dụng/ghi nợ</option>
                                                        <option value="bank_transfer">🏦 Chuyển khoản</option>
                                                        <option value="momo">📱 MoMo</option>
                                                        <option value="zalopay">📱 ZaloPay</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="notes<?php echo $booking['id']; ?>" class="form-label">Ghi chú</label>
                                                    <textarea class="form-control" id="notes<?php echo $booking['id']; ?>" name="notes" rows="3" placeholder="Ghi chú về giao dịch..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" name="process_payment" class="btn btn-primary">
                                                    💳 Xác nhận thanh toán
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">✨ Không có thanh toán nào chờ xử lý!</p>
            </div>
    <?php endif; ?>
    </div>
</div>

<!-- Payment History -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-chart-bar"></i> Lịch sử thanh toán</h3>
    </div>
    <div class="card-body">
    <?php
        $payments = $conn->query("
        SELECT p.*, b.id as booking_id, r.room_number, g.name as guest_name,
                   u.username as processed_by_user
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN rooms r ON b.room_id = r.id
        JOIN guests g ON b.guest_id = g.id
            LEFT JOIN users u ON p.processed_by = u.id
        ORDER BY p.created_at DESC
            LIMIT 50
    ");
    ?>
    
        <?php if ($payments->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="paymentsHistoryTable" class="table table-hover w-100">
                    <thead class="table-success">
                    <tr>
                        <th>Mã GD</th>
                        <th>Booking</th>
                        <th>Khách hàng</th>
                            <th>Phòng</th>
                        <th>Số tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                        <?php while ($payment = $payments->fetch_assoc()): ?>
                        <tr>
                                <td><code><?php echo $payment['transaction_id']; ?></code></td>
                                <td>#<?php echo $payment['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($payment['guest_name']); ?></td>
                                <td><?php echo $payment['room_number']; ?></td>
                            <td><strong><?php echo number_format($payment['amount'], 0, ',', '.'); ?> VND</strong></td>
                            <td>
                                <?php
                                $method_icons = [
                                        'cash' => '💵',
                                        'card' => '💳',
                                        'bank_transfer' => '🏦',
                                        'momo' => '📱',
                                        'zalopay' => '📱'
                                    ];
                                    $method_names = [
                                        'cash' => 'Tiền mặt',
                                        'card' => 'Thẻ',
                                        'bank_transfer' => 'Chuyển khoản',
                                        'momo' => 'MoMo',
                                        'zalopay' => 'ZaloPay'
                                    ];
                                    ?>
                                    <span class="badge bg-info">
                                        <?php echo $method_icons[$payment['payment_method']] ?? '<i class="fas fa-dollar-sign"></i>'; ?> 
                                        <?php echo $method_names[$payment['payment_method']] ?? $payment['payment_method']; ?>
                                    </span>
                            </td>
                            <td>
                                <?php
                                    $status_badges = [
                                        'pending' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'refunded' => 'bg-secondary'
                                    ];
                                    $status_text = [
                                        'pending' => '⏳ Chờ xử lý',
                                        'completed' => '✅ Hoàn thành',
                                        'failed' => '❌ Thất bại',
                                        'refunded' => '🔄 Đã hoàn tiền'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$payment['payment_status']] ?? 'bg-secondary'; ?>">
                                        <?php echo $status_text[$payment['payment_status']] ?? $payment['payment_status']; ?>
                                </span>
                            </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <?php if ($payment['payment_status'] === 'completed'): ?>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#refundModal<?php echo $payment['id']; ?>">
                                            <i class="fas fa-undo"></i> <i class="fas fa-dollar-sign"></i> Hoàn tiền
                                    </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Không thể hoàn tiền</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                            
                            <!-- Refund Modal -->
                            <?php if ($payment['payment_status'] === 'completed'): ?>
                                <div class="modal fade" id="refundModal<?php echo $payment['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-dollar-sign"></i> Xử lý hoàn tiền</h5>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Mã giao dịch:</label>
                                                        <input type="text" class="form-control" value="<?php echo $payment['transaction_id']; ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Số tiền gốc:</label>
                                                        <input type="text" class="form-control" value="<?php echo number_format($payment['amount'], 0, ',', '.'); ?> VND" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="refund_amount<?php echo $payment['id']; ?>" class="form-label">Số tiền hoàn *</label>
                                                        <input type="number" class="form-control" id="refund_amount<?php echo $payment['id']; ?>" 
                                                               name="refund_amount" value="<?php echo $payment['amount']; ?>" 
                                                               min="0" max="<?php echo $payment['amount']; ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="refund_reason<?php echo $payment['id']; ?>" class="form-label">Lý do hoàn tiền *</label>
                                                        <textarea class="form-control" id="refund_reason<?php echo $payment['id']; ?>" 
                                                                  name="refund_reason" rows="3" placeholder="Nhập lý do hoàn tiền..." required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" name="process_refund" class="btn btn-warning">
                                                        <i class="fas fa-dollar-sign"></i> Xác nhận hoàn tiền
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
            <div class="text-center text-muted py-4">
                <p class="fs-5">📭 Chưa có giao dịch thanh toán nào!</p>
            </div>
    <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-4 mb-4">
    <?php
    $payment_stats = $conn->query("
        SELECT 
            COUNT(*) as total_payments,
            SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN payment_status = 'refunded' THEN amount ELSE 0 END) as total_refunds
        FROM payments
    ")->fetch_assoc();
    
    $unpaid_bookings = $conn->query("
        SELECT COUNT(*) as count, SUM(total_price) as total
        FROM bookings 
        WHERE status IN ('booked', 'checked_in', 'checked_out') 
        AND (payment_status != 'paid' OR payment_status IS NULL)
    ")->fetch_assoc();
    ?>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-primary text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $payment_stats['total_payments']; ?></div>
                <div class="fs-5">💳 Tổng giao dịch</div>
    </div>
    </div>
</div>

    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo number_format($payment_stats['total_revenue'], 0, ',', '.'); ?></div>
                <div class="fs-5"><i class="fas fa-dollar-sign"></i> Doanh thu (VND)</div>
            </div>
            </div>
            </div>
            
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-warning text-dark text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $unpaid_bookings['count']; ?></div>
                <div class="fs-5">📋 Chờ thanh toán</div>
            </div>
    </div>
</div>

    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-info text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo number_format($payment_stats['total_refunds'], 0, ',', '.'); ?></div>
                <div class="fs-5">🔄 Tổng hoàn tiền (VND)</div>
            </div>
            </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($('#pendingPaymentsTable').length) {
        $('#pendingPaymentsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[3, 'asc']], // Sort by check-in date
            columnDefs: [
                {
                    targets: [7], // Action column
                    orderable: false,
                    searchable: false,
                    width: '120px'
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
    
    if ($('#paymentsHistoryTable').length) {
        $('#paymentsHistoryTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 25,
            order: [[7, 'desc']], // Sort by creation date
            columnDefs: [
                {
                    targets: [8], // Action column
                    orderable: false,
                    searchable: false,
                    width: '100px'
                }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }
});
</script>
