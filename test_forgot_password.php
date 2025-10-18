<?php
include "db.php";

echo "<h2>🧪 Test chức năng quên mật khẩu</h2>";

// Test data
$test_cases = [
    ['email' => 'admin@hotel.com', 'phone' => '0123456789', 'expected' => 'admin'],
    ['email' => 'staff@hotel.com', 'phone' => '0123456790', 'expected' => 'staff'],
    ['email' => 'customer@hotel.com', 'phone' => '0123456791', 'expected' => 'customer'],
];

echo "<div class='row'>";
foreach ($test_cases as $i => $test) {
    echo "<div class='col-md-4 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>Test " . ($i + 1) . "</div>";
    echo "<div class='card-body'>";
    
    $email = $test['email'];
    $phone = $test['phone'];
    $expected = $test['expected'];
    
    echo "<p><strong>Email:</strong> <code>$email</code></p>";
    echo "<p><strong>Phone:</strong> <code>$phone</code></p>";
    echo "<p><strong>Expected:</strong> $expected</p>";
    
    // Test query
    $query = $conn->prepare("SELECT id, username, full_name, email, phone FROM users WHERE email = ? AND phone = ?");
    $query->bind_param("ss", $email, $phone);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<div class='alert alert-success'>";
        echo "<h6>✅ Thành công!</h6>";
        echo "<p><strong>Found:</strong> " . htmlspecialchars($user['username']) . "</p>";
        echo "<p><strong>Full Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
        echo "</div>";
        
        // Test forgot password
        echo "<form method='POST' action='forgot_password.php' class='mt-2'>";
        echo "<input type='hidden' name='email' value='$email'>";
        echo "<input type='hidden' name='phone' value='$phone'>";
        echo "<button type='submit' name='reset_password' class='btn btn-primary btn-sm'>";
        echo "<i class='fas fa-key'></i> Test Quên mật khẩu";
        echo "</button>";
        echo "</form>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h6>❌ Không tìm thấy!</h6>";
        echo "<p>Không tìm thấy user với email và phone này.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

echo "<div class='mt-4'>";
echo "<a href='forgot_password.php' class='btn btn-primary'>🔐 Trang quên mật khẩu</a> ";
echo "<a href='debug_users.php' class='btn btn-info'>🔍 Debug users</a> ";
echo "<a href='install_database.php' class='btn btn-success'>🔧 Cài đặt lại DB</a>";
echo "</div>";
?>
