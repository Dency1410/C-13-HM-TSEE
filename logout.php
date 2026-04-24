<?php
session_start();

// Detect who is logging out (admin panel or frontend)
$is_admin_logout = isset($_GET['admin']) || (strpos($_SERVER['HTTP_REFERER'] ?? '', '/admin/') !== false);

if ($is_admin_logout) {
    // Only clear admin session keys
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_avatar'],
        $_SESSION['user_role']
    );
    header("Location: login.php");
} else {
    // Only clear customer session keys
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_email'],
        $_SESSION['user_name'],
        $_SESSION['role'],
        $_SESSION['profile_pic'],
        $_SESSION['user_avatar'],
        $_SESSION['user_phone'],
        $_SESSION['user_gender'],
        $_SESSION['user_dob'],
        $_SESSION['user_address'],
        $_SESSION['redirect_to']
    );
    header("Location: home.php");
}
exit();
?>