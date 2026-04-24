<?php
require '../includes/db.php';
require_once '../check_login.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: manage_sizes.php");
    exit();
}

// Handle Update Size
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_size'])) {
    $name = mysqli_real_escape_string($conn, strtoupper(trim($_POST['name'])));
    mysqli_query($conn, "UPDATE sizes SET name='$name' WHERE id=$id");
    header("Location: manage_sizes.php");
    exit();
}

$size_query = mysqli_query($conn, "SELECT * FROM sizes WHERE id=$id");
if (mysqli_num_rows($size_query) === 0) {
    header("Location: manage_sizes.php");
    exit();
}
$size = mysqli_fetch_assoc($size_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Size - Admin</title>
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
            <h1>Edit Size</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="container-fluid mt-4">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Edit Size</h5>
                    <a href="manage_sizes.php" class="btn btn-sm btn-outline-secondary">Go Back</a>
                </div>
                <form method="POST" novalidate id="editSizeForm">
                    <div class="mb-3">
                        <label class="form-label">Size Value</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($size['name']) ?>" required
                               data-validation="required">
                        <small id="name_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <button type="submit" name="update_size" class="btn btn-dark w-100">Update Size</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
