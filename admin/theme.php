<?php
/**
 * Admin Theme & Branding
 * Matches React: site_name, logo, favicon, donation_image, colors, font, border-radius
 * With file upload (drag & drop + choose file + paste URL)
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['theme_action'] ?? '') === 'save') {
    $payload = [
        'site_name' => trim($_POST['site_name'] ?? DEFAULT_SITE_NAME),
        'primary_color' => trim($_POST['primary_color'] ?? DEFAULT_PRIMARY_COLOR),
        'secondary_color' => trim($_POST['secondary_color'] ?? DEFAULT_SECONDARY_COLOR),
        'accent_color' => trim($_POST['accent_color'] ?? DEFAULT_ACCENT_COLOR),
        'font_family' => trim($_POST['font_family'] ?? 'Inter, sans-serif'),
        'border_radius' => trim($_POST['border_radius'] ?? '0.375rem'),
    ];

    // Handle logo upload or URL
    if (!empty($_FILES['logo_file']['tmp_name'])) {
        $url = upload_to_supabase_storage($_FILES['logo_file'], 'branding');
        if ($url) $payload['logo_url'] = $url;
    } elseif (isset($_POST['logo_url'])) {
        $payload['logo_url'] = trim($_POST['logo_url']) ?: null;
    }

    // Handle favicon upload or URL
    if (!empty($_FILES['favicon_file']['tmp_name'])) {
        $url = upload_to_supabase_storage($_FILES['favicon_file'], 'branding');
        if ($url) $payload['favicon_url'] = $url;
    } elseif (isset($_POST['favicon_url'])) {
        $payload['favicon_url'] = trim($_POST['favicon_url']) ?: null;
    }

    // Handle donation image upload or URL
    if (!empty($_FILES['donation_file']['tmp_name'])) {
        $url = upload_to_supabase_storage($_FILES['donation_file'], 'branding');
        if ($url) $payload['donation_image_url'] = $url;
    } elseif (isset($_POST['donation_image_url'])) {
        $payload['donation_image_url'] = trim($_POST['donation_image_url']) ?: null;
    }

    // Use PATCH to update existing row (id=1)
    $result = supabase_query('theme_config', ['id' => 'eq.1'], 'PATCH', $payload);
    
    if (isset($result['error'])) {
        flash('error', 'Failed to save: ' . ($result['message'] ?? 'Unknown error'));
    } else {
        flash('success', 'Theme saved successfully');
    }
    redirect('/admin/theme');
}

$theme = get_theme();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h2>Theme & Branding</h2>
    <p class="text-muted">Customize your store's identity. Changes apply site-wide.</p>
</div>

<form method="POST" action="/admin/theme" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="theme_action" value="save">
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Site name</label>
            <input type="text" name="site_name" value="<?= e($theme['site_name'] ?? '') ?>" class="form-input" placeholder="ShopMart">
        </div>
        
        <!-- Logo -->
        <div class="form-group full">
            <label>Logo</label>
            <div class="upload-zone" id="logoZone">
                <div class="upload-area" onclick="document.getElementById('logo_file').click()">
                    <?php if (!empty($theme['logo_url'])): ?>
                    <img src="<?= e($theme['logo_url']) ?>" class="upload-preview" alt="Logo">
                    <?php else: ?>
                    <div class="upload-placeholder">
                        <span class="upload-icon">⬆️</span>
                        <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> or drag and drop an image (PNG, JPG, SVG, WebP)</p>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" name="logo_file" id="logo_file" accept="image/*" class="file-input-hidden">
                <div class="upload-url-row">
                    <button type="button" class="btn-sm" onclick="document.getElementById('logo_file').click()">⬆️ Choose file</button>
                    <input type="text" name="logo_url" value="<?= e($theme['logo_url'] ?? '') ?>" class="form-input" placeholder="...or paste image URL">
                </div>
            </div>
        </div>

        <!-- Favicon -->
        <div class="form-group full">
            <label>Favicon</label>
            <div class="upload-zone">
                <div class="upload-area" onclick="document.getElementById('favicon_file').click()">
                    <?php if (!empty($theme['favicon_url'])): ?>
                    <img src="<?= e($theme['favicon_url']) ?>" class="upload-preview-sm" alt="Favicon">
                    <?php else: ?>
                    <div class="upload-placeholder">
                        <span class="upload-icon">⬆️</span>
                        <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> or drag and drop an image (PNG, JPG, SVG, WebP)</p>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" name="favicon_file" id="favicon_file" accept="image/*" class="file-input-hidden">
                <div class="upload-url-row">
                    <button type="button" class="btn-sm" onclick="document.getElementById('favicon_file').click()">⬆️ Choose file</button>
                    <input type="text" name="favicon_url" value="<?= e($theme['favicon_url'] ?? '') ?>" class="form-input" placeholder="...or paste image URL">
                </div>
            </div>
        </div>

        <!-- Donation card image -->
        <div class="form-group full">
            <label>Donation card image (checkout)</label>
            <div class="upload-zone">
                <div class="upload-area" onclick="document.getElementById('donation_file').click()">
                    <?php if (!empty($theme['donation_image_url'])): ?>
                    <img src="<?= e($theme['donation_image_url']) ?>" class="upload-preview" alt="Donation">
                    <?php else: ?>
                    <div class="upload-placeholder">
                        <span class="upload-icon">⬆️</span>
                        <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> or drag and drop an image (PNG, JPG, SVG, WebP)</p>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" name="donation_file" id="donation_file" accept="image/*" class="file-input-hidden">
                <div class="upload-url-row">
                    <button type="button" class="btn-sm" onclick="document.getElementById('donation_file').click()">⬆️ Choose file</button>
                    <input type="text" name="donation_image_url" value="<?= e($theme['donation_image_url'] ?? '') ?>" class="form-input" placeholder="...or paste image URL">
                </div>
            </div>
        </div>

        <!-- Colors -->
        <div class="form-group">
            <label>Primary</label>
            <div class="color-input">
                <input type="color" name="primary_color" value="<?= e($theme['primary_color'] ?? DEFAULT_PRIMARY_COLOR) ?>" id="pc">
                <input type="text" value="<?= e($theme['primary_color'] ?? DEFAULT_PRIMARY_COLOR) ?>" class="form-input" id="pct" oninput="document.getElementById('pc').value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label>Secondary</label>
            <div class="color-input">
                <input type="color" name="secondary_color" value="<?= e($theme['secondary_color'] ?? DEFAULT_SECONDARY_COLOR) ?>" id="sc">
                <input type="text" value="<?= e($theme['secondary_color'] ?? DEFAULT_SECONDARY_COLOR) ?>" class="form-input" id="sct" oninput="document.getElementById('sc').value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label>Accent</label>
            <div class="color-input">
                <input type="color" name="accent_color" value="<?= e($theme['accent_color'] ?? DEFAULT_ACCENT_COLOR) ?>" id="ac">
                <input type="text" value="<?= e($theme['accent_color'] ?? DEFAULT_ACCENT_COLOR) ?>" class="form-input" id="act" oninput="document.getElementById('ac').value=this.value">
            </div>
        </div>

        <div class="form-group">
            <label>Font family (CSS)</label>
            <input type="text" name="font_family" value="<?= e($theme['font_family'] ?? 'Inter, sans-serif') ?>" class="form-input">
        </div>
        <div class="form-group">
            <label>Border radius (e.g. 0.375rem)</label>
            <input type="text" name="border_radius" value="<?= e($theme['border_radius'] ?? '0.375rem') ?>" class="form-input">
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-primary">Save</button>
    </div>
</form>

<script>
// Sync color pickers with text inputs
document.getElementById('pc').addEventListener('input', function(){ document.getElementById('pct').value = this.value; });
document.getElementById('sc').addEventListener('input', function(){ document.getElementById('sct').value = this.value; });
document.getElementById('ac').addEventListener('input', function(){ document.getElementById('act').value = this.value; });

// Drag and drop support for all upload zones
document.querySelectorAll('.upload-area').forEach(function(area) {
    area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
    area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
    area.addEventListener('drop', function(e) {
        e.preventDefault(); this.classList.remove('drag-over');
        var input = this.parentElement.querySelector('input[type="file"]');
        if (input && e.dataTransfer.files.length > 0) {
            input.files = e.dataTransfer.files;
            var reader = new FileReader();
            reader.onload = function(ev) {
                area.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview" alt="Preview">';
            };
            reader.readAsDataURL(e.dataTransfer.files[0]);
        }
    });
});

// Show preview on file select
document.querySelectorAll('input[type="file"]').forEach(function(input) {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var area = this.parentElement.querySelector('.upload-area');
            var reader = new FileReader();
            reader.onload = function(ev) {
                area.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview" alt="Preview">';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
