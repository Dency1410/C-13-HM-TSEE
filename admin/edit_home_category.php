<?php
require '../includes/db.php';
require_once '../check_login.php';

if (!isset($_GET['id'])) { header("Location: manage_home_categories.php"); exit; }
$id = (int)$_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM home_categories WHERE id=$id");
if (!$result || mysqli_num_rows($result) == 0) { header("Location: manage_home_categories.php"); exit; }
$data = mysqli_fetch_assoc($result);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = mysqli_real_escape_string($conn, $_POST['title']);
    $description   = mysqli_real_escape_string($conn, $_POST['description']);
    $link_url      = mysqli_real_escape_string($conn, $_POST['link_url']);
    $display_order = (int)$_POST['display_order'];
    $is_active     = isset($_POST['is_active']) ? 1 : 0;

    // Handle Image Upload or URL
    $image_url = $data['image_url'];
    $uploadDir = '../uploads/';
    $allowTypes = ['jpg', 'png', 'jpeg', 'webp'];

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowTypes)) {
            $fileName = time() . '_homecat.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $fileName)) {
                $image_url = 'uploads/' . $fileName;
            }
        }
    }
    if (!empty($_POST['image_url_text'])) {
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url_text']);
    }

    $sql = "UPDATE home_categories SET
        title='$title',
        description='$description',
        image_url='$image_url',
        link_url='$link_url',
        display_order=$display_order,
        is_active=$is_active
    WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_home_categories.php?success=Category+updated+successfully!");
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
    <title>Edit Home Category - Admin</title>
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
        .field-group { margin-bottom:18px; }
        .field-label { display:block; margin-bottom:7px; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#000; }
        .field-label .req { color:#E50914; margin-left:2px; }
        .field-input { width:100%; padding:11px 14px; border:1px solid #e0e0e0; font-size:14px; font-family:'Inter',sans-serif; color:#333; transition:border-color 0.2s; box-sizing:border-box; }
        .field-input:focus { outline:none; border-color:#000; }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .image-group { border:1px dashed #ddd; padding:14px; background:#fafafa; margin-bottom:18px; }
        .image-group label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; margin-bottom:6px; display:block; }
        .img-preview { width:100%; max-height:130px; object-fit:cover; margin-bottom:10px; border:1px solid #eee; border-radius:4px; }
        .btn-row { display:flex; gap:12px; margin-top:28px; }
        .btn-publish { flex:1; background:#E50914; color:#fff; border:none; padding:14px 20px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; cursor:pointer; }
        .btn-publish:hover { background:#b00710; }
        .btn-cancel-link { flex:1; background:#fff; color:#666; border:1px solid #e0e0e0; padding:14px 20px; font-size:13px; font-weight:500; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .btn-cancel-link:hover { border-color:#000; color:#000; }

    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="manage_home_categories.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
            <h1>Edit Category</h1>
        </div>
        <?php include 'header.php'; ?>
    </div>

    <div class="form-card">
        <div class="form-card-title"><i class="fas fa-edit"></i> Edit Shop Category</div>
        <?php if ($message): ?><div class="alert alert-danger"><?= $message ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate id="editHomeCategoryForm">

            <div class="field-group">
                <label class="field-label">Category Title <span class="req">*</span></label>
                <input type="text" name="title" class="field-input" value="<?= htmlspecialchars($data['title']) ?>" required
                       data-validation="required,alphabetic,min" data-min="2">
                <small id="title_error" class="small text-danger" style="display:none;"></small>
            </div>

            <div class="field-group">
                <label class="field-label">Description <span class="req">*</span></label>
                <input type="text" name="description" class="field-input" value="<?= htmlspecialchars($data['description']) ?>"
                       data-validation="required,min" data-min="5">
                <small id="description_error" class="small text-danger" style="display:none;"></small>
            </div>

            <div class="image-group">
                <?php if ($data['image_url']): ?>
                    <img src="<?= htmlspecialchars($data['image_url']) ?>" class="img-preview" alt="Current image">
                <?php endif; ?>
                <label>Replace Category Image (Upload)</label>
                <input type="file" name="image_file" class="field-input" accept="image/*"
                       data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                <small id="image_file_error" class="small text-danger" style="display:none;"></small>
                <label style="margin-top:10px;">OR Replace with Image URL <small style="text-transform:none;font-weight:400;">(leave blank to keep existing)</small></label>
                <input type="text" name="image_url_text" class="field-input" placeholder="https://...">
            </div>

            <div class="field-group">
                <label class="field-label">Link URL <span class="req">*</span></label>
                <input type="text" name="link_url" class="field-input" value="<?= htmlspecialchars($data['link_url']) ?>" required
                       data-validation="required">
                <small id="link_url_error" class="small text-danger" style="display:none;"></small>
            </div>

            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Display Order <span class="req">*</span></label>
                    <input type="number" name="display_order" class="field-input" value="<?= (int)$data['display_order'] ?>"
                           data-validation="required,number">
                    <small id="display_order_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group" style="display:flex;align-items:center;padding-top:25px;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                               <?= $data['is_active'] ? 'checked' : '' ?> style="cursor:pointer; width:40px; height:20px;">
                        <label class="form-check-label ms-2" for="isActive" style="font-weight:600; cursor:pointer;">Active</label>
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn-publish"><i class="fas fa-save"></i> Save Changes</button>
                <a href="manage_home_categories.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
