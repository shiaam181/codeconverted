<?php
/**
 * Tenant Admin Dashboard
 * Shows stats, UPI info, and toggle for showing/hiding default products
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantSlug = $tenant['slug'] ?? '';
$tenantId = $tenant['id'] ?? '';
$tenantName = $tenant['name'] ?? 'Store';

// Auth guard
if (empty($_SESSION['tenant_admin_token']) || ($_SESSION['tenant_admin_slug'] ?? '') !== $tenantSlug) {
    redirect("/t/{$tenantSlug}/admin/login");
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tenant_action'] ?? '') === 'logout') {
    unset($_SESSION['tenant_admin_token'], $_SESSION['tenant_admin_user_id'], $_SESSION['tenant_admin_slug']);
    redirect("/t/{$tenantSlug}/admin/login");
}

// Handle toggle show_default_products
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tenant_action'] ?? '') === 'toggle_defaults') {
    $currentVal = ($tenant['show_default_products'] ?? true) ? true : false;
    supabase_query('tenants', ['id' => 'eq.' . $tenantId], 'PATCH', [
        'show_default_products' => !$currentVal,
    ]);
    flash('success', !$currentVal ? 'Default products now visible on storefront' : 'Default products hidden from storefront');
    redirect("/t/{$tenantSlug}/admin");
}

// Handle toggle delivery charges
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tenant_action'] ?? '') === 'toggle_delivery') {
    $currentVal = ($tenant['delivery_charges_enabled'] ?? false) ? true : false;
    supabase_query('tenants', ['id' => 'eq.' . $tenantId], 'PATCH', [
        'delivery_charges_enabled' => !$currentVal,
    ]);
    flash('success', !$currentVal ? 'Delivery charges enabled' : 'Delivery charges disabled');
    redirect("/t/{$tenantSlug}/admin");
}

// Get fresh tenant data
$tenant = get_tenant_by_slug($tenantSlug);
$_SESSION['current_tenant'] = $tenant;
$stats = get_tenant_stats($tenantId);

// Subscription info
$expiresAt = $tenant['expires_at'] ?? null;
$isExpired = $expiresAt && strtotime($expiresAt) <= time();
$daysLeft = $expiresAt ? max(0, (int) ceil((strtotime($expiresAt) - time()) / 86400)) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Dashboard — <?= e($tenantName) ?></title>
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
.ta-main{padding:16px;max-width:800px}
.ta-main h2{font-size:1.25rem;font-weight:700;margin-bottom:4px}
.ta-main .sub{font-size:13px;color:#666;margin-bottom:1rem}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:1rem}
.stat-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;display:flex;align-items:center;gap:12px}
.stat-card .icon{font-size:1.5rem}
.stat-card .label{font-size:11px;color:#666}
.stat-card .value{font-size:1.25rem;font-weight:700}
.info-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:12px}
.info-card h3{font-size:14px;font-weight:600;margin-bottom:4px}
.info-card p{font-size:12px;color:#666}
.sub-banner{border-radius:10px;padding:14px 16px;margin-bottom:1rem;font-size:13px;display:flex;align-items:center;gap:8px}
.sub-banner.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.sub-banner.warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.sub-banner.expired{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.sub-banner.none{background:#f3f4f6;border:1px solid #e5e7eb;color:#374151}
.toggle-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
.toggle-btn{padding:6px 16px;border-radius:6px;border:1px solid #d0d0d0;background:#fff;font-size:12px;font-weight:500;cursor:pointer}
.toggle-btn.on{background:#2874f0;color:#fff;border-color:#2874f0}
.alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem}
</style>
</head>
<body>
<header class="ta-header">
    <a href="/t/<?= e($tenantSlug) ?>" style="font-size:12px;color:#666;text-decoration:none">🏪 View store</a>
    <h1>Dashboard</h1>
    <form method="POST" style="display:inline">
        <input type="hidden" name="tenant_action" value="logout">
        <button type="submit" style="font-size:12px;color:#666;border:none;background:none;cursor:pointer">🚪 Sign out</button>
    </form>
</header>

<nav class="ta-nav">
    <a href="/t/<?= e($tenantSlug) ?>/admin" class="active">Dashboard</a>
    <a href="/t/<?= e($tenantSlug) ?>/admin/products">Products</a>
    <a href="/t/<?= e($tenantSlug) ?>/admin/upi">UPI Payment</a>
    <a href="/t/<?= e($tenantSlug) ?>" target="_blank">Preview Store</a>
</nav>

<main class="ta-main">
    <?php if ($msg = get_flash('success')): ?>
    <div class="alert-success"><?= e($msg) ?></div>
    <?php endif; ?>

    <h2>Welcome, <?= e($tenantName) ?></h2>
    <p class="sub">Manage your products and your UPI payment ID.</p>
    
    <!-- Subscription Status -->
    <?php if ($isExpired): ?>
    <div class="sub-banner expired">⚠️ Your subscription has expired. Contact the store owner to renew.</div>
    <?php elseif ($daysLeft !== null && $daysLeft <= 7): ?>
    <div class="sub-banner warn">⏰ Your subscription expires in <?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?> (<?= date('M d, Y', strtotime($expiresAt)) ?>)</div>
    <?php elseif ($daysLeft !== null): ?>
    <div class="sub-banner ok">✅ Subscription active — <?= $daysLeft ?> days remaining (expires <?= date('M d, Y', strtotime($expiresAt)) ?>)</div>
    <?php else: ?>
    <div class="sub-banner none">ℹ️ No expiry set — store runs without a time limit</div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="stat-grid">
        <div class="stat-card">
            <span class="icon">📦</span>
            <div><p class="label">Products</p><p class="value"><?= $stats['products'] ?></p></div>
        </div>
        <div class="stat-card">
            <span class="icon">🛒</span>
            <div><p class="label">Orders</p><p class="value"><?= $stats['orders'] ?></p></div>
        </div>
        <div class="stat-card">
            <span class="icon">📱</span>
            <div><p class="label">UPI ID</p><p class="value" style="font-size:12px"><?= e($tenant['upi_id'] ?? 'Not set') ?></p></div>
        </div>
    </div>
    
    <!-- Show Default Products Toggle -->
    <div class="info-card">
        <div class="toggle-row">
            <div>
                <h3>Show default products</h3>
                <p>When off, products synced from the master catalog are hidden from your storefront. Your own products are unaffected.</p>
            </div>
            <form method="POST" style="display:inline">
                <input type="hidden" name="tenant_action" value="toggle_defaults">
                <button type="submit" class="toggle-btn <?= ($tenant['show_default_products'] ?? true) ? 'on' : '' ?>">
                    <?= ($tenant['show_default_products'] ?? true) ? 'ON' : 'OFF' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Delivery Charges Toggle -->
    <div class="info-card">
        <div class="toggle-row">
            <div>
                <h3>Delivery charges</h3>
                <p>When on, ₹40 delivery is added to carts under ₹500. When off, customers pay only the product price.</p>
            </div>
            <form method="POST" style="display:inline">
                <input type="hidden" name="tenant_action" value="toggle_delivery">
                <button type="submit" class="toggle-btn <?= ($tenant['delivery_charges_enabled'] ?? false) ? 'on' : '' ?>">
                    <?= ($tenant['delivery_charges_enabled'] ?? false) ? 'ON' : 'OFF' ?>
                </button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
