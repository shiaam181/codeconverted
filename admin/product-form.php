<?php
/**
 * Admin Product Create/Edit Form with image upload
 */

$productId = get_param('id');
$product = null;
$isEdit = false;

if ($productId) {
    $data = supabase_query('products', [
        'id' => 'eq.' . $productId,
        'select' => '*,product_images(id,url,sort_order)',
    ]);
    $product = $data[0] ?? null;
    $isEdit = !!$product;
}

$categories = get_categories();

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit Product' : 'New Product' ?></h2>
        <a href="/admin/products" class="btn-outline">← Back to products</a>
    </div>
</div>

<form method="POST" action="/admin/products" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="product_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Title *</label>
            <input type="text" name="title" required value="<?= e($product['title'] ?? '') ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="<?= e($product['slug'] ?? '') ?>" class="form-input" placeholder="Auto-generated from title">
        </div>
        
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" value="<?= e($product['brand'] ?? '') ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label>Price (₹) *</label>
            <input type="number" name="price" required step="0.01" value="<?= $product['price'] ?? 0 ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label>MRP (₹)</label>
            <input type="number" name="mrp" step="0.01" value="<?= $product['mrp'] ?? '' ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label>Discount %</label>
            <input type="number" name="discount_percent" value="<?= $product['discount_percent'] ?? 0 ?>" class="form-input">
        </div>
        
        <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" value="<?= $product['stock'] ?? 0 ?>" class="form-input">
        </div>
        
        <div class="form-group full">
            <label>Category</label>
            <select name="category_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['id']) ?>" <?= ($product['category_id'] ?? '') === $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group full">
            <label>Description</label>
            <textarea name="description" rows="4" class="form-textarea"><?= e($product['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($product['is_active'] ?? true) ? 'checked' : '' ?>>
                Active (visible on store)
            </label>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_featured" <?= ($product['is_featured'] ?? false) ? 'checked' : '' ?>>
                Featured
            </label>
        </div>
    </div>
    
    <!-- Image Upload Section -->
    <div class="form-group full" style="margin-top: 20px;">
        <label>Product Images</label>
        
        <?php if ($isEdit && !empty($product['product_images'])): 
            $existingImgs = $product['product_images'];
            usort($existingImgs, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
        ?>
        <div class="image-preview-grid" style="margin-bottom: 12px;">
            <?php foreach ($existingImgs as $img): ?>
            <img src="<?= e(img_url($img['url'], ['w' => 120, 'h' => 120])) ?>" class="preview-thumb" alt="">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="upload-zone">
            <div class="upload-area" onclick="document.getElementById('product_images').click()" id="productUploadArea">
                <div class="upload-placeholder">
                    <span class="upload-icon">⬆️</span>
                    <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> or drag and drop an image (PNG, JPG, SVG, WebP)</p>
                </div>
            </div>
            <input type="file" name="product_images[]" id="product_images" multiple accept="image/*" class="file-input-hidden">
            <div class="upload-url-row">
                <button type="button" class="btn-sm" onclick="document.getElementById('product_images').click()">⬆️ Choose file</button>
                <span class="text-muted text-sm">Select multiple images for this product</span>
            </div>
        </div>
        <div id="imagePreviewList" class="image-preview-grid" style="margin-top: 8px;"></div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/products" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update Product' : 'Create Product' ?></button>
    </div>
</form>

<script>
// Drag and drop for product images
var area = document.getElementById('productUploadArea');
var input = document.getElementById('product_images');
var previewList = document.getElementById('imagePreviewList');

area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
area.addEventListener('drop', function(e) {
    e.preventDefault(); this.classList.remove('drag-over');
    input.files = e.dataTransfer.files;
    showPreviews(e.dataTransfer.files);
});
input.addEventListener('change', function() { showPreviews(this.files); });

function showPreviews(files) {
    previewList.innerHTML = '';
    for (var i = 0; i < files.length; i++) {
        var reader = new FileReader();
        reader.onload = function(ev) {
            var img = document.createElement('img');
            img.src = ev.target.result;
            img.className = 'preview-thumb';
            previewList.appendChild(img);
        };
        reader.readAsDataURL(files[i]);
    }
}
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
