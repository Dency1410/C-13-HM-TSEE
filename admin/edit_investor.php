<?php
require '../includes/db.php';
require_once '../check_login.php';

if (!isset($_GET['id'])) { header("Location: manage_investor.php"); exit; }
$id = (int)$_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM investor_page WHERE id=$id");
if (!$result || mysqli_num_rows($result) == 0) { header("Location: manage_investor.php"); exit; }
$data = mysqli_fetch_assoc($result);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_title            = mysqli_real_escape_string($conn, $_POST['page_title']);
    $contact_section_title = mysqli_real_escape_string($conn, $_POST['contact_section_title']);
    $contact_name          = mysqli_real_escape_string($conn, $_POST['contact_name']);
    $contact_phone         = mysqli_real_escape_string($conn, $_POST['contact_phone']);
    $contact_email         = mysqli_real_escape_string($conn, $_POST['contact_email']);

    // Hero image: keep existing unless a new one is given
    $hero_image = $data['hero_image'];
    $uploadDir  = '../uploads/';
    $allowTypes = ['jpg', 'png', 'jpeg', 'webp'];

    if (isset($_FILES['hero_image_file']) && $_FILES['hero_image_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['hero_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowTypes)) {
            $fileName = time() . '_investor_hero.' . $ext;
            if (move_uploaded_file($_FILES['hero_image_file']['tmp_name'], $uploadDir . $fileName))
                $hero_image = 'uploads/' . $fileName;
        }
    }
    if (!empty($_POST['hero_image_url'])) {
        $hero_image = mysqli_real_escape_string($conn, $_POST['hero_image_url']);
    }

    $sql = "UPDATE investor_page SET
        page_title='$page_title',
        hero_image='$hero_image',
        contact_section_title='$contact_section_title',
        contact_name='$contact_name',
        contact_phone='$contact_phone',
        contact_email='$contact_email'
    WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_investor.php?success=Record+updated+successfully!");
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
    <title>Edit Investor Record - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .form-card { background:#fff; border:1px solid #e0e0e0; padding:36px 40px; max-width:700px; margin:0 auto 40px; }
        .form-card-title { font-size:13px; font-weight:700; color:#000; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:24px; padding-bottom:14px; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; gap:10px; }
        .form-card-title i { color:#E50914; }
        .section-divider { font-size:11px; font-weight:700; color:#999; text-transform:uppercase; letter-spacing:1.5px; margin:28px 0 16px; border-bottom:1px solid #f0f0f0; padding-bottom:8px; }
        .field-group { margin-bottom:18px; }
        .field-label { display:block; margin-bottom:7px; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#000; }
        .field-label .req { color:#E50914; margin-left:2px; }
        .field-input { width:100%; padding:11px 14px; border:1px solid #e0e0e0; font-size:14px; font-family:'Inter',sans-serif; color:#333; transition:border-color 0.2s; box-sizing:border-box; }
        .field-input:focus { outline:none; border-color:#000; }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .image-group { border:1px dashed #ddd; padding:14px; background:#fafafa; }
        .image-group label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; margin-bottom:6px; display:block; }
        .img-preview { width:100%; max-height:130px; object-fit:cover; margin-bottom:10px; border:1px solid #eee; }
        .btn-row { display:flex; gap:12px; margin-top:28px; }
        .btn-publish { flex:1; background:#E50914; color:#fff; border:none; padding:14px 20px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; cursor:pointer; }
        .btn-publish:hover { background:#b00710; }
        .btn-cancel-link { flex:1; background:#fff; color:#666; border:1px solid #e0e0e0; padding:14px 20px; font-size:13px; font-weight:500; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .btn-cancel-link:hover { border-color:#000; color:#000; }

        .field-input.is-invalid {
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
            <a href="manage_investor.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
            <h1>Edit Investor Record</h1>
        </div>
        <?php include 'header.php'; ?>
    </div>

    <div class="form-card">
        <div class="form-card-title"><i class="fas fa-edit"></i> Edit Record #<?= $id ?></div>
        <?php if ($message): ?><div class="alert alert-danger"><?= $message ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate id="editInvestorForm">

            <!-- Page Title -->
            <div class="field-group">
                <label class="field-label">Page Title <span class="req">*</span></label>
                <input type="text" name="page_title" class="field-input" value="<?= htmlspecialchars($data['page_title']) ?>" required
                       data-validation="required,min" data-min="3">
                <small id="page_title_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Hero Image -->
            <div class="section-divider">Hero Image</div>
            <div class="image-group">
                <?php if ($data['hero_image']): ?>
                    <img src="../<?= htmlspecialchars($data['hero_image']) ?>" class="img-preview" alt="Current hero image">
                <?php endif; ?>
                <label>Replace with File Upload</label>
                <input type="file" name="hero_image_file" class="field-input" accept="image/*"
                       data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                <small id="hero_image_file_error" class="small text-danger" style="display:none;"></small>
                <label style="margin-top:10px;">OR Replace with URL <small style="font-weight:400;text-transform:none;">(leave blank to keep existing)</small></label>
                <input type="text" name="hero_image_url" class="field-input" placeholder="https://...">
            </div>

            <!-- Contact Section -->
            <div class="section-divider">Contact Information</div>
            <div class="field-group">
                <label class="field-label">Contact Section Title</label>
                <input type="text" name="contact_section_title" class="field-input" value="<?= htmlspecialchars($data['contact_section_title']) ?>"
                       data-validation="required,min" data-min="2">
                <small id="contact_section_title_error" class="small text-danger" style="display:none;"></small>
            </div>
            <div class="field-group">
                <label class="field-label">Contact Name <span class="req">*</span></label>
                <input type="text" name="contact_name" class="field-input" value="<?= htmlspecialchars($data['contact_name']) ?>" required
                       data-validation="required,alphabetic,min" data-min="3">
                <small id="contact_name_error" class="small text-danger" style="display:none;"></small>
            </div>
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Phone <span class="req">*</span></label>
                    <input type="text" name="contact_phone" class="field-input" value="<?= htmlspecialchars($data['contact_phone']) ?>" required
                           data-validation="required,phone,min" data-min="10">
                    <small id="contact_phone_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Email <span class="req">*</span></label>
                    <input type="email" name="contact_email" class="field-input" value="<?= htmlspecialchars($data['contact_email']) ?>" required
                           data-validation="required,email">
                    <small id="contact_email_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn-publish"><i class="fas fa-save"></i> Save Changes</button>
                <a href="manage_investor.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
