<?php
if (session_status() === PHP_SESSION_NONE) {
    // Keep sessions alive for 30 days (2,592,000 seconds)
    $session_lifetime = 2592000;
    ini_set('session.gc_maxlifetime', $session_lifetime);
    ini_set('session.cookie_lifetime', $session_lifetime);
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path'     => '/',
        'secure'   => false, // set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
$host = "localhost";
$user = "root";        
$pass = "";           
$db   = "hm"; 
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}else{
    // echo "Connection Successfully";
}
?>