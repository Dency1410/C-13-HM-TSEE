<?php require_once '../check_login.php'; ?>
<?php
require '../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $availability = mysqli_real_escape_string($conn, $_POST['availability']);

    $sql = "INSERT INTO contact_options (title, description, phone, email, availability) 
            VALUES ('$title', '$description', '$phone', '$email', '$availability')";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_contacts.php?success=Contact+method+added+successfully!");
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
    <title>Add Contact Option - Admin</title>
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
                <a href="manage_contacts.php" style="color:#999;font-size:20px;text-decoration:none;"><i
                        class="fas fa-arrow-left"></i></a>
                <h1>Add Contact Option</h1>
            </div>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section p-4 bg-white mt-4" style="border-radius: 8px;">
            <?php if ($message): ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" novalidate id="addContactForm">
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Chat with us, Call us"
                        required data-validation="required,min" data-min="3">
                    <small id="title_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Description or instructions"
                        data-validation="required,min" data-min="10"></textarea>
                    <small id="description_error" class="small text-danger" style="display:none;"></small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 1800-889-8000"
                            required data-validation="required,phone,min" data-min="10">
                        <small id="phone_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. support@example.com"
                            required data-validation="required,email">
                        <small id="email_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Availability <span class="text-danger">*</span></label>
                    <input type="text" name="availability" class="form-control"
                        placeholder="e.g. Monday - Sunday: 8.00 - 22.00" data-validation="required,min" data-min="5">
                    <small id="availability_error" class="small text-danger" style="display:none;"></small>
                </div>

                <button type="submit" class="btn btn-primary px-4 py-2">Create Contact Method</button>
            </form>
        </div>
    </div>
</body>

</html>