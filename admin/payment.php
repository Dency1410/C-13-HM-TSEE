<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

// Fetch all payment transactions from orders table
$query = "SELECT id, first_name, last_name, total, razorpay_payment_id, payment_status, created_at FROM orders WHERE razorpay_payment_id IS NOT NULL AND razorpay_payment_id != '' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .badge-status {
            padding: 5px 14px; font-size: 11px; font-weight: 700;
            display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 4px;
        }
        .badge-pending    { background: #ffc107; color: #000; }
        .badge-processing { background: #17a2b8; color: #fff; }
        .badge-delivered  { background: #28a745; color: #fff; }
        .badge-cancelled  { background: #E50010; color: #fff; }
        
        .no-results td {
            padding: 60px 20px;
            text-align: center;
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
            <h1>Payment Management</h1>
            <?php include 'header.php'?>
        </div>
        <div class="users-section">
            <div class="section-header">
                <h2>Payment Transactions</h2>
            </div>
            
            <div class="table-responsive-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="paymentTableBody">
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    $statusClass = 'badge-pending';
                                    if ($row['payment_status'] === 'Success' || $row['payment_status'] === 'Completed') $statusClass = 'badge-delivered';
                                    elseif ($row['payment_status'] === 'Failed' || $row['payment_status'] === 'Cancelled') $statusClass = 'badge-cancelled';
                                ?>
                                <tr>
                                    <td><strong style="font-family: monospace; background: #f5f5f5; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($row['razorpay_payment_id']) ?></strong></td>
                                    <td><a href="view_order.php?id=<?= $row['id'] ?>" style="color: #E50010; font-weight: 600; text-decoration: none;">#HM<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></a></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><strong>$<?= number_format($row['total'], 2) ?></strong></td>
                                    <td>Razorpay</td>
                                    <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($row['payment_status']) ?></span></td>
                                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?= $row['id'] ?>" class="action-btn view" title="View Order" style="color: #0d9488; background: #e6f6f5; padding: 6px 10px; border-radius: 4px; border: 1px solid #b2dfdb; display: inline-block;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="no-results">
                                <td colspan="8">
                                    <i class="fas fa-receipt"></i>
                                    No payment transactions found.
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
    <!-- Assuming initializeSearch exists in script.js, but if we don't have a searchInput we shouldn't call it blindly -->
</body>
</html>