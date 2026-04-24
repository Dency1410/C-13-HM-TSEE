

<?php
require 'includes/db.php';

$faqs = [];
$query = "SELECT * FROM faqs ORDER BY id ASC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $faqs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service - H&M</title>

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
            line-height: 1.6;
            color: #222222;
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
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

        /* Kids Panel Special Styles */
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

        /* Customer Service Page Styles */
        .customer-service-container {
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

        .main-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 60px;
            margin-bottom: 60px;
        }

        /* Left Section - Order Tracking */
        .left-section {
            max-width: 600px;
        }

        .section-heading {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .form-label .required {
            color: #e50010;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 1px solid #222222;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input::placeholder {
            color: #999999;
        }

        .form-input:focus {
            border-color: #666666;
        }

        .btn-track {
            width: 100%;
            padding: 18px;
            background-color: #222222;
            color: #ffffff;
            border: none;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-bottom: 15px;
        }

        .btn-track:hover {
            background-color: #000000;
        }

        .btn-return {
            width: 100%;
            padding: 18px;
            background-color: #ffffff;
            color: #222222;
            border: 1px solid #222222;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-return:hover {
            background-color: #f4f4f4;
        }

        .info-text {
            font-size: 13px;
            color: #666666;
            line-height: 1.6;
            margin-top: 25px;
        }

        /* Right Section - Categories */
        .right-section {
            position: sticky;
            top: 90px;
            height: fit-content;
        }

        .category-heading {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .category-list {
            list-style: none;
            padding: 0;
        }

        .category-item {
            margin-bottom: 15px;
        }

        .category-link {
            font-size: 16px;
            color: #222222;
            text-decoration: none;
            font-weight: 400;
            transition: color 0.2s;
            display: block;
        }

        .category-link:hover {
            color: #666666;
        }

        /* FAQ Section */
        .faq-section {
            border-top: 1px solid #e5e5e5;
            padding-top: 50px;
            margin-bottom: 60px;
        }

        .faq-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            text-transform: uppercase;
        }

        .faq-list {
            max-width: 600px;
        }

        .faq-item {
            border-top: 1px solid #e5e5e5;
        }

        .faq-question {
            width: 100%;
            padding: 20px 40px 20px 0;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 400;
            text-align: left;
            cursor: pointer;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: color 0.2s;
        }

        .faq-question:hover {
            color: #666666;
        }

        .faq-question::after {
            content: '+';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            font-weight: 300;
            transition: transform 0.3s;
        }

        .faq-question.active::after {
            content: '−';
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding: 0 0 0 0;
        }

        .faq-answer.active {
            max-height: 500px;
            padding: 0 0 25px 0;
        }

        .faq-answer p {
            font-size: 14px;
            line-height: 1.7;
            color: #666666;
        }

        .privacy-link {
            color: #222222;
            text-decoration: underline;
        }

        .privacy-link:hover {
            color: #666666;
        }

        /* Contact Section */
        .contact-section {
            border-top: 1px solid #e5e5e5;
            padding-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        .contact-info h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .contact-info p {
            font-size: 14px;
            line-height: 1.7;
            color: #666666;
            margin-bottom: 20px;
        }

        .contact-link {
            display: inline-block;
            font-size: 14px;
            color: #222222;
            text-decoration: underline;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .contact-link:hover {
            color: #666666;
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

        /* ═══════════════════════════════════
           RESPONSIVE DESIGN
        ═══════════════════════════════════ */
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .right-section {
                position: static;
                max-width: 600px;
            }

            .contact-section {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-columns {
                grid-template-columns: repeat(2, 1fr);
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .hm-navbar {
                padding: 0 15px;
                height: 60px;
            }

            .hm-nav-menu {
                gap: 18px;
                margin-left: 20px;
            }
            
            .hm-nav-menu a {
                font-size: 12px;
            }

            .side-panel {
                width: 70vw;
                top: 60px;
                height: calc(100vh - 60px);
            }

            .panel-overlay {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .page-title {
                font-size: 32px;
                margin-bottom: 30px;
            }

            .customer-service-container {
                padding: 30px 15px;
            }

            .main-content {
                gap: 30px;
            }

            .faq-title {
                font-size: 20px;
            }

            .faq-question {
                font-size: 14px;
                padding: 18px 35px 18px 0;
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
            .hm-nav-menu {
                gap: 12px;
                margin-left: 10px;
            }
            
            .hm-nav-menu a {
                font-size: 10px;
                letter-spacing: 0.3px;
            }
            
            .hm-icons {
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .side-panel {
                width: 85vw;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .btn-track, .btn-return {
                padding: 15px;
                font-size: 12px;
            }
        }
        
        /* Bootstrap Grid Overrides */
        @media (min-width: 768px) {
            .col-md-6 {
                flex: 0 0 auto;
                width: 50%;
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

    <!-- Main Content -->
    <div class="customer-service-container">
        <!-- Page Title -->
        <h1 class="page-title">Customer Service</h1>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2 class="faq-title">FAQ</h2>
            <div class="faq-list">
                <?php if (count($faqs) > 0): ?>
                    <?php foreach ($faqs as $faq): ?>
                        <div class="faq-item">
                            <button class="faq-question"><?= htmlspecialchars($faq['question']) ?></button>
                            <div class="faq-answer">
                                <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No FAQs available at this time.</p>
                <?php endif; ?>
            </div>

            <p class="info-text" style="margin-top: 30px;">
                For questions regarding your personal data, and instructions on how to access or delete 
                your stored information, please visit our 
                <a href="#" class="privacy-link">Privacy Notice.</a>
            </p>
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

            // FAQ Accordion functionality with jQuery
            $('.faq-question').on('click', function() {
                const $answer = $(this).next('.faq-answer');
                const isActive = $(this).hasClass('active');

                // Close all FAQs
                $('.faq-question').removeClass('active');
                $('.faq-answer').removeClass('active');

                // Open clicked FAQ if it wasn't active
                if (!isActive) {
                    $(this).addClass('active');
                    $answer.addClass('active');
                }
            });

            // Track Order Form Submit
            $('#trackOrderForm').on('submit', function(e) {
                e.preventDefault();
                const orderNumber = $('#orderNumber').val().trim();
                
                if (orderNumber === '') {
                    alert('Please enter your order number');
                    $('#orderNumber').css('border-color', '#e50010');
                } else {
                    $('#orderNumber').css('border-color', '#222222');
                    alert('Tracking order: ' + orderNumber + '\n\nIn production, this would redirect to order tracking page.');
                }
            });

            // Return Button
            $('#returnBtn').on('click', function() {
                alert('Redirecting to returns portal...\n\nIn production, this would take you to the returns page.');
            });

            // Category Links
            $('.category-link').on('click', function(e) {
                e.preventDefault();
                const category = $(this).text();
                alert('Navigating to: ' + category + '\n\nIn production, this would take you to the category page.');
            });

            // Privacy Link
            $('.privacy-link').on('click', function(e) {
                e.preventDefault();
                alert('Opening Privacy Notice\n\nIn production, this would open the privacy policy page.');
            });

            // Contact Link
            $('.contact-link').on('click', function(e) {
                e.preventDefault();
                alert('Opening contact options\n\nIn production, this would open the contact form or chatbot.');
            });

            // Responsive adjustments
            function checkWindowSize() {
                if ($(window).width() <= 768) {
                    // Mobile adjustments if needed
                }
            }
            checkWindowSize();
            $(window).resize(checkWindowSize);
        });
    </script>
<script src="autocomplete.js"></script>
</body>
</html>
