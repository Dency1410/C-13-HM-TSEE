<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    product_id INT(11) NOT NULL,
    quantity INT(11) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'cart' created successfully.\n";

    // Insert dummy data if table is empty
    $check = mysqli_query($conn, "SELECT id FROM cart LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        // Find 2 products
        $prodRes = mysqli_query($conn, "SELECT id FROM products LIMIT 2");
        if ($prodRes && mysqli_num_rows($prodRes) > 0) {
            $stmt = $conn->prepare("INSERT INTO cart (product_id, quantity) VALUES (?, 1)");
            while ($row = mysqli_fetch_assoc($prodRes)) {
                $pid = $row['id'];
                $stmt->bind_param("i", $pid);
                $stmt->execute();
            }
            echo "Initial cart data inserted successfully.\n";
        } else {
            echo "No products found to add to cart.\n";
        }
    } else {
        echo "Cart data already exists.\n";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
