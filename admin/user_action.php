<?php
require_once '../includes/db.php';
require_once '../check_login.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? 'update';

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['user_id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }
    // Remove avatar file if exists
    $r = $conn->query("SELECT profile_photo FROM users WHERE id = $id LIMIT 1");
    if ($row = $r->fetch_assoc()) {
        if (!empty($row['profile_photo'])) {
            $path = dirname(__DIR__) . '/' . $row['profile_photo'];
            if (file_exists($path)) @unlink($path);
        }
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
$id        = (int)  ($_POST['user_id']      ?? 0);
$full_name = trim(   $_POST['full_name']    ?? '');
$email     = trim(   $_POST['email']        ?? '');
$role      = trim(   $_POST['role']         ?? 'customer');
$status    = trim(   $_POST['status']       ?? 'active');
$new_pass  =         $_POST['new_password'] ?? '';

if (!$id || $full_name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
// Check email uniqueness
$chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$chk->bind_param("si", $email, $id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already used by another account.']);
    exit;
}
$chk->close();

if ($new_pass !== '') {
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, status=?, password=? WHERE id=?");
    $stmt->bind_param("sssssi", $full_name, $email, $role, $status, $hash, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $full_name, $email, $role, $status, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
$conn->close();
?>
