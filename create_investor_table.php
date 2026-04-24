<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS investor_page (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_title VARCHAR(255) DEFAULT 'Investors',
    hero_image VARCHAR(500) DEFAULT '',
    contact_section_title VARCHAR(255) DEFAULT 'Contact',
    contact_name VARCHAR(255) DEFAULT '',
    contact_phone VARCHAR(100) DEFAULT '',
    contact_email VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

if (mysqli_query($conn, $sql)) {
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM investor_page");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] == 0) {
        $insert = "INSERT INTO investor_page (page_title, hero_image, contact_section_title, contact_name, contact_phone, contact_email)
        VALUES (
            'Investors',
            'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=800&q=80',
            'Contact',
            'Joseph Ahlberg',
            '+46 8 7965500',
            'info@hm.com'
        )";
        mysqli_query($conn, $insert);
        echo "<p style='color:green;font-family:sans-serif;'>✔ investor_page table created and default data inserted!</p>";
    } else {
        echo "<p style='color:green;font-family:sans-serif;'>✔ Table already exists. No changes made.</p>";
    }
} else {
    echo "<p style='color:red;font-family:sans-serif;'>✘ Error: " . mysqli_error($conn) . "</p>";
}
?>
