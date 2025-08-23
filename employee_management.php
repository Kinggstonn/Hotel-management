<?php 
include "db.php"; 
include "auth/auth_functions.php";

// Require admin permission only
requireLogin();
requirePermission('admin');

$page_title = "Employee Management - Hotel Management";
$success_message = "";
$error_message = "";

// Handle employee creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_employee'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    try {
        // Check if username/email already exists
        $check_user = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_user->bind_param("ss", $username, $email);
        $check_user->execute();
        
        if ($check_user->get_result()->num_rows > 0) {
            $error_message = "<i class='fas fa-exclamation-triangle'></i> Username hoặc email đã tồn tại!";
        } else {
            // Hash password and create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $create_user = $conn->prepare("
                INSERT INTO users (username, password, full_name, email, phone, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $create_user->bind_param("sssssss", $username, $hashed_password, $full_name, $email, $phone, $role, $status);
            
            if ($create_user->execute()) {
                $success_message = "<i class='fas fa-check-circle'></i> Tạo nhân viên thành công!";
            } else {
                $error_message = "<i class='fas fa-exclamation-triangle'></i> Có lỗi khi tạo nhân viên!";
            }
        }
    } catch (Exception $e) {
        $error_message = "<i class='fas fa-exclamation-triangle'></i> Lỗi: " . $e->getMessage();
    }
}

// Handle employee update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employee'])) {
    $user_id = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $new_password = $_POST['new_password'];
    
    try {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_user = $conn->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, role = ?, status = ?, password = ?
                WHERE id = ? AND id != ?
            ");
            $current_user_id = $_SESSION['user_id'];
            $update_user->bind_param("ssssssii", $full_name, $email, $phone, $role, $status, $hashed_password, $user_id, $current_user_id);
        } else {
            // Update without changing password
            $update_user = $conn->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, role = ?, status = ?
                WHERE id = ? AND id != ?
            ");
            $current_user_id = $_SESSION['user_id'];
            $update_user->bind_param("sssssii", $full_name, $email, $phone, $role, $status, $user_id, $current_user_id);
        }
        
        if ($update_user->execute() && $update_user->affected_rows > 0) {
            $success_message = "<i class='fas fa-check-circle'></i> Cập nhật nhân viên thành công!";
        } else {
            $error_message = "<i class='fas fa-exclamation-triangle'></i> Không thể cập nhật hoặc không có thay đổi!";
        }
    } catch (Exception $e) {
        $error_message = "<i class='fas fa-exclamation-triangle'></i> Lỗi: " . $e->getMessage();
    }
}

// Handle employee deactivation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $current_user_id = $_SESSION['user_id'];
        $update_status = $conn->prepare("
            UPDATE users 
            SET status = ?
            WHERE id = ? AND id != ?
        ");
        $update_status->bind_param("sii", $new_status, $user_id, $current_user_id);
        
        if ($update_status->execute()) {
            $status_text = $new_status === 'active' ? 'kích hoạt' : 'vô hiệu hóa';
            $success_message = "<i class='fas fa-check-circle'></i> Đã <i class='fas fa-toggle-on'></i> $status_text nhân viên thành công!";
        } else {
            $error_message = "<i class='fas fa-exclamation-triangle'></i> Không thể cập nhật trạng thái!";
        }
    } catch (Exception $e) {
        $error_message = "<i class='fas fa-exclamation-triangle'></i> Lỗi: " . $e->getMessage();
    }
}

include "includes/header.php";
?>

<div class="card shadow mb-4">
    <div class="card-body">
        <h2 class="card-title fs-3 text-primary"><i class="fas fa-users"></i> Employee Management System</h2>
        <p class="text-muted">Quản lý nhân viên và phân quyền trong hệ thống khách sạn</p>
    </div>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Employee Statistics -->
<?php
$employee_stats = $conn->query("
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customer_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
    FROM users
")->fetch_assoc();
?>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-primary text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $employee_stats['total_employees']; ?></div>
                <div class="fs-5"><i class="fas fa-users"></i> Tổng users</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-warning text-dark text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $employee_stats['admin_count']; ?></div>
                <div class="fs-5"><i class="fas fa-crown"></i> Quản trị viên</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-info text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $employee_stats['staff_count']; ?></div>
                <div class="fs-5"><i class="fas fa-user-tie"></i> Nhân viên</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card bg-success text-white text-center h-100 shadow">
            <div class="card-body">
                <div class="display-4 fw-bold mb-2"><?php echo $employee_stats['active_count']; ?></div>
                <div class="fs-5"><i class="fas fa-check-circle"></i> Đang hoạt động</div>
            </div>
        </div>
    </div>
</div>

<!-- Add New Employee -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h3 class="card-title fs-5 mb-0">➕ Thêm nhân viên mới</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> <i class="fas fa-user"></i> Username *
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="Tên đăng nhập">
                    <div class="invalid-feedback">
                        Vui lòng nhập username
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="password" class="form-label">
                        <i class="fas fa-key"></i> <i class="fas fa-lock"></i> Password *
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Mật khẩu" minlength="6">
                    <div class="invalid-feedback">
                        Vui lòng nhập password (tối thiểu 6 ký tự)
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="full_name" class="form-label">
                        <i class="fas fa-id-badge"></i> <i class="fas fa-tag"></i> Họ và tên *
                    </label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required placeholder="Họ và tên đầy đủ">
                    <div class="invalid-feedback">
                        Vui lòng nhập họ và tên
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="email@hotel.com">
                    <div class="invalid-feedback">
                        Vui lòng nhập email hợp lệ
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i> <i class="fas fa-phone"></i> Số điện thoại
                    </label>
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="0123456789">
                </div>
                
                <div class="col-md-6">
                    <label for="role" class="form-label">
                        <i class="fas fa-shield-alt"></i> <i class="fas fa-shield-alt"></i> Vai trò *
                    </label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Chọn vai trò...</option>
                        <option value="admin"><i class="fas fa-crown"></i> Quản trị viên</option>
                        <option value="staff"><i class="fas fa-user-tie"></i> Nhân viên</option>
                        <option value="customer"><i class="fas fa-user"></i> Khách hàng</option>
                    </select>
                    <div class="invalid-feedback">
                        Vui lòng chọn vai trò
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">
                        <i class="fas fa-toggle-on"></i> <i class="fas fa-sync-alt"></i> Trạng thái *
                    </label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="">Chọn trạng thái...</option>
                        <option value="active"><i class="fas fa-check-circle"></i> Hoạt động</option>
                        <option value="inactive"><i class="fas fa-times-circle"></i> Không hoạt động</option>
                    </select>
                    <div class="invalid-feedback">
                        Vui lòng chọn trạng thái
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="create_employee" class="btn btn-success btn-lg">
                                            <i class="fas fa-user-plus"></i> <i class="fas fa-users"></i> Tạo nhân viên mới
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Employee List -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title fs-5 mb-0"><i class="fas fa-list-alt"></i> Danh sách nhân viên</h3>
    </div>
    <div class="card-body">
        <?php
        $employees = $conn->query("
            SELECT id, username, full_name, email, phone, role, status, created_at
            FROM users 
            ORDER BY created_at DESC
        ");
        ?>
        
        <?php if ($employees->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="employeesTable" class="table table-hover w-100">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($employee = $employees->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $employee['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($employee['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($employee['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($employee['phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($employee['phone']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($employee['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $role_badges = [
                                        'admin' => 'bg-danger',
                                        'staff' => 'bg-warning',
                                        'customer' => 'bg-info'
                                    ];
                                    $role_text = [
                                        'admin' => '<i class="fas fa-crown"></i> Admin',
                                        'staff' => '<i class="fas fa-user-tie"></i> Staff',
                                        'customer' => '<i class="fas fa-user"></i> Customer'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $role_badges[$employee['role']] ?? 'bg-secondary'; ?>">
                                        <?php echo $role_text[$employee['role']] ?? $employee['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'active' => 'bg-success',
                                        'inactive' => 'bg-secondary'
                                    ];
                                    $status_text = [
                                        'active' => '<i class="fas fa-check-circle"></i> Hoạt động',
                                        'inactive' => '<i class="fas fa-times-circle"></i> Không hoạt động'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$employee['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo $status_text[$employee['status']] ?? $employee['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($employee['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editEmployeeModal<?php echo $employee['id']; ?>">
                                            ✏️ Sửa
                                        </button>
                                        
                                        <?php if ($employee['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#toggleStatusModal<?php echo $employee['id']; ?>">
                                                🔄 Trạng thái
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Edit Employee Modal -->
                            <div class="modal fade" id="editEmployeeModal<?php echo $employee['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Chỉnh sửa nhân viên</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Username:</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['username']); ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="new_password<?php echo $employee['id']; ?>" class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                                                        <input type="password" class="form-control" id="new_password<?php echo $employee['id']; ?>" name="new_password" minlength="6">
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label for="edit_full_name<?php echo $employee['id']; ?>" class="form-label">Họ và tên *</label>
                                                        <input type="text" class="form-control" id="edit_full_name<?php echo $employee['id']; ?>" name="full_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_email<?php echo $employee['id']; ?>" class="form-label">Email *</label>
                                                        <input type="email" class="form-control" id="edit_email<?php echo $employee['id']; ?>" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_phone<?php echo $employee['id']; ?>" class="form-label">Số điện thoại</label>
                                                        <input type="tel" class="form-control" id="edit_phone<?php echo $employee['id']; ?>" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_role<?php echo $employee['id']; ?>" class="form-label">Vai trò *</label>
                                                        <select class="form-select" id="edit_role<?php echo $employee['id']; ?>" name="role" required>
                                                            <option value="admin" <?php echo $employee['role'] === 'admin' ? 'selected' : ''; ?>><i class="fas fa-crown"></i> Quản trị viên</option>
                                                            <option value="staff" <?php echo $employee['role'] === 'staff' ? 'selected' : ''; ?>><i class="fas fa-user-tie"></i> Nhân viên</option>
                                                            <option value="customer" <?php echo $employee['role'] === 'customer' ? 'selected' : ''; ?>><i class="fas fa-user"></i> Khách hàng</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_status<?php echo $employee['id']; ?>" class="form-label">Trạng thái *</label>
                                                        <select class="form-select" id="edit_status<?php echo $employee['id']; ?>" name="status" required>
                                                            <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>><i class="fas fa-check-circle"></i> Hoạt động</option>
                                                            <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>><i class="fas fa-times-circle"></i> Không hoạt động</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" name="update_employee" class="btn btn-primary">
                                                    ✏️ Cập nhật
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Toggle Status Modal -->
                            <?php if ($employee['id'] != $_SESSION['user_id']): ?>
                                <div class="modal fade" id="toggleStatusModal<?php echo $employee['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">🔄 Thay đổi trạng thái</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Nhân viên:</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['full_name']); ?> (<?php echo htmlspecialchars($employee['username']); ?>)" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="new_status<?php echo $employee['id']; ?>" class="form-label">Trạng thái mới *</label>
                                                        <select class="form-select" id="new_status<?php echo $employee['id']; ?>" name="new_status" required>
                                                            <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>><i class="fas fa-check-circle"></i> Hoạt động</option>
                                                            <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>><i class="fas fa-times-circle"></i> Không hoạt động</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="alert alert-warning">
                                                                                <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lưu ý:</strong> Bạn không thể thay đổi trạng thái của chính mình!
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" name="toggle_status" class="btn btn-warning">
                                                        🔄 Cập nhật trạng thái
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
                <p class="fs-5">📭 Chưa có nhân viên nào trong hệ thống!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($('#employeesTable').length) {
        $('#employeesTable').DataTable({
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
                    width: '150px'
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
    
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
});
</script>
