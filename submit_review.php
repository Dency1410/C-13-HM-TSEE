<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to review a product.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review_text = trim($_POST['review_text'] ?? '');

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Please select a star rating between 1 and 5.']);
        exit;
    }

    if (empty($review_text)) {
        echo json_encode(['success' => false, 'message' => 'Please write a review.']);
        exit;
    }

    // ── Guard: only allow review if the user has a DELIVERED order containing this product ──
    $check = $conn->prepare(
        "SELECT oi.id FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered'
         LIMIT 1"
    );
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You can only review products from delivered orders.']);
        $check->close();
        exit;
    }
    $check->close();

    // Check if the user has already reviewed this product
    $stmt = $conn->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing review
        $update = $conn->prepare("UPDATE product_reviews SET rating = ?, review_text = ?, created_at = NOW() WHERE user_id = ? AND product_id = ?");
        $update->bind_param("isii", $rating, $review_text, $user_id, $product_id);
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Your review has been updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update review. Please try again.']);
        }
    } else {
        // Insert new review
        $insert = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiis", $product_id, $user_id, $rating, $review_text);
        if ($insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Thank you! Your review has been submitted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save review. Please try again.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
