<?php
require_once '../check_login.php';
require_once '../includes/db.php';

// Fetch user wishlists data
$query = "SELECT u.id as user_id, u.full_name, COUNT(w.id) as item_count, SUM(p.price) as total_value, MAX(w.created_at) as last_updated
          FROM wishlist w
          JOIN users u ON w.user_id = u.id
          JOIN products p ON w.product_id = p.id
          GROUP BY u.id
          ORDER BY last_updated DESC";
$wishlists = mysqli_query($conn, $query);

// Helper function for time ago
function timeAgo($timestamp) {
    if (!$timestamp) return 'Never';
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return floor($diff / 86400) . ' days ago';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'sidebar.php' ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Wishlist Management</h1>
            <?php include 'header.php' ?>
        </div>
        <div class="users-section">
            <div class="section-header">
                <h2>User Wishlists</h2>
            </div>
            <div class="table-responsive-wrap">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Items</th>
                        <th>Total Value</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($wishlists) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($wishlists)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                                <td><?= $row['item_count'] ?> items</td>
                                <td>$<?= number_format($row['total_value'], 2) ?></td>
                                <td><?= timeAgo($row['last_updated']) ?></td>
                                <td>
                                    <button class="action-btn view" onclick="viewWishlist(<?= $row['user_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">No wishlists found.</td>
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
        function viewWishlist(id) {
            // This could open a modal or redirect to a detail page
            showNotification('Viewing wishlist for User ID #' + id, 'success');
        }
    </script>
</body>

</html>