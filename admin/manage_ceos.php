<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete CEO
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM ceos WHERE id=$id");
    header("Location: manage_ceos.php");
    exit();
}

$ceos = mysqli_query($conn, "SELECT * FROM ceos ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage CEOs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>CEO Management</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All CEOs</h2>
                <a style="text-decoration: none;" class="btn-add" href="add_ceo.php"><i class="fas fa-plus"></i> Add CEO</a>
            </div>
            
            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($ceos)): ?>
                            <tr>
                                <td>
                                    <?php if($row['image']): ?>
                                        <?php $imgSrc = (strpos($row['image'], 'http') === 0) ? $row['image'] : '../' . $row['image']; ?>
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" style="width:50px; height:50px; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:50px; height:50px; background:#eee; display:flex; align-items:center; justify-content:center;"><i class="fas fa-user text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td>
                                    <?php if($row['status'] == 'Active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <div class="action-group">
                                        <a href="edit_ceo.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit CEO"><i class="fas fa-edit"></i></a>
                                        <a href="manage_ceos.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete CEO" onclick="return confirm('Delete this CEO? This cannot be undone.')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($ceos) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center">No CEOs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
