<?php
require '../includes/db.php';
require_once '../check_login.php';

$message = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: manage_contacts.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM contact_options WHERE id = $id");
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: manage_contacts.php");
    exit();
}
$member = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = mysqli_real_escape_string($conn, $_POST['title']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $phone        = mysqli_real_escape_string($conn, $_POST['phone']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $availability = mysqli_real_escape_string($conn, $_POST['availability']);

    $sql = "UPDATE contact_options SET 
            title='$title', 
            description='$description', 
            phone='$phone', 
            email='$email', 
            availability='$availability' 
            WHERE id=$id";
            
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_contacts.php?success=Contact+method+updated+successfully!");
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
    <title>Edit Contact Option - Admin</title>
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
                <a href="manage_contacts.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
                <h1>Edit Contact Option</h1>
            </div>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section p-4 bg-white mt-4" style="border-radius: 8px;">
            <?php if ($message): ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" novalidate id="editContactForm">
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($member['title']) ?>" required
                           data-validation="required,min" data-min="3">
                    <small id="title_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="6"
                              data-validation="required,min" data-min="10"><?= htmlspecialchars($member['description']) ?></textarea>
                    <small id="description_error" class="small text-danger" style="display:none;"></small>
                    <div class="form-text">Provide details (HTML is allowed).</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone']) ?>"
                               required data-validation="required,phone,min" data-min="10">
                        <small id="phone_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email']) ?>"
                               required data-validation="required,email">
                        <small id="email_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Availability <span class="text-danger">*</span></label>
                    <input type="text" name="availability" class="form-control" value="<?= htmlspecialchars($member['availability']) ?>"
                           data-validation="required,min" data-min="5">
                    <small id="availability_error" class="small text-danger" style="display:none;"></small>
                </div>

                <button type="submit" class="btn btn-primary px-4 py-2">Update Contact Method</button>
            </form>
        </div>
    </div>
</body>
</html>
