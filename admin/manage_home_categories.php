<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM home_categories WHERE id=$id");
    header("Location: manage_home_categories.php?success=Category+deleted+successfully!");
    exit();
}

$rows = mysqli_query($conn, "SELECT * FROM home_categories ORDER BY display_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Home Categories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Home Categories</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>Shop by Categories</h2>
                <a style="text-decoration:none;" class="btn-add" href="add_home_category.php">
                    <i class="fas fa-plus"></i> Add Category
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Link URL</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <?php if ($row['image_url']): ?>
                                    <img src="<?= htmlspecialchars($row['image_url']) ?>"
                                         style="width:70px;height:50px;object-fit:cover;border:1px solid #eee;border-radius:4px;">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($row['description']) ?>
                            </td>
                            <td><small><?= htmlspecialchars($row['link_url']) ?></small></td>
                            <td><?= (int)$row['display_order'] ?></td>
                            <td>
                                <?php if ($row['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <div class="action-group">
                                    <a href="edit_home_category.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Category">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_home_categories.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Category"
                                       onclick="return confirm('Delete this category? This cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($rows) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                No categories found. <a href="add_home_category.php">Add one now</a>.
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
