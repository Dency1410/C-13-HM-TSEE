<?php
session_start();
require_once 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'mail_content.php';

if (!isset($_SESSION['forgot_email'])) {
    $_SESSION['error'] = 'Session expired. Please restart the reset process.';
    echo "<script>window.location.href = 'forget-password.php';</script>";
    exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['forgot_email']);
$db_success = false;
$send_email = false;
$redirect_url = null;

$user_query = "SELECT full_name FROM users WHERE email = '$email'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

if (!$user_data) {
    $_SESSION['error'] = 'Email not registered.';
    $redirect_url = "forget-password.php";
} else {
    $q = "SELECT * FROM password_resets WHERE email='$email' ORDER BY id DESC LIMIT 1";
    $res = mysqli_query($conn, $q);
    $count = mysqli_num_rows($res);
    $result = mysqli_fetch_assoc($res);

    if ($count > 0 && $result['locked_until'] != null && strtotime($result['locked_until']) > time()) {
        $_SESSION['error'] = "Account locked out. Please try again after 24 hours.";
        $redirect_url = "login.php";
    } else {
        $new_otp = sprintf("%06d", mt_rand(100000, 999999));
        $expiry_time = date("Y-m-d H:i:s", strtotime('+2 minutes'));

        if ($count > 0) {
            $attempts = $result['attempts'];
            if ($attempts >= 3) {
                // If they already tried 3 times, lock them out.
                $lock_time = date("Y-m-d H:i:s", strtotime('+24 hours'));
                mysqli_query($conn, "UPDATE password_resets SET locked_until = '$lock_time' WHERE email = '$email'");
                
                $_SESSION['error'] = "Maximum OTP request limit reached. You have been locked out for 24 hours.";
                $redirect_url = "login.php";
            } else {
                $attempts += 1;
                $update_query = "UPDATE password_resets SET otp = '$new_otp', expires_at = '$expiry_time', attempts = $attempts WHERE email = '$email'";
                if (mysqli_query($conn, $update_query)) {
                    $db_success = true;
                    $send_email = true;
                }
            }
        } else {
            // Shouldn't happen unless deleted mid-flight, but handle safely
            $insert_query = "INSERT INTO password_resets (email, otp, expires_at, attempts) VALUES ('$email', '$new_otp', '$expiry_time', 1)";
            if (mysqli_query($conn, $insert_query)) {
                $db_success = true;
                $send_email = true;
            }
        }
    }
}

if ($db_success && $send_email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 587; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dencysantoki@gmail.com'; 
        $mail->Password   = 'xdfp opip sngt akdh';   
        $mail->SMTPSecure = 'tls';
        
        $mail->setFrom('dencysantoki@gmail.com', 'H&M Team');
        $mail->addAddress($email, $user_data['full_name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'H&M - Password Reset OTP (Resent)';
        $mail->Body    = getForgotPasswordOtpEmailBody($new_otp, $user_data['full_name']);
        $mail->AltBody = "Hello " . $user_data['full_name'] . ",\n\nYour new OTP code to reset your password is: $new_otp\n\nThis code is valid for exactly 2 minutes.\n\nBest regards,\nH&M Team";
        
        $mail->send();
        
        unset($_SESSION['forgot_otp_verified']);
        $_SESSION['success'] = 'A new OTP has been sent! Please check your email.';
        $redirect_url = "verify_otp.php";
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to resend the email. Please try again.';
        $redirect_url = "verify_otp.php";
    }
} else if ($db_success == false && $redirect_url == null) {
    $_SESSION['error'] = 'Database error. Please try again.';
    $redirect_url = "verify_otp.php";
}

if ($redirect_url) {
    echo "<script>window.location.href = '$redirect_url';</script>";
    exit();
}
?>
