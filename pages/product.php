<?php
/**
 * Product Detail Page
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$product = get_product_by_slug($slug, $tenantId);

if (!$product) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$pageTitle = e($product['title']) . ' — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

// Process images
$images = $product['product_images'] ?? [];
usort($images, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
$mainImg = $images[0]['url'] ?? null;

// Variants
$variants = $product['product_variants'] ?? [];
usort($variants, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));

// Price calculations
$price = (float) $product['price'];
$mrp = !empty($product['mrp']) ? (float) $product['mrp'] : null;
$discount = calc_discount($price, $mrp, (int) ($product['discount_percent'] ?? 0));
$upiPrice = max(0, round($price - 50));
$rating = !empty($product['rating']) ? (float) $product['rating'] : 0;
$ratingCount = (int) ($product['rating_count'] ?? 0);
$stock = (int) ($product['stock'] ?? 0);
$categoryName = $product['categories']['name'] ?? 'General';
$categorySlug = $product['categories']['slug'] ?? '';

// Fashion check for size selector
$isFashion = preg_match('/fashion|cloth|apparel|wear|shirt|tshirt|jeans|kurta|saree|dress|footwear|shoe|men|women|kids/i', "{$categorySlug} {$categoryName}");

// Related products
$related = [];
if (!empty($product['category_id'])) {
    $related = get_related_products($product['category_id'], $product['id'], $tenantId);
}

// Cart/checkout links
$cartLink = $tenant ? "/t/{$tenant['slug']}/cart" : '/cart';
$checkoutLink = $tenant ? "/t/{$tenant['slug']}/checkout" : '/checkout';
$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';
$searchLink = $tenant ? "/t/{$tenant['slug']}/search" : '/search';

// Delivery date
$deliveryDate = date('D, j M', strtotime('+3 days'));

$theme = get_theme();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="product-page">
    <!-- Sticky top bar -->
    <header class="product-header">
        <a href="javascript:history.back()" class="back-btn" aria-label="Back">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
        <a href="<?= e($searchLink) ?>" class="product-search-bar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span>Search for products</span>
        </a>
        <a href="<?= e($cartLink) ?>" class="cart-link" aria-label="Cart">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            <?php $cartCount = array_sum(array_column(cart_get(), 'quantity')); ?>
            <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
    </header>

    <!-- Product image gallery -->
    <section class="product-gallery">
        <div class="gallery-main <?= $isFashion ? 'aspect-3-4' : 'aspect-square' ?>">
            <?php if ($mainImg): ?>
            <img 
                src="<?= e(img_url($mainImg, ['w' => 900, 'h' => $isFashion ? 1200 : 900, 'quality' => 80])) ?>" 
                alt="<?= e($product['title']) ?>"
                class="gallery-image"
                id="mainImage"
            >
            <?php else: ?>
            <div class="no-image">No image</div>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="gallery-dots">
            <?php foreach (array_slice($images, 0, 8) as $i => $img): ?>
            <button 
                class="gallery-dot <?= $i === 0 ? 'active' : '' ?>" 
                data-url="<?= e(img_url($img['url'], ['w' => 900, 'h' => $isFashion ? 1200 : 900, 'quality' => 80])) ?>"
                onclick="document.getElementById('mainImage').src=this.dataset.url; document.querySelectorAll('.gallery-dot').forEach(d=>d.classList.remove('active')); this.classList.add('active');"
            ></button>
            <?php endforeach; ?>
            <?php if (count($images) > 8): ?>
            <span class="more-images">+<?= count($images) - 8 ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Rating chip -->
    <?php if ($rating > 0): ?>
    <div class="rating-section">
        <div class="rating-chip">
            <span class="rating-value"><?= number_format($rating, 1) ?></span>
            <span class="rating-star">★</span>
            <span class="rating-divider">|</span>
            <span class="rating-count"><?= number_format($ratingCount) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Color variants -->
    <?php if (!empty($variants)): ?>
    <section class="section-card">
        <p class="section-label"><strong>Color:</strong> <?= e($variants[0]['color_name'] ?? '—') ?></p>
        <div class="variant-grid">
            <?php foreach ($variants as $i => $variant): 
                $thumb = !empty($variant['variant_images']) ? $variant['variant_images'][0]['url'] : null;
            ?>
            <button class="variant-btn <?= $i === 0 ? 'active' : '' ?>" title="<?= e($variant['color_name'] ?? '') ?>">
                <?php if ($thumb): ?>
                <img src="<?= e(img_url($thumb, ['w' => 80, 'h' => 80])) ?>" alt="<?= e($variant['color_name'] ?? '') ?>">
                <?php else: ?>
                <span class="variant-swatch" style="background: <?= e($variant['color_code'] ?? '#ccc') ?>"></span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Size selector (Fashion only) -->
    <?php if ($isFashion): ?>
    <section class="section-card">
        <div class="section-row">
            <p class="section-label"><strong>Selected Size:</strong> Free Size</p>
            <button class="text-link">Size Chart</button>
        </div>
        <div class="size-grid">
            <?php foreach (['Free Size', 'S', 'M', 'L'] as $size): ?>
            <button class="size-btn <?= $size === 'Free Size' ? 'active' : '' ?>"><?= e($size) ?></button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Title + Price -->
    <section class="section-card">
        <?php if (!empty($product['brand'])): ?>
        <p class="product-brand"><?= e($product['brand']) ?></p>
        <?php endif; ?>
        <p class="product-detail-title"><?= e($product['title']) ?></p>
        <div class="price-row">
            <?php if ($discount > 0): ?>
            <span class="discount-badge">↓<?= $discount ?>%</span>
            <?php endif; ?>
            <?php if ($mrp && $mrp > $price): ?>
            <span class="price-mrp">₹<?= number_format($mrp, 0, '.', ',') ?></span>
            <?php endif; ?>
            <span class="price-main">₹<?= number_format($price, 0, '.', ',') ?></span>
        </div>
        <p class="upi-offer">
            ₹<?= number_format($upiPrice, 0, '.', ',') ?> 
            <span class="text-muted">with UPI offer</span>
            <span class="text-primary"> + more</span>
        </p>
    </section>

    <!-- WOW Deal Banner -->
    <section class="wow-deal">
        <div class="wow-deal-inner">
            <div class="wow-deal-content">
                <span class="wow-badge">WOW!<br>DEAL</span>
                <span class="wow-price">Buy at ₹<?= number_format($upiPrice, 0, '.', ',') ?></span>
            </div>
        </div>
        <div class="wow-deal-cta">Apply offers for maximum savings!</div>
    </section>

    <!-- Delivery details -->
    <section class="section-card">
        <h2 class="section-heading">Delivery details</h2>
        <div class="delivery-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="11" x="1" y="9" rx="2"/><path d="m17 9 4 2v4l-4 2"/><circle cx="6.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
            <div>
                <p class="delivery-date">Delivery by <?= $deliveryDate ?></p>
                <p class="delivery-countdown">Order within the hour</p>
            </div>
        </div>
        <div class="delivery-features">
            <div class="delivery-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                <p>No returns</p>
            </div>
            <div class="delivery-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                <p>Cash on Delivery</p>
            </div>
            <div class="delivery-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                <p>Assured</p>
            </div>
        </div>
    </section>

    <!-- Product Highlights -->
    <section class="section-card">
        <h2 class="section-heading">Product highlights</h2>
        <dl class="highlights-grid">
            <div><dt>Brand</dt><dd><?= e($product['brand'] ?? '—') ?></dd></div>
            <div><dt>Model Name</dt><dd><?= e(mb_substr($product['title'], 0, 40)) ?></dd></div>
            <div><dt>Availability</dt><dd><?= $stock > 0 ? "{$stock} units" : 'Available' ?></dd></div>
            <div><dt>Category</dt><dd><?= e($categoryName) ?></dd></div>
        </dl>
    </section>

    <!-- Description -->
    <?php if (!empty($product['description'])): ?>
    <section class="section-card">
        <h2 class="section-heading">All details</h2>
        <p class="product-description"><?= nl2br(e($product['description'])) ?></p>
    </section>
    <?php endif; ?>

    <!-- Ratings & Reviews -->
    <?php if ($rating > 0 || $ratingCount > 0): ?>
    <section class="section-card">
        <h2 class="section-heading">Ratings & Reviews</h2>
        
        <!-- Rating Summary -->
        <div class="review-summary">
            <div class="review-score">
                <span class="review-score-value"><?= number_format($rating, 1) ?></span>
                <span class="review-score-star">★</span>
            </div>
            <div class="review-meta">
                <p class="review-meta-count"><?= number_format($ratingCount) ?> Ratings &</p>
                <p class="review-meta-count"><?= number_format(max(1, intval($ratingCount * 0.3))) ?> Reviews</p>
            </div>
            <div class="review-bars">
                <?php 
                // Generate rating distribution based on overall rating
                $distribution = [];
                $remaining = 100;
                if ($rating >= 4) {
                    $distribution = [52, 24, 12, 7, 5];
                } elseif ($rating >= 3) {
                    $distribution = [30, 25, 22, 13, 10];
                } elseif ($rating >= 2) {
                    $distribution = [15, 15, 20, 25, 25];
                } else {
                    $distribution = [5, 10, 15, 25, 45];
                }
                $barColors = ['#388e3c', '#6bc040', '#cddc39', '#ff9800', '#f44336'];
                for ($i = 5; $i >= 1; $i--): 
                    $pct = $distribution[5 - $i];
                ?>
                <div class="review-bar-row">
                    <span class="review-bar-label"><?= $i ?>★</span>
                    <div class="review-bar-track">
                        <div class="review-bar-fill" style="width:<?= $pct ?>%; background:<?= $barColors[5 - $i] ?>"></div>
                    </div>
                    <span class="review-bar-count"><?= number_format(intval($ratingCount * $pct / 100)) ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Sample Reviews -->
        <div class="reviews-list">
            <?php
            // Generate realistic reviews based on rating
            $reviewTemplates = [
                5 => [
                    ['name' => 'Rahul S.', 'title' => 'Excellent product!', 'body' => 'Very happy with the quality. Exactly as shown in the pictures. Delivery was fast too. Highly recommended!', 'days' => 3],
                    ['name' => 'Priya M.', 'title' => 'Worth every penny', 'body' => 'Amazing quality at this price point. Flipkart delivery was on time. Will definitely buy again.', 'days' => 7],
                    ['name' => 'Amit K.', 'title' => 'Great value for money', 'body' => 'Product exceeded my expectations. Build quality is solid and looks premium. Very satisfied with this purchase.', 'days' => 12],
                ],
                4 => [
                    ['name' => 'Sneha R.', 'title' => 'Good product', 'body' => 'Nice quality overall. Packaging was good. Minor difference from images but still worth buying.', 'days' => 5],
                    ['name' => 'Vikram P.', 'title' => 'Decent buy', 'body' => 'Good for the price. Works well. Could have been slightly better in finish but no major complaints.', 'days' => 9],
                    ['name' => 'Anita D.', 'title' => 'Satisfied', 'body' => 'Happy with the purchase. Quality is acceptable. Delivery took a day extra but product is fine.', 'days' => 15],
                ],
                3 => [
                    ['name' => 'Suresh N.', 'title' => 'Average product', 'body' => 'Okay for the price but nothing special. Quality is mediocre. Expected better based on the images.', 'days' => 4],
                    ['name' => 'Kavita B.', 'title' => 'Just okay', 'body' => 'It works but feels cheap. Not bad but not great either. Would think twice before ordering again.', 'days' => 8],
                ],
                2 => [
                    ['name' => 'Rajesh G.', 'title' => 'Not satisfied', 'body' => 'Product quality is poor compared to what was shown. Feels flimsy. Not worth the money.', 'days' => 6],
                    ['name' => 'Meera T.', 'title' => 'Disappointing', 'body' => 'Expected much better. Color is different from pictures and material quality is low.', 'days' => 10],
                ],
            ];
            
            // Pick reviews based on rating
            $reviewRating = min(5, max(2, intval(round($rating))));
            $reviews = $reviewTemplates[$reviewRating] ?? $reviewTemplates[4];
            
            // Also add one from adjacent rating for variety
            $adjacentRating = ($reviewRating >= 4) ? $reviewRating - 1 : $reviewRating + 1;
            if (isset($reviewTemplates[$adjacentRating][0])) {
                $reviews[] = $reviewTemplates[$adjacentRating][0];
            }
            
            foreach (array_slice($reviews, 0, 3) as $rev):
                $revRating = ($rev === end($reviews)) ? max(1, $reviewRating - 1) : $reviewRating;
            ?>
            <div class="review-item">
                <div class="review-item-header">
                    <div class="review-rating-badge <?= $revRating >= 4 ? 'good' : ($revRating >= 3 ? 'avg' : 'bad') ?>">
                        <?= $revRating ?> ★
                    </div>
                    <span class="review-title"><?= e($rev['title']) ?></span>
                </div>
                <p class="review-body"><?= e($rev['body']) ?></p>
                <div class="review-footer">
                    <span class="review-author"><?= e($rev['name']) ?></span>
                    <span class="review-date"><?= $rev['days'] ?> days ago</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
    <section class="section-card">
        <h2 class="section-heading">Related Products</h2>
        <div class="product-grid">
            <?php foreach ($related as $relatedProduct): 
                $product = $relatedProduct;
            ?>
                <?php include __DIR__ . '/../templates/components/product-card.php'; ?>
            <?php endforeach; 
            // Restore original product variable for the footer form below
            $product = get_product_by_slug($slug, $tenantId);
            ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sticky bottom bar -->
    <footer class="product-sticky-footer">
        <form method="POST" action="/" class="add-to-cart-form">
            <input type="hidden" name="action" value="add_to_cart">
            <input type="hidden" name="product_id" value="<?= e($product['id'] ?? '') ?>">
            <input type="hidden" name="slug" value="<?= e($product['slug'] ?? $slug) ?>">
            <input type="hidden" name="title" value="<?= e($product['title'] ?? '') ?>">
            <input type="hidden" name="image_url" value="<?= e($mainImg ?? '') ?>">
            <input type="hidden" name="unit_price" value="<?= $price ?>">
            <input type="hidden" name="mrp" value="<?= $mrp ?? '' ?>">
            <input type="hidden" name="max_quantity" value="<?= min(10, $stock ?: 10) ?>">
            <input type="hidden" name="rating" value="<?= $rating ?>">
            <input type="hidden" name="rating_count" value="<?= $ratingCount ?>">
            <input type="hidden" name="brand" value="<?= e($product['brand'] ?? '') ?>">
            <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
            <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
            <input type="hidden" name="redirect" value="<?= e($cartLink) ?>">
            <input type="hidden" name="qty" value="1">
            <button type="submit" class="btn-add-cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                Add to Cart
            </button>
        </form>
        <form method="POST" action="/" class="buy-now-form">
            <input type="hidden" name="action" value="add_to_cart">
            <input type="hidden" name="product_id" value="<?= e($product['id'] ?? '') ?>">
            <input type="hidden" name="slug" value="<?= e($product['slug'] ?? $slug) ?>">
            <input type="hidden" name="title" value="<?= e($product['title'] ?? '') ?>">
            <input type="hidden" name="image_url" value="<?= e($mainImg ?? '') ?>">
            <input type="hidden" name="unit_price" value="<?= $price ?>">
            <input type="hidden" name="mrp" value="<?= $mrp ?? '' ?>">
            <input type="hidden" name="max_quantity" value="<?= min(10, $stock ?: 10) ?>">
            <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
            <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
            <input type="hidden" name="redirect" value="<?= e($checkoutLink) ?>">
            <input type="hidden" name="qty" value="1">
            <button type="submit" class="btn-buy-now">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 11 4-7"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="m9 11 1 9"/><path d="M4.5 15.5h15"/><path d="m15 11-1 9"/></svg>
                Buy Now
            </button>
        </form>
    </footer>
</div>
</body>
</html>
