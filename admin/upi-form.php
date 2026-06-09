<?php
/**
 * Admin UPI App Create/Edit
 */
$appId = get_param('id');
$app = null;
$isEdit = false;
if ($appId) {
    $data = supabase_query('upi_methods', ['id' => 'eq.' . $appId, 'select' => '*']);
    $app = $data[0] ?? null;
    $isEdit = !!$app;
}

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit UPI App' : 'New UPI App' ?></h2>
        <a href="/admin/upi" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/upi" class="admin-form">
    <input type="hidden" name="upi_action" value="<?= $isEdit ? 'update_app' : 'create_app' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Display Name *</label>
            <input type="text" name="app_name" required value="<?= e($app['name'] ?? '') ?>" class="form-input" placeholder="Google Pay">
        </div>
        <div class="form-group full">
            <label>Logo URL</label>
            <input type="url" name="app_logo_url" value="<?= e($app['logo_url'] ?? '') ?>" class="form-input" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="app_sort_order" value="<?= $app['sort_order'] ?? 0 ?>" class="form-input">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="app_is_active" <?= ($app['is_active'] ?? true) ? 'checked' : '' ?>>
                Active
            </label>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/upi" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    </div>
</form>

<?php require __DIR__ . '/layout-end.php'; ?>
