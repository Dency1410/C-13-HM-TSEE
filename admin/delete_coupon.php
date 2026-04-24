<?php
session_start();
require_once '../includes/db.php';
require_once '../check_login.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['toast'] = "Coupon deleted successfully.";
    } else {
        $_SESSION['toast'] = "Failed to delete coupon.";
    }
}
header("Location: offer-coupon.php");
exit;
