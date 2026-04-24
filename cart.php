
<?php
session_start();
require 'includes/db.php';

$cart_items = [];
$cart_count = 0;

// Ensure columns exist (safe even if already there)
$check_uid = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'user_id'");
if ($check_uid && mysqli_num_rows($check_uid) === 0) {
    mysqli_query($conn, "ALTER TABLE cart ADD COLUMN user_id INT(11) DEFAULT NULL");
}
$check_size = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'size'");
if ($check_size && mysqli_num_rows($check_size) === 0) {
    mysqli_query($conn, "ALTER TABLE cart ADD COLUMN size VARCHAR(20) DEFAULT NULL");
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if ($user_id !== null) {
    $query = "SELECT p.id, p.name, p.price, p.image,
                     c.id as cart_id, c.quantity, c.size
              FROM cart c
              INNER JOIN products p ON c.product_id = p.id
              WHERE c.user_id = $user_id
              ORDER BY c.created_at DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cart_items[] = $row;
            $cart_count  += $row['quantity'];
        }
    }
} else {
    // Guest: session cart
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $entry) {
            $pid = (int)$entry['product_id'];
            $res = mysqli_query($conn, "SELECT id, name, price, image FROM products WHERE id = $pid");
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $row['cart_id']  = $pid . '_' . $entry['size'];
                $row['quantity'] = $entry['quantity'];
                $row['size']     = $entry['size'];
                $cart_items[]    = $row;
                $cart_count     += $entry['quantity'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Bag - H&M</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f9f9f9;
        }

        /* ═══════════════════════════════════
           HEADER NAVIGATION
        ═══════════════════════════════════ */
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

        /* Logo */
        .hm-logo {
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .hm-logo-svg {
            width: 55px;
            height: auto;
            display: block;
            transition: all 0.3s ease;
        }

        /* Navigation Menu - Left Side */
        .hm-nav-menu {
            display: flex;
            align-items: center;
            gap: 40px;
            list-style: none;
            margin: 0 0 0 50px;
            padding: 0;
            transition: all 0.3s ease;
        }

        .hm-nav-menu li {
            margin: 0;
        }

        .hm-nav-menu a {
            text-decoration: none;
            color: #707070;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 25px 0;
            display: inline-block;
            position: relative;
            transition: color 0.3s ease;
        }

        .hm-nav-menu a:hover {
            color: #000000;
        }

        /* Header Icons */
        .hm-icons {
            display: flex;
            align-items: center;
            gap: 34px;
        }

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

        .hm-icon-btn svg {
            display: block;
        }

        .hm-icon-btn:hover {
            transform: scale(1.1);
        }

        /* Cart Count Badge */
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

        /* ═══════════════════════════════════
           SIDE PANEL FOR NAVIGATION
        ═══════════════════════════════════ */
        .side-panel {
            position: fixed;
            left: 0;
            top: 70px;
            width: 400px;
            max-width: 85vw;
            height: calc(100vh - 70px);
            background-color: #ffffff;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 999;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            padding: 40px 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .side-panel::-webkit-scrollbar {
            display: none;
        }

        .side-panel.active {
            transform: translateX(0);
        }

        .side-panel-header {
            padding: 0 30px 20px 30px;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-arrow {
            cursor: pointer;
            color: #222222;
            font-size: 20px;
            transition: transform 0.2s ease;
        }

        .back-arrow:hover {
            transform: translateX(-3px);
        }

        .side-panel-title {
            font-size: 24px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .side-panel-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .side-panel-menu li {
            margin: 0;
        }

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
            background-color: rgba(0, 0, 0, 0.3);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .panel-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .kids-panel-content {
            padding: 0 30px;
        }

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
            padding-left: 0;
        }

        .kids-column-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .kids-column-menu li {
            margin: 0;
        }

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
            padding-left: 0;
        }

        .kids-column-menu a:hover {
            color: #E50010;
            padding-left: 10px;
            border-left-color: #E50010;
        }

        /* Main Content */
        .container-fluid {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 40px;
        }

        @media (max-width: 768px) {
            .container-fluid {
                padding: 0 20px;
            }
        }

        .page-title {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 50px;
            text-transform: uppercase;
            color: #222;
        }

        /* UPI Payment Notice - LEFT ALIGNED (NO ICON) */
        .upi-notice {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px 20px;
            margin-bottom: 30px;
            font-weight: 600;
            text-align: left;
            border-radius: 4px;
            font-size: 16px;
            color: #333;
            display: flex;
            align-items: center;
        }

        /* Product Cards */
        .product-item {
            margin-bottom: 20px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .product-item .card-body {
            padding: 25px;
        }

        .product-img-container {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-item:hover .product-img {
            transform: scale(1.05);
        }

        /* Favorite Heart Icon - WITHOUT OUTER CIRCLE */
        .favorite-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
        }

        .favorite-btn i {
            font-size: 24px;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .favorite-btn .far {
            color: rgba(255, 255, 255, 0.8);
        }

        .favorite-btn .fas {
            color: #E50010;
            animation: heartBeat 0.3s ease;
        }

        @keyframes heartBeat {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.3);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Product Info */
        .product-brand {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            color: #222;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #222;
            margin-bottom: 15px;
        }

        .product-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 6px;
        }

        .product-details strong {
            color: #444;
            font-weight: 600;
        }

        .stock-warning {
            color: #E50010;
            font-weight: 600;
            margin-left: 5px;
        }

        /* Quantity Controls */
        .quantity-controls {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-wrapper {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #333;
            transition: background-color 0.3s;
        }

        .quantity-btn:hover {
            background-color: #e9ecef;
        }

        .quantity-input {
            width: 50px;
            height: 40px;
            text-align: center;
            border: none;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            font-size: 16px;
            font-weight: 600;
            color: #222;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 14px;
            cursor: pointer;
            padding: 5px 10px;
            transition: color 0.3s;
        }

        .remove-btn:hover {
            color: #E50010;
        }

        /* Order Summary */
        .order-summary-card {
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 25px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 700;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .summary-total {
            font-size: 22px;
            font-weight: 700;
            color: #222;
            padding-top: 5px;
            margin-top: 5px;
        }

        /* Buttons */
        .checkout-btn {
            background-color: #222;
            color: white;
            border: none;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .checkout-btn:hover {
            background-color: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .login-btn {
            background-color: white;
            color: #222;
            border: 2px solid #222;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .login-btn:hover {
            background-color: #222;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Payment Methods */
        .payment-methods {
            display: flex;
            gap: 12px;
            margin: 25px 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .payment-icon {
            width: 50px;
            height: 32px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #555;
            background: white;
            padding: 5px;
            transition: all 0.3s ease;
        }

        .payment-icon:hover {
            border-color: #999;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .payment-icon img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Footer Info */
        .footer-info {
            font-size: 13px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            line-height: 1.6;
        }

        .footer-info p {
            margin-bottom: 8px;
        }

        .footer-info i {
            color: #E50010;
            margin-right: 8px;
            width: 16px;
        }

        .footer-info a {
            color: #222;
            text-decoration: underline;
        }

        .footer-info a:hover {
            color: #E50010;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .hm-navbar {
                padding: 0 30px;
                height: 65px;
            }

            .hm-logo-svg {
                width: 60px;
            }

            .hm-nav-menu {
                gap: 30px;
                margin-left: 35px;
            }

            .hm-nav-menu a {
                font-size: 13px;
                padding: 20px 0;
            }

            .hm-icons {
                gap: 20px;
            }

            .hm-icon-btn svg {
                width: 19px;
                height: 19px;
            }

            .product-img-container {
                height: 180px;
            }
        }

        @media (max-width: 768px) {
            .hm-navbar {
                padding: 0 20px;
                height: 60px;
            }

            .hm-logo-svg {
                width: 55px;
            }

            .hm-nav-menu {
                gap: 20px;
                margin-left: 25px;
            }

            .hm-nav-menu a {
                font-size: 12px;
                padding: 18px 0;
            }

            .hm-icons {
                gap: 15px;
            }

            .hm-icon-btn svg {
                width: 18px;
                height: 18px;
            }

            .container-fluid {
                padding: 0 15px;
                margin: 20px auto;
            }

            .product-item .card-body {
                padding: 20px;
            }

            .product-img-container {
                height: 160px;
            }

            .order-summary-card {
                position: static;
                margin-top: 30px;
            }
        }

        @media (max-width: 576px) {
            .product-title {
                font-size: 16px;
            }

            .product-price {
                font-size: 18px;
            }

            .quantity-btn {
                width: 35px;
                height: 35px;
            }

            .quantity-input {
                width: 45px;
                height: 35px;
            }

            .product-img-container {
                height: 200px;
            }
        }

        /* Small Mobile (320px - 480px) */
        @media (max-width: 480px) {
            .hm-navbar {
                padding: 0 12px;
                height: 55px;
            }

            .hm-logo-svg {
                width: 45px;
            }

            .hm-nav-menu {
                gap: 15px;
                margin-left: 15px;
            }

            .hm-nav-menu a {
                font-size: 10px;
                padding: 15px 0;
                letter-spacing: 0.3px;
            }

            .hm-icons {
                gap: 11px;
            }

            .hm-icon-btn svg {
                width: 16px;
                height: 16px;
            }

            .product-item .row {
                flex-direction: column;
            }

            .product-img-container {
                height: 250px;
                margin-bottom: 15px;
            }

            .quantity-controls {
                flex-wrap: wrap;
            }
        }

        /* ═══════════════════════════════════
           SEARCH OVERLAY (from your header)
        ═══════════════════════════════════ */
        .search-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 100px;
        }

        .search-overlay.active {
            display: flex;
        }

        .search-container {
            background-color: white;
            width: 90%;
            max-width: 700px;
            padding: 30px;
            border-radius: 4px;
            position: relative;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .search-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #707070;
            transition: color 0.2s ease;
        }

        .search-close:hover {
            color: #222222;
        }

        .search-input-wrapper {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #222222;
            padding-bottom: 10px;
        }

        .search-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 18px;
            padding: 10px 0;
            color: #222222;
        }

        .search-input::placeholder {
            color: #707070;
        }

        .search-submit-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #222222;
            font-size: 20px;
            padding: 5px 10px;
        }

        .search-popular {
            margin-top: 25px;
        }

        .search-popular h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #707070;
            margin-bottom: 15px;
        }

        .search-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-tag {
            padding: 8px 16px;
            background-color: #f4f4f4;
            border: none;
            border-radius: 20px;
            font-size: 13px;
            color: #222222;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .search-tag:hover {
            background-color: #e5e5e5;
        }

        /* Bootstrap Grid Enhancement */
        @media (min-width: 768px) {
            .col-md-3 {
                flex: 0 0 auto;
                width: 25%;
            }

            .col-md-9 {
                flex: 0 0 auto;
                width: 75%;
            }
        }

        @media (min-width: 992px) {
            .col-lg-8 {
                flex: 0 0 auto;
                width: 66.66666667%;
            }

            .col-lg-4 {
                flex: 0 0 auto;
                width: 33.33333333%;
            }
        }

        /* ═══════════════════════════════════
           FOOTER SECTION
        ═══════════════════════════════════ */
        .hm-footer {
            background-color: #222222;
            color: #ffffff;
            padding: 60px 0 30px 0;
            margin-top: 80px;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .footer-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 60px;
            margin-bottom: 50px;
        }

        .footer-column {
            display: flex;
            flex-direction: column;
        }

        .footer-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            color: #999999;
        }

        .footer-copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #444444;
        }

        .footer-copyright p {
            font-size: 13px;
            color: #999999;
            margin: 0;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .footer-columns {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-container {
                padding: 0 20px;
            }
        }
    </style>
</head>

<body>
    <!-- ═══════════════════════════════════
         H&M HEADER NAVIGATION
    ═══════════════════════════════════ -->
    <header class="hm-header">
        <nav class="hm-navbar">
            <div class="hm-logo">
                <a href="home.php">
                    <svg class="hm-logo-svg" viewBox="0 0 200 132" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M94.378.062c-1.39-.335-4.266.748-7.295 1.888-2.33.877-4.749 1.788-6.65 2.113-1.389.238-2.72 1.72-3.178 2.767a1250.033 1250.033 0 0 0-18.713 45.388 476.105 476.105 0 0 0-24.188 4.794c6.503-16.72 13.092-33.208 19.519-49.08 3.162-7.81-5.162-8.547-8.392-.63l-.206.503c-4.237 10.392-11.984 29.386-20.54 51.47A516.167 516.167 0 0 0 4.483 64.68c-5.146 1.486-5.368 2.857-3.14 5.944.689.955 1.776 1.326 2.805 1.677.823.281 1.61.55 2.125 1.094.646.682 1.236 1.392 1.819 2.093 2.132 2.563 4.16 5.001 8.383 5.452-4.803 12.82-9.594 26.039-13.933 38.768-2.724 7.99 5.039 9.625 8.021 1.171 4.808-13.629 9.883-27.425 15.086-41.179 3.688-.857 11.837-2.621 20.163-4.424l.005-.001 4.377-.948c-7.706 21.094-12.772 37.117-14.68 44.914-.323 1.324.114 2.128.436 2.719.043.079.083.154.12.226 1.246 1.816 2.463 2.795 3.763 3.841 1.406 1.131 2.907 2.339 4.647 4.768.91 1.275 3.94 1.962 4.978-1.175 7.127-21.543 14.46-41.755 21.305-59.876 2.841-.622 7.956-1.856 11.09-6.527 3.483-5.194 5.414-6.474 6.699-7.325.766-.508 1.302-.863 1.8-1.805 1.673-3.163.566-6.134-5.377-5.4 0 0-2.244.16-6.384.632 2.77-7.133 5.395-13.81 7.815-19.968v-.003c3.331-8.476 6.275-15.967 8.68-22.305 1.407-3.71 1.595-6.426-.708-6.98Z"
                            fill="#E50010" />
                        <path
                            d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.326-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z"
                            fill="#E50010" />
                        <path
                            d="M85.55 97.56a42.278 42.278 0 0 1 1.561-1.44c3.569-3.093 6.977-.025 3.449 5.204a59.27 59.27 0 0 1-2.557 3.526c.446 1.271.844 2.365 1.16 3.176 1.825 4.678-2.966 5.851-4.51 1.976a88.444 88.444 0 0 1-.42-1.078c-2.913 2.58-6.28 4.204-9.88 3.085-5.92-1.842-7.427-10.178-1.899-16.6 2.218-2.577 3.887-4.365 5.282-5.793-.42-1.375-.76-2.528-.983-3.335-.718-2.6-1.366-5.63 1.236-8.719 4.88-5.79 16.2-.65 10.474 8.264-1.38 2.147-2.992 4.175-4.674 6.231a530.665 530.665 0 0 0 1.761 5.503Zm-7.04 1.149c-2.912 3.998-1.188 5.421.975 4.097a17.54 17.54 0 0 0 2.036-1.486 406.179 406.179 0 0 1-1.524-4.577 75.337 75.337 0 0 0-1.488 1.966Zm3.303-13.187a53.206 53.206 0 0 0 1.546-1.67c3.605-4.07-3.522-5.773-1.881.452.092.351.207.764.335 1.218Z"
                            fill="#E50010" />
                    </svg>
                </a>
            </div>

            <ul class="hm-nav-menu">
                <li id="ladiesMenuItem">
                    <a href="product.php?gender=Ladies">LADIES</a>
                </li>
                <li id="menMenuItem">
                    <a href="product.php?gender=Men">MEN</a>
                </li>
                <li id="kidsMenuItem">
                    <a href="product.php?gender=Kids">KIDS</a>
                </li>
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
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </button>

                <button class="hm-icon-btn" aria-label="Cart" onclick="window.location.href='<?= isset($_SESSION["user_id"]) ? "cart.php" : "login.php" ?>'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 7V5a4 4 0 0 1 8 0v2" />
                        <rect x="3" y="7" width="14" height="13" rx="1" />
                    </svg>
                    <span class="cart-badge" id="cartCount" style="display: none;">0</span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Panel Overlay -->
    <div class="search-overlay" id="searchOverlay"></div>

    <!-- Side Panel for Ladies Menu -->
    <div class="side-panel" id="ladiesSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeLadiesPanelBtn">
                <i class="fas fa-arrow-left"></i>
            </span>
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

    <!-- Side Panel for Men Menu -->
    <div class="side-panel" id="menSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeMenPanelBtn">
                <i class="fas fa-arrow-left"></i>
            </span>
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

    <!-- Side Panel for Kids Menu -->
    <div class="side-panel" id="kidsSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeKidsPanelBtn">
                <i class="fas fa-arrow-left"></i>
            </span>
            <h3 class="side-panel-title">Kids</h3>
        </div>
        <div class="kids-panel-content">
            <div class="kids-columns">
                <!-- Girls Column -->
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

                <!-- Boys Column -->
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

    <!-- Shopping Bag Content -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="page-title">SHOPPING BAG</h1>
            </div>
        </div>

        <!-- UPI Payment Notice - LEFT ALIGNED WITHOUT ICON -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="upi-notice">
                    UPI PAYMENT SUPPORTS QUICKER REFUNDS
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Products Section -->
            <div class="col-lg-8 mb-4">
                <?php if (count($cart_items) > 0): ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="product-item" id="product-<?= htmlspecialchars($item['cart_id']) ?>"
                            data-price="<?= htmlspecialchars($item['price']) ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-12">
                                        <div class="product-img-container">
                                            <?php
                                            $imagePath = !empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/300x400?text=No+Image';
                                            if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, 'uploads/') !== 0) {
                                                $imagePath = 'uploads/' . ltrim($imagePath, '/');
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imagePath) ?>"
                                                alt="<?= htmlspecialchars($item['name'] ?? 'Product') ?>" class="product-img"
                                                data-product="<?= htmlspecialchars($item['cart_id']) ?>">
                                            <button class="favorite-btn"
                                                data-product="<?= htmlspecialchars($item['cart_id']) ?>">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-9 col-sm-12">
                                        <p class="product-brand">H&M</p>
                                        <h5 class="product-title"><?= htmlspecialchars($item['name'] ?? 'Product') ?></h5>
                                        <p class="product-price">$<?= number_format($item['price'] ?? 0, 2) ?></p>

                                        <div class="product-details">
                                            <?php if (!empty($item['size'])): ?>
                                            <p><strong>Size:</strong> <?= htmlspecialchars($item['size']) ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="quantity-controls">
                                            <div class="quantity-wrapper">
                                                <button class="quantity-btn" data-action="decrease"
                                                    data-product="<?= htmlspecialchars($item['cart_id']) ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="text" class="quantity-input"
                                                    value="<?= htmlspecialchars($item['quantity']) ?>" readonly
                                                    data-product="<?= htmlspecialchars($item['cart_id']) ?>">
                                                <button class="quantity-btn" data-action="increase"
                                                    data-product="<?= htmlspecialchars($item['cart_id']) ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <button class="remove-btn" data-product="<?= htmlspecialchars($item['cart_id']) ?>">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div
                        style="background: white; padding: 40px; text-align: center; border-radius: 8px; border: 1px solid #e5e5e5;">
                        <i class="fas fa-shopping-bag" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                        <h4>Your shopping bag is empty</h4>
                        <a href="home.php" class="btn btn-dark mt-3">CONTINUE SHOPPING</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Summary Section -->
            <div class="col-lg-4">
                <div class="order-summary-card">
                    <h5 class="summary-title" style="display: none;">DISCOUNTS</h5>
                    <div class="order-summary">
                        <div class="summary-row summary-total">
                            <span>TOTAL</span>
                            <span class="fw-bold" id="total-amount">$ <?php
                                $phpTotal = array_reduce($cart_items, function($carry, $item) {
                                    return $carry + ($item['price'] * $item['quantity']);
                                }, 0);
                                echo number_format($phpTotal, 2);
                            ?></span>
                        </div>

                        <button class="checkout-btn" id="checkout-btn">
                            CONTINUE TO CHECKOUT
                        </button>

                        <div class="footer-info">
                            <p><i class="fas fa-info-circle"></i> Prices and delivery costs are not confirmed until
                                you've reached the checkout.</p>
                            <p><i class="fas fa-undo-alt"></i> 15 days free returns.</p>
                            <p><i class="fas fa-headset"></i> Need help? Please contact <a
                                    href="customer-service.php">Customer Support</a>.</p>
                            <p><i class="fas fa-sms"></i> Customers would receive an SMS/WhatsApp notifications
                                regarding deliveries on the registered phone number</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════
         FOOTER SECTION
    ═══════════════════════════════════ -->
    <footer class="hm-footer">
        <div class="footer-container">
            <div class="footer-columns">
                <!-- Shop Column -->
                <div class="footer-column">
                    <h3 class="footer-title">SHOP</h3>
                    <ul class="footer-links">
                        <li><a href="product.php?gender=Ladies">LADIES</a></li>
                        <li><a href="product.php?gender=Men">MEN</a></li>
                        <li><a href="product.php?gender=Kids">KIDS</a></li>
                    </ul>
                </div>

                <!-- Corporate Info Column -->
                <div class="footer-column">
                    <h3 class="footer-title">CORPORATE INFO</h3>
                    <ul class="footer-links">
                        <li><a href="about-us.php">ABOUT US</a></li>
                        <li><a href="ceo.php">CEO</a></li>
                        <li><a href="investor.php">INVESTOR</a></li>
                        <li><a href="board-of-director.php">BOARD OF DIRECTOR</a></li>
                    </ul>
                </div>

                <!-- Help Column -->
                <div class="footer-column">
                    <h3 class="footer-title">HELP</h3>
                    <ul class="footer-links">
                        <li><a href="customer-service.php">CUSTOMER SERVICE</a></li>

                        <li><a href="contact.php">CONTACT</a></li>
                    </ul>
                </div>
            </div>

            <!-- Copyright Notice -->
            <div class="footer-copyright">
                <p>The content of this site is copyright-protected and is the property of H & M Hennes & Mauritz AB.</p>
            </div>
        </div>
    </footer>

    <script>
        $(document).ready(function () {
            const $ladiesMenuItem = $('#ladiesMenuItem');
            const $ladiesSidePanel = $('#ladiesSidePanel');
            const $panelOverlay = $('#searchOverlay');
            const $closeLadiesPanelBtn = $('#closeLadiesPanelBtn');
            const $menMenuItem = $('#menMenuItem');
            const $menSidePanel = $('#menSidePanel');
            const $closeMenPanelBtn = $('#closeMenPanelBtn');
            const $kidsMenuItem = $('#kidsMenuItem');
            const $kidsSidePanel = $('#kidsSidePanel');
            const $closeKidsPanelBtn = $('#closeKidsPanelBtn');

            // Update cart total & badge on page load
            updateCartTotal();
            updateCartBadge(<?= (int)$cart_count ?>);


            // Heart icon click handler - turn red when clicked (NO SAVING TO LOCALSTORAGE)
            $('.favorite-btn').click(function (e) {
                e.stopPropagation();
                const heartIcon = $(this).find('i');

                if (heartIcon.hasClass('far')) {
                    // Add to favorites (temporary only)
                    heartIcon.removeClass('far').addClass('fas');
                    $(this).addClass('active');
                } else {
                    // Remove from favorites (temporary only)
                    heartIcon.removeClass('fas').addClass('far');
                    $(this).removeClass('active');
                }
            });

            // Remove product button handler
            $('.remove-btn').click(function () {
                const cartId = $(this).data('product');
                const productItem = $(`#product-${cartId}`);

                $.post('update_cart.php', { action: 'remove', cart_id: cartId }, function (data) {
                    if (data.status === 'success') {
                        productItem.animate({ opacity: 0, marginLeft: '-100%' }, 300, function () {
                            $(this).remove();
                            updateCartTotal();
                            updateCartBadge(data.cart_count);
                            if ($('.product-item').length === 0) location.reload();
                        });
                    }
                }, 'json').fail(function () {
                    // Fallback: remove from DOM anyway
                    productItem.remove();
                    updateCartTotal();
                });
            });

            // Quantity controls
            $('.quantity-btn').click(function () {
                const action   = $(this).data('action');
                const cartId   = $(this).data('product');
                const input    = $(`input[data-product="${cartId}"]`);
                let quantity   = parseInt(input.val());

                $.post('update_cart.php', { action: action, cart_id: cartId }, function (data) {
                    if (data.status === 'success') {
                        if (action === 'increase') {
                            input.val(quantity + 1);
                        } else if (action === 'decrease') {
                            if (quantity <= 1) {
                                $(`#product-${cartId}`).remove();
                                if ($('.product-item').length === 0) location.reload();
                            } else {
                                input.val(quantity - 1);
                            }
                        }
                        updateCartTotal();
                        updateCartBadge(data.cart_count);
                    }
                }, 'json');
            });

            // Checkout button handler - DIRECT REDIRECT
            $('#checkout-btn').click(function () {
                // Save cart data temporarily (session only)
                const cartData = getCartData();
                sessionStorage.setItem('checkoutCart', JSON.stringify(cartData));

                // Redirect to checkout page directly
                window.location.href = 'checkout.php';
            });



            function closeAllPanels() {
                $ladiesSidePanel.removeClass('active');
                $menSidePanel.removeClass('active');
                $kidsSidePanel.removeClass('active');
            }

            // LADIES MENU CONTROLS
            $ladiesMenuItem.on('mouseenter', function () {
                closeAllPanels();
                $ladiesSidePanel.addClass('active');
            });

            $ladiesSidePanel.on('mouseenter', function () {
                $ladiesSidePanel.addClass('active');
            });

            $ladiesMenuItem.on('mouseleave', function (e) {
                setTimeout(() => {
                    if (!$ladiesSidePanel.is(':hover')) {
                        $ladiesSidePanel.removeClass('active');
                    }
                }, 100);
            });

            $ladiesSidePanel.on('mouseleave', function () {
                $ladiesSidePanel.removeClass('active');
            });

            $closeLadiesPanelBtn.on('click', function () {
                closeAllPanels();
            });

            // MEN MENU CONTROLS
            $menMenuItem.on('mouseenter', function () {
                closeAllPanels();
                $menSidePanel.addClass('active');
            });

            $menSidePanel.on('mouseenter', function () {
                $menSidePanel.addClass('active');
            });

            $menMenuItem.on('mouseleave', function (e) {
                setTimeout(() => {
                    if (!$menSidePanel.is(':hover')) {
                        $menSidePanel.removeClass('active');
                    }
                }, 100);
            });

            $menSidePanel.on('mouseleave', function () {
                $menSidePanel.removeClass('active');
            });

            $closeMenPanelBtn.on('click', function () {
                closeAllPanels();
            });

            // KIDS MENU CONTROLS
            $kidsMenuItem.on('mouseenter', function () {
                closeAllPanels();
                $kidsSidePanel.addClass('active');
            });

            $kidsSidePanel.on('mouseenter', function () {
                $kidsSidePanel.addClass('active');
            });

            $kidsMenuItem.on('mouseleave', function (e) {
                setTimeout(() => {
                    if (!$kidsSidePanel.is(':hover')) {
                        $kidsSidePanel.removeClass('active');
                    }
                }, 100);
            });

            $kidsSidePanel.on('mouseleave', function () {
                $kidsSidePanel.removeClass('active');
            });

            $closeKidsPanelBtn.on('click', function () {
                closeAllPanels();
            });

            $panelOverlay.on('click', function () {
                closeAllPanels();
            });
        });

        function updateCartTotal() {
            let total = 0;
            $('.product-item').each(function () {
                const cartId   = $(this).attr('id').replace('product-', '');
                const quantity = parseInt($(`input[data-product="${cartId}"]`).val()) || 0;
                const price    = parseFloat($(this).data('price')) || 0;
                total += price * quantity;
            });
            $('#total-amount').text('$ ' + total.toFixed(2));
        }

        function updateCartCount() {
            let itemCount = 0;
            $('.product-item').each(function () {
                const cartId   = $(this).attr('id').replace('product-', '');
                const quantity = parseInt($(`input[data-product="${cartId}"]`).val()) || 0;
                itemCount += quantity;
            });
            updateCartBadge(itemCount);
        }

        function updateCartBadge(count) {
            const badge = $('#cartCount');
            if (count > 0) {
                badge.text(count).show();
            } else {
                badge.text(0).hide();
            }
        }

        function getCartData() {
            const cartItems = [];
            $('.product-item').each(function () {
                const cartId   = $(this).attr('id').replace('product-', '');
                const title    = $(this).find('.product-title').text();
                const quantity = parseInt($(`input[data-product="${cartId}"]`).val()) || 0;
                const price    = parseFloat($(this).data('price')) || 0;
                cartItems.push({ id: cartId, title, quantity, price, total: price * quantity });
            });
            return {
                items: cartItems,
                total: parseFloat($('#total-amount').text().replace('$ ', '')) || 0
            };
        }

        // Search Overlay Functions
        function openSearch() {
            const overlay = document.getElementById('searchOverlay');
            const searchInput = document.getElementById('searchInput');
            overlay.classList.add('active');
            setTimeout(() => searchInput.focus(), 100);
        }

        function closeSearch() {
            const overlay = document.getElementById('searchOverlay');
            overlay.classList.remove('active');
            document.getElementById('searchInput').value = '';
        }

        function handleSearch(event) {
            event.preventDefault();
            const query = document.getElementById('searchInput').value;
            if (query.trim()) {
                alert('Searching for: ' + query + '\n\nIn production, this would redirect to: search.php?q=' + query);
                closeSearch();
            }
        }

        function searchFor(term) {
            document.getElementById('searchInput').value = term;
            handleSearch(new Event('submit'));
        }

        // Close search on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSearch();
            }
        });

        // Close search when clicking outside
        document.getElementById('searchOverlay').addEventListener('click', function (e) {
            if (e.target === this) {
                closeSearch();
            }
        });
        


    </script>
<script src="autocomplete.js"></script>
</body>

</html>
