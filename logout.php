<?php
session_start();
include "auth/auth_functions.php";

// Log the logout action (optional)
if (isLoggedIn()) {
    $user_info = getUserInfo();
    error_log("User {$user_info['username']} ({$user_info['role']}) logged out at " . date('Y-m-d H:i:s'));
}

// Logout the user and immediately redirect to login
logoutUser();
header('Location: login.php');
exit();
