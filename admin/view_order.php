<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: order.php");
    exit();
}

// Fetch order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    echo "<p style='padding:40px;font-family:Inter,sans-serif;'>Order not found.</p>";
    exit();
}

// Fetch order items with product info
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$items_stmt->bind_param("i", $id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];
while ($r = $items_result->fetch_assoc()) {
    $order_items[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #HM<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?> - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .view-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 32px;
            margin-bottom: 28px;
            border-radius: 6px;
        }
        .view-card-title {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #000;
            margin-bottom: 24px;
            padding-bottom: 14px;
            border-bottom: 2px solid #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .view-card-title i { color: #E50010; font-size: 16px; }

        .order-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .meta-chip {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-radius: 4px;
        }
        .meta-chip span { color: #000; font-weight: 700; margin-left: 5px; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-item {
            margin-bottom: 16px;
        }
        .info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #777;
            margin-bottom: 6px;
            display: block;
        }
        .info-val {
            font-size: 15px;
            color: #222;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .order-item-row {
            display: flex;
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }
        .order-item-row:last-child { border-bottom: none; }
        
        .item-img-box {
            width: 90px;
            height: 110px;
            flex-shrink: 0;
            margin-right: 24px;
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
        }
        .item-img-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .item-details { flex-grow: 1; }
        .item-name {
            font-size: 16px;
            font-weight: 600;
            color: #222;
            margin-bottom: 8px;
        }
        .item-meta-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }
        .item-price-total {
            font-weight: 700;
            color: #000;
            font-size: 14px;
            text-align: right;
            min-width: 100px;
        }

        .totals-box { background: #f9f9f9; border: 1px solid #e0e0e0; padding: 24px; border-radius: 6px; }
        .totals-row { display: flex; justify-content: space-between; font-size: 15px; color: #555; margin-bottom: 12px; }
        .totals-row.grand-total {
            font-size: 18px; font-weight: 800; color: #000;
            border-top: 2px solid #000; padding-top: 14px; margin-top: 8px; margin-bottom: 0;
        }

        .badge-status {
            padding: 6px 14px; font-size: 12px; font-weight: 700;
            display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;
            border-radius: 4px;
        }
        .badge-pending    { background: #ffc107; color: #000; }
        .badge-processing { background: #17a2b8; color: #fff; }
        .badge-delivered  { background: #28a745; color: #fff; }
        .badge-cancelled  { background: #E50010; color: #fff; }

        .btn-cancel {
            color: #666; font-size: 14px; text-decoration: none; font-weight: 600;
            padding: 13px 24px; border: 1px solid #e0e0e0; border-radius: 4px;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: #fff;
        }
        .btn-cancel:hover { background: #f5f5f5; color: #000; }
        
        .btn-edit {
            color: #fff; font-size: 14px; text-decoration: none; font-weight: 600;
            padding: 13px 24px; border: none; border-radius: 4px;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: #000; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-edit:hover { background: #E50010; color: #fff; }

        .action-row { display: flex; gap: 14px; align-items: center; margin-top: 20px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>View Order <span style="color:#E50010;">#HM<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span></h1>
            <?php include 'header.php'; ?>
        </div>

        <div style="max-width: 960px;">

            <!-- Order Meta -->
            <div class="view-card" style="margin-bottom: 24px;">
                <div class="order-meta">
                    <div class="meta-chip">Order ID<span>#HM<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span></div>
                    <div class="meta-chip">Placed<span><?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?></span></div>
                    <div class="meta-chip">Items<span><?= count($order_items) ?></span></div>
                    <?php
                        $sc = 'badge-pending';
                        if ($order['status']==='Processing') $sc='badge-processing';
                        elseif ($order['status']==='Delivered') $sc='badge-delivered';
                        elseif ($order['status']==='Cancelled') $sc='badge-cancelled';
                    ?>
                    <div class="meta-chip">Status<span class="badge-status <?= $sc ?>" style="margin-left: 10px;"><?= htmlspecialchars($order['status']) ?></span></div>
                </div>
            </div>

            <!-- Customer & Shipping Info -->
            <div class="view-card">
                <div class="view-card-title">
                    <i class="fas fa-file-invoice"></i> Order Details
                </div>
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <span class="info-label">Customer Name</span>
                            <span class="info-val"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contact</span>
                            <span class="info-val"><?= htmlspecialchars($order['email']) ?><br><?= htmlspecialchars($order['phone']) ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">Shipping Address</span>
                            <span class="info-val">
                                <?= htmlspecialchars($order['address']) ?><br>
                                <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> <?= htmlspecialchars($order['zip_code']) ?><br>
                                <?= htmlspecialchars($order['country']) ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment ID</span>
                            <span class="info-val" style="font-family: monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">
                                <?= htmlspecialchars($order['razorpay_payment_id'] ?? 'N/A') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items List -->
            <div class="view-card">
                <div class="view-card-title">
                    <i class="fas fa-shopping-bag"></i> Purchased Products
                </div>
                <div class="order-items-list" style="margin-top: -10px;">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item-row">
                            <div class="item-img-box">
                                <img src="<?= htmlspecialchars($item['image'] ?? 'default.png') ?>" alt="Product">
                            </div>
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></div>
                                <div class="item-meta-info">Unit Price: $<?= number_format($item['price'], 2) ?></div>
                                <div class="item-meta-info">Quantity: <?= (int)$item['quantity'] ?></div>
                            </div>
                            <div class="item-price-total">
                                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Totals -->
            <div class="view-card" style="margin-bottom: 10px;">
                <div class="view-card-title">
                    <i class="fas fa-calculator"></i> Order Summary
                </div>
                <div class="totals-box">
                    <div class="totals-row"><span>Subtotal</span><span>$<?= number_format($order['subtotal'], 2) ?></span></div>
                    <div class="totals-row"><span>Tax (8%)</span><span>$<?= number_format($order['tax'], 2) ?></span></div>
                    <div class="totals-row"><span>Shipping</span><span>$<?= number_format($order['shipping'], 2) ?></span></div>
                    <div class="totals-row grand-total"><span>Grand Total</span><span>$<?= number_format($order['total'], 2) ?></span></div>
                </div>
            </div>

            <div class="action-row">
                <a href="order.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn-edit">
                    <i class="fas fa-edit"></i> Edit Order
                </a>
            </div>

        </div><!-- /max-width -->
    </div><!-- /main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
