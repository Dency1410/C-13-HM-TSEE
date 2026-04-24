<?php
session_start();
require 'includes/db.php';

// Get current filters from URL
$gender_param = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : 'all';
$category_slug = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'all';

// Map gender parameter to database values
$gender_query = "";
if ($gender_param === 'girl') {
    $gender_query = "p.gender = 'Kids Girl'";
} elseif ($gender_param === 'boy') {
    $gender_query = "p.gender = 'Kids Boy'";
} else {
    $gender_query = "(p.gender = 'Kids Girl' OR p.gender = 'Kids Boy')";
}

// Fetch categories for hover menus
$all_categories = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Kids' ORDER BY name ASC");
$categories_list = [];
while ($cat_row = mysqli_fetch_assoc($all_categories)) {
    $categories_list[] = $cat_row;
}
$men_categories_query = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Men' ORDER BY name ASC");
$men_categories = [];
while ($cat = mysqli_fetch_assoc($men_categories_query)) {
    $men_categories[] = $cat;
}
$ladies_categories_query = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Ladies' ORDER BY name ASC");
$ladies_categories = [];
while ($cat = mysqli_fetch_assoc($ladies_categories_query)) {
    $ladies_categories[] = $cat;
}

// Build products query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $gender_query";

if ($category_slug !== 'all') {
    $cat_id = (int)$category_slug;
    $query .= " AND c.id = $cat_id";
}

$query .= " ORDER BY p.created_at DESC";
$wishlisted_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wish_res = mysqli_query($conn, "SELECT product_id FROM wishlist WHERE user_id = $uid");
    while ($wrow = mysqli_fetch_assoc($wish_res)) {
        $wishlisted_ids[] = $wrow['product_id'];
    }
}
$products_result = mysqli_query($conn, $query);
$product_count = mysqli_num_rows($products_result);

// Category Title Mapping
$category_title = "All Kids Products";
$cat_name = "All Products";
if ($category_slug !== 'all') {
    foreach($categories_list as $cat) {
        if ($cat['id'] == (int)$category_slug) {
            $cat_name = $cat['name'];
            break;
        }
    }
}
if ($gender_param === 'girl') {
    $category_title = "Girls - " . $cat_name;
} elseif ($gender_param === 'boy') {
    $category_title = "Boys - " . $cat_name;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids - H&M</title>

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

        /* ═══════════════════════════════════
           KIDS PAGE MAIN CONTENT
        ═══════════════════════════════════ */
        .kids-page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            display: flex;
            gap: 40px;
        }

        /* Category Sidebar */
        .category-sidebar {
            width: 280px;
            flex-shrink: 0;
            position: sticky;
            top: 110px;
            height: fit-content;
            max-height: calc(100vh - 140px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .category-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .category-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .category-sidebar::-webkit-scrollbar-thumb {
            background: #cccccc;
            border-radius: 10px;
        }

        .category-sidebar::-webkit-scrollbar-thumb:hover {
            background: #E50010;
        }

        .category-section {
            margin-bottom: 35px;
        }

        .category-section-title {
            font-size: 20px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #E50010;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list li {
            margin-bottom: 5px;
        }

        .category-list a {
            display: block;
            padding: 12px 15px;
            color: #555555;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-radius: 4px;
            border-left: 3px solid transparent;
        }

        .category-list a:hover {
            background-color: #f8f8f8;
            color: #E50010;
            padding-left: 20px;
            border-left-color: #E50010;
        }

        .category-list a.active {
            background-color: #fff5f5;
            color: #E50010;
            font-weight: 700;
            border-left-color: #E50010;
            padding-left: 20px;
        }

        /* Products Section */
        .products-section {
            flex: 1;
        }

        .products-header {
            margin-bottom: 30px;
        }

        .products-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .products-count {
            font-size: 14px;
            color: #707070;
            font-weight: 400;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 50px;
        }

        .product-card {
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image-wrapper {
            position: relative;
            overflow: hidden;
            background-color: #f8f8f8;
            aspect-ratio: 3/4;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.08);
        }

        .product-wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .product-card:hover .product-wishlist-btn {
            opacity: 1;
        }

        .product-wishlist-btn:hover {
            background-color: #E50010;
            color: white;
            transform: scale(1.1);
        }

        .product-info {
            padding: 0 5px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #222222;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .product-description {
            font-size: 14px;
            color: #707070;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #222222;
        }

        .no-products {
            text-align: center;
            padding: 80px 20px;
            color: #707070;
        }

        .no-products i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #e5e5e5;
        }

        .no-products h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #222222;
        }

        .no-products p {
            font-size: 16px;
            color: #707070;
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
           RESPONSIVE DESIGN
        ═══════════════════════════════════ */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .kids-page-container {
                flex-direction: column;
                padding: 20px;
            }

            .category-sidebar {
                width: 100%;
                position: static;
                margin-bottom: 30px;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .side-panel {
                width: 70vw;
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

            .hm-nav-menu {
                gap: 18px;
                margin-left: 20px;
            }

            .side-panel {
                width: 85vw;
            }

            .products-header h1 {
                font-size: 24px;
            }
        }
    .product-wishlist-btn.active { color: #E50010; opacity: 1; }
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
                        <path d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.316-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z" fill="#E50010" />
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
                    <a href="product.php?gender=Kids" class="active">KIDS</a>
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
<?php
session_start();
require 'includes/db.php';

// Get current filters from URL
$gender_param = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : 'all';
$category_slug = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'all';

// Map gender parameter to database values
$gender_query = "";
if ($gender_param === 'girl') {
    $gender_query = "p.gender = 'Kids Girl'";
} elseif ($gender_param === 'boy') {
    $gender_query = "p.gender = 'Kids Boy'";
} else {
    $gender_query = "(p.gender = 'Kids Girl' OR p.gender = 'Kids Boy')";
}

// Fetch categories for hover menus
$all_categories = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Kids' ORDER BY name ASC");
$categories_list = [];
while ($cat_row = mysqli_fetch_assoc($all_categories)) {
    $categories_list[] = $cat_row;
}
$men_categories_query = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Men' ORDER BY name ASC");
$men_categories = [];
while ($cat = mysqli_fetch_assoc($men_categories_query)) {
    $men_categories[] = $cat;
}
$ladies_categories_query = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Ladies' ORDER BY name ASC");
$ladies_categories = [];
while ($cat = mysqli_fetch_assoc($ladies_categories_query)) {
    $ladies_categories[] = $cat;
}

// Build products query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $gender_query";

if ($category_slug !== 'all') {
    $cat_id = (int)$category_slug;
    $query .= " AND c.id = $cat_id";
}

$query .= " ORDER BY p.created_at DESC";
$wishlisted_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wish_res = mysqli_query($conn, "SELECT product_id FROM wishlist WHERE user_id = $uid");
    while ($wrow = mysqli_fetch_assoc($wish_res)) {
        $wishlisted_ids[] = $wrow['product_id'];
    }
}
$products_result = mysqli_query($conn, $query);
$product_count = mysqli_num_rows($products_result);

// Category Title Mapping
$category_title = "All Kids Products";
$cat_name = "All Products";
if ($category_slug !== 'all') {
    foreach($categories_list as $cat) {
        if ($cat['id'] == (int)$category_slug) {
            $cat_name = $cat['name'];
            break;
        }
    }
}
if ($gender_param === 'girl') {
    $category_title = "Girls - " . $cat_name;
} elseif ($gender_param === 'boy') {
    $category_title = "Boys - " . $cat_name;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids - H&M</title>

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

        /* ═══════════════════════════════════
           KIDS PAGE MAIN CONTENT
        ═══════════════════════════════════ */
        .kids-page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            display: flex;
            gap: 40px;
        }

        /* Category Sidebar */
        .category-sidebar {
            width: 280px;
            flex-shrink: 0;
            position: sticky;
            top: 110px;
            height: fit-content;
            max-height: calc(100vh - 140px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .category-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .category-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .category-sidebar::-webkit-scrollbar-thumb {
            background: #cccccc;
            border-radius: 10px;
        }

        .category-sidebar::-webkit-scrollbar-thumb:hover {
            background: #E50010;
        }

        .category-section {
            margin-bottom: 35px;
        }

        .category-section-title {
            font-size: 20px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #E50010;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list li {
            margin-bottom: 5px;
        }

        .category-list a {
            display: block;
            padding: 12px 15px;
            color: #555555;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-radius: 4px;
            border-left: 3px solid transparent;
        }

        .category-list a:hover {
            background-color: #f8f8f8;
            color: #E50010;
            padding-left: 20px;
            border-left-color: #E50010;
        }

        .category-list a.active {
            background-color: #fff5f5;
            color: #E50010;
            font-weight: 700;
            border-left-color: #E50010;
            padding-left: 20px;
        }

        /* Products Section */
        .products-section {
            flex: 1;
        }

        .products-header {
            margin-bottom: 30px;
        }

        .products-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .products-count {
            font-size: 14px;
            color: #707070;
            font-weight: 400;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 50px;
        }

        .product-card {
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image-wrapper {
            position: relative;
            overflow: hidden;
            background-color: #f8f8f8;
            aspect-ratio: 3/4;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.08);
        }

        .product-wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .product-card:hover .product-wishlist-btn {
            opacity: 1;
        }

        .product-wishlist-btn:hover {
            background-color: #E50010;
            color: white;
            transform: scale(1.1);
        }

        .product-info {
            padding: 0 5px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #222222;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .product-description {
            font-size: 14px;
            color: #707070;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #222222;
        }

        .no-products {
            text-align: center;
            padding: 80px 20px;
            color: #707070;
        }

        .no-products i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #e5e5e5;
        }

        .no-products h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #222222;
        }

        .no-products p {
            font-size: 16px;
            color: #707070;
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
           RESPONSIVE DESIGN
        ═══════════════════════════════════ */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .kids-page-container {
                flex-direction: column;
                padding: 20px;
            }

            .category-sidebar {
                width: 100%;
                position: static;
                margin-bottom: 30px;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .side-panel {
                width: 70vw;
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

            .hm-nav-menu {
                gap: 18px;
                margin-left: 20px;
            }

            .side-panel {
                width: 85vw;
            }

            .products-header h1 {
                font-size: 24px;
            }
        }
    .product-wishlist-btn.active { color: #E50010; opacity: 1; }
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
                        <path d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.316-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z" fill="#E50010" />
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
                    <a href="product.php?gender=Kids" class="active">KIDS</a>
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
         KIDS PAGE MAIN CONTENT
    ═══════════════════════════════════ -->
    <div class="kids-page-container">
        <!-- Category Sidebar -->
        <aside class="category-sidebar">
            <!-- Girls Section -->
            <div class="category-section">
                <h2 class="category-section-title">Girls</h2>
                <ul class="category-list">
                    <li><a href="kids.php?gender=girl" class="<?= ($gender_param === 'girl' && $category_slug === 'all') ? 'active' : '' ?>">All Products</a></li>
                    <?php foreach($categories_list as $cat): ?>
                        <li><a href="kids.php?gender=girl&category=<?= urlencode($cat['slug']) ?>" class="<?= ($gender_param === 'girl' && $category_slug === $cat['slug']) ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Boys Section -->
            <div class="category-section">
                <h2 class="category-section-title">Boys</h2>
                <ul class="category-list">
                    <li><a href="kids.php?gender=boy" class="<?= ($gender_param === 'boy' && $category_slug === 'all') ? 'active' : '' ?>">All Products</a></li>
                    <?php foreach($categories_list as $cat): ?>
                        <li><a href="kids.php?gender=boy&category=<?= urlencode($cat['slug']) ?>" class="<?= ($gender_param === 'boy' && $category_slug === $cat['slug']) ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Products Section -->
        <section class="products-section">
            <div class="products-header">
                <h1 id="categoryTitle"><?= htmlspecialchars($category_title) ?></h1>
                <p class="products-count" id="productsCount"><?= $product_count ?> product<?= $product_count !== 1 ? 's' : '' ?></p>
            </div>

            <div class="products-grid" id="productsGrid">
                <?php if ($product_count === 0): ?>
                    <div class="no-products" style="grid-column: 1 / -1;">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Products Found</h3>
                        <p>There are no products in this category yet.</p>
                    </div>
                <?php else: ?>
                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="product-card">
                            <div class="product-image-wrapper">
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                                <button class="product-wishlist-btn" onclick="event.preventDefault(); addToWishlist(<?= $product['id'] ?>)">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="product-description"><?= htmlspecialchars($product['description'] ?? '') ?></p>
                                <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- ═══════════════════════════════════
         FOOTER SECTION
    ═══════════════════════════════════ -->
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
                <p>The content of this site is copyright-protected and is the property of H & M Hennes & Mauritz AB.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">function toggleWishlist(btn) {
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

    <script>



        // Initialize page removed - server side rendering

        // Side Panel Controls
        const ladiesMenuItem = document.getElementById('ladiesMenuItem');
        const ladiesSidePanel = document.getElementById('ladiesSidePanel');
        const panelOverlay = document.getElementById('panelOverlay');
        const closeLadiesPanelBtn = document.getElementById('closeLadiesPanelBtn');

        const menMenuItem = document.getElementById('menMenuItem');
        const menSidePanel = document.getElementById('menSidePanel');
        const closeMenPanelBtn = document.getElementById('closeMenPanelBtn');

        const kidsMenuItem = document.getElementById('kidsMenuItem');
        const kidsSidePanel = document.getElementById('kidsSidePanel');
        const closeKidsPanelBtn = document.getElementById('closeKidsPanelBtn');

        function closeAllPanels() {
            ladiesSidePanel.classList.remove('active');
            menSidePanel.classList.remove('active');
            kidsSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        }

        // LADIES MENU CONTROLS
        ladiesMenuItem.addEventListener('mouseenter', function() {
            closeAllPanels();
            ladiesSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        ladiesSidePanel.addEventListener('mouseenter', function() {
            ladiesSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        ladiesMenuItem.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!ladiesSidePanel.matches(':hover')) {
                    ladiesSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });

        ladiesSidePanel.addEventListener('mouseleave', function() {
            ladiesSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });

        closeLadiesPanelBtn.addEventListener('click', function() {
            closeAllPanels();
        });

        // MEN MENU CONTROLS
        menMenuItem.addEventListener('mouseenter', function() {
            closeAllPanels();
            menSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        menSidePanel.addEventListener('mouseenter', function() {
            menSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        menMenuItem.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!menSidePanel.matches(':hover')) {
                    menSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });

        menSidePanel.addEventListener('mouseleave', function() {
            menSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });

        closeMenPanelBtn.addEventListener('click', function() {
            closeAllPanels();
        });

        // KIDS MENU CONTROLS
        kidsMenuItem.addEventListener('mouseenter', function() {
            closeAllPanels();
            kidsSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        kidsSidePanel.addEventListener('mouseenter', function() {
            kidsSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        kidsMenuItem.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!kidsSidePanel.matches(':hover')) {
                    kidsSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });

        kidsSidePanel.addEventListener('mouseleave', function() {
            kidsSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });

        closeKidsPanelBtn.addEventListener('click', function() {
            closeAllPanels();
        });

        panelOverlay.addEventListener('click', function() {
            closeAllPanels();
        });
    function toggleWishlist(btn) {
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

<script src="autocomplete.js">function toggleWishlist(btn) {
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
