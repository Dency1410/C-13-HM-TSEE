<?php
require_once '../includes/db.php';
require_once '../check_login.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['user_name']   ?? '');
    $email     = trim($_POST['user_email']  ?? '');
    $role      = trim($_POST['user_role']   ?? 'customer');
    $status    = trim($_POST['user_status'] ?? 'active');
    $password  = trim($_POST['user_password'] ?? '');

    // Validate
    if ($full_name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if (strlen($password) < 6 && $password !== '') $errors[] = 'Password must be at least 6 characters.';

    // Check email unique
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) $errors[] = 'That email is already registered.';
        $chk->close();
    }

    // Handle avatar upload
    $profile_pic = '';
    if (!empty($_FILES['user_avatar']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $ftype   = $_FILES['user_avatar']['type'];
        $fsize   = $_FILES['user_avatar']['size'];
        if (!in_array($ftype, $allowed)) {
            $errors[] = 'Invalid image type. Use JPG, PNG or WEBP.';
        } elseif ($fsize > 5 * 1024 * 1024) {
            $errors[] = 'Image must be under 5 MB.';
        } else {
            $upload_dir = dirname(__DIR__) . '/uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext      = pathinfo($_FILES['user_avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['user_avatar']['tmp_name'], $upload_dir . $filename)) {
                $profile_pic = 'uploads/avatars/' . $filename;
            } else {
                $errors[] = 'Failed to upload avatar.';
            }
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status, profile_photo, is_verified, created_at) VALUES (?,?,?,?,?,?,1,NOW())");
        $stmt->bind_param("ssssss", $full_name, $email, $hash, $role, $status, $profile_pic);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .form-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 36px 40px;
            max-width: 680px;
            margin: 0 auto;
        }

        .form-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-title i { color: #E50914; }

        .field-group { margin-bottom: 22px; }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #000;
        }

        .field-label .req { color: #E50914; margin-left: 2px; }

        .field-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #333;
            background: #fff;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .field-input:focus { outline: none; border-color: #000; }

        select.field-input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 38px;
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Upload Zone */
        .upload-zone {
            border: 2px dashed #d0d0d0;
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            background: #fafafa;
            transition: all 0.2s;
            position: relative;
        }

        .upload-zone:hover { border-color: #000; background: #f5f5f5; }
        .upload-zone.has-image { border-style: solid; border-color: #000; padding: 0; }

        .upload-zone i.upload-icon { font-size: 32px; color: #bbb; display: block; margin-bottom: 10px; }
        .upload-zone p { margin: 0 0 5px; font-size: 14px; color: #555; }
        .upload-zone small { font-size: 12px; color: #aaa; }

        #previewImg { width: 100%; max-height: 240px; object-fit: contain; display: none; }

        .field-input.is-invalid {
            border-color: #E50914 !important;
        }

        small.text-danger {
            color: #E50914;
            font-size: 11px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }

        .upload-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .upload-zone.has-image:hover .upload-overlay { display: flex; }

        .upload-overlay button {
            background: #fff;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .upload-overlay .btn-change { color: #000; }
        .upload-overlay .btn-remove { color: #E50914; }

        /* Buttons */
        .btn-row { display: flex; gap: 12px; margin-top: 30px; }

        .btn-publish {
            flex: 1;
            background: #E50914;
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-publish:hover { background: #b00710; }

        .btn-cancel-link {
            flex: 1;
            background: #fff;
            color: #666;
            border: 1px solid #e0e0e0;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-cancel-link:hover { border-color: #000; color: #000; }

        .alert-box {
            padding: 13px 16px;
            margin-bottom: 22px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .alert-box.success { background: #f0fff4; border-left: 4px solid #2e7d32; color: #2e7d32; }
        .alert-box.error   { background: #fff0f0; border-left: 4px solid #E50010; color: #c62828; }

        @media (max-width: 768px) {
            .form-card { padding: 24px 16px; }
            .two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="users.php" style="color:#999;font-size:20px;text-decoration:none;transition:color .2s;"
               onmouseover="this.style.color='#000'" onmouseout="this.style.color='#999'">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Add New User</h1>
        </div>
        <?php include 'header.php'; ?>
    </div>

    <div class="form-card">
        <div class="form-card-title">
            <i class="fas fa-user-plus"></i> User Details
        </div>

        <?php if ($success): ?>
        <div class="alert-box success">
            <i class="fas fa-check-circle"></i>
            <span>User added successfully! <a href="users.php" style="color:#2e7d32;font-weight:700;">View all users →</a></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert-box error">
            <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:2px;"></i>
            <ul style="margin:0;padding-left:14px;">
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="addUserForm" novalidate>

            <!-- Avatar -->
            <div class="field-group">
                <label class="field-label">Avatar</label>
                <div class="upload-zone" id="uploadZone" onclick="triggerUpload()">
                    <i class="fas fa-cloud-upload-alt upload-icon" id="uploadIcon"></i>
                    <p id="uploadText">Click to upload avatar image</p>
                    <small id="uploadHint">JPG, PNG, WebP &nbsp;·&nbsp; Max 5 MB</small>
                    <img id="previewImg" src="" alt="Preview">
                    <div class="upload-overlay" id="uploadOverlay">
                        <button type="button" class="btn-change" onclick="event.stopPropagation();triggerUpload()">
                            <i class="fas fa-pencil-alt"></i> Change
                        </button>
                        <button type="button" class="btn-remove" onclick="event.stopPropagation();removeImage()">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <input type="file" id="imageInput" name="user_avatar"
                       accept="image/jpeg,image/png,image/webp" style="display:none;"
                       onchange="previewImage(event)"
                       data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                <small id="user_avatar_error" class="small text-danger" style="display:none;"></small>
            </div>

            <div class="field-group">
                <label class="field-label" for="userName">Name <span class="req">*</span></label>
                <input type="text" id="userName" name="user_name" class="field-input"
                       placeholder="e.g., John Smith" maxlength="100" 
                       data-validation="required,alphabetic,min" data-min="3"
                       value="<?= htmlspecialchars($_POST['user_name'] ?? '') ?>">
                <small id="user_name_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Email -->
            <div class="field-group">
                <label class="field-label" for="userEmail">Email <span class="req">*</span></label>
                <input type="email" id="userEmail" name="user_email" class="field-input"
                       placeholder="e.g., john@example.com"
                       data-validation="required,email"
                       value="<?= htmlspecialchars($_POST['user_email'] ?? '') ?>">
                <small id="user_email_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Password -->
            <div class="field-group">
                <label class="field-label" for="userPassword">Password <span class="req">*</span></label>
                <input type="password" id="userPassword" name="user_password" class="field-input"
                       placeholder="Minimum 8 characters"
                       data-validation="required,strongpassword">
                <small id="user_password_error" class="small text-danger" style="display:none;"></small>
            </div>

            <!-- Role & Status -->
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label" for="userRole">Role <span class="req">*</span></label>
                    <select id="userRole" name="user_role" class="field-input" data-validation="required,select">
                        <option value="">Select Role</option>
                        <option value="customer" <?= (($_POST['user_role'] ?? '') === 'customer') ? 'selected' : '' ?>>Customer</option>
                        <option value="admin"    <?= (($_POST['user_role'] ?? '') === 'admin')    ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <small id="user_role_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label" for="userStatus">Status <span class="req">*</span></label>
                    <select id="userStatus" name="user_status" class="field-input" data-validation="required,select">
                        <option value="active"   <?= (($_POST['user_status'] ?? 'active') === 'active')   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($_POST['user_status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <small id="user_status_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>

            <!-- Buttons -->
            <div class="btn-row">
                <button type="submit" class="btn-publish">
                    <i class="fas fa-plus"></i> Add User
                </button>
                <a href="users.php" class="btn-cancel-link">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>

        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script>
function triggerUpload() {
    document.getElementById('imageInput').click();
}

function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const img  = document.getElementById('previewImg');
        const zone = document.getElementById('uploadZone');
        img.src = e.target.result;
        img.style.display = 'block';
        document.getElementById('uploadIcon').style.display = 'none';
        document.getElementById('uploadText').style.display = 'none';
        document.getElementById('uploadHint').style.display = 'none';
        zone.classList.add('has-image');
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    const zone = document.getElementById('uploadZone');
    document.getElementById('previewImg').style.display = 'none';
    document.getElementById('previewImg').src = '';
    document.getElementById('uploadIcon').style.display = 'block';
    document.getElementById('uploadText').style.display = 'block';
    document.getElementById('uploadHint').style.display = 'block';
    document.getElementById('imageInput').value = '';
    zone.classList.remove('has-image');
}
</script>
</body>
</html>
