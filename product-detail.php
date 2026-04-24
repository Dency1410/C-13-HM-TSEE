<?php
session_start();
require 'includes/db.php';


$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$query = "SELECT p.*, c.name as category_name, c.gender as category_gender 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = $product_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    header("Location: product.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

$size_query = "SELECT s.name 
               FROM product_sizes ps 
               JOIN sizes s ON ps.size_id = s.id 
               WHERE ps.product_id = $product_id";
$size_result = mysqli_query($conn, $size_query);

$is_wishlisted = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wish_check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $uid AND product_id = $product_id");
    if (mysqli_num_rows($wish_check) > 0) {
        $is_wishlisted = true;
    }
}
$sizes = [];
while ($row = mysqli_fetch_assoc($size_result)) {
    $sizes[] = $row['name'];
}
$header_cart_count = 0;
$header_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if ($header_user_id !== null) {
    // Only attempt if table exists - ignore errors if it doesn't
    $res = @mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $header_user_id");
    if ($res) $header_cart_count = (int)(mysqli_fetch_assoc($res)['total'] ?? 0);
} else {
    $header_cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
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
<?php $pageTitle = htmlspecialchars($product['name']) . " - H&M"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Detail - H&M</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #ffffff;
        }

        /* HEADER NAVIGATION */
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

        .hm-logo { flex-shrink: 0; }

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

        .hm-nav-menu li { margin: 0; }

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

        /* PRODUCT DETAIL PAGE */
        .product-detail-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            display: flex;
            gap: 0;
        }

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

        .product-wishlist-icon.active { color: #E50010; }

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

        .product-price-section { margin-bottom: 25px; }

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

        /* SIZE SELECTION - H&M STYLE */
        .size-section { margin-bottom: 30px; }

        .size-label {
            font-size: 14px;
            font-weight: 600;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: block;
        }

        .size-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .size-option {
            border: 1px solid #e5e5e5;
            background-color: #ffffff;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: #222222;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .size-option:hover { border-color: #222222; }

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

        .size-guide-link:hover { color: #E50010; }

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

        .add-to-bag-btn:hover { background-color: #000000; }

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

        .reviews-link:hover { color: #E50010; }

        .star-rating {
            color: #222222;
            font-size: 14px;
        }

        .star-rating i { margin-right: 2px; }

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

        .delivery-title.active i { transform: rotate(45deg); }

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

        .delivery-links a:hover { color: #E50010; }

        /* FOOTER SECTION */
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

        .footer-links li { margin-bottom: 12px; }

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

        .footer-links a:hover { color: #999999; }

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

        /* SIZE GUIDE MODAL */
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

        .size-guide-modal.active { right: 0; }

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

        .size-guide-close:hover { transform: scale(1.1); }

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

        .size-chart-table thead tr { border-bottom: 2px solid #222222; }

        .size-chart-table th {
            padding: 15px 10px;
            font-size: 13px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }

        .size-chart-table tbody tr { border-bottom: 1px solid #e5e5e5; }

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

        /* REVIEWS MODAL */
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

        .reviews-modal.active { right: 0; }

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

        .reviews-modal-close:hover { transform: scale(1.1); }

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

        .reviews-stars i { margin: 0 2px; }

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

        .review-item:last-child { border-bottom: none; }

        .review-stars {
            font-size: 14px;
            color: #222222;
            margin-bottom: 12px;
        }

        .review-stars i { margin-right: 2px; }

        .review-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 12px;
            color: #707070;
        }

        .review-detail-item { display: inline-block; }

        /* RESPONSIVE */
        @media (max-width: 968px) {
            .product-detail-container { flex-direction: column; }
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
            .size-guide-modal { width: 100%; }
            .reviews-modal { width: 100%; }
            .footer-columns {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .footer-container { padding: 0 20px; }
        }

        @media (max-width: 480px) {
            .hm-navbar {
                padding: 0 15px;
                height: 60px;
            }
            .product-images-section { padding: 20px; }
            .product-main-image { max-width: 280px; }
            .size-grid { gap: 6px; }
            .size-option {
                width: 48px;
                height: 48px;
                font-size: 12px;
            }
            .size-guide-modal { width: 100%; }
            .reviews-modal { width: 100%; }
            .size-guide-content { padding: 20px; }
            .reviews-modal-content { padding: 20px; }
            .size-range-buttons { flex-direction: column; }
        }
    </style>
</head>

<body>
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
                <li><a href="product.php?gender=Ladies" <?= (isset($product['category_gender']) && $product['category_gender'] == 'Ladies') ? 'class="active"' : '' ?>>LADIES</a></li>
                <li><a href="product.php?gender=Men" <?= (isset($product['category_gender']) && $product['category_gender'] == 'Men') ? 'class="active"' : '' ?>>MEN</a></li>
                <li><a href="product.php?gender=Kids" <?= (isset($product['category_gender']) && in_array($product['category_gender'], ['Kids', 'Kids Girl', 'Kids Boy'])) ? 'class="active"' : '' ?>>KIDS</a></li>
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

                <button class="hm-icon-btn" aria-label="Cart" onclick="window.location.href='cart.php'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 7V5a4 4 0 0 1 8 0v2" />
                        <rect x="3" y="7" width="14" height="13" rx="1" />
                    </svg>
                    <span class="cart-badge" id="cartCount" <?= $header_cart_count > 0 ? '' : 'style="display: none;"' ?>><?= $header_cart_count ?></span>
                </button>
            </div>
        </nav>
    </header>

    <div class="product-detail-container">
        <!-- Left Side - Product Images -->
        <div class="product-images-section">
            <?php $img = $product['image'] ? $product['image'] : 'https://via.placeholder.com/500x450'; ?>
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                class="product-main-image">
        </div>

        <!-- Right Side - Product Details -->
        <div class="product-details-section" style="position: relative;">
            <button class="product-wishlist-icon <?= $is_wishlisted ? 'active' : '' ?>" id="wishlistBtn" data-product-id="<?= $product_id ?>" onclick="toggleWishlistDetail(this)">
                <i class="far fa-heart"></i>
            </button>

            <h1 class="product-detail-title"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="product-price-section">
                <span class="product-sale-price">$<?= number_format($product['price'], 2) ?></span>
                <?php if (!empty($product['old_price'])): ?>
                    <span class="product-original-price">$<?= number_format($product['old_price'], 2) ?></span>
                <?php endif; ?>
                <p class="product-tax-info">MRP inclusive of all taxes</p>
                <br>
                <p class="product-tax-info"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
            </div>

            <!-- Size Selection -->
            <div class="size-section">
                <label class="size-label">Select Size</label>
                <div class="size-grid" id="sizeGrid">
                    <?php if (empty($sizes)): ?>
                        <div class="size-option out-of-stock">No Sizes Available</div>
                    <?php else: ?>
                        <?php foreach ($sizes as $s): ?>
                            <div class="size-option" data-size="<?= htmlspecialchars($s) ?>"
                                onclick="selectSize('<?= htmlspecialchars($s) ?>')"><?= htmlspecialchars($s) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add to Bag Button -->
            <button class="add-to-bag-btn" id="addToBagBtn" >ADD TO BAG</button>
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
    
    <!-- REVIEWS MODAL -->
    <div class="reviews-modal-overlay" id="reviewsOverlay" onclick="closeReviews()"></div>
    <div class="reviews-modal" id="reviewsModal">
        <div class="reviews-modal-content">
            <button class="reviews-modal-close" onclick="closeReviews()">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="reviews-modal-title">Reviews</h2>

            <div class="reviews-overall">
                <div class="reviews-rating-number"><?= $reviews_count > 0 ? number_format($average_rating, 1) : '0.0' ?></div>
                <div class="reviews-stars">
                    <?php
                    for ($i = 0; $i < $full_stars; $i++) { echo '<i class="fas fa-star"></i>'; }
                    if ($half_star) { echo '<i class="fas fa-star-half-alt"></i>'; }
                    for ($i = 0; $i < $empty_stars; $i++) { echo '<i class="far fa-star"></i>'; }
                    ?>
                </div>
                <div style="font-size: 13px; color: #707070;"><?= $reviews_count ?> Reviews</div>
            </div>

            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p style="text-align: center; color: #707070; margin-top: 20px;">No reviews yet for this product.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item">
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <div style="font-size: 14px; color: #222222; margin-bottom: 10px; line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($rev['review_text'])) ?>
                            </div>
                            <div class="review-details">
                                <div class="review-detail-item"><?= htmlspecialchars($rev['full_name']) ?></div>
                                <div class="review-detail-item"><?= date('M j, Y', strtotime($rev['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        function openReviews() {
            document.getElementById('reviewsModal').classList.add('active');
            document.getElementById('reviewsOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeReviews() {
            document.getElementById('reviewsModal').classList.remove('active');
            document.getElementById('reviewsOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        let selectedSize = null;

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

        const PRODUCT_ID = <?= (int)$product_id ?>;

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
                body: 'product_id=' + PRODUCT_ID + '&size=' + encodeURIComponent(selectedSize)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Added to your bag!', 'success');
                    // Update header badge
                    const badge = document.getElementById('cartCount');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.style.display = data.cart_count > 0 ? 'block' : 'none';
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

        // ── Toast notification ──
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

         function toggleDelivery() {
            const deliveryContent = document.getElementById('deliveryContent');
            const deliveryTitle = document.querySelector('.delivery-title');

            deliveryContent.classList.toggle('active');
            deliveryTitle.classList.toggle('active');
        }

    </script>
<script src="autocomplete.js"></script>
<script>
function toggleWishlistDetail(btn) {
    const productId = btn.getAttribute('data-product-id');
    
    fetch('toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                btn.classList.add('active');
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.classList.remove('active');
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
        } else {
            alert(data.message);
            if (data.message.includes('login')) {
                window.location.href = 'login.php';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>
