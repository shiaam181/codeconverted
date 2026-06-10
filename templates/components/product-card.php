<?php
/**
 * Product Card Component — Flipkart style
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$images = $product['product_images'] ?? [];
usort($images, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
$img = $images[0] ?? null;

$price = (float) $product['price'];
$mrp = !empty($product['mrp']) ? (float) $product['mrp'] : null;
$discount = calc_discount($price, $mrp, (int) ($product['discount_percent'] ?? 0));
$rating = !empty($product['rating']) ? max(4.0, (float) $product['rating']) : 4.2;
$bankOffer = max(0, round($price * 0.89)); // ~11% bank discount
$deliveryDate = date('j M', strtotime('+3 days'));

$productLink = $tenant 
    ? "/t/{$tenant['slug']}/product/{$product['slug']}" 
    : "/product/{$product['slug']}";
?>
<a href="<?= e($productLink) ?>" class="product-card">
    <div class="product-image-wrapper">
        <?php if ($img): ?>
        <img 
            src="<?= e(img_url($img['url'], ['w' => 320, 'h' => 320, 'resize' => 'contain'])) ?>" 
            alt="<?= e($product['title']) ?>"
            class="product-image"
            loading="lazy"
            decoding="async"
        >
        <?php else: ?>
        <div class="product-placeholder">📦</div>
        <?php endif; ?>
        <div class="product-rating-badge">
            <?= number_format($rating, 1) ?> <span class="star">★</span>
        </div>
    </div>
    <div class="product-info">
        <h3 class="product-title"><?= e($product['title']) ?></h3>
        <?php if ($discount > 0): ?>
        <p class="product-discount"><?= $discount ?>% OFF</p>
        <?php endif; ?>
        <div class="product-price-row">
            <?php if ($mrp && $mrp > $price): ?>
            <span class="product-mrp">₹<?= number_format($mrp, 0, '.', ',') ?></span>
            <?php endif; ?>
            <span class="product-price">₹<?= number_format($price, 0, '.', ',') ?></span>
        </div>
        <p class="product-bank-offer">
            <span class="bank-price">₹<?= number_format($bankOffer, 0, '.', ',') ?></span> <span class="bank-text">with Bank offer</span>
        </p>
        <p class="product-delivery">Get it by <strong><?= $deliveryDate ?></strong></p>
    </div>
</a>
