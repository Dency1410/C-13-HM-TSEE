<?php
require 'includes/db.php';
$sql = "CREATE TABLE IF NOT EXISTS ceos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT NULL,
    image VARCHAR(255) NULL,
    bio TEXT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) {
    echo "Table ceos created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>
