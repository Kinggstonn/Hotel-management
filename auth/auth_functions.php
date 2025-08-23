<?php
// Authentication Functions for Hotel Management System

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user info
 */
function getUserInfo() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'] ?? ''
    ];
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getUserRole();
    
    // Admin has all permissions
    if ($role === 'admin') {
        return true;
    }
    
    // Staff permissions
    if ($role === 'staff') {
        $staff_permissions = [
            'view_rooms',
            'view_bookings', 
            'create_booking',
            'cancel_booking',
            'view_reports'
        ];
        return in_array($permission, $staff_permissions);
    }
    
    // Customer permissions
    if ($role === 'customer') {
        $customer_permissions = [
            'view_rooms',
            'create_booking',
            'cancel_own_booking',
            'view_own_bookings'
        ];
        return in_array($permission, $customer_permissions);
    }
    
    return false;
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

/**
 * Require specific permission
 */
function requirePermission($permission) {
    requireLogin();
    
    if (!hasPermission($permission)) {
        $_SESSION['error'] = 'Bạn không có quyền truy cập tính năng này!';
        header('Location: ../dashboard.php');
        exit();
    }
}

/**
 * Login user
 */
function loginUser($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['full_name'] = $user_data['full_name'] ?? $user_data['username'];
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Get role display name
 */
function getRoleDisplayName($role) {
    switch ($role) {
        case 'admin':
            return '👑 Quản trị viên';
        case 'staff':
            return '👨‍💼 Nhân viên';
        case 'customer':
            return '👤 Khách hàng';
        default:
            return '❓ Không xác định';
    }
}

/**
 * Get role color
 */
function getRoleColor($role) {
    switch ($role) {
        case 'admin':
            return '#e74c3c';
        case 'staff':
            return '#3498db';
        case 'customer':
            return '#27ae60';
        default:
            return '#7f8c8d';
    }
}

/**
 * Check if user can cancel specific booking
 */
function canCancelBooking($booking_id, $conn) {
    $user = getUserInfo();
    if (!$user) return false;
    
    // Admin can cancel any booking
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Staff can cancel any booking
    if ($user['role'] === 'staff') {
        return true;
    }
    
    // Customer can only cancel their own bookings
    if ($user['role'] === 'customer') {
        $query = "SELECT b.* FROM bookings b 
                  JOIN guests g ON b.guest_id = g.id 
                  WHERE b.id = ? AND g.user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $booking_id, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    return false;
}

/**
 * Redirect based on role after login
 */
function redirectAfterLogin() {
    $role = getUserRole();
    
    switch ($role) {
        case 'admin':
            header('Location: admin/admin_dashboard.php');
            break;
        case 'staff':
            header('Location: dashboard.php');
            break;
        case 'customer':
            header('Location: index.php');
            break;
        default:
            header('Location: index.php');
    }
    exit();
}
?>
