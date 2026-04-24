<?php
session_start();
require 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT rating, review_text FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'rating' => (int)$row['rating'],
        'review_text' => $row['review_text']
    ]);
} else {
    echo json_encode(['success' => false]);
}
