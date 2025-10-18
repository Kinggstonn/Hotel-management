<?php
include "db.php";

echo "<h2>🔍 Debug - Kiểm tra dữ liệu users</h2>";

try {
    // Hiển thị tất cả users
    echo "<h3>📋 Danh sách users trong database:</h3>";
    $users_query = $conn->query("SELECT id, username, full_name, email, phone FROM users");
    
    if ($users_query->num_rows > 0) {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Phone</th></tr></thead>";
        echo "<tbody>";
        
        while ($user = $users_query->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['phone']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p class='text-danger'>❌ Không có user nào trong database!</p>";
    }
    
    // Test query với dữ liệu mẫu
    echo "<h3>🧪 Test query với dữ liệu mẫu:</h3>";
    
    $test_email = "admin@hotel.com";
    $test_phone = "0123456789";
    
    echo "<p><strong>Test với:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <code>$test_email</code></li>";
    echo "<li>Phone: <code>$test_phone</code></li>";
    echo "</ul>";
    
    $test_query = $conn->prepare("SELECT id, username, full_name, email, phone FROM users WHERE email = ? AND phone = ?");
    $test_query->bind_param("ss", $test_email, $test_phone);
    $test_query->execute();
    $test_result = $test_query->get_result();
    
    if ($test_result->num_rows > 0) {
        $test_user = $test_result->fetch_assoc();
        echo "<div class='alert alert-success'>";
        echo "<h5>✅ Tìm thấy user:</h5>";
        echo "<p><strong>ID:</strong> " . $test_user['id'] . "</p>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($test_user['username']) . "</p>";
        echo "<p><strong>Full Name:</strong> " . htmlspecialchars($test_user['full_name']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($test_user['email']) . "</p>";
        echo "<p><strong>Phone:</strong> " . htmlspecialchars($test_user['phone']) . "</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h5>❌ Không tìm thấy user với email và phone này!</h5>";
        echo "</div>";
        
        // Kiểm tra từng trường riêng lẻ
        echo "<h4>🔍 Kiểm tra từng trường:</h4>";
        
        // Check email only
        $email_query = $conn->prepare("SELECT id, username, email, phone FROM users WHERE email = ?");
        $email_query->bind_param("s", $test_email);
        $email_query->execute();
        $email_result = $email_query->get_result();
        
        if ($email_result->num_rows > 0) {
            $email_user = $email_result->fetch_assoc();
            echo "<div class='alert alert-warning'>";
            echo "<h6>📧 Tìm thấy user với email này:</h6>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($email_user['email']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($email_user['phone']) . "</p>";
            echo "<p><strong>Phone length:</strong> " . strlen($email_user['phone']) . " ký tự</p>";
            echo "<p><strong>Phone bytes:</strong> " . bin2hex($email_user['phone']) . "</p>";
            echo "</div>";
        } else {
            echo "<p class='text-danger'>❌ Không tìm thấy user với email: $test_email</p>";
        }
        
        // Check phone only
        $phone_query = $conn->prepare("SELECT id, username, email, phone FROM users WHERE phone = ?");
        $phone_query->bind_param("s", $test_phone);
        $phone_query->execute();
        $phone_result = $phone_query->get_result();
        
        if ($phone_result->num_rows > 0) {
            $phone_user = $phone_result->fetch_assoc();
            echo "<div class='alert alert-warning'>";
            echo "<h6>📱 Tìm thấy user với phone này:</h6>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($phone_user['email']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($phone_user['phone']) . "</p>";
            echo "</div>";
        } else {
            echo "<p class='text-danger'>❌ Không tìm thấy user với phone: $test_phone</p>";
        }
    }
    
    echo "<div class='mt-4'>";
    echo "<a href='forgot_password.php' class='btn btn-primary'>🔐 Quên mật khẩu</a> ";
    echo "<a href='install_database.php' class='btn btn-success'>🔧 Cài đặt lại database</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
