

<?php
include_once 'check_login.php';
require_once 'includes/db.php';

// Always fetch latest data from DB
$uid = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, phone, gender, dob, address, profile_photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    $_SESSION['user_name'] = $row['full_name'];
    $_SESSION['user_email'] = $row['email'];
    $_SESSION['user_phone'] = $row['phone'] ?? '';
    $_SESSION['user_gender'] = $row['gender'] ?? '';
    $_SESSION['user_dob'] = $row['dob'] ?? '';
    $_SESSION['user_address'] = $row['address'] ?? '';
    $_SESSION['profile_pic'] = $row['profile_photo'] ?? '';
    $_SESSION['user_avatar'] = $row['profile_photo'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>H&M | My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            color: #222;
        }

        /* ── HEADER ── */
        .hm-header {
            background: #fff;
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

        .hm-nav-menu a {
            text-decoration: none;
            color: #707070;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 25px 0;
            display: inline-block;
            transition: color 0.3s;
        }

        .hm-nav-menu a:hover {
            color: #000;
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
            color: #222;
            font-size: 20px;
            transition: transform 0.2s;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hm-icon-btn:hover {
            transform: scale(1.1);
        }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #E50010;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 16px;
            text-align: center;
            line-height: 1.2;
        }

        /* ── SIDE PANELS ── */
        .side-panel {
            position: fixed;
            left: 0;
            top: 70px;
            width: 400px;
            max-width: 85vw;
            height: calc(100vh - 70px);
            background: #fff;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 999;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            padding: 40px 0;
            scrollbar-width: none;
        }

        .side-panel::-webkit-scrollbar {
            display: none;
        }

        .side-panel.active {
            transform: translateX(0);
        }

        .side-panel-header {
            padding: 0 30px 20px;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-arrow {
            cursor: pointer;
            color: #222;
            font-size: 20px;
            transition: transform 0.2s;
        }

        .back-arrow:hover {
            transform: translateX(-3px);
        }

        .side-panel-title {
            font-size: 24px;
            font-weight: 700;
            color: #222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .side-panel-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .side-panel-menu a {
            display: block;
            padding: 15px 30px;
            color: #222;
            text-decoration: none;
            font-size: 16px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .side-panel-menu a:hover {
            background: #f8f8f8;
            border-left-color: #E50010;
            padding-left: 35px;
        }

        .panel-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background: rgba(0, 0, 0, 0.3);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
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
            color: #222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .kids-column-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .kids-column-menu a {
            display: block;
            padding: 12px 0;
            color: #222;
            text-decoration: none;
            font-size: 15px;
            font-weight: 400;
            text-transform: uppercase;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .kids-column-menu a:hover {
            color: #E50010;
            padding-left: 10px;
            border-left-color: #E50010;
        }

        /* ── PAGE LAYOUT ── */
        .container-custom {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 40px 80px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 40px;
            text-transform: uppercase;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 32px;
            align-items: stretch;
        }

        /* ── LEFT SIDEBAR ── */
        .sidebar-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            display: flex;
            flex-direction: column;
        }

        .sidebar-top {
            padding: 32px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-bottom: 1px solid #e5e5e5;
        }

        .avatar-wrap {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #E50010;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 16px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .sidebar-name {
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #222;
            margin-bottom: 5px;
        }

        .sidebar-email {
            font-size: 13px;
            color: #888;
            word-break: break-word;
        }

        .menu-group {
            padding: 16px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .menu-group:last-child {
            border-bottom: none;
        }

        .menu-group-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #aaa;
            padding: 0 24px;
            margin-bottom: 6px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            cursor: pointer;
        }

        .menu-item i {
            width: 18px;
            font-size: 14px;
            color: #888;
            text-align: center;
            transition: color 0.2s;
        }

        .menu-item:hover {
            background: #fafafa;
            border-left-color: #E50010;
            color: #E50010;
        }

        .menu-item:hover i {
            color: #E50010;
        }

        .menu-item.active {
            background: #fdf0f0;
            border-left-color: #E50010;
            color: #E50010;
        }

        .menu-item.active i {
            color: #E50010;
        }

        .sidebar-logout {
            padding: 16px 24px;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background: none;
            color: #E50010;
            border: 1px solid #E50010;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #E50010;
            color: #fff;
        }

        /* ── RIGHT CONTENT PANEL ── */
        .content-panel {
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 36px 40px 50px;
            min-height: 500px;
        }

        /* content sections — only active one visible */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* ── PERSONAL DETAIL STYLES ── */
        .section-heading {
            font-size: 23px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #222;
            padding-bottom: 14px;
            border-bottom: 2px solid #E50010;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-heading i {
            color: #E50010;
            font-size: 17px;
        }

        /* avatar upload row */
        .avatar-section {
            display: flex;
            align-items: center;
            gap: 26px;
            margin-bottom: 36px;
            padding-bottom: 32px;
            border-bottom: 1px solid #f0f0f0;
        }

        .avatar-big {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #E50010;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 40px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }

        .avatar-big img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .avatar-upload-label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #222;
            margin-bottom: 5px;
        }

        .avatar-upload-hint {
            font-size: 12px;
            color: #999;
            margin-bottom: 13px;
            line-height: 1.5;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            border: 1.5px solid #222;
            background: none;
            color: #222;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.25s;
        }

        .upload-btn:hover {
            background: #222;
            color: #fff;
        }

        /* fields grid */
        .fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 36px;
        }

        .field-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field-item.full {
            grid-column: 1 / -1;
        }

        .field-lbl {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #000;
        }

        .field-val {
            font-size: 17px;
            font-weight: 500;
            color: #222;
            padding: 10px 0;
            border-bottom: 1.5px solid #ebebeb;
            min-height: 42px;
            display: flex;
            align-items: center;
            line-height: 1.4;
        }

        .field-val.addr {
            align-items: flex-start;
            flex-direction: column;
            line-height: 1.7;
        }

        .field-val .empty {
            color: #ccc;
            font-style: italic;
            font-weight: 400;
            font-size: 15px;
        }

        .field-val.pw {
            letter-spacing: 4px;
            font-size: 20px;
            color: #555;
        }

        .gender-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fdf0f0;
            color: #E50010;
            border: 1px solid #f5c2c7;
            padding: 3px 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .bday {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bday i {
            color: #E50010;
            font-size: 13px;
        }

        /* edit button */
        .edit-row {
            margin-top: 34px;
            display: flex;
            justify-content: flex-end;
        }

        .edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            background: #E50010;
            color: #fff;
            border: none;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.25s;
            text-decoration: none;
        }

        .edit-btn:hover {
            background: #b8000c;
            color: #fff;
        }

        /* ── FORM FIELDS ── */
        .field-input {
            width: 100%;
            padding: 11px 0;
            border: none;
            border-bottom: 1.5px solid #e0e0e0;
            background: transparent;
            font-size: 17px;
            font-weight: 500;
            color: #111 !important;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .field-input:focus { border-bottom-color: #E50010; }
        .field-input::placeholder { color: #999; font-weight: 400; font-size: 15px; }

        .gender-options {
            display: flex;
            gap: 12px;
            padding: 8px 0 4px;
        }

        .gender-opt {
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #444;
            padding: 7px 16px;
            border: 1.5px solid #e0e0e0;
            transition: all 0.2s;
            user-select: none;
        }

        .gender-opt input[type="radio"] { display: none; }

        .gender-opt.selected,
        .gender-opt:has(input:checked) {
            border-color: #E50010;
            background: #fdf0f0;
            color: #E50010;
        }

        .pw-wrap { position: relative; }
        .pw-wrap .field-input { padding-right: 36px; }

        .pw-toggle-btn {
            position: absolute;
            right: 12px;
            bottom: 12px;
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            font-size: 15px;
            padding: 0;
            transition: color 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            width: 24px;
        }

        .pw-toggle-btn:hover {
            color: #E50010;
            transform: scale(1.1);
        }

        .alert-msg {
            padding: 13px 18px;
            font-size: 13px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .alert-msg.success { background: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; }
        .alert-msg.error   { background: #fdecea; border: 1px solid #f5c2c7; color: #c62828; }

        /* ── ACTION BUTTONS ── */
        .btn-row {
            margin-top: 36px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn-save {
            padding: 12px 32px;
            background: #E50010;
            color: #fff;
            border: none;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.25s;
        }

        .btn-save:hover { background: #b8000c; }

        /* placeholder for other sections */
        .content-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            text-align: center;
        }

        .content-placeholder i {
            font-size: 48px;
            color: #e5e5e5;
            margin-bottom: 14px;
        }

        .content-placeholder p {
            font-size: 13px;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* ── WELCOME BANNER ── */
        .welcome-banner {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 14px 20px;
            margin-bottom: 32px;
            font-size: 14px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
        }

        /* ── FOOTER ── */
        .hm-footer {
            background: #222;
            color: #fff;
            padding: 60px 0 30px;
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

        .footer-title {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
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
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #999;
        }

        .footer-copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #444;
            font-size: 13px;
            color: #999;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hm-nav-menu {
                display: none;
            }

            .container-custom {
                padding: 30px 20px 60px;
            }

            .page-title {
                font-size: 36px;
            }

            .footer-columns {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-container {
                padding: 0 20px;
            }

            .content-panel {
                padding: 24px 20px 36px;
            }

            .fields-grid {
                grid-template-columns: 1fr;
            }

            .field-item.full {
                grid-column: 1;
            }

            .avatar-section {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 30px;
            }
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
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 7V5a4 4 0 0 1 8 0v2" />
                        <rect x="3" y="7" width="14" height="13" rx="1" />
                    </svg>
                    <span class="cart-badge" id="cartCount" style="display:none;">0</span>
                </button>
            </div>
        </nav>
    </header>

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
                <div>
                    <h4 class="kids-column-title">GIRL</h4>
                    <ul class="kids-column-menu">
                        <li><a href="kids.php?gender=girl&category=21">Dresses</a></li>
                        <li><a href="kids.php?gender=girl&category=22">Jeans</a></li>
                        <li><a href="kids.php?gender=girl&category=23">T-shirts</a></li>
                        <li><a href="kids.php?gender=girl&category=24">Shorts</a></li>
                        <li><a href="kids.php?gender=girl&category=25">Jumpsuits & playsuits</a></li>
                    </ul>
                </div>
                <div>
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

    <!-- MAIN -->
    <div class="container-custom">

        <h1 class="page-title">ORDER HISTORY</h1>

        <div class="welcome-banner" id="welcomeBanner">
            <i class="fas fa-check-circle"></i>
            <span>Welcome to H&amp;M! Your account has been created successfully.</span>
        </div>

        <div class="profile-layout">

            <!-- ══ LEFT SIDEBAR ══ -->
            <div class="sidebar-card">

                <div class="sidebar-top">
                    <div>
                        <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? ''); ?>" alt=""
                            id="sidebarAvatarImg" height="110px" width="110px"
                            style="border-radius: 50%;object-fit: cover;">

                    </div>
                    <div class="sidebar-name" id="sidebarName">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </div>
                    <div class="sidebar-email" id="sidebarEmail">
                        <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>
                    </div>
                </div>

                <!-- ACCOUNT -->
                <div class="menu-group">
                    <div class="menu-group-label">Account</div>
                    <a class="menu-item " data-section="personal-detail">
                        <i class="fas fa-user"></i> Personal Detail
                    </a>
                    <a class="menu-item" data-section="edit-profile">
                        <i class="fas fa-pen"></i> Edit Profile
                    </a>
                    <a class="menu-item" data-section="change-password">
                        <i class="fas fa-lock"></i> Change Password
                    </a>
                </div>


                <div class="menu-group">
                    <div class="menu-group-label">Shopping</div>
                    <a class="menu-item" href="wishlist.php">
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                    <a class="menu-item" href="cart.php">
                        <i class="fas fa-shopping-bag"></i> Cart
                    </a>
                    <a class="menu-item active" href="order-history.php">
                        <i class="fas fa-box"></i> Orders
                    </a>
                    <a class="menu-item" href="shipment.php">
                        <i class="fas fa-truck"></i> Shipment
                    </a>
                    <a class="menu-item" href="ratings.php">
                        <i class="fas fa-star"></i> Rating &amp; Review
                    </a>
                </div>

                <!-- LOGOUT -->
                <div class="sidebar-logout">
                    <button class="logout-btn" id="logoutBtn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>

            </div>
            <!-- ══ END SIDEBAR ══ -->

            <!-- ══ RIGHT CONTENT ══ -->
            <div class="content-panel">


                <!-- ── ORDERS LIST ── -->
                <div class="content-section active" id="section-order-history">
                    <div class="section-heading" style="margin-bottom: 20px;">
                        <i class="fas fa-box"></i>
                        Order History
                    </div>
                    <?php
                        // Fetch Orders
                        $ord_q = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
                        $orders = [];
                        if ($ord_q) {
                            while ($r = mysqli_fetch_assoc($ord_q)) {
                                $orders[] = $r;
                            }
                        }
                    ?>
                    <?php if (count($orders) === 0): ?>
                        <div class="content-placeholder">
                            <i class="fas fa-box-open" style="font-size: 40px; color: #ddd; margin-bottom: 15px;"></i>
                            <p style="color: #888; font-size: 14px; text-transform: uppercase; font-weight: 600;">You haven\'t placed any orders yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    $order_id = $order['id'];
                                    $status = $order['status'];
                                    $status_color = '#E50010';
                                    if($status === 'Delivered') $status_color = '#2e7d32';
                                    elseif($status === 'Processing') $status_color = '#e67300';
                                    
                                    $items_q = mysqli_query($conn, "SELECT oi.*, p.name, p.image1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
                                    $items = [];
                                    while ($it = mysqli_fetch_assoc($items_q)) {
                                        $items[] = $it;
                                    }
                                ?>
                                <div style="border: 1px solid #e5e5e5; margin-bottom: 20px; padding: 20px; background: #fff;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #e5e5e5; padding-bottom: 15px; margin-bottom: 15px;">
                                        <div>
                                            <div style="font-size: 14px; color:#222; font-weight: 700;">Order #HM<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                            <div style="font-size: 12px; color:#666; margin-top:2px;"><?= date('F j, Y', strtotime($order['created_at'])) ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 14px; font-weight: 700; color: #222;">$<?= number_format($order['total'], 2) ?></div>
                                            <div style="font-size: 12px; color:<?= $status_color ?>; font-weight: 700; text-transform:uppercase; margin-top:2px;"><?= htmlspecialchars($status) ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php foreach ($items as $it): ?>
                                    <div style="display:flex; gap: 15px; margin-bottom: 12px; align-items:center;">
                                        <img src="uploads/<?= htmlspecialchars($it['image1']) ?>" alt="Image" style="width:60px; height:80px; object-fit:cover; background:#f5f5f5;">
                                        <div>
                                            <div style="font-size: 13px; font-weight: 600; color:#222; margin-bottom:4px;"><?= htmlspecialchars($it['name']) ?></div>
                                            <div style="font-size: 12px; color:#666;">Qty: <?= $it['quantity'] ?> &nbsp;|&nbsp; $<?= number_format($it['price'], 2) ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                    </div>
            <!-- ══ END RIGHT CONTENT ══ -->

        </div>
    </div>

    <!-- FOOTER -->
    <footer class="hm-footer">
        <div class="footer-container">
            <div class="footer-columns">
                <div>
                    <h3 class="footer-title">SHOP</h3>
                    <ul class="footer-links">
                        <li><a href="product.php?gender=Ladies">LADIES</a></li>
                        <li><a href="product.php?gender=Men">MEN</a></li>
                        <li><a href="product.php?gender=Kids">KIDS</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="footer-title">CORPORATE INFO</h3>
                    <ul class="footer-links">
                        <li><a href="about-us.php">ABOUT US</a></li>
                        <li><a href="ceo.php">CEO</a></li>
                        <li><a href="investor.php">INVESTOR</a></li>
                        <li><a href="board-of-director.php">BOARD OF DIRECTOR</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="footer-title">HELP</h3>
                    <ul class="footer-links">
                        <li><a href="customer-service.php">CUSTOMER SERVICE</a></li>
                        <li><a href="contact.php">CONTACT</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-copyright">
                <p>The content of this site is copyright-protected and is the property of H &amp; M Hennes &amp; Mauritz
                    AB.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* Helper for password visibility */
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        $(document).ready(function () {

            /* ── Tab Switching ── */
            $('[data-section]').on('click', function (e) {
                e.preventDefault();
                const targetSection = $(this).data('section');

                // Update active menu link
                $('.menu-item').removeClass('active');
                $(this).addClass('active');

                // Update active section
                $('.content-section').removeClass('active');
                $('#section-' + targetSection).addClass('active');

                // Clear alerts
                $('.alert-msg').hide();
            });

            /* ── Avatar Edit Preview ── */
            $('#avatarEditInput').on('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    const src = e.target.result;
                    $('#avatarEditImg, #avatarBigImg, #sidebarAvatarImg').attr('src', src).show();
                    $('#avatarEditInitial, #avatarBigInitial, #sidebarAvatarInitial').hide();
                };
                reader.readAsDataURL(file);
            });

            /* ── Gender Selection Logic ── */
            $(document).on('click', '.gender-opt', function() {
                $(this).siblings().removeClass('selected');
                $(this).addClass('selected');
            });

            /* ── Edit Profile AJAX ── */
            $('#editProfileForm').on('submit', function (e) {
                e.preventDefault();
                const $btn = $(this).find('.btn-save');
                const originalHtml = $btn.html();
                
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                $('#successMsgProfile, #errorMsgProfile').hide();

                $.ajax({
                    url: 'update-profile.php',
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        try {
                            const data = typeof res === 'string' ? JSON.parse(res) : res;
                            if (data.success) {
                                $('#successMsgProfile').css('display', 'flex').hide().fadeIn(300);
                                
                                // Update UI elements
                                if (data.name) {
                                    $('#sidebarName, #userNameLabel').text(data.name);
                                    // Also update name in the detail view fields
                                    $('.field-val:contains("Full Name")').next().text(data.name); 
                                    // Refetching or partial refresh is better, but for now we suggest refresh or manually update
                                    // Let's just suggest a reload if we want 100% sync or update manually
                                    location.reload(); // Simplest way to sync all static PHP values
                                }
                            } else {
                                $('#errorTextProfile').text(data.message || 'Error updating profile.');
                                $('#errorMsgProfile').css('display', 'flex').hide().fadeIn(300);
                            }
                        } catch (err) {
                            $('#errorTextProfile').text('Server error. Please try again.');
                            $('#errorMsgProfile').css('display', 'flex').hide().fadeIn(300);
                        }
                    },
                    error: function () {
                        $('#errorTextProfile').text('Network error. Please try again.');
                        $('#errorMsgProfile').css('display', 'flex').hide().fadeIn(300);
                    },
                    complete: function () {
                        $btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

            /* ── Change Password AJAX ── */
            $('#changePasswordForm').on('submit', function (e) {
                e.preventDefault();
                const $btn = $(this).find('.btn-save');
                const originalHtml = $btn.html();

                $btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                $('#successMsgPass, #errorMsgPass').hide();

                $.ajax({
                    url: 'update-password.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        try {
                            const data = typeof res === 'string' ? JSON.parse(res) : res;
                            if (data.success) {
                                $('#successMsgPass').find('span').text(data.message);
                                $('#successMsgPass').css('display', 'flex').hide().fadeIn(300);
                                $('#changePasswordForm')[0].reset();
                            } else {
                                $('#errorTextPass').text(data.message || 'Error updating password.');
                                $('#errorMsgPass').css('display', 'flex').hide().fadeIn(300);
                            }
                        } catch (err) {
                            $('#errorTextPass').text('Server error. Please try again.');
                            $('#errorMsgPass').css('display', 'flex').hide().fadeIn(300);
                        }
                    },
                    error: function () {
                        $('#errorTextPass').text('Network error. Please try again.');
                        $('#errorMsgPass').css('display', 'flex').hide().fadeIn(300);
                    },
                    complete: function () {
                        $btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

            /* Logout */
            $('#logoutBtn').on('click', function () {
                window.location.href = 'logout.php';
            });

            /* Side panels */
            function closeAllPanels() {
                $('#ladiesSidePanel, #menSidePanel, #kidsSidePanel').removeClass('active');
                $('#panelOverlay').removeClass('active');
            }

            function bindPanel(menuId, panelId) {
                const $menu = $('#' + menuId);
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
            bindPanel('menMenuItem', 'menSidePanel');
            bindPanel('kidsMenuItem', 'kidsSidePanel');

            $('#closeLadiesPanelBtn, #closeMenPanelBtn, #closeKidsPanelBtn').on('click', closeAllPanels);
            $('#panelOverlay').on('click', closeAllPanels);

        });
    </script>
<script src="autocomplete.js"></script>
</body>

</html>
