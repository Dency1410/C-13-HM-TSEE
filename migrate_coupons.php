<?php
require_once 'includes/db.php';

// 1. Create coupons table
$sql1 = "CREATE TABLE IF NOT EXISTS `coupons` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `coupon_code` varchar(50) NOT NULL,
    `discount_type` enum('Percentage', 'Fixed') NOT NULL DEFAULT 'Percentage',
    `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
    `valid_until` date NOT NULL,
    `max_uses` int(11) NOT NULL DEFAULT 0,
    `current_uses` int(11) NOT NULL DEFAULT 0,
    `status` enum('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `coupon_code` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conn, $sql1)) {
    echo "Coupons table created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

// 2. Alter orders table
// Add coupon_code if it doesn't exist
$res = mysqli_query($conn, "SHOW COLUMNS FROM `orders` LIKE 'coupon_code'");
if (mysqli_num_rows($res) == 0) {
    if (mysqli_query($conn, "ALTER TABLE `orders` ADD `coupon_code` varchar(50) DEFAULT NULL AFTER `total`")) {
        echo "Added coupon_code to orders.\n";
    }
}

// Add discount_amount if it doesn't exist
$res = mysqli_query($conn, "SHOW COLUMNS FROM `orders` LIKE 'discount_amount'");
if (mysqli_num_rows($res) == 0) {
    if (mysqli_query($conn, "ALTER TABLE `orders` ADD `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`")) {
        echo "Added discount_amount to orders.\n";
    }
}

echo "Database migrations complete.\n";
