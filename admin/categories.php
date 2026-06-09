<?php
/**
 * Admin Categories Management
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cat_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $payload = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['name'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? '') ?: null,
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
}

$categories = get_admin_categories();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>Categories</h2>
            <p class="text-muted">Organize products into browsable categories.</p>
        </div>
        <a href="/admin/categories/new" class="btn-primary">+ New category</a>
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
                <td><?= $c['image_url'] ? '<img src="' . e($c['image_url']) . '" class="table-thumb" alt="">' : '—' ?></td>
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
