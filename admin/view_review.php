<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: review.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT r.*, p.name as product_name, p.image, u.full_name as customer_name, u.email 
    FROM product_reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();

if (!$review) {
    echo "<p style='padding:40px;font-family:Inter,sans-serif;'>Review not found.</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Review - H&M Admin</title>
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

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
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
        
        .product-preview {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .product-img-box {
            width: 100px;
            height: 120px;
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
        }
        .product-img-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .star-box {
            font-size: 20px;
            color: #ffc107;
            margin-bottom: 12px;
        }

        .btn-cancel {
            color: #666; font-size: 14px; text-decoration: none; font-weight: 600;
            padding: 13px 24px; border: 1px solid #e0e0e0; border-radius: 4px;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: #fff;
        }
        .btn-cancel:hover { background: #f5f5f5; color: #000; }
        
        .btn-danger-action {
            color: #fff; font-size: 14px; text-decoration: none; font-weight: 600;
            padding: 13px 24px; border: none; border-radius: 4px;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: #E50010; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-danger-action:hover { background: #b0000a; color: #fff; }

        .action-row { display: flex; gap: 14px; align-items: center; margin-top: 20px; justify-content: space-between;}
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>View Review</h1>
            <?php include 'header.php'; ?>
        </div>

        <div style="max-width: 960px;">

            <div class="view-card">
                <div class="view-card-title">
                    <i class="fas fa-box"></i> Product Context
                </div>
                <div class="product-preview">
                    <div class="product-img-box">
                        <img src="<?= htmlspecialchars($review['image'] ?? 'default.png') ?>" alt="Product">
                    </div>
                    <div>
                        <div class="info-label">Reviewed Product</div>
                        <div class="info-val" style="font-size: 18px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($review['product_name'] ?? 'Unknown Product') ?></div>
                        <div class="info-label" style="margin-top: 15px;">Date Submitted</div>
                        <div class="info-val"><?= date('F j, Y \a\t g:i A', strtotime($review['created_at'])) ?></div>
                    </div>
                </div>
            </div>

            <div class="view-card">
                <div class="view-card-title">
                    <i class="fas fa-user"></i> Customer Info & Rating
                </div>
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <span class="info-label">Customer Name</span>
                            <span class="info-val"><?= htmlspecialchars($review['customer_name'] ?? 'Anonymous') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Customer Email</span>
                            <span class="info-val"><?= htmlspecialchars($review['email'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">Score</span>
                            <div class="star-box">
                                <?php
                                $rating = (int)$review['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) echo '<i class="fas fa-star"></i>';
                                    else echo '<i class="far fa-star"></i>';
                                }
                                ?>
                                <span style="font-size: 14px; color:#222; font-weight: 600; margin-left: 8px; vertical-align: middle;">
                                    <?= $rating ?> / 5
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Full Review Content</span>
                            <div class="info-val" style="background:#f9f9f9; padding: 16px; border: 1px solid #eee; border-radius: 4px; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-row">
                <a href="review.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Back to Reviews
                </a>
                <a href="delete_review.php?id=<?= $review['id'] ?>" class="btn-danger-action" onclick="return confirm('Are you sure you want to permanently delete this review?');">
                    <i class="fas fa-trash"></i> Delete Review
                </a>
            </div>

        </div><!-- /max-width -->
    </div><!-- /main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
