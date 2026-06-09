<?php
/**
 * Admin Categories Management
 * Features: New category, Bulk upload (images → categories), Edit, Delete
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cat_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        // Handle image file upload
        $imageUrl = trim($_POST['image_url'] ?? '') ?: null;
        if (!empty($_FILES['image_file']['tmp_name'])) {
            $uploaded = upload_to_supabase_storage($_FILES['image_file'], 'categories');
            if ($uploaded) $imageUrl = $uploaded;
        }

        $payload = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['name'] ?? ''),
            'image_url' => $imageUrl,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ];
        
        if (!$payload['name']) {
            flash('error', 'Name is required');
            redirect('/admin/categories');
        }
        
        if ($action === 'update' && !empty($_POST['cat_id'])) {
            supabase_query('categories', ['id' => 'eq.' . $_POST['cat_id']], 'PATCH', $payload);
        } else {
            supabase_query('categories', [], 'POST', $payload);
        }
        flash('success', 'Category saved');
        redirect('/admin/categories');
    }
    
    if ($action === 'delete' && !empty($_POST['cat_id'])) {
        supabase_query('categories', ['id' => 'eq.' . $_POST['cat_id']], 'DELETE');
        flash('success', 'Category deleted');
        redirect('/admin/categories');
    }

    if ($action === 'bulk_upload') {
        $count = 0;
        if (!empty($_FILES['bulk_images']['tmp_name'])) {
            $maxOrder = 0;
            $existing = get_admin_categories();
            foreach ($existing as $c) $maxOrder = max($maxOrder, $c['sort_order'] ?? 0);
            
            foreach ($_FILES['bulk_images']['tmp_name'] as $i => $tmp) {
                if (!$tmp || $_FILES['bulk_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name' => $_FILES['bulk_images']['name'][$i],
                    'tmp_name' => $tmp,
                    'type' => $_FILES['bulk_images']['type'][$i],
                ];
                $imgUrl = upload_to_supabase_storage($file, 'categories');
                if (!$imgUrl) continue;
                
                $name = pathinfo($_FILES['bulk_images']['name'][$i], PATHINFO_FILENAME);
                $name = str_replace(['-', '_'], ' ', $name);
                $name = ucfirst(trim($name)) ?: 'Category';
                $maxOrder++;
                
                supabase_query('categories', [], 'POST', [
                    'name' => $name,
                    'slug' => slugify($name) . '-' . $maxOrder,
                    'image_url' => $imgUrl,
                    'sort_order' => $maxOrder,
                    'is_active' => true,
                ]);
                $count++;
            }
        }
        flash('success', "Created {$count} categories from uploaded images");
        redirect('/admin/categories');
    }
}

$categories = get_admin_categories();

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
            <h2>Categories</h2>
            <p class="text-muted">Organize your products into browsable categories.</p>
        </div>
        <div class="btn-group">
            <form method="POST" action="/admin/categories" enctype="multipart/form-data" style="display:inline">
                <input type="hidden" name="cat_action" value="bulk_upload">
                <label class="btn-outline" style="cursor:pointer">
                    ⬆️ Bulk upload
                    <input type="file" name="bulk_images[]" multiple accept="image/*,image/svg+xml,.svg" class="file-input-hidden" onchange="this.form.submit()">
                </label>
            </form>
            <a href="/admin/categories/new" class="btn-primary">+ New category</a>
        </div>
    </div>
</div>

<?php if (empty($categories)): ?>
<div class="empty-state"><p>No categories yet.</p></div>
<?php else: ?>
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Order</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><?= !empty($c['image_url']) ? '<img src="' . e($c['image_url']) . '" class="table-thumb" alt="">' : '—' ?></td>
                <td class="td-title"><?= e($c['name']) ?></td>
                <td class="text-muted"><?= e($c['slug']) ?></td>
                <td><?= $c['sort_order'] ?></td>
                <td><?= $c['is_active'] ? '✓' : '✗' ?></td>
                <td class="td-actions">
                    <a href="/admin/categories/edit?id=<?= e($c['id']) ?>" class="btn-sm">Edit</a>
                    <form method="POST" action="/admin/categories" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="cat_action" value="delete">
                        <input type="hidden" name="cat_id" value="<?= e($c['id']) ?>">
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
