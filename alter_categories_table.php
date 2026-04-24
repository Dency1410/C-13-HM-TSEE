<?php
require_once 'includes/db.php';

// Safe drop using try/catch
try {
    mysqli_query($conn, "ALTER TABLE categories DROP COLUMN slug;");
    echo "<p style='color:green;'>✅ Dropped `slug` column successfully.</p>";
} catch (Exception $e) {
    echo "<p style='color:orange;'>ℹ️ `slug` already dropped or error: " . $e->getMessage() . "</p>";
}

try {
    mysqli_query($conn, "ALTER TABLE categories ADD COLUMN gender ENUM('Ladies', 'Men', 'Kids', 'Kids Girl', 'Kids Boy') NOT NULL DEFAULT 'Ladies';");
    echo "<p style='color:green;'>✅ Added `gender` column successfully.</p>";
} catch (Exception $e) {
    echo "<p style='color:orange;'>ℹ️ `gender` already exists or error: " . $e->getMessage() . "</p>";
}

mysqli_query($conn, "UPDATE categories SET gender='Ladies' WHERE name LIKE '%dress%' OR name LIKE '%skirt%' OR name LIKE '%ladies%'");
mysqli_query($conn, "UPDATE categories SET gender='Men' WHERE name LIKE '%men%' OR name LIKE '%suit%'");
mysqli_query($conn, "UPDATE categories SET gender='Kids' WHERE name LIKE '%kid%' OR name LIKE '%boy%' OR name LIKE '%girl%'");

echo "<p><a href='admin/manage_categories.php'>→ Go to Manage Categories</a></p>";
?>
