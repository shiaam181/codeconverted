<?php
/**
 * Homepage
 * Renders: Header, Category Strip, Banner Carousel, Product Grid, Footer
 */

$pageTitle = 'Online Shopping for Fashion, Electronics & More';
$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;

// Get homepage layout
$layout = get_homepage_layout();

// Get all products for the infinite grid
$hideDefaults = $tenantId && isset($tenant['show_default_products']) && $tenant['show_default_products'] === false;
$products = get_products([
    'tenant_id' => $tenantId,
    'hide_defaults' => $hideDefaults,
    'limit' => 200,
]);

// Include header
require __DIR__ . '/../templates/header.php';
?>

<main class="main-content">
    <?php if (empty($layout)): ?>
        <!-- Default layout -->
        <?php include __DIR__ . '/../templates/components/category-strip.php'; ?>
        <?php 
        $position = 'hero';
        include __DIR__ . '/../templates/components/banner-carousel.php'; 
        ?>
        <?php 
        $gridTitle = 'Products';
        include __DIR__ . '/../templates/components/product-grid.php'; 
        ?>
    <?php else: ?>
        <!-- Dynamic layout from database -->
        <?php foreach ($layout as $section): ?>
            <?php if ($section['section_type'] === 'category_strip'): ?>
                <?php include __DIR__ . '/../templates/components/category-strip.php'; ?>
            <?php elseif ($section['section_type'] === 'banner_carousel'): ?>
                <?php 
                $config = is_array($section['config']) ? $section['config'] : json_decode($section['config'] ?? '{}', true);
                $position = $config['position'] ?? 'hero';
                include __DIR__ . '/../templates/components/banner-carousel.php'; 
                ?>
            <?php else: ?>
                <!-- Unknown section type: <?= e($section['section_type']) ?> -->
            <?php endif; ?>
        <?php endforeach; ?>
        
        <!-- Product grid always shown at the bottom -->
        <?php 
        $gridTitle = 'Products';
        include __DIR__ . '/../templates/components/product-grid.php'; 
        ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
