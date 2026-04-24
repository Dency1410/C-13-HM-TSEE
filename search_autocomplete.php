<?php
require_once __DIR__ . '/includes/db.php';

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. Search Categories
$cat_query = "SELECT id, name, gender FROM categories WHERE name LIKE '%$search%' LIMIT 3";
$cat_res = mysqli_query($conn, $cat_query);
if ($cat_res) {
    while ($row = mysqli_fetch_assoc($cat_res)) {
        $results[] = [
            'type' => 'category',
            'title' => htmlspecialchars($row['name']) . ' (' . htmlspecialchars($row['gender']) . ')',
            'url' => 'product.php?gender=' . urlencode($row['gender']) . '&category=' . $row['id']
        ];
    }
}

// 2. Search Products
$prod_query = "SELECT p.id, p.name, p.image, p.price, c.name as category_name 
               FROM products p 
               LEFT JOIN categories c ON p.category_id = c.id 
               WHERE p.name LIKE '%$search%' OR p.description LIKE '%$search%' 
               LIMIT 5";
$prod_res = mysqli_query($conn, $prod_query);
if ($prod_res) {
    while ($row = mysqli_fetch_assoc($prod_res)) {
        $img = $row['image'] ? $row['image'] : 'https://via.placeholder.com/50';
        $results[] = [
            'type' => 'product',
            'title' => htmlspecialchars($row['name']),
            'subtitle' => '$' . number_format($row['price'], 2) . ($row['category_name'] ? ' in ' . htmlspecialchars($row['category_name']) : ''),
            'image' => htmlspecialchars($img),
            'url' => 'product-detail.php?id=' . $row['id']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
?>
