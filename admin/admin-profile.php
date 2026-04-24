<?php
require_once '../includes/db.php';
require_once '../check_login.php';

$user_id = $_SESSION['admin_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    header("Location: ../login.php");
    exit();
}

$full_name      = $admin['full_name'] ?? 'Admin User';
$email          = $admin['email'] ?? '';
$role           = $admin['role'] ?? 'Administrator';
$phone          = $admin['phone'] ?? '';
$dob            = $admin['dob'] ?? '';
$address        = $admin['address'] ?? '';
$profile_photo  = !empty($admin['profile_photo']) ? '../' . $admin['profile_photo'] : 'https://ui-avatars.com/api/?name='.urlencode($full_name).'&background=000000&color=fff&size=200';
$joined_date    = date('M Y', strtotime($admin['created_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        /* ── Profile Avatar Block ── */
        .profile-avatar-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 36px 24px;
            text-align: center;
            position: sticky;
            top: 24px;
        }

        .profile-avatar-card .avatar-ring {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solid #000;
            object-fit: cover;
            margin-bottom: 16px;
        }

        .profile-avatar-card h4 {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 4px;
            color: #111;
        }

        .profile-avatar-card .role-badge {
            display: inline-block;
            background: #111;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 3px 12px;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .profile-meta-list {
            list-style: none;
            padding: 0;
            margin: 0;
            border-top: 1px solid #f0f0f0;
            padding-top: 16px;
            text-align: left;
        }

        .profile-meta-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #555;
            padding: 7px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .profile-meta-list li i {
            color: #000;
            min-width: 16px;
            font-size: 13px;
        }

        .avatar-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 2px;
            font-size: 12px;
            font-weight: 600;
            padding: 7px 16px;
            cursor: pointer;
            color: #333;
            margin-bottom: 20px;
            transition: all 0.2s;
        }

        .avatar-upload-btn:hover {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        /* ── Tab Navigation ── */
        .profile-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e5e5e5;
            margin-bottom: 28px;
        }

        .profile-tab-btn {
            background: none;
            border: none;
            padding: 12px 24px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #888;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-tab-btn:hover {
            color: #111;
        }

        .profile-tab-btn.active {
            color: #111;
            border-bottom-color: #e50010;
        }

        .profile-tab-btn i {
            font-size: 13px;
        }

        /* ── Tab Panels ── */
        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        /* ── Form Styles ── */
        .form-section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-label-custom {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 6px;
        }

        .form-control-custom {
            border: 1px solid #ddd;
            border-radius: 2px;
            padding: 10px 14px;
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
            color: #111;
            background: #fff;
            width: 100%;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control-custom:focus {
            border-color: #111;
            box-shadow: none;
        }

        .form-control-custom:disabled {
            background: #f8f8f8;
            color: #888;
            cursor: not-allowed;
        }

        .form-control-custom.is-invalid {
            border-color: #e50010;
        }

        .invalid-msg {
            font-size: 11px;
            color: #e50010;
            margin-top: 4px;
            display: none;
        }

        .form-control-custom.is-invalid+.invalid-msg {
            display: block;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .form-control-custom {
            padding-right: 42px;
        }

        .input-icon-wrap .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 14px;
            background: none;
            border: none;
            padding: 0;
        }

        .toggle-pw:hover {
            color: #111;
        }

        /* Password strength */
        .pw-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #eee;
            margin-top: 8px;
            overflow: hidden;
        }

        .pw-strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: all 0.3s;
        }

        .pw-strength-label {
            font-size: 11px;
            margin-top: 4px;
            color: #888;
        }

        /* ── Save Button ── */
        .btn-save {
            background: #111;
            color: #fff;
            border: none;
            padding: 11px 28px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: #e50010;
        }

        .btn-cancel {
            background: transparent;
            color: #555;
            border: 1px solid #ddd;
            padding: 10px 22px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            border-color: #111;
            color: #111;
        }

        /* ── Toast Notification ── */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast-msg {
            background: #111;
            color: #fff;
            padding: 14px 20px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
            animation: slideInToast 0.3s ease;
            border-left: 4px solid #e50010;
        }

        .toast-msg.success {
            border-left-color: #16a34a;
        }

        .toast-msg.error {
            border-left-color: #e50010;
        }

        @keyframes slideInToast {
            from {
                opacity: 0;
                transform: translateX(40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ── Checklist ── */
        .pw-rules {
            list-style: none;
            padding: 0;
            margin: 10px 0 0;
        }

        .pw-rules li {
            font-size: 12px;
            color: #aaa;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            transition: color 0.2s;
        }

        .pw-rules li i {
            font-size: 11px;
        }

        .pw-rules li.pass {
            color: #16a34a;
        }

        .pw-rules li.fail {
            color: #e50010;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }

            .profile-tab-btn {
                padding: 10px 16px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar (your existing sidebar) -->
    <?php include 'sidebar.php' ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1>Admin Profile</h1>
            <?php include 'header.php' ?>

        </div>


        <div class="content-section">
            <div class="row g-4">

                <!-- LEFT: Avatar Card -->
                <div class="col-lg-3 col-md-4">
                    <div class="profile-avatar-card">
                        <img src="<?php echo htmlspecialchars($profile_photo); ?>"
                            alt="Admin" class="avatar-ring" id="avatarPreview">
                        <h4 id="displayAdminName"><?php echo htmlspecialchars($full_name); ?></h4>
                        <div class="role-badge"><?php echo htmlspecialchars($role); ?></div>

                        <label class="avatar-upload-btn" for="avatarInput">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <form id="avatarForm" enctype="multipart/form-data" style="display:none;">
                           <input type="file" id="avatarInput" name="profile_picture" accept="image/*" onchange="previewAvatar(this)">
                        </form>

                        <ul class="profile-meta-list">
                            <li><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></li>
                            <li><i class="fas fa-phone"></i> <?php echo !empty($phone) ? htmlspecialchars($phone) : 'Not provided'; ?></li>
                            <li><i class="fas fa-map-marker-alt"></i> <?php echo !empty($address) ? htmlspecialchars($address) : 'Address not set'; ?></li>
                            <li><i class="fas fa-calendar-alt"></i> Joined <?php echo htmlspecialchars($joined_date); ?></li>
                            <li><i class="fas fa-clock"></i> Last login: Today</li>
                            <li style="border-top: 1px solid #fecaca; margin-top: 10px; padding-top: 10px;">
                                <a href="../logout.php" style="color: #e50010; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-sign-out-alt"></i> Logout System
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- RIGHT: Tabbed Forms -->
                <div class="col-lg-9 col-md-8">
                    <div style="background:#fff;border:1px solid #e5e5e5;border-radius:4px;padding:28px 28px 32px">

                        <!-- Tab Navigation -->
                        <div class="profile-tabs">
                            <button class="profile-tab-btn active" onclick="switchTab('editProfile', this)">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </button>
                            <button class="profile-tab-btn" onclick="switchTab('changePassword', this)">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </div>

                        <!-- ── TAB 1: Edit Profile ── -->
                        <div class="tab-panel active" id="tab-editProfile">
                            <div class="form-section-title">Personal Information</div>
                            <form id="editProfileForm" novalidate>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label-custom">Full Name <span style="color:#e50010;">*</span></label>
                                        <input type="text" class="form-control-custom" name="full_name" id="fullName"
                                            value="<?php echo htmlspecialchars($full_name); ?>" required
                                            data-validation="required,alphabetic,min" data-min="3">
                                        <small id="full_name_error" class="invalid-msg" style="display:none;"></small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label-custom">Email Address</label>
                                        <input type="email" class="form-control-custom" id="email" value="<?php echo htmlspecialchars($email); ?>"
                                            name="email" readonly style="background:#f5f5f5; color:#888;">
                                        <small id="email_error" class="invalid-msg" style="display:none;"></small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label-custom">Phone No</label>
                                        <input type="text" class="form-control-custom" id="phoneno" value="<?php echo htmlspecialchars($phone); ?>"
                                            name="phone"
                                            data-validation="phone,min" data-min="10">
                                        <small id="phone_error" class="invalid-msg" style="display:none;"></small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label-custom">Birth Date</label>
                                        <input type="date" class="form-control-custom" id="dob" value="<?php echo htmlspecialchars($dob); ?>"
                                            name="dob">
                                        <span id="dob_error" class="invalid-msg"></span>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label-custom">Street Address</label>
                                        <input type="text" class="form-control-custom" id="address"
                                            value="<?php echo htmlspecialchars($address); ?>" name="address"
                                            data-validation="min" data-min="5">
                                        <small id="address_error" class="invalid-msg" style="display:none;"></small>
                                    </div>
                                </div>

                                <div class="d-flex gap-3 flex-wrap">
                                    <button type="submit" class="btn-save">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button" class="btn-cancel" onclick="location.reload()">Reset</button>
                                </div>
                            </form>
                        </div>

                            <!-- ── TAB 2: Change Password ── -->
                            <div class="tab-panel" id="tab-changePassword">
                                <div class="form-section-title">Update Your Password</div>
                                <form id="changePasswordForm" novalidate>
                                    <div class="row g-3 mb-2">
                                        <!-- Current Password -->
                                        <div class="col-12">
                                            <label class="form-label-custom">Current Password</label>
                                            <div class="input-icon-wrap">
                                                <input type="password" class="form-control-custom" id="current_password"
                                                    placeholder="Enter current password" name="current_password" required
                                                    data-validation="required,min" data-min="6">
                                                <button type="button" class="toggle-pw" onclick="togglePw('current_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <small id="current_password_error" class="invalid-msg" style="display:none;"></small>
                                            </div>
                                        </div>

                                        <!-- New Password -->
                                        <div class="col-md-12">
                                            <label class="form-label-custom">New Password</label>
                                            <div class="input-icon-wrap">
                                                <input type="password" class="form-control-custom" id="new_password"
                                                    placeholder="Min. 8 characters" name="new_password" required minlength="8"
                                                    data-validation="required,strongpassword">
                                                <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <small id="new_password_error" class="invalid-msg" style="display:none;"></small>
                                            </div>
                                            <div class="pw-strength-bar"><div class="pw-strength-fill" id="strengthFill"></div></div>
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="col-md-12">
                                            <label class="form-label-custom">Confirm New Password</label>
                                            <div class="input-icon-wrap">
                                                <input type="password" class="form-control-custom" id="confirm_password"
                                                    placeholder="Re-enter new password" name="confirm_password" required
                                                    data-validation="required,min" data-min="8">
                                                <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <small id="confirm_password_error" class="invalid-msg" style="display:none;"></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-3 flex-wrap mt-3">
                                        <button class="btn-save" type="submit">
                                            <i class="fas fa-key"></i> Update Password
                                        </button>
                                        <button class="btn-cancel" type="reset">Clear</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            /* ── Toast Notification ── */
            function showToast(message, type = 'success') {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast-msg ${type}`;
                toast.innerHTML = `
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                `;
                container.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(40px)';
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            }

            /* ── Tab Switching ── */
            function switchTab(tabId, btn) {
                $('.tab-panel').removeClass('active');
                $('.profile-tab-btn').removeClass('active');
                $('#tab-' + tabId).addClass('active');
                $(btn).addClass('active');
            }

            /* ── Toggle Password Visibility ── */
            function togglePw(fieldId, btn) {
                const field = document.getElementById(fieldId);
                const icon = btn.querySelector('i');
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }

            /* ── Avatar Preview & Auto-upload ── */
            function previewAvatar(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
                    reader.readAsDataURL(input.files[0]);

                    // Auto-submit the avatar form
                    const formData = new FormData();
                    formData.append('profile_picture', input.files[0]);
                    formData.append('full_name', $('#fullName').val());
                    formData.append('email', $('#email').val()); // Added missing email
                    
                    $.ajax({
                        url: '../update-profile.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            const data = typeof res === 'string' ? JSON.parse(res) : res;
                            if(data.success) showToast('Profile picture updated!');
                            else showToast(data.message || 'Error updating photo', 'error');
                        }
                    });
                }
            }

            $(document).ready(function() {
                /* ── Edit Profile AJAX ── */
                $('#editProfileForm').on('submit', function(e) {
                    e.preventDefault();
                    const $btn = $(this).find('button[type="submit"]');
                    const originalContent = $btn.html();
                    $btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                    $('.invalid-msg').hide().text('');

                    $.ajax({
                        url: '../update-profile.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        success: function(res) {
                            const data = typeof res === 'string' ? JSON.parse(res) : res;
                            if(data.success) {
                                showToast('Profile updated successfully!');
                                $('#displayAdminName').text($('#fullName').val());
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showToast(data.message || 'Update failed', 'error');
                            }
                        },
                        error: () => showToast('Server error', 'error'),
                        complete: () => $btn.html(originalContent).prop('disabled', false)
                    });
                });

                /* ── Change Password AJAX ── */
                $('#changePasswordForm').on('submit', function(e) {
                    e.preventDefault();
                    const $btn = $(this).find('button[type="submit"]');
                    const originalContent = $btn.html();
                    $btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                    $('.invalid-msg').hide().text('');

                    $.ajax({
                        url: '../update-password.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        success: function(res) {
                            const data = typeof res === 'string' ? JSON.parse(res) : res;
                            if(data.success) {
                                showToast(data.message || 'Password changed!');
                                $('#changePasswordForm')[0].reset();
                            } else {
                                showToast(data.message || 'Action failed', 'error');
                            }
                        },
                        error: () => showToast('Server error', 'error'),
                        complete: () => $btn.html(originalContent).prop('disabled', false)
                    });
                });
            });
        </script>
</body>
</html>
</body>

</html>