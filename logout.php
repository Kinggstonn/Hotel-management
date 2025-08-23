<?php
session_start();
include "auth/auth_functions.php";

// Log the logout action (optional)
if (isLoggedIn()) {
    $user_info = getUserInfo();
    // You could log this to a file or database if needed
    error_log("User {$user_info['username']} ({$user_info['role']}) logged out at " . date('Y-m-d H:i:s'));
}

// Logout the user
logoutUser();

$page_title = "Đăng xuất - Hotel Management";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/hotel-style.css">
    <style>
        .logout-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .logout-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }
        
        .logout-card h1 {
            color: #27ae60;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .logout-card p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .redirect-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            color: #27ae60;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <h1>✅ Đăng xuất thành công</h1>
            <p>Cảm ơn bạn đã sử dụng Hotel Management System!</p>
            
            <div class="redirect-info">
                <p>✅ Đăng xuất hoàn tất! Bạn có thể đăng nhập lại hoặc quay về trang chủ.</p>
            </div>
            
            <div class="nav-buttons">
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-key"></i> <i class="fas fa-lock"></i> Đăng nhập lại
                </a>
                <a href="index.php" class="btn btn-secondary hover-lift">
                    🏠 Trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        // No auto redirect - user decides when to navigate
        console.log('Logout completed successfully');
    </script>
</body>
</html>
