<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM about_us WHERE id=$id");
    header("Location: manage_about_us.php");
    exit();
}

$rows = mysqli_query($conn, "SELECT * FROM about_us ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>About Us Content</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Sections</h2>
                <a style="text-decoration:none;" class="btn-add" href="add_about_us.php"><i class="fas fa-plus"></i> Add Section</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Section Key</th>
                            <th>Hero Title</th>
                            <th>Intro Title (Preview)</th>
                            <th>Stats</th>
                            <th>Last Updated</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($row['section_key']) ?></span></td>
                            <td><?= htmlspecialchars($row['hero_title']) ?></td>
                            <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= htmlspecialchars(substr($row['intro_title'], 0, 80)) ?>...
                            </td>
                            <td>
                                <small>
                                    <?= htmlspecialchars($row['stat1_number']) ?> | 
                                    <?= htmlspecialchars($row['stat2_number']) ?> | 
                                    <?= htmlspecialchars($row['stat3_number']) ?> | 
                                    <?= htmlspecialchars($row['stat4_number']) ?>
                                </small>
                            </td>
                            <td><small><?= $row['updated_at'] ?></small></td>
                            <td style="white-space:nowrap;">
                                <div class="action-group">
                                    <a href="edit_about_us.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Section">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_about_us.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Section"
                                       onclick="return confirm('Delete this section? This cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($rows) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">No sections found. <a href="add_about_us.php">Add one now</a>.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
