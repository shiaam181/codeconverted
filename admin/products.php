<?php
/**
 * Admin Products Management
 */

// Handle actions
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
        } else {
            supabase_query('products', [], 'POST', $payload);
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
}

// Get products list
$search = get_param('search', '');
$products = get_admin_products($search);
$categories = get_categories();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>Products</h2>
            <p class="text-muted">Manage your catalog. <?= count($products) ?> products.</p>
        </div>
        <a href="/admin/products/new" class="btn-primary">+ New product</a>
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
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Brand</th>
                <th>Price</th>
                <th>MRP</th>
                <th>Stock</th>
                <th>Active</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): 
                $imgs = $p['product_images'] ?? [];
                usort($imgs, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
                $cover = $imgs[0]['url'] ?? null;
            ?>
            <tr>
                <td>
                    <?php if ($cover): ?>
                    <img src="<?= e(img_url($cover, ['w' => 80, 'h' => 80])) ?>" class="table-thumb" alt="">
                    <?php else: ?>
                    <span class="no-image-sm">—</span>
                    <?php endif; ?>
                </td>
                <td class="td-title"><?= e($p['title']) ?></td>
                <td><?= e($p['brand'] ?? '—') ?></td>
                <td>₹<?= number_format($p['price'], 0, '.', ',') ?></td>
                <td><?= $p['mrp'] ? '₹' . number_format($p['mrp'], 0, '.', ',') : '—' ?></td>
                <td><?= $p['stock'] ?></td>
                <td><?= $p['is_active'] ? '✓' : '✗' ?></td>
                <td><?= ($p['is_featured'] ?? false) ? '★' : '—' ?></td>
                <td class="td-actions">
                    <a href="/admin/products/edit?id=<?= e($p['id']) ?>" class="btn-sm">Edit</a>
                    <form method="POST" action="/admin/products" style="display:inline" onsubmit="return confirm('Delete this product?')">
                        <input type="hidden" name="product_action" value="delete">
                        <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
                        <button type="submit" class="btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
