<?php
/**
 * Admin Banner Create/Edit Form
 */

$bannerId = get_param('id');
$banner = null;
$isEdit = false;

if ($bannerId) {
    $data = supabase_query('banners', ['id' => 'eq.' . $bannerId, 'select' => '*']);
    $banner = $data[0] ?? null;
    $isEdit = !!$banner;
}

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit Banner' : 'New Banner' ?></h2>
        <a href="/admin/banners" class="btn-outline">← Back to banners</a>
    </div>
</div>

<form method="POST" action="/admin/banners" class="admin-form">
    <input type="hidden" name="banner_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="banner_id" value="<?= e($banner['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Title (optional)</label>
            <input type="text" name="title" value="<?= e($banner['title'] ?? '') ?>" class="form-input">
        </div>
        
        <div class="form-group full">
            <label>Image URL *</label>
            <input type="url" name="image_url" required value="<?= e($banner['image_url'] ?? '') ?>" class="form-input" placeholder="https://...">
            <?php if ($isEdit && !empty($banner['image_url'])): ?>
            <img src="<?= e($banner['image_url']) ?>" class="preview-banner" alt="">
            <?php endif; ?>
        </div>
        
        <div class="form-group full">
            <label>Link URL</label>
            <input type="text" name="link_url" value="<?= e($banner['link_url'] ?? '') ?>" class="form-input" placeholder="https://... or /products/something">
        </div>
        
        <div class="form-group">
            <label>Position</label>
            <select name="position" class="form-select">
                <?php foreach (['hero', 'secondary', 'promo'] as $pos): ?>
                <option value="<?= $pos ?>" <?= ($banner['position'] ?? 'hero') === $pos ? 'selected' : '' ?>><?= $pos ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= $banner['sort_order'] ?? 0 ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($banner['is_active'] ?? true) ? 'checked' : '' ?>>
                Active
            </label>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/banners" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update Banner' : 'Create Banner' ?></button>
    </div>
</form>

<?php require __DIR__ . '/layout-end.php'; ?>
