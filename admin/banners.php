<?php
/**
 * Admin Banners Management
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['banner_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $imageUrl = trim($_POST['image_url'] ?? '');
        
        // Handle file upload for banner image - overrides URL if present
        if (!empty($_FILES['banner_image_file']['tmp_name']) && $_FILES['banner_image_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedUrl = upload_to_supabase_storage($_FILES['banner_image_file'], 'banners');
            if ($uploadedUrl) {
                $imageUrl = $uploadedUrl;
            }
        }
        
        $payload = [
            'title' => trim($_POST['title'] ?? '') ?: null,
            'image_url' => $imageUrl,
            'link_url' => trim($_POST['link_url'] ?? '') ?: null,
            'position' => $_POST['position'] ?? 'hero',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ];
        
        if (!$payload['image_url']) {
            flash('error', 'Image URL or file upload is required');
            redirect('/admin/banners');
        }
        
        if ($action === 'update' && !empty($_POST['banner_id'])) {
            supabase_query('banners', ['id' => 'eq.' . $_POST['banner_id']], 'PATCH', $payload);
        } else {
            supabase_query('banners', [], 'POST', $payload);
        }
        flash('success', 'Banner saved');
        redirect('/admin/banners');
    }
    
    if ($action === 'delete' && !empty($_POST['banner_id'])) {
        supabase_query('banners', ['id' => 'eq.' . $_POST['banner_id']], 'DELETE');
        flash('success', 'Banner deleted');
        redirect('/admin/banners');
    }
}

$banners = get_admin_banners();

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
            <h2>Banners</h2>
            <p class="text-muted">Hero, secondary, and promo banners shown on the homepage.</p>
        </div>
        <a href="/admin/banners/new" class="btn-primary">+ New banner</a>
    </div>
</div>

<?php if (empty($banners)): ?>
<div class="empty-state"><p>No banners yet.</p></div>
<?php else: ?>
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Position</th>
                <th>Link</th>
                <th>Order</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($banners as $b): ?>
            <tr>
                <td><img src="<?= e($b['image_url']) ?>" class="table-thumb-wide" alt=""></td>
                <td><?= e($b['title'] ?? '—') ?></td>
                <td><span class="badge"><?= e($b['position']) ?></span></td>
                <td class="text-muted td-truncate"><?= e($b['link_url'] ?? '—') ?></td>
                <td><?= $b['sort_order'] ?></td>
                <td><?= $b['is_active'] ? '✓' : '✗' ?></td>
                <td class="td-actions">
                    <a href="/admin/banners/edit?id=<?= e($b['id']) ?>" class="btn-sm">Edit</a>
                    <form method="POST" action="/admin/banners" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="banner_action" value="delete">
                        <input type="hidden" name="banner_id" value="<?= e($b['id']) ?>">
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
