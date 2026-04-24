<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

// Fetch active carts grouped by user
$query = "
    SELECT 
        c.user_id, 
        u.full_name as user_name,
        COUNT(c.id) as total_items, 
        SUM(p.price * c.quantity) as subtotal
    FROM cart c 
    JOIN users u ON c.user_id = u.id 
    JOIN products p ON c.product_id = p.id 
    GROUP BY c.user_id, u.full_name
    ORDER BY subtotal DESC
";
$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Page Layout ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            margin: 0;
        }

        /* ── Top Bar ── */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px 30px;
            background: #fff;
            border-bottom: 1px solid #eee;
        }

        .top-bar h1 {
            font-size: clamp(18px, 3vw, 26px);
            font-weight: 700;
            color: #111;
            margin: 0;
        }

        /* ── Users / Cart Section ── */
        .users-section {
            padding: 24px 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-header h2 {
            font-size: clamp(16px, 2.5vw, 20px);
            font-weight: 600;
            color: #111;
            margin: 0;
        }

        /* ── Table Wrapper (horizontal scroll on small screens) ── */
        .table-wrapper {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.07);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ── Custom Table ── */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 480px; /* prevents columns from collapsing too small */
        }

        .custom-table thead {
            background: #111;
        }

        .custom-table thead th {
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 18px;
            white-space: nowrap;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.15s ease;
        }

        .custom-table tbody tr:last-child {
            border-bottom: none;
        }

        .custom-table tbody tr:hover {
            background: #fafafa;
        }

        .custom-table tbody td {
            padding: 14px 18px;
            font-size: 14px;
            color: #444;
            vertical-align: middle;
            white-space: nowrap;
        }

        .custom-table tbody td strong {
            color: #111;
            font-weight: 600;
        }

        /* ── Coupon Badge ── */
        .coupon-badge {
            display: inline-block;
            background: #fff3f3;
            color: #E50914;
            border: 1px solid #ffd0d0;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        /* ── Discount Chip ── */
        .discount-chip {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            font-size: 13px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ── Card Layout for very small screens ── */
        @media (max-width: 560px) {
            .users-section {
                padding: 16px;
            }

            .top-bar {
                padding: 14px 16px;
            }

            /* Switch to card layout on very small screens */
            .table-wrapper {
                box-shadow: none;
                background: transparent;
                overflow-x: visible;
            }

            .custom-table {
                display: block;
                min-width: unset;
            }

            .custom-table thead {
                display: none; /* hide header row */
            }

            .custom-table tbody {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .custom-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 1px 6px rgba(0,0,0,0.07);
                border-bottom: none;
                padding: 4px 0;
                overflow: hidden;
            }

            .custom-table tbody tr:hover {
                background: #fff;
            }

            .custom-table tbody td {
                white-space: normal;
                position: relative;
                padding: 10px 14px;
                font-size: 13px;
                border-bottom: 1px solid #f5f5f5;
            }

            /* data-label shows column name above value */
            .custom-table tbody td::before {
                content: attr(data-label);
                display: block;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.6px;
                color: #999;
                margin-bottom: 3px;
            }

            /* User cell spans full width */
            .custom-table tbody td:first-child {
                grid-column: 1 / -1;
                background: #111;
                color: #fff !important;
                border-radius: 10px 10px 0 0;
            }

            .custom-table tbody td:first-child strong {
                color: #fff;
                font-size: 14px;
            }

            .custom-table tbody td:first-child::before {
                color: #888;
            }
        }

        /* ── Tablet ── */
        @media (max-width: 768px) {
            .main-content {
                padding-top: 70px; /* room for toggle button */
            }
        }
        
        .no-results td {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 15px;
        }
        .no-results td i { font-size: 40px; display: block; margin-bottom: 12px; color: #ddd; }
    </style>
</head>

<body>
    <?php include 'sidebar.php' ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Shopping Cart Management</h1>
            <?php include 'header.php' ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>Active Carts</h2>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Items</th>
                            <th>Subtotal</th>
                            <th>Applied Coupon</th>
                            <th>Discount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td data-label="User"><strong><?= htmlspecialchars($row['user_name'] ?? 'Unknown User') ?></strong></td>
                                <td data-label="Items"><?= (int)$row['total_items'] ?> item<?= $row['total_items'] > 1 ? 's' : '' ?></td>
                                <td data-label="Subtotal">$<?= number_format($row['subtotal'], 2) ?></td>
                                <td data-label="Applied Coupon"><span style="color:#aaa; font-style:italic;">None</span></td>
                                <td data-label="Discount"><span style="color:#aaa; font-style:italic;">N/A</span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="no-results">
                                <td colspan="5">
                                    <i class="fas fa-shopping-cart"></i>
                                    No active carts currently.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>