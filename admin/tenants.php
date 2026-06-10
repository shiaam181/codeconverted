<?php
/**
 * Admin Customer Stores (Tenants) Management
 * Features: Primary domain, Create store, Toggle, Delete, Reset password, Set expiry, Renew
 * Product seeding from master catalog on creation
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['tenant_action'] ?? '';
    
    if ($action === 'save_domain') {
        $domain = trim($_POST['primary_domain'] ?? '');
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        upsert_app_setting('primary_domain', $domain);
        flash('success', 'Primary domain saved');
        redirect('/admin/tenants');
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
        $upiId = trim($_POST['upi_id'] ?? '') ?: null;
        
        if (!$name || !$slug) {
            flash('error', 'Name and slug are required');
            redirect('/admin/tenants');
        }
        
        // Check if slug already exists
        $existing = supabase_query('tenants', ['slug' => 'eq.' . $slug, 'select' => 'id']);
        if (!empty($existing) && !isset($existing['error'])) {
            flash('error', 'Slug already in use');
            redirect('/admin/tenants');
        }
        
        $payload = [
            'name' => $name,
            'slug' => $slug,
            'upi_id' => $upiId,
            'is_active' => true,
            'show_default_products' => true,
        ];
        
        $expiryDays = $_POST['expiry_days'] ?? '';
        if ($expiryDays && is_numeric($expiryDays)) {
            $payload['expires_at'] = date('c', strtotime("+{$expiryDays} days"));
        }
        
        // Create tenant admin account if email/password provided
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $ownerId = null;
        
        if ($adminEmail && strlen($adminPassword) >= 6) {
            // Use synthetic email format: username.slug@tenant.local (matching React version)
            $username = explode('@', $adminEmail)[0];
            $tenantEmail = strtolower($username) . '.' . strtolower($slug) . '@tenant.local';
            $authResult = supabase_auth_signup($tenantEmail, $adminPassword);
            if (!isset($authResult['error']) && !empty($authResult['id'])) {
                $ownerId = $authResult['id'];
            } elseif (!isset($authResult['error']) && !empty($authResult['user']['id'])) {
                $ownerId = $authResult['user']['id'];
            }
            if ($ownerId) {
                $payload['owner_user_id'] = $ownerId;
            }
        }
        
        $tenantResult = supabase_query('tenants', [], 'POST', $payload);
        
        if (!empty($tenantResult) && !isset($tenantResult['error'])) {
            $newTenant = is_array($tenantResult[0] ?? null) ? $tenantResult[0] : $tenantResult;
            $newTenantId = $newTenant['id'] ?? null;
            
            // Seed products from master catalog (copy all master products to this tenant)
            $copySeed = isset($_POST['copy_seed']) ? true : true; // default: always seed
            if ($newTenantId && $copySeed) {
                seed_tenant_products($newTenantId, $slug);
            }
            
            flash('success', "Store \"{$name}\" created" . ($ownerId ? " with admin account" : ""));
        } else {
            // If tenant creation failed and we created a user, note it
            flash('error', 'Failed to create store. ' . ($tenantResult['message'] ?? ''));
        }
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
        $tenantId = $_POST['tenant_id'];
        // Delete tenant products and images first
        $tenantProducts = supabase_query('products', ['tenant_id' => 'eq.' . $tenantId, 'select' => 'id']);
        if (is_array($tenantProducts) && !isset($tenantProducts['error'])) {
            foreach ($tenantProducts as $p) {
                supabase_query('product_images', ['product_id' => 'eq.' . $p['id']], 'DELETE');
            }
            supabase_query('products', ['tenant_id' => 'eq.' . $tenantId], 'DELETE');
        }
        // Delete orders for this tenant
        supabase_query('orders', ['tenant_id' => 'eq.' . $tenantId], 'DELETE');
        // Delete the tenant
        supabase_query('tenants', ['id' => 'eq.' . $tenantId], 'DELETE');
        flash('success', 'Store deleted');
        redirect('/admin/tenants');
    }
    
    if ($action === 'set_expiry' && !empty($_POST['tenant_id'])) {
        $expiryDays = $_POST['expiry_days'] ?? '';
        $expiresAt = null;
        if ($expiryDays && is_numeric($expiryDays)) {
            // Extend from later of (now, current expiry) so renewal adds time
            $tenantData = supabase_query('tenants', ['id' => 'eq.' . $_POST['tenant_id'], 'select' => 'expires_at']);
            $currentExpiry = (!empty($tenantData) && !isset($tenantData['error'])) ? ($tenantData[0]['expires_at'] ?? null) : null;
            $fromTime = time();
            if ($currentExpiry && strtotime($currentExpiry) > $fromTime) {
                $fromTime = strtotime($currentExpiry);
            }
            $expiresAt = date('c', $fromTime + ((int)$expiryDays * 86400));
        }
        supabase_query('tenants', ['id' => 'eq.' . $_POST['tenant_id']], 'PATCH', [
            'expires_at' => $expiresAt,
        ]);
        flash('success', 'Expiry updated');
        redirect('/admin/tenants');
    }

    if ($action === 'reset_password' && !empty($_POST['tenant_id'])) {
        $newPassword = trim($_POST['new_password'] ?? '');
        if (strlen($newPassword) < 6) {
            flash('error', 'Password must be at least 6 characters');
            redirect('/admin/tenants');
        }
        // Get the tenant's owner_user_id
        $tenantData = supabase_query('tenants', ['id' => 'eq.' . $_POST['tenant_id'], 'select' => 'owner_user_id']);
        $ownerUserId = (!empty($tenantData) && !isset($tenantData['error'])) ? ($tenantData[0]['owner_user_id'] ?? null) : null;
        
        if ($ownerUserId) {
            // Use Supabase Admin API to update password (requires service_role key)
            $serviceKey = getenv('SUPABASE_SERVICE_ROLE_KEY');
            if ($serviceKey) {
                $result = supabase_admin_update_user($ownerUserId, ['password' => $newPassword]);
                if (!isset($result['error'])) {
                    flash('success', 'Password updated');
                } else {
                    flash('error', 'Failed to reset password: ' . ($result['message'] ?? 'Unknown error'));
                }
            } else {
                flash('error', 'Password reset requires SUPABASE_SERVICE_ROLE_KEY in .env');
            }
        } else {
            flash('error', 'Tenant has no admin account. Create one first.');
        }
        redirect('/admin/tenants');
    }
}

$tenants = get_admin_tenants();
$primaryDomain = get_app_setting('primary_domain') ?? '';

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
            <p class="text-muted">Create and manage your customer storefronts. Each customer gets their own URL and login.</p>
        </div>
        <a href="/admin/tenants/new" class="btn-primary">+ New customer store</a>
    </div>
</div>

<!-- Primary Domain -->
<div class="admin-card">
    <h3>Primary domain</h3>
    <p class="text-muted text-sm">Used for every customer store link (Store + Admin). Leave empty to use this preview URL.</p>
    <form method="POST" action="/admin/tenants" class="domain-form">
        <input type="hidden" name="tenant_action" value="save_domain">
        <div class="input-with-btn">
            <input type="text" name="primary_domain" value="<?= e($primaryDomain) ?>" class="form-input" placeholder="paytmtest.lovable.app">
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</div>

<!-- Tenant Cards -->
<?php if (empty($tenants)): ?>
<div class="empty-state"><p>No customer stores yet.</p></div>
<?php else: ?>
<div class="tenant-grid">
    <?php foreach ($tenants as $t): 
        // URL logic: if primary_domain is set, use it. Otherwise use the current server origin.
        // This ensures stores work locally without pointing to an external hosted domain.
        if ($primaryDomain) {
            $base = "https://{$primaryDomain}";
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
            $base = "{$scheme}://{$host}";
        }
        $storeUrl = "{$base}/t/{$t['slug']}";
        $adminUrl = "{$base}/t/{$t['slug']}/admin/login";
        $isExpired = !empty($t['expires_at']) && strtotime($t['expires_at']) <= time();
        if (empty($t['expires_at'])) {
            $expiryText = 'No expiry';
        } elseif ($isExpired) {
            $expiryText = 'Expired';
        } else {
            $daysLeft = max(0, (int) ceil((strtotime($t['expires_at']) - time()) / 86400));
            $hoursLeft = max(0, (int) ceil((strtotime($t['expires_at']) - time()) / 3600) % 24);
            $expiryText = "{$daysLeft}d {$hoursLeft}h left";
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
                <button type="submit" class="toggle-pill <?= $t['is_active'] ? 'active' : '' ?>">
                    <?= $t['is_active'] ? '● Active' : '○ Inactive' ?>
                </button>
            </form>
        </div>
        
        <div class="tenant-card-urls">
            <div class="url-row">
                <span class="url-label">Store:</span>
                <code class="url-value"><?= e($storeUrl) ?></code>
                <button type="button" class="btn-copy" onclick="navigator.clipboard.writeText('<?= e($storeUrl) ?>');this.textContent='✓';setTimeout(()=>this.textContent='📋',1000)">📋</button>
            </div>
            <div class="url-row">
                <span class="url-label">Admin:</span>
                <code class="url-value"><?= e($adminUrl) ?></code>
                <button type="button" class="btn-copy" onclick="navigator.clipboard.writeText('<?= e($adminUrl) ?>');this.textContent='✓';setTimeout(()=>this.textContent='📋',1000)">📋</button>
            </div>
        </div>
        
        <div class="tenant-card-expiry <?= $isExpired ? 'expired' : '' ?>">
            🕐 <?= e($expiryText) ?>
            <?php if (!empty($t['expires_at'])): ?>
            · <?= date('m/d/Y', strtotime($t['expires_at'])) ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($t['upi_id'])): ?>
        <p class="text-muted text-sm">UPI: <?= e($t['upi_id']) ?></p>
        <?php endif; ?>
        
        <div class="tenant-card-actions">
            <a href="<?= e($storeUrl) ?>" target="_blank" class="btn-sm btn-outline">🔗 Open store</a>
            <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('renewModal_<?= e($t['id']) ?>').style.display='flex'">🕐 Renew / change</button>
            <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('pwModal_<?= e($t['id']) ?>').style.display='flex'">🔑 Reset password</button>
            <form method="POST" action="/admin/tenants" style="display:inline" onsubmit="return confirm('Delete this store and ALL its data?')">
                <input type="hidden" name="tenant_action" value="delete">
                <input type="hidden" name="tenant_id" value="<?= e($t['id']) ?>">
                <button type="submit" class="btn-sm btn-danger">🗑️ Delete</button>
            </form>
        </div>
    </div>

    <!-- Renew Modal -->
    <div class="modal-overlay" id="renewModal_<?= e($t['id']) ?>" style="display:none">
        <div class="modal-content modal-sm">
            <div class="modal-header"><h3>Renew / change — <?= e($t['name']) ?></h3><button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="modal-close">✕</button></div>
            <form method="POST" action="/admin/tenants">
                <input type="hidden" name="tenant_action" value="set_expiry">
                <input type="hidden" name="tenant_id" value="<?= e($t['id']) ?>">
                <div class="form-group">
                    <label>Extend by:</label>
                    <select name="expiry_days" class="form-select">
                        <option value="7">+7 days</option>
                        <option value="30" selected>+30 days</option>
                        <option value="90">+90 days</option>
                        <option value="180">+6 months</option>
                        <option value="365">+1 year</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal-overlay" id="pwModal_<?= e($t['id']) ?>" style="display:none">
        <div class="modal-content modal-sm">
            <div class="modal-header"><h3>Reset password — <?= e($t['name']) ?></h3><button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="modal-close">✕</button></div>
            <form method="POST" action="/admin/tenants">
                <input type="hidden" name="tenant_action" value="reset_password">
                <input type="hidden" name="tenant_id" value="<?= e($t['id']) ?>">
                <div class="form-group">
                    <label>New password</label>
                    <input type="text" name="new_password" class="form-input" required minlength="6" placeholder="Min 6 characters">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
