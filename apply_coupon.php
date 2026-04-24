<?php
header('Content-Type: application/json');
require_once 'includes/db.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

$code = strtoupper(trim($data['code'] ?? ''));
$subtotal = (float)($data['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM coupons WHERE coupon_code = ? AND status = 'ACTIVE'");
$stmt->bind_param("s", $code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or inactive coupon code.']);
    exit;
}

$coupon = $res->fetch_assoc();

if (strtotime($coupon['valid_until']) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'This coupon has expired.']);
    exit;
}

if ($coupon['current_uses'] >= $coupon['max_uses']) {
    echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

// Calculate discount
$discountAmount = 0;
if ($coupon['discount_type'] === 'Percentage') {
    $discountAmount = $subtotal * ($coupon['discount_value'] / 100);
} else {
    $discountAmount = $coupon['discount_value'];
}

// Cap discount at subtotal
if ($discountAmount > $subtotal) {
    $discountAmount = $subtotal;
}

echo json_encode([
    'success' => true,
    'coupon_code' => $coupon['coupon_code'],
    'discount_amount' => round($discountAmount, 2),
    'discount_type' => $coupon['discount_type'],
    'discount_value' => $coupon['discount_value']
]);
exit;
