<?php
/**
 * Product Card Component
 * Usage: include with $product variable set
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$images = $product['product_images'] ?? [];
usort($images, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
$img = $images[0] ?? null;

$price = (float) $product['price'];
$mrp = !empty($product['mrp']) ? (float) $product['mrp'] : null;
$discount = calc_discount($price, $mrp, (int) ($product['discount_percent'] ?? 0));
$rating = !empty($product['rating']) ? (float) $product['rating'] : 0;
$buyAt = max(0, round($price - 50));

$productLink = $tenant 
    ? "/t/{$tenant['slug']}/product/{$product['slug']}" 
    : "/product/{$product['slug']}";
?>
<a href="<?= e($productLink) ?>" class="product-card">
    <div class="product-image-wrapper">
        <?php if ($img): ?>
        <img 
            src="<?= e(img_url($img['url'], ['w' => 320, 'h' => 320, 'resize' => 'cover'])) ?>" 
            alt="<?= e($product['title']) ?>"
            class="product-image"
            loading="lazy"
            decoding="async"
        >
        <?php else: ?>
        <div class="product-placeholder">📦</div>
        <?php endif; ?>
        <?php if ($rating > 0): ?>
        <div class="product-rating-badge">
            <?= number_format($rating, 1) ?> ★
        </div>
        <?php endif; ?>
    </div>
    <div class="product-info">
        <h3 class="product-title"><?= e($product['title']) ?></h3>
        <div class="product-price-row">
            <?php if ($mrp && $mrp > $price): ?>
            <span class="product-mrp">₹<?= number_format($mrp, 0, '.', ',') ?></span>
            <?php endif; ?>
            <span class="product-price">₹<?= number_format($price, 0, '.', ',') ?></span>
        </div>
        <div class="product-deal">
            <?php if ($discount > 0): ?>
            Buy at ₹<?= number_format($buyAt, 0, '.', ',') ?>
            <?php else: ?>
            &nbsp;
            <?php endif; ?>
        </div>
    </div>
</a>
