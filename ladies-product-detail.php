<?php $pageTitle = 'Product Detail - H&M'; include 'header.php'; 
// Fetch Reviews
$reviews_query = "SELECT r.*, u.full_name FROM product_reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = $product_id ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_query);
$reviews = [];
$total_rating = 0;
while ($r_row = mysqli_fetch_assoc($reviews_result)) {
    $reviews[] = $r_row;
    $total_rating += $r_row['rating'];
}
$reviews_count = count($reviews);
$average_rating = $reviews_count > 0 ? round($total_rating / $reviews_count, 1) : 0;
$full_stars = floor($average_rating);
$half_star = ($average_rating - $full_stars) >= 0.5 ? 1 : 0;
$empty_stars = 5 - $full_stars - $half_star;
?>
<!-- ═══════════════════════════════════
         PRODUCT DETAIL PAGE
    ═══════════════════════════════════ -->
    <div class="product-detail-container">
        <!-- Left Side - Product Images -->
        <div class="product-images-section" id="productImages">
            <!-- Images will be loaded here by JavaScript -->
        </div>

        <!-- Right Side - Product Details -->
        <div class="product-details-section" style="position: relative;">
            <button class="product-wishlist-icon">
                <i class="far fa-heart"></i>
            </button>

            <h1 class="product-detail-title" id="productTitle">Loading...</h1>

            <div class="product-price-section">
                <span class="product-sale-price" id="salePrice">Rs. 1,269.00</span>
                <span class="product-original-price" id="originalPrice">Rs. 1,499.00</span>
                <p class="product-tax-info">MRP inclusive of all taxes</p>
            </div>

            <!-- Size Selection -->
            <div class="size-section">
                <label class="size-label">Select Size</label>
                <div class="size-grid" id="sizeGrid">
                    <div class="size-option" data-size="XXS">XXS</div>
                    <div class="size-option" data-size="XS">XS</div>
                    <div class="size-option" data-size="S">S</div>
                    <div class="size-option" data-size="M">M</div>
                    <div class="size-option" data-size="L">L</div>
                    <div class="size-option" data-size="XL">XL</div>
                </div>
                <a class="size-guide-link" onclick="openSizeGuide()">SIZE GUIDE</a>
            </div>

            <!-- Add to Bag Button -->
            <button class="add-to-bag-btn" id="addToBagBtn">ADD</button>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <a class="reviews-link" onclick="openReviews()">REVIEWS [3]</a>
                </div>
                <div class="star-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span class="rating-number">4.7</span>
                </div>
            </div>

            <!-- Delivery Section -->
            <div class="delivery-section">
                <div class="delivery-title" onclick="toggleDelivery()">
                    DELIVERY, PAYMENT AND RETURNS
                    <i class="fas fa-plus"></i>
                </div>
                <div class="delivery-content" id="deliveryContent">
                    <p class="delivery-time">Delivery Time : 2-7 days</p>
                    <p class="delivery-text">
                        For health protection and hygiene reasons, returns are unavailable for underwear, swimwear,
                        piercing jewelry, perfumes/fragrances, face masks, hair tools, hair accessories, beauty
                        products/tools and cosmetics.
                    </p>
                    <p class="delivery-links">
                        For more information, please visit our <a href="customer-service.php">Customer Service</a>
                        pages.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
