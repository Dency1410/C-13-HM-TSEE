<?php
session_start();
require 'includes/db.php';


$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$gender = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : (!empty($_GET['search']) ? '' : 'Men');
$category_slug = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';

// Price & Size filters
$price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : '';
$selected_sizes = isset($_GET['sizes']) && is_array($_GET['sizes']) ? array_map('intval', $_GET['sizes']) : [];

$is_kids_page = in_array($gender, ['Kids', 'Kids Girl', 'Kids Boy']);

// Fetch categories for sidebar
if ($is_kids_page) {
    $categories_girls = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Kids Girl' ORDER BY name ASC");
    $categories_boys = mysqli_query($conn, "SELECT * FROM categories WHERE gender='Kids Boy' ORDER BY name ASC");
} else {
    $categories = mysqli_query($conn, "SELECT * FROM categories WHERE gender='$gender' ORDER BY name ASC");
}

// Fetch all sizes for sidebar filter - in correct clothing size order
$all_sizes_result = mysqli_query($conn, "SELECT * FROM sizes ORDER BY FIELD(name,'XXS','XS','S','M','L','XL','XXL'), name ASC");
$all_sizes = [];
while ($sz = mysqli_fetch_assoc($all_sizes_result)) {
    $all_sizes[] = $sz;
}

// Build products query
$query = "SELECT DISTINCT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id ";

// Join product_sizes only when size filter is active
if (!empty($selected_sizes)) {
    $size_ids_str = implode(',', $selected_sizes);
    $query .= " INNER JOIN product_sizes ps ON ps.product_id = p.id AND ps.size_id IN ($size_ids_str) ";
}

$query .= " WHERE 1=1 ";

if (!empty($search)) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%') ";
} else {
    if ($gender == 'Kids') {
        $query .= " AND p.gender IN ('Kids Girl', 'Kids Boy') ";
    } else {
        $query .= " AND p.gender = '$gender' ";
    }

    if (!empty($category_slug)) {
        $cat_id = (int)$category_slug;
        $query .= " AND c.id = $cat_id";
    }
}

// Price range filters
if ($price_min !== '') {
    $query .= " AND p.price >= $price_min ";
}
if ($price_max !== '') {
    $query .= " AND p.price <= $price_max ";
}

$query .= " ORDER BY p.created_at DESC";

$products = mysqli_query($conn, $query);
$product_count = mysqli_num_rows($products);

$wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wish_res = mysqli_query($conn, "SELECT product_id FROM wishlist WHERE user_id = $uid");
    while($wish_row = mysqli_fetch_assoc($wish_res)) {
        $wishlist_ids[] = $wish_row['product_id'];
    }
}

$category_title = "All Products";
if (!empty($search)) {
    $category_title = "Search Results for '" . htmlspecialchars($_GET['search']) . "'";
} else {
    if ($gender == 'Kids') {
        $category_title = "ALL KIDS PRODUCTS";
    } elseif ($gender == 'Kids Girl') {
        $category_title = "ALL GIRLS PRODUCTS";
    } elseif ($gender == 'Kids Boy') {
        $category_title = "ALL BOYS PRODUCTS";
    }

    if (!empty($category_slug)) {
        $temp_query = "";
        if ($gender == 'Kids') {
            $temp_query = "SELECT * FROM categories WHERE gender IN ('Kids Girl', 'Kids Boy')";
        } else {
            $temp_query = "SELECT * FROM categories WHERE gender='$gender'";
        }
        $temp_categories = mysqli_query($conn, $temp_query);
        while($cat = mysqli_fetch_assoc($temp_categories)) {
            if ($cat['id'] == (int)$category_slug) {
                $category_title = strtoupper($cat['name']);
                break;
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
    <title><?= htmlspecialchars($gender) ?> - <?= htmlspecialchars($category_title) ?> - H&M</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .hm-nav-menu a {
            text-decoration: none;
            color: #707070;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 25px 0;
            display: inline-block;
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
            cursor: pointer;
            color: #222222;
            font-size: 20px;
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
        }

        .men-page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            display: flex;
            gap: 40px;
        }

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

        .category-title {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E50010;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list a {
            display: block;
            padding: 12px 15px;
            color: #555555;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .category-list a.active {
            background-color: #fff5f5;
            color: #E50010;
            font-weight: 700;
            border-left: 3px solid #E50010;
            padding-left: 12px;
        }

        /* â”€â”€ Filter Sections â”€â”€ */
        .filter-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
        }

        .filter-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #222;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .filter-title .filter-collapse-icon {
            font-size: 16px;
            color: #222;
            font-weight: 300;
            line-height: 1;
            transition: transform 0.25s;
        }

        .filter-title.collapsed .filter-collapse-icon {
            transform: rotate(-45deg);
        }

        .filter-body {
            overflow: hidden;
            transition: max-height 0.35s ease;
        }

        /* â”€â”€ Dual Range Price Slider â”€â”€ */
        .price-display-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .price-display-val {
            font-size: 13px;
            font-weight: 700;
            color: #222;
        }

        .price-display-val span {
            font-size: 11px;
            font-weight: 500;
            color: #888;
            margin-left: 3px;
        }

        .dual-range-wrapper {
            position: relative;
            height: 28px;
            margin-bottom: 16px;
        }

        .dual-range-wrapper input[type=range] {
            -webkit-appearance: none;
            appearance: none;
            position: absolute;
            width: 100%;
            height: 3px;
            background: transparent;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            outline: none;
        }

        .dual-range-wrapper input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 2px;
            background: #111;
            cursor: pointer;
            pointer-events: all;
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }

        .dual-range-wrapper input[type=range]::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 2px;
            background: #111;
            cursor: pointer;
            pointer-events: all;
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }

        .range-track {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 0;
            right: 0;
            height: 3px;
            background: #ddd;
            border-radius: 2px;
            pointer-events: none;
        }

        .range-track-fill {
            position: absolute;
            height: 100%;
            background: #111;
            border-radius: 2px;
        }

        /* â”€â”€ Size Chips â”€â”€ */
        .size-group-label {
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }

        .size-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 12px;
        }

        .size-chip-label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .size-chip-label input[type="checkbox"] {
            display: none;
        }

        .size-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
            padding: 7px 10px;
            border: 1px solid #ccc;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            background: #fff;
            transition: all 0.18s;
            white-space: nowrap;
            cursor: pointer;
        }

        .size-chip-label input[type="checkbox"]:checked + .size-chip {
            border-color: #111;
            color: #111;
            background: #f0f0f0;
        }

        .size-chip:hover {
            border-color: #333;
            color: #111;
        }

        /* â”€â”€ Apply / Clear buttons â”€â”€ */
        .filter-apply-btn {
            display: block;
            width: 100%;
            padding: 11px;
            background: #111;
            color: #fff;
            border: none;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 6px;
        }

        .filter-apply-btn:hover {
            background: #E50010;
        }

        .filter-clear-link {
            display: block;
            text-align: center;
            margin-top: 8px;
            font-size: 12px;
            color: #999;
            text-decoration: underline;
            cursor: pointer;
        }

        .filter-clear-link:hover {
            color: #E50010;
        }

        /* â”€â”€ Filter Drawer â”€â”€ */
        .filter-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 1200;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .filter-drawer-overlay.open {
            display: block;
            opacity: 1;
        }

        .filter-drawer {
            position: fixed;
            top: 0;
            right: -420px;
            width: 380px;
            max-width: 95vw;
            height: 100vh;
            background: #fff;
            z-index: 1300;
            display: flex;
            flex-direction: column;
            transition: right 0.35s cubic-bezier(0.4,0,0.2,1);
            box-shadow: -4px 0 24px rgba(0,0,0,0.12);
        }

        .filter-drawer.open {
            right: 0;
        }

        .filter-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 28px 18px;
            border-bottom: 1px solid #e5e5e5;
        }

        .filter-drawer-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #111;
        }

        .filter-drawer-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #555;
            line-height: 1;
            padding: 0 2px;
            transition: color 0.2s;
        }

        .filter-drawer-close:hover { color: #E50010; }

        .filter-drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px 28px;
        }

        .filter-drawer-body .filter-section {
            border-top: 1px solid #e5e5e5;
            padding-top: 22px;
            margin-top: 22px;
        }

        .filter-drawer-body .filter-section:first-child {
            border-top: none;
            padding-top: 0;
            margin-top: 0;
        }

        .filter-drawer-footer {
            padding: 18px 28px;
            border-top: 1px solid #e5e5e5;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* â”€â”€ Filter Toggle Button â”€â”€ */
        .products-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .products-header-left h1 {
            font-size: 32px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .filter-toggle-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: 1.5px solid #222;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            color: #222;
            transition: all 0.2s;
            white-space: nowrap;
            margin-top: 4px;
        }

        .filter-toggle-btn:hover {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        .filter-toggle-btn svg {
            flex-shrink: 0;
        }

        .filter-active-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #E50010;
            display: none;
            margin-left: 2px;
        }

        .filter-active-dot.show { display: inline-block; }

        .products-section {
            flex: 1;
        }

        .products-header h1 {
            font-size: 32px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .products-count {
            font-size: 14px;
            color: #707070;
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
        }

        .product-info {
            padding: 0 5px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #E50010;
        }

        .wishlist-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .wishlist-btn:hover {
            transform: scale(1.1);
            background: #fff;
        }

        .wishlist-btn i {
            font-size: 18px;
            color: #707070;
            transition: color 0.3s ease;
        }

        .wishlist-btn.active i {
            color: #E50010;
        }

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #707070;
        }

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
    </style>
</head>

<body>
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
                <li><a href="product.php?gender=Ladies" class="<?= $gender == 'Ladies' ? 'active' : '' ?>">LADIES</a></li>
                <li><a href="product.php?gender=Men" class="<?= $gender == 'Men' ? 'active' : '' ?>">MEN</a></li>
                <li><a href="product.php?gender=Kids" class="<?= in_array($gender, ['Kids', 'Kids Girl', 'Kids Boy']) ? 'active' : '' ?>">KIDS</a></li>
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

    <div class="men-page-container">
        <?php if (empty($search)): ?>
        <aside class="category-sidebar">
            <?php if ($is_kids_page): ?>
                <h2 class="category-title">CATEGORIES</h2>
                
                <h3 style="font-size:18px; font-weight:700; color:#222; text-transform:uppercase; margin-bottom:15px;">GIRLS</h3>
                <ul class="category-list" style="margin-bottom: 40px;">
                    <li><a href="product.php?gender=Kids Girl" class="<?= ($gender == 'Kids Girl' && empty($category_slug)) ? 'active' : '' ?>">All Products</a></li>
                    <?php while ($cat = mysqli_fetch_assoc($categories_girls)): ?>
                        <li><a href="product.php?gender=Kids Girl&category=<?= $cat['id'] ?>" class="<?= $category_slug == $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endwhile; ?>
                </ul>

                <h3 style="font-size:18px; font-weight:700; color:#222; text-transform:uppercase; margin-bottom:15px;">BOYS</h3>
                <ul class="category-list">
                    <li><a href="product.php?gender=Kids Boy" class="<?= ($gender == 'Kids Boy' && empty($category_slug)) ? 'active' : '' ?>">All Products</a></li>
                    <?php while ($cat = mysqli_fetch_assoc($categories_boys)): ?>
                        <li><a href="product.php?gender=Kids Boy&category=<?= $cat['id'] ?>" class="<?= $category_slug == $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <h2 class="category-title">Categories</h2>
                <ul class="category-list">
                    <li><a href="product.php?gender=<?= urlencode($gender) ?>"
                            class="<?= empty($category_slug) ? 'active' : '' ?>">All Products</a></li>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <li><a href="product.php?gender=<?= urlencode($gender) ?>&category=<?= $cat['id'] ?>"
                                class="<?= $category_slug == $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

        </aside>
        <?php endif; ?>

        <!-- â•â• FILTER DRAWER (right slide-in) â•â• -->
        <div class="filter-drawer-overlay" id="filterOverlay"></div>
        <div class="filter-drawer" id="filterDrawer" role="dialog" aria-modal="true" aria-label="Filters">
            <div class="filter-drawer-header">
                <span class="filter-drawer-title">Filter</span>
                <button class="filter-drawer-close" id="filterDrawerClose" aria-label="Close filters">&times;</button>
            </div>
            <div class="filter-drawer-body">
                <form method="GET" action="product.php" id="filterForm">
                    <!-- Preserve existing params -->
                    <?php if (!empty($gender)): ?>
                        <input type="hidden" name="gender" value="<?= htmlspecialchars($gender) ?>">
                    <?php endif; ?>
                    <?php if (!empty($category_slug)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_slug) ?>">
                    <?php endif; ?>
                    <!-- Hidden inputs updated by JS sliders -->
                    <input type="hidden" id="price_min" name="price_min" value="<?= $price_min !== '' ? htmlspecialchars($price_min) : '' ?>">
                    <input type="hidden" id="price_max" name="price_max" value="<?= $price_max !== '' ? htmlspecialchars($price_max) : '' ?>">

                    <!-- â”€â”€ Price Range Filter â”€â”€ -->
                    <div class="filter-section">
                        <div class="filter-title" id="priceToggle">
                            <span>PRICE RANGE</span>
                            <span class="filter-collapse-icon">-</span>
                        </div>
                        <div class="filter-body" id="priceBody">
                            <div class="price-display-row">
                                <div class="price-display-val" id="priceMinDisplay">
                                    <span>$</span><?= $price_min !== '' ? number_format($price_min, 2) : '0.00' ?> 
                                </div>
                                <div class="price-display-val" id="priceMaxDisplay">
                                    <span>$</span><?= $price_max !== '' ? number_format($price_max, 2) : '500.00' ?>
                                </div>
                            </div>
                            <div class="dual-range-wrapper" id="dualRangeWrapper">
                                <div class="range-track">
                                    <div class="range-track-fill" id="rangeTrackFill"></div>
                                </div>
                                <input type="range" id="sliderMin" min="0" max="500" step="1"
                                       value="<?= $price_min !== '' ? (int)$price_min : 0 ?>">
                                <input type="range" id="sliderMax" min="0" max="500" step="1"
                                       value="<?= $price_max !== '' ? (int)$price_max : 500 ?>">
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Size Filter â”€â”€ -->
                    <?php if (!empty($all_sizes)): ?>
                    <div class="filter-section">
                        <div class="filter-title" id="sizeToggle">
                            <span>SIZE</span>
                            <span class="filter-collapse-icon">-</span>
                        </div>
                        <div class="filter-body" id="sizeBody">
                            <p class="size-group-label">XXS - XXL</p>
                            <div class="size-chips">
                                <?php foreach ($all_sizes as $sz): ?>
                                    <label class="size-chip-label">
                                        <input type="checkbox" name="sizes[]" value="<?= $sz['id'] ?>"
                                               <?= in_array($sz['id'], $selected_sizes) ? 'checked' : '' ?>>
                                        <span class="size-chip"><?= htmlspecialchars($sz['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="filter-drawer-footer">
                        <button type="submit" class="filter-apply-btn">Apply Filters</button>
                        <?php if ($price_min !== '' || $price_max !== '' || !empty($selected_sizes)): ?>
                            <a href="product.php?gender=<?= urlencode($gender) ?><?= !empty($category_slug) ? '&category='.urlencode($category_slug) : '' ?>" class="filter-clear-link">
                                âœ• Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <section class="products-section">
            <div class="products-header">
                <div class="products-header-left">
                    <h1><?= htmlspecialchars($category_title) ?></h1>
                    <p class="products-count"><?= $product_count ?> products</p>
                </div>
                <button class="filter-toggle-btn" id="filterToggleBtn" type="button" aria-expanded="false" aria-controls="filterDrawer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="4" y1="6" x2="20" y2="6"/>
                        <line x1="7" y1="12" x2="17" y2="12"/>
                        <line x1="10" y1="18" x2="14" y2="18"/>
                    </svg>
                    Filter
                    <span class="filter-active-dot <?= ($price_min !== '' || $price_max !== '' || !empty($selected_sizes)) ? 'show' : '' ?>" id="filterActiveDot"></span>
                </button>
            </div>
            <div class="products-grid">
                <?php if ($product_count == 0): ?>
                    <div class="no-products">
                        <i class="fas fa-shopping-bag fa-3x mb-3 text-muted"></i>
                        <h3>No Products Found</h3>
                        <p>There are no products in this category yet.</p>
                    </div>
                <?php else: ?>
                    <?php while ($prod = mysqli_fetch_assoc($products)): ?>
                        <a href="product-detail.php?id=<?= $prod['id'] ?>" class="product-card">
                            <div class="product-image-wrapper">
                                <?php $img = $prod['image'] ? $prod['image'] : 'https://via.placeholder.com/300x400'; ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prod['name']) ?>"
                                    class="product-image">
                                <button type="button" class="wishlist-btn <?= in_array($prod['id'], $wishlist_ids) ? 'active' : '' ?>" 
                                        data-product-id="<?= $prod['id'] ?>" onclick="event.preventDefault(); toggleWishlist(this);">
                                    <i class="<?= in_array($prod['id'], $wishlist_ids) ? 'fas' : 'far' ?> fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($prod['name']) ?></h3>
                                <p class="product-price">$<?= number_format($prod['price'], 2) ?></p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
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
<script src="autocomplete.js"></script>
<script>
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

// â”€â”€ Filter Drawer open/close â”€â”€
(function () {
    const toggleBtn = document.getElementById('filterToggleBtn');
    const drawer    = document.getElementById('filterDrawer');
    const overlay   = document.getElementById('filterOverlay');
    const closeBtn  = document.getElementById('filterDrawerClose');

    function openDrawer() {
        drawer.classList.add('open');
        overlay.style.display = 'block';
        // Force reflow for transition
        requestAnimationFrame(() => overlay.classList.add('open'));
        document.body.style.overflow = 'hidden';
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        // Re-init slider after drawer is visible
        setTimeout(initSlider, 50);
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        setTimeout(() => { overlay.style.display = 'none'; }, 300);
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openDrawer);
    if (closeBtn)  closeBtn.addEventListener('click', closeDrawer);
    if (overlay)   overlay.addEventListener('click', closeDrawer);

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDrawer();
    });

    // Auto-open drawer if filters are active (so user can see their selections)
    <?php if ($price_min !== '' || $price_max !== '' || !empty($selected_sizes)): ?>
    // Filters are active - keep drawer closed but show the red dot indicator
    <?php endif; ?>
})();

// â”€â”€ Filter accordion toggles â”€â”€
(function () {
    function makeToggle(toggleId, bodyId) {
        const toggle = document.getElementById(toggleId);
        const body   = document.getElementById(bodyId);
        if (!toggle || !body) return;
        body.style.maxHeight = body.scrollHeight + 'px';
        toggle.addEventListener('click', function () {
            const collapsed = toggle.classList.toggle('collapsed');
            body.style.maxHeight = collapsed ? '0' : body.scrollHeight + 'px';
        });
    }
    makeToggle('priceToggle', 'priceBody');
    makeToggle('sizeToggle',  'sizeBody');
})();

// â”€â”€ Dual Range Price Slider â”€â”€
function initSlider() {
    const sliderMin  = document.getElementById('sliderMin');
    const sliderMax  = document.getElementById('sliderMax');
    const hiddenMin  = document.getElementById('price_min');
    const hiddenMax  = document.getElementById('price_max');
    const minDisplay = document.getElementById('priceMinDisplay');
    const maxDisplay = document.getElementById('priceMaxDisplay');
    const trackFill  = document.getElementById('rangeTrackFill');

    if (!sliderMin || !sliderMax || sliderMin._inited) return;
    sliderMin._inited = true;

    const RANGE_MIN = parseInt(sliderMin.min);
    const RANGE_MAX = parseInt(sliderMin.max);

    function fmt(v) {
        return parseFloat(v).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function update() {
        let minVal = parseInt(sliderMin.value);
        let maxVal = parseInt(sliderMax.value);
        if (minVal >= maxVal) { minVal = maxVal - 1; sliderMin.value = minVal; }
        if (maxVal <= minVal) { maxVal = minVal + 1; sliderMax.value = maxVal; }

        const pct = v => ((v - RANGE_MIN) / (RANGE_MAX - RANGE_MIN)) * 100;
        trackFill.style.left  = pct(minVal) + '%';
        trackFill.style.width = (pct(maxVal) - pct(minVal)) + '%';

        minDisplay.innerHTML = fmt(minVal) + ' <span>$</span>';
        maxDisplay.innerHTML = fmt(maxVal) + ' <span>$</span>';

        hiddenMin.value = (minVal > RANGE_MIN) ? minVal : '';
        hiddenMax.value = (maxVal < RANGE_MAX) ? maxVal : '';
    }

    sliderMin.addEventListener('input', update);
    sliderMax.addEventListener('input', update);
    update();
}
initSlider();
</script>
</body>

</html>

