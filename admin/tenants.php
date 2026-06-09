<?php
/**
 * Admin Customer Stores (Tenants) Management
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['tenant_action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
        
        if (!$name || !$slug) {
            flash('error', 'Name and slug are required');
            redirect('/admin/tenants');
        }
        
        $payload = [
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ];
        
        // Set expiry if provided
        $expiryDays = $_POST['expiry_days'] ?? '';
        if ($expiryDays && is_numeric($expiryDays)) {
            $payload['expires_at'] = date('c', strtotime("+{$expiryDays} days"));
        }
        
        supabase_query('tenants', [], 'POST', $payload);
        flash('success', "Store \"{$name}\" created");
        redirect('/admin/tenants');
    }
    
    if ($action === 'toggle' && !empty($_POST['tenant_id'])) {
        $currentActive = $_POST['current_active'] === '1';
        supabase_query('tenants', ['id' => 'eq.' . $_POST['tenant_id']], 'PATCH', [
            'is_active' => !$currentActive,
        ]);
        flash('success', 'Store status updated');
        redirect('/admin/tenants');
    }
    
    if ($action === 'delete' && !empty($_POST['tenant_id'])) {
        supabase_query('tenants', ['id' => 'eq.' . $_POST['tenant_id']], 'DELETE');
        flash('success', 'Store deleted');
        redirect('/admin/tenants');
    }
    
    if ($action === 'set_expiry' && !empty($_POST['tenant_id'])) {
        $expiryDays = $_POST['expiry_days'] ?? '';
        $expiresAt = null;
        if ($expiryDays && is_numeric($expiryDays)) {
            $expiresAt = date('c', strtotime("+{$expiryDays} days"));
        }
        supabase_query('tenants', ['id' => 'eq.' . $_POST['tenant_id']], 'PATCH', [
            'expires_at' => $expiresAt,
        ]);
        flash('success', 'Expiry updated');
        redirect('/admin/tenants');
    }
}

$tenants = get_admin_tenants();

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
            <h2>Customer Stores</h2>
            <p class="text-muted">Create and manage customer storefronts. Each gets their own URL.</p>
        </div>
        <a href="/admin/tenants/new" class="btn-primary">+ New Store</a>
    </div>
</div>

<?php if (empty($tenants)): ?>
<div class="empty-state"><p>No customer stores yet.</p></div>
<?php else: ?>
<div class="tenant-grid">
    <?php foreach ($tenants as $t): 
        $storeUrl = "/t/{$t['slug']}";
        $adminUrl = "/t/{$t['slug']}/admin/login";
        $isExpired = !empty($t['expires_at']) && strtotime($t['expires_at']) <= time();
        $expiryText = '';
        if (empty($t['expires_at'])) {
            $expiryText = 'No expiry';
        } elseif ($isExpired) {
            $expiryText = 'Expired';
        } else {
            $daysLeft = max(0, (int) ((strtotime($t['expires_at']) - time()) / 86400));
            $expiryText = "{$daysLeft} days left";
        }
    ?>
    <div class="tenant-card">
        <div class="tenant-card-header">
            <div>
                <strong><?= e($t['name']) ?></strong>
                <p class="text-muted text-sm">/<?= e($t['slug']) ?></p>
            </div>
            <form method="POST" action="/admin/tenants" style="display:inline">
                <input type="hidden" name="tenant_action" value="toggle">
                <input type="hidden" name="tenant_id" value="<?= e($t['id']) ?>">
                <input type="hidden" name="current_active" value="<?= $t['is_active'] ? '1' : '0' ?>">
                <button type="submit" class="btn-sm <?= $t['is_active'] ? 'btn-success' : 'btn-outline' ?>">
                    <?= $t['is_active'] ? 'Active' : 'Inactive' ?>
                </button>
            </form>
        </div>
        
        <div class="tenant-card-urls">
            <p class="text-sm"><strong>Store:</strong> <code><?= e($storeUrl) ?></code></p>
            <p class="text-sm"><strong>Admin:</strong> <code><?= e($adminUrl) ?></code></p>
        </div>
        
        <div class="tenant-card-expiry <?= $isExpired ? 'expired' : '' ?>">
            ⏱ <?= e($expiryText) ?>
            <?php if (!empty($t['expires_at'])): ?>
            · <?= date('d M Y', strtotime($t['expires_at'])) ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($t['upi_id'])): ?>
        <p class="text-muted text-sm">UPI: <?= e($t['upi_id']) ?></p>
        <?php endif; ?>
        
        <div class="tenant-card-actions">
            <a href="<?= e($storeUrl) ?>" target="_blank" class="btn-sm btn-outline">Open Store</a>
            <form method="POST" action="/admin/tenants" style="display:inline">
                <input type="hidden" name="tenant_action" value="set_expiry">
                <input type="hidden" name="tenant_id" value="<?= e($t['id']) ?>">
                <select name="expiry_days" class="form-select-sm" onchange="this.form.submit()">
                    <option value="">Set expiry...</option>
                    <option value="7">+7 days</option>
                    <option value="30">+30 days</option>
                    <option value="90">+90 days</option>
                    <option value="365">+1 year</option>
                </select>
            </form>
            <form method="POST" action="/admin/tenants" style="display:inline" onsubmit="return confirm('Delete this store?')">
                <input type="hidden" name="tenant_action" value="delete">
                <input type="hidden" name="tenant_id" value="<?= e($t['id']) ?>">
                <button type="submit" class="btn-sm btn-danger">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
