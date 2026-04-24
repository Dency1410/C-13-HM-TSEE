<?php
require '../includes/db.php';
require_once '../check_login.php';

if (!isset($_GET['id'])) { header("Location: manage_about_us.php"); exit; }
$id = (int)$_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM about_us WHERE id=$id");
if (!$result || mysqli_num_rows($result) == 0) { header("Location: manage_about_us.php"); exit; }
$data = mysqli_fetch_assoc($result);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_key    = mysqli_real_escape_string($conn, trim($_POST['section_key']));
    $hero_title     = mysqli_real_escape_string($conn, $_POST['hero_title']);
    $intro_title    = mysqli_real_escape_string($conn, $_POST['intro_title']);
    $intro_text     = mysqli_real_escape_string($conn, $_POST['intro_text']);
    $stat1_number   = mysqli_real_escape_string($conn, $_POST['stat1_number']);
    $stat1_label    = mysqli_real_escape_string($conn, $_POST['stat1_label']);
    $stat2_number   = mysqli_real_escape_string($conn, $_POST['stat2_number']);
    $stat2_label    = mysqli_real_escape_string($conn, $_POST['stat2_label']);
    $stat3_number   = mysqli_real_escape_string($conn, $_POST['stat3_number']);
    $stat3_label    = mysqli_real_escape_string($conn, $_POST['stat3_label']);
    $stat4_number   = mysqli_real_escape_string($conn, $_POST['stat4_number']);
    $stat4_label    = mysqli_real_escape_string($conn, $_POST['stat4_label']);
    $stat_reference = mysqli_real_escape_string($conn, $_POST['stat_reference']);
    $our_way_title  = mysqli_real_escape_string($conn, $_POST['our_way_title']);
    $our_way_desc   = mysqli_real_escape_string($conn, $_POST['our_way_description']);
    $col1_title     = mysqli_real_escape_string($conn, $_POST['col1_title']);
    $col1_text      = mysqli_real_escape_string($conn, $_POST['col1_text']);
    $col2_title     = mysqli_real_escape_string($conn, $_POST['col2_title']);
    $col2_text      = mysqli_real_escape_string($conn, $_POST['col2_text']);
    $col3_title     = mysqli_real_escape_string($conn, $_POST['col3_title']);
    $col3_text      = mysqli_real_escape_string($conn, $_POST['col3_text']);

    $uploadDir  = '../uploads/';
    $allowTypes = ['jpg', 'png', 'jpeg', 'webp'];

    function uploadImgEdit($fileKey, $uploadDir, $allowTypes, $existing) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowTypes)) {
                $fileName = time() . '_' . $fileKey . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $fileName))
                    return 'uploads/' . $fileName;
            }
        }
        return $existing; // Keep existing if no new file
    }

    $our_way_image = uploadImgEdit('our_way_image', $uploadDir, $allowTypes, $data['our_way_image']);
    $col1_image    = uploadImgEdit('col1_image', $uploadDir, $allowTypes, $data['col1_image']);
    $col2_image    = uploadImgEdit('col2_image', $uploadDir, $allowTypes, $data['col2_image']);
    $col3_image    = uploadImgEdit('col3_image', $uploadDir, $allowTypes, $data['col3_image']);

    // Allow overriding with URL if field is not empty
    if (!empty($_POST['our_way_image_url'])) $our_way_image = mysqli_real_escape_string($conn, $_POST['our_way_image_url']);
    if (!empty($_POST['col1_image_url']))    $col1_image    = mysqli_real_escape_string($conn, $_POST['col1_image_url']);
    if (!empty($_POST['col2_image_url']))    $col2_image    = mysqli_real_escape_string($conn, $_POST['col2_image_url']);
    if (!empty($_POST['col3_image_url']))    $col3_image    = mysqli_real_escape_string($conn, $_POST['col3_image_url']);

    $sql = "UPDATE about_us SET
        section_key='$section_key', hero_title='$hero_title',
        intro_title='$intro_title', intro_text='$intro_text',
        stat1_number='$stat1_number', stat1_label='$stat1_label',
        stat2_number='$stat2_number', stat2_label='$stat2_label',
        stat3_number='$stat3_number', stat3_label='$stat3_label',
        stat4_number='$stat4_number', stat4_label='$stat4_label',
        stat_reference='$stat_reference',
        our_way_title='$our_way_title', our_way_description='$our_way_desc', our_way_image='$our_way_image',
        col1_title='$col1_title', col1_text='$col1_text', col1_image='$col1_image',
        col2_title='$col2_title', col2_text='$col2_text', col2_image='$col2_image',
        col3_title='$col3_title', col3_text='$col3_text', col3_image='$col3_image'
    WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_about_us.php?success=Section+updated+successfully!");
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
    <title>Edit About Us - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .form-card { background:#fff; border:1px solid #e0e0e0; padding:36px 40px; max-width:860px; margin:0 auto 40px; }
        
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
        .form-card-title { font-size:13px; font-weight:700; color:#000; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:24px; padding-bottom:14px; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; gap:10px; }
        .form-card-title i { color:#E50914; }
        .section-divider { font-size:11px; font-weight:700; color:#999; text-transform:uppercase; letter-spacing:1.5px; margin:28px 0 16px; border-bottom:1px solid #f0f0f0; padding-bottom:8px; }
        .field-group { margin-bottom:18px; }
        .field-label { display:block; margin-bottom:7px; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#000; }
        .field-label .req { color:#E50914; margin-left:2px; }
        .field-input { width:100%; padding:11px 14px; border:1px solid #e0e0e0; font-size:14px; font-family:'Inter',sans-serif; color:#333; transition:border-color 0.2s; box-sizing:border-box; }
        .field-input:focus { outline:none; border-color:#000; }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .four-col { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:12px; }
        .three-col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
        .image-group { border:1px dashed #ddd; padding:14px; background:#fafafa; margin-bottom:10px; }
        .image-group label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; margin-bottom:6px; display:block; }
        .img-preview { width:100%; max-height:120px; object-fit:cover; margin-bottom:8px; border:1px solid #eee; }
        .btn-row { display:flex; gap:12px; margin-top:28px; }
        .btn-publish { flex:1; background:#E50914; color:#fff; border:none; padding:14px 20px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; cursor:pointer; }
        .btn-publish:hover { background:#b00710; }
        .btn-cancel-link { flex:1; background:#fff; color:#666; border:1px solid #e0e0e0; padding:14px 20px; font-size:13px; font-weight:500; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .btn-cancel-link:hover { border-color:#000; color:#000; }
        .hint { font-size:11px; color:#999; margin-top:4px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="manage_about_us.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
            <h1>Edit About Us Section</h1>
        </div>
        <?php include 'header.php'; ?>
    </div>

    <div class="form-card">
        <div class="form-card-title"><i class="fas fa-edit"></i> Edit Section: <em><?= htmlspecialchars($data['section_key']) ?></em></div>
        <?php if ($message): ?><div class="alert alert-danger"><?= $message ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate id="editAboutUsForm">

            <!-- Basic -->
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Section Key <span class="req">*</span></label>
                    <input type="text" name="section_key" class="field-input" value="<?= htmlspecialchars($data['section_key']) ?>" required
                           data-validation="required,alphabetic,min" data-min="3">
                    <small id="section_key_error" class="small text-danger" style="display:none;"></small>
                    <p class="hint">Unique identifier (lowercase, no spaces)</p>
                </div>
                <div class="field-group">
                    <label class="field-label">Hero Title <span class="req">*</span></label>
                    <input type="text" name="hero_title" class="field-input" value="<?= htmlspecialchars($data['hero_title']) ?>" required
                           data-validation="required,min" data-min="3">
                    <small id="hero_title_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>

            <!-- Intro -->
            <div class="field-group">
                <label class="field-label">Intro Title <span class="req">*</span></label>
                <textarea name="intro_title" class="field-input" rows="2" required
                          data-validation="required,min" data-min="5"><?= htmlspecialchars($data['intro_title']) ?></textarea>
                <small id="intro_title_error" class="small text-danger" style="display:none;"></small>
            </div>
            <div class="field-group">
                <label class="field-label">Intro Text <span class="req">*</span></label>
                <textarea name="intro_text" class="field-input" rows="3"
                          data-validation="required,min" data-min="10"><?= htmlspecialchars($data['intro_text']) ?></textarea>
                <small id="intro_text_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Stats -->
            <div class="section-divider">Statistics (4 Items)</div>
            <div class="four-col">
                <div class="field-group">
                    <label class="field-label">Stat 1 Number <span class="req">*</span></label>
                    <input type="text" name="stat1_number" class="field-input" value="<?= htmlspecialchars($data['stat1_number']) ?>"
                           data-validation="required">
                    <small id="stat1_number_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Stat 1 Label <span class="req">*</span></label>
                    <input type="text" name="stat1_label" class="field-input" value="<?= htmlspecialchars($data['stat1_label']) ?>"
                           data-validation="required">
                    <small id="stat1_label_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Stat 2 Number <span class="req">*</span></label>
                    <input type="text" name="stat2_number" class="field-input" value="<?= htmlspecialchars($data['stat2_number']) ?>"
                           data-validation="required">
                    <small id="stat2_number_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Stat 2 Label <span class="req">*</span></label>
                    <input type="text" name="stat2_label" class="field-input" value="<?= htmlspecialchars($data['stat2_label']) ?>"
                           data-validation="required">
                    <small id="stat2_label_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>
            <div class="four-col">
                <div class="field-group">
                    <label class="field-label">Stat 3 Number <span class="req">*</span></label>
                    <input type="text" name="stat3_number" class="field-input" value="<?= htmlspecialchars($data['stat3_number']) ?>"
                           data-validation="required">
                    <small id="stat3_number_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Stat 3 Label <span class="req">*</span></label>
                    <input type="text" name="stat3_label" class="field-input" value="<?= htmlspecialchars($data['stat3_label']) ?>"
                           data-validation="required">
                    <small id="stat3_label_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Stat 4 Number <span class="req">*</span></label>
                    <input type="text" name="stat4_number" class="field-input" value="<?= htmlspecialchars($data['stat4_number']) ?>"
                           data-validation="required">
                    <small id="stat4_number_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Stat 4 Label <span class="req">*</span></label>
                    <input type="text" name="stat4_label" class="field-input" value="<?= htmlspecialchars($data['stat4_label']) ?>"
                           data-validation="required">
                    <small id="stat4_label_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>
            <div class="field-group">
                <label class="field-label">Stats Reference Text <span class="req">*</span></label>
                <input type="text" name="stat_reference" class="field-input" value="<?= htmlspecialchars($data['stat_reference']) ?>"
                       data-validation="required,min" data-min="5">
                <small id="stat_reference_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Our Way -->
            <div class="section-divider">Our Way Section</div>
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Our Way Title <span class="req">*</span></label>
                    <input type="text" name="our_way_title" class="field-input" value="<?= htmlspecialchars($data['our_way_title']) ?>"
                           data-validation="required,min" data-min="3">
                    <small id="our_way_title_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Our Way Image</label>
                    <div class="image-group">
                        <?php if ($data['our_way_image']): ?>
                            <img src="<?= strpos($data['our_way_image'], 'http') === 0 ? $data['our_way_image'] : '../' . $data['our_way_image'] ?>" class="img-preview">
                        <?php endif; ?>
                        <label>Replace with File Upload</label>
                        <input type="file" name="our_way_image" class="field-input" accept="image/*"
                               data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                        <small id="our_way_image_error" class="small text-danger" style="display:none;"></small>
                        <label style="margin-top:8px;">OR Replace with URL</label>
                        <input type="text" name="our_way_image_url" class="field-input" placeholder="Leave blank to keep existing">
                        <small id="our_way_image_url_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>
            </div>
            <div class="field-group">
                <label class="field-label">Our Way Description <span class="req">*</span></label>
                <textarea name="our_way_description" class="field-input" rows="3"
                          data-validation="required,min" data-min="10"><?= htmlspecialchars($data['our_way_description']) ?></textarea>
                <small id="our_way_description_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Three Column Cards -->
            <div class="section-divider">Three Feature Cards</div>
            <div class="three-col">
                <!-- Card 1 -->
                <div>
                    <div class="field-group">
                        <label class="field-label">Card 1 Title <span class="req">*</span></label>
                        <input type="text" name="col1_title" class="field-input" value="<?= htmlspecialchars($data['col1_title']) ?>"
                               data-validation="required,min" data-min="3">
                        <small id="col1_title_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Card 1 Text <span class="req">*</span></label>
                        <textarea name="col1_text" class="field-input" rows="2"
                                  data-validation="required,min" data-min="5"><?= htmlspecialchars($data['col1_text']) ?></textarea>
                        <small id="col1_text_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="image-group">
                        <?php if ($data['col1_image']): ?>
                            <img src="<?= strpos($data['col1_image'], 'http') === 0 ? $data['col1_image'] : '../' . $data['col1_image'] ?>" class="img-preview">
                        <?php endif; ?>
                        <label>Replace File</label>
                        <input type="file" name="col1_image" class="field-input" accept="image/*"
                               data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                        <small id="col1_image_error" class="small text-danger" style="display:none;"></small>
                        <label style="margin-top:8px;">OR URL</label>
                        <input type="text" name="col1_image_url" class="field-input" placeholder="Leave blank to keep existing">
                        <small id="col1_image_url_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>
                <!-- Card 2 -->
                <div>
                    <div class="field-group">
                        <label class="field-label">Card 2 Title <span class="req">*</span></label>
                        <input type="text" name="col2_title" class="field-input" value="<?= htmlspecialchars($data['col2_title']) ?>"
                               data-validation="required,min" data-min="3">
                        <small id="col2_title_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Card 2 Text <span class="req">*</span></label>
                        <textarea name="col2_text" class="field-input" rows="2"
                                  data-validation="required,min" data-min="5"><?= htmlspecialchars($data['col2_text']) ?></textarea>
                        <small id="col2_text_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="image-group">
                        <?php if ($data['col2_image']): ?>
                            <img src="<?= strpos($data['col2_image'], 'http') === 0 ? $data['col2_image'] : '../' . $data['col2_image'] ?>" class="img-preview">
                        <?php endif; ?>
                        <label>Replace File</label>
                        <input type="file" name="col2_image" class="field-input" accept="image/*"
                               data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                        <small id="col2_image_error" class="small text-danger" style="display:none;"></small>
                        <label style="margin-top:8px;">OR URL</label>
                        <input type="text" name="col2_image_url" class="field-input" placeholder="Leave blank to keep existing">
                        <small id="col2_image_url_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>
                <!-- Card 3 -->
                <div>
                    <div class="field-group">
                        <label class="field-label">Card 3 Title <span class="req">*</span></label>
                        <input type="text" name="col3_title" class="field-input" value="<?= htmlspecialchars($data['col3_title']) ?>"
                               data-validation="required,min" data-min="3">
                        <small id="col3_title_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Card 3 Text <span class="req">*</span></label>
                        <textarea name="col3_text" class="field-input" rows="2"
                                  data-validation="required,min" data-min="5"><?= htmlspecialchars($data['col3_text']) ?></textarea>
                        <small id="col3_text_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div class="image-group">
                        <?php if ($data['col3_image']): ?>
                            <img src="<?= strpos($data['col3_image'], 'http') === 0 ? $data['col3_image'] : '../' . $data['col3_image'] ?>" class="img-preview">
                        <?php endif; ?>
                        <label>Replace File</label>
                        <input type="file" name="col3_image" class="field-input" accept="image/*"
                               data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                        <small id="col3_image_error" class="small text-danger" style="display:none;"></small>
                        <label style="margin-top:8px;">OR URL</label>
                        <input type="text" name="col3_image_url" class="field-input" placeholder="Leave blank to keep existing">
                        <small id="col3_image_url_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn-publish"><i class="fas fa-save"></i> Save Changes</button>
                <a href="manage_about_us.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
