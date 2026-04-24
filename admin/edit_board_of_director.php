<?php
require '../includes/db.php';
require_once '../check_login.php';

$message = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: manage_board_of_director.php");
    exit();
}

// Fetch existing
$result = mysqli_query($conn, "SELECT * FROM board_of_director WHERE id = $id");
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: manage_board_of_director.php");
    exit();
}
$member = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);

    // Handle image upload
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $imagePath = $member['image']; // Default to existing
    
    // Check if new image was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowTypes = ['jpg', 'png', 'jpeg', 'webp'];
        if (in_array($ext, $allowTypes)) {
            $fileName = time() . '_bod.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                $imagePath = 'uploads/' . $fileName;
            }
        }
    } else if (!empty($_POST['image_url'])) {
        $imagePath = mysqli_real_escape_string($conn, $_POST['image_url']);
    }

    $sql = "UPDATE board_of_director SET name='$name', title='$title', image='$imagePath' WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_board_of_director.php?success=Member+updated+successfully!");
        exit;
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Board of Director - Admin</title>
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
                <h1>Edit Board of Director</h1>
            </div>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section p-4 bg-white mt-4" style="border-radius: 8px;">
            <?php if ($message): ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate id="editDirectorForm">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($member['name']) ?>" required
                           data-validation="required,alphabetic,min" data-min="3">
                    <small id="name_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($member['title']) ?>" placeholder="e.g. Chairman, Board Member" required
                           data-validation="required,min" data-min="3">
                    <small id="title_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="mb-4 p-3 border" style="background: #f9f9f9;">
                    <label class="form-label"><strong>Member Image</strong></label>
                    
                    <div class="mb-3">
                        <label class="d-block text-muted small mb-1">Current Image:</label>
                        <?php if (strpos($member['image'], 'http') === 0): ?>
                            <img src="<?= htmlspecialchars($member['image']) ?>" width="100" style="border-radius:4px;">
                        <?php else: ?>
                            <img src="../<?= htmlspecialchars($member['image']) ?>" width="100" style="border-radius:4px;">
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted small">Replace with Local File:</label>
                        <input type="file" name="image" class="form-control" accept="image/*"
                               data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                        <small id="image_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="text-center text-muted small mb-2">OR</div>
                    <div>
                        <label class="form-label text-muted small">Update External Image URL (leave blank to keep current):</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4 py-2">Update Member</button>
            </form>
        </div>
    </div>
</body>
</html>
