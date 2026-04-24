<?php
require_once '../check_login.php';
require '../includes/db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    header("Location: products.php");
    exit();
}

// Fetch all products
$products = mysqli_query($conn, "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
");
$total = $products ? mysqli_num_rows($products) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php' ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Product Management</h1>
            <?php include 'header.php' ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Products <span style="font-size:13px;font-weight:400;color:#999;">(<?= $total ?>)</span></h2>
                <a style="text-decoration: none;" class="btn-add" href="add_product.php"><i class="fas fa-plus"></i>Add Product</a>
            </div>

            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Gender</th>
                            <th>Price</th>
                            <th>Old Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td>
                                    <?php if($row['image']): 
                                        $imgSrc = (strpos($row['image'], 'http') === 0) ? $row['image'] : '../' . $row['image'];
                                    ?>
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Product" style="width:50px; height:50px; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:50px; height:50px; background:#eee; display:flex; align-items:center; justify-content:center;"><i class="fas fa-image text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($row['name'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($row['category_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['gender'] ?? '') ?></td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td><?= $row['old_price'] ? '$' . number_format($row['old_price'], 2) : '-' ?></td>
                                <td><?= $row['stock'] ?></td>
                                <td>
                                    <?php if($row['status'] == 'In Stock'): ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php elseif($row['status'] == 'Out of Stock'): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><?= htmlspecialchars($row['status'] ?? '') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <div class="action-group">
                                        <a href="edit_product.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Product"><i class="fas fa-edit"></i></a>
                                        <a href="products.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Product" onclick="return confirm('Delete this product? This cannot be undone.');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; padding:40px 20px; color:#999;">
                                    <i class="fas fa-box-open" style="font-size:32px; display:block; margin-bottom:10px; color:#ddd;"></i>
                                    No products found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>