<?php
/**
 * Admin Homepage Layout Manager
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['layout_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $config = [];
        if (($_POST['section_type'] ?? '') === 'banner_carousel') {
            $config['position'] = $_POST['config_position'] ?? 'hero';
        }
        if (($_POST['section_type'] ?? '') === 'product_grid') {
            $config['kind'] = $_POST['config_kind'] ?? 'featured';
            $config['limit'] = (int) ($_POST['config_limit'] ?? 8);
            if (!empty($_POST['config_slug'])) $config['slug'] = $_POST['config_slug'];
        }
        
        $payload = [
            'section_type' => $_POST['section_type'] ?? 'product_grid',
            'title' => trim($_POST['title'] ?? '') ?: null,
            'config' => json_encode($config),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_enabled' => isset($_POST['is_enabled']),
        ];
        
        if ($action === 'update' && !empty($_POST['section_id'])) {
            supabase_query('homepage_layout', ['id' => 'eq.' . $_POST['section_id']], 'PATCH', $payload);
        } else {
            supabase_query('homepage_layout', [], 'POST', $payload);
        }
        flash('success', 'Section saved');
        redirect('/admin/homepage-layout');
    }
    
    if ($action === 'toggle' && !empty($_POST['section_id'])) {
        $currentEnabled = $_POST['current_enabled'] === '1';
        supabase_query('homepage_layout', ['id' => 'eq.' . $_POST['section_id']], 'PATCH', [
            'is_enabled' => !$currentEnabled,
        ]);
        flash('success', 'Section toggled');
        redirect('/admin/homepage-layout');
    }
    
    if ($action === 'delete' && !empty($_POST['section_id'])) {
        supabase_query('homepage_layout', ['id' => 'eq.' . $_POST['section_id']], 'DELETE');
        flash('success', 'Section removed');
        redirect('/admin/homepage-layout');
    }
}

$sections = get_homepage_layout_admin();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>Homepage Layout</h2>
            <p class="text-muted">Manage sections shown on the homepage. Toggle to show/hide.</p>
        </div>
        <a href="/admin/homepage-layout/new" class="btn-primary">+ Add Section</a>
    </div>
</div>

<?php if (empty($sections)): ?>
<div class="empty-state"><p>No layout sections yet. The homepage will use defaults.</p></div>
<?php else: ?>
<div class="cards-list">
    <?php foreach ($sections as $s): 
        $config = is_string($s['config'] ?? '') ? json_decode($s['config'], true) : ($s['config'] ?? []);
        $typeLabel = match($s['section_type']) {
            'banner_carousel' => 'Banner Carousel',
            'category_strip' => 'Category Strip',
            'product_grid' => 'Product Grid',
            default => $s['section_type'],
        };
    ?>
    <div class="list-card">
        <div class="list-card-left">
            <span class="list-card-order"><?= $s['sort_order'] ?></span>
            <div>
                <strong><?= e($s['title'] ?? $typeLabel) ?></strong>
                <p class="text-muted text-sm"><?= e($s['section_type']) ?></p>
            </div>
        </div>
        <div class="list-card-actions">
            <form method="POST" action="/admin/homepage-layout" style="display:inline">
                <input type="hidden" name="layout_action" value="toggle">
                <input type="hidden" name="section_id" value="<?= e($s['id']) ?>">
                <input type="hidden" name="current_enabled" value="<?= $s['is_enabled'] ? '1' : '0' ?>">
                <button type="submit" class="btn-sm <?= $s['is_enabled'] ? 'btn-success' : 'btn-outline' ?>">
                    <?= $s['is_enabled'] ? 'On' : 'Off' ?>
                </button>
            </form>
            <a href="/admin/homepage-layout/edit?id=<?= e($s['id']) ?>" class="btn-sm">Edit</a>
            <form method="POST" action="/admin/homepage-layout" style="display:inline" onsubmit="return confirm('Remove?')">
                <input type="hidden" name="layout_action" value="delete">
                <input type="hidden" name="section_id" value="<?= e($s['id']) ?>">
                <button type="submit" class="btn-sm btn-danger">Remove</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
