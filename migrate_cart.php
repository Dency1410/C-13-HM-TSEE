<?php
/**
 * Run this ONCE to add user_id and size columns to the cart table.
 * Safe to re-run — checks before altering.
 */
require 'includes/db.php';

$columns_needed = [
    'user_id' => "ALTER TABLE cart ADD COLUMN user_id INT(11) DEFAULT NULL",
    'size'    => "ALTER TABLE cart ADD COLUMN size VARCHAR(20) DEFAULT NULL",
];

foreach ($columns_needed as $col => $sql) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE '$col'");
    if ($check && mysqli_num_rows($check) === 0) {
        if (mysqli_query($conn, $sql)) {
            echo "Added column '$col' to cart table.<br>";
        } else {
            echo "Error adding '$col': " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column '$col' already exists.<br>";
    }
}

echo "<br>Done! You can delete this file now.";
?>
