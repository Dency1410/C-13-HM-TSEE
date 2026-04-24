<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete Size
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM sizes WHERE id=$id");
    header("Location: manage_sizes.php");
    exit();
}

$sizes = mysqli_query($conn, "SELECT * FROM sizes");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sizes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: none;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Manage Sizes</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Sizes</h2>
                <a style="text-decoration: none;" class="btn-add" href="add_size.php"><i class="fas fa-plus"></i> Add Size</a>
            </div>
            
            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($sizes)): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td style="white-space:nowrap;">
                                    <div class="action-group">
                                        <a href="edit_size.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Size"><i class="fas fa-edit"></i></a>
                                        <a href="manage_sizes.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Size" onclick="return confirm('Delete this size? This cannot be undone.')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($sizes) == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center">No sizes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>