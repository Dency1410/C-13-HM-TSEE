<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM board_of_director WHERE id=$id");
    header("Location: manage_board_of_director.php?success=Deleted+successfully!");
    exit();
}

$rows = mysqli_query($conn, "SELECT * FROM board_of_director ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Board of Directors - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Board of Directors</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Members</h2>
                <a style="text-decoration:none;" class="btn-add" href="add_board_of_director.php"><i class="fas fa-plus"></i> Add Member</a>
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
                            <th>Name</th>
                            <th>Title</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <?php if (strpos($row['image'], 'http') === 0): ?>
                                    <img src="<?= htmlspecialchars($row['image']) ?>" width="60" style="border-radius:4px; object-fit:cover; aspect-ratio:1;">
                                <?php else: ?>
                                    <img src="../<?= htmlspecialchars($row['image']) ?>" width="60" style="border-radius:4px; object-fit:cover; aspect-ratio:1;">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td style="white-space:nowrap;">
                                <div class="action-group">
                                    <a href="edit_board_of_director.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Member">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_board_of_director.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Member"
                                       onclick="return confirm('Delete this member? This cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($rows) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center">No board members found. <a href="add_board_of_director.php">Add one now</a>.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
