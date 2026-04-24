<?php
require '../includes/db.php';
require_once '../check_login.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: manage_categories.php");
    exit();
}

// Handle Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    mysqli_query($conn, "UPDATE categories SET name='$name', gender='$gender' WHERE id=$id");
    header("Location: manage_categories.php");
    exit();
}

$category_query = mysqli_query($conn, "SELECT * FROM categories WHERE id=$id");
if (mysqli_num_rows($category_query) === 0) {
    header("Location: manage_categories.php");
    exit();
}
$category = mysqli_fetch_assoc($category_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .card { padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: none; max-width: 500px; margin: 0 auto; }
        
        .form-control.is-invalid {
            border-color: #E50010 !important;
        }

        small.text-danger {
            color: #E50010;
            font-size: 11px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Edit Category</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="container-fluid mt-4">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Edit Category</h5>
                    <a href="manage_categories.php" class="btn btn-sm btn-outline-secondary">Go Back</a>
                </div>
                <form method="POST" novalidate id="editCategoryForm">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required
                               data-validation="required,alphabetic,min" data-min="2">
                        <small id="name_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control" required data-validation="required,select">
                            <option value="">Select Gender</option>
                            <option value="Ladies" <?= $category['gender'] == 'Ladies' ? 'selected' : '' ?>>Ladies</option>
                            <option value="Men" <?= $category['gender'] == 'Men' ? 'selected' : '' ?>>Men</option>
                            <option value="Kids Girl" <?= $category['gender'] == 'Kids Girl' ? 'selected' : '' ?>>Kids Girl</option>
                            <option value="Kids Boy" <?= $category['gender'] == 'Kids Boy' ? 'selected' : '' ?>>Kids Boy</option>
                        </select>
                        <small id="gender_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <button type="submit" name="update_category" class="btn btn-dark w-100">Update Category</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
