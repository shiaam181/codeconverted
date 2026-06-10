<?php
/**
 * Category Page
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$sort = get_param('sort', 'popular');

$data = get_products_by_category($slug, $sort, $tenantId);
$category = $data['category'];
$products = $data['products'];

if (!$category) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$pageTitle = $category['name'] . ' — ' . (get_theme()['site_name'] ?? DEFAULT_SITE_NAME);
$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';
$categoryLink = $tenant ? "/t/{$tenant['slug']}/category/{$slug}" : "/category/{$slug}";

$theme = get_theme();

// Include full layout
require __DIR__ . '/../templates/header.php';
?>

<main class="category-page-content">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= e($homeLink) ?>">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current"><?= e($category['name']) ?></span>
    </div>

    <!-- Title + Sort -->
    <div class="category-header">
        <div>
            <h1 class="category-title"><?= e($category['name']) ?></h1>
        </div>
        <form action="<?= e($categoryLink) ?>" method="GET" class="sort-form">
            <label class="sort-label">Filters</label>
            <select name="sort" onchange="this.form.submit()" class="sort-select">
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Popularity</option>
                <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Customer Rating</option>
            </select>
        </form>
    </div>

    <!-- Product Grid -->
    <section class="category-products">
        <?php if (empty($products)): ?>
        <div class="no-results">
            <p>No products in this category yet.</p>
            <a href="<?= e($homeLink) ?>" class="text-link">Browse home</a>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/../templates/components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
