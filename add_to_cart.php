<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$size       = isset($_POST['size'])       ? trim($_POST['size'])       : '';

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product']);
    exit;
}

if (empty($size)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a size']);
    exit;
}

// Verify product exists
$check = mysqli_query($conn, "SELECT id FROM products WHERE id = $product_id");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

// Determine user_id (NULL for guests)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// Ensure cart table has user_id and size columns (safe auto-migrate)
$check_uid = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'user_id'");
if ($check_uid && mysqli_num_rows($check_uid) === 0) {
    mysqli_query($conn, "ALTER TABLE cart ADD COLUMN user_id INT(11) DEFAULT NULL");
}
$check_size = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'size'");
if ($check_size && mysqli_num_rows($check_size) === 0) {
    mysqli_query($conn, "ALTER TABLE cart ADD COLUMN size VARCHAR(20) DEFAULT NULL");
}

if ($user_id !== null) {
    // Check if same product+size already in cart for this user
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
    $stmt->bind_param("iis", $user_id, $product_id, $size);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Increment quantity
        $row = $res->fetch_assoc();
        $new_qty = $row['quantity'] + 1;
        $stmt2 = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt2->bind_param("ii", $new_qty, $row['id']);
        $stmt2->execute();
    } else {
        // Insert new row
        $stmt2 = $conn->prepare("INSERT INTO cart (user_id, product_id, size, quantity) VALUES (?, ?, ?, 1)");
        $stmt2->bind_param("iis", $user_id, $product_id, $size);
        $stmt2->execute();
    }
} else {
    // Guest: use session cart
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $key = $product_id . '_' . $size;
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity']++;
    } else {
        $_SESSION['cart'][$key] = ['product_id' => $product_id, 'size' => $size, 'quantity' => 1];
    }
}

// Get updated cart count
if ($user_id !== null) {
    $count_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id");
    $count_row = mysqli_fetch_assoc($count_res);
    $cart_count = (int)($count_row['total'] ?? 0);
} else {
    $cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
}

echo json_encode([
    'status'     => 'success',
    'message'    => 'Product added to bag!',
    'cart_count' => $cart_count
]);
?>
