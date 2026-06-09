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
$products = get_products([
    'tenant_id' => $tenantId,
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
            <?php switch ($section['section_type']):
                case 'category_strip': ?>
                    <?php include __DIR__ . '/../templates/components/category-strip.php'; ?>
                    <?php break; ?>
                <?php case 'banner_carousel': ?>
                    <?php 
                    $config = is_array($section['config']) ? $section['config'] : json_decode($section['config'] ?? '{}', true);
                    $position = $config['position'] ?? 'hero';
                    include __DIR__ . '/../templates/components/banner-carousel.php'; 
                    ?>
                    <?php break; ?>
                <?php default: ?>
                    <!-- Unknown section type: <?= e($section['section_type']) ?> -->
                    <?php break; ?>
            <?php endswitch; ?>
        <?php endforeach; ?>
        
        <!-- Product grid always shown at the bottom -->
        <?php 
        $gridTitle = 'Products';
        include __DIR__ . '/../templates/components/product-grid.php'; 
        ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
