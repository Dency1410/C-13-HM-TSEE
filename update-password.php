<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

// Must be logged in
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = (int)$_SESSION['admin_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

// Collect & sanitise inputs
$current_pass = $_POST['current_password'] ?? '';
$new_pass     = $_POST['new_password']     ?? '';
$confirm_pass = $_POST['confirm_password'] ?? '';

if ($current_pass === '' || $new_pass === '' || $confirm_pass === '') {
    echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
    exit;
}

if ($new_pass !== $confirm_pass) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

if (strlen($new_pass) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
    exit;
}

// Check current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($current_pass, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

// Update to new password
$hash = password_hash($new_pass, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->bind_param('si', $hash, $user_id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$update->close();
$conn->close();
?>
