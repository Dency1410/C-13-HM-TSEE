<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

// Fetch all product reviews
$query = "
    SELECT r.*, p.name as product_name, u.full_name as customer_name 
    FROM product_reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$result = mysqli_query($conn, $query);

// Toast notification handler
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
    <title>Reviews - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
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

    <!-- Toast notification -->
    <?php if ($toast): ?>
    <div class="toast-msg show" id="toastMsg"><?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Review Management</h1>
            <?php include 'header.php'?>
        </div>
        <div class="users-section">
            <div class="section-header">
                <h2>Product Reviews</h2>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customer</th>
                            <th style="min-width: 100px;">Rating</th>
                            <th style="max-width: 300px;">Review</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reviewTableBody">
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['product_name'] ?? 'Unknown Product') ?></strong></td>
                                    <td><?= htmlspecialchars($row['customer_name'] ?? 'Anonymous') ?></td>
                                    <td>
                                        <div style="display: flex; gap: 2px;">
                                            <?php
                                            $rating = (int)$row['rating'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star" style="color: #ffc107;"></i>';
                                                } else {
                                                    echo '<i class="far fa-star" style="color: #ffc107;"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td style="max-width: 300px; white-space: normal; line-height: 1.4;">
                                        <?= nl2br(htmlspecialchars($row['review_text'])) ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-group" style="display:flex; gap:8px;">
                                            <a href="view_review.php?id=<?= $row['id'] ?>" class="action-btn view" title="View Review" style="color: #0d9488; background: #e6f6f5; padding: 6px 10px; border-radius: 4px; border: 1px solid #b2dfdb; display: inline-block;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="delete_review.php?id=<?= $row['id'] ?>" class="action-btn delete" title="Delete Review" onclick="return confirm('Are you sure you want to delete this review?');" style="display: inline-block;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="no-results">
                                <td colspan="6">
                                    <i class="fas fa-comment-slash"></i>
                                    No reviews found.
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