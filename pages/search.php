<?php
/**
 * Search Page
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$q = trim(get_param('q', ''));
$pageTitle = $q ? "Search: {$q}" : 'Search — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

$results = [];
if (strlen($q) >= 1) {
    $results = search_products($q, $tenantId);
}

$searchLink = $tenant ? "/t/{$tenant['slug']}/search" : '/search';
$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';

// Discover suggestions
$discover = ['shoes', 't shirts', 'laptops', 'watches', 'tv', 'sarees', 'headphones', 'bluetooth', 'fridge', 'bedsheet', 'water bottle', 'jeans'];

$theme = get_theme();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="search-page">
    <!-- Header -->
    <header class="search-header">
        <a href="<?= e($homeLink) ?>" class="back-btn" aria-label="Back">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
        <form action="<?= e($searchLink) ?>" method="GET" class="search-form">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input 
                type="text" 
                name="q" 
                value="<?= e($q) ?>" 
                placeholder="Search for Products, Brands and More"
                class="search-input"
                autofocus
            >
            <?php if ($q): ?>
            <a href="<?= e($searchLink) ?>" class="clear-btn" aria-label="Clear">✕</a>
            <?php endif; ?>
        </form>
    </header>

    <?php if (!$q): ?>
    <!-- Discover More -->
    <div class="discover-section">
        <p class="discover-title">Discover More</p>
        <div class="discover-tags">
            <?php foreach ($discover as $tag): ?>
            <a href="<?= e($searchLink) ?>?q=<?= urlencode($tag) ?>" class="discover-tag"><?= e($tag) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif (empty($results)): ?>
    <div class="no-results">
        <p>No products found for "<?= e($q) ?>"</p>
    </div>
    <?php else: ?>
    <!-- Search Results -->
    <div class="search-results">
        <?php foreach ($results as $product): 
            $images = $product['product_images'] ?? [];
            usort($images, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
            $img = $images[0] ?? null;
            $price = (float) $product['price'];
            $mrp = !empty($product['mrp']) ? (float) $product['mrp'] : null;
            $pct = calc_discount($price, $mrp, (int) ($product['discount_percent'] ?? 0));
            $rating = !empty($product['rating']) ? (float) $product['rating'] : 0;
            $productLink = $tenant 
                ? "/t/{$tenant['slug']}/product/{$product['slug']}" 
                : "/product/{$product['slug']}";
        ?>
        <a href="<?= e($productLink) ?>" class="search-result-item">
            <div class="result-image">
                <?php if ($img): ?>
                <img src="<?= e(img_url($img['url'], ['w' => 160, 'h' => 160])) ?>" alt="<?= e($product['title']) ?>" loading="lazy">
                <?php else: ?>
                <div class="placeholder-img">📦</div>
                <?php endif; ?>
            </div>
            <div class="result-info">
                <p class="result-title"><?= e($product['title']) ?></p>
                <?php if ($rating > 0): ?>
                <div class="result-rating">
                    <span class="rating-badge"><?= number_format($rating, 1) ?> ★</span>
                    <?php if (!empty($product['rating_count'])): ?>
                    <span class="rating-count">(<?= number_format($product['rating_count']) ?>)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="result-price-row">
                    <?php if ($pct > 0): ?>
                    <span class="item-discount">↓<?= $pct ?>%</span>
                    <?php endif; ?>
                    <?php if ($mrp && $mrp > $price): ?>
                    <span class="item-mrp">₹<?= number_format($mrp, 0, '.', ',') ?></span>
                    <?php endif; ?>
                    <span class="item-price">₹<?= number_format($price, 0, '.', ',') ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
