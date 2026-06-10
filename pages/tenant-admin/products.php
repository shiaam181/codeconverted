<?php
/**
 * Tenant Admin Products Page
 * Features: List, Add, Edit, Delete products, Flipkart import, Bulk CSV import, Clear all
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantSlug = $tenant['slug'] ?? '';
$tenantId = $tenant['id'] ?? '';
$tenantName = $tenant['name'] ?? 'Store';

// Auth guard
if (empty($_SESSION['tenant_admin_token']) || ($_SESSION['tenant_admin_slug'] ?? '') !== $tenantSlug) {
    redirect("/t/{$tenantSlug}/admin/login");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['product_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        
        if (!$title || $price <= 0) {
            flash('error', 'Title and valid price are required');
            redirect("/t/{$tenantSlug}/admin/products");
        }
        
        $mrp = !empty($_POST['mrp']) ? (float) $_POST['mrp'] : null;
        $discountPercent = ($mrp && $mrp > $price) ? (int) round((($mrp - $price) / $mrp) * 100) : 0;
        $productSlug = slugify($title) . '-' . substr(base_convert(mt_rand(), 10, 36), 0, 5);
        
        $payload = [
            'title' => $title,
            'slug' => $productSlug,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'brand' => trim($_POST['brand'] ?? '') ?: null,
            'price' => $price,
            'mrp' => $mrp,
            'discount_percent' => $discountPercent,
            'stock' => (int) ($_POST['stock'] ?? 10),
            'is_active' => isset($_POST['is_active']) ? true : true,
            'is_featured' => isset($_POST['is_featured']) ? true : false,
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'tenant_id' => $tenantId,
        ];
        
        if ($action === 'update' && !empty($_POST['product_id'])) {
            unset($payload['slug']); // Don't change slug on edit
            supabase_query('products', ['id' => 'eq.' . $_POST['product_id']], 'PATCH', $payload);
            
            // Update images if provided
            $imageUrls = array_filter(array_map('trim', explode("\n", $_POST['image_urls'] ?? '')));
            if (!empty($imageUrls)) {
                supabase_query('product_images', ['product_id' => 'eq.' . $_POST['product_id']], 'DELETE');
                foreach ($imageUrls as $i => $url) {
                    if ($url) {
                        supabase_query('product_images', [], 'POST', [
                            'product_id' => $_POST['product_id'],
                            'url' => $url,
                            'sort_order' => $i,
                        ]);
                    }
                }
            }
            flash('success', 'Product updated');
        } else {
            $result = supabase_query('products', [], 'POST', $payload);
            if (!empty($result) && !isset($result['error'])) {
                $newId = is_array($result[0] ?? null) ? $result[0]['id'] : ($result['id'] ?? null);
                // Add images
                $imageUrls = array_filter(array_map('trim', explode("\n", $_POST['image_urls'] ?? '')));
                if ($newId && !empty($imageUrls)) {
                    foreach ($imageUrls as $i => $url) {
                        if ($url) {
                            supabase_query('product_images', [], 'POST', [
                                'product_id' => $newId,
                                'url' => $url,
                                'sort_order' => $i,
                            ]);
                        }
                    }
                }
                flash('success', 'Product added');
            } else {
                flash('error', 'Failed to add product');
            }
        }
        redirect("/t/{$tenantSlug}/admin/products");
    }
    
    if ($action === 'delete' && !empty($_POST['product_id'])) {
        supabase_query('product_images', ['product_id' => 'eq.' . $_POST['product_id']], 'DELETE');
        supabase_query('products', ['id' => 'eq.' . $_POST['product_id']], 'DELETE');
        flash('success', 'Product deleted');
        redirect("/t/{$tenantSlug}/admin/products");
    }
    
    if ($action === 'clear_all') {
        // Delete all products for this tenant
        $tenantProducts = supabase_query('products', ['tenant_id' => 'eq.' . $tenantId, 'select' => 'id']);
        if (is_array($tenantProducts) && !isset($tenantProducts['error'])) {
            foreach ($tenantProducts as $p) {
                supabase_query('product_images', ['product_id' => 'eq.' . $p['id']], 'DELETE');
            }
        }
        supabase_query('products', ['tenant_id' => 'eq.' . $tenantId], 'DELETE');
        flash('success', 'All products cleared');
        redirect("/t/{$tenantSlug}/admin/products");
    }
    
    if ($action === 'flipkart_import') {
        $link = trim($_POST['flipkart_link'] ?? '');
        if (!$link || !preg_match('/flipkart\.com|fkrt\.|dl\.flipkart\.com/i', $link)) {
            flash('error', 'Provide a valid Flipkart product link');
            redirect("/t/{$tenantSlug}/admin/products");
        }
        
        // Use the flipkart scraper
        $imported = flipkart_import_product($link, $tenantId);
        if ($imported) {
            flash('success', "Imported: {$imported['title']}");
        } else {
            flash('error', 'Failed to import product from Flipkart. Try again or use a different link.');
        }
        redirect("/t/{$tenantSlug}/admin/products");
    }
    
    if ($action === 'flipkart_category_import') {
        $link = trim($_POST['flipkart_cat_link'] ?? '');
        $limit = (int) ($_POST['import_limit'] ?? 12);
        if (!$link || !preg_match('/flipkart\.com/i', $link)) {
            flash('error', 'Provide a valid Flipkart category/search link');
            redirect("/t/{$tenantSlug}/admin/products");
        }
        
        $imported = flipkart_import_category($link, $tenantId, $limit);
        if ($imported > 0) {
            flash('success', "Imported {$imported} products from Flipkart");
        } else {
            flash('error', 'No products could be imported. Try a different link.');
        }
        redirect("/t/{$tenantSlug}/admin/products");
    }
    
    if ($action === 'bulk_csv') {
        $csv = trim($_POST['csv_data'] ?? '');
        if (!$csv) {
            flash('error', 'Paste CSV data');
            redirect("/t/{$tenantSlug}/admin/products");
        }
        
        $lines = array_filter(array_map('trim', explode("\n", $csv)));
        // Skip header if it starts with "Title"
        if (!empty($lines) && stripos($lines[0], 'title') === 0) {
            array_shift($lines);
        }
        
        $count = 0;
        $categories = supabase_query('categories', ['select' => 'id,slug']);
        $categoryMap = [];
        if (is_array($categories) && !isset($categories['error'])) {
            foreach ($categories as $c) $categoryMap[$c['slug']] = $c['id'];
        }
        
        foreach ($lines as $line) {
            $cells = array_map('trim', str_getcsv($line));
            if (empty($cells[0]) || !is_numeric($cells[1] ?? '')) continue;
            
            $title = $cells[0];
            $price = (float) $cells[1];
            $mrp = !empty($cells[2]) ? (float) $cells[2] : null;
            $stock = (int) ($cells[3] ?? 10);
            $brand = $cells[4] ?? null;
            $catSlug = $cells[5] ?? '';
            $imageUrl = $cells[6] ?? '';
            
            $discountPercent = ($mrp && $mrp > $price) ? (int) round((($mrp - $price) / $mrp) * 100) : 0;
            
            $result = supabase_query('products', [], 'POST', [
                'tenant_id' => $tenantId,
                'title' => $title,
                'slug' => slugify($title) . '-' . substr(base_convert(mt_rand(), 10, 36), 0, 5),
                'price' => $price,
                'mrp' => $mrp,
                'discount_percent' => $discountPercent,
                'stock' => $stock,
                'brand' => $brand ?: null,
                'category_id' => !empty($catSlug) ? ($categoryMap[$catSlug] ?? null) : null,
                'is_active' => true,
            ]);
            
            if (!empty($result) && !isset($result['error'])) {
                $newId = is_array($result[0] ?? null) ? $result[0]['id'] : ($result['id'] ?? null);
                if ($newId && $imageUrl) {
                    supabase_query('product_images', [], 'POST', [
                        'product_id' => $newId,
                        'url' => $imageUrl,
                        'sort_order' => 0,
                    ]);
                }
                $count++;
            }
        }
        
        flash('success', "{$count} products added via CSV");
        redirect("/t/{$tenantSlug}/admin/products");
    }
    
    // Handle logout from this page too
    if (($_POST['tenant_action'] ?? '') === 'logout') {
        unset($_SESSION['tenant_admin_token'], $_SESSION['tenant_admin_user_id'], $_SESSION['tenant_admin_slug']);
        redirect("/t/{$tenantSlug}/admin/login");
    }
}

// Get products
$search = trim($_GET['q'] ?? '');
$products = get_tenant_products($tenantId, $search);
$categories = supabase_query('categories', ['select' => 'id,name', 'order' => 'name.asc']);
if (!is_array($categories) || isset($categories['error'])) $categories = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Products — <?= e($tenantName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f8fafc;min-height:100vh}
.ta-header{position:sticky;top:0;z-index:30;height:56px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #e5e7eb;background:#fff;padding:0 16px}
.ta-header h1{font-size:15px;font-weight:600;flex:1}
.ta-nav{display:flex;gap:8px;overflow-x:auto;padding:12px 16px;background:#fff;border-bottom:1px solid #f0f0f0}
.ta-nav a{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;color:#333;white-space:nowrap;transition:background .15s}
.ta-nav a.active{background:#2874f0;color:#fff}
.ta-nav a:hover:not(.active){background:#f0f0f0}
.ta-main{padding:16px}
.section-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:1rem}
.section-header h2{font-size:1.25rem;font-weight:700}
.section-header .sub{font-size:12px;color:#666}
.btn-row{display:flex;gap:8px;flex-wrap:wrap}
.btn{padding:8px 14px;border-radius:6px;border:1px solid #d0d0d0;background:#fff;font-size:12px;font-weight:500;cursor:pointer;text-decoration:none;color:#333;display:inline-flex;align-items:center;gap:4px}
.btn:hover{background:#f0f0f0}
.btn-primary{background:#2874f0;color:#fff;border-color:#2874f0}
.btn-primary:hover{background:#1a5dc8}
.btn-danger{background:#ef4444;color:#fff;border-color:#ef4444}
.btn-danger:hover{background:#dc2626}
.search-bar{margin-bottom:1rem}
.search-bar input{width:100%;max-width:300px;padding:8px 14px;border:1px solid #d0d0d0;border-radius:8px;font-size:13px;outline:none}
.search-bar input:focus{border-color:#2874f0}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.product-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;transition:box-shadow .15s}
.product-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
.product-card .img-wrap{aspect-ratio:1;background:#f9f9f9;display:grid;place-items:center;position:relative}
.product-card .img-wrap img{max-width:100%;max-height:100%;object-fit:contain}
.product-card .info{padding:10px}
.product-card .info .title{font-size:12px;font-weight:500;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2rem}
.product-card .info .brand{font-size:10px;color:#666;margin-top:2px}
.product-card .info .price-row{display:flex;align-items:baseline;gap:4px;margin-top:4px}
.product-card .info .price{font-size:14px;font-weight:700}
.product-card .info .mrp{font-size:10px;color:#666;text-decoration:line-through}
.product-card .actions{display:flex;gap:4px;padding:0 10px 10px}
.product-card .actions .btn{padding:4px 8px;font-size:10px}
.badge-inactive{position:absolute;top:4px;right:4px;background:#ef4444;color:#fff;font-size:9px;padding:2px 6px;border-radius:3px}
.badge-default{position:absolute;top:4px;left:4px;background:#f59e0b;color:#fff;font-size:9px;padding:2px 6px;border-radius:3px}
.empty-state{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:2rem;text-align:center;color:#666;font-size:13px}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.show{display:flex}
.modal{background:#fff;border-radius:12px;max-width:600px;width:100%;max-height:85vh;overflow-y:auto;padding:1.5rem}
.modal h3{font-size:1.1rem;font-weight:700;margin-bottom:1rem}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;font-weight:500;margin-bottom:4px;color:#333}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:8px 12px;border:1px solid #d0d0d0;border-radius:6px;font-size:13px;outline:none}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#2874f0}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:1rem}
.alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem}
</style>
</head>
<body>
<header class="ta-header">
    <a href="/t/<?= e($tenantSlug) ?>" style="font-size:12px;color:#666;text-decoration:none">🏪 View store</a>
    <h1>Products</h1>
    <form method="POST" style="display:inline">
        <input type="hidden" name="tenant_action" value="logout">
        <button type="submit" style="font-size:12px;color:#666;border:none;background:none;cursor:pointer">🚪 Sign out</button>
    </form>
</header>

<nav class="ta-nav">
    <a href="/t/<?= e($tenantSlug) ?>/admin">Dashboard</a>
    <a href="/t/<?= e($tenantSlug) ?>/admin/products" class="active">Products</a>
    <a href="/t/<?= e($tenantSlug) ?>/admin/upi">UPI Payment</a>
    <a href="/t/<?= e($tenantSlug) ?>" target="_blank">Preview Store</a>
</nav>

<main class="ta-main">
    <?php if ($msg = get_flash('success')): ?>
    <div class="alert-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
    <div class="alert-error"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="section-header">
        <div>
            <h2>Products</h2>
            <p class="sub">Your store catalog only.</p>
        </div>
        <div class="btn-row">
            <button type="button" class="btn" onclick="document.getElementById('bulkModal').classList.add('show')">📊 Bulk add</button>
            <button type="button" class="btn" onclick="document.getElementById('flipkartModal').classList.add('show')">🔗 Import product</button>
            <button type="button" class="btn" onclick="document.getElementById('flipkartCatModal').classList.add('show')">🔗 Import category</button>
            <?php if (!empty($products)): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Clear ALL products? This cannot be undone.')">
                <input type="hidden" name="product_action" value="clear_all">
                <button type="submit" class="btn btn-danger">🗑️ Clear All</button>
            </form>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')">+ New product</button>
        </div>
    </div>
    
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products...">
        </form>
    </div>
    
    <?php if (empty($products)): ?>
    <div class="empty-state">No products yet — click "New product" or import from Flipkart.</div>
    <?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p): 
            $images = $p['product_images'] ?? [];
            usort($images, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
            $cover = $images[0]['url'] ?? '';
            $isDefault = !empty($p['is_default_product']);
        ?>
        <div class="product-card">
            <div class="img-wrap">
                <?php if ($cover): ?>
                <img src="<?= e($cover) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                <?php else: ?>
                <span style="font-size:10px;color:#999">No image</span>
                <?php endif; ?>
                <?php if (!($p['is_active'] ?? true)): ?>
                <span class="badge-inactive">Inactive</span>
                <?php endif; ?>
                <?php if ($isDefault): ?>
                <span class="badge-default">Default</span>
                <?php endif; ?>
            </div>
            <div class="info">
                <p class="title"><?= e($p['title']) ?></p>
                <p class="brand"><?= e($p['brand'] ?? '—') ?></p>
                <div class="price-row">
                    <span class="price">₹<?= number_format((float)$p['price'], 0, '.', ',') ?></span>
                    <?php if (!empty($p['mrp']) && (float)$p['mrp'] > (float)$p['price']): ?>
                    <span class="mrp">₹<?= number_format((float)$p['mrp'], 0, '.', ',') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn" onclick="editProduct(<?= e(json_encode($p)) ?>)">✏️ Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
                    <input type="hidden" name="product_action" value="delete">
                    <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
                    <button type="submit" class="btn" style="color:#ef4444">🗑️</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- Add/Edit Product Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h3 id="modalTitle">New product</h3>
        <form method="POST" id="productForm">
            <input type="hidden" name="product_action" value="create" id="formAction">
            <input type="hidden" name="product_id" value="" id="formProductId">
            
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" id="fTitle" required>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" id="fBrand">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="fStock" value="10">
                </div>
                <div class="form-group">
                    <label>Price (₹) *</label>
                    <input type="number" name="price" id="fPrice" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>MRP (₹)</label>
                    <input type="number" name="mrp" id="fMrp" step="0.01">
                </div>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" id="fCategory">
                    <option value="">— None —</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="fDescription" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Image URLs (one per line)</label>
                <textarea name="image_urls" id="fImages" rows="3" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg"></textarea>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_featured" id="fFeatured"> Featured product</label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Flipkart Import Modal -->
<div class="modal-overlay" id="flipkartModal">
    <div class="modal">
        <h3>Import from Flipkart</h3>
        <form method="POST">
            <input type="hidden" name="product_action" value="flipkart_import">
            <div class="form-group">
                <label>Flipkart product link</label>
                <input type="url" name="flipkart_link" required placeholder="https://www.flipkart.com/...">
            </div>
            <div class="form-actions">
                <button type="button" class="btn" onclick="document.getElementById('flipkartModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Flipkart Category Import Modal -->
<div class="modal-overlay" id="flipkartCatModal">
    <div class="modal">
        <h3>Import Flipkart category/search</h3>
        <form method="POST">
            <input type="hidden" name="product_action" value="flipkart_category_import">
            <div class="form-group">
                <label>Flipkart category/search link</label>
                <input type="url" name="flipkart_cat_link" required placeholder="https://www.flipkart.com/search?q=...">
            </div>
            <div class="form-group">
                <label>Max products to import</label>
                <input type="number" name="import_limit" value="12" min="1" max="40">
            </div>
            <div class="form-actions">
                <button type="button" class="btn" onclick="document.getElementById('flipkartCatModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk CSV Modal -->
<div class="modal-overlay" id="bulkModal">
    <div class="modal">
        <h3>Bulk add products (CSV)</h3>
        <form method="POST">
            <input type="hidden" name="product_action" value="bulk_csv">
            <div class="form-group">
                <label>CSV rows</label>
                <textarea name="csv_data" rows="10" placeholder="Title,Price,MRP,Stock,Brand,CategorySlug,ImageURL&#10;Sample Product,999,1299,10,Brand,electronics,https://example.com/img.jpg"></textarea>
            </div>
            <p style="font-size:11px;color:#666;margin-bottom:1rem">Columns: Title, Price, MRP, Stock, Brand, CategorySlug, ImageURL</p>
            <div class="form-actions">
                <button type="button" class="btn" onclick="document.getElementById('bulkModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add products</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('modalTitle').textContent = 'Edit product';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formProductId').value = p.id;
    document.getElementById('fTitle').value = p.title || '';
    document.getElementById('fBrand').value = p.brand || '';
    document.getElementById('fStock').value = p.stock || 0;
    document.getElementById('fPrice').value = p.price || 0;
    document.getElementById('fMrp').value = p.mrp || '';
    document.getElementById('fCategory').value = p.category_id || '';
    document.getElementById('fDescription').value = p.description || '';
    document.getElementById('fFeatured').checked = p.is_featured || false;
    // Populate images
    var imgs = (p.product_images || []).sort(function(a,b){return (a.sort_order||0)-(b.sort_order||0)});
    document.getElementById('fImages').value = imgs.map(function(i){return i.url}).join('\n');
    document.getElementById('addModal').classList.add('show');
}

// Reset form when opening for new product
document.querySelector('[onclick*="addModal"]').addEventListener('click', function() {
    document.getElementById('modalTitle').textContent = 'New product';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formProductId').value = '';
    document.getElementById('productForm').reset();
});

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) el.classList.remove('show');
    });
});
</script>
</body>
</html>
