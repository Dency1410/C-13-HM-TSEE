<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

//  Stat Cards 
$total_users    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role != 'admin'"))[0];
$total_orders   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders"))[0];
$total_products = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products"))[0];
$total_revenue  = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'Cancelled'"))[0];

// Month-over-month growth helpers
$this_month  = date('Y-m');
$last_month  = date('Y-m', strtotime('-1 month'));

$users_this  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at,'%Y-%m')='$this_month' AND role != 'admin'"))[0];
$users_last  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at,'%Y-%m')='$last_month' AND role != 'admin'"))[0];

$orders_this = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$this_month'"))[0];
$orders_last = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$last_month'"))[0];

$rev_this    = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='Cancelled' AND DATE_FORMAT(created_at,'%Y-%m')='$this_month'"))[0];
$rev_last    = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='Cancelled' AND DATE_FORMAT(created_at,'%Y-%m')='$last_month'"))[0];

function growth($curr, $prev) {
    if ($prev == 0) return $curr > 0 ? ['up', '+100%'] : ['flat', '-'];
    $pct = round((($curr - $prev) / $prev) * 100, 1);
    return $pct >= 0 ? ['up', "+{$pct}%"] : ['down', "{$pct}%"];
}
[$ug, $up] = growth($users_this, $users_last);
[$og, $op] = growth($orders_this, $orders_last);
[$rg, $rp] = growth($rev_this, $rev_last);

//  Order Status Breakdown 
$status_res = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
$status_counts = ['Pending'=>0,'Processing'=>0,'Delivered'=>0,'Cancelled'=>0];
while ($s = mysqli_fetch_assoc($status_res)) {
    $status_counts[$s['status']] = (int)$s['cnt'];
}

//  Recent Orders (last 7) 
$recent_orders = mysqli_query($conn, "
    SELECT o.id, o.first_name, o.last_name, o.email, o.total, o.status, o.created_at,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 7
");

//  Recent Users (last 5) 
$recent_users = mysqli_query($conn, "
    SELECT id, full_name, email, created_at, profile_photo
    FROM users
    WHERE role != 'admin'
    ORDER BY created_at DESC
    LIMIT 5
");

//  Low Stock Products (stock < 5, or just latest 5 if no stock col) 
$low_stock = mysqli_query($conn, "
    SELECT id, name, price, image
    FROM products
    ORDER BY created_at DESC
    LIMIT 5
");

//  Recent Reviews 
$recent_reviews = mysqli_query($conn, "
    SELECT r.rating, r.review_text, r.created_at, u.full_name AS user_name, p.name AS product_name
    FROM product_reviews r
    LEFT JOIN users u ON u.id = r.user_id
    LEFT JOIN products p ON p.id = r.product_id
    ORDER BY r.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /*  Stat Cards  */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px,1fr)); gap: 20px; margin-bottom: 28px; }
        .stat-card { background:#fff; border:1px solid #e0e0e0; padding:26px; position:relative; transition:.2s; }
        .stat-card:hover { border-color:#000; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .stat-icon { width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:20px;background:#000;color:#fff;margin-bottom:14px; }
        .stat-card p { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#999;margin:0 0 6px; }
        .stat-card h3 { font-size:30px;font-weight:700;color:#000;margin:0 0 8px; }
        .stat-trend { display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;background:#f5f5f5; }
        .stat-trend.up   { color:#16a34a; }
        .stat-trend.down { color:#E50010; }
        .stat-trend.flat { color:#999; }

        /*  Status Breakdown  */
        .status-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px; margin-bottom:28px; }
        .status-chip { padding:16px 20px; border:1px solid #e0e0e0; text-align:center; }
        .status-chip .chip-num { font-size:26px;font-weight:700;color:#000;line-height:1; }
        .status-chip .chip-lbl { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-top:5px;color:#666; }
        .chip-pending   .chip-num { color:#d97706; }
        .chip-processing .chip-num{ color:#0891b2; }
        .chip-delivered .chip-num { color:#16a34a; }
        .chip-cancelled .chip-num { color:#E50010; }

        /*  Two-col grid  */
        .dash-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }
        @media(max-width:900px){ .dash-grid{grid-template-columns:1fr;} }

        /*  Section card  */
        .dash-card { background:#fff; border:1px solid #e0e0e0; padding:24px; }
        .dash-card-title { font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:#000;margin:0 0 18px;padding-bottom:14px;border-bottom:2px solid #000;display:flex;align-items:center;gap:8px; }
        .dash-card-title i { color:#E50010; }

        /*  Mini table  */
        .mini-table { width:100%;border-collapse:collapse; }
        .mini-table th { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#999;padding:7px 10px;text-align:left;border-bottom:1px solid #e0e0e0; }
        .mini-table td { font-size:13px;color:#333;padding:10px 10px;border-bottom:1px solid #f0f0f0;vertical-align:middle; }
        .mini-table tr:last-child td { border-bottom:none; }
        .mini-table tr:hover td { background:#fafafa; }

        /*  Status badges (mini)  */
        .badge-sm { padding:3px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;display:inline-block; }
        .bs-pending    { background:#fef3c7;color:#d97706; }
        .bs-processing { background:#cffafe;color:#0891b2; }
        .bs-delivered  { background:#dcfce7;color:#16a34a; }
        .bs-cancelled  { background:#fee2e2;color:#E50010; }

        /*  Star rating  */
        .stars { color:#f59e0b;font-size:12px; }

        /*  User row  */
        .user-row-avatar { width:34px;height:34px;border-radius:50%;object-fit:cover;border:1.5px solid #e0e0e0; }
        .user-initial { width:34px;height:34px;border-radius:50%;background:#E50010;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0; }

        /*  Product thumbnail  */
        .prod-thumb { width:38px;height:38px;object-fit:cover;border:1px solid #e0e0e0; }

        /*  View all link  */
        .view-all { display:block;text-align:center;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:#666;text-decoration:none;margin-top:14px;padding-top:12px;border-top:1px solid #e0e0e0; }
        .view-all:hover { color:#000; }

        .order-id-badge { font-weight:700;color:#000;font-size:12px;letter-spacing:.4px; }

        /*  Revenue format  */
        .rev-val { font-size:28px;font-weight:700;color:#000;margin:0 0 8px;word-break:break-all; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Dashboard</h1>
            <?php include 'header.php'; ?>
        </div>

        <!--  Stat Cards  -->
        <div class="stats-grid">
            <!-- Total Users -->
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <p>Total Users</p>
                <h3><?= number_format($total_users) ?></h3>
                <span class="stat-trend <?= $ug ?>">
                    <i class="fas fa-arrow-<?= $ug === 'up' ? 'up' : ($ug === 'down' ? 'down' : 'right') ?>"></i>
                    <?= $up ?> this month
                </span>
            </div>

            <!-- Total Orders -->
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <p>Total Orders</p>
                <h3><?= number_format($total_orders) ?></h3>
                <span class="stat-trend <?= $og ?>">
                    <i class="fas fa-arrow-<?= $og === 'up' ? 'up' : ($og === 'down' ? 'down' : 'right') ?>"></i>
                    <?= $op ?> this month
                </span>
            </div>

            <!-- Total Products -->
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <p>Total Products</p>
                <h3><?= number_format($total_products) ?></h3>
                <span class="stat-trend flat"><i class="fas fa-layer-group"></i> In catalogue</span>
            </div>

            <!-- Revenue -->
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <p>Revenue (excl. cancelled)</p>
                <?php
                    if ($total_revenue >= 100000)      echo '<div class="rev-val">$' . number_format($total_revenue/100000,2) . 'L</div>';
                    elseif ($total_revenue >= 1000)    echo '<div class="rev-val">$' . number_format($total_revenue/1000,1) . 'K</div>';
                    else                               echo '<div class="rev-val">$' . number_format($total_revenue,2) . '</div>';
                ?>
                <span class="stat-trend <?= $rg ?>">
                    <i class="fas fa-arrow-<?= $rg === 'up' ? 'up' : ($rg === 'down' ? 'down' : 'right') ?>"></i>
                    <?= $rp ?> this month
                </span>
            </div>
        </div>

        <!--  Order Status Breakdown  -->
        <div class="status-row">
            <div class="status-chip chip-pending">
                <div class="chip-num"><?= $status_counts['Pending'] ?></div>
                <div class="chip-lbl"><i class="fas fa-clock"></i> Pending</div>
            </div>
            <div class="status-chip chip-processing">
                <div class="chip-num"><?= $status_counts['Processing'] ?></div>
                <div class="chip-lbl"><i class="fas fa-cog"></i> Processing</div>
            </div>
            <div class="status-chip chip-delivered">
                <div class="chip-num"><?= $status_counts['Delivered'] ?></div>
                <div class="chip-lbl"><i class="fas fa-check-circle"></i> Delivered</div>
            </div>
            <div class="status-chip chip-cancelled">
                <div class="chip-num"><?= $status_counts['Cancelled'] ?></div>
                <div class="chip-lbl"><i class="fas fa-times-circle"></i> Cancelled</div>
            </div>
        </div>

        <!--  Recent Orders + Recent Users  -->
        <div class="dash-grid">

            <!-- Recent Orders -->
            <div class="dash-card">
                <div class="dash-card-title"><i class="fas fa-shopping-cart"></i> Recent Orders</div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_orders)):
                            $bsc = [
                                'Pending'    => 'bs-pending',
                                'Processing' => 'bs-processing',
                                'Delivered'  => 'bs-delivered',
                                'Cancelled'  => 'bs-cancelled',
                            ][$row['status']] ?? 'bs-pending';
                        ?>
                        <tr>
                            <td><span class="order-id-badge">#HM<?= str_pad($row['id'],6,'0',STR_PAD_LEFT) ?></span></td>
                            <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
                            <td style="font-weight:600;">$<?= number_format($row['total'],2) ?></td>
                            <td><span class="badge-sm <?= $bsc ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="order.php" class="view-all"><i class="fas fa-arrow-right"></i> View All Orders</a>
            </div>

            <!-- Recent Users -->
            <div class="dash-card">
                <div class="dash-card-title"><i class="fas fa-users"></i> Recent Customers</div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($recent_users)): ?>
                        <tr>
                            <td style="width:40px;">
                                <?php if (!empty($u['profile_photo'])): ?>
                                    <img src="../<?= htmlspecialchars($u['profile_photo']) ?>" class="user-row-avatar" alt="">
                                <?php else: ?>
                                    <span class="user-initial"><?= strtoupper(substr($u['full_name'],0,1)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></td>
                            <td style="color:#666;font-size:12px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="color:#999;font-size:12px;"><?= date('M j', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="users.php" class="view-all"><i class="fas fa-arrow-right"></i> View All Users</a>
            </div>
        </div>

        <!--  Recent Products + Recent Reviews  -->
        <div class="dash-grid">

            <!-- Recent Products -->
            <div class="dash-card">
                <div class="dash-card-title"><i class="fas fa-box"></i> Recently Added Products</div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Product</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prod = mysqli_fetch_assoc($low_stock)): ?>
                        <tr>
                            <td style="width:46px;">
                                <?php if (!empty($prod['image'])): 
                                $imgSrc = (strpos($prod['image'], 'http') === 0) ? $prod['image'] : '../' . $prod['image'];
                            ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" class="prod-thumb" alt="">
                                <?php else: ?>
                                    <div style="width:38px;height:38px;background:#f5f5f5;border:1px solid #e0e0e0;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-image" style="color:#ccc;font-size:14px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?= htmlspecialchars($prod['name']) ?></td>
                            <td style="font-weight:700;">$ <?= number_format($prod['price'],2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="products.php" class="view-all"><i class="fas fa-arrow-right"></i> View All Products</a>
            </div>

            <!-- Recent Reviews -->
            <div class="dash-card">
                <div class="dash-card-title"><i class="fas fa-star"></i> Recent Reviews</div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_reviews = false;
                        while ($rev = mysqli_fetch_assoc($recent_reviews)):
                            $has_reviews = true;
                        ?>
                        <tr>
                            <td style="font-weight:600;font-size:12px;"><?= htmlspecialchars($rev['user_name'] ?? 'Guest') ?></td>
                            <td style="color:#555;font-size:12px;"><?= htmlspecialchars(substr($rev['product_name'] ?? '-', 0, 22)) ?><?= strlen($rev['product_name'] ?? '') > 22 ? '...' : '' ?></td>
                            <td>
                                <span class="stars">
                                    <?php for ($i=1;$i<=5;$i++) echo $i<=(int)$rev['rating'] ? '&#9734;' : '&#9733;'; ?>
                                </span>
                            </td>
                            <td style="color:#999;font-size:11px;"><?= date('M j', strtotime($rev['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_reviews): ?>
                        <tr><td colspan="4" style="text-align:center;color:#999;padding:30px 10px;"><i class="fas fa-comment-slash" style="display:block;font-size:24px;margin-bottom:8px;color:#ddd;"></i>No reviews yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <a href="review.php" class="view-all"><i class="fas fa-arrow-right"></i> View All Reviews</a>
            </div>
        </div>

    </div><!-- /main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>

