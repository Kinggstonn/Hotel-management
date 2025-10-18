<?php
include "db.php";
include "auth/auth_functions.php";

// Require login
requireLogin();

$page_title = "Demo Luồng Đặt Phòng → Thanh toán";

include "includes/header.php";
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="card-title fs-3 mb-0"><i class="fas fa-route"></i> Demo Luồng Đặt Phòng → Thanh toán</h2>
        </div>
        <div class="card-body">
            <p class="text-muted">Trải nghiệm luồng đặt phòng hoàn chỉnh từ chọn phòng đến thanh toán thành công</p>
        </div>
    </div>

    <!-- Flow Diagram -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h3 class="card-title fs-5 mb-0"><i class="fas fa-sitemap"></i> Luồng xử lý</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="flow-diagram">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h5>Chọn phòng</h5>
                                <p>Khách hàng chọn phòng từ danh sách</p>
                                <a href="index.php" class="btn btn-primary btn-sm">Xem phòng</a>
                            </div>
                        </div>
                        
                        <div class="step-arrow">→</div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h5>Đặt phòng</h5>
                                <p>Điền thông tin và xác nhận đặt phòng</p>
                                <span class="badge bg-success">Tự động chuyển</span>
                            </div>
                        </div>
                        
                        <div class="step-arrow">→</div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h5>Thanh toán</h5>
                                <p>Nhập thông tin thẻ và thanh toán</p>
                                <span class="badge bg-info">Nếu thành công</span>
                            </div>
                        </div>
                        
                        <div class="step-arrow">→</div>
                        
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h5>Hóa đơn</h5>
                                <p>Xem và in hóa đơn</p>
                                <span class="badge bg-success">Hoàn thành</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="step">
                            <div class="step-number">❌</div>
                            <div class="step-content">
                                <h5>Thanh toán thất bại</h5>
                                <p>Quay lại trang đặt phòng</p>
                                <span class="badge bg-warning">Nếu thất bại</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Rooms -->
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h3 class="card-title fs-5 mb-0"><i class="fas fa-bed"></i> Phòng có sẵn để test</h3>
        </div>
        <div class="card-body">
            <?php
            $available_rooms = $conn->query("
                SELECT * FROM rooms 
                WHERE status = 'available' 
                ORDER BY room_number ASC
                LIMIT 6
            ");
            ?>
            
            <?php if ($available_rooms->num_rows > 0): ?>
                <div class="row g-3">
                    <?php while ($room = $available_rooms->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bed"></i> Phòng <?php echo $room['room_number']; ?>
                                        <span class="badge <?php echo $room['type'] === 'VIP' ? 'bg-warning' : 'bg-primary'; ?> ms-2">
                                            <?php echo $room['type']; ?>
                                        </span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <strong>Tầng:</strong> <?php echo $room['floor']; ?><br>
                                        <strong>Giá:</strong> <span class="text-success fw-bold">
                                            <?php echo number_format($room['price'], 0, ',', '.'); ?> VND/đêm
                                        </span>
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <a href="book.php?room_id=<?php echo $room['id']; ?>" 
                                       class="btn btn-primary w-100">
                                        <i class="fas fa-calendar-plus"></i> Đặt phòng này
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Không có phòng trống</h5>
                    <p>Tất cả phòng đã được đặt. Vui lòng thử lại sau.</p>
                    <a href="index.php" class="btn btn-primary">Xem tất cả phòng</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Test Cases -->
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-dark">
            <h3 class="card-title fs-5 mb-0"><i class="fas fa-vial"></i> Test Cases</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-check-circle text-success"></i> Test Case 1: Luồng thành công</h5>
                    <ol>
                        <li>Chọn phòng có sẵn</li>
                        <li>Điền thông tin đặt phòng</li>
                        <li>Nhấn "Đặt phòng"</li>
                        <li><strong>→ Tự động chuyển đến trang thanh toán</strong></li>
                        <li>Nhập thẻ hợp lệ: <code>4111111111111111</code></li>
                        <li>Nhấn "Xác nhận thanh toán"</li>
                        <li><strong>→ Chuyển đến trang hóa đơn</strong></li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-times-circle text-danger"></i> Test Case 2: Thanh toán thất bại</h5>
                    <ol>
                        <li>Chọn phòng có sẵn</li>
                        <li>Điền thông tin đặt phòng</li>
                        <li>Nhấn "Đặt phòng"</li>
                        <li><strong>→ Tự động chuyển đến trang thanh toán</strong></li>
                        <li>Nhập thẻ không hợp lệ: <code>1234567890123456</code></li>
                        <li>Nhấn "Xác nhận thanh toán"</li>
                        <li><strong>→ Hiển thị lỗi và nút "Quay lại đặt phòng"</strong></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card shadow">
        <div class="card-header bg-secondary text-white">
            <h3 class="card-title fs-5 mb-0"><i class="fas fa-bolt"></i> Thao tác nhanh</h3>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Xem tất cả phòng
                </a>
                <a href="test_payment.php" class="btn btn-info">
                    <i class="fas fa-vial"></i> Test thanh toán
                </a>
                <a href="payment_billing.php" class="btn btn-success">
                    <i class="fas fa-credit-card"></i> Quản lý thanh toán
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.flow-diagram {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

.step {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: #f8f9fa;
    min-width: 200px;
    flex: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.step-content h5 {
    margin: 0 0 5px 0;
    color: #495057;
}

.step-content p {
    margin: 0;
    font-size: 14px;
    color: #6c757d;
}

.step-arrow {
    font-size: 24px;
    color: #007bff;
    font-weight: bold;
}

@media (max-width: 768px) {
    .flow-diagram {
        flex-direction: column;
    }
    
    .step-arrow {
        transform: rotate(90deg);
    }
}
</style>

<?php include "includes/footer.php"; ?>
