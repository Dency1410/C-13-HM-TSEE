<?php require_once '../check_login.php'; ?>
<?php
require '../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);

    // Handle image upload
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $imagePath = '';
    // Check if an image was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowTypes = ['jpg', 'png', 'jpeg', 'webp'];
        if (in_array($ext, $allowTypes)) {
            $fileName = time() . '_bod.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                $imagePath = 'uploads/' . $fileName;
            }
        }
    }
    
    // Fallback to URL input if no file uploaded
    if (empty($imagePath) && !empty($_POST['image_url'])) {
        $imagePath = mysqli_real_escape_string($conn, $_POST['image_url']);
    }

    if (empty($imagePath)) {
        $message = "Please upload an image or provide an image URL.";
    } else {
        $sql = "INSERT INTO board_of_director (name, title, image) VALUES ('$name', '$title', '$imagePath')";
        if (mysqli_query($conn, $sql)) {
            header("Location: manage_board_of_director.php?success=Member+added+successfully!");
            exit;
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Board of Director - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
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
            <div style="display:flex;align-items:center;gap:14px;">
                <a href="manage_board_of_director.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
                <h1>Add Board of Director</h1>
            </div>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section p-4 bg-white mt-4" style="border-radius: 8px;">
            <?php if ($message): ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate id="addDirectorForm">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           data-validation="required,alphabetic,min" data-min="3">
                    <small id="name_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Chairman, Board Member" required
                           data-validation="required,min" data-min="3">
                    <small id="title_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="mb-4 p-3 border" style="background: #f9f9f9;">
                    <label class="form-label"><strong>Member Image</strong> <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <label class="form-label text-muted small">Upload Local File:</label>
                        <input type="file" name="image" class="form-control" accept="image/*"
                               data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                        <small id="image_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="text-center text-muted small mb-2">OR</div>
                    <div>
                        <label class="form-label text-muted small">External Image URL:</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4 py-2">Add Member</button>
            </form>
        </div>
    </div>
</body>
</html>
