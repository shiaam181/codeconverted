<?php
/**
 * Admin Payment Offer Create/Edit with logo upload
 */
$offerId = get_param('id');
$offer = null;
$isEdit = false;
if ($offerId) {
    $data = supabase_query('payment_offers', ['id' => 'eq.' . $offerId, 'select' => '*']);
    $offer = $data[0] ?? null;
    $isEdit = !!$offer;
}
$brands = ['all' => 'All Offers', 'cashback_banner' => '💚 Cashback Banner (top green box)', 'sbi' => 'ShopMart SBI Credit Card', 'axis' => 'ShopMart Axis Bank', 'upi' => 'UPI'];

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit Offer' : 'New Offer' ?></h2>
        <a href="/admin/payment-offers" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/payment-offers" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="offer_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="offer_id" value="<?= e($offer['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <!-- Logo Upload -->
        <div class="form-group full">
            <label>Logo / icon</label>
            <div class="upload-zone">
                <div class="upload-area" onclick="document.getElementById('logo_file').click()" id="logoUploadArea">
                    <?php if ($isEdit && !empty($offer['logo_url'])): ?>
                    <img src="<?= e($offer['logo_url']) ?>" class="upload-preview-sm" alt="">
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
                    <input type="text" name="logo_url" value="<?= e($offer['logo_url'] ?? '') ?>" class="form-input" placeholder="...or paste image URL">
                </div>
            </div>
        </div>

        <!-- Second Logo (for cashback banner) -->
        <div class="form-group full" id="logo2Group">
            <label>Second Logo / icon (for cashback banner - shows 2 icons)</label>
            <div class="upload-zone">
                <div class="upload-area" onclick="document.getElementById('logo_file_2').click()" id="logoUploadArea2">
                    <?php if ($isEdit && !empty($offer['logo_url_2'])): ?>
                    <img src="<?= e($offer['logo_url_2']) ?>" class="upload-preview-sm" alt="">
                    <?php else: ?>
                    <div class="upload-placeholder">
                        <span class="upload-icon">⬆️</span>
                        <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> second icon</p>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" name="logo_file_2" id="logo_file_2" accept="image/*" class="file-input-hidden">
                <div class="upload-url-row">
                    <button type="button" class="btn-sm" onclick="document.getElementById('logo_file_2').click()">⬆️ Choose file</button>
                    <input type="text" name="logo_url_2" value="<?= e($offer['logo_url_2'] ?? '') ?>" class="form-input" placeholder="...or paste second image URL">
                </div>
            </div>
        </div>

        <div class="form-group full">
            <label>Title *</label>
            <input type="text" name="title" required value="<?= e($offer['title'] ?? '') ?>" class="form-input" placeholder="Get ₹50 cashback">
        </div>
        <div class="form-group full">
            <label>Description</label>
            <textarea name="description" rows="3" class="form-textarea"><?= e($offer['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Coupon code (optional)</label>
            <input type="text" name="code" value="<?= e($offer['code'] ?? '') ?>" class="form-input" placeholder="FLAT50">
        </div>
        <div class="form-group">
            <label>Tab / brand</label>
            <select name="brand" class="form-select">
                <?php foreach ($brands as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($offer['brand'] ?? 'all') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= $offer['sort_order'] ?? 0 ?>" class="form-input">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($offer['is_active'] ?? true) ? 'checked' : '' ?>>
                Active
            </label>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/payment-offers" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    </div>
</form>

<script>
var area = document.getElementById('logoUploadArea');
var input = document.getElementById('logo_file');
area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
area.addEventListener('drop', function(e) {
    e.preventDefault(); this.classList.remove('drag-over');
    input.files = e.dataTransfer.files;
    var reader = new FileReader();
    reader.onload = function(ev) { area.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview-sm" alt="">'; };
    reader.readAsDataURL(e.dataTransfer.files[0]);
});
input.addEventListener('change', function() {
    if (this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) { area.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview-sm" alt="">'; };
        reader.readAsDataURL(this.files[0]);
    }
});

// Second logo
var area2 = document.getElementById('logoUploadArea2');
var input2 = document.getElementById('logo_file_2');
area2.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
area2.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
area2.addEventListener('drop', function(e) {
    e.preventDefault(); this.classList.remove('drag-over');
    input2.files = e.dataTransfer.files;
    var reader = new FileReader();
    reader.onload = function(ev) { area2.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview-sm" alt="">'; };
    reader.readAsDataURL(e.dataTransfer.files[0]);
});
input2.addEventListener('change', function() {
    if (this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) { area2.innerHTML = '<img src="' + ev.target.result + '" class="upload-preview-sm" alt="">'; };
        reader.readAsDataURL(this.files[0]);
    }
});

// Show/hide second logo based on brand
var brandSelect = document.querySelector('select[name="brand"]');
var logo2Group = document.getElementById('logo2Group');
function toggleLogo2() {
    logo2Group.style.display = brandSelect.value === 'cashback_banner' ? '' : 'none';
}
brandSelect.addEventListener('change', toggleLogo2);
toggleLogo2();
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
