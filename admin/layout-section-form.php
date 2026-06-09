<?php
/**
 * Admin Homepage Layout Section Create/Edit
 */
$sectionId = get_param('id');
$section = null;
$isEdit = false;
if ($sectionId) {
    $data = supabase_query('homepage_layout', ['id' => 'eq.' . $sectionId, 'select' => '*']);
    $section = $data[0] ?? null;
    $isEdit = !!$section;
}
$config = [];
if ($section) {
    $config = is_string($section['config'] ?? '') ? json_decode($section['config'], true) : ($section['config'] ?? []);
}

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit Section' : 'Add Section' ?></h2>
        <a href="/admin/homepage-layout" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/homepage-layout" class="admin-form">
    <input type="hidden" name="layout_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="section_id" value="<?= e($section['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group">
            <label>Section Type</label>
            <select name="section_type" class="form-select" id="sectionType" onchange="toggleConfig()">
                <option value="banner_carousel" <?= ($section['section_type'] ?? '') === 'banner_carousel' ? 'selected' : '' ?>>Banner Carousel</option>
                <option value="category_strip" <?= ($section['section_type'] ?? '') === 'category_strip' ? 'selected' : '' ?>>Category Strip</option>
                <option value="product_grid" <?= ($section['section_type'] ?? '') === 'product_grid' ? 'selected' : '' ?>>Product Grid</option>
            </select>
        </div>
        <div class="form-group">
            <label>Title (optional)</label>
            <input type="text" name="title" value="<?= e($section['title'] ?? '') ?>" class="form-input">
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= $section['sort_order'] ?? 0 ?>" class="form-input">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_enabled" <?= ($section['is_enabled'] ?? true) ? 'checked' : '' ?>>
                Enabled
            </label>
        </div>
        
        <!-- Banner config -->
        <div class="form-group full config-banner">
            <label>Banner Position</label>
            <select name="config_position" class="form-select">
                <option value="hero" <?= ($config['position'] ?? 'hero') === 'hero' ? 'selected' : '' ?>>hero</option>
                <option value="secondary" <?= ($config['position'] ?? '') === 'secondary' ? 'selected' : '' ?>>secondary</option>
                <option value="promo" <?= ($config['position'] ?? '') === 'promo' ? 'selected' : '' ?>>promo</option>
            </select>
        </div>
        
        <!-- Product grid config -->
        <div class="form-group config-grid">
            <label>Source</label>
            <select name="config_kind" class="form-select">
                <option value="featured" <?= ($config['kind'] ?? 'featured') === 'featured' ? 'selected' : '' ?>>Featured</option>
                <option value="all" <?= ($config['kind'] ?? '') === 'all' ? 'selected' : '' ?>>All products</option>
                <option value="category" <?= ($config['kind'] ?? '') === 'category' ? 'selected' : '' ?>>By category</option>
            </select>
        </div>
        <div class="form-group config-grid">
            <label>Limit</label>
            <input type="number" name="config_limit" value="<?= $config['limit'] ?? 8 ?>" class="form-input">
        </div>
        <div class="form-group config-grid full">
            <label>Category Slug (if by category)</label>
            <input type="text" name="config_slug" value="<?= e($config['slug'] ?? '') ?>" class="form-input" placeholder="e.g. electronics">
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/homepage-layout" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    </div>
</form>

<script>
function toggleConfig() {
    const type = document.getElementById('sectionType').value;
    document.querySelectorAll('.config-banner').forEach(el => el.style.display = type === 'banner_carousel' ? '' : 'none');
    document.querySelectorAll('.config-grid').forEach(el => el.style.display = type === 'product_grid' ? '' : 'none');
}
toggleConfig();
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
