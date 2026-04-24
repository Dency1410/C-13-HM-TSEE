<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS about_us (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE,
    hero_title VARCHAR(255) DEFAULT 'About us',
    intro_title TEXT,
    intro_text TEXT,
    stat1_number VARCHAR(50) DEFAULT '',
    stat1_label VARCHAR(255) DEFAULT '',
    stat2_number VARCHAR(50) DEFAULT '',
    stat2_label VARCHAR(255) DEFAULT '',
    stat3_number VARCHAR(50) DEFAULT '',
    stat3_label VARCHAR(255) DEFAULT '',
    stat4_number VARCHAR(50) DEFAULT '',
    stat4_label VARCHAR(255) DEFAULT '',
    stat_reference TEXT,
    our_way_title VARCHAR(255) DEFAULT 'Our way',
    our_way_description TEXT,
    our_way_image VARCHAR(255) DEFAULT '',
    col1_title VARCHAR(100) DEFAULT '',
    col1_text TEXT,
    col1_image VARCHAR(255) DEFAULT '',
    col2_title VARCHAR(100) DEFAULT '',
    col2_text TEXT,
    col2_image VARCHAR(255) DEFAULT '',
    col3_title VARCHAR(100) DEFAULT '',
    col3_text TEXT,
    col3_image VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

if (mysqli_query($conn, $sql)) {
    // Insert default data if table is empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM about_us");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] == 0) {
        $insert = "INSERT INTO about_us (section_key, hero_title, intro_title, intro_text,
            stat1_number, stat1_label, stat2_number, stat2_label,
            stat3_number, stat3_label, stat4_number, stat4_label,
            stat_reference, our_way_title, our_way_description, our_way_image,
            col1_title, col1_text, col1_image,
            col2_title, col2_text, col2_image,
            col3_title, col3_text, col3_image)
        VALUES (
            'main',
            'About us',
            'H&M Group is a global fashion and design company, with over 4,000 stores in more than 79 markets and online sales in 60 markets.',
            'All our brands and ventures share the same passion for making great and more sustainable fashion and design available to everyone. Each brand has its own unique identity, and together they complement each other and strengthen H&M Group – all to offer our customers unbeatable value and to enable a more circular lifestyle.',
            '79', 'markets (2024)',
            '140,000', 'employees globally (2024)',
            '234', 'billion SEK in net sales in 2024',
            '89%', 'recycled or sustainably sourced materials in our commercial products (2024)',
            'Figures from our Annual and sustainability report 2024.',
            'Our way',
            'We follow all regulations in the markets where we operate and aim to do the right thing. Acting with consistency and strong ethics helps us remain a trusted company and partner.',
            'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=400&q=80',
            'OUR HISTORY', 'From a single store in 1947 we had grown into a family of brands, offering fashion and design to customers worldwide.', 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=600&q=80',
            'OUR VALUES', 'At H&M Group, we are guided by shared values. They are part of who we are, what we stand for and how we act.', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80',
            'INCLUSION & DIVERSITY', 'We are a value-driven company that strives to lead the way to a more inclusive and circular fashion future.', 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=600&q=80'
        )";
        mysqli_query($conn, $insert);
        echo "<p style='color:green;font-family:sans-serif;'>✔ Table created and default data inserted!</p>";
    } else {
        echo "<p style='color:green;font-family:sans-serif;'>✔ Table already exists. No changes made.</p>";
    }
} else {
    echo "<p style='color:red;font-family:sans-serif;'>✘ Error: " . mysqli_error($conn) . "</p>";
}
?>
