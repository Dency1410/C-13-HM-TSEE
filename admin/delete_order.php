<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Delete order items first (child records)
    $stmt1 = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    
    // Then delete the order
    $stmt2 = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    
    $_SESSION['toast'] = 'Order deleted successfully.';
}
header("Location: order.php");
exit();
?>
