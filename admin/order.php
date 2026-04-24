<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

// Search & filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'ssss';
}
if ($status_filter !== '') {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM orders o";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY o.created_at DESC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $sql);
}

// Toast from session
$toast = '';
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-bar form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            flex: 1;
        }
        .filter-bar input[type="text"] {
            padding: 9px 14px;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            min-width: 220px;
            font-family: 'Inter', sans-serif;
        }
        .filter-bar input[type="text"]:focus { outline: none; border-color: #000; }
        .filter-bar select {
            padding: 9px 14px;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: #fff;
            cursor: pointer;
        }
        .filter-bar select:focus { outline: none; border-color: #000; }
        .filter-bar button {
            padding: 9px 20px;
            background: #000;
            color: #fff;
            border: none;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            font-weight: 600;
        }
        .filter-bar button:hover { background: #E50010; }
        .filter-bar a.clear-btn {
            padding: 9px 16px;
            background: #f5f5f5;
            color: #666;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
        }
        .filter-bar a.clear-btn:hover { background: #e0e0e0; color: #000; }
        .toast-msg {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #000;
            color: #fff;
            padding: 14px 24px;
            font-size: 14px;
            z-index: 9999;
            display: none;
            animation: fadeInUp 0.3s ease;
        }
        .toast-msg.show { display: block; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .order-id-badge {
            font-weight: 700;
            font-size: 13px;
            color: #000;
            letter-spacing: 0.5px;
        }
        .items-count {
            display: inline-block;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }
        .no-results td {
            padding: 60px 20px;
            text-align: center;
            color: #999;
            font-size: 15px;
        }
        .no-results td i { font-size: 40px; display: block; margin-bottom: 12px; color: #ddd; }
        .results-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Toast notification -->
    <?php if ($toast): ?>
    <div class="toast-msg show" id="toastMsg"><?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Order Management</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Orders</h2>
            </div>

            <?php
            $total_rows = $result ? mysqli_num_rows($result) : 0;
            ?>

            <div class="table-responsive-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Items</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th style="white-space:nowrap; width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_rows > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    $statusClass = 'badge-pending';
                                    if ($row['status'] === 'Processing') $statusClass = 'badge-processing';
                                    elseif ($row['status'] === 'Delivered') $statusClass = 'badge-delivered';
                                    elseif ($row['status'] === 'Cancelled') $statusClass = 'badge-cancelled';
                                ?>
                                <tr>
                                    <td><span class="order-id-badge">#HM<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></span></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><span class="items-count"><?= (int)$row['item_count'] ?> item<?= $row['item_count'] != 1 ? 's' : '' ?></span></td>
                                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                    <td><strong>$<?= number_format($row['total'], 2) ?></strong></td>
                                    <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="view_order.php?id=<?= $row['id'] ?>" class="action-btn view" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_order.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Order">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_order.php?id=<?= $row['id'] ?>"
                                               class="action-btn delete"
                                               title="Delete Order"
                                               onclick="return confirm('Delete order #HM<?= str_pad($row['id'],6,'0',STR_PAD_LEFT) ?>? This cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="no-results">
                                <td colspan="8">
                                    <i class="fas fa-shopping-cart"></i>
                                    No orders found<?= ($search || $status_filter) ? ' matching your filter' : '' ?>.
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
    <script>
        // Auto-hide toast
        const toast = document.getElementById('toastMsg');
        if (toast) {
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; }, 3000);
            setTimeout(() => { toast.style.display = 'none'; }, 3500);
        }
    </script>
</body>
</html>