

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

// Fetch user orders and their items
$orders = [];
if (isset($uid) && $uid > 0) {
    $q_orders = "SELECT * FROM orders WHERE user_id = $uid ORDER BY id DESC";
    $res_orders = mysqli_query($conn, $q_orders);
    if ($res_orders) {
        while ($o_row = mysqli_fetch_assoc($res_orders)) {
            $order_id = $o_row['id'];
            $items = [];
            $q_items = "SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id";
            $res_items = mysqli_query($conn, $q_items);
            if ($res_items) {
                while ($i_row = mysqli_fetch_assoc($res_items)) {
                    $items[] = $i_row;
                }
            }
            $o_row['items'] = $items;
            $orders[] = $o_row;
        }
    }
}
// Fetch user reviews
$user_reviews = [];
if (isset($uid) && $uid > 0) {
    $q_reviews = "
        SELECT r.*, p.name as product_name, p.image 
        FROM product_reviews r 
        JOIN products p ON r.product_id = p.id 
        WHERE r.user_id = $uid 
        ORDER BY r.created_at DESC
    ";
    $res_reviews = mysqli_query($conn, $q_reviews);
    if ($res_reviews) {
        while ($r_row = mysqli_fetch_assoc($res_reviews)) {
            $user_reviews[] = $r_row;
        }
    }
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

        /* â”€â”€ HEADER â”€â”€ */
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

        /* â”€â”€ SIDE PANELS â”€â”€ */
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

        /* â”€â”€ PAGE LAYOUT â”€â”€ */
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

        /* â”€â”€ LEFT SIDEBAR â”€â”€ */
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

        /* â”€â”€ RIGHT CONTENT PANEL â”€â”€ */
        .content-panel {
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 36px 40px 50px;
            min-height: 500px;
        }

        /* content sections â€” only active one visible */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* â”€â”€ PERSONAL DETAIL STYLES â”€â”€ */
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

        /* â”€â”€ FORM FIELDS â”€â”€ */
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

        /* â”€â”€ ACTION BUTTONS â”€â”€ */
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

        /* â”€â”€ ORDERS SECTION STYLES â”€â”€ */
        .order-card {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            margin-bottom: 24px;
            overflow: hidden;
            background: #fff;
        }

        .order-card-header {
            background: #fafafa;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-meta {
            display: flex;
            gap: 24px;
        }

        .meta-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-lbl {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .meta-val {
            font-size: 14px;
            color: #222;
            font-weight: 500;
        }

        .order-id-col {
            text-align: right;
        }

        .order-items-list {
            padding: 0;
        }

        .order-item-row {
            display: flex;
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .item-img-box {
            width: 80px;
            flex-shrink: 0;
            margin-right: 20px;
        }

        .item-img-box img {
            width: 100%;
            display: block;
            background: #f5f5f5;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-size: 15px;
            font-weight: 600;
            color: #222;
            margin-bottom: 6px;
        }

        .item-qty, .item-price {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 4px;
            background: #e8f5e9;
            color: #2e7d32;
        }

        .btn-review {
            background: none;
            border: 1px solid #E50010;
            color: #E50010;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }
        .btn-review:hover {
            background: #E50010;
            color: #fff;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }

        .alert-msg {
            display: none;
            padding: 10px 14px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }
        .alert-msg.success { background: #e8f5e9; color: #2e7d32; }
        .alert-msg.error { background: #ffebee; color: #c62828; }

        /* â”€â”€ WELCOME BANNER â”€â”€ */
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

        /* â”€â”€ FOOTER â”€â”€ */
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

        /* â”€â”€ RESPONSIVE â”€â”€ */
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

        .field-input.error,
        .field-input.is-invalid {
            border-color: #E50010 !important;
        }

        .field-input.valid,
        .field-input.is-valid {
            border-color: #28a745 !important;
        }

        small.text-danger {
            color: #E50010;
            font-size: 11px;
            margin-top: 5px;
            display: block;
            font-weight: 400;
            letter-spacing: 0.3px;
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
                        <path d="M94.378.062c-1.39-.335-4.266.748-7.295 1.888-2.33.877-4.749 1.788-6.65 2.113-1.389.238-2.72 1.72-3.178 2.767a1250.033 1250.033 0 0 0-18.713 45.388 476.105 476.105 0 0 0-24.188 4.794c6.503-16.72 13.092-33.208 19.519-49.08 3.162-7.81-5.162-8.547-8.392-.63l-.206.503c-4.237 10.392-11.984 29.386-20.54 51.47A516.167 516.167 0 0 0 4.483 64.68c-5.146 1.486-5.368 2.857-3.14 5.944.689.955 1.776 1.326 2.805 1.677.823.281 1.61.55 2.125 1.094.646.682 1.236 1.392 1.819 2.093 2.132 2.563 4.16 5.001 8.383 5.452-4.803 12.82-9.594 26.039-13.933 38.768-2.724 7.99 5.039 9.625 8.021 1.171 4.808-13.629 9.883-27.425 15.086-41.179 3.688-.857 11.837-2.621 20.163-4.424l.005-.001 4.377-.948c-7.706 21.094-12.772 37.117-14.68 44.914-.323 1.324.114 2.128.436 2.719.043.079.083.154.12.226 1.246 1.816 2.463 2.795 3.763 3.841 1.406 1.131 2.907 2.339 4.647 4.768.91 1.275 3.94 1.962 4.978-1.175 7.127-21.543 14.46-41.755 21.305-59.876 2.841-.622 7.956-1.856 11.09-6.527 3.483-5.194 5.414-6.474 6.699-7.325.766-.508 1.302-.863 1.8-1.805 1.673-3.163.566-6.134-5.377-5.4 0 0-2.244.16-6.384.632 2.77-7.133 5.395-13.81 7.815-19.968v-.003c3.331-8.476 6.275-15.967 8.68-22.305 1.407-3.71 1.595-6.426-.708-6.98Z" fill="#E50010" />
                        <path d="M140.484 4.007c7.256-3.577 10.858-3.1 10.936.512.101 4.608-.566 10.686-1.06 15.187l-.03.274c-.899 8.195-2 15.89-3.081 23.444-2.157 15.077-4.233 29.59-4.461 46.388 11.859-30.703 21.808-52.042 34.61-78.329 3.133-6.437 5.391-6.997 7.787-7.592.717-.178 1.446-.359 2.215-.701 13.017-5.792 13.505-2.234 11.804 4.838-6.317 26.244-22.455 108.852-24.927 121.571-.717 3.68-4.71 2.121-5.753.681-2.057-2.843-4.229-4.444-5.957-5.718-2.165-1.596-3.634-2.679-3.309-5.049 2.904-21.207 13.357-74.414 16.082-86.953-13.902 28.484-28.308 64.09-35.704 84.278-1.572 4.287-4.426 3.973-6.206.836-.978-1.722-2.315-3.115-3.629-4.483-2.049-2.133-4.041-4.207-4.529-7.378-1.647-10.726.058-27.747 1.67-43.833.876-8.743 1.724-17.21 1.991-24.24-7.564 21.805-20.265 64.144-25.828 83.273-2.301 7.915-9.936 6.623-7.907-1.091 8.456-32.102 26.663-88.878 34.549-109.296 1.316-3.407 4.146-4.31 7.119-5.26 1.213-.388 2.45-.783 3.618-1.359Z" fill="#E50010" />
                        <path d="M85.55 97.56a42.278 42.278 0 0 1 1.561-1.44c3.569-3.093 6.977-.025 3.449 5.204a59.27 59.27 0 0 1-2.557 3.526c.446 1.271.844 2.365 1.16 3.176 1.825 4.678-2.966 5.851-4.51 1.976a88.444 88.444 0 0 1-.42-1.078c-2.913 2.58-6.28 4.204-9.88 3.085-5.92-1.842-7.427-10.178-1.899-16.6 2.218-2.577 3.887-4.365 5.282-5.793-.42-1.375-.76-2.528-.983-3.335-.718-2.6-1.366-5.63 1.236-8.719 4.88-5.79 16.2-.65 10.474 8.264-1.38 2.147-2.992 4.175-4.674 6.231a530.665 530.665 0 0 0 1.761 5.503Zm-7.04 1.149c-2.912 3.998-1.188 5.421.975 4.097a17.54 17.54 0 0 0 2.036-1.486 406.179 406.179 0 0 1-1.524-4.577 75.337 75.337 0 0 0-1.488 1.966Zm3.303-13.187a53.206 53.206 0 0 0 1.546-1.67c3.605-4.07-3.522-5.773-1.881.452.092.351.207.764.335 1.218Z" fill="#E50010" />
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

    <!-- Side Panel â€” Ladies -->
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

    <!-- Side Panel â€” Men -->
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

    <!-- Side Panel â€” Kids -->
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

        <h1 class="page-title">MY PROFILE</h1>

        <div class="welcome-banner" id="welcomeBanner">
            <i class="fas fa-check-circle"></i>
            <span>Welcome to H&amp;M! Your account has been created successfully.</span>
        </div>

        <div class="profile-layout">

            <!-- â•â• LEFT SIDEBAR â•â• -->
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
                    <a class="menu-item active" data-section="personal-detail">
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
                    <a class="menu-item" data-section="order-history">
                        <i class="fas fa-box"></i> Orders
                    </a>
                    <a class="menu-item" data-section="shipments">
                        <i class="fas fa-truck"></i> Shipment
                    </a>
                    <a class="menu-item" data-section="ratings">
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
            <!-- â•â• END SIDEBAR â•â• -->

            <!-- â•â• RIGHT CONTENT â•â• -->
            <div class="content-panel">

                <!-- â”€â”€ PERSONAL DETAIL SECTION (default) â”€â”€ -->
                <div class="content-section active" id="section-personal-detail">

                    <div class="section-heading">
                        <i class="fas fa-id-card"></i>
                        Personal Detail
                    </div>

                    <div class="avatar-section" style="border-bottom: none; padding-bottom: 0; margin-bottom: 24px;">
                        <div class="avatar-big" id="avatarBig"
                            onclick="document.getElementById('avatarFileInput').click()">
                            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? ''); ?>" alt="Profile"
                                id="avatarBigImg" <?php if (!empty($_SESSION['user_avatar'])): ?>style="display:block;"
                            <?php endif; ?>>
                            <span id="avatarBigInitial" <?php if (!empty($_SESSION['user_avatar'])): ?>style="display:none;"
                                <?php endif; ?>>
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Fields -->
                    <div class="fields-grid">

                        <div class="field-item">
                            <span class="field-lbl">Full Name</span>
                            <div class="field-val">
                                <?php
                                $name = trim($_SESSION['user_name'] ?? '');
                                echo $name !== '' ? htmlspecialchars($name) : '<span class="empty">Not provided</span>';
                                ?>
                            </div>
                        </div>

                        <div class="field-item">
                            <span class="field-lbl">Email Address</span>
                            <div class="field-val">
                                <?php
                                $email = trim($_SESSION['user_email'] ?? '');
                                echo $email !== '' ? htmlspecialchars($email) : '<span class="empty">Not provided</span>';
                                ?>
                            </div>
                        </div>

                        <div class="field-item">
                            <span class="field-lbl">Gender</span>
                            <div class="field-val">
                                <?php
                                $gender = trim($_SESSION['user_gender'] ?? '');
                                if ($gender !== ''):
                                    $icon = strtolower($gender) === 'female' ? 'fa-venus' : (strtolower($gender) === 'male' ? 'fa-mars' : 'fa-genderless');
                                    ?>
                                    <span class="gender-pill">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                        <?php echo htmlspecialchars($gender); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="empty">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="field-item">
                            <span class="field-lbl">Phone Number</span>
                            <div class="field-val">
                                <?php
                                $phone = trim($_SESSION['user_phone'] ?? '');
                                echo $phone !== '' ? htmlspecialchars($phone) : '<span class="empty">Not provided</span>';
                                ?>
                            </div>
                        </div>

                        <div class="field-item">
                            <span class="field-lbl">Birthday</span>
                            <div class="field-val">
                                <?php
                                $dob = trim($_SESSION['user_dob'] ?? '');
                                if ($dob !== ''):
                                    ?>
                                    <span class="bday">
                                        <i class="fas fa-birthday-cake"></i>
                                        <?php echo date('F j, Y', strtotime($dob)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="empty">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="field-item">
                            <span class="field-lbl">Password</span>
                            <div class="field-val pw">********</div>
                        </div>

                        <div class="field-item full">
                            <span class="field-lbl">Address</span>
                            <div class="field-val addr">
                                <?php
                                $address = trim($_SESSION['user_address'] ?? '');
                                echo $address !== '' ? nl2br(htmlspecialchars($address)) : '<span class="empty">Not provided</span>';
                                ?>
                            </div>
                        </div>

                    </div>



                    <!-- Edit Button -->
                    <div class="edit-row">
                        <button type="button" class="edit-btn" onclick="$('[data-section=edit-profile]').click()">
                            <i class="fas fa-pen"></i> Edit Profile
                        </button>
                    </div>

                </div>

                <!-- â”€â”€ EDIT PROFILE SECTION â”€â”€ -->
                <div class="content-section" id="section-edit-profile">
                    <div class="section-heading">
                        <i class="fas fa-pen"></i>
                        Edit Profile
                    </div>

                    <div class="alert-msg success" id="successMsgProfile">
                        <i class="fas fa-check-circle"></i>
                        <span>Profile updated successfully!</span>
                    </div>
                    <div class="alert-msg error" id="errorMsgProfile">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="errorTextProfile">Something went wrong.</span>
                    </div>

                    <form id="editProfileForm" method="POST" action="update-profile.php" enctype="multipart/form-data" novalidate>
                        <div class="avatar-section">
                            <div class="avatar-big" id="avatarEditBig" onclick="document.getElementById('avatarEditInput').click()">
                                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? ''); ?>" alt="Profile" id="avatarEditImg"
                                     <?php if (!empty($_SESSION['user_avatar'])): ?>style="display:block;"<?php endif; ?>>
                                <span id="avatarEditInitial" <?php if (!empty($_SESSION['user_avatar'])): ?>style="display:none;"<?php endif; ?>>
                                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                                </span>
                           
                            </div>
                            <div>
                                <div class="avatar-upload-label">Profile Picture</div>
                                <div class="avatar-upload-hint">JPG, PNG or GIF &middot; Max 2 MB</div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('avatarEditInput').click()">
                                    <i class="fas fa-upload"></i> Upload Photo
                                </button>
                                <input type="file" id="avatarEditInput" name="profile_picture" accept="image/*" style="display:none;"
                                       data-validation="filesize" data-filesize="2">
                                <small id="profile_picture_error" class="small text-danger" style="display:none;"></small>
                            </div>
                        </div>

                        <div class="fields-grid">
                            <div class="field-item">
                                <label class="field-lbl">Full Name <span class="req">*</span></label>
                                <input type="text" name="full_name" class="field-input" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                                       data-validation="required,alphabetic,min" data-min="3">
                                <small id="full_name_error" class="small text-danger" style="display:none;"></small>
                            </div>

                            <div class="field-item">
                                <label class="field-lbl">Email Address</label>
                                <input type="email" name="email" class="field-input" readonly value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" 
                                       style="background-color: #f5f5f5; color: #777 !important; cursor: not-allowed; border-bottom-color: #eee;">
                                <small style="color: #999; font-size: 11px; margin-top: 5px;">Email cannot be changed.</small>
                            </div>

                            <div class="field-item">
                                <span class="field-lbl">Gender</span>
                                <div class="gender-options">
                                    <?php $g = strtolower($_SESSION['user_gender'] ?? ''); ?>
                                    <label class="gender-opt <?php echo $g === 'male' ? 'selected' : ''; ?>">
                                        <input type="radio" name="gender" value="Male" <?php echo $g === 'male' ? 'checked' : ''; ?>>
                                        <i class="fas fa-mars"></i> Male
                                    </label>
                                    <label class="gender-opt <?php echo $g === 'female' ? 'selected' : ''; ?>">
                                        <input type="radio" name="gender" value="Female" <?php echo $g === 'female' ? 'checked' : ''; ?>>
                                        <i class="fas fa-venus"></i> Female
                                    </label>
                                    <label class="gender-opt <?php echo ($g !== 'male' && $g !== 'female' && $g !== '') ? 'selected' : ''; ?>">
                                        <input type="radio" name="gender" value="Other" <?php echo ($g !== 'male' && $g !== 'female' && $g !== '') ? 'checked' : ''; ?>>
                                        <i class="fas fa-genderless"></i> Other
                                    </label>
                                </div>
                            </div>
                            
                            <div class="field-item">
                                <label class="field-lbl">Phone Number</label>
                                <input type="tel" name="phone" class="field-input" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>"
                                       data-validation="number,min" data-min="10">
                                <small id="phone_error" class="small text-danger" style="display:none;"></small>
                            </div>
                            
                            <div class="field-item">
                                <label class="field-lbl">Birthday</label>
                                <input type="date" name="dob" class="field-input" value="<?php echo htmlspecialchars($_SESSION['user_dob'] ?? ''); ?>"
                                       data-validation="required">
                                <small id="dob_error" class="small text-danger" style="display:none;"></small>
                            </div>

                            <div class="field-item full">
                                <label class="field-lbl">Address <span class="req">*</span></label>
                                <textarea name="address" class="field-input" rows="3" style="resize:vertical;"
                                          data-validation="required,min" data-min="5"><?php echo htmlspecialchars($_SESSION['user_address'] ?? ''); ?></textarea>
                                <small id="address_error" class="small text-danger" style="display:none;"></small>
                            </div>
                        </div>

                        <div class="btn-row">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-check"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- â”€â”€ CHANGE PASSWORD SECTION â”€â”€ -->
                <div class="content-section" id="section-change-password">
                    <div class="section-heading">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </div>

                    <div class="alert-msg success" id="successMsgPass">
                        <i class="fas fa-check-circle"></i>
                        <span>Password updated successfully!</span>
                    </div>
                    <div class="alert-msg error" id="errorMsgPass">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="errorTextPass">Something went wrong.</span>
                    </div>

                    <form id="changePasswordForm" method="POST" action="update-password.php" novalidate>
                        <div class="fields-grid">
                            <div class="field-item full">
                                <label class="field-lbl">Current Password <span class="req">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="current_password" id="currPass" class="field-input" 
                                           data-validation="required">
                                    <button type="button" class="pw-toggle-btn" onclick="togglePass('currPass', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small id="current_password_error" class="small text-danger" style="display:none;"></small>
                            </div>
                            <div class="field-item full">
                                <label class="field-lbl">New Password <span class="req">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="new_password" id="newPass" class="field-input" 
                                           data-validation="required,strongpassword,confirmpassword">
                                    <button type="button" class="pw-toggle-btn" onclick="togglePass('newPass', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small id="new_password_error" class="small text-danger" style="display:none;"></small>
                            </div>
                            <div class="field-item full">
                                <label class="field-lbl">Confirm Password <span class="req">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="confirm_password" id="new_password_confirm" class="field-input" 
                                           data-validation="required">
                                    <button type="button" class="pw-toggle-btn" onclick="togglePass('new_password_confirm', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small id="confirm_password_error" class="small text-danger" style="display:none;"></small>
                            </div>
                        </div>

                        <div class="btn-row">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-lock"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- â”€â”€ ORDERS SECTION â”€â”€ -->
                <div class="content-section" id="section-order-history">
                    <div class="section-heading">
                        <i class="fas fa-box"></i>
                        Order History
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="content-placeholder">
                            <i class="fas fa-box-open"></i>
                            <p>You have not placed any orders yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-card-header">
                                    <div class="order-meta">
                                        <div class="meta-box">
                                            <span class="meta-lbl">Total Paid</span>
                                            <span class="meta-val">$<?php echo number_format($order['total'] ?? 0, 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="order-id-col">
                                        <span class="meta-lbl">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span><br>
                                        <span class="status-badge"><?php echo htmlspecialchars($order['payment_status'] ?? 'Completed'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="order-items-list">
                                    <?php if(!empty($order['items'])): foreach ($order['items'] as $item): ?>
                                    <div class="order-item-row">
                                        <div class="item-img-box">
                                            <img src="<?php echo htmlspecialchars($item['image'] ?? 'default.png'); ?>" alt="Product">
                                        </div>
                                        <div class="item-details">
                                            <div class="item-name"><?php echo htmlspecialchars($item['name'] ?? 'Product Name'); ?></div>
                                            <div class="item-qty">Qty: <?php echo htmlspecialchars($item['quantity'] ?? 1); ?></div>
                                            <div class="item-price">$<?php echo number_format($item['price'] ?? 0, 2); ?> each</div>
                                            <?php if ($order['status'] === 'Delivered'): ?>
                                            <button type="button" class="btn-review" onclick="openReviewModal(<?php echo (int)$item['product_id']; ?>, '<?php echo addslashes(htmlspecialchars($item['name'] ?? 'Product Name')); ?>')">
                                                <i class="fas fa-star"></i> Rate &amp; Review
                                            </button>
                                            <?php else: ?>
                                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-top:8px;">
                                                <i class="fas fa-lock"></i> Review available after delivery
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- â”€â”€ SHIPMENTS SECTION â”€â”€ -->
                <div class="content-section" id="section-shipments">
                    <div class="section-heading">
                        <i class="fas fa-truck"></i>
                        My Shipments Intransit
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="content-placeholder">
                            <i class="fas fa-box-open"></i>
                            <p>You have no active shipments.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 24px;">
                            <?php foreach ($orders as $order): ?>
                                <div style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 24px; background: #fff; position: relative; overflow: hidden;">
                                    <?php if ($order['status'] === 'Cancelled'): ?>
                                        <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #E50010;"></div>
                                    <?php else: ?>
                                        <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #E50010;"></div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                                        <div>
                                            <div style="font-size: 16px; font-weight: 700; color: #111;">Shipment #HM<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                            <div style="font-size: 13px; color: #666; margin-top: 4px;">Placed on <?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 14px; font-weight: 600; text-transform: uppercase;">
                                                <?php if ($order['status'] === 'Cancelled'): ?>
                                                    <span style="color: #E50010;"><i class="fas fa-times-circle"></i> Cancelled</span>
                                                <?php elseif ($order['status'] === 'Delivered'): ?>
                                                    <span style="color: #E50010;"><i class="fas fa-check-circle"></i> Delivered</span>
                                                <?php else: ?>
                                                    <span style="color: #E50010;"><i class="fas fa-shipping-fast"></i> <?php echo htmlspecialchars($order['status']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 13px; color: #555; margin-top: 4px;"><?php echo count($order['items']); ?> item(s) | $<?php echo number_format($order['total'], 2); ?></div>
                                        </div>
                                    </div>

                                    <?php if ($order['status'] !== 'Cancelled'): ?>
                                    <!-- Tracking Status Bar -->
                                    <div style="position: relative; margin: 30px 0 10px 0; display: flex; justify-content: space-between; align-items: center;">
                                        <!-- Line Background -->
                                        <div style="position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: #e0e0e0; transform: translateY(-50%); z-index: 1;"></div>
                                        
                                        <?php 
                                            // Determine Progress Width
                                            $progress = 0;
                                            if ($order['status'] === 'Pending') $progress = 0;
                                            if ($order['status'] === 'Processing') $progress = 50;
                                            if ($order['status'] === 'Delivered') $progress = 100;
                                        ?>
                                        <!-- Line Active -->
                                        <div style="position: absolute; top: 50%; left: 0; height: 3px; background: #E50010; transform: translateY(-50%); z-index: 2; width: <?php echo $progress; ?>%; transition: width 0.5s;"></div>

                                        <!-- Step 1: Pending -->
                                        <div style="position: relative; z-index: 3; text-align: center; background: #fff; padding: 0 10px;">
                                            <div style="width: 24px; height: 24px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff; background: #E50010; border: 2px solid #E50010;">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 8px; color: #222;">Pending</div>
                                        </div>

                                        <!-- Step 2: Processing -->
                                        <div style="position: relative; z-index: 3; text-align: center; background: #fff; padding: 0 10px;">
                                            <div style="width: 24px; height: 24px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 10px; background: <?php echo $progress >= 50 ? '#E50010' : '#fff'; ?>; color: <?php echo $progress >= 50 ? '#fff' : '#ccc'; ?>; border: 2px solid <?php echo $progress >= 50 ? '#E50010' : '#e0e0e0'; ?>;">
                                                <?php if($progress >= 50): ?><i class="fas fa-check"></i><?php else: ?><i class="fas fa-cog"></i><?php endif; ?>
                                            </div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 8px; color: <?php echo $progress >= 50 ? '#222' : '#999'; ?>;">Processing</div>
                                        </div>

                                        <!-- Step 3: Delivered -->
                                        <div style="position: relative; z-index: 3; text-align: center; background: #fff; padding: 0 10px;">
                                            <div style="width: 24px; height: 24px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 10px; background: <?php echo $progress === 100 ? '#E50010' : '#fff'; ?>; color: <?php echo $progress === 100 ? '#fff' : '#ccc'; ?>; border: 2px solid <?php echo $progress === 100 ? '#E50010' : '#e0e0e0'; ?>;">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 8px; color: <?php echo $progress === 100 ? '#222' : '#999'; ?>;">Delivered</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- â”€â”€ RATINGS SECTION â”€â”€ -->
                <div class="content-section" id="section-ratings">
                    <div class="section-heading">
                        <i class="fas fa-star"></i>
                        My Ratings &amp; Reviews
                    </div>

                    <?php if (empty($user_reviews)): ?>
                        <div class="content-placeholder">
                            <i class="fas fa-comment-slash"></i>
                            <p>You haven't reviewed any products yet.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <?php foreach ($user_reviews as $rev): ?>
                                <div style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; display: flex; gap: 20px; flex-wrap: wrap; background: #fff;">
                                    <div style="width: 80px; height: 100px; border: 1px solid #eee; background: #fafafa; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 4px; flex-shrink: 0;">
                                        <img src="<?php echo htmlspecialchars($rev['image'] ?? 'default.png'); ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;" alt="Product">
                                    </div>
                                    <div style="flex-grow: 1;">
                                        <div style="font-size: 16px; font-weight: 600; color: #111; margin-bottom: 6px;"><?php echo htmlspecialchars($rev['product_name']); ?></div>
                                        <div style="color: #ffc107; font-size: 14px; margin-bottom: 8px;">
                                            <?php
                                            $rating = (int)$rev['rating'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) echo '<i class="fas fa-star"></i>';
                                                else echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                            <span style="color: #666; font-size: 12px; margin-left: 8px;"><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($rev['created_at'])); ?></span>
                                        </div>
                                        <div style="font-size: 14px; color: #444; line-height: 1.5; background: #f9f9f9; border-left: 4px solid #e0e0e0; padding: 10px 14px; border-radius: 4px;">
                                            <?php echo nl2br(htmlspecialchars($rev['review_text'])); ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: flex-start; justify-content: flex-end;">
                                        <button type="button" class="btn-review" onclick="openReviewModal(<?php echo (int)$rev['product_id']; ?>, '<?php echo addslashes(htmlspecialchars($rev['product_name'])); ?>')" style="margin-top:0;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- â”€â”€ END PERSONAL DETAIL â”€â”€ -->

            </div>
            <!-- â•â• END RIGHT CONTENT â•â• -->

        </div>
    </div>

    <!-- REVIEW MODAL -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 0; border: 1px solid #e5e5e5;">
                <div class="modal-header" style="border-bottom: 1px solid #e5e5e5; background: #fafafa;">
                    <h5 class="modal-title" style="font-size: 16px; font-weight: 700; text-transform: uppercase;">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div id="reviewAlert" class="alert-msg">
                        <i class="fas fa-info-circle"></i> <span></span>
                    </div>
                    <form id="reviewForm">
                        <input type="hidden" name="product_id" id="reviewProductId">
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Product</label>
                            <div id="reviewProductName" style="font-size: 15px; font-weight: 600;"></div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Your Rating <span style="color:#E50010">*</span></label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Your Review <span style="color:#E50010">*</span></label>
                            <textarea name="review_text" rows="4" style="width: 100%; border: 1px solid #e0e0e0; padding: 12px; font-size: 14px; outline: none; transition: border 0.2s;" required placeholder="What did you like or dislike?"></textarea>
                        </div>

                        <button type="submit" class="btn-save" style="width: 100%; justify-content: center; margin: 0;">Submit Review</button>
                    </form>
                </div>
            </div>
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

            /* â”€â”€ Tab Switching â”€â”€ */
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

            /* â”€â”€ Avatar Edit Preview & Auto-Save â”€â”€ */
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
                
                // Auto-submit the form to ensure the picture is saved immediately
                $('#editProfileForm').submit();
            });

            /* â”€â”€ Gender Selection Logic â”€â”€ */
            $(document).on('click', '.gender-opt', function() {
                $(this).siblings().removeClass('selected');
                $(this).addClass('selected');
            });

            /* â”€â”€ Edit Profile AJAX â”€â”€ */
            $('#editProfileForm').on('submit', function (e) {
                e.preventDefault();

                var $form = $(this);
                var isValid = true;

                // Trigger validation on all fields
                $form.find('input, textarea, select').each(function() {
                    $(this).trigger('change');
                });

                // Check if any field is invalid
                if ($form.find('.is-invalid').length > 0) {
                    return false;
                }

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

            /* â”€â”€ Change Password AJAX â”€â”€ */
            $('#changePasswordForm').on('submit', function (e) {
                e.preventDefault();
                
                var $form = $(this);
                var isValid = true;

                // Trigger validation on all fields
                $form.find('input, textarea, select').each(function() {
                    $(this).trigger('change');
                });

                // Check if any field is invalid
                if ($form.find('.is-invalid').length > 0) {
                    return false;
                }

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
            // â”€â”€ Review Form Submission â”€â”€
            $('#reviewForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $(this).find('button[type="submit"]');
                const originalHtml = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);
                
                $.ajax({
                    url: 'submit_review.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        const $alert = $('#reviewAlert');
                        if (res.success) {
                            $alert.removeClass('error').addClass('success').html('<i class="fas fa-check-circle"></i> <span>' + res.message + '</span>').show();
                            setTimeout(function() {
                                bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                            }, 2000);
                        } else {
                            $alert.removeClass('success').addClass('error').html('<i class="fas fa-exclamation-circle"></i> <span>' + res.message + '</span>').show();
                            $btn.html(originalHtml).prop('disabled', false);
                        }
                    },
                    error: function() {
                        const $alert = $('#reviewAlert');
                        $alert.removeClass('success').addClass('error').html('<i class="fas fa-exclamation-circle"></i> <span>Network error. Please try again.</span>').show();
                        $btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

        });

        /* Global function to open Review Modal */
        function openReviewModal(productId, productName) {
            document.getElementById('reviewProductId').value = productId;
            document.getElementById('reviewProductName').textContent = productName;
            document.getElementById('reviewForm').reset();
            document.getElementById('reviewAlert').style.display = 'none';
            
            // Fetch existing review
            fetch('get_review.php?product_id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('input[name="rating"][value="' + data.rating + '"]').checked = true;
                        document.querySelector('textarea[name="review_text"]').value = data.review_text;
                    }
                })
                .catch(err => console.error('Error fetching review:', err));

            const modalElement = document.getElementById('reviewModal');
            // Check if instance exists
            let modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalElement);
            }
            modalInstance.show();
        }
    </script>
    <script src="js/form-validation.js"></script>
    <script src="autocomplete.js"></script>
</body>

</html>

