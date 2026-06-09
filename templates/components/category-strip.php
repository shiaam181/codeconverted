<?php
/**
 * Category Strip Component
 */
$categories = get_categories();
$tenant = $_SESSION['current_tenant'] ?? null;
$activeCategory = $activeCategory ?? null;

if (empty($categories)) return;
?>
<nav class="category-strip">
    <div class="category-scroll">
        <?php foreach ($categories as $index => $cat): 
            $isActive = $activeCategory ? ($cat['id'] === $activeCategory) : ($index === 0);
            $link = $tenant ? "/t/{$tenant['slug']}/category/{$cat['slug']}" : "/category/{$cat['slug']}";
        ?>
        <a href="<?= e($link) ?>" class="category-item <?= $isActive ? 'active' : '' ?>">
            <div class="category-icon <?= $isActive ? 'active' : '' ?>">
                <?php if (!empty($cat['image_url'])): ?>
                <img src="<?= e($cat['image_url']) ?>" alt="<?= e($cat['name']) ?>" loading="lazy">
                <?php else: ?>
                <span class="category-letter"><?= e(mb_substr($cat['name'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <span class="category-name <?= $isActive ? 'active' : '' ?>"><?= e($cat['name']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>
