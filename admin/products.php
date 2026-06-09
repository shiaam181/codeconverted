<?php
/**
 * Admin Products Management
 * Features: New product, Bulk add, Import product, Import category, Push to all stores, Clear All
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['product_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $payload = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'brand' => trim($_POST['brand'] ?? '') ?: null,
            'price' => (float) ($_POST['price'] ?? 0),
            'mrp' => !empty($_POST['mrp']) ? (float) $_POST['mrp'] : null,
            'discount_percent' => (int) ($_POST['discount_percent'] ?? 0),
            'stock' => (int) ($_POST['stock'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'is_active' => isset($_POST['is_active']),
            'is_featured' => isset($_POST['is_featured']),
        ];
        
        if ($action === 'update' && !empty($_POST['product_id'])) {
            supabase_query('products', ['id' => 'eq.' . $_POST['product_id']], 'PATCH', $payload);
            // Handle image uploads
            if (!empty($_FILES['product_images']['tmp_name'][0])) {
                foreach ($_FILES['product_images']['tmp_name'] as $i => $tmp) {
                    if (!$tmp) continue;
                    $file = ['name' => $_FILES['product_images']['name'][$i], 'tmp_name' => $tmp, 'type' => $_FILES['product_images']['type'][$i]];
                    $imgUrl = upload_to_supabase_storage($file, 'products');
                    if ($imgUrl) {
                        supabase_query('product_images', [], 'POST', ['product_id' => $_POST['product_id'], 'url' => $imgUrl, 'sort_order' => $i]);
                    }
                }
            }
        } else {
            $result = supabase_query('products', [], 'POST', $payload);
            if (!empty($result[0]['id']) && !empty($_FILES['product_images']['tmp_name'][0])) {
                $pid = $result[0]['id'];
                foreach ($_FILES['product_images']['tmp_name'] as $i => $tmp) {
                    if (!$tmp) continue;
                    $file = ['name' => $_FILES['product_images']['name'][$i], 'tmp_name' => $tmp, 'type' => $_FILES['product_images']['type'][$i]];
                    $imgUrl = upload_to_supabase_storage($file, 'products');
                    if ($imgUrl) {
                        supabase_query('product_images', [], 'POST', ['product_id' => $pid, 'url' => $imgUrl, 'sort_order' => $i]);
                    }
                }
            }
        }
        flash('success', 'Product saved');
        redirect('/admin/products');
    }
    
    if ($action === 'delete' && !empty($_POST['product_id'])) {
        supabase_query('product_images', ['product_id' => 'eq.' . $_POST['product_id']], 'DELETE');
        supabase_query('products', ['id' => 'eq.' . $_POST['product_id']], 'DELETE');
        flash('success', 'Product deleted');
        redirect('/admin/products');
    }

    if ($action === 'clear_all') {
        supabase_query('products', ['tenant_id' => 'is.null'], 'DELETE');
        flash('success', 'All products cleared');
        redirect('/admin/products');
    }

    if ($action === 'push_to_stores') {
        // Get all master products
        $masterProducts = supabase_query('products', ['tenant_id' => 'is.null', 'select' => '*', 'limit' => '500']);
        $tenants = get_admin_tenants();
        $pushed = 0;
        if (is_array($masterProducts) && !isset($masterProducts['error'])) {
            foreach ($tenants as $t) {
                if (!$t['is_active']) continue;
                foreach ($masterProducts as $mp) {
                    $clone = $mp;
                    unset($clone['id'], $clone['created_at'], $clone['updated_at']);
                    $clone['tenant_id'] = $t['id'];
                    $clone['slug'] = $mp['slug'] . '-' . substr($t['id'], 0, 6);
                    supabase_query('products', [], 'POST', $clone);
                    $pushed++;
                }
            }
        }
        flash('success', "Pushed {$pushed} products to all active stores");
        redirect('/admin/products');
    }

    if ($action === 'import_product') {
        $url = trim($_POST['import_url'] ?? '');
        if ($url) {
            flash('success', 'Product import from URL is not yet implemented in PHP version. Add product manually.');
        } else {
            flash('error', 'Please provide a product URL');
        }
        redirect('/admin/products');
    }

    if ($action === 'import_category') {
        $url = trim($_POST['import_cat_url'] ?? '');
        if ($url) {
            flash('success', 'Category import from URL is not yet implemented in PHP version. Add products manually.');
        } else {
            flash('error', 'Please provide a category URL');
        }
        redirect('/admin/products');
    }

    if ($action === 'bulk_add') {
        $csv = trim($_POST['csv_data'] ?? '');
        $lines = array_filter(array_map('trim', explode("\n", $csv)));
        if (!empty($lines[0]) && stripos($lines[0], 'title') !== false) {
            array_shift($lines); // Remove header
        }
        $added = 0;
        $categories = get_categories();
        $catMap = [];
        foreach ($categories as $c) $catMap[$c['slug']] = $c['id'];
        
        foreach ($lines as $line) {
            $cells = array_map('trim', str_getcsv($line));
            if (empty($cells[0]) || empty($cells[1])) continue;
            $title = $cells[0];
            $price = (float) ($cells[1] ?? 0);
            $mrp = (float) ($cells[2] ?? 0);
            $stock = (int) ($cells[3] ?? 0);
            $brand = $cells[4] ?? null;
            $catSlug = $cells[5] ?? '';
            $imageUrl = $cells[6] ?? '';
            
            $productPayload = [
                'title' => $title,
                'slug' => slugify($title) . '-' . substr(md5(uniqid()), 0, 4),
                'price' => $price,
                'mrp' => $mrp ?: null,
                'stock' => $stock,
                'brand' => $brand ?: null,
                'category_id' => $catMap[$catSlug] ?? null,
                'discount_percent' => ($mrp > $price) ? (int) round((($mrp - $price) / $mrp) * 100) : 0,
                'is_active' => true,
            ];
            $res = supabase_query('products', [], 'POST', $productPayload);
            if (!empty($res[0]['id']) && $imageUrl) {
                supabase_query('product_images', [], 'POST', ['product_id' => $res[0]['id'], 'url' => $imageUrl, 'sort_order' => 0]);
            }
            $added++;
        }
        flash('success', "{$added} products added");
        redirect('/admin/products');
    }
}

$search = get_param('search', '');
$products = get_admin_products($search);
$categories = get_categories();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <div class="section-header">
        <div>
            <h1 class="text-2xl font-bold">Products</h1>
            <p class="text-muted">Manage your catalog.</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn-outline" onclick="document.getElementById('bulkModal').style.display='flex'">📋 Bulk add</button>
            <button type="button" class="btn-outline" onclick="document.getElementById('importModal').style.display='flex'">🔗 Import product</button>
            <button type="button" class="btn-outline" onclick="document.getElementById('importCatModal').style.display='flex'">🔗 Import category</button>
            <form method="POST" action="/admin/products" style="display:inline" onsubmit="return confirm('Push all master products to every active store?')">
                <input type="hidden" name="product_action" value="push_to_stores">
                <button type="submit" class="btn-secondary">📤 Push to all stores</button>
            </form>
            <form method="POST" action="/admin/products" style="display:inline" onsubmit="return confirm('DELETE ALL PRODUCTS? This cannot be undone!')">
                <input type="hidden" name="product_action" value="clear_all">
                <button type="submit" class="btn-danger">🗑️ Clear All Products</button>
            </form>
            <a href="/admin/products/new" class="btn-primary">+ New product</a>
        </div>
    </div>
</div>

<!-- Search -->
<form action="/admin/products" method="GET" class="search-form-inline">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search products..." class="form-input">
    <button type="submit" class="btn-outline">Search</button>
</form>

<!-- Products Grid -->
<?php if (empty($products)): ?>
<div class="empty-state">
    <p>No products yet.</p>
    <a href="/admin/products/new" class="btn-primary">Create your first product</a>
</div>
<?php else: ?>
<div class="product-cards-grid">
    <?php foreach ($products as $p): 
        $imgs = $p['product_images'] ?? [];
        usort($imgs, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
        $cover = $imgs[0]['url'] ?? null;
        $discount = ($p['mrp'] && $p['mrp'] > $p['price']) ? (int) round((($p['mrp'] - $p['price']) / $p['mrp']) * 100) : ($p['discount_percent'] ?? 0);
    ?>
    <div class="product-admin-card">
        <div class="product-admin-img">
            <?php if ($cover): ?>
            <img src="<?= e(img_url($cover, ['w' => 200, 'h' => 200])) ?>" alt="">
            <?php else: ?>
            <span class="no-img-text">No image</span>
            <?php endif; ?>
            <?php if ($p['is_featured'] ?? false): ?>
            <span class="badge-featured">Featured</span>
            <?php endif; ?>
            <?php if (!$p['is_active']): ?>
            <span class="badge-inactive">Inactive</span>
            <?php endif; ?>
        </div>
        <div class="product-admin-overlay">
            <a href="/admin/products/edit?id=<?= e($p['id']) ?>" class="btn-sm btn-edit">✏️ Edit</a>
            <form method="POST" action="/admin/products" style="display:inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="product_action" value="delete">
                <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
                <button type="submit" class="btn-sm btn-danger">🗑️</button>
            </form>
        </div>
        <div class="product-admin-info">
            <p class="product-admin-title"><?= e($p['title']) ?></p>
            <p class="product-admin-meta"><?= e($p['brand'] ?? '—') ?></p>
            <div class="product-admin-price">
                <span class="price-main">₹<?= number_format($p['price'], 0, '.', ',') ?></span>
                <?php if ($p['mrp'] && $p['mrp'] > $p['price']): ?>
                <span class="price-mrp">₹<?= number_format($p['mrp'], 0, '.', ',') ?></span>
                <span class="price-off"><?= $discount ?>% off</span>
                <?php endif; ?>
            </div>
            <p class="product-admin-stock">Stock: <?= $p['stock'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Bulk Add Modal -->
<div class="modal-overlay" id="bulkModal" style="display:none">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Bulk add products</h3>
            <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="modal-close">✕</button>
        </div>
        <form method="POST" action="/admin/products">
            <input type="hidden" name="product_action" value="bulk_add">
            <div class="form-group">
                <label>CSV rows</label>
                <textarea name="csv_data" rows="10" class="form-textarea" placeholder="Title,Price,MRP,Stock,Brand,CategorySlug,ImageURL
Sample Product,999,1299,10,Brand,electronics,https://example.com/img.jpg">Title,Price,MRP,Stock,Brand,CategorySlug,ImageURL
Sample Product,999,1299,10,Brand,electronics,https://example.com/image.jpg</textarea>
                <p class="form-hint">Columns: Title, Price, MRP, Stock, Brand, CategorySlug, ImageURL</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary">Add products</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Product Modal -->
<div class="modal-overlay" id="importModal" style="display:none">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import product</h3>
            <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="modal-close">✕</button>
        </div>
        <form method="POST" action="/admin/products">
            <input type="hidden" name="product_action" value="import_product">
            <div class="form-group">
                <label>Product URL (Flipkart/Amazon link)</label>
                <input type="url" name="import_url" class="form-input" placeholder="https://www.flipkart.com/...">
                <p class="form-hint">Paste a Flipkart product link to import title, images, price, and description.</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Category Modal -->
<div class="modal-overlay" id="importCatModal" style="display:none">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import category</h3>
            <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="modal-close">✕</button>
        </div>
        <form method="POST" action="/admin/products">
            <input type="hidden" name="product_action" value="import_category">
            <div class="form-group">
                <label>Category URL (Flipkart category link)</label>
                <input type="url" name="import_cat_url" class="form-input" placeholder="https://www.flipkart.com/...">
                <p class="form-hint">Paste a Flipkart category link to import multiple products at once.</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/layout-end.php'; ?>
