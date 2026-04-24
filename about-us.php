<?php
require 'includes/db.php';
// Fetch the main about_us record (section_key = 'main')
$result = mysqli_query($conn, "SELECT * FROM about_us WHERE section_key='main' LIMIT 1");
$au = $result ? mysqli_fetch_assoc($result) : [];

// Helper to safely output a field or fallback
function auVal($au, $key, $fallback = '') {
    return htmlspecialchars($au[$key] ?? $fallback);
}

// Resolve image src: could be a relative path or a full URL
function auImg($au, $key, $fallback = '') {
    $val = $au[$key] ?? $fallback;
    if (!$val) return $fallback;
    return (strpos($val, 'http') === 0) ? $val : $val;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - H&M</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- jQuery (needed for validation) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- jQuery Validation Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            overflow-x: hidden;
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

        /* ═══════════════════════════════════
           ABOUT US PAGE CONTENT
        ═══════════════════════════════════ */
        
        /* Hero Section with Image */
        .page-title {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 15px;
            text-transform: uppercase;
            padding: 40px 0 0 40px;
        }

        .about-hero {
            width: 100%;
            height: 70vh;
            position: relative;
            overflow: hidden;
            background: #f5f5f5;
        }

        .about-hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
        }

        /* Main Content Section */
        .about-main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 60px 80px 60px;
        }

        .about-intro {
            margin-bottom: 60px;
        }

        .about-intro-title {
            font-size: 42px;
            font-weight: 300;
            color: #222222;
            margin-bottom: 30px;
            line-height: 1.3;
            letter-spacing: -0.5px;
            font-family: Georgia, 'Times New Roman', serif;
        }

        .about-intro-text {
            font-size: 16px;
            line-height: 1.8;
            color: #222222;
            margin-bottom: 20px;
        }

        .about-links {
            margin-top: 30px;
        }

        .about-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #222222;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .about-link:hover {
            gap: 15px;
        }

        .about-link i {
            font-size: 12px;
        }

        /* Statistics Section */
        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 60px 80px;
            margin: 80px 0;
        }

        .stat-item {
            text-align: left;
        }

        .stat-number {
            font-size: 100px;
            font-weight: 300;
            color: #222222;
            line-height: 1;
            margin-bottom: 10px;
            letter-spacing: -2px;
        }

        .stat-label {
            font-size: 16px;
            color: #222222;
            line-height: 1.5;
            font-family: Georgia, 'Times New Roman', serif;
        }

        .stat-reference {
            margin-top: 40px;
            font-size: 14px;
            color: #666666;
        }

        .stat-reference a {
            color: #222222;
            text-decoration: underline;
        }

        /* Our Way Section */
        .our-way-section {
            margin: 100px 0 80px 0;
        }

        .our-way-title {
            font-size: 90px;
            font-weight: 300;
            color: #222222;
            margin-bottom: 40px;
            letter-spacing: -2px;
            font-family: Georgia, 'Times New Roman', serif;
        }

        .our-way-banner {
            width: 100%;
            height: 500px;
            background: #222222;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .our-way-banner-content {
            position: relative;
            z-index: 2;
        }

        .our-way-banner-image {
            width: 300px;
            height: auto;
            object-fit: cover;
        }

        .our-way-banner-text {
            position: absolute;
            left: 200px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            color: white;
            font-weight: 300;
            font-family: Georgia, 'Times New Roman', serif;
            z-index: 1;
        }

        .our-way-description {
            margin: 60px 0;
            font-size: 16px;
            line-height: 1.8;
            color: #222222;
            max-width: 900px;
        }

       
       

        /* Three Column Image Section */
        .three-column-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            margin: 80px 0;
        }

        .column-item {
            position: relative;
            overflow: hidden;
            aspect-ratio: 3/4;
        }

        .column-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .column-item:hover .column-image {
            transform: scale(1.05);
        }

        .column-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 30px;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        }

        .column-title {
            color: white;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .column-text {
            color: white;
            font-size: 13px;
            line-height: 1.6;
        }

        /* Related Links Section */
        .related-section {
            margin: 80px 0;
        }

        .related-title {
            font-size: 12px;
            font-weight: 600;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        .related-links-list {
            list-style: none;
            padding: 0;
        }

        .related-links-list li {
            margin-bottom: 20px;
        }

        .related-links-list a {
            font-size: 32px;
            font-weight: 300;
            color: #222222;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            font-family: Georgia, 'Times New Roman', serif;
            transition: gap 0.3s ease;
        }

        .related-links-list a:hover {
            gap: 25px;
        }

        .related-links-list i {
            font-size: 20px;
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
           RESPONSIVE DESIGN (Enhanced with Bootstrap classes)
        ═══════════════════════════════════ */
        @media (max-width: 1024px) {
            .about-hero-title {
                font-size: 70px;
                padding: 40px 40px 30px 40px;
            }

            .about-main-content {
                padding: 20px 40px 60px 40px;
            }

            .about-stats {
                gap: 40px;
            }

            .stat-number {
                font-size: 80px;
            }

            .our-way-title {
                font-size: 70px;
            }

            .our-way-banner-text {
                font-size: 36px;
                left: 100px;
            }
        }

        @media (max-width: 768px) {
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

            .hm-navbar {
                padding: 0 20px;
            }

            .hm-nav-menu {
                gap: 20px;
                margin-left: 20px;
            }

            .hm-nav-menu a {
                font-size: 14px;
            }

            .about-hero {
                height: 50vh;
            }

            .about-hero-title {
                font-size: 50px;
                padding: 30px 30px 20px 30px;
            }

            .about-main-content {
                padding: 10px 30px 40px 30px;
            }

            .about-intro-title {
                font-size: 32px;
            }

            .about-stats {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .stat-number {
                font-size: 70px;
            }

            .our-way-title {
                font-size: 50px;
            }

            .our-way-banner {
                height: 400px;
            }

            .our-way-banner-text {
                font-size: 28px;
                left: 60px;
            }

            .our-way-banner-image {
                width: 200px;
            }

            .three-column-section {
                grid-template-columns: 1fr;
            }

            .related-links-list a {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .hm-navbar {
                padding: 0 15px;
                height: 60px;
            }

            .hm-nav-menu {
                gap: 15px;
                margin-left: 15px;
            }

            .hm-nav-menu a {
                font-size: 12px;
            }

            .hm-logo-svg {
                width: 45px;
            }

            .hm-icons {
                gap: 20px;
            }

            .side-panel {
                width: 85vw;
            }

            .about-hero-title {
                font-size: 40px;
                padding: 20px 20px 15px 20px;
            }

            .about-main-content {
                padding: 10px 20px 30px 20px;
            }

            .about-intro-title {
                font-size: 28px;
            }

            .about-intro-text {
                font-size: 15px;
            }

            .stat-number {
                font-size: 60px;
            }

            .stat-label {
                font-size: 14px;
            }

            .our-way-title {
                font-size: 40px;
            }

            .our-way-banner {
                height: 300px;
            }

            .our-way-banner-text {
                font-size: 22px;
                left: 30px;
            }

            .our-way-banner-image {
                width: 150px;
            }

            .related-links-list a {
                font-size: 20px;
            }
        }

        /* Additional classes for newsletter validation */
        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .newsletter-input {
            padding: 8px 12px;
            border: 1px solid #555;
            background: #333;
            color: #fff;
            border-radius: 4px;
        }
        .newsletter-btn {
            background: #E50010;
            color: white;
            border: none;
            padding: 10px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 4px;
        }
        .error {
            color: #ff8080;
            font-size: 13px;
            margin-top: 5px;
        }
        label.error {
            display: block;
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

    <!-- ═══════════════════════════════════
         ABOUT US PAGE CONTENT
    ═══════════════════════════════════ -->

    <!-- About Us Title -->
    <h1 class="page-title">ABOUT US</h1>

    <!-- Hero Section -->
    <!-- <section class="about-hero">
        <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&q=80" alt="About H&M" class="about-hero-image">
    </section> -->

    <!-- Main Content -->
    <div class="about-main-content">
        
        <!-- Introduction Section -->
        <div class="about-intro">
            <h2 class="about-intro-title"><?= auVal($au, 'intro_title', 'H&amp;M Group is a global fashion and design company.') ?></h2>
            <p class="about-intro-text">
                <?= auVal($au, 'intro_text', '') ?>
            </p>
            <div class="about-links">
                <a href="#" class="about-link">
                    <i class="fas fa-arrow-right"></i>
                    OUR BRANDS
                </a>
                <a href="#" class="about-link">
                    <i class="fas fa-arrow-right"></i>
                    OUR BUSINESS IDEA
                </a>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="about-stats">
            <?php if (!empty($au['stat1_number'])): ?>
            <div class="stat-item">
                <div class="stat-number"><?= auVal($au, 'stat1_number') ?></div>
                <div class="stat-label"><?= auVal($au, 'stat1_label') ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($au['stat2_number'])): ?>
            <div class="stat-item">
                <div class="stat-number"><?= auVal($au, 'stat2_number') ?></div>
                <div class="stat-label"><?= auVal($au, 'stat2_label') ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($au['stat3_number'])): ?>
            <div class="stat-item">
                <div class="stat-number"><?= auVal($au, 'stat3_number') ?></div>
                <div class="stat-label"><?= auVal($au, 'stat3_label') ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($au['stat4_number'])): ?>
            <div class="stat-item">
                <div class="stat-number"><?= auVal($au, 'stat4_number') ?></div>
                <div class="stat-label"><?= auVal($au, 'stat4_label') ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($au['stat_reference'])): ?>
        <div class="stat-reference">
            <?= auVal($au, 'stat_reference') ?>
        </div>
        <?php endif; ?>

        <!-- Our Way Section -->
        <div class="our-way-section">
            <h2 class="our-way-title"><?= auVal($au, 'our_way_title', 'Our way') ?></h2>
            
            <?php $ourWayImg = auImg($au, 'our_way_image', ''); if ($ourWayImg): ?>
            <div class="our-way-banner">
                <div class="our-way-banner-content">
                    <img src="<?= htmlspecialchars($ourWayImg) ?>" alt="Our Way" class="our-way-banner-image">
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($au['our_way_description'])): ?>
            <p class="our-way-description">
                <?= auVal($au, 'our_way_description') ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Three Column Image Section -->
        <div class="three-column-section">
            <?php
            $cols = [
                ['img'=>auImg($au,'col1_image',''), 'title'=>auVal($au,'col1_title'), 'text'=>auVal($au,'col1_text')],
                ['img'=>auImg($au,'col2_image',''), 'title'=>auVal($au,'col2_title'), 'text'=>auVal($au,'col2_text')],
                ['img'=>auImg($au,'col3_image',''), 'title'=>auVal($au,'col3_title'), 'text'=>auVal($au,'col3_text')],
            ];
            foreach ($cols as $col): if (!$col['img'] && !$col['title']) continue; ?>
            <div class="column-item">
                <?php if ($col['img']): ?>
                <img src="<?= htmlspecialchars($col['img']) ?>" alt="<?= $col['title'] ?>" class="column-image">
                <?php endif; ?>
                <div class="column-overlay">
                    <div class="column-title"><?= $col['title'] ?></div>
                    <div class="column-text"><?= $col['text'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Related Links Section -->
        <!-- <div class="related-section">
            <div class="related-title">RELATED</div>
            <ul class="related-links-list">
                <li>
                    <a href="#">
                        CEO of H&M Group <i class="fas fa-arrow-right"></i>
                    </a>
                </li>
                <li>
                    <a href="#">
                        Organisation and management <i class="fas fa-arrow-right"></i>
                    </a>
                </li>
                <li>
                    <a href="#">
                        Sustainability <i class="fas fa-arrow-right"></i>
                    </a>
                </li>
            </ul>
        </div> -->

    </div>

    <!-- ═══════════════════════════════════
         FOOTER SECTION (with Newsletter form for validation demo)
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

                <!-- Help Column with Newsletter form (validation) -->
                <div class="footer-column">
                    <h3 class="footer-title">HELP</h3>
                    <ul class="footer-links">
                        <li><a href="customer-service.php">CUSTOMER SERVICE</a></li>

                        <li><a href="contact.php">CONTACT</a></li>
                    </ul>
                    <!-- Newsletter signup (for validation demo) -->
                    <!-- <form class="newsletter-form mt-4" id="newsletterForm">
                        <label for="newsletterEmail" class="text-white-50 text-uppercase small">Subscribe</label>
                        <input type="email" class="newsletter-input" name="email" id="newsletterEmail" placeholder="Your email" required>
                        <button type="submit" class="newsletter-btn">Subscribe</button>
                    </form> -->
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
        // Side Panel Control for Ladies Menu
        const ladiesMenuItem = document.getElementById('ladiesMenuItem');
        const ladiesSidePanel = document.getElementById('ladiesSidePanel');
        const panelOverlay = document.getElementById('panelOverlay');
        const closeLadiesPanelBtn = document.getElementById('closeLadiesPanelBtn');

        // Side Panel Control for Men Menu
        const menMenuItem = document.getElementById('menMenuItem');
        const menSidePanel = document.getElementById('menSidePanel');
        const closeMenPanelBtn = document.getElementById('closeMenPanelBtn');

        // Side Panel Control for Kids Menu
        const kidsMenuItem = document.getElementById('kidsMenuItem');
        const kidsSidePanel = document.getElementById('kidsSidePanel');
        const closeKidsPanelBtn = document.getElementById('closeKidsPanelBtn');

        // Function to close all panels
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

        panelOverlay.addEventListener('click', function() {
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

        // jQuery validation for newsletter form (without changing any other code)
        $(document).ready(function() {
            $("#newsletterForm").validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    }
                },
                messages: {
                    email: {
                        required: "Please enter your email",
                        email: "Enter a valid email address"
                    }
                },
                submitHandler: function(form) {
                    alert("Thank you for subscribing (validation passed).");
                    form.reset();
                }
            });
        });
    </script>

<script src="autocomplete.js"></script>
</body>

</html>
