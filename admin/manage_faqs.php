<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';


// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_query = "DELETE FROM faqs WHERE id = $id";
    if (mysqli_query($conn, $delete_query)) {
        header("Location: manage_faqs.php?msg=deleted");
        exit();
    } else {
        $error = "Error deleting record: " . mysqli_error($conn);
    }
}

// Fetch all FAQs
$query = "SELECT * FROM faqs ORDER BY id ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customer Service FAQs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Customer Service FAQs</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>All FAQs</h2>
                <a class="btn-add" href="add_faq.php" style="text-decoration:none;"><i class="fas fa-plus"></i> Add New FAQ</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                <div class="alert alert-success">FAQ added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="alert alert-success">FAQ updated successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success">FAQ deleted successfully!</div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="table-responsive-wrap">
                <table class="custom-table" style="width:100%; text-align:left;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Question</th>
                            <th style="width: 40%">Answer</th>
                            <th style="white-space:nowrap; width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><span class="badge bg-dark"><?= htmlspecialchars($row['question']) ?></span></td>
                                    <td><?= htmlspecialchars(substr($row['answer'], 0, 100)) . (strlen($row['answer']) > 100 ? '...' : '') ?></td>
                                    <td style="white-space:nowrap;">
                                        <div class="action-group">
                                            <a href="edit_faq.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Edit FAQ"><i class="fas fa-edit"></i></a>
                                            <a href="manage_faqs.php?delete=<?= $row['id'] ?>" class="action-btn delete" title="Delete FAQ" onclick="return confirm('Delete this FAQ? This cannot be undone.');"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No FAQs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
