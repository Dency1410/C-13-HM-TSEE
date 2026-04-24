<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM investor_page WHERE id=$id");
    header("Location: manage_investor.php");
    exit();
}

$rows = mysqli_query($conn, "SELECT * FROM investor_page ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Investor Page - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Investor Page Content</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Records</h2>
                <a style="text-decoration:none;" class="btn-add" href="add_investor.php"><i class="fas fa-plus"></i> Add Record</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hero Image</th>
                            <th>Page Title</th>
                            <th>Contact Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Last Updated</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <?php if ($row['hero_image']): ?>
                                    <img src="<?= htmlspecialchars($row['hero_image']) ?>" style="width:70px;height:45px;object-fit:cover;border:1px solid #eee;">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['page_title']) ?></td>
                            <td><?= htmlspecialchars($row['contact_name']) ?></td>
                            <td><?= htmlspecialchars($row['contact_phone']) ?></td>
                            <td><?= htmlspecialchars($row['contact_email']) ?></td>
                            <td><small><?= $row['updated_at'] ?></small></td>
                            <td style="white-space:nowrap;">
                                <div class="action-group">
                                    <a href="edit_investor.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Record">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_investor.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Record"
                                       onclick="return confirm('Delete this investor record? This cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($rows) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">No records found. <a href="add_investor.php">Add one now</a>.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
