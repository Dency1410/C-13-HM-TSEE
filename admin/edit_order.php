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

// Fetch order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image
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

$success = '';
$error   = '';

// Handle Status-only Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = trim($_POST['status'] ?? '');
    $allowed    = ['Pending', 'Processing', 'Delivered', 'Cancelled'];

    if (!in_array($new_status, $allowed)) {
        $error = 'Invalid status selected.';
    } else {
        $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $upd->bind_param("si", $new_status, $id);
        if ($upd->execute()) {
            $order['status'] = $new_status;
            $_SESSION['toast'] = 'Order #HM' . str_pad($id, 6, '0', STR_PAD_LEFT) . ' status updated to ' . $new_status . '.';
            header("Location: order.php");
            exit();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #HM<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?> - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 32px;
            margin-bottom: 24px;
        }
        .form-card-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #000;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 2px solid #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-card-title i { color: #E50010; font-size: 15px; }

        /* Meta chips */
        .order-meta { display: flex; gap: 14px; flex-wrap: wrap; }
        .meta-chip {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
        }
        .meta-chip span { color: #000; font-weight: 700; }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 18px;
        }
        .info-item label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 4px;
        }
        .info-item p {
            font-size: 14px;
            font-weight: 500;
            color: #111;
            margin: 0;
            line-height: 1.4;
        }

        /* Status select */
        .status-select-wrap { max-width: 280px; }
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
            margin-bottom: 8px;
        }
        .field-input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e0e0e0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #000;
            background: #fff;
            transition: border-color 0.2s;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
        }
        .field-input:focus { outline: none; border-color: #000; }
        select.field-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23333' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 38px;
        }

        /* Status badges */
        .badge-status {
            padding: 5px 14px; font-size: 11px; font-weight: 700;
            display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-pending    { background: #ffc107; color: #000; }
        .badge-processing { background: #17a2b8; color: #fff; }
        .badge-delivered  { background: #28a745; color: #fff; }
        .badge-cancelled  { background: #E50010; color: #fff; }

        /* Items table (read-only) */
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table thead th {
            background: #000; color: #fff;
            padding: 10px 14px;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            text-align: left;
        }
        .items-table tbody tr { border-bottom: 1px solid #e0e0e0; }
        .items-table tbody td { padding: 12px 14px; font-size: 14px; color: #333; vertical-align: middle; }
        .items-table tfoot td {
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            border-top: 2px solid #000;
        }
        .item-product-name { font-weight: 600; color: #111; }

        /* Totals */
        .totals-box { background: #f9f9f9; border: 1px solid #e0e0e0; padding: 20px 24px; }
        .totals-row { display: flex; justify-content: space-between; font-size: 14px; color: #555; margin-bottom: 10px; }
        .totals-row.grand-total {
            font-size: 17px; font-weight: 700; color: #000;
            border-top: 2px solid #000; padding-top: 12px; margin-top: 6px; margin-bottom: 0;
        }

        /* Action row */
        .action-row { display: flex; gap: 14px; align-items: center; margin-top: 4px; }
        .btn-submit {
            background: #000; color: #fff; border: none;
            padding: 13px 36px; font-size: 13px;
            font-family: 'Inter', sans-serif; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            cursor: pointer; transition: background 0.2s;
        }
        .btn-submit:hover { background: #E50010; }
        .btn-cancel {
            color: #666; font-size: 14px; text-decoration: none;
            padding: 13px 20px; border: 1px solid #e0e0e0;
            display: inline-block; transition: all 0.2s;
        }
        .btn-cancel:hover { background: #f5f5f5; color: #000; }

        /* Alert */
        .alert-error {
            background: #fff5f5; border-left: 4px solid #E50010;
            padding: 14px 18px; margin-bottom: 20px;
            font-size: 13px; color: #c00;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Edit Order <span style="color:#E50010;">#HM<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span></h1>
            <?php include 'header.php'; ?>
        </div>

        <div style="max-width: 860px;">

            <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ── Order Meta ── -->
            <div class="form-card">
                <div class="order-meta">
                    <div class="meta-chip">Order ID: <span>#HM<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span></div>
                    <div class="meta-chip">Placed: <span><?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?></span></div>
                    <div class="meta-chip">Items: <span><?= count($order_items) ?></span></div>
                    <?php
                        $sc = 'badge-pending';
                        if ($order['status'] === 'Processing') $sc = 'badge-processing';
                        elseif ($order['status'] === 'Delivered') $sc = 'badge-delivered';
                        elseif ($order['status'] === 'Cancelled') $sc = 'badge-cancelled';
                    ?>
                    <div class="meta-chip">Status: <span class="badge-status <?= $sc ?>"><?= htmlspecialchars($order['status']) ?></span></div>
                </div>
            </div>

            <!-- ── Update Status (ONLY editable field) ── -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-edit"></i> Update Order Status
                </div>
                <form method="POST" id="statusForm">
                    <div class="status-select-wrap">
                        <label class="field-label" for="statusSelect">Order Status *</label>
                        <select name="status" id="statusSelect" class="field-input">
                            <?php foreach (['Pending', 'Processing', 'Delivered', 'Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="action-row" style="margin-top: 20px;">
                        <button type="submit" name="update_status" class="btn-submit">
                            <i class="fas fa-save"></i> Save Status
                        </button>
                        <a href="order.php" class="btn-cancel">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </form>
            </div>

            <!-- ── Customer Info (read-only) ── -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-user"></i> Customer Information
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?= htmlspecialchars($order['email']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <p><?= htmlspecialchars($order['phone']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Address</label>
                        <p><?= htmlspecialchars($order['address']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>City</label>
                        <p><?= htmlspecialchars($order['city']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>ZIP / PIN</label>
                        <p><?= htmlspecialchars($order['zip_code']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>State</label>
                        <p><?= htmlspecialchars($order['state']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Country</label>
                        <p><?= htmlspecialchars($order['country']) ?></p>
                    </div>
                </div>
            </div>

            <!-- ── Order Items (read-only) ── -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-shopping-bag"></i> Order Items
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="width:110px; text-align:right;">Unit Price</th>
                            <th style="width:80px; text-align:center;">Qty</th>
                            <th style="width:110px; text-align:right;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="item-product-name"><?= htmlspecialchars($item['product_name'] ?? 'Deleted Product') ?></td>
                            <td style="text-align:right;">₹<?= number_format($item['price'], 2) ?></td>
                            <td style="text-align:center;"><?= (int)$item['quantity'] ?></td>
                            <td style="text-align:right; font-weight:600;">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ── Order Totals (read-only) ── -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-calculator"></i> Order Totals
                </div>
                <div class="totals-box">
                    <div class="totals-row"><span>Subtotal</span><span>₹<?= number_format($order['subtotal'], 2) ?></span></div>
                    <div class="totals-row"><span>Tax</span><span>₹<?= number_format($order['tax'], 2) ?></span></div>
                    <div class="totals-row"><span>Shipping</span><span>₹<?= number_format($order['shipping'], 2) ?></span></div>
                    <div class="totals-row grand-total"><span>Grand Total</span><span>₹<?= number_format($order['total'], 2) ?></span></div>
                </div>
            </div>

        </div><!-- /max-width -->
    </div><!-- /main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
