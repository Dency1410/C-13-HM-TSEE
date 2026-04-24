<?php
require_once 'includes/db.php';

if (isset($_GET['email1'])) {
    // Escape incoming email and check existence in users table (not registration)
    $email = mysqli_real_escape_string($conn, trim($_GET['email1']));
    $sql = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
    $users = mysqli_query($conn, $sql);
    
    if ($users === false) {
        die('Query failed: ' . mysqli_error($conn));
    }

    if ($users->num_rows > 0) {
        // Return plain text expected by frontend JS.
        echo 'true';
    } else {
        // Email is not registered.
        echo 'false';
    }

    $users->free();
}
?>
