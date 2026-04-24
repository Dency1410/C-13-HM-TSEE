<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    product_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'wishlist' created successfully.\n";

    // Insert dummy data if table is empty
    $check = mysqli_query($conn, "SELECT id FROM wishlist LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        // Find 3 products
        $prodRes = mysqli_query($conn, "SELECT id FROM products LIMIT 3");
        if ($prodRes && mysqli_num_rows($prodRes) > 0) {
            $stmt = $conn->prepare("INSERT INTO wishlist (product_id) VALUES (?)");
            while ($row = mysqli_fetch_assoc($prodRes)) {
                $pid = $row['id'];
                $stmt->bind_param("i", $pid);
                $stmt->execute();
            }
            echo "Initial wishlist data inserted successfully.\n";
        } else {
            echo "No products found to add to wishlist.\n";
        }
    } else {
        echo "Wishlist data already exists.\n";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
