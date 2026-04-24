<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

// Must be logged in
if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = (int) $_SESSION['admin_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

// Collect & sanitise inputs
$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$gender    = trim($_POST['gender']    ?? '');
$dob       = trim($_POST['dob']       ?? '');
$address   = trim($_POST['address']   ?? '');
$new_pass  = $_POST['new_password']   ?? '';

if ($full_name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// Check if email is taken by another user
$chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$chk->bind_param("si", $email, $user_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'That email is already in use by another account.']);
    exit;
}
$chk->close();

// Handle avatar upload
$profile_pic = $_SESSION['profile_pic'] ?? '';
if (!empty($_FILES['profile_picture']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $ftype   = $_FILES['profile_picture']['type'];
    $fsize   = $_FILES['profile_picture']['size'];
    if (!in_array($ftype, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, GIF or WEBP.']);
        exit;
    }
    if ($fsize > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image must be under 2 MB.']);
        exit;
    }
    $upload_dir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $ext      = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $dest     = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
        // Delete old avatar if it exists
        if ($profile_pic && file_exists(__DIR__ . '/' . $profile_pic)) {
            @unlink(__DIR__ . '/' . $profile_pic);
        }
        $profile_pic = 'uploads/avatars/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
        exit;
    }
}

$stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, gender=?, dob=?, address=?, profile_photo=? WHERE id=?");
$dob_val = $dob !== '' ? $dob : null;
$stmt->bind_param("ssssssi", $full_name, $phone, $gender, $dob_val, $address, $profile_pic, $user_id);

if ($stmt->execute()) {
    // Refresh session variables
    $_SESSION['user_name']    = $full_name;
    $_SESSION['user_email']   = $email;
    $_SESSION['user_phone']   = $phone;
    $_SESSION['user_gender']  = $gender;
    $_SESSION['user_dob']     = $dob;
    $_SESSION['user_address'] = $address;
    $_SESSION['profile_pic']  = $profile_pic;
    $_SESSION['user_avatar']  = $profile_pic;

    echo json_encode([
        'success'    => true,
        'name'       => $full_name,
        'email'      => $email,
        'avatar_url' => $profile_pic ? $profile_pic : ''
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
