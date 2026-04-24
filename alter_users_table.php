<?php
require 'includes/db.php';

$columns = [
    "phone"     => "VARCHAR(20) NULL",
    "gender"    => "VARCHAR(20) NULL",
    "dob"       => "DATE NULL",
    "address"   => "TEXT NULL",
    "role"      => "ENUM('customer','admin') NOT NULL DEFAULT 'customer'",
    "status"    => "ENUM('active','inactive') NOT NULL DEFAULT 'active'",
    "last_login"=> "DATETIME NULL",
];

foreach ($columns as $col => $definition) {
    // Check if column already exists
    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE users ADD COLUMN `$col` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "✅ Added column: <strong>$col</strong><br>";
        } else {
            echo "❌ Error adding $col: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "ℹ️ Column <strong>$col</strong> already exists.<br>";
    }
}

echo "<br><strong>Done.</strong>";
?>
