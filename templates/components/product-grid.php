<?php
/**
 * Product Grid Component
 * Usage: include with $products array set
 */

$gridTitle = $gridTitle ?? 'Products';
$products = $products ?? [];
?>
<section class="product-grid-section">
    <h3 class="section-title"><?= e($gridTitle) ?></h3>
    <?php if (empty($products)): ?>
    <p class="empty-message">No products yet.</p>
    <?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
