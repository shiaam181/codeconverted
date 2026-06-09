<?php
/**
 * Admin Category Create/Edit Form
 */

$catId = get_param('id');
$category = null;
$isEdit = false;

if ($catId) {
    $data = supabase_query('categories', ['id' => 'eq.' . $catId, 'select' => '*']);
    $category = $data[0] ?? null;
    $isEdit = !!$category;
}

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit Category' : 'New Category' ?></h2>
        <a href="/admin/categories" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/categories" class="admin-form">
    <input type="hidden" name="cat_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="cat_id" value="<?= e($category['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" required value="<?= e($category['name'] ?? '') ?>" class="form-input">
        </div>
        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="<?= e($category['slug'] ?? '') ?>" class="form-input" placeholder="Auto from name">
        </div>
        <div class="form-group full">
            <label>Image URL</label>
            <input type="url" name="image_url" value="<?= e($category['image_url'] ?? '') ?>" class="form-input" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= $category['sort_order'] ?? 0 ?>" class="form-input">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($category['is_active'] ?? true) ? 'checked' : '' ?>>
                Active
            </label>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/categories" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    </div>
</form>

<?php require __DIR__ . '/layout-end.php'; ?>
