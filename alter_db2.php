<?php
require 'includes/db.php';
$sql = "ALTER TABLE products ADD COLUMN old_price DECIMAL(10,2) NULL";
if (mysqli_query($conn, $sql)) {
    echo "Successfully added old_price column.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
