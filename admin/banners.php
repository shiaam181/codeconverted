<?php
/**
 * Admin Banners Management
 * Features: New banner, Bulk upload, Edit, Delete
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['banner_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        // Handle image file upload
        $imageUrl = trim($_POST['image_url'] ?? '');
        if (!empty($_FILES['image_file']['tmp_name'])) {
            $uploaded = upload_to_supabase_storage($_FILES['image_file'], 'banners');
            if ($uploaded) $imageUrl = $uploaded;
        }
        
        if (!$imageUrl) {
            flash('error', 'Image is required');
            redirect('/admin/banners');
        }

        $payload = [
            'title' => trim($_POST['title'] ?? '') ?: null,
            'image_url' => $imageUrl,
            'link_url' => trim($_POST['link_url'] ?? '') ?: null,
            'position' => $_POST['position'] ?? 'hero',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ];
        
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

    if ($action === 'bulk_upload') {
        $count = 0;
        if (!empty($_FILES['bulk_banners']['tmp_name'])) {
            $maxOrder = 0;
            $existing = get_admin_banners();
            foreach ($existing as $b) $maxOrder = max($maxOrder, $b['sort_order'] ?? 0);
            
            foreach ($_FILES['bulk_banners']['tmp_name'] as $i => $tmp) {
                if (!$tmp || $_FILES['bulk_banners']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name' => $_FILES['bulk_banners']['name'][$i],
                    'tmp_name' => $tmp,
                    'type' => $_FILES['bulk_banners']['type'][$i],
                ];
                $imgUrl = upload_to_supabase_storage($file, 'banners');
                if (!$imgUrl) continue;
                
                $title = pathinfo($_FILES['bulk_banners']['name'][$i], PATHINFO_FILENAME);
                $title = str_replace(['-', '_'], ' ', $title);
                $maxOrder++;
                
                supabase_query('banners', [], 'POST', [
                    'title' => ucfirst(trim($title)) ?: null,
                    'image_url' => $imgUrl,
                    'position' => 'hero',
                    'sort_order' => $maxOrder,
                    'is_active' => true,
                ]);
                $count++;
            }
        }
        flash('success', "Created {$count} banners from uploaded images");
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
        <div class="btn-group">
            <form method="POST" action="/admin/banners" enctype="multipart/form-data" style="display:inline">
                <input type="hidden" name="banner_action" value="bulk_upload">
                <label class="btn-outline" style="cursor:pointer">
                    ⬆️ Bulk upload
                    <input type="file" name="bulk_banners[]" multiple accept="image/*" class="file-input-hidden" onchange="this.form.submit()">
                </label>
            </form>
            <a href="/admin/banners/new" class="btn-primary">+ New banner</a>
        </div>
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
