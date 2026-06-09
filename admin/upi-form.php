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

// Handle file upload for logo if submitted via this form's action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['app_logo_file']['tmp_name'])) {
    // The actual save is handled in upi.php, but we process the upload here
    // Actually this form posts to /admin/upi, so the upload handling is there
}

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit UPI App' : 'New UPI App' ?></h2>
        <a href="/admin/upi" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/upi" enctype="multipart/form-data" class="admin-form">
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
            <?php if ($isEdit && !empty($app['logo_url'])): ?>
            <img src="<?= e($app['logo_url']) ?>" style="max-height: 48px; margin-top: 0.5rem;" alt="Logo">
            <?php endif; ?>
        </div>
        <div class="form-group full">
            <label>Or Upload Logo Image</label>
            <input type="file" name="app_logo_file" accept="image/*" class="form-input">
            <p class="form-hint">If a file is uploaded, it will be used instead of the URL above.</p>
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
