<?php
if (session_status() === PHP_SESSION_NONE) {
    $session_lifetime = 2592000; // 30 days
    ini_set('session.gc_maxlifetime', $session_lifetime);
    ini_set('session.cookie_lifetime', $session_lifetime);
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$is_admin_page = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);

if ($is_admin_page) {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../login.php");
        exit();
    }
} else {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>