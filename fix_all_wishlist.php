<?php
$files = ['men.php', 'ladies.php', 'kids.php'];

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $content = file_get_contents($f);

    // 1. Add session_start
    if (strpos($content, 'session_start()') === false) {
        $content = preg_replace('/<\?php/', "<?php\nsession_start();", $content, 1);
    }

    // 2. Add wishlist fetching logic at the top
    $fetch_logic = <<<'EOD'
$wishlisted_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wish_res = mysqli_query($conn, "SELECT product_id FROM wishlist WHERE user_id = $uid");
    while ($wrow = mysqli_fetch_assoc($wish_res)) {
        $wishlisted_ids[] = $wrow['product_id'];
    }
}
EOD;

    if (strpos($content, '$wishlisted_ids = [];') === false) {
        $content = str_replace('$products_result = mysqli_query($conn, $query);', $fetch_logic . "\n\$products_result = mysqli_query(\$conn, \$query);", $content);
    }

    // 3. Add JS function
    $js_function = <<<'JS'
function toggleWishlist(btn) {
    const productId = btn.getAttribute('data-product-id');
    fetch('toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                btn.classList.add('active');
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.classList.remove('active');
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
        } else {
            alert(data.message);
            if (data.message.includes('login')) {
                window.location.href = 'login.php';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
JS;

    if (strpos($content, 'function toggleWishlist(') === false) {
        $content = str_replace('</script>', $js_function . "\n</script>", $content);
    }

    // 4. Update button rendering
    $old_btn = '<button class="product-wishlist-btn" onclick="event.preventDefault(); addToWishlist(<?= $product[\'id\'] ?>)">
                                    <i class="far fa-heart"></i>
                                </button>';
    
    $new_btn = '<?php $is_fav = in_array($product[\'id\'], $wishlisted_ids); ?>
                                <button class="product-wishlist-btn <?= $is_fav ? \'active\' : \'\' ?>" data-product-id="<?= $product[\'id\'] ?>" onclick="event.preventDefault(); toggleWishlist(this)">
                                    <i class="<?= $is_fav ? \'fas\' : \'far\' ?> fa-heart"></i>
                                </button>';
    
    $content = str_replace($old_btn, $new_btn, $content);

    // 5. Add CSS for active state
    $css_active = '.product-wishlist-btn.active { color: #E50010; opacity: 1; }';
    if (strpos($content, '.product-wishlist-btn.active') === false) {
        $content = str_replace('</style>', $css_active . "\n</style>", $content);
    }

    file_put_contents($f, $content);
    echo "Updated $f\n";
}
?>
