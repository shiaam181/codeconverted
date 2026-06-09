<?php
/**
 * Admin Dashboard
 */

$stats = get_admin_stats();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingAction = $_POST['setting_action'] ?? '';
    if ($settingAction === 'grid_cols') {
        upsert_app_setting('storefront_grid_cols', $_POST['grid_cols'] ?? '2');
        flash('success', "Storefront set to {$_POST['grid_cols']} columns on mobile");
        redirect('/admin');
    }
    if ($settingAction === 'delivery_charges') {
        $enabled = isset($_POST['delivery_enabled']) ? 'true' : 'false';
        upsert_app_setting('delivery_charges_enabled', $enabled);
        flash('success', $enabled === 'true' ? 'Delivery charges enabled' : 'Delivery charges disabled');
        redirect('/admin');
    }
}

$gridCols = get_app_setting('storefront_grid_cols') ?? '2';
$deliveryEnabled = get_app_setting('delivery_charges_enabled') === 'true';

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h2>Dashboard</h2>
    <p class="text-muted">Manage products, images, banners, logo, layout, and orders from one place.</p>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-info"><p class="stat-label">Products</p><p class="stat-value"><?= $stats['products'] ?></p></div><span class="stat-icon">📦</span></div>
    <div class="stat-card"><div class="stat-info"><p class="stat-label">Categories</p><p class="stat-value"><?= $stats['categories'] ?></p></div><span class="stat-icon">📁</span></div>
    <div class="stat-card"><div class="stat-info"><p class="stat-label">Banners</p><p class="stat-value"><?= $stats['banners'] ?></p></div><span class="stat-icon">🖼️</span></div>
    <div class="stat-card"><div class="stat-info"><p class="stat-label">Orders</p><p class="stat-value"><?= $stats['orders'] ?></p></div><span class="stat-icon">🛒</span></div>
    <div class="stat-card stat-wide"><div class="stat-info"><p class="stat-label">Total Revenue (all orders)</p><p class="stat-value large">₹<?= number_format($stats['revenue'], 0, '.', ',') ?></p></div></div>
</div>

<div class="admin-section"><h3>Store Controls</h3>
<div class="actions-grid">
    <a href="/admin/products" class="action-card"><span class="action-icon">➕</span><div><strong>Add product</strong><p>Create a product with photos, price, stock.</p></div></a>
    <a href="/admin/products" class="action-card"><span class="action-icon">📦</span><div><strong>Bulk add products</strong><p>Open Products and paste CSV rows.</p></div></a>
    <a href="/admin/banners" class="action-card"><span class="action-icon">🖼️</span><div><strong>Change hero images</strong><p>Upload homepage hero, promo banners.</p></div></a>
    <a href="/admin/theme" class="action-card"><span class="action-icon">🎨</span><div><strong>Change logo</strong><p>Update store name, logo, favicon, colors.</p></div></a>
    <a href="/admin/homepage-layout" class="action-card"><span class="action-icon">📐</span><div><strong>Arrange homepage</strong><p>Control which sections show and order.</p></div></a>
    <a href="/?preview=1" target="_blank" class="action-card"><span class="action-icon">🏪</span><div><strong>View store</strong><p>Open the live customer-facing page.</p></div></a>
</div></div>

<div class="admin-section"><h3>Storefront layout</h3>
<div class="settings-grid">
    <div class="setting-card">
        <h4>Products per row (mobile)</h4>
        <p class="text-muted text-sm">Choose how many product cards appear per row. Larger screens scale up automatically.</p>
        <form method="POST" action="/admin" class="setting-form">
            <input type="hidden" name="setting_action" value="grid_cols">
            <select name="grid_cols" class="form-select" onchange="this.form.submit()">
                <option value="2" <?= $gridCols === '2' ? 'selected' : '' ?>>2 columns</option>
                <option value="3" <?= $gridCols === '3' ? 'selected' : '' ?>>3 columns</option>
                <option value="4" <?= $gridCols === '4' ? 'selected' : '' ?>>4 columns</option>
            </select>
        </form>
    </div>
    <div class="setting-card">
        <h4>Delivery charges</h4>
        <p class="text-muted text-sm">When on, ₹40 delivery is added to carts under ₹500. When off, customers pay only the product price.</p>
        <form method="POST" action="/admin" class="setting-form">
            <input type="hidden" name="setting_action" value="delivery_charges">
            <label class="toggle-label">
                <input type="checkbox" name="delivery_enabled" <?= $deliveryEnabled ? 'checked' : '' ?> onchange="this.form.submit()">
                <span><?= $deliveryEnabled ? 'Enabled' : 'Disabled' ?></span>
            </label>
        </form>
    </div>
</div></div>

<?php require __DIR__ . '/layout-end.php'; ?>
