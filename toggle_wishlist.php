<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to like products.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit();
}

// Check if already in wishlist
$check_query = "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    // Remove from wishlist
    $delete_query = "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
    $delete_row = mysqli_fetch_assoc($check_result);
    $wishlist_id = $delete_row['id'];
    
    $query = "DELETE FROM wishlist WHERE id = $wishlist_id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Removed from wishlist.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove from wishlist.']);
    }
} else {
    // Add to wishlist
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Added to wishlist.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add to wishlist.']);
    }
    $stmt->close();
}
?>
