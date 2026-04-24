<?php
session_start();
require_once 'includes/db.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    header("Location: index.php");
    exit;
}

// Fetch Reviews
$reviews_query = "SELECT r.*, u.full_name FROM product_reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = $product_id ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_query);
$reviews = [];
$total_rating = 0;
while ($r_row = mysqli_fetch_assoc($reviews_result)) {
    $reviews[] = $r_row;
    $total_rating += $r_row['rating'];
}
$reviews_count = count($reviews);
$average_rating = $reviews_count > 0 ? round($total_rating / $reviews_count, 1) : 0;
$full_stars = floor($average_rating);
$half_star = ($average_rating - $full_stars) >= 0.5 ? 1 : 0;
$empty_stars = 5 - $full_stars - $half_star;
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Detail - H&M</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

    <style>
        .error {
            color: #E50010;
            font-size: 13px;
            margin-top: 5px;
            display: block;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 500;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #ffffff;
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
        }

        .hm-logo {
            flex-shrink: 0;
        }

        .hm-logo-svg {
            width: 55px;
            height: auto;
            display: block;
        }

        .hm-nav-menu {
            display: flex;
            align-items: center;
            gap: 40px;
            list-style: none;
            margin: 0 0 0 50px;
            padding: 0;
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
            transition: color 0.3s ease;
        }

        .hm-nav-menu a:hover {
            color: #000000;
        }

        .hm-nav-menu a.active {
            color: #000000;
            border-bottom: 2px solid #E50010;
        }

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
        }

        .hm-icon-btn:hover {
            transform: scale(1.1);
        }

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
        }

        /* ═══════════════════════════════════
           PRODUCT DETAIL PAGE
        ═══════════════════════════════════ */
        .product-detail-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            display: flex;
            gap: 0;
        }

        /* Left Side - Product Images - 50% */
        .product-images-section {
            width: 50%;
            flex-shrink: 0;
            background-color: #f8f8f8;
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 20px;
        }

        .product-main-image {
            width: 100%;
            max-width: 500px;
            max-height: 450px;
            object-fit: cover;
            display: block;
        }

        /* Right Side - Product Details - 50% */
        .product-details-section {
            width: 50%;
            flex-shrink: 0;
            padding: 40px 60px;
            background-color: #ffffff;
            overflow-y: auto;
        }

        .product-wishlist-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #222222;
            transition: all 0.3s ease;
        }

        .product-wishlist-icon:hover {
            color: #E50010;
            transform: scale(1.1);
        }

        .product-detail-title {
            font-size: 20px;
            font-weight: 400;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            margin-top: 0;
        }

        .product-price-section {
            margin-bottom: 25px;
        }

        .product-sale-price {
            font-size: 20px;
            font-weight: 700;
            color: #E50010;
            margin-right: 12px;
        }

        .product-original-price {
            font-size: 18px;
            font-weight: 400;
            color: #999999;
            text-decoration: line-through;
        }

        .product-tax-info {
            font-size: 12px;
            color: #707070;
            margin-top: 5px;
        }

        .size-section {
            margin-bottom: 30px;
        }

        .size-label {
            font-size: 14px;
            font-weight: 600;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            display: block;
        }

        .size-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .size-option {
            border: 1px solid #e5e5e5;
            background-color: #ffffff;
            padding: 12px 0;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #222222;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .size-option:hover {
            border-color: #222222;
        }

        .size-option.selected {
            background-color: #222222;
            color: #ffffff;
            border-color: #222222;
        }

        .size-option.out-of-stock {
            color: #cccccc;
            cursor: not-allowed;
            position: relative;
        }

        .size-option.out-of-stock::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #cccccc;
            transform: rotate(-45deg);
        }

        .size-guide-link {
            font-size: 13px;
            color: #222222;
            text-decoration: underline;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
            font-weight: 400;
        }

        .size-guide-link:hover {
            color: #E50010;
        }

        .add-to-bag-btn {
            width: 100%;
            background-color: #222222;
            color: #ffffff;
            border: none;
            padding: 18px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .add-to-bag-btn:hover {
            background-color: #000000;
        }

        .add-to-bag-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .reviews-section {
            padding: 20px 0;
            border-top: 1px solid #e5e5e5;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 25px;
        }

        .reviews-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .reviews-link {
            font-size: 14px;
            color: #222222;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 400;
        }

        .reviews-link:hover {
            color: #E50010;
        }

        .star-rating {
            color: #222222;
            font-size: 14px;
        }

        .star-rating i {
            margin-right: 2px;
        }

        .rating-number {
            font-size: 14px;
            font-weight: 600;
            color: #222222;
            margin-left: 5px;
        }

        .delivery-section {
            padding: 20px 0;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 20px;
        }

        .delivery-title {
            font-size: 13px;
            font-weight: 600;
            color: #222222;
            text-transform: uppercase;
            margin-bottom: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0;
        }

        .delivery-title i {
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .delivery-title.active i {
            transform: rotate(45deg);
        }

        .delivery-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-top: 0;
        }

        .delivery-content.active {
            max-height: 500px;
            padding-top: 20px;
        }

        .delivery-time {
            font-size: 14px;
            color: #222222;
            margin-bottom: 20px;
            font-weight: 400;
        }

        .delivery-text {
            font-size: 14px;
            color: #222222;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .delivery-links {
            font-size: 14px;
            color: #222222;
            line-height: 1.6;
        }

        .delivery-links a {
            color: #222222;
            text-decoration: underline;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .delivery-links a:hover {
            color: #E50010;
        }

        /* ═══════════════════════════════════
           FOOTER SECTION
        ═══════════════════════════════════ */
        .hm-footer {
            background-color: #222222;
            color: #ffffff;
            padding: 60px 0 30px 0;
            margin-top: 0;
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
            font-size: 15px;
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

        /* ═══════════════════════════════════
           SIZE GUIDE MODAL
        ═══════════════════════════════════ */
        .size-guide-modal {
            position: fixed;
            top: 0;
            right: -100%;
            width: 500px;
            height: 100vh;
            background-color: #ffffff;
            box-shadow: -2px 0 20px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            transition: right 0.3s ease;
            overflow-y: auto;
        }

        .size-guide-modal.active {
            right: 0;
        }

        .size-guide-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .size-guide-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .size-guide-content {
            padding: 30px;
            position: relative;
        }

        .size-guide-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: #222222;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
            z-index: 10;
        }

        .size-guide-close:hover {
            transform: scale(1.1);
        }

        .size-range-title {
            font-size: 15px;
            font-weight: 600;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            margin-bottom: 25px;
        }

        .size-range-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .size-range-btn {
            flex: 1;
            padding: 12px 20px;
            background-color: #222222;
            color: #ffffff;
            border: 1px solid #222222;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .size-range-btn:not(.active) {
            background-color: #ffffff;
            color: #222222;
        }

        .size-range-btn:hover {
            background-color: #222222;
            color: #ffffff;
        }

        .size-chart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .size-chart-table thead tr {
            border-bottom: 2px solid #222222;
        }

        .size-chart-table th {
            padding: 15px 10px;
            font-size: 13px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }

        .size-chart-table tbody tr {
            border-bottom: 1px solid #e5e5e5;
        }

        .size-chart-table td {
            padding: 15px 10px;
            font-size: 13px;
            color: #222222;
            text-align: center;
        }

        .size-chart-table td:first-child {
            font-weight: 600;
            text-align: left;
        }

        /* ═══════════════════════════════════
           REVIEWS MODAL
        ═══════════════════════════════════ */
        .reviews-modal {
            position: fixed;
            top: 0;
            right: -100%;
            width: 500px;
            height: 100vh;
            background-color: #ffffff;
            box-shadow: -2px 0 20px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            transition: right 0.3s ease;
            overflow-y: auto;
        }

        .reviews-modal.active {
            right: 0;
        }

        .reviews-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .reviews-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .reviews-modal-content {
            padding: 30px;
            position: relative;
        }

        .reviews-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: #222222;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
            z-index: 10;
        }

        .reviews-modal-close:hover {
            transform: scale(1.1);
        }

        .reviews-modal-title {
            font-size: 16px;
            font-weight: 600;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 30px;
            text-align: center;
        }

        .reviews-overall {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 25px;
        }

        .reviews-rating-number {
            font-size: 32px;
            font-weight: 700;
            color: #222222;
            margin-bottom: 10px;
        }

        .reviews-stars {
            font-size: 20px;
            color: #222222;
            margin-bottom: 15px;
        }

        .reviews-stars i {
            margin: 0 2px;
        }

        .reviews-sort {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 25px;
            cursor: pointer;
        }

        .reviews-sort span {
            font-size: 13px;
            font-weight: 600;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .reviews-sort i {
            font-size: 14px;
            color: #222222;
        }

        .review-item {
            padding: 20px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-stars {
            font-size: 14px;
            color: #222222;
            margin-bottom: 12px;
        }

        .review-stars i {
            margin-right: 2px;
        }

        .review-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 12px;
            color: #707070;
        }

        .review-detail-item {
            display: inline-block;
        }

        /* ═══════════════════════════════════
           RESPONSIVE DESIGN
        ═══════════════════════════════════ */
        @media (max-width: 968px) {
            .product-detail-container {
                flex-direction: column;
            }

            .product-images-section {
                position: static;
                height: auto;
                width: 100%;
                padding: 30px 20px;
            }

            .product-main-image {
                max-width: 350px;
                margin: 0 auto;
            }

            .product-details-section {
                width: 100%;
                padding: 30px 20px;
            }

            .size-guide-modal {
                width: 100%;
            }

            .reviews-modal {
                width: 100%;
            }

            .footer-columns {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-container {
                padding: 0 20px;
            }
        }

        @media (max-width: 480px) {
            .hm-navbar {
                padding: 0 15px;
                height: 60px;
            }

            .product-images-section {
                padding: 20px;
            }

            .product-main-image {
                max-width: 280px;
            }

            .size-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .size-guide-modal {
                width: 100%;
            }

            .reviews-modal {
                width: 100%;
            }

            .size-guide-content {
                padding: 20px;
            }

            .reviews-modal-content {
                padding: 20px;
            }

            .size-range-buttons {
                flex-direction: column;
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
                            d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.316-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z"
                            fill="#E50010" />
                        <path
                            d="M85.55 97.56a42.278 42.278 0 0 1 1.561-1.44c3.569-3.093 6.977-.025 3.449 5.204a59.27 59.27 0 0 1-2.557 3.526c.446 1.271.844 2.365 1.16 3.176 1.825 4.678-2.966 5.851-4.51 1.976a88.444 88.444 0 0 1-.42-1.078c-2.913 2.58-6.28 4.204-9.88 3.085-5.92-1.842-7.427-10.178-1.899-16.6 2.218-2.577 3.887-4.365 5.282-5.793-.42-1.375-.76-2.528-.983-3.335-.718-2.6-1.366-5.63 1.236-8.719 4.88-5.79 16.2-.65 10.474 8.264-1.38 2.147-2.992 4.175-4.674 6.231a530.665 530.665 0 0 0 1.761 5.503Zm-7.04 1.149c-2.912 3.998-1.188 5.421.975 4.097a17.54 17.54 0 0 0 2.036-1.486 406.179 406.179 0 0 1-1.524-4.577 75.337 75.337 0 0 0-1.488 1.966Zm3.303-13.187a53.206 53.206 0 0 0 1.546-1.67c3.605-4.07-3.522-5.773-1.881.452.092.351.207.764.335 1.218Z"
                            fill="#E50010" />
                    </svg>
                </a>
            </div>

            <ul class="hm-nav-menu">
                <li><a href="product.php?gender=Ladies">LADIES</a></li>
                <li><a href="product.php?gender=Men" class="active">MEN</a></li>
                <li><a href="product.php?gender=Kids">KIDS</a></li>
            </ul>

            <div class="hm-icons">
                <form action="product.php" method="GET" class="search-form"
                    style="position:relative; margin: 0; display: flex; align-items: center;">
                    <input type="text" name="search" placeholder="Search..." class="form-control form-control-sm"
                        style="border-radius: 20px; padding-right: 10px; display: none; width: 200px; position: absolute; right: 30px; z-index: 10;">
                    <button type="button" class="hm-icon-btn" aria-label="Search"
                        onclick="var i = this.previousElementSibling; if (i.style.display === 'none' || i.style.display === '') { i.style.display = 'block'; i.focus(); } else if (i.value.trim() !== '') { i.closest('form').submit(); } else { i.style.display = 'none'; }">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round">
                            <circle cx="11" cy="11" r="7" />
                            <line x1="16.5" y1="16.5" x2="22" y2="22" />
                        </svg>
                    </button>
                </form>

                <?php if (isset($_SESSION['user_id'])): ?>
                       <button class="hm-icon-btn" aria-label="Account" onclick="window.location.href='profile.php'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 22c0-4 4-7 8-7s8 3 8 7" />
                    </svg>
                    </button>
                <?php else: ?>
                        <button class="hm-icon-btn" aria-label="Account" onclick="window.location.href='login.php'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round">
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
                        stroke-linecap=" round" stroke-linejoin="round">
                        <path d="M6 7V5a4 4 0 0 1 8 0v2" />
                        <rect x="3" y="7" width="14" height="13" rx="1" />
                    </svg>
                    <span class="cart-badge" id="cartCount" style="display: none;">0</span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Panel Overlay -->
    <div class="panel-overlay" id="panelOverlay"></div>

    <!-- Side Panel for Ladies Menu -->
    <div class="side-panel" id="ladiesSidePanel">
        <div class="side-panel-header">
            <span class="back-arrow" id="closeLadiesPanelBtn">
                <i class="fas fa-arrow-left"></i>
            </span>
            <h3 class="side-panel-title">Ladies</h3>
        </div>
        <ul class="side-panel-menu">
            <li><a href="product.php?gender=Ladies&category=1">Tops</a></li>
            <li><a href="product.php?gender=Ladies&category=2">Jeans</a></li>
            <li><a href="product.php?gender=Ladies&category=3">Dresses</a></li>
            <li><a href="product.php?gender=Ladies&category=4">Jackets & Coats</a></li>
            <li><a href="product.php?gender=Ladies&category=5">Sweatshirts & Hoodies</a></li>
            <li><a href="product.php?gender=Ladies&category=6">Skirts</a></li>
            <li><a href="product.php?gender=Ladies&category=7">Shirts</a></li>
            <li><a href="product.php?gender=Ladies&category=8">Shorts</a></li>
            <li><a href="product.php?gender=Ladies&category=9">Blazer & Waistcoats</a></li>
            <li><a href="product.php?gender=Ladies&category=10">Jumpsuits</a></li>
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
            <li><a href="product.php?gender=Men&category=11">Jackets & Coats</a></li>
            <li><a href="product.php?gender=Men&category=12">Hoodies & Sweatshirts</a></li>
            <li><a href="product.php?gender=Men&category=13">T-shirts</a></li>
            <li><a href="product.php?gender=Men&category=14">Polos</a></li>
            <li><a href="product.php?gender=Men&category=15">Shirts</a></li>
            <li><a href="product.php?gender=Men&category=16">Blazer & Suits</a></li>
            <li><a href="product.php?gender=Men&category=17">Shorts</a></li>
            <li><a href="product.php?gender=Men&category=18">Jeans</a></li>
            <li><a href="product.php?gender=Men&category=19">Sweatpants</a></li>
            <li><a href="product.php?gender=Men&category=20">Trousers</a></li>
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
                        <li><a href="product.php?gender=Kids&category=21">Dresses</a></li>
                        <li><a href="product.php?gender=Kids&category=22">Jeans</a></li>
                        <li><a href="product.php?gender=Kids&category=23">T-shirts</a></li>
                        <li><a href="product.php?gender=Kids&category=24">Shorts</a></li>
                        <li><a href="product.php?gender=Kids&category=25">Jumpsuits & playsuits</a></li>
                    </ul>
                </div>

                <!-- Boys Column -->
                <div class="kids-column">
                    <h4 class="kids-column-title">BOY</h4>
                    <ul class="kids-column-menu">
                        <li><a href="product.php?gender=Kids&category=26">Tshirts & shirts</a></li>
                        <li><a href="product.php?gender=Kids&category=27">Jeans</a></li>
                        <li><a href="product.php?gender=Kids&category=28">Shorts</a></li>
                        <li><a href="product.php?gender=Kids&category=29">Trousers</a></li>
                        <li><a href="product.php?gender=Kids&category=30">Sweaters & sweatshirts</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════
         PRODUCT DETAIL PAGE
    ═══════════════════════════════════ -->
    <div class="product-detail-container">
        <!-- Left Side - Product Images -->
        <div class="product-images-section" id="productImages">
            <!-- Images will be loaded here by JavaScript -->
        </div>

        <!-- Right Side - Product Details -->
        <div class="product-details-section" style="position: relative;">
            <button class="product-wishlist-icon">
                <i class="far fa-heart"></i>
            </button>

            <h1 class="product-detail-title" id="productTitle">Loading...</h1>

            <div class="product-price-section">
                <span class="product-sale-price" id="salePrice">Rs. 1,269.00</span>
                <span class="product-original-price" id="originalPrice">Rs. 1,499.00</span>
                <p class="product-tax-info">MRP inclusive of all taxes</p>
            </div>

            <!-- Size Selection -->
            <div class="size-section">
                <label class="size-label">Select Size</label>
                <div class="size-grid" id="sizeGrid">
                    <div class="size-option" data-size="XS">XS</div>
                    <div class="size-option" data-size="S">S</div>
                    <div class="size-option" data-size="M">M</div>
                    <div class="size-option" data-size="L">L</div>
                    <div class="size-option" data-size="XL">XL</div>
                    <div class="size-option" data-size="XXL">XXL</div>
                </div>
                <a class="size-guide-link" onclick="openSizeGuide()">SIZE GUIDE</a>
            </div>

            <!-- Add to Bag Button -->
            <button class="add-to-bag-btn" id="addToBagBtn">ADD</button>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <a class="reviews-link" onclick="openReviews()">REVIEWS [<?= $reviews_count ?>]</a>
                </div>
                <div class="star-rating">
                    <?php
                    for ($i = 0; $i < $full_stars; $i++) { echo '<i class="fas fa-star"></i>'; }
                    if ($half_star) { echo '<i class="fas fa-star-half-alt"></i>'; }
                    for ($i = 0; $i < $empty_stars; $i++) { echo '<i class="far fa-star"></i>'; }
                    ?>
                    <span class="rating-number"><?= $reviews_count > 0 ? number_format($average_rating, 1) : '0.0' ?></span>
                </div>
            </div>

            <!-- Delivery Section -->
            <div class="delivery-section">
                <div class="delivery-title" onclick="toggleDelivery()">
                    DELIVERY, PAYMENT AND RETURNS
                    <i class="fas fa-plus"></i>
                </div>
                <div class="delivery-content" id="deliveryContent">
                    <p class="delivery-time">Delivery Time : 2-7 days</p>
                    <p class="delivery-text">
                        For health protection and hygiene reasons, returns are unavailable for underwear, swimwear,
                        piercing jewelry, perfumes/fragrances, face masks, hair tools, hair accessories, beauty
                        products/tools and cosmetics.
                    </p>
                    <p class="delivery-links">
                        For more information, please visit our <a href="customer-service.php">Customer Service</a>
                        pages.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════
         FOOTER SECTION
    ═══════════════════════════════════ -->


    <!-- Size Guide Modal Overlay -->
    <div class="size-guide-overlay" id="sizeGuideOverlay" onclick="closeSizeGuide()"></div>

    <!-- Size Guide Modal -->
    <div class="size-guide-modal" id="sizeGuideModal">
        <div class="size-guide-content">
            <button class="size-guide-close" onclick="closeSizeGuide()">
                <i class="fas fa-times"></i>
            </button>

            <div class="size-range-title">SELECT SIZE RANGE</div>

            <div class="size-range-buttons">
                <button class="size-range-btn active" onclick="selectSizeRange('xxs-s')">XXS-S</button>
                <button class="size-range-btn" onclick="selectSizeRange('m-xl')">M-XL</button>
                <button class="size-range-btn" onclick="selectSizeRange('xxl-3xl')">XXL-3XL</button>
            </div>

            <!-- Size Chart Table -->
            <table class="size-chart-table" id="sizeChartTable">
                <thead>
                    <tr>
                        <th></th>
                        <th>XXS</th>
                        <th>XS</th>
                        <th>S</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>UK</td>
                        <td>28</td>
                        <td>30R-32R</td>
                        <td>34R-36R</td>
                    </tr>
                    <tr>
                        <td>EUR</td>
                        <td>38</td>
                        <td>40-42</td>
                        <td>44-46</td>
                    </tr>
                    <tr>
                        <td>Chest cm</td>
                        <td>74-78</td>
                        <td>78-86</td>
                        <td>86-90</td>
                    </tr>
                    <tr>
                        <td>Chest inch</td>
                        <td>29-30¾</td>
                        <td>30¾-33¾</td>
                        <td>33¾-35½</td>
                    </tr>
                    <tr>
                        <td>Waist cm</td>
                        <td>62-66</td>
                        <td>66-74</td>
                        <td>74-78</td>
                    </tr>
                    <tr>
                        <td>Waist inch</td>
                        <td>24½-26</td>
                        <td>26-29¼</td>
                        <td>29¼-30¾</td>
                    </tr>
                    <tr>
                        <td>Arm length cm</td>
                        <td>59</td>
                        <td>59</td>
                        <td>59-60</td>
                    </tr>
                    <tr>
                        <td>Arm length inch</td>
                        <td>23¼</td>
                        <td>23¼</td>
                        <td>23¼-23¾</td>
                    </tr>
                    <tr>
                        <td>Neckline cm</td>
                        <td>33</td>
                        <td>34-35</td>
                        <td>36</td>
                    </tr>
                    <tr>
                        <td>Neckline inch</td>
                        <td>13</td>
                        <td>13¼-13¾</td>
                        <td>14</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reviews Modal Overlay -->
    <div class="reviews-modal-overlay" id="reviewsModalOverlay" onclick="closeReviews()"></div>

    <!-- Reviews Modal -->
    <div class="reviews-modal" id="reviewsModal">
        <div class="reviews-modal-content">
            <button class="reviews-modal-close" onclick="closeReviews()">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="reviews-modal-title">REVIEWS</h2>

            <div class="reviews-overall">
                <div class="reviews-rating-number">4.3</div>
                <div class="reviews-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
            </div>

            <div class="reviews-sort">
                <span>SORT BY</span>
                <i class="fas fa-plus"></i>
            </div>

            <!-- Review Items -->
            <div class="review-item">
                <div class="review-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <div class="review-details">
                    <span class="review-detail-item">Light beige</span>
                    <span class="review-detail-item">XS</span>
                    <span class="review-detail-item">True to Size: Spot on</span>
                    <span class="review-detail-item">Length: Spot on</span>
                </div>
            </div>

            <div class="review-item">
                <div class="review-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <div class="review-details">
                    <span class="review-detail-item">Light beige</span>
                    <span class="review-detail-item">XS</span>
                    <span class="review-detail-item">True to Size: Spot on</span>
                    <span class="review-detail-item">Length: Spot on</span>
                </div>
            </div>

            <div class="review-item">
                <div class="review-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="far fa-star"></i>
                </div>
                <div class="review-details">
                    <span class="review-detail-item">Red</span>
                    <span class="review-detail-item">S</span>
                    <span class="review-detail-item">True to Size: Spot on</span>
                    <span class="review-detail-item">Length: Spot on</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Product Database - Men's Products from men.php
        const productsDatabase = {
            1: {
                id: 1,
                name: "Denim Jacket",
                description: "Classic denim jacket",
                salePrice: "Rs. 4,199.00",
                originalPrice: "Rs. 5,199.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1576871337622-98d48d1cf531?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: ["M"]
            },
            2: {
                id: 2,
                name: "Bomber Jacket",
                description: "Lightweight bomber style",
                salePrice: "Rs. 4,799.00",
                originalPrice: "Rs. 5,799.00",
                discount: "-17%",
                images: [
                    "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: ["XL"]
            },
            3: {
                id: 3,
                name: "Leather Jacket",
                description: "Faux leather moto jacket",
                salePrice: "Rs. 5,999.00",
                originalPrice: "Rs. 7,499.00",
                discount: "-20%",
                images: [
                    "https://images.unsplash.com/photo-1551028719-00167b16eac5?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: []
            },
            4: {
                id: 4,
                name: "Basic Hoodie",
                description: "Cotton blend pullover",
                salePrice: "Rs. 2,099.00",
                originalPrice: "Rs. 2,599.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            5: {
                id: 5,
                name: "Zip Hoodie",
                description: "Full zip hoodie",
                salePrice: "Rs. 2,399.00",
                originalPrice: "Rs. 2,899.00",
                discount: "-17%",
                images: [
                    "https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: ["L"]
            },
            6: {
                id: 6,
                name: "Oversized Sweatshirt",
                description: "Relaxed fit crewneck",
                salePrice: "Rs. 2,649.00",
                originalPrice: "Rs. 3,299.00",
                discount: "-20%",
                images: [
                    "https://images.unsplash.com/photo-1578587018452-892bacefd3f2?w=800&h=1000&fit=crop"
                ],
                sizes: ["M", "L", "XL", "XXL"],
                outOfStock: []
            },
            7: {
                id: 7,
                name: "Basic Tee",
                description: "Cotton crew neck tee",
                salePrice: "Rs. 899.00",
                originalPrice: "Rs. 1,099.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            8: {
                id: 8,
                name: "V-Neck T-Shirt",
                description: "Classic v-neck style",
                salePrice: "Rs. 1,019.00",
                originalPrice: "Rs. 1,249.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: ["M"]
            },
            9: {
                id: 9,
                name: "Graphic Tee",
                description: "Printed graphic t-shirt",
                salePrice: "Rs. 1,199.00",
                originalPrice: "Rs. 1,499.00",
                discount: "-20%",
                images: [
                    "https://images.unsplash.com/photo-1622445275576-721325763afe?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: []
            },
            10: {
                id: 10,
                name: "Pique Polo",
                description: "Classic pique polo shirt",
                salePrice: "Rs. 1,799.00",
                originalPrice: "Rs. 2,199.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1626497764746-6dc36546b388?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            11: {
                id: 11,
                name: "Slim Fit Polo",
                description: "Modern slim fit polo",
                salePrice: "Rs. 2,099.00",
                originalPrice: "Rs. 2,599.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1609709295948-17d77cb2a69b?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: ["XL"]
            },
            12: {
                id: 12,
                name: "Striped Polo",
                description: "Striped polo shirt",
                salePrice: "Rs. 1,979.00",
                originalPrice: "Rs. 2,399.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1628864851414-1311b61ec696?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: []
            },
            13: {
                id: 13,
                name: "Oxford Shirt",
                description: "Classic button-down",
                salePrice: "Rs. 2,399.00",
                originalPrice: "Rs. 2,999.00",
                discount: "-20%",
                images: [
                    "https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: ["L"]
            },
            14: {
                id: 14,
                name: "Linen Shirt",
                description: "Breathable linen shirt",
                salePrice: "Rs. 2,699.00",
                originalPrice: "Rs. 3,299.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            15: {
                id: 15,
                name: "Flannel Shirt",
                description: "Casual flannel style",
                salePrice: "Rs. 2,579.00",
                originalPrice: "Rs. 3,199.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: ["M"]
            },
            16: {
                id: 16,
                name: "Navy Blazer",
                description: "Classic navy blazer",
                salePrice: "Rs. 7,799.00",
                originalPrice: "Rs. 9,499.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1593030103066-0093718efeb9?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            17: {
                id: 17,
                name: "Suit Jacket",
                description: "Formal suit jacket",
                salePrice: "Rs. 8,999.00",
                originalPrice: "Rs. 10,999.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: ["XL"]
            },
            18: {
                id: 18,
                name: "Casual Blazer",
                description: "Unstructured blazer",
                salePrice: "Rs. 7,199.00",
                originalPrice: "Rs. 8,799.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL"],
                outOfStock: []
            },
            19: {
                id: 19,
                name: "Chino Shorts",
                description: "Classic chino shorts",
                salePrice: "Rs. 1,799.00",
                originalPrice: "Rs. 2,199.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: ["L"]
            },
            20: {
                id: 20,
                name: "Cargo Shorts",
                description: "Utility cargo style",
                salePrice: "Rs. 2,099.00",
                originalPrice: "Rs. 2,599.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1591195920158-bbce3a8c6e11?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            21: {
                id: 21,
                name: "Denim Shorts",
                description: "Casual denim shorts",
                salePrice: "Rs. 1,979.00",
                originalPrice: "Rs. 2,399.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1556821840-3a8cc9be8e8c?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: ["M"]
            },
            22: {
                id: 22,
                name: "Slim Fit Jeans",
                description: "Modern slim fit",
                salePrice: "Rs. 2,999.00",
                originalPrice: "Rs. 3,699.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1542272604-787c3835535d?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            23: {
                id: 23,
                name: "Straight Jeans",
                description: "Classic straight leg",
                salePrice: "Rs. 3,299.00",
                originalPrice: "Rs. 3,999.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1475178626620-a4d074967452?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: ["XL"]
            },
            24: {
                id: 24,
                name: "Relaxed Fit Jeans",
                description: "Comfortable relaxed fit",
                salePrice: "Rs. 3,149.00",
                originalPrice: "Rs. 3,799.00",
                discount: "-17%",
                images: [
                    "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: []
            },
            25: {
                id: 25,
                name: "Joggers",
                description: "Tapered jogger pants",
                salePrice: "Rs. 2,399.00",
                originalPrice: "Rs. 2,899.00",
                discount: "-17%",
                images: [
                    "https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: ["L"]
            },
            26: {
                id: 26,
                name: "Sweatpants",
                description: "Classic sweatpants",
                salePrice: "Rs. 2,099.00",
                originalPrice: "Rs. 2,599.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1514866726945-fd5b29c78c2e?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            27: {
                id: 27,
                name: "Track Pants",
                description: "Athletic track pants",
                salePrice: "Rs. 2,699.00",
                originalPrice: "Rs. 3,299.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1578932750294-f5075e85f44a?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL"],
                outOfStock: ["M"]
            },
            28: {
                id: 28,
                name: "Dress Pants",
                description: "Formal dress trousers",
                salePrice: "Rs. 3,599.00",
                originalPrice: "Rs. 4,399.00",
                discount: "-18%",
                images: [
                    "https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL", "XXL"],
                outOfStock: []
            },
            29: {
                id: 29,
                name: "Chinos",
                description: "Casual chino pants",
                salePrice: "Rs. 2,999.00",
                originalPrice: "Rs. 3,699.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1506629082955-511b1aa562c8?w=800&h=1000&fit=crop"
                ],
                sizes: ["S", "M", "L", "XL", "XXL"],
                outOfStock: ["XL"]
            },
            30: {
                id: 30,
                name: "Pleated Trousers",
                description: "Classic pleated style",
                salePrice: "Rs. 3,899.00",
                originalPrice: "Rs. 4,799.00",
                discount: "-19%",
                images: [
                    "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&h=1000&fit=crop"
                ],
                sizes: ["XS", "S", "M", "L", "XL"],
                outOfStock: []
            }
        };

        // Get product ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = parseInt(urlParams.get('id')) || 1;
        const product = productsDatabase[productId];

        let selectedSize = null;

        // Load product details
        function loadProductDetails() {
            if (!product) {
                window.location.href = 'men.php';
                return;
            }

            // Update product image - only show the first image
            const imagesSection = document.getElementById('productImages');
            imagesSection.innerHTML = '';

            const img = document.createElement('img');
            img.src = product.images[0];
            img.alt = product.name;
            img.className = 'product-main-image';
            imagesSection.appendChild(img);

            // Update product details
            document.getElementById('productTitle').textContent = product.name.toUpperCase();
            document.getElementById('salePrice').textContent = product.salePrice;
            document.getElementById('originalPrice').textContent = product.originalPrice;

            // Update size options
            const sizeGrid = document.getElementById('sizeGrid');
            sizeGrid.innerHTML = '';

            product.sizes.forEach(size => {
                const sizeDiv = document.createElement('div');
                sizeDiv.className = 'size-option';
                sizeDiv.setAttribute('data-size', size);
                sizeDiv.textContent = size;

                if (product.outOfStock.includes(size)) {
                    sizeDiv.classList.add('out-of-stock');
                } else {
                    sizeDiv.onclick = () => selectSize(size);
                }

                sizeGrid.appendChild(sizeDiv);
            });
        }

        // Select size
        function selectSize(size) {
            document.querySelectorAll('.size-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            const selectedOption = document.querySelector(`[data-size="${size}"]`);
            if (selectedOption && !selectedOption.classList.contains('out-of-stock')) {
                selectedOption.classList.add('selected');
                selectedSize = size;
                document.getElementById('addToBagBtn').disabled = false;
            }
        }

        // Size Guide Modal Functions
        function openSizeGuide() {
            document.getElementById('sizeGuideModal').classList.add('active');
            document.getElementById('sizeGuideOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSizeGuide() {
            document.getElementById('sizeGuideModal').classList.remove('active');
            document.getElementById('sizeGuideOverlay').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Reviews Modal Functions
        function openReviews() {
            document.getElementById('reviewsModal').classList.add('active');
            document.getElementById('reviewsModalOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeReviews() {
            document.getElementById('reviewsModal').classList.remove('active');
            document.getElementById('reviewsModalOverlay').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Toggle Delivery Section
        function toggleDelivery() {
            const deliveryContent = document.getElementById('deliveryContent');
            const deliveryTitle = document.querySelector('.delivery-title');

            deliveryContent.classList.toggle('active');
            deliveryTitle.classList.toggle('active');
        }

        function selectSizeRange(range) {
            // Remove active class from all buttons
            document.querySelectorAll('.size-range-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Add active class to clicked button
            event.target.classList.add('active');

            // Update size chart based on range
            const sizeChartTable = document.getElementById('sizeChartTable');

            if (range === 'xxs-s') {
                sizeChartTable.innerHTML = `
                    <thead>
                        <tr>
                            <th></th>
                            <th>XXS</th>
                            <th>XS</th>
                            <th>S</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>UK</td>
                            <td>28</td>
                            <td>30R-32R</td>
                            <td>34R-36R</td>
                        </tr>
                        <tr>
                            <td>EUR</td>
                            <td>38</td>
                            <td>40-42</td>
                            <td>44-46</td>
                        </tr>
                        <tr>
                            <td>Chest cm</td>
                            <td>74-78</td>
                            <td>78-86</td>
                            <td>86-90</td>
                        </tr>
                        <tr>
                            <td>Chest inch</td>
                            <td>29-30¾</td>
                            <td>30¾-33¾</td>
                            <td>33¾-35½</td>
                        </tr>
                        <tr>
                            <td>Waist cm</td>
                            <td>62-66</td>
                            <td>66-74</td>
                            <td>74-78</td>
                        </tr>
                        <tr>
                            <td>Waist inch</td>
                            <td>24½-26</td>
                            <td>26-29¼</td>
                            <td>29¼-30¾</td>
                        </tr>
                        <tr>
                            <td>Arm length cm</td>
                            <td>59</td>
                            <td>59</td>
                            <td>59-60</td>
                        </tr>
                        <tr>
                            <td>Arm length inch</td>
                            <td>23¼</td>
                            <td>23¼</td>
                            <td>23¼-23¾</td>
                        </tr>
                        <tr>
                            <td>Neckline cm</td>
                            <td>33</td>
                            <td>34-35</td>
                            <td>36</td>
                        </tr>
                        <tr>
                            <td>Neckline inch</td>
                            <td>13</td>
                            <td>13¼-13¾</td>
                            <td>14</td>
                        </tr>
                    </tbody>
                `;
            } else if (range === 'm-xl') {
                sizeChartTable.innerHTML = `
                    <thead>
                        <tr>
                            <th></th>
                            <th>M</th>
                            <th>L</th>
                            <th>XL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>UK</td>
                            <td>38R-40R</td>
                            <td>42R-44R</td>
                            <td>46R-48R</td>
                        </tr>
                        <tr>
                            <td>EUR</td>
                            <td>48-50</td>
                            <td>52-54</td>
                            <td>56-58</td>
                        </tr>
                        <tr>
                            <td>Chest cm</td>
                            <td>90-98</td>
                            <td>98-106</td>
                            <td>106-114</td>
                        </tr>
                        <tr>
                            <td>Chest inch</td>
                            <td>35½-38½</td>
                            <td>38½-41¾</td>
                            <td>41¾-44¾</td>
                        </tr>
                        <tr>
                            <td>Waist cm</td>
                            <td>78-86</td>
                            <td>86-94</td>
                            <td>94-102</td>
                        </tr>
                        <tr>
                            <td>Waist inch</td>
                            <td>30¾-33¾</td>
                            <td>33¾-37</td>
                            <td>37-40¼</td>
                        </tr>
                        <tr>
                            <td>Arm length cm</td>
                            <td>60</td>
                            <td>61</td>
                            <td>62</td>
                        </tr>
                        <tr>
                            <td>Arm length inch</td>
                            <td>23¾</td>
                            <td>24</td>
                            <td>24½</td>
                        </tr>
                        <tr>
                            <td>Neckline cm</td>
                            <td>37-38</td>
                            <td>39-40</td>
                            <td>41-42</td>
                        </tr>
                        <tr>
                            <td>Neckline inch</td>
                            <td>14½-15</td>
                            <td>15¼-15¾</td>
                            <td>16-16½</td>
                        </tr>
                    </tbody>
                `;
            } else if (range === 'xxl-3xl') {
                sizeChartTable.innerHTML = `
                    <thead>
                        <tr>
                            <th></th>
                            <th>XXL</th>
                            <th>3XL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>UK</td>
                            <td>50R-52R</td>
                            <td>54R-56R</td>
                        </tr>
                        <tr>
                            <td>EUR</td>
                            <td>60-62</td>
                            <td>64-66</td>
                        </tr>
                        <tr>
                            <td>Chest cm</td>
                            <td>114-122</td>
                            <td>122-130</td>
                        </tr>
                        <tr>
                            <td>Chest inch</td>
                            <td>44¾-48</td>
                            <td>48-51¼</td>
                        </tr>
                        <tr>
                            <td>Waist cm</td>
                            <td>102-110</td>
                            <td>110-118</td>
                        </tr>
                        <tr>
                            <td>Waist inch</td>
                            <td>40¼-43¼</td>
                            <td>43¼-46½</td>
                        </tr>
                        <tr>
                            <td>Arm length cm</td>
                            <td>63</td>
                            <td>64</td>
                        </tr>
                        <tr>
                            <td>Arm length inch</td>
                            <td>24¾</td>
                            <td>25¼</td>
                        </tr>
                        <tr>
                            <td>Neckline cm</td>
                            <td>43-44</td>
                            <td>45-46</td>
                        </tr>
                        <tr>
                            <td>Neckline inch</td>
                            <td>17-17¼</td>
                            <td>17¾-18</td>
                        </tr>
                    </tbody>
                `;
            }
        }

        // Initialize page
        loadProductDetails();

        // ADD TO BAG LOGIC
        document.getElementById('addToBagBtn').addEventListener('click', function () {
            if (!selectedSize) {
                showToast('Please select a size first', 'error');
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'ADDING…';

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId + '&size=' + encodeURIComponent(selectedSize)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Added to your bag!', 'success');
                    // Update header badge
                    const badge = document.getElementById('cartCount');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                } else {
                    showToast(data.message || 'Something went wrong', 'error');
                }
            })
            .catch(() => showToast('Network error. Please try again.', 'error'))
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'ADD TO BAG';
            });
        });

        // Toast notification
        function showToast(msg, type) {
            let toast = document.getElementById('hm-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'hm-toast';
                toast.style.cssText = [
                    'position:fixed','bottom:30px','left:50%','transform:translateX(-50%) translateY(20px)',
                    'padding:14px 28px','border-radius:4px','font-size:14px','font-weight:600',
                    'letter-spacing:0.5px','color:#fff','z-index:9999','opacity:0',
                    'transition:all 0.35s ease','pointer-events:none','text-transform:uppercase'
                ].join(';');
                document.body.appendChild(toast);
            }
            toast.textContent = msg;
            toast.style.backgroundColor = type === 'success' ? '#222222' : '#E50010';
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(-50%) translateY(0)';
            });
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(20px)';
            }, 2800);
        }
    </script>

    <script src="autocomplete.js"></script>
</body>

</html>