<?php
$file = 'product-detail.php';
$content = file_get_contents($file);

// Add session_start
if (strpos($content, 'session_start()') === false) {
    $content = preg_replace('/<\?php/', "<?php\nsession_start();", $content, 1);
}

// Add wishlist check logic
$logic = <<<'EOD'
$is_wishlisted = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wish_check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $uid AND product_id = $product_id");
    if (mysqli_num_rows($wish_check) > 0) {
        $is_wishlisted = true;
    }
}
EOD;

if (strpos($content, '$is_wishlisted') === false) {
    $content = str_replace('$sizes = [];', $logic . "\n\$sizes = [];", $content);
}

// Update button
$old_btn = '<button class="product-wishlist-icon">';
$new_btn = '<button class="product-wishlist-icon <?= $is_wishlisted ? \'active\' : \'\' ?>" id="wishlistBtn" data-product-id="<?= $product_id ?>" onclick="toggleWishlistDetail(this)">';

if (strpos($content, 'onclick="toggleWishlistDetail(this)"') === false) {
    $content = str_replace($old_btn, $new_btn, $content);
}

// Add CSS
$css_old = '.product-wishlist-icon:hover {
            color: #E50010;
            transform: scale(1.1);
        }';
$css_new = '.product-wishlist-icon:hover {
            color: #E50010;
            transform: scale(1.1);
        }

        .product-wishlist-icon.active {
            color: #E50010;
        }';

if (strpos($content, '.product-wishlist-icon.active') === false) {
    if (strpos($content, $css_old) !== false) {
        $content = str_replace($css_old, $css_new, $content);
    } else {
        // Fallback: search for a smaller snippet of CSS
        $content = str_replace('color: #222222;', "color: #222222;\n            transition: all 0.3s ease;\n        }\n\n        .product-wishlist-icon.active {\n            color: #E50010;\n        }", $content);
    }
}

file_put_contents($file, $content);
echo "Updated product-detail.php successfully\n";
?>
