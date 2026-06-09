<?php
/**
 * Admin Theme & Branding
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['theme_action'] ?? '') === 'save') {
    $payload = [
        'site_name' => trim($_POST['site_name'] ?? DEFAULT_SITE_NAME),
        'logo_url' => trim($_POST['logo_url'] ?? '') ?: null,
        'favicon_url' => trim($_POST['favicon_url'] ?? '') ?: null,
        'donation_image_url' => trim($_POST['donation_image_url'] ?? '') ?: null,
        'primary_color' => trim($_POST['primary_color'] ?? DEFAULT_PRIMARY_COLOR),
        'secondary_color' => trim($_POST['secondary_color'] ?? DEFAULT_SECONDARY_COLOR),
        'accent_color' => trim($_POST['accent_color'] ?? DEFAULT_ACCENT_COLOR),
        'font_family' => trim($_POST['font_family'] ?? 'Inter, sans-serif'),
        'border_radius' => trim($_POST['border_radius'] ?? '0.375rem'),
    ];
    
    supabase_query('theme_config', ['id' => 'eq.1'], 'PATCH', $payload);
    flash('success', 'Theme saved');
    redirect('/admin/theme');
}

$theme = get_theme();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h2>Theme & Branding</h2>
    <p class="text-muted">Customize your store's identity. Changes apply site-wide.</p>
</div>

<form method="POST" action="/admin/theme" class="admin-form">
    <input type="hidden" name="theme_action" value="save">
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Site Name</label>
            <input type="text" name="site_name" value="<?= e($theme['site_name'] ?? '') ?>" class="form-input">
        </div>
        
        <div class="form-group full">
            <label>Logo URL</label>
            <input type="url" name="logo_url" value="<?= e($theme['logo_url'] ?? '') ?>" class="form-input" placeholder="https://...">
            <?php if (!empty($theme['logo_url'])): ?>
            <img src="<?= e($theme['logo_url']) ?>" class="preview-logo" alt="Logo">
            <?php endif; ?>
        </div>
        
        <div class="form-group full">
            <label>Favicon URL</label>
            <input type="url" name="favicon_url" value="<?= e($theme['favicon_url'] ?? '') ?>" class="form-input" placeholder="https://...">
        </div>
        
        <div class="form-group full">
            <label>Donation Card Image (checkout)</label>
            <input type="url" name="donation_image_url" value="<?= e($theme['donation_image_url'] ?? '') ?>" class="form-input" placeholder="https://...">
        </div>
        
        <div class="form-group">
            <label>Primary Color</label>
            <div class="color-input">
                <input type="color" name="primary_color" value="<?= e($theme['primary_color'] ?? DEFAULT_PRIMARY_COLOR) ?>">
                <input type="text" name="primary_color_text" value="<?= e($theme['primary_color'] ?? DEFAULT_PRIMARY_COLOR) ?>" class="form-input" onchange="this.previousElementSibling.value=this.value" readonly>
            </div>
        </div>
        
        <div class="form-group">
            <label>Secondary Color</label>
            <div class="color-input">
                <input type="color" name="secondary_color" value="<?= e($theme['secondary_color'] ?? DEFAULT_SECONDARY_COLOR) ?>">
                <input type="text" value="<?= e($theme['secondary_color'] ?? DEFAULT_SECONDARY_COLOR) ?>" class="form-input" readonly>
            </div>
        </div>
        
        <div class="form-group">
            <label>Accent Color</label>
            <div class="color-input">
                <input type="color" name="accent_color" value="<?= e($theme['accent_color'] ?? DEFAULT_ACCENT_COLOR) ?>">
                <input type="text" value="<?= e($theme['accent_color'] ?? DEFAULT_ACCENT_COLOR) ?>" class="form-input" readonly>
            </div>
        </div>
        
        <div class="form-group">
            <label>Font Family (CSS)</label>
            <input type="text" name="font_family" value="<?= e($theme['font_family'] ?? 'Inter, sans-serif') ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label>Border Radius</label>
            <input type="text" name="border_radius" value="<?= e($theme['border_radius'] ?? '0.375rem') ?>" class="form-input">
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-primary">Save Changes</button>
    </div>
</form>

<?php require __DIR__ . '/layout-end.php'; ?>
