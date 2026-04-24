<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS `home_categories` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `title`         VARCHAR(100) NOT NULL,
    `description`   VARCHAR(255) DEFAULT '',
    `image_url`     VARCHAR(500) DEFAULT '',
    `link_url`      VARCHAR(500) DEFAULT '',
    `display_order` INT DEFAULT 0,
    `is_active`     TINYINT(1) DEFAULT 1,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color:green;font-weight:bold;'>✅ Table <code>home_categories</code> created (or already exists).</p>";

    // Insert default data if table is empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM home_categories");
    $row   = mysqli_fetch_assoc($check);
    if ($row['cnt'] == 0) {
        $defaults = [
            ["Ladies", "Explore the latest trends in women's fashion", "https://images.unsplash.com/photo-1483985988355-763728e1935b?w=800&q=80", "ladies.php", 1],
            ["Men",    "Explore the latest trends in men's fashion",   "https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?w=800&q=80", "men.php",    2],
            ["Kids",   "Explore the latest trends in kids's fashion",  "https://images.unsplash.com/photo-1503944583220-79d8926ad5e2?w=800&q=80", "kids.php",   3],
        ];
        foreach ($defaults as $d) {
            $t = mysqli_real_escape_string($conn, $d[0]);
            $desc = mysqli_real_escape_string($conn, $d[1]);
            $img  = mysqli_real_escape_string($conn, $d[2]);
            $lnk  = mysqli_real_escape_string($conn, $d[3]);
            $ord  = (int)$d[4];
            mysqli_query($conn, "INSERT INTO home_categories (title, description, image_url, link_url, display_order, is_active) VALUES ('$t','$desc','$img','$lnk',$ord,1)");
        }
        echo "<p style='color:blue;'>✅ Default categories (Ladies, Men, Kids) inserted.</p>";
    } else {
        echo "<p style='color:orange;'>ℹ️ Table already has data — no defaults inserted.</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Error: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='home.php'>→ Go to Home Page</a> | <a href='admin/manage_home_categories.php'>→ Manage Categories</a></p>";
?>
