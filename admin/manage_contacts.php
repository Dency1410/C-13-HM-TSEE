<?php
require '../includes/db.php';
require_once '../check_login.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM contact_options WHERE id=$id");
    header("Location: manage_contacts.php?success=Contact+method+deleted!");
    exit();
}

$rows = mysqli_query($conn, "SELECT * FROM contact_options ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contacts - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Contact Options</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All Contact Methods</h2>
                <a style="text-decoration:none;" class="btn-add" href="add_contact.php"><i class="fas fa-plus"></i> Add Method</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Description (Preview)</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($row['title']) ?></span></td>
                            <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= htmlspecialchars(substr($row['description'], 0, 80)) ?>...
                            </td>
                            <td><?= htmlspecialchars($row['phone']) ?: '-' ?></td>
                            <td><?= htmlspecialchars($row['email']) ?: '-' ?></td>
                            <td style="white-space:nowrap;">
                                <div class="action-group">
                                    <a href="edit_contact.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit Contact">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_contacts.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete Contact"
                                       onclick="return confirm('Delete this contact method? This cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($rows) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">No contact methods found. <a href="add_contact.php">Add one now</a>.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
