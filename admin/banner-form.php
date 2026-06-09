<?php
/**
 * Admin Banner Create/Edit Form with file upload
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

<form method="POST" action="/admin/banners" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="banner_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="banner_id" value="<?= e($banner['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Title (optional)</label>
            <input type="text" name="title" value="<?= e($banner['title'] ?? '') ?>" class="form-input">
        </div>
        
        <!-- Image Upload -->
        <div class="form-group full">
            <label>Image</label>
            <div class="upload-zone">
                <div class="upload-area" onclick="document.getElementById('image_file').click()" id="bannerUploadArea">
                    <?php if ($isEdit && !empty($banner['image_url'])): ?>
                    <img src="<?= e($banner['image_url']) ?>" class="upload-preview" alt="" style="max-height:140px;">
                    <?php else: ?>
                    <div class="upload-placeholder">
                        <span class="upload-icon">⬆️</span>
                        <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> or drag and drop an image (PNG, JPG, SVG, WebP)</p>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" name="image_file" id="image_file" accept="image/*" class="file-input-hidden">
                <div class="upload-url-row">
                    <button type="button" class="btn-sm" onclick="document.getElementById('image_file').click()">⬆️ Choose file</button>
                    <input type="text" name="image_url" value="<?= e($banner['image_url'] ?? '') ?>" class="form-input" placeholder="...or paste image URL">
                </div>
            </div>
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
            <label>Order</label>
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

<script>
var area = document.getElementById('bannerUploadArea');
var input = document.getElementById('image_file');
area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
area.addEventListener('drop', function(e) {
    e.preventDefault(); this.classList.remove('drag-over');
    input.files = e.dataTransfer.files;
    var reader = new FileReader();
    reader.onload = function(ev) { area.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview" style="max-height:140px;" alt="">'; };
    reader.readAsDataURL(e.dataTransfer.files[0]);
});
input.addEventListener('change', function() {
    if (this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) { area.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview" style="max-height:140px;" alt="">'; };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
