

<?php
// checkout.php
session_start();
require 'includes/db.php';

// Fetch cart items
$cart_items = [];
$subtotal = 0;

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if ($user_id !== null) {
    $query = "SELECT p.id, p.name as title, p.price, p.image, 
                     c.id as cart_id, c.quantity, c.size 
              FROM cart c 
              INNER JOIN products p ON c.product_id = p.id
              WHERE c.user_id = $user_id
              ORDER BY c.created_at DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cart_items[] = $row;
            $subtotal += $row['price'] * $row['quantity'];
        }
    }
} else {
    // Guest: session cart
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $entry) {
            $pid = (int)$entry['product_id'];
            $res = mysqli_query($conn, "SELECT id, name as title, price, image FROM products WHERE id = $pid");
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $row['cart_id']  = $pid . '_' . $entry['size'];
                $row['quantity'] = $entry['quantity'];
                $row['size']     = $entry['size'];
                $cart_items[]    = $row;
                $subtotal       += $row['price'] * $row['quantity'];
            }
        }
    }
}

$tax = round($subtotal * 0.08, 2);
$shipping = ($subtotal > 0) ? 5.99 : 0;
$total = $subtotal + $tax + $shipping;

// Handle AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_order') {
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
        exit;
    }
    
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zipCode = trim($_POST['zipCode'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    $razorpay_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
    if(empty($razorpay_payment_id)){
        echo json_encode(['success' => false, 'message' => 'Payment validation failed. Please try again.']);
        exit;
    }
    $payment_status = 'Success';
    
    $coupon_code = trim($_POST['couponCode'] ?? '');
    $discount_amount = 0;
    
    // Re-verify coupon backend
    if (!empty($coupon_code)) {
        $stmt_c = $conn->prepare("SELECT * FROM coupons WHERE coupon_code = ? AND status = 'ACTIVE' AND valid_until >= CURDATE() AND current_uses < max_uses");
        $stmt_c->bind_param("s", $coupon_code);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();
        if ($res_c->num_rows > 0) {
            $coupon = $res_c->fetch_assoc();
            if ($coupon['discount_type'] === 'Percentage') {
                $discount_amount = $subtotal * ($coupon['discount_value'] / 100);
            } else {
                $discount_amount = $coupon['discount_value'];
            }
            if ($discount_amount > $subtotal) $discount_amount = $subtotal;
        } else {
            $coupon_code = null; // invalid
        }
    } else {
        $coupon_code = null;
    }

    $total = $subtotal - $discount_amount + $tax + $shipping;

    $stmt = $conn->prepare("INSERT INTO `orders` (user_id, first_name, last_name, email, phone, address, city, zip_code, state, country, subtotal, tax, shipping, total, coupon_code, discount_amount, razorpay_payment_id, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssssddddsiss", $user_id, $firstName, $lastName, $email, $phone, $address, $city, $zipCode, $state, $country, $subtotal, $tax, $shipping, $total, $coupon_code, $discount_amount, $razorpay_payment_id, $payment_status);
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        
        $stmt_item = $conn->prepare("INSERT INTO `order_items` (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt_item->execute();
        }
        
        if ($coupon_code) {
            $conn->query("UPDATE coupons SET current_uses = current_uses + 1 WHERE coupon_code = '" . $conn->real_escape_string($coupon_code) . "'");
        }
        
        if ($user_id !== null) {
            $conn->query("DELETE FROM `cart` WHERE user_id = $user_id");

            // Save shipping details back to users table for future pre-fill
            $save = $conn->prepare(
                "UPDATE users SET phone=?, address=?, city=?, zip_code=?, state=?, country=? WHERE id=?"
            );
            $save->bind_param("ssssssi", $phone, $address, $city, $zipCode, $state, $country, $user_id);
            $save->execute();
        } else {
            unset($_SESSION['cart']);
        }
        
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }
}

// Pre-fill ALL checkout fields from the users table (saved from previous orders)
$user_prefill = [
    'firstName' => '',
    'lastName'  => '',
    'email'     => '',
    'phone'     => '',
    'address'   => '',
    'city'      => '',
    'zipCode'   => '',
    'state'     => '',
    'country'   => '',
];
if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];

    // Step 1: Load from users table
    $u = $conn->prepare("SELECT full_name, email, phone, address, city, zip_code, state, country FROM users WHERE id = ?");
    $u->bind_param("i", $uid);
    $u->execute();
    $urow = $u->get_result()->fetch_assoc();
    if ($urow) {
        $name_parts = explode(' ', $urow['full_name'] ?? '', 2);
        $user_prefill['firstName'] = $name_parts[0] ?? '';
        $user_prefill['lastName']  = $name_parts[1] ?? '';
        $user_prefill['email']     = $urow['email']    ?? '';
        $user_prefill['phone']     = $urow['phone']    ?? '';
        $user_prefill['address']   = $urow['address']  ?? '';
        $user_prefill['city']      = $urow['city']     ?? '';
        $user_prefill['zipCode']   = $urow['zip_code'] ?? '';
        $user_prefill['state']     = $urow['state']    ?? '';
        $user_prefill['country']   = $urow['country']  ?? '';
    }

    // Step 2: If city/state/country are missing, fall back to the most recent order
    if (empty($user_prefill['city']) || empty($user_prefill['state']) || empty($user_prefill['country'])) {
        $o = $conn->prepare(
            "SELECT phone, address, city, zip_code, state, country FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $o->bind_param("i", $uid);
        $o->execute();
        $orow = $o->get_result()->fetch_assoc();
        if ($orow) {
            if (empty($user_prefill['phone']))   $user_prefill['phone']   = $orow['phone']    ?? '';
            if (empty($user_prefill['address'])) $user_prefill['address'] = $orow['address']  ?? '';
            if (empty($user_prefill['city']))    $user_prefill['city']    = $orow['city']     ?? '';
            if (empty($user_prefill['zipCode'])) $user_prefill['zipCode'] = $orow['zip_code'] ?? '';
            if (empty($user_prefill['state']))   $user_prefill['state']   = $orow['state']    ?? '';
            if (empty($user_prefill['country'])) $user_prefill['country'] = $orow['country']  ?? '';

            // Sync found values back to users table so next load is fast
            $sync = $conn->prepare(
                "UPDATE users SET phone=?, address=?, city=?, zip_code=?, state=?, country=? WHERE id=?"
            );
            $sync->bind_param(
                "ssssssi",
                $user_prefill['phone'],
                $user_prefill['address'],
                $user_prefill['city'],
                $user_prefill['zipCode'],
                $user_prefill['state'],
                $user_prefill['country'],
                $uid
            );
            $sync->execute();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - H&M</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: white;
            color: #222222;
        }

        /* HEADER */
        .hm-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .hm-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            height: 70px;
            transition: all 0.3s ease;
        }

        .hm-logo { flex-shrink: 0; transition: all 0.3s ease; }
        .hm-logo-svg { width: 55px; height: auto; display: block; transition: all 0.3s ease; }

        .hm-nav-menu {
            display: flex;
            align-items: center;
            gap: 40px;
            list-style: none;
            margin: 0 0 0 50px;
            padding: 0;
        }

        .hm-nav-menu li { margin: 0; position: relative; }

        .hm-nav-menu a {
            text-decoration: none;
            color: #707070;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 25px 0;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .hm-nav-menu a:hover { color: #000000; }

        .hm-icons { display: flex; align-items: center; gap: 34px; }

        .hm-icon-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #222222;
            font-size: 20px;
            transition: transform 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hm-icon-btn svg { display: block; }
        .hm-icon-btn:hover { transform: scale(1.1); }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background-color: #E50010;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 16px;
            text-align: center;
            line-height: 1.2;
        }

        /* SIDE PANELS */
        .side-panel {
            position: fixed;
            left: 0;
            top: 70px;
            width: 400px;
            max-width: 85vw;
            height: calc(100vh - 70px);
            background-color: #ffffff;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 999;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            padding: 40px 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .side-panel::-webkit-scrollbar { display: none; }
        .side-panel.active { transform: translateX(0); }

        .side-panel-header {
            padding: 0 30px 20px 30px;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-arrow { cursor: pointer; color: #222222; font-size: 20px; transition: transform 0.2s ease; }
        .back-arrow:hover { transform: translateX(-3px); }

        .side-panel-title {
            font-size: 24px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .side-panel-menu { list-style: none; padding: 0; margin: 0; }
        .side-panel-menu li { margin: 0; }

        .side-panel-menu a {
            display: block;
            padding: 15px 30px;
            color: #222222;
            text-decoration: none;
            font-size: 16px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .side-panel-menu a:hover {
            background-color: #f8f8f8;
            border-left-color: #E50010;
            padding-left: 35px;
        }

        .panel-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background-color: rgba(0,0,0,0.3);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .panel-overlay.active { opacity: 1; visibility: visible; }

        .kids-panel-content { padding: 0 30px; }

        .kids-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .kids-column-title {
            font-size: 18px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .kids-column-menu { list-style: none; padding: 0; margin: 0; }
        .kids-column-menu li { margin: 0; }

        .kids-column-menu a {
            display: block;
            padding: 12px 0;
            color: #222222;
            text-decoration: none;
            font-size: 15px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .kids-column-menu a:hover {
            color: #E50010;
            padding-left: 10px;
            border-left-color: #E50010;
        }

        /* CHECKOUT LAYOUT */
        .container-custom {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 40px 80px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 50px;
            text-transform: uppercase;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 60fr 40fr;
            gap: 50px;
            align-items: stretch;
        }

        .checkout-form-col {
            display: flex;
            flex-direction: column;
        }

        .checkout-form-col .form-section {
            flex: 1;
            margin-bottom: 0;
        }

        .order-summary {
            background: #f8f8f8;
            padding: 32px;
            border: 1px solid #e5e5e5;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-sizing: border-box;
        }

        /* FORM SECTIONS */
        .form-section { margin-bottom: 48px; }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            color: #222;
            margin-bottom: 24px;
        }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #d0d0d0;
            border-radius: 0;
            font-size: 15px;
            font-family: inherit;
            background-color: #fff;
            color: #222;
            transition: border-color 0.25s ease;
            appearance: none;
            -webkit-appearance: none;
        }

        input:focus, select:focus { outline: none; border-color: #000; }
        input::placeholder { color: #aaa; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .select-wrapper { position: relative; }

        .select-wrapper::after {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #555;
            pointer-events: none;
        }



        .summary-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            color: #222;
            margin-bottom: 24px;
        }

        .cart-item {
            display: flex;
            gap: 14px;
            padding: 16px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .item-image {
            width: 72px;
            height: 90px;
            object-fit: cover;
            background: #e5e5e5;
            flex-shrink: 0;
        }

        .item-details { flex: 1; min-width: 0; }

        .item-name {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
            color: #000;
        }

        .item-info { font-size: 12px; color: #888; margin-bottom: 2px; letter-spacing: 0.2px; }

        .item-price { font-size: 13px; font-weight: 700; color: #000; margin-top: 6px; }

        /* Promo */
        .promo-section { padding: 20px 0; border-bottom: 1px solid #e5e5e5; }

        .promo-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 10px;
        }

        .promo-row { display: flex; gap: 0; }

        .promo-row input {
            flex: 1;
            border-right: none;
            font-size: 13px;
            padding: 11px 14px;
        }

        .promo-row input:focus { border-color: #000; }

        .apply-btn {
            padding: 11px 20px;
            background: #000;
            color: #fff;
            border: 1px solid #000;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
            font-family: inherit;
        }

        .apply-btn:hover { background: #E50010; border-color: #E50010; }

        /* Price Breakdown */
        .price-breakdown { padding: 20px 0 0; }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 13px;
            color: #555;
            letter-spacing: 0.2px;
        }

        .price-row.discount { color: #E50010; }

        .price-row.total {
            font-size: 16px;
            font-weight: 700;
            color: #000;
            border-top: 2px solid #000;
            padding-top: 16px;
            margin-top: 4px;
            margin-bottom: 0;
        }

        /* Checkout Button */
        .checkout-btn {
            width: 100%;
            padding: 17px;
            background: #222;
            color: #fff;
            border: 1px solid #222;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 22px;
            font-family: inherit;
            border-radius: 0;
        }

        .checkout-btn:hover { background: #E50010; border-color: #E50010; color: #fff; }

        .checkout-btn.loading { opacity: 0.75; pointer-events: none; letter-spacing: 1px; }

        /* Security Note */
        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 14px;
            font-size: 11px;
            color: #999;
            letter-spacing: 0.3px;
        }

        .security-note i { color: #888; font-size: 12px; }

        /* TOAST */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #222;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-size: 14px;
            z-index: 2000;
            display: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .toast-notification.show {
            display: block;
            animation: fadeInOut 3s ease;
        }

        @keyframes fadeInOut {
            0%   { opacity: 0; transform: translateX(-50%) translateY(20px); }
            10%  { opacity: 1; transform: translateX(-50%) translateY(0); }
            90%  { opacity: 1; transform: translateX(-50%) translateY(0); }
            100% { opacity: 0; transform: translateX(-50%) translateY(20px); }
        }

        /* SUCCESS MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .modal-overlay.active { display: flex; }

        .success-modal {
            background: #fff;
            padding: 56px 44px;
            max-width: 480px;
            width: 92%;
            text-align: center;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .modal-check {
            width: 76px;
            height: 76px;
            background: #E50010;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
        }

        .modal-check svg { width: 38px; height: 38px; stroke: #fff; stroke-width: 3; fill: none; }

        .modal-title {
            font-size: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
            color: #000;
        }

        .modal-message { font-size: 15px; color: #666; line-height: 1.6; margin-bottom: 6px; }

        .order-number {
            font-size: 17px;
            font-weight: 700;
            color: #E50010;
            letter-spacing: 1px;
            margin: 18px 0 32px;
        }

        .modal-btn {
            padding: 15px 40px;
            background: #222;
            color: #fff;
            border: 1px solid #222;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .modal-btn:hover { background: #E50010; border-color: #E50010; }

        /* FOOTER */
        .hm-footer { background-color: #222222; color: #ffffff; padding: 60px 0 30px; margin-top: 0; }

        .footer-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; }

        .footer-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 60px;
            margin-bottom: 50px;
        }

        .footer-column { display: flex; flex-direction: column; }

        .footer-title {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 12px; }

        .footer-links a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover { color: #999; }

        .footer-copyright { text-align: center; padding-top: 30px; border-top: 1px solid #444; }
        .footer-copyright p { font-size: 13px; color: #999; margin: 0; line-height: 1.6; }

        /* RESPONSIVE */
        @media (max-width: 1100px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .order-summary { position: relative; top: 0; }
        }

        @media (max-width: 768px) {
            .hm-nav-menu { display: none; }
            .container-custom { padding: 30px 20px 60px; }
            .page-title { font-size: 36px; }
            .form-row { grid-template-columns: 1fr; }
            .footer-columns { grid-template-columns: 1fr; gap: 40px; }
            .footer-container { padding: 0 20px; }
        }

        @media (max-width: 480px) {
            .side-panel { width: 85vw; }
            .page-title { font-size: 30px; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="hm-header">
        <nav class="hm-navbar">
            <div class="hm-logo">
                <a href="home.php">
                    <svg class="hm-logo-svg" viewBox="0 0 200 132" xmlns="http://www.w3.org/2000/svg">
                        <path d="M94.378.062c-1.39-.335-4.266.748-7.295 1.888-2.33.877-4.749 1.788-6.65 2.113-1.389.238-2.72 1.72-3.178 2.767a1250.033 1250.033 0 0 0-18.713 45.388 476.105 476.105 0 0 0-24.188 4.794c6.503-16.72 13.092-33.208 19.519-49.08 3.162-7.81-5.162-8.547-8.392-.63l-.206.503c-4.237 10.392-11.984 29.386-20.54 51.47A516.167 516.167 0 0 0 4.483 64.68c-5.146 1.486-5.368 2.857-3.14 5.944.689.955 1.776 1.326 2.805 1.677.823.281 1.61.55 2.125 1.094.646.682 1.236 1.392 1.819 2.093 2.132 2.563 4.16 5.001 8.383 5.452-4.803 12.82-9.594 26.039-13.933 38.768-2.724 7.99 5.039 9.625 8.021 1.171 4.808-13.629 9.883-27.425 15.086-41.179 3.688-.857 11.837-2.621 20.163-4.424l.005-.001 4.377-.948c-7.706 21.094-12.772 37.117-14.68 44.914-.323 1.324.114 2.128.436 2.719.043.079.083.154.12.226 1.246 1.816 2.463 2.795 3.763 3.841 1.406 1.131 2.907 2.339 4.647 4.768.91 1.275 3.94 1.962 4.978-1.175 7.127-21.543 14.46-41.755 21.305-59.876 2.841-.622 7.956-1.856 11.09-6.527 3.483-5.194 5.414-6.474 6.699-7.325.766-.508 1.302-.863 1.8-1.805 1.673-3.163.566-6.134-5.377-5.4 0 0-2.244.16-6.384.632 2.77-7.133 5.395-13.81 7.815-19.968v-.003c3.331-8.476 6.275-15.967 8.68-22.305 1.407-3.71 1.595-6.426-.708-6.98Z" fill="#E50010"/>
                        <path d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.326-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z" fill="#E50010"/>
                        <path d="M85.55 97.56a42.278 42.278 0 0 1 1.561-1.44c3.569-3.093 6.977-.025 3.449 5.204a59.27 59.27 0 0 1-2.557 3.526c.446 1.271.844 2.365 1.16 3.176 1.825 4.678-2.966 5.851-4.51 1.976a88.444 88.444 0 0 1-.42-1.078c-2.913 2.58-6.28 4.204-9.88 3.085-5.92-1.842-7.427-10.178-1.899-16.6 2.218-2.577 3.887-4.365 5.282-5.793-.42-1.375-.76-2.528-.983-3.335-.718-2.6-1.366-5.63 1.236-8.719 4.88-5.79 16.2-.65 10.474 8.264-1.38 2.147-2.992 4.175-4.674 6.231a530.665 530.665 0 0 0 1.761 5.503Zm-7.04 1.149c-2.912 3.998-1.188 5.421.975 4.097a17.54 17.54 0 0 0 2.036-1.486 406.179 406.179 0 0 1-1.524-4.577 75.337 75.337 0 0 0-1.488 1.966Zm3.303-13.187a53.206 53.206 0 0 0 1.546-1.67c3.605-4.07-3.522-5.773-1.881.452.092.351.207.764.335 1.218Z" fill="#E50010"/>
                    </svg>
                </a>
            </div>

            <ul class="hm-nav-menu">
                <li id="ladiesMenuItem"><a href="product.php?gender=Ladies">LADIES</a></li>
                <li id="menMenuItem"><a href="product.php?gender=Men">MEN</a></li>
                <li id="kidsMenuItem"><a href="product.php?gender=Kids">KIDS</a></li>
            </ul>

            <div class="hm-icons">
                <form action="product.php" method="GET" class="search-form" style="position:relative; margin: 0; display: flex; align-items: center;">
                    <input type="text" name="search" placeholder="Search..." class="form-control form-control-sm" style="border-radius: 20px; padding-right: 10px; display: none; width: 200px; position: absolute; right: 30px; z-index: 10;">
                    <button type="button" class="hm-icon-btn" aria-label="Search" onclick="var i = this.previousElementSibling; if (i.style.display === 'none' || i.style.display === '') { i.style.display = 'block'; i.focus(); } else if (i.value.trim() !== '') { i.closest('form').submit(); } else { i.style.display = 'none'; }">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <circle cx="11" cy="11" r="7" />
                            <line x1="16.5" y1="16.5" x2="22" y2="22" />
                        </svg>
                    </button>
                </form>
                 <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="hm-icon-btn" aria-label="Account" onclick="window.location.href='profile.php'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 22c0-4 4-7 8-7s8 3 8 7" />
                        </svg>
                    </button>
                <?php else: ?>
                    <button class="hm-icon-btn" aria-label="Account" onclick="window.location.href='login.php'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 22c0-4 4-7 8-7s8 3 8 7" />
                        </svg>
                    </button>
                <?php endif; ?>
                <button class="hm-icon-btn" aria-label="Wishlist" onclick="window.location.href='wishlist.php'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
                <button class="hm-icon-btn" aria-label="Cart" onclick="window.location.href='<?= isset($_SESSION["user_id"]) ? "cart.php" : "login.php" ?>'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 7V5a4 4 0 0 1 8 0v2"/>
                        <rect x="3" y="7" width="14" height="13" rx="1"/>
                    </svg>
                    <?php $header_cart_count = array_sum(array_column($cart_items, 'quantity')); ?>
                    <span class="cart-badge" id="cartCount" <?= $header_cart_count > 0 ? '' : 'style="display: none;"' ?>><?= $header_cart_count ?></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Panel Overlay -->
    <div class="panel-overlay" id="panelOverlay"></div>

    <!-- Side Panel — Ladies -->
    <div class="side-panel" id="ladiesSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeLadiesPanelBtn"><i class="fas fa-arrow-left"></i></span>
            <h3 class="side-panel-title">Ladies</h3>
        </div>
        <ul class="side-panel-menu">
            <li><a href="ladies.php?category=1">Tops</a></li>
            <li><a href="ladies.php?category=2">Jeans</a></li>
            <li><a href="ladies.php?category=3">Dresses</a></li>
            <li><a href="ladies.php?category=4">Jackets & Coats</a></li>
            <li><a href="ladies.php?category=5">Sweatshirts & Hoodies</a></li>
            <li><a href="ladies.php?category=6">Skirts</a></li>
            <li><a href="ladies.php?category=7">Shirts</a></li>
            <li><a href="ladies.php?category=8">Shorts</a></li>
            <li><a href="ladies.php?category=9">Blazer & Waistcoats</a></li>
            <li><a href="ladies.php?category=10">Jumpsuits</a></li>
        </ul>
    </div>

    <!-- Side Panel — Men -->
    <div class="side-panel" id="menSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeMenPanelBtn"><i class="fas fa-arrow-left"></i></span>
            <h3 class="side-panel-title">Men</h3>
        </div>
        <ul class="side-panel-menu">
            <li><a href="men.php?category=11">Jackets & Coats</a></li>
            <li><a href="men.php?category=12">Hoodies & Sweatshirts</a></li>
            <li><a href="men.php?category=13">T-shirts</a></li>
            <li><a href="men.php?category=14">Polos</a></li>
            <li><a href="men.php?category=15">Shirts</a></li>
            <li><a href="men.php?category=16">Blazer & Suits</a></li>
            <li><a href="men.php?category=17">Shorts</a></li>
            <li><a href="men.php?category=18">Jeans</a></li>
            <li><a href="men.php?category=19">Sweatpants</a></li>
            <li><a href="men.php?category=20">Trousers</a></li>
        </ul>
    </div>

    <!-- Side Panel — Kids -->
    <div class="side-panel" id="kidsSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeKidsPanelBtn"><i class="fas fa-arrow-left"></i></span>
            <h3 class="side-panel-title">Kids</h3>
        </div>
        <div class="kids-panel-content">
            <div class="kids-columns">
                <div class="kids-column">
                    <h4 class="kids-column-title">GIRL</h4>
                    <ul class="kids-column-menu">
                        <li><a href="kids.php?gender=girl&category=21">Dresses</a></li>
                        <li><a href="kids.php?gender=girl&category=22">Jeans</a></li>
                        <li><a href="kids.php?gender=girl&category=23">T-shirts</a></li>
                        <li><a href="kids.php?gender=girl&category=24">Shorts</a></li>
                        <li><a href="kids.php?gender=girl&category=25">Jumpsuits & playsuits</a></li>
                    </ul>
                </div>
                <div class="kids-column">
                    <h4 class="kids-column-title">BOY</h4>
                    <ul class="kids-column-menu">
                        <li><a href="kids.php?gender=boy&category=26">Tshirts & shirts</a></li>
                        <li><a href="kids.php?gender=boy&category=27">Jeans</a></li>
                        <li><a href="kids.php?gender=boy&category=28">Shorts</a></li>
                        <li><a href="kids.php?gender=boy&category=29">Trousers</a></li>
                        <li><a href="kids.php?gender=boy&category=30">Sweaters & sweatshirts</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast-notification" id="toast"></div>

    <!-- MAIN CONTENT -->
    <div class="container-custom">
        <h1 class="page-title">CHECKOUT</h1>

        <div class="checkout-grid">

            <!-- ════ LEFT: FORM ════ -->
            <div class="checkout-form-col">

                <!-- Shipping Information -->
                <div class="form-section">
                    <h2 class="section-title">Shipping Information</h2>

                    <!-- Row 1: First Name & Last Name -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" placeholder="John" value="<?= htmlspecialchars($user_prefill['firstName']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" placeholder="Doe" value="<?= htmlspecialchars($user_prefill['lastName']) ?>" required>
                        </div>
                    </div>

                    <!-- Row 2: Email & Phone -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" placeholder="your.email@example.com" value="<?= htmlspecialchars($user_prefill['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" placeholder="+91 99999 00000" value="<?= htmlspecialchars($user_prefill['phone']) ?>" required>
                        </div>
                    </div>

                    <!-- Row 3: Address -->
                    <div class="form-group">
                        <label for="address">Street Address</label>
                        <input type="text" id="address" placeholder="123 Fashion Street" value="<?= htmlspecialchars($user_prefill['address']) ?>" required>
                    </div>

                    <!-- Row 4: City & PIN Code -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" placeholder="New York" value="<?= htmlspecialchars($user_prefill['city']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="zipCode">PIN Code</label>
                            <input type="text" id="zipCode" placeholder="360001" value="<?= htmlspecialchars($user_prefill['zipCode']) ?>" required>
                        </div>
                    </div>

                    <!-- Row 5: State & Country -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="state">State</label>
                            <div class="select-wrapper">
                                <select id="state" required>
                                    <option value="">Select State</option>
                                    <option value="gj" <?= $user_prefill['state']==='gj'?'selected':'' ?>>Gujarat</option>
                                    <option value="mh" <?= $user_prefill['state']==='mh'?'selected':'' ?>>Maharashtra</option>
                                    <option value="dl" <?= $user_prefill['state']==='dl'?'selected':'' ?>>Delhi</option>
                                    <option value="ka" <?= $user_prefill['state']==='ka'?'selected':'' ?>>Karnataka</option>
                                    <option value="tn" <?= $user_prefill['state']==='tn'?'selected':'' ?>>Tamil Nadu</option>
                                    <option value="up" <?= $user_prefill['state']==='up'?'selected':'' ?>>Uttar Pradesh</option>
                                    <option value="rj" <?= $user_prefill['state']==='rj'?'selected':'' ?>>Rajasthan</option>
                                    <option value="wb" <?= $user_prefill['state']==='wb'?'selected':'' ?>>West Bengal</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <div class="select-wrapper">
                                <select id="country" required>
                                    <option value="">Select Country</option>
                                    <option value="in" <?= $user_prefill['country']==='in'?'selected':'' ?>>India</option>
                                    <option value="us" <?= $user_prefill['country']==='us'?'selected':'' ?>>United States</option>
                                    <option value="uk" <?= $user_prefill['country']==='uk'?'selected':'' ?>>United Kingdom</option>
                                    <option value="ca" <?= $user_prefill['country']==='ca'?'selected':'' ?>>Canada</option>
                                    <option value="de" <?= $user_prefill['country']==='de'?'selected':'' ?>>Germany</option>
                                    <option value="fr" <?= $user_prefill['country']==='fr'?'selected':'' ?>>France</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- ════ END LEFT FORM ════ -->

            <!-- ════ RIGHT: ORDER SUMMARY ════ -->
            <div class="order-summary">
                <h3 class="summary-title">Order Summary</h3>

                <?php if (count($cart_items) > 0): ?>
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <?php
                        $imagePath = $item['image'] ?? 'https://via.placeholder.com/300x400?text=No+Image';
                        if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, 'uploads/') !== 0) {
                            $imagePath = 'uploads/' . ltrim($imagePath, '/');
                        }
                        $itemName = $item['title'] ?? ($item['name'] ?? 'Product');
                        ?>
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($itemName) ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($itemName) ?></div>
                            <div class="item-info">Qty: <?= htmlspecialchars($item['quantity']) ?></div>
                            <?php if (!empty($item['size'])): ?>
                            <div class="item-info">Size: <?= htmlspecialchars($item['size']) ?></div>
                            <?php endif; ?>
                            <div class="item-price">$<?= htmlspecialchars(number_format($item['price'], 2)) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="padding: 20px 0;">Your cart is empty.</p>
                <?php endif; ?>

                <!-- Promo -->
                <div class="promo-section">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <p class="promo-label">Promo Code</p>
                        <a href="#" id="viewCouponsLink" style="font-size: 11px; text-decoration: underline; color: #E50010; font-weight: 700; text-transform: uppercase;">View Coupons</a>
                    </div>
                    <div class="promo-row">
                        <input type="text" id="promoCode" placeholder="Enter code">
                        <button class="apply-btn" id="applyPromo">Apply</button>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Subtotal</span>
                        <span id="subtotal">$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="price-row discount" id="discountRow" style="display:none;">
                        <span>Discount (<span id="discountNameLabel">Code</span>)</span>
                        <span id="discountAmt">-$0.00</span>
                    </div>
                    <div class="price-row">
                        <span>Shipping</span>
                        <span id="shipping">$<?= number_format($shipping, 2) ?></span>
                    </div>
                    <div class="price-row">
                        <span>Tax</span>
                        <span id="tax">$<?= number_format($tax, 2) ?></span>
                    </div>
                    <div class="price-row total">
                        <span>Total</span>
                        <span id="total">$<?= number_format($total, 2) ?></span>
                    </div>
                </div>

                <button class="checkout-btn" id="checkoutBtn">Complete Purchase</button>

                <div class="security-note">
                    <i class="fas fa-lock"></i>
                    Secure &amp; encrypted checkout
                </div>
            </div>
            <!-- ════ END RIGHT ORDER SUMMARY ════ -->

        </div>
    </div>

    <!-- COUPON MODAL -->
    <div class="modal-overlay" id="couponModal">
        <div class="success-modal" style="text-align: left; max-width: 400px; padding: 24px; position: relative;">
            <button id="closeCouponModal" style="position: absolute; top: 16px; right: 20px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            <h2 class="modal-title" style="font-size: 18px; margin-bottom: 20px; text-align: center;">Available Coupons</h2>
            <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                <?php
                $c_query = "SELECT * FROM coupons WHERE status = 'ACTIVE' AND valid_until >= CURDATE() AND current_uses < max_uses ORDER BY created_at DESC";
                $c_res = mysqli_query($conn, $c_query);
                if ($c_res && mysqli_num_rows($c_res) > 0) {
                    while ($c_row = mysqli_fetch_assoc($c_res)) {
                        $c_id = $c_row['id'];
                        $c_code = htmlspecialchars($c_row['coupon_code']);
                        $c_val = ($c_row['discount_type'] === 'Percentage') 
                            ? number_format($c_row['discount_value'], 0) . '%' 
                            : '$' . number_format($c_row['discount_value'], 2);
                        
                        echo '
                        <div style="border: 1px dashed #ccc; border-radius: 6px; padding: 16px; margin-bottom: 12px; background: #fafafa; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; color: #E50010; font-size: 15px; margin-bottom: 4px;">' . $c_code . '</div>
                                <div style="font-size: 12px; color: #555;">Save ' . $c_val . ' off your subtotal.</div>
                                <div style="font-size: 11px; color: #888; margin-top: 4px;">Expires ' . date('M d, Y', strtotime($c_row['valid_until'])) . '</div>
                            </div>
                            <button class="apply-btn select-coupon-btn" data-code="' . $c_code . '" style="padding: 8px 12px; font-size: 10px;">Apply</button>
                        </div>';
                    }
                } else {
                    echo '<p style="text-align: center; color: #666; font-size: 13px;">No coupons available right now.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div class="modal-overlay" id="congratsModal">
        <div class="success-modal">
            <div class="modal-check">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h2 class="modal-title">Order Placed!</h2>
            <p class="modal-message">Thank you for shopping at H&amp;M.</p>
            <p class="modal-message">Your order has been successfully placed.</p>
            <p class="order-number">Order #<span id="orderNumber"></span></p>
            <button class="modal-btn" id="continueShopping">Continue Shopping</button>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="hm-footer">
        <div class="footer-container">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3 class="footer-title">SHOP</h3>
                    <ul class="footer-links">
                        <li><a href="product.php?gender=Ladies">LADIES</a></li>
                        <li><a href="product.php?gender=Men">MEN</a></li>
                        <li><a href="product.php?gender=Kids">KIDS</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">CORPORATE INFO</h3>
                    <ul class="footer-links">
                        <li><a href="about-us.php">ABOUT US</a></li>
                        <li><a href="ceo.php">CEO</a></li>
                        <li><a href="investor.php">INVESTOR</a></li>
                        <li><a href="board-of-director.php">BOARD OF DIRECTOR</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">HELP</h3>
                    <ul class="footer-links">
                        <li><a href="customer-service.php">CUSTOMER SERVICE</a></li>

                        <li><a href="contact.php">CONTACT</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-copyright">
                <p>The content of this site is copyright-protected and is the property of H &amp; M Hennes &amp; Mauritz AB.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function () {

        /* Side Panel Controls */
        function closeAllPanels() {
            $('#ladiesSidePanel, #menSidePanel, #kidsSidePanel').removeClass('active');
            $('#panelOverlay').removeClass('active');
        }

        function bindPanel(menuId, panelId) {
            const $menu  = $('#' + menuId);
            const $panel = $('#' + panelId);

            $menu.on('mouseenter', function () {
                closeAllPanels();
                $panel.addClass('active');
                $('#panelOverlay').addClass('active');
            });

            $menu.on('mouseleave', function () {
                setTimeout(function () {
                    if (!$panel.is(':hover')) {
                        $panel.removeClass('active');
                        $('#panelOverlay').removeClass('active');
                    }
                }, 100);
            });

            $panel.on('mouseenter', function () {
                $panel.addClass('active');
                $('#panelOverlay').addClass('active');
            }).on('mouseleave', function () {
                $panel.removeClass('active');
                $('#panelOverlay').removeClass('active');
            });
        }

        bindPanel('ladiesMenuItem', 'ladiesSidePanel');
        bindPanel('menMenuItem',    'menSidePanel');
        bindPanel('kidsMenuItem',   'kidsSidePanel');

        $('#closeLadiesPanelBtn, #closeMenPanelBtn, #closeKidsPanelBtn').on('click', closeAllPanels);
        $('#panelOverlay').on('click', closeAllPanels);

        /* Toast */
        function showToast(msg) {
            $('#toast').text(msg).addClass('show');
            setTimeout(function () { $('#toast').removeClass('show'); }, 3000);
        }

        /* Promo & Coupon Logic */
        $('#viewCouponsLink').on('click', function(e) {
            e.preventDefault();
            $('#couponModal').addClass('active');
        });

        $('#closeCouponModal').on('click', function() {
            $('#couponModal').removeClass('active');
        });

        // Click outside to close coupon modal
        $('#couponModal').on('click', function(e) {
            if ($(e.target).is('#couponModal')) {
                $(this).removeClass('active');
            }
        });

        // Click 'Apply' on a coupon list item
        $('.select-coupon-btn').on('click', function() {
            const code = $(this).data('code');
            $('#promoCode').val(code);
            $('#couponModal').removeClass('active');
            $('#applyPromo').trigger('click');
        });

        $('#applyPromo').on('click', function () {
            const code = $('#promoCode').val().trim().toUpperCase();
            if (!code) { showToast('Please enter a promo code'); return; }

            let subText = $('#subtotal').text().replace('$', '').replace(',', '');
            let sub = parseFloat(subText);
            if (isNaN(sub) || sub === 0) return;

            const $btn = $(this);
            const originalText = $btn.text();
            $btn.text('...').prop('disabled', true);

            fetch('apply_coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, subtotal: sub })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const discount = parseFloat(data.discount_amount);
                    const newSub = sub - discount;
                    const tax = newSub * 0.08;
                    const total = newSub + 5.99 + tax;

                    $('#discountNameLabel').text(data.coupon_code);
                    $('#discountAmt').text('-$' + discount.toFixed(2));
                    $('#discountRow').show();
                    $('#tax').text('$' + tax.toFixed(2));
                    $('#total').text('$' + total.toFixed(2));
                    showToast('Promo applied — you saved $' + discount.toFixed(2) + '!');
                    $btn.text('Applied').css('background', '#888');
                } else {
                    $btn.prop('disabled', false).text(originalText);
                    showToast(data.message);
                }
            })
            .catch(err => {
                $btn.prop('disabled', false).text(originalText);
                showToast('Error applying coupon.');
            });
        });

        /* Checkout Validation & Razorpay */
        $('#checkoutBtn').on('click', function () {
            const fields = ['firstName','lastName','email','phone','address','city','zipCode','state','country'];
            let valid = true;
            let firstBad = null;
            let formData = { 
                action: 'submit_order',
                couponCode: $('#promoCode').val().trim()
            };

            fields.forEach(function (id) {
                const $f = $('#' + id);
                formData[id] = $f.val();
                if (!$f.val() || !$f.val().trim()) {
                    $f.css('border-color', '#E50010');
                    valid = false;
                    if (!firstBad) firstBad = $f;
                } else {
                    $f.css('border-color', '#d0d0d0');
                }
            });

            if (!valid) {
                if (firstBad) {
                    $('html,body').animate({ scrollTop: firstBad.offset().top - 120 }, 400);
                    firstBad.focus();
                }
                showToast('Please fill in all required fields');
                return;
            }

            const totalAmountStr = $('#total').text().replace('$', '').replace(',', '');
            const totalAmount = Math.round(parseFloat(totalAmountStr) * 100); // Razorpay expects amount in cents

            if(isNaN(totalAmount) || totalAmount <= 0) {
                showToast('Invalid total amount.');
                return;
            }

            const options = {
                "key": "rzp_test_SbRVDzshCTWw7X",
                "secret": "5bymK8J98ED9kKF292K1MRgQ",
                "amount": totalAmount,
                "currency": "USD",
                "name": "H&M Store",
                "description": "Order Payment",
                "image": "https://upload.wikimedia.org/wikipedia/commons/5/53/H%26M-Logo.svg",
                "handler": function (response){
                    formData['razorpay_payment_id'] = response.razorpay_payment_id;
                    
                    const $btn = $('#checkoutBtn');
                    $btn.addClass('loading').text('Processing…');

                    $.post('checkout.php', formData, function(res) {
                        $btn.removeClass('loading').text('Complete Purchase');
                        try {
                            const data = JSON.parse(res);
                            if (data.success) {
                                const orderNum = 'HM' + String(data.order_id).padStart(6, '0');
                                $('#orderNumber').text(orderNum);
                                $('#congratsModal').addClass('active');
                            } else {
                                showToast(data.message || 'Error processing order.');
                            }
                        } catch(e) {
                            showToast('Server error.');
                        }
                    }).fail(function() {
                        $btn.removeClass('loading').text('Complete Purchase');
                        showToast('Network error.');
                    });
                },
                "prefill": {
                    "name": formData.firstName + " " + formData.lastName,
                    "email": formData.email,
                    "contact": formData.phone
                },
                "theme": {
                    "color": "#E50010"
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                showToast("Payment failed: " + response.error.description);
            });
            rzp.open();
        });

        /* Modal close */
        $('#continueShopping').on('click', function () {
            $('#congratsModal').removeClass('active');
            window.location.href = 'home.php';
        });

        $('#congratsModal').on('click', function (e) {
            if ($(e.target).is('#congratsModal')) $(this).removeClass('active');
        });

        /* Field border reset on input */
        $('input, select').on('input change', function () {
            if ($(this).val()) $(this).css('border-color', '#d0d0d0');
        });

    });
    </script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="autocomplete.js"></script>
</body>
</html>
