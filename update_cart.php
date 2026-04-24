<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action   = $_POST['action']  ?? '';
$cart_id  = $_POST['cart_id'] ?? '';          // may be integer id OR "pid_size" for guests
$user_id  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if ($cart_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid cart item']);
    exit;
}

if ($user_id !== null) {
    // ── DB-backed cart (logged-in user) ──────────────────────────────────
    $db_cart_id = (int)$cart_id;
    if ($db_cart_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid cart id']);
        exit;
    }
    $where = "id = $db_cart_id AND user_id = $user_id";

    if ($action === 'remove') {
        mysqli_query($conn, "DELETE FROM cart WHERE $where");

    } elseif ($action === 'increase') {
        mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE $where");

    } elseif ($action === 'decrease') {
        $res = mysqli_query($conn, "SELECT quantity FROM cart WHERE $where");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if ((int)$row['quantity'] <= 1) {
                mysqli_query($conn, "DELETE FROM cart WHERE $where");
            } else {
                mysqli_query($conn, "UPDATE cart SET quantity = quantity - 1 WHERE $where");
            }
        }
    }

    // Return updated count
    $count_res  = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id");
    $count_row  = mysqli_fetch_assoc($count_res);
    $cart_count = (int)($count_row['total'] ?? 0);

} else {
    // ── Session-based cart (guest) ────────────────────────────────────────
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $key = $cart_id;   // e.g. "28_M"

    if ($action === 'remove') {
        unset($_SESSION['cart'][$key]);

    } elseif ($action === 'increase') {
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity']++;
        }

    } elseif ($action === 'decrease') {
        if (isset($_SESSION['cart'][$key])) {
            if ($_SESSION['cart'][$key]['quantity'] <= 1) {
                unset($_SESSION['cart'][$key]);
            } else {
                $_SESSION['cart'][$key]['quantity']--;
            }
        }
    }

    $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

echo json_encode(['status' => 'success', 'cart_count' => $cart_count]);
?>
