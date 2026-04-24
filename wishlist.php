<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$wishlist_items = [];
// Fetch products only for the logged-in user
$query = "SELECT products.*, wishlist.id as wishlist_id FROM wishlist 
          INNER JOIN products ON wishlist.product_id = products.id
          WHERE wishlist.user_id = $user_id
          ORDER BY wishlist.created_at DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $product_id_loop = (int)$row['id'];
        $size_query = "SELECT s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = $product_id_loop";
        $size_result = mysqli_query($conn, $size_query);
        $sizes = [];
        if ($size_result) {
            while ($size_row = mysqli_fetch_assoc($size_result)) {
                $sizes[] = $size_row['name'];
            }
        }
        $row['available_sizes'] = $sizes;
        $wishlist_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favourites - H&M</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
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
            background-color: white;
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
            position: relative;
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
        .container-custom {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 50px;
            text-transform: uppercase;
        }

        .item-count {
            font-size: 14px;
            color: #666;
            margin-bottom: 40px;
        }

        .products-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .product-card {
            background: white;
            padding: 0;
            transition: all 0.3s ease;
            position: relative;
            width: calc(25% - 15px);
            border-radius: 0;
        }

        .product-card.removing {
            opacity: 0;
            transform: scale(0.8);
        }

        .product-image {
            width: 100%;
            height: 450px;
            object-fit: cover;
            margin-bottom: 15px;
            border-radius: 0;
            background-color: #f5f5f5;
        }

        .heart-container {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 10;
        }

        .heart-icon {
            width: 22px;
            height: 22px;
            fill: #e50010;
            cursor: pointer;
            transition: transform 0.25s ease;
        }

        .heart-icon:hover {
            transform: scale(1.15);
        }

        .tooltip {
            position: absolute;
            top: -45px;
            right: 0;
            background-color: #3a3a3a;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 10;
        }

        .tooltip::after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: 15px;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #3a3a3a;
        }

        .heart-container:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }

        .product-info {
            padding: 0 0 15px 0;
            position: relative;
        }

        .product-name {
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            color: #000;
            font-weight: 400;
            text-align: left;
            line-height: 1.4;
        }

        .product-price {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #000;
            text-align: left;
        }

        .add-button {
            width: 100%;
            padding: 16px;
            background: white;
            border: 1px solid #222;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 400;
            border-radius: 0;
            color: #222;
        }

        .add-button:hover {
            background: #222;
            color: white;
        }

        .add-button.added {
            background: #222;
            color: white;
        }

        /* Empty State */
        .empty-favourites {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .empty-favourites i {
            font-size: 60px;
            color: #e50010;
            margin-bottom: 20px;
        }
        
        .empty-favourites h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .empty-favourites p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .shop-now-btn {
            background: #222;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .shop-now-btn:hover {
            background: #e50010;
            color: white;
        }

        /* Toast Notification */
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
            0% { opacity: 0; transform: translateX(-50%) translateY(20px); }
            10% { opacity: 1; transform: translateX(-50%) translateY(0); }
            90% { opacity: 1; transform: translateX(-50%) translateY(0); }
            100% { opacity: 0; transform: translateX(-50%) translateY(20px); }
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
            font-size: 16px;
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

        /* Responsive Design */
        @media (max-width: 1200px) {
            .hm-navbar {
                padding: 0 30px;
            }
        }

        @media (max-width: 1024px) {
            .product-card {
                width: calc(33.333% - 14px);
            }
        }

        @media (max-width: 992px) {
            .hm-nav-menu { 
                gap: 25px; 
                margin-left: 20px; 
            }
            .hm-nav-menu a {
                font-size: 14px;
            }
            .hm-icons {
                gap: 25px;
            }
        }

        @media (max-width: 768px) {
            .hm-nav-menu {
                display: none;
            }
            .hm-icons {
                gap: 20px;
            }
            .product-card {
                width: calc(50% - 10px);
            }
            .product-image {
                height: 350px;
            }
            h1 {
                font-size: 36px;
            }
            .footer-columns {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .footer-container {
                padding: 0 20px;
            }
        }

        @media (max-width: 576px) {
            .product-image {
                height: 300px;
            }
            .add-button {
                padding: 14px;
                font-size: 13px;
            }
            .heart-container {
                bottom: 75px;
            }
        }

        @media (max-width: 480px) {
            .product-card {
                width: 100%;
            }
            h1 {
                font-size: 32px;
            }
            .heart-icon {
                width: 20px;
                height: 20px;
            }
            .product-image {
                height: 400px;
            }
            .side-panel {
                width: 85vw;
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
                        <path d="M94.378.062c-1.39-.335-4.266.748-7.295 1.888-2.33.877-4.749 1.788-6.65 2.113-1.389.238-2.72 1.72-3.178 2.767a1250.033 1250.033 0 0 0-18.713 45.388 476.105 476.105 0 0 0-24.188 4.794c6.503-16.72 13.092-33.208 19.519-49.08 3.162-7.81-5.162-8.547-8.392-.63l-.206.503c-4.237 10.392-11.984 29.386-20.54 51.47A516.167 516.167 0 0 0 4.483 64.68c-5.146 1.486-5.368 2.857-3.14 5.944.689.955 1.776 1.326 2.805 1.677.823.281 1.61.55 2.125 1.094.646.682 1.236 1.392 1.819 2.093 2.132 2.563 4.16 5.001 8.383 5.452-4.803 12.82-9.594 26.039-13.933 38.768-2.724 7.99 5.039 9.625 8.021 1.171 4.808-13.629 9.883-27.425 15.086-41.179 3.688-.857 11.837-2.621 20.163-4.424l.005-.001 4.377-.948c-7.706 21.094-12.772 37.117-14.68 44.914-.323 1.324.114 2.128.436 2.719.043.079.083.154.12.226 1.246 1.816 2.463 2.795 3.763 3.841 1.406 1.131 2.907 2.339 4.647 4.768.91 1.275 3.94 1.962 4.978-1.175 7.127-21.543 14.46-41.755 21.305-59.876 2.841-.622 7.956-1.856 11.09-6.527 3.483-5.194 5.414-6.474 6.699-7.325.766-.508 1.302-.863 1.8-1.805 1.673-3.163.566-6.134-5.377-5.4 0 0-2.244.16-6.384.632 2.77-7.133 5.395-13.81 7.815-19.968v-.003c3.331-8.476 6.275-15.967 8.68-22.305 1.407-3.71 1.595-6.426-.708-6.98Z" fill="#E50010" />
                        <path d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.326-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z" fill="#E50010" />
                        <path d="M85.55 97.56a42.278 42.278 0 0 1 1.561-1.44c3.569-3.093 6.977-.025 3.449 5.204a59.27 59.27 0 0 1-2.557 3.526c.446 1.271.844 2.365 1.16 3.176 1.825 4.678-2.966 5.851-4.51 1.976a88.444 88.444 0 0 1-.42-1.078c-2.913 2.58-6.28 4.204-9.88 3.085-5.92-1.842-7.427-10.178-1.899-16.6 2.218-2.577 3.887-4.365 5.282-5.793-.42-1.375-.76-2.528-.983-3.335-.718-2.6-1.366-5.63 1.236-8.719 4.88-5.79 16.2-.65 10.474 8.264-1.38 2.147-2.992 4.175-4.674 6.231a530.665 530.665 0 0 0 1.761 5.503Zm-7.04 1.149c-2.912 3.998-1.188 5.421.975 4.097a17.54 17.54 0 0 0 2.036-1.486 406.179 406.179 0 0 1-1.524-4.577 75.337 75.337 0 0 0-1.488 1.966Zm3.303-13.187a53.206 53.206 0 0 0 1.546-1.67c3.605-4.07-3.522-5.773-1.881.452.092.351.207.764.335 1.218Z" fill="#E50010" />
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
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </button>

                <button class="hm-icon-btn" aria-label="Cart" onclick="window.location.href='<?= isset($_SESSION["user_id"]) ? "cart.php" : "login.php" ?>'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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

    <!-- Toast Notification -->
    <div class="toast-notification" id="toast"></div>

    <!-- Main Content -->
    <div class="container-custom">
        <h1 class="page-title">FAVOURITES</h1>
        <p class="item-count"><span id="item-count"><?= count($wishlist_items) ?></span> ITEMS</p>

        <div class="products-grid" id="products-grid">
            <?php if (count($wishlist_items) > 0): ?>
                <?php foreach ($wishlist_items as $item): ?>
                    <!-- Product Card -->
                    <div class="product-card" data-id="<?= htmlspecialchars($item['wishlist_id']) ?>" data-product-id="<?= $item['id'] ?>">
                        <?php 
                            $imagePath = $item['image'] ? $item['image'] : 'https://via.placeholder.com/300x400?text=No+Image';
                        ?>
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-image">
                        <div class="product-info">
                            <div class="heart-container" onclick="toggleWishlistFromPage(this)">
                                <svg class="heart-icon active" viewBox="0 0 24 24" style="fill: #E50010;">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5
                                             2 5.42 4.42 3 7.5 3
                                             9.24 3 10.91 3.81 12 5.08
                                             13.09 3.81 14.76 3 16.5 3
                                             19.58 3 22 5.42 22 8.5
                                             22 12.28 18.6 15.36 13.45 20.04
                                             L12 21.35z"/>
                                </svg>
                                <div class="tooltip">Remove from Favourites</div>
                            </div>
                            <div class="product-name" style="padding-right: 35px;"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="product-price">$ <?= htmlspecialchars(number_format($item['price'], 2)) ?></div>
                            <select class="form-select size-select mb-2" style="border-radius: 0; font-size: 14px; text-transform: uppercase;">
                                <option value="" disabled selected>Select Size</option>
                                <?php if (!empty($item['available_sizes'])): ?>
                                    <?php foreach($item['available_sizes'] as $sz): ?>
                                        <option value="<?= htmlspecialchars($sz) ?>"><?= htmlspecialchars($sz) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Out of Stock</option>
                                <?php endif; ?>
                            </select>
                            <button class="add-button">ADD</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-favourites" style="width: 100%;">
                    <i class="far fa-heart"></i>
                    <h3>Your favourites list is empty</h3>
                    <p>Save your favourite items and they will appear here</p>
                    <a href="home.php" class="shop-now-btn">SHOP NOW</a>
                </div>
            <?php endif; ?>
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

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Side Panel Controls with jQuery
            const $ladiesMenuItem = $('#ladiesMenuItem');
            const $ladiesSidePanel = $('#ladiesSidePanel');
            const $panelOverlay = $('#panelOverlay');
            const $closeLadiesPanelBtn = $('#closeLadiesPanelBtn');
            const $menMenuItem = $('#menMenuItem');
            const $menSidePanel = $('#menSidePanel');
            const $closeMenPanelBtn = $('#closeMenPanelBtn');
            const $kidsMenuItem = $('#kidsMenuItem');
            const $kidsSidePanel = $('#kidsSidePanel');
            const $closeKidsPanelBtn = $('#closeKidsPanelBtn');

            // Function to close all panels
            function closeAllPanels() {
                $ladiesSidePanel.removeClass('active');
                $menSidePanel.removeClass('active');
                $kidsSidePanel.removeClass('active');
                $panelOverlay.removeClass('active');
            }

            // LADIES MENU CONTROLS
            $ladiesMenuItem.on('mouseenter', function() {
                closeAllPanels();
                $ladiesSidePanel.addClass('active');
                $panelOverlay.addClass('active');
            });

            $ladiesSidePanel.on('mouseenter', function() {
                $ladiesSidePanel.addClass('active');
                $panelOverlay.addClass('active');
            });

            $ladiesMenuItem.on('mouseleave', function(e) {
                setTimeout(() => {
                    if (!$ladiesSidePanel.is(':hover')) {
                        $ladiesSidePanel.removeClass('active');
                        $panelOverlay.removeClass('active');
                    }
                }, 100);
            });

            $ladiesSidePanel.on('mouseleave', function() {
                $ladiesSidePanel.removeClass('active');
                $panelOverlay.removeClass('active');
            });

            $closeLadiesPanelBtn.on('click', function() {
                closeAllPanels();
            });

            // MEN MENU CONTROLS
            $menMenuItem.on('mouseenter', function() {
                closeAllPanels();
                $menSidePanel.addClass('active');
                $panelOverlay.addClass('active');
            });

            $menSidePanel.on('mouseenter', function() {
                $menSidePanel.addClass('active');
                $panelOverlay.addClass('active');
            });

            $menMenuItem.on('mouseleave', function(e) {
                setTimeout(() => {
                    if (!$menSidePanel.is(':hover')) {
                        $menSidePanel.removeClass('active');
                        $panelOverlay.removeClass('active');
                    }
                }, 100);
            });

            $menSidePanel.on('mouseleave', function() {
                $menSidePanel.removeClass('active');
                $panelOverlay.removeClass('active');
            });

            $closeMenPanelBtn.on('click', function() {
                closeAllPanels();
            });

            // KIDS MENU CONTROLS
            $kidsMenuItem.on('mouseenter', function() {
                closeAllPanels();
                $kidsSidePanel.addClass('active');
                $panelOverlay.addClass('active');
            });

            $kidsSidePanel.on('mouseenter', function() {
                $kidsSidePanel.addClass('active');
                $panelOverlay.addClass('active');
            });

            $kidsMenuItem.on('mouseleave', function(e) {
                setTimeout(() => {
                    if (!$kidsSidePanel.is(':hover')) {
                        $kidsSidePanel.removeClass('active');
                        $panelOverlay.removeClass('active');
                    }
                }, 100);
            });

            $kidsSidePanel.on('mouseleave', function() {
                $kidsSidePanel.removeClass('active');
                $panelOverlay.removeClass('active');
            });

            $closeKidsPanelBtn.on('click', function() {
                closeAllPanels();
            });

            $panelOverlay.on('click', function() {
                closeAllPanels();
            });

            // Toast notification function
            function showToast(message) {
                const $toast = $('#toast');
                $toast.text(message).addClass('show');
                setTimeout(() => {
                    $toast.removeClass('show');
                }, 3000);
            }

            // Update item count function
            function updateItemCount() {
                const count = $('.product-card').length;
                $('#item-count').text(count);
                
                // Check if empty and show empty state if needed
                if (count === 0) {
                    if ($('.empty-favourites').length === 0) {
                        const emptyHtml = `
                            <div class="empty-favourites">
                                <i class="far fa-heart"></i>
                                <h3>Your favourites list is empty</h3>
                                <p>Save your favourite items and they will appear here</p>
                                <a href="home.php" class="shop-now-btn">SHOP NOW</a>
                            </div>
                        `;
                        $('.products-grid').replaceWith(emptyHtml);
                    }
                }
            }

            // Add to cart functionality - AJAX without visual refresh
            $('.add-button').on('click', function() {
                const $button = $(this);
                const $productCard = $button.closest('.product-card');
                const productName = $productCard.find('.product-name').text();
                const productId = $productCard.attr('data-product-id');
                const selectedSize = $productCard.find('.size-select').val();
                
                if (!selectedSize) {
                    showToast('Please select a size first.');
                    return;
                }
                
                $button.text('ADDING...');

                // Post to add_to_cart.php
                $.post('add_to_cart.php', {
                    action: 'add',
                    product_id: productId,
                    size: selectedSize,
                    quantity: 1
                }, function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.status === 'success' || data.success) {
                            // Add visual feedback
                            $button.addClass('added').text('ADDED');
                            showToast(productName + ' added to cart');

                            // Update header cart count badge if returned
                            const newCount = data.cart_count || data.total_items;
                            if (newCount !== undefined) {
                                $('#cartCount').text(newCount).show();
                            }

                            // Reset after 2 seconds
                            setTimeout(() => {
                                $button.removeClass('added').text('ADD');
                            }, 2000);
                        } else {
                            $button.text('ADD');
                            showToast(data.message || 'Could not add to cart.');
                        }
                    } catch(e) {
                        $button.text('ADD');
                        showToast('Error communicating with server.');
                    }
                }).fail(function() {
                    $button.text('ADD');
                    showToast('Network error, please try again.');
                });
            });

            // Heart toggle functionality with removal
            window.toggleWishlistFromPage = function(container) {
                const $productCard = $(container).closest('.product-card');
                const productId = $productCard.attr('data-product-id');
                const productName = $productCard.find('.product-name').text();
                
                fetch('toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.action === 'removed') {
                        $productCard.addClass('removing');
                        showToast(productName + ' removed from favourites');
                        
                        setTimeout(() => {
                            $productCard.remove();
                            updateItemCount();
                        }, 300);
                    } else if (data.status === 'error') {
                        showToast(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            };

            // Responsive adjustments
            function checkWindowSize() {
                if ($(window).width() <= 768) {
                    // Mobile specific adjustments if needed
                }
            }
            
            checkWindowSize();
            $(window).resize(checkWindowSize);
        });
    </script>
<script src="autocomplete.js"></script>
</body>
</html>
