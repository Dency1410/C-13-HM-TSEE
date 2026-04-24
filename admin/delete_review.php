<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM product_reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = "Review deleted successfully.";
    } else {
        $_SESSION['toast'] = "Failed to delete review.";
    }
}

header("Location: review.php");
exit;
