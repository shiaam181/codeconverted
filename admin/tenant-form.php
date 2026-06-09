<?php
/**
 * Admin Create Tenant Form
 */
require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2>New Customer Store</h2>
        <a href="/admin/tenants" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/tenants" class="admin-form">
    <input type="hidden" name="tenant_action" value="create">
    
    <div class="form-grid">
        <div class="form-group">
            <label>Store Name *</label>
            <input type="text" name="name" required class="form-input" placeholder="My Customer Store">
        </div>
        <div class="form-group">
            <label>Slug (URL) *</label>
            <input type="text" name="slug" required class="form-input" placeholder="customer1" pattern="[a-z0-9-]+">
            <p class="form-hint">Store URL: /t/slug · Letters, numbers, hyphens only</p>
        </div>
        <div class="form-group">
            <label>Subscription (days)</label>
            <select name="expiry_days" class="form-select">
                <option value="">No expiry</option>
                <option value="1">1 day</option>
                <option value="7">7 days</option>
                <option value="30" selected>30 days</option>
                <option value="90">90 days</option>
                <option value="180">6 months</option>
                <option value="365">1 year</option>
            </select>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/tenants" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary">Create Store</button>
    </div>
</form>

<?php require __DIR__ . '/layout-end.php'; ?>
